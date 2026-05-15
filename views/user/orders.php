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
        
        <div style="margin-bottom: 50px;">
            <h1 style="font-weight: 900; font-size: 2.5rem; margin: 0; letter-spacing: -1px;">My Shopping</h1>
            <p style="color: #7f8c8d; margin-top: 8px; font-size: 1.1rem;">Track your installments and active plans.</p>
        </div>

        <?php 
        $ordersArray = [];
        while($row = $result->fetch_assoc()) { $ordersArray[] = $row; }
        
        $activeOrders = array_filter($ordersArray, function($o) { return $o['status'] !== 'paid'; });
        $paidOrders = array_filter($ordersArray, function($o) { return $o['status'] === 'paid'; });
        ?>

        <!-- SECTION 1: ACTIVE PLANS -->
        <h2 style="font-weight: 900; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
            <span style="background: #222; color: white; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.9rem;">🚀</span> 
            Active Plans
        </h2>

        <?php if (count($activeOrders) > 0): ?>
            <?php foreach($activeOrders as $order): ?>
                <div style="background: white; padding: 30px; border-radius: 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.02);">
                    <!-- Same card layout as before -->
                    <div style="display: flex; align-items: center; gap: 25px;">
                        <div style="width: 80px; height: 80px; overflow: hidden; border-radius: 12px; background: #f0f0f0;">
                            <img src="/uploads/products/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.src='https://via.placeholder.com/80?text=P'">
                        </div>
                        <div>
                            <div style="font-size: 1.15rem; font-weight: 800; color: #2c3e50;"><?php echo htmlspecialchars($order['product_name']); ?></div>
                            <div style="color: #95a5a6; font-size: 0.8rem; margin-top: 4px;">Order #<?php echo $order['id']; ?></div>
                            
                            <?php 
                                // Check if installments exist
                                $st_check = $conn->prepare("SELECT COUNT(*) as count FROM installments WHERE order_id = ?");
                                $st_check->bind_param("i", $order['id']);
                                $st_check->execute();
                                $hasInstallments = $st_check->get_result()->fetch_assoc()['count'] > 0;
                                
                                if ($hasInstallments): ?>
                                    <div style="display: inline-block; margin-top: 10px; padding: 4px 10px; border-radius: 30px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; background: #eafaf1; color: #27ae60;">Active</div>
                                <?php else: ?>
                                    <div style="display: inline-block; margin-top: 10px; padding: 4px 10px; border-radius: 30px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; background: #fff4e5; color: #ff9800;">Draft</div>
                                <?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.4rem; font-weight: 900; color: #222;"><?php echo number_format($order['total_price'], 2); ?> DH</div>
                        
                        <?php if ($hasInstallments): ?>
                            <a href="view_installments.php?order_id=<?php echo $order['id']; ?>" 
                               style="display: inline-block; margin-top: 10px; padding: 8px 16px; background: #222; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 0.8rem;">
                                Manage Plan →
                            </a>
                        <?php else: ?>
                            <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                                <a href="calculate_installments.php?order_id=<?php echo $order['id']; ?>" 
                                   style="display: inline-block; padding: 8px 16px; background: #ff9800; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 0.8rem;">
                                    Finish Plan Setup →
                                </a>
                                <form action="../../controllers/OrderController.php" method="POST" style="margin: 0;" onsubmit="return confirm('Remove this draft from your shopping?')">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" style="background: none; border: none; color: #ff7675; cursor: pointer; font-size: 0.8rem; font-weight: bold; text-decoration: underline;">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #bdc3c7; margin-bottom: 50px;">No active installment plans.</p>
        <?php endif; ?>

        <!-- SECTION 2: PURCHASE HISTORY -->
        <h2 style="font-weight: 900; margin-top: 60px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
            <span style="background: #0984e3; color: white; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.9rem;">✅</span> 
            Purchase History
        </h2>

        <?php if (count($paidOrders) > 0): ?>
            <?php foreach($paidOrders as $order): ?>
                <div style="background: #fdfdfd; padding: 25px; border-radius: 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border: 1px solid #f1f1f1; opacity: 0.85;">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div style="width: 60px; height: 60px; overflow: hidden; border-radius: 10px; filter: grayscale(1);">
                            <img src="/uploads/products/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.src='https://via.placeholder.com/60?text=P'">
                        </div>
                        <div>
                            <div style="font-size: 1rem; font-weight: 700; color: #2c3e50;"><?php echo htmlspecialchars($order['product_name']); ?></div>
                            <div style="color: #95a5a6; font-size: 0.75rem;">Paid off on <?php echo date('d M Y', strtotime($order['created_at'])); ?></div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 900; color: #95a5a6;"><?php echo number_format($order['total_price'], 2); ?> DH</div>
                        <div style="color: #3498db; font-size: 0.75rem; font-weight: bold; margin-top: 5px;">🛡️ OWNED</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #bdc3c7;">Your fully paid orders will appear here.</p>
        <?php endif; ?>

    </div>

</body>
</html>
