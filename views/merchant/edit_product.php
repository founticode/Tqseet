<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Merchants allowed!
requireRole("merchant");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// NEW: Lookup actual Merchant ID and Profile
$stmt_m = $conn->prepare("SELECT * FROM merchants WHERE user_id = ?");
$stmt_m->bind_param("i", $user['id']);
$stmt_m->execute();
$merchantData = $stmt_m->get_result()->fetch_assoc();
$merchantId = $merchantData['id'] ?? 0;

$productId  = $_GET["id"] ?? 0;

// Fetch product details and ENSURE it belongs to this merchant
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND merchant_id = ?");
$stmt->bind_param("ii", $productId, $merchantId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h2 style='color:red;'>❌ Error: Product not found or unauthorized access.</h2>
            <br><a href='products.php'>← Back to Inventory</a>
         </div>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Edit Product</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #f8f9fa;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 700px; margin: 40px auto; padding: 30px; background: white; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h1 style="margin-top: 0;">Edit Product</h1>
        <p style="color: #666;">Update your product details below. Any changes will be live immediately.</p>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <form action="../../controllers/ProductController.php?action=edit" method="POST" enctype="multipart/form-data">
            <!-- Hidden ID field -->
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

            <div style="margin-bottom: 20px;">
                <label for="name" style="display: block; margin-bottom: 5px; font-weight: bold;">Product Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="description" style="display: block; margin-bottom: 5px; font-weight: bold;">Description:</label>
                <textarea id="description" name="description" rows="5" required 
                          style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box;"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="price" style="display: block; margin-bottom: 5px; font-weight: bold;">Price (DH):</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo $product['price']; ?>" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Product Image:</label>
                <div style="display: flex; align-items: start; gap: 20px; background: #fdfdfd; padding: 15px; border: 1px solid #dee2e6; border-radius: 4px;">
                    <div>
                        <p style="margin: 0 0 10px 0; font-size: 0.8rem; color: #666;">Current Image:</p>
                        <img src="../../uploads/products/<?php echo $product['image']; ?>" alt="Current" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                    <div style="flex-grow: 1;">
                        <p style="margin: 0 0 10px 0; font-size: 0.8rem; color: #666;">Upload new (leave blank to keep current):</p>
                        <input type="file" name="product_image" accept="image/*">
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 20px;">
                <button type="submit" style="background: #007bff; color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 1rem;">
                    Update Product
                </button>
                <a href="products.php" style="color: #6c757d; text-decoration: none;">Cancel</a>
            </div>

        </form>
    </div>

</body>
</html>
