<?php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!isLoggedIn()) {
    die("Unauthorized Access: Please login first.");
}

$user = currentUser();
$db = new Database();
$conn = $db->connect();

$merchantId = 0;
// If the user is a merchant, fetch their merchant ID (needed for create/delete)
if ($user['role'] === 'merchant') {
    $merchantData = ensureMerchantRecord($conn);
    $merchantId = $merchantData['id'];
}

// Security Gate: Only merchants can create or delete
if (($action === 'create' || $action === 'delete') && $user['role'] !== 'merchant') {
    die("Unauthorized Access: Only merchants can manage payment links.");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        
        if (empty($title) || $amount <= 0) {
            header("Location: ../views/merchant/payment_links.php?error=Invalid inputs. Title is required and amount must be greater than zero.");
            exit;
        }

        // Handle Optional Image Upload
        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            $targetDir = __DIR__ . "/../uploads/products/";
            if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
            
            $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = "link_" . time() . "_" . $merchantId . "." . $fileExt;
            move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $imageName);
        }

        // Generate a random 16-character secure hash
        $hash = bin2hex(random_bytes(8));

        $stmt = $conn->prepare("INSERT INTO payment_links (merchant_id, link_hash, title, description, image, amount, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("issssd", $merchantId, $hash, $title, $description, $imageName, $amount);
        
        if ($stmt->execute()) {
            header("Location: ../views/merchant/payment_links.php?success=Payment Link generated successfully.");
        } else {
            header("Location: ../views/merchant/payment_links.php?error=Failed to generate link.");
        }
        exit;
    }
    
    if ($action === 'delete') {
        $link_id = intval($_POST['link_id'] ?? 0);
        
        $stmt = $conn->prepare("DELETE FROM payment_links WHERE id = ? AND merchant_id = ?");
        $stmt->bind_param("ii", $link_id, $merchantId);
        
        if ($stmt->execute()) {
            header("Location: ../views/merchant/payment_links.php?success=Payment Link deleted.");
        } else {
            header("Location: ../views/merchant/payment_links.php?error=Failed to delete link.");
        }
        exit;
    }
    if ($action === 'publish') {
        $link_id = intval($_POST['link_id'] ?? 0);
        
        $stmt = $conn->prepare("SELECT * FROM payment_links WHERE id = ? AND merchant_id = ?");
        $stmt->bind_param("ii", $link_id, $merchantId);
        $stmt->execute();
        $link = $stmt->get_result()->fetch_assoc();
        
        if ($link) {
            $desc = !empty($link['description']) ? $link['description'] : $link['title'];
            $img = !empty($link['image']) ? $link['image'] : 'default_product.png';
            // Insert as a NORMAL product (is_payment_link = FALSE)
            $stmt_prod = $conn->prepare("INSERT INTO products (merchant_id, name, description, price, image, is_payment_link) VALUES (?, ?, ?, ?, ?, FALSE)");
            $stmt_prod->bind_param("issds", $link['merchant_id'], $link['title'], $desc, $link['amount'], $img);
            
            if ($stmt_prod->execute()) {
                header("Location: ../views/merchant/payment_links.php?success=Successfully published to public catalog.");
            } else {
                header("Location: ../views/merchant/payment_links.php?error=Failed to publish to catalog.");
            }
        } else {
            header("Location: ../views/merchant/payment_links.php?error=Link not found.");
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'checkout') {
        $hash = $_GET['hash'] ?? '';
        
        // 1. Fetch the payment link
        $stmt = $conn->prepare("SELECT * FROM payment_links WHERE link_hash = ? AND status = 'active'");
        $stmt->bind_param("s", $hash);
        $stmt->execute();
        $link = $stmt->get_result()->fetch_assoc();
        
        if (!$link) {
            die("Link expired or invalid.");
        }
        
        // Prevent a merchant from checking out their own links
        if ($user['role'] === 'merchant' && $link['merchant_id'] == $merchantId) {
            die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                    <h1 style='color:#e74c3c;'>Action Not Allowed</h1>
                    <p>As a merchant, you cannot checkout using your own payment links.</p>
                    <a href='../views/merchant/dashboard.php'>Return to Dashboard</a>
                 </div>");
        }
        
        // 2. Reuse existing dummy product or create a new one for this payment link
        $stmt_check = $conn->prepare("SELECT id FROM products WHERE merchant_id = ? AND name = ? AND price = ? AND is_payment_link = TRUE LIMIT 1");
        $stmt_check->bind_param("isd", $link['merchant_id'], $link['title'], $link['amount']);
        $stmt_check->execute();
        $existing = $stmt_check->get_result()->fetch_assoc();
        
        if ($existing) {
            $newProductId = $existing['id'];
        } else {
            $desc = !empty($link['description']) ? $link['description'] : "Payment for " . $link['title'];
            $img = !empty($link['image']) ? $link['image'] : 'default_product.png';
            $stmt_prod = $conn->prepare("INSERT INTO products (merchant_id, name, description, price, image, is_payment_link) VALUES (?, ?, ?, ?, ?, TRUE)");
            $stmt_prod->bind_param("issds", $link['merchant_id'], $link['title'], $desc, $link['amount'], $img);
            $stmt_prod->execute();
            $newProductId = $conn->insert_id;
        }
        
        // 3. Optional: mark link as paid or pending here, but for now we leave it active until checkout finishes.
        
        // 4. Redirect customer to the standard BNPL checkout flow
        header("Location: ../views/user/place_order.php?id=" . $newProductId);
        exit;
    }
}

header("Location: ../views/merchant/dashboard.php");
exit;
