<?php
require_once __DIR__ . "/../../includes/auth.php";

// Protect: ONLY Merchants allowed!
requireRole("merchant");

$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Merchant Dashboard</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #f8f9fa;">

    <!-- Navigation Bar -->
    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1200px; margin: 20px auto; padding: 20px;">
        
        <header style="background: #343a40; color: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h1 style="margin: 0;">Store Manager</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.8;">Welcome back, <strong><?php echo $user['name']; ?></strong>! Manage your inventory and sales.</p>
        </header>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            
            <!-- Quick Action: Add Product -->
            <div style="background: white; padding: 30px; border-radius: 8px; border: 1px solid #dee2e6; text-align: center; transition: 0.3s;">
                <h3 style="margin-top: 0;">List New Product</h3>
                <p style="color: #666; margin-bottom: 25px;">Ready to sell something new? Create a listing here.</p>
                <a href="add_product.php" style="display: block; background: #007bff; color: white; padding: 12px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    + Add Product
                </a>
            </div>

            <!-- Manage Products -->
            <div style="background: white; padding: 30px; border-radius: 8px; border: 1px solid #dee2e6; text-align: center;">
                <h3 style="margin-top: 0;">Inventory Management</h3>
                <p style="color: #666; margin-bottom: 25px;">Track, edit, or remove your current products.</p>
                <a href="products.php" style="display: block; background: #6c757d; color: white; padding: 12px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    Manage Products
                </a>
            </div>

            <!-- Orders -->
            <div style="background: white; padding: 30px; border-radius: 8px; border: 1px solid #dee2e6; text-align: center;">
                <h3 style="margin-top: 0;">Sales & Orders</h3>
                <p style="color: #666; margin-bottom: 25px;">Check who bought your items and payment status.</p>
                <a href="orders.php" style="display: block; background: #28a745; color: white; padding: 12px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    View Orders
                </a>
            </div>

        </div>

        <div style="margin-top: 40px; padding: 20px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px; color: #856404;">
            <strong>Merchant Tip:</strong> High-quality product images increase sales by up to 40%!
        </div>

    </div>

</body>
</html>
