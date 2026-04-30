<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect the page
requireLogin();
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch all orders for this user, including product info
$stmt = $conn->prepare("SELECT o.*, p.name as product_name, p.image as product_image 
                        FROM orders o 
                        JOIN products p ON o.product_id = p.id 
                        WHERE o.user_id = ? 
                        ORDER BY o.created_at DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - My Shopping Plans</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f4f7f9; margin: 0; color: #333;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 900px; margin: 60px auto; padding: 0 20px;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
            <div>
                <h1 style="font-weight: 900; font-size: 2.5rem; margin: 0; letter-spacing: -1px;">My Shopping</h1>
                <p style="color: #7f8c8d; margin-top: 8px; font-size: 1.1rem;">Track your installments and active plans.</p>
            </div>
            <?php if (isset($_GET['success'])): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px 20px; border-radius: 8px; font-weight: bold; font-size: 0.9rem; border: 1px solid #c3e6cb;">
                    ✅ Order Placed Successfully!
                </div>
            <?php endif; ?>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <?php while($order = $result->fetch_assoc()): ?>
                <div style="background: white; padding: 30px; border-radius: 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.02); transition: transform 0.2s ease;">
                    
                    <!-- Left Side: Product Info -->
                    <div style="display: flex; align-items: center; gap: 25px;">
                        <div style="width: 100px; height: 100px; overflow: hidden; border-radius: 15px; background: #f0f0f0;">
                            <img src="../../uploads/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.src='https://via.placeholder.com/100?text=Product'">
                        </div>
                        <div>
                            <div style="font-size: 1.3rem; font-weight: 800; color: #2c3e50;"><?php echo htmlspecialchars($order['product_name']); ?></div>
                            <div style="color: #95a5a6; font-size: 0.9rem; margin-top: 6px; font-weight: 500;">
                                Order #<?php echo $order['id']; ?> • <?php echo date('d M Y', strtotime($order['created_at'])); ?>
                            </div>
                            
                            <!-- Professional Status Badges -->
                            <?php 
                                if ($order['status'] === 'active') {
                                    $statusColor = '#27ae60';
                                    $statusBg = '#eafaf1';
                                    $labelText = 'Active Plan';
                                } elseif ($order['status'] === 'pending') {
                                    $statusColor = '#f39c12';
                                    $statusBg = '#fef9e7';
                                    $labelText = 'Payment Pending';
                                } else {
                                    $statusColor = '#7f8c8d';
                                    $statusBg = '#f4f6f7';
                                    $labelText = ucfirst($order['status']);
                                }
                            ?>
                            <div style="display: inline-block; margin-top: 15px; padding: 5px 14px; border-radius: 30px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; color: <?php echo $statusColor; ?>; background: <?php echo $statusBg; ?>;">
                                <?php echo $labelText; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Price & Action -->
                    <div style="text-align: right;">
                        <div style="font-size: 0.85rem; color: #95a5a6; font-weight: bold; text-transform: uppercase; margin-bottom: 5px;">Total Value</div>
                        <div style="font-size: 1.6rem; font-weight: 900; color: #222;"><?php echo number_format($order['total_price'], 2); ?> <span style="font-size: 0.9rem;">DH</span></div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px;">
                            <a href="../public/product_detail.php?id=<?php echo $order['product_id']; ?>" 
                               style="color: #007bff; text-decoration: none; font-size: 0.85rem; font-weight: bold;">View Details</a>
                            
                            <?php if ($order['status'] !== 'cancelled'): ?>
                                <a href="../../controllers/OrderController.php?action=cancel&id=<?php echo $order['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to cancel this plan?')"
                                   style="color: #d9534f; text-decoration: none; font-size: 0.85rem; font-weight: bold;">Cancel Plan</a>
                            <?php endif; ?>
                        </div>

                        <a href="view_installments.php?order_id=<?php echo $order['id']; ?>" 
                           style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #222; color: white; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 0.85rem;">
                            Manage Plan →
                        </a>
                    </div>

                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 120px 20px; background: white; border-radius: 30px; border: 2px dashed #eee;">
                <div style="font-size: 4rem; margin-bottom: 20px;">🛍️</div>
                <h2 style="color: #2c3e50; font-weight: 900;">Your shopping bag is empty</h2>
                <p style="color: #95a5a6; margin-bottom: 30px;">Start your BNPL journey by choosing a product from our catalog.</p>
                <a href="../public/catalog.php" style="display: inline-block; padding: 15px 35px; background: #007bff; color: white; text-decoration: none; border-radius: 15px; font-weight: bold; box-shadow: 0 10px 20px rgba(0,123,255,0.2);">
                    Explore Catalog
                </a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
