<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect the page
requireLogin();
$user = currentUser();

$orderId = $_GET['order_id'] ?? 0;

$db = new Database();
$conn = $db->connect();

// NEW: Check for saved card (Tabby style)
$stmt_card = $conn->prepare("SELECT last_four FROM payment_methods WHERE user_id = ? LIMIT 1");
$stmt_card->bind_param("i", $user['id']);
$stmt_card->execute();
$savedCard = $stmt_card->get_result()->fetch_assoc();

// 1. Fetch Order Info
$stmt = $conn->prepare("SELECT o.*, p.name as product_name, p.image as product_image 
                        FROM orders o 
                        JOIN products p ON o.product_id = p.id 
                        WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $orderId, $user['id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("<div style='font-family:sans-serif; text-align:center; padding:100px;'>
            <h1>Payment plan not found.</h1>
            <a href='orders.php'>Back to My Shopping</a>
         </div>");
}

// 2. Fetch Installments
$stmt_ins = $conn->prepare("SELECT * FROM installments WHERE order_id = ? ORDER BY due_date ASC");
$stmt_ins->bind_param("i", $orderId);
$stmt_ins->execute();
$installmentsResult = $stmt_ins->get_result();

$totalPaid = 0;
$totalRemaining = 0;
$installmentRows = [];

while ($row = $installmentsResult->fetch_assoc()) {
    if ($row['status'] === 'paid') {
        $totalPaid += $row['amount'];
    } else {
        $totalRemaining += $row['amount'];
    }
    $installmentRows[] = $row;
}
$canPayNext = true;
$anyPaid = false;
foreach ($installmentRows as $ins) {
    if ($ins['status'] === 'paid') {
        $anyPaid = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Payment Timeline</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; margin: 0; color: #0f172a; }
        h1, h2, h3, .brand-font { font-family: 'Outfit', sans-serif; }
        
        .summary-card { background: white; border-radius: 20px; padding: 30px; display: flex; align-items: center; gap: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #e2e8f0; }
        .summary-image { width: 120px; height: 120px; border-radius: 16px; overflow: hidden; background: #f1f5f9; flex-shrink: 0; }
        .summary-title { margin: 0; font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px; color: #0f172a; }
        .summary-stats { display: flex; gap: 30px; margin-top: 15px; }
        
        .timeline-card { background: white; border-radius: 20px; padding: 35px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .timeline-item-content { flex-grow: 1; display: flex; justify-content: space-between; align-items: flex-start; margin-top: 4px; }
        .timeline-item-right { text-align: right; }
        .pay-btn-container { margin-top: 12px; }
        
        @media (max-width: 600px) {
            .summary-card { flex-direction: column; text-align: center; gap: 15px; padding: 20px; }
            .summary-image { width: 100px; height: 100px; }
            .summary-title { font-size: 1.3rem !important; line-height: 1.2; }
            .summary-stats { width: 100%; justify-content: space-between; gap: 10px; background: #f8fafc; padding: 15px; border-radius: 12px; margin-top: 5px; box-sizing: border-box; }
            
            .timeline-card { padding: 20px; }
            .timeline-item-content { flex-direction: column; align-items: stretch !important; gap: 10px; }
            .timeline-item-right { text-align: left; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 12px; border-radius: 12px; margin-top: 5px; }
            .pay-btn-container { margin-top: 0 !important; }
        }
    </style>
</head>
<body>

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 750px; margin: 60px auto; padding: 0 20px;">
        
        <!-- Summary Card -->
        <div class="summary-card">
            <div class="summary-image">
                <img src="/uploads/products/<?php echo htmlspecialchars($order['product_image']); ?>" 
                     style="width: 100%; height: 100%; object-fit: cover;"
                     onerror="this.src='https://via.placeholder.com/140?text=Product'">
            </div>
            <div style="flex-grow: 1; width: 100%;">
                <h1 class="summary-title"><?php echo htmlspecialchars($order['product_name']); ?></h1>
                <p style="color: #64748b; margin: 6px 0 10px 0; font-size: 0.9rem; font-weight: 500;">Plan #<?php echo $order['id']; ?></p>
                
                <div class="summary-stats">
                    <div>
                        <div style="font-size: 0.75rem; color: #b2bec3; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">Paid Already</div>
                        <div style="font-size: 1.4rem; font-weight: 900; color: #00b894; margin-top: 4px;"><?php echo number_format($totalPaid, 2); ?> <span style="font-size: 0.8rem;">DH</span></div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: #b2bec3; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">Outstanding</div>
                        <div style="font-size: 1.4rem; font-weight: 900; color: #d63031; margin-top: 4px;"><?php echo number_format($totalRemaining, 2); ?> <span style="font-size: 0.8rem;">DH</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Section -->
        <div class="timeline-card">
            <h3 style="margin: 0 0 30px 0; font-weight: 800; font-size: 1.3rem; color: #0f172a;">Payment Journey</h3>

            <?php foreach ($installmentRows as $index => $ins): ?>
                <div style="display: flex; gap: 30px; position: relative; padding-bottom: 40px;">
                    
                    <!-- The Visual Progress Line -->
                    <?php if ($index < count($installmentRows) - 1): ?>
                        <div style="position: absolute; left: 16px; top: 35px; bottom: 0; width: 2px; background: <?php echo ($ins['status'] === 'paid') ? '#00b894' : '#dfe6e9'; ?>;"></div>
                    <?php endif; ?>

                    <!-- Circle Icon -->
                    <div style="position: relative; z-index: 2;">
                        <?php if ($ins['status'] === 'paid'): ?>
                            <div style="width: 34px; height: 34px; border-radius: 50%; background: #00b894; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem; box-shadow: 0 4px 10px rgba(0, 184, 148, 0.3);">✓</div>
                        <?php else: ?>
                            <div style="width: 30px; height: 30px; border-radius: 50%; background: white; border: 2px solid #dfe6e9; color: #b2bec3; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 0.7rem;">
                                <?php echo $index + 1; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Payment Info -->
                    <div class="timeline-item-content">
                        <div>
                            <div style="font-weight: 700; font-size: 1.1rem; color: <?php echo ($ins['status'] === 'paid') ? '#10b981' : '#0f172a'; ?>;">
                                <?php 
                                    if ($index === 0) echo "Initial Downpayment";
                                    else echo "Installment #" . ($index + 1);
                                ?>
                            </div>
                            <div style="font-size: 0.9rem; color: #636e72; font-weight: 500; margin-top: 6px;">
                                Due: <?php echo ($index === 0 && $ins['status'] === 'paid') ? "Completed at Purchase" : date('d M Y', strtotime($ins['due_date'])); ?>
                            </div>
                        </div>

                        <div class="timeline-item-right">
                            <div style="font-size: 1.3rem; font-weight: 900; color: #222;"><?php echo number_format($ins['amount'], 2); ?> <span style="font-size: 0.8rem;">DH</span></div>
                            
                            <?php if ($ins['status'] === 'unpaid'): ?>
                                <div class="pay-btn-container">
                                    <?php if ($canPayNext): ?>
                                    <a href="checkout_payment.php?order_id=<?php echo $orderId; ?>&installment_id=<?php echo $ins['id']; ?>&amount=<?php echo $ins['amount']; ?>" 
                                       style="display: inline-block; margin-top: 12px; background: #222; color: white; text-decoration: none; padding: 8px 18px; border-radius: 10px; font-weight: bold; font-size: 0.8rem; transition: transform 0.2s;">
                                        Pay Now
                                    </a>
                                    <?php $canPayNext = false; // Next ones are locked until this is paid ?>
                                    <?php else: ?>
                                    <div style="margin-top: 12px; background: #f1f3f5; color: #b2bec3; padding: 8px 18px; border-radius: 10px; font-weight: bold; font-size: 0.8rem; display: inline-block; cursor: not-allowed; border: 1px solid #e1e4e8;">
                                        🔒 Locked
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="pay-btn-container" style="display: flex; align-items: center; gap: 5px; color: #10b981; font-weight: 700; font-size: 0.85rem;">
                                    <span style="font-size: 1rem;">🛡️</span> Fully Paid
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 40px; display: flex; flex-direction: column; align-items: center; gap: 15px;">
            <a href="orders.php" style="color: #636e72; text-decoration: none; font-weight: bold; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px;">
                <span>←</span> Back to Shopping History
            </a>
            
            <?php if (!$anyPaid): ?>
                <form action="../../controllers/OrderController.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this plan? This will remove it from your shopping history.')">
                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                    <button type="submit" style="background: transparent; border: none; color: #ff7675; font-size: 0.85rem; font-weight: bold; cursor: pointer; text-decoration: underline;">
                        Cancel This Plan & Remove from Shopping
                    </button>
                </form>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
