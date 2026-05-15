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
$merchantStatus = $merchantData['status'] ?? 'pending';

// SECURITY GATE
if ($merchantStatus !== 'approved') {
    header("Location: dashboard.php?error=pending_approval");
    exit;
}

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
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1100px; margin: 60px auto; padding: 0 20px;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
            <div>
                <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">My Inventory</h1>
                <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Manage your product catalog and listings.</p>
            </div>
            <a href="add_product.php" style="background: #222; color: white; padding: 15px 30px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 0.9rem; box-shadow: 0 10px 20px rgba(0,0,0,0.1); transition: 0.3s;">
                + Add New Product
            </a>
        </div>

        <!-- Inventory Table Container -->
        <div style="background: white; border-radius: 25px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); overflow: hidden;">
            
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; background: #fafafa; border-bottom: 1px solid #f1f1f1;">
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Product</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Category</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Price</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Status</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" style="padding: 60px; text-align: center; color: #b2bec3; font-style: italic;">Your inventory is empty. Start adding products!</td>
                        </tr>
                    <?php endif; ?>

                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #f8f9fa; transition: 0.2s;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='white'">
                            <td style="padding: 20px;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <img src="../../uploads/products/<?php echo $row['image']; ?>" style="width: 60px; height: 60px; border-radius: 12px; object-fit: cover; border: 1px solid #f1f1f1;">
                                    <div>
                                        <div style="font-weight: 800; color: #2d3436; font-size: 1rem;"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div style="font-size: 0.8rem; color: #b2bec3; margin-top: 4px;">ID: #<?php echo $row['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 20px; color: #636e72; font-size: 0.9rem;">Electronics</td> <!-- Hardcoded for now -->
                            <td style="padding: 20px; font-weight: 900; color: #2d3436;"><?php echo number_format($row['price'], 2); ?> DH</td>
                            <td style="padding: 20px;">
                                <span style="background: rgba(0, 184, 148, 0.1); color: #00b894; padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">Active</span>
                            </td>
                            <td style="padding: 20px; text-align: right;">
                                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                    <a href="edit_product.php?id=<?php echo $row['id']; ?>" style="padding: 8px 15px; border-radius: 8px; background: #f1f3f5; color: #495057; text-decoration: none; font-size: 0.8rem; font-weight: bold; transition: 0.3s;">Edit</a>
                                    
                                    <form action="../../controllers/ProductController.php?action=delete" method="POST" onsubmit="return confirm('Are you sure you want to remove this product?')" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" style="padding: 8px 15px; border-radius: 8px; background: rgba(214, 48, 49, 0.05); color: #d63031; border: none; font-size: 0.8rem; font-weight: bold; cursor: pointer; transition: 0.3s;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
