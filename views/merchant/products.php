<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Merchants allowed!
requireRole("merchant");

$merchantId = $_SESSION["merchant_id"] ?? null;

$db = new Database();
$conn = $db->connect();

// Fetch products for THIS merchant only
$stmt = $conn->prepare("SELECT * FROM products WHERE merchant_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $merchantId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Manage My Products</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #f8f9fa;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1000px; margin: 30px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
            <h1 style="margin: 0;">My Inventory</h1>
            <a href="add_product.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">
                + Add New Product
            </a>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead style="background: #f1f1f1;">
                <tr>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Image</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Product Details</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Price</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px;">
                                <img src="../../uploads/products/<?php echo $row['image']; ?>" alt="Product" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                            </td>
                            <td style="padding: 12px;">
                                <div style="font-weight: bold; font-size: 1.1rem;"><?php echo $row['name']; ?></div>
                                <div style="color: #666; font-size: 0.85rem; margin-top: 5px;">Added on: <?php echo date("M d, Y", strtotime($row['created_at'])); ?></div>
                            </td>
                            <td style="padding: 12px; font-weight: bold; color: #28a745;">
                                <?php echo number_format($row['price'], 2); ?> DH
                            </td>
                            <td style="padding: 12px;">
                                <!-- Edit Button -->
                                <a href="edit_product.php?id=<?php echo $row['id']; ?>" style="color: #007bff; text-decoration: none; font-weight: bold; margin-right: 20px; font-size: 0.9rem;">Edit</a>
                                
                                <!-- Delete Form -->
                                <form action="../../controllers/ProductController.php?action=delete" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" style="background: none; border: none; color: #dc3545; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 60px; color: #666;">
                            <p style="font-size: 1.2rem; margin-bottom: 10px;">You haven't listed any products yet.</p>
                            <a href="add_product.php" style="color: #007bff; font-weight: bold;">Start selling now!</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

</body>
</html>
