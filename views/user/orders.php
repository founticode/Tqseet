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
    <style>
        .orders-container { max-width: 1000px; margin: 60px auto; padding: 0 20px; }
        .order-card { display: flex; align-items: center; justify-content: space-between; }
        .order-info { display: flex; align-items: center; gap: 20px; }
        .order-actions { text-align: right; }
        @media (max-width: 768px) {
            .order-card { flex-direction: column; align-items: flex-start; gap: 20px; }
            .order-actions { text-align: left; width: 100%; }
            .order-actions .btn-group { display: flex; flex-wrap: wrap; gap: 12px; }
        }
    </style>
</head>
<body style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #fafafa; margin: 0; color: #111827;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="orders-container">
        
        <div style="margin-bottom: 40px; text-align: center;">
            <h1 style="font-weight: 900; font-size: 2.8rem; margin: 0; letter-spacing: -1px; color: #111827;">My Shopping</h1>
            <p style="color: #6b7280; margin-top: 8px; font-size: 1.1rem;">Track your installments and active plans.</p>
        </div>

        <?php 
        $ordersArray = [];
        while($row = $result->fetch_assoc()) { $ordersArray[] = $row; }
        
        $activeOrders = array_filter($ordersArray, function($o) { return $o['status'] !== 'paid'; });
        $paidOrders = array_filter($ordersArray, function($o) { return $o['status'] === 'paid'; });
        ?>

        <!-- SECTION 1: ACTIVE PLANS -->
        <h2 style="font-weight: 900; font-size: 1.25rem; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #f3f4f6; padding-bottom: 12px;">
            <span style="background: #111827; color: white; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.9rem;">🚀</span> 
            Active Plans
        </h2>

        <?php if (count($activeOrders) > 0): ?>
            <?php foreach($activeOrders as $order): ?>
                <div class="order-card" style="background: white; padding: 24px; border-radius: 20px; margin-bottom: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border: 1px solid #f3f4f6; transition: transform 0.2s;">
                    
                    <div class="order-info">
                        <div style="width: 80px; height: 80px; overflow: hidden; border-radius: 14px; background: #f9fafb; border: 1px solid #e5e7eb;">
                            <img src="/uploads/products/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.src='https://via.placeholder.com/80?text=P'">
                        </div>
                        <div>
                            <div style="font-size: 1.15rem; font-weight: 800; color: #111827; margin-bottom: 4px;"><?php echo htmlspecialchars($order['product_name']); ?></div>
                            <div style="color: #6b7280; font-size: 0.85rem;">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                            
                            <?php 
                                $st_check = $conn->prepare("SELECT COUNT(*) as count FROM installments WHERE order_id = ?");
                                $st_check->bind_param("i", $order['id']);
                                $st_check->execute();
                                $hasInstallments = $st_check->get_result()->fetch_assoc()['count'] > 0;
                                
                                if ($hasInstallments): ?>
                                    <div style="display: inline-block; margin-top: 10px; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; background: #dcfce7; color: #166534; letter-spacing: 0.5px;">Active</div>
                                <?php else: ?>
                                    <div style="display: inline-block; margin-top: 10px; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; background: #fef3c7; color: #92400e; letter-spacing: 0.5px;">Draft</div>
                                <?php endif; ?>
                        </div>
                    </div>

                    <div class="order-actions">
                        <div style="font-size: 1.4rem; font-weight: 900; color: #111827; margin-bottom: 8px;"><?php echo number_format($order['total_price'], 2); ?> <span style="font-size: 1rem; color: #6b7280;">DH</span></div>
                        
                        <?php if ($hasInstallments): ?>
                            <div class="btn-group">
                                <a href="view_installments.php?order_id=<?php echo $order['id']; ?>" 
                                   style="display: inline-block; padding: 10px 20px; background: #111827; color: white; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 0.85rem; transition: background 0.2s;">
                                    Manage Plan
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="btn-group" style="display: flex; align-items: center; gap: 12px; justify-content: flex-end;">
                                <a href="calculate_installments.php?order_id=<?php echo $order['id']; ?>" 
                                   style="display: inline-block; padding: 10px 20px; background: #f59e0b; color: white; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 0.85rem;">
                                    Setup
                                </a>
                                <form action="../../controllers/OrderController.php" method="POST" style="margin: 0;" onsubmit="return confirm('Remove this draft from your shopping?')">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" style="background: #fee2e2; color: #991b1b; padding: 10px 16px; border: none; border-radius: 12px; cursor: pointer; font-size: 0.85rem; font-weight: 700;">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="background: white; border-radius: 20px; padding: 40px; text-align: center; border: 2px dashed #e5e7eb; color: #9ca3af; font-weight: 600; font-size: 0.95rem;">
                No active installment plans. Ready to start shopping?
            </div>
        <?php endif; ?>

        <!-- SECTION 2: PURCHASE HISTORY -->
        <h2 style="font-weight: 900; font-size: 1.25rem; margin-top: 60px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #f3f4f6; padding-bottom: 12px;">
            <span style="background: #10b981; color: white; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.9rem;">✅</span> 
            Purchase History
        </h2>

        <?php if (count($paidOrders) > 0): ?>
            <div style="display: grid; gap: 16px;">
                <?php foreach($paidOrders as $order): ?>
                    <div class="order-card" style="background: white; padding: 20px; border-radius: 16px; border: 1px solid #f3f4f6; opacity: 0.85;">
                        <div class="order-info">
                            <div style="width: 50px; height: 50px; overflow: hidden; border-radius: 10px; filter: grayscale(1); opacity: 0.7;">
                                <img src="/uploads/products/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                     style="width: 100%; height: 100%; object-fit: cover;"
                                     onerror="this.src='https://via.placeholder.com/50?text=P'">
                            </div>
                            <div>
                                <div style="font-size: 1rem; font-weight: 700; color: #4b5563;"><?php echo htmlspecialchars($order['product_name']); ?></div>
                                <div style="color: #9ca3af; font-size: 0.8rem; font-weight: 500;">Paid off on <?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="order-actions">
                            <div style="font-weight: 800; color: #9ca3af; font-size: 1.1rem;"><?php echo number_format($order['total_price'], 2); ?> DH</div>
                            <div style="color: #10b981; font-size: 0.75rem; font-weight: 800; margin-top: 4px; letter-spacing: 0.5px;">🛡️ FULLY OWNED</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="padding: 20px; text-align: center; color: #9ca3af; font-weight: 600; font-size: 0.95rem;">
                Your fully paid orders will appear here.
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
