<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

requireRole("admin");

$orderId = $_GET['order_id'] ?? 0;

$db = new Database();
$conn = $db->connect();

// 1. Fetch Order Info
$stmt = $conn->prepare("SELECT o.*, p.name as product_name, p.image as product_image, u.name as customer_name, u.email as customer_email 
                        FROM orders o 
                        JOIN products p ON o.product_id = p.id 
                        JOIN users u ON o.user_id = u.id
                        WHERE o.id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Plan not found.");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan #<?php echo $orderId; ?> Details - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
    <style>
        .plan-header { background: white; border-radius: 12px; padding: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 24px; border: 1px solid #e2e8f0; }
        .plan-header-info { display: flex; gap: 20px; align-items: center; text-align: left; }
        .plan-header-stats { text-align: right; }
        .timeline-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .timeline-item-content { flex-grow: 1; display: flex; justify-content: space-between; align-items: flex-start; margin-top: 4px; }
        .timeline-item-right { text-align: right; }
        
        @media (max-width: 600px) {
            .plan-header { flex-direction: column; text-align: center; gap: 15px; padding: 20px; }
            .plan-header-info { flex-direction: column; gap: 10px; text-align: center; }
            .plan-header-stats { text-align: center; width: 100%; background: #f8fafc; padding: 15px; border-radius: 8px; margin-top: 5px; box-sizing: border-box; }
            .timeline-card { padding: 20px; }
            .timeline-item-content { flex-direction: column; align-items: stretch !important; gap: 10px; }
            .timeline-item-right { text-align: left; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 12px; border-radius: 8px; margin-top: 5px; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . "/../../includes/admin_sidebar.php"; ?>
    <main class="main-content">
        <header class="page-header" style="margin-bottom: 24px;">
            <div>
                <a href="all_installments.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem; font-weight: 600;">← Back to Payment Monitor</a>
                <h1 style="margin-top: 10px;">Plan #<?php echo $orderId; ?></h1>
            </div>
        </header>

        <div class="plan-header">
            <div class="plan-header-info">
                <img src="/uploads/products/<?php echo htmlspecialchars($order['product_image']); ?>" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/80?text=Product'">
                <div>
                    <h2 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: #111827;"><?php echo htmlspecialchars($order['product_name']); ?></h2>
                    <p style="margin: 4px 0 0 0; color: #6b7280; font-size: 0.9rem;">Customer: <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>(<?php echo htmlspecialchars($order['customer_email']); ?>)</p>
                </div>
            </div>
            <div class="plan-header-stats">
                <div style="font-size: 0.8rem; color: #6b7280; text-transform: uppercase; font-weight: 800;">Total Paid</div>
                <div style="font-size: 1.4rem; font-weight: 800; color: #10b981;"><?php echo number_format($totalPaid, 2); ?> DH</div>
                <div style="font-size: 0.85rem; font-weight: 600; color: #ef4444; margin-top: 4px;">Remaining: <?php echo number_format($totalRemaining, 2); ?> DH</div>
            </div>
        </div>

        <div class="timeline-card">
            <h3 style="margin: 0 0 30px 0; font-weight: 800; font-size: 1.1rem; color: #111827;">Installment Timeline</h3>
            <?php foreach ($installmentRows as $index => $ins): ?>
                <div style="display: flex; gap: 30px; position: relative; padding-bottom: 30px;">
                    <?php if ($index < count($installmentRows) - 1): ?>
                        <div style="position: absolute; left: 14px; top: 30px; bottom: 0; width: 2px; background: <?php echo ($ins['status'] === 'paid') ? '#10b981' : '#e2e8f0'; ?>;"></div>
                    <?php endif; ?>
                    <div style="position: relative; z-index: 2;">
                        <?php if ($ins['status'] === 'paid'): ?>
                            <div style="width: 30px; height: 30px; border-radius: 50%; background: #10b981; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.8rem;">✓</div>
                        <?php else: ?>
                            <div style="width: 30px; height: 30px; border-radius: 50%; background: white; border: 2px solid #e2e8f0; color: #9ca3af; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem;"><?php echo $index + 1; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="timeline-item-content">
                        <div>
                            <div style="font-weight: 700; font-size: 1rem; color: <?php echo ($ins['status'] === 'paid') ? '#10b981' : '#111827'; ?>;">
                                <?php echo ($index === 0) ? "Initial Downpayment" : "Installment #" . ($index + 1); ?>
                            </div>
                            <div style="font-size: 0.85rem; color: #6b7280; font-weight: 500; margin-top: 4px;">
                                Due: <?php echo ($index === 0 && $ins['status'] === 'paid') ? "Completed at Purchase" : date('d M Y', strtotime($ins['due_date'])); ?>
                            </div>
                        </div>
                        <div class="timeline-item-right">
                            <div style="font-size: 1.1rem; font-weight: 800; color: #111827;"><?php echo number_format($ins['amount'], 2); ?> <span style="font-size: 0.7rem;">DH</span></div>
                            <div style="font-size: 0.8rem; font-weight: 700; margin-top: 4px; color: <?php echo ($ins['status'] === 'paid') ? '#10b981' : '#f59e0b'; ?>;">
                                <?php echo ($ins['status'] === 'paid') ? 'Paid' : 'Pending'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
