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
    $stmt_m = $conn->prepare("SELECT id FROM merchants WHERE user_id = ?");
    $stmt_m->bind_param("i", $user['id']);
    $stmt_m->execute();
    $merchantData = $stmt_m->get_result()->fetch_assoc();
    $merchantId = $merchantData['id'] ?? 0;
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
        
        // 2. Create a temporary 'product' for this payment link to reuse the existing checkout flow
        $desc = !empty($link['description']) ? $link['description'] : "Payment for " . $link['title'];
        $img = !empty($link['image']) ? $link['image'] : 'default_product.png';
        $stmt_prod = $conn->prepare("INSERT INTO products (merchant_id, name, description, price, image, is_payment_link) VALUES (?, ?, ?, ?, ?, TRUE)");
        $stmt_prod->bind_param("issds", $link['merchant_id'], $link['title'], $desc, $link['amount'], $img);
        $stmt_prod->execute();
        $newProductId = $conn->insert_id;
        
        // 3. Optional: mark link as paid or pending here, but for now we leave it active until checkout finishes.
        
        // 4. Redirect customer to the standard BNPL checkout flow
        header("Location: ../views/user/place_order.php?id=" . $newProductId);
        exit;
    }
}

header("Location: ../views/merchant/dashboard.php");
exit;
