<?php
session_start();
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/db.php";

// Protect: ONLY Merchants allowed!
requireRole("merchant");

$db = new Database();
$conn = $db->connect();

// 1. UNIVERSAL FIX: Fetch the actual Merchant Profile ID and Status for all actions
$userId = currentUser()['id'];
$stmt_m = $conn->prepare("SELECT id, status FROM merchants WHERE user_id = ?");
$stmt_m->bind_param("i", $userId);
$stmt_m->execute();
$merchantData = $stmt_m->get_result()->fetch_assoc();

if (!$merchantData) {
    // This shouldn't happen with the new registration flow, but keep as fallback
    header("Location: ../views/merchant/dashboard.php?error=no_profile");
    exit;
}

$merchantId = $merchantData['id'];
$merchantStatus = $merchantData['status'];

// SECURITY GATE: Block all management actions if merchant is not approved
if ($merchantStatus !== 'approved') {
    header("Location: ../views/merchant/dashboard.php?error=pending_approval");
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === "add" && $_SERVER["REQUEST_METHOD"] === "POST") {
    
    $name        = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $price       = $_POST["price"];
    $file        = $_FILES["product_image"];

    // 1. Basic Validation
    if (empty($name) || empty($price) || empty($file["name"])) {
        die("Error: Please fill all required fields.");
    }

    // 2. Handle Image Upload
    $targetDir = __DIR__ . "/../uploads/products/";
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileExt = pathinfo($file["name"], PATHINFO_EXTENSION);
    $fileName = "prod_" . time() . "_" . $merchantId . "." . $fileExt;
    $targetFile = $targetDir . $fileName;

    // Check if it's a real image
    if (!getimagesize($file["tmp_name"])) {
        die("Error: Selected file is not a valid image.");
    }

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        
        // 3. Save to Database
        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("INSERT INTO products (merchant_id, name, description, price, image) VALUES (?, ?, ?, ?, ?)");
        // i=int, s=string, s=string, d=double(price), s=string
        $stmt->bind_param("issds", $merchantId, $name, $description, $price, $fileName);

        if ($stmt->execute()) {
            echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
            echo "<h2 style='color:green;'>✅ Product Published Successfully!</h2>";
            echo "<p>Your item '<strong>$name</strong>' is now live.</p>";
            echo "<br><a href='../views/merchant/dashboard.php' style='text-decoration:none; color:blue;'>← Back to Dashboard</a>";
            echo "</div>";
        } else {
            echo "❌ Database Error: Could not save product data.";
        }

        $stmt->close();
        $conn->close();

    } else {
        echo "❌ Error: Failed to upload product image. Check folder permissions.";
    }

} elseif ($action === "delete" && $_SERVER["REQUEST_METHOD"] === "POST") {
    
    $productId  = $_POST["product_id"];

    // 1. SECURITY: First check if this product belongs to this merchant
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ? AND merchant_id = ?");
    $stmt->bind_param("ii", $productId, $merchantId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $imageName = $row['image'];

        // 2. Delete from Database
        try {
            $stmt_del = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt_del->bind_param("i", $productId);
            
            if ($stmt_del->execute()) {
                // 3. Delete the physical image file from the server
                $imagePath = __DIR__ . "/../uploads/products/" . $imageName;
                if (file_exists($imagePath) && $imageName !== 'default_product.png') {
                    unlink($imagePath);
                }
                header("Location: ../views/merchant/products.php?deleted=1");
                exit;
            } else {
                echo "❌ Error: Could not delete product from database.";
            }
            $stmt_del->close();
        } catch (mysqli_sql_exception $e) {
            // This happens if the product has active orders attached to it (foreign key block)
            header("Location: ../views/merchant/products.php?error=product_has_orders");
            exit;
        }
    } else {
        die("❌ Error: Unauthorized action or product not found.");
    }

    $stmt->close();
    $conn->close();

} elseif ($action === "edit" && $_SERVER["REQUEST_METHOD"] === "POST") {
    
    $productId   = $_POST["product_id"];
    $name        = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $price       = $_POST["price"];
    $file        = $_FILES["product_image"];

    // 1. SECURITY: Check Ownership
    $stmt_check = $conn->prepare("SELECT image FROM products WHERE id = ? AND merchant_id = ?");
    $stmt_check->bind_param("ii", $productId, $merchantId);
    $stmt_check->execute();
    $oldProduct = $stmt_check->get_result()->fetch_assoc();

    if (!$oldProduct) {
        die("❌ Error: Unauthorized action or product not found.");
    }

    // 2. Decide if we are updating the image
    if (!empty($file["name"])) {
        // --- NEW IMAGE UPLOADED ---
        $targetDir = __DIR__ . "/../uploads/products/";
        $fileExt = pathinfo($file["name"], PATHINFO_EXTENSION);
        $fileName = "prod_" . time() . "_" . $merchantId . "." . $fileExt;
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            // Delete old physical image file
            $oldImagePath = $targetDir . $oldProduct['image'];
            if (file_exists($oldImagePath)) { 
                unlink($oldImagePath); 
            }

            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, image = ? WHERE id = ?");
            $stmt->bind_param("ssdsi", $name, $description, $price, $fileName, $productId);
        } else {
            die("❌ Error: Failed to upload new image.");
        }
    } else {
        // --- NO NEW IMAGE: Only update text fields ---
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ? WHERE id = ?");
        $stmt->bind_param("ssdi", $name, $description, $price, $productId);
    }

    if ($stmt->execute()) {
        header("Location: ../views/merchant/products.php?updated=1");
        exit;
    } else {
        echo "❌ Error: Could not update product in database.";
    }

    $stmt->close();
    $conn->close();

} else {
    header("Location: ../views/merchant/dashboard.php");
    exit;
}
