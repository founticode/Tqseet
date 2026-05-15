<?php
require_once __DIR__ . "/../../includes/auth.php";

// Protect: ONLY Merchants allowed!
requireRole("merchant");

require_once __DIR__ . "/../../config/db.php";
$db = new Database();
$conn = $db->connect();
$user = currentUser();

$stmt_m = $conn->prepare("SELECT status FROM merchants WHERE user_id = ?");
$stmt_m->bind_param("i", $user['id']);
$stmt_m->execute();
$merchantData = $stmt_m->get_result()->fetch_assoc();
$status = $merchantData['status'] ?? 'pending';

if ($status !== 'approved') {
    header("Location: dashboard.php?error=pending_approval");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Add Product</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #f8f9fa;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 700px; margin: 40px auto; padding: 30px; background: white; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h1 style="margin-top: 0;">List a New Product</h1>
        <p style="color: #666;">Provide high-quality info and images to attract more customers.</p>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <form action="../../controllers/ProductController.php?action=add" method="POST" enctype="multipart/form-data">
            
            <div style="margin-bottom: 20px;">
                <label for="name" style="display: block; margin-bottom: 5px; font-weight: bold;">Product Name:</label>
                <input type="text" id="name" name="name" placeholder="e.g. iPhone 15 Pro" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="description" style="display: block; margin-bottom: 5px; font-weight: bold;">Description:</label>
                <textarea id="description" name="description" rows="5" placeholder="Describe your product features, condition, etc." required 
                          style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box;"></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="price" style="display: block; margin-bottom: 5px; font-weight: bold;">Price (DH):</label>
                <input type="number" id="price" name="price" step="0.01" placeholder="0.00" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 30px;">
                <label for="product_image" style="display: block; margin-bottom: 5px; font-weight: bold;">Product Image:</label>
                <div style="background: #fdfdfd; padding: 15px; border: 2px dashed #dee2e6; border-radius: 4px;">
                    <input type="file" id="product_image" name="product_image" accept="image/*" required>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 20px;">
                <button type="submit" style="background: #007bff; color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 1rem;">
                    Publish Listing
                </button>
                <a href="dashboard.php" style="color: #6c757d; text-decoration: none;">Cancel</a>
            </div>

        </form>
    </div>

</body>
</html>
