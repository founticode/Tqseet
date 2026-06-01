<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY "user" role allowed
requireRole("user");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch Credit Info
$stmt_f = $conn->prepare("SELECT * FROM user_financials WHERE user_id = ?");
$stmt_f->bind_param("i", $user['id']);
$stmt_f->execute();
$financial = $stmt_f->get_result()->fetch_assoc();

// Fetch Identity Info
$stmt_i = $conn->prepare("SELECT * FROM user_verifications WHERE user_id = ?");
$stmt_i->bind_param("i", $user['id']);
$stmt_i->execute();
$identity = $stmt_i->get_result()->fetch_assoc();

// Fetch Real-time Verification Status
$stmt_u = $conn->prepare("SELECT is_verified FROM users WHERE id = ?");
$stmt_u->bind_param("i", $user['id']);
$stmt_u->execute();
$userData = $stmt_u->get_result()->fetch_assoc();
$isVerified = $userData['is_verified'] ?? 0;
$stmt_u->close();

$status = $financial['status'] ?? 'none';
$maxLimit = $financial['credit_limit'] ?? 0;
$id_status = $identity['status'] ?? 'none';

// --- Calculate Available Credit (Limit - Unpaid installments) ---
$stmt_debt = $conn->prepare("
    SELECT SUM(amount) as debt 
    FROM installments i 
    JOIN orders o ON i.order_id = o.id 
    WHERE o.user_id = ? AND i.status != 'paid'
");
$stmt_debt->bind_param("i", $user['id']);
$stmt_debt->execute();
$debtResult = $stmt_debt->get_result()->fetch_assoc();
$totalDebt = $debtResult['debt'] ?? 0;
$stmt_debt->close();

$availableLimit = $maxLimit - $totalDebt;

// --- Fetch Next Due Installment Info ---
$stmt_next_due = $conn->prepare("
    SELECT i.amount, i.due_date 
    FROM installments i 
    JOIN orders o ON i.order_id = o.id 
    WHERE o.user_id = ? AND i.status != 'paid' 
    ORDER BY i.due_date ASC LIMIT 1
");
$stmt_next_due->bind_param("i", $user['id']);
$stmt_next_due->execute();
$nextDueResult = $stmt_next_due->get_result()->fetch_assoc();
$nextDueAmount = $nextDueResult['amount'] ?? 0;
$nextDueDate = $nextDueResult['due_date'] ?? null;
$stmt_next_due->close();

// --- Fetch Active Plans Count ---
$stmt_active_count = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'active'");
$stmt_active_count->bind_param("i", $user['id']);
$stmt_active_count->execute();
$activeCount = $stmt_active_count->get_result()->fetch_assoc()['count'] ?? 0;
$stmt_active_count->close();

// --- Fetch Latest Active Order and Installments for Visual Timeline ---
$stmt_active_order = $conn->prepare("
    SELECT o.*, p.name as product_name 
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    WHERE o.user_id = ? AND o.status = 'active' 
    ORDER BY o.id DESC LIMIT 1
");
$stmt_active_order->bind_param("i", $user['id']);
$stmt_active_order->execute();
$activeOrder = $stmt_active_order->get_result()->fetch_assoc();
$stmt_active_order->close();

$installments = [];
if ($activeOrder) {
    $stmt_ins_timeline = $conn->prepare("SELECT * FROM installments WHERE order_id = ? ORDER BY due_date ASC");
    $stmt_ins_timeline->bind_param("i", $activeOrder['id']);
    $stmt_ins_timeline->execute();
    $res_ins = $stmt_ins_timeline->get_result();
    while ($row = $res_ins->fetch_assoc()) {
        $installments[] = $row;
    }
    $stmt_ins_timeline->close();
}

// --- Combined Recent Transactions (Paid Installment Payments + Product Purchases) ---
$stmt_transactions = $conn->prepare("
    (
        SELECT 
            i.amount, 
            i.due_date as transaction_date, 
            p.name as product_name, 
            m.store_name, 
            'payment' as type,
            o.status as order_status
        FROM installments i 
        JOIN orders o ON i.order_id = o.id 
        JOIN products p ON o.product_id = p.id 
        JOIN merchants m ON p.merchant_id = m.id 
        WHERE o.user_id = ? AND i.status = 'paid'
    )
    UNION ALL
    (
        SELECT 
            o.total_price as amount, 
            o.created_at as transaction_date, 
            p.name as product_name, 
            m.store_name, 
            'purchase' as type,
            o.status as order_status
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        JOIN merchants m ON p.merchant_id = m.id 
        WHERE o.user_id = ?
    )
    ORDER BY transaction_date DESC, type ASC
    LIMIT 4
");
$stmt_transactions->bind_param("ii", $user['id'], $user['id']);
$stmt_transactions->execute();
$transResult = $stmt_transactions->get_result();
$transactions = [];
while ($row = $transResult->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt_transactions->close();

// --- Calculate Credit Score ---
$creditScore = 580;
if ($status === 'approved' && $id_status === 'approved') {
    $creditScore = 780;
} elseif ($status === 'pending' || $id_status === 'pending') {
    $creditScore = 650;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Credit Hub</title>
</head>
<body style="background-color: #f8fafc;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="dashboard-container">
        
        <!-- Welcome Panel -->
        <div class="welcome-panel">
            <div class="welcome-info">
                <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0;">Welcome back, <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?></h2>
                <div style="display: inline-flex; gap: 8px;">
                    <?php if ($isVerified == 1): ?>
                        <span class="badge badge-success" style="font-size: 0.65rem; padding: 4px 8px; font-weight: 700;">✓ OTP Verified</span>
                    <?php else: ?>
                        <a href="../auth/verify_otp.php" class="badge badge-danger" style="font-size: 0.65rem; padding: 4px 8px; font-weight: 700; text-decoration: none; cursor: pointer;">⏳ OTP Required (Click to Verify)</a>
                    <?php endif; ?>

                    <?php if ($status === 'approved' && $id_status === 'approved'): ?>
                        <span class="badge badge-success" style="font-size: 0.65rem; padding: 4px 8px; font-weight: 700;">✓ KYC Approved</span>
                    <?php elseif ($status === 'pending' || $id_status === 'pending'): ?>
                        <span class="badge badge-warning" style="font-size: 0.65rem; padding: 4px 8px; font-weight: 700;">⏳ KYC Under Review</span>
                    <?php else: ?>
                        <a href="settings.php" class="badge badge-danger" style="font-size: 0.65rem; padding: 4px 8px; font-weight: 700; text-decoration: none; cursor: pointer;">❌ Incomplete KYC (Click to Complete)</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="welcome-sync">
                Last Sync: Today, <?php echo date('h:i A'); ?>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <!-- Available Credit Card -->
            <div class="credit-card-panel">
                <div>
                    <div class="credit-card-chip"></div>
                    <div class="credit-card-label">AVAILABLE CREDIT LIMIT</div>
                    <div class="credit-card-value"><?php echo number_format($availableLimit, 2); ?> <span style="font-size: 1.1rem; font-weight: 500; opacity: 0.85;">DH</span></div>
                </div>
                
                <div>
                    <div class="credit-card-details-row">
                        <div>
                            <div class="credit-card-meta-label">Used Credit</div>
                            <div class="credit-card-meta-value"><?php echo number_format($totalDebt, 2); ?> DH</div>
                        </div>
                        <div>
                            <div class="credit-card-meta-label">Next Due</div>
                            <div class="credit-card-meta-value">
                                <?php echo ($nextDueAmount > 0) ? number_format($nextDueAmount, 2) . ' DH' : '0.00 DH'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="credit-card-actions">
                        <a href="orders.php" class="credit-card-btn-solid">Make Payment</a>
                        <a href="#payment-timeline" class="credit-card-btn-secondary">View Statement</a>
                    </div>
                </div>
            </div>

            <!-- Profile & Credit Score Card -->
            <div class="dashboard-card">
                <div class="profile-card-header">
                    <div class="profile-avatar-circle">
                        <?php 
                        $initials = '';
                        $words = explode(' ', $user['name']);
                        foreach ($words as $w) {
                            $initials .= strtoupper(substr($w, 0, 1));
                        }
                        echo htmlspecialchars(substr($initials, 0, 2));
                        ?>
                    </div>
                    <div>
                        <div class="profile-avatar-name"><?php echo htmlspecialchars($user['name']); ?></div>
                        <div class="profile-avatar-role">Consumer Profile</div>
                    </div>
                </div>

                <div class="detail-list" style="margin-bottom: 25px;">
                    <div class="detail-row">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Phone Number</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user['phone']); ?></div>
                    </div>
                </div>

                <div class="profile-card-metrics">
                    <div class="profile-card-metric-box">
                        <div class="profile-card-metric-label">Credit Score</div>
                        <div class="profile-card-metric-value <?php echo ($creditScore >= 700) ? 'score-high' : ''; ?>">
                            <?php echo $creditScore; ?>
                        </div>
                    </div>
                    <div class="profile-card-metric-box">
                        <div class="profile-card-metric-label">Active Plans</div>
                        <div class="profile-card-metric-value">
                            <?php echo $activeCount; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Installment Timeline Widget -->
        <div id="payment-timeline" class="dashboard-card" style="margin-bottom: 40px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0;">Installment Timeline</h3>
                <?php if ($activeOrder): ?>
                    <a href="view_installments.php?order_id=<?php echo $activeOrder['id']; ?>" style="font-size: 0.8rem; font-weight: 700; color: #005a4e; text-decoration: none;">
                        <?php echo htmlspecialchars($activeOrder['product_name']); ?> →
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($activeOrder && count($installments) > 0): ?>
                <div class="timeline-horizontal-container">
                    
                    <!-- Line connecting steps -->
                    <div class="timeline-horizontal-line">
                        <?php 
                        // Calculate progress bar width based on number of paid installments
                        $paidCount = 0;
                        foreach ($installments as $ins) {
                            if ($ins['status'] === 'paid') $paidCount++;
                        }
                        $totalIns = count($installments);
                        $progressPercent = ($totalIns > 1) ? ($paidCount / ($totalIns - 1)) * 100 : 0;
                        if ($progressPercent > 100) $progressPercent = 100;
                        ?>
                        <div class="timeline-horizontal-line-progress" style="width: <?php echo $progressPercent; ?>%;"></div>
                    </div>

                    <!-- Step items -->
                    <?php 
                    $foundFirstUnpaid = false;
                    foreach ($installments as $idx => $ins): 
                        $stepClass = '';
                        $nodeContent = '';
                        
                        if ($ins['status'] === 'paid') {
                            $stepClass = 'paid';
                            $nodeContent = '✓';
                        } else if (!$foundFirstUnpaid) {
                            $stepClass = 'due';
                            $nodeContent = '📅';
                            $foundFirstUnpaid = true;
                        } else {
                            $stepClass = 'locked';
                            $nodeContent = '🔒';
                        }
                    ?>
                        <div class="timeline-step <?php echo $stepClass; ?>">
                            <div class="timeline-node">
                                <?php echo $nodeContent; ?>
                            </div>
                            <div class="timeline-step-label">
                                <?php 
                                if ($idx === 0) echo "Downpayment";
                                else echo "Payment " . ($idx + 1);
                                ?>
                            </div>
                            <div class="timeline-step-date">
                                <?php echo date('d M Y', strtotime($ins['due_date'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div style="text-align: center; padding: 40px 20px;">
                    <div style="font-size: 2.5rem; margin-bottom: 15px;">🛍️</div>
                    <h4 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0 0 10px 0;">No Active Payment Plans</h4>
                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 20px; max-width: 400px; margin-left: auto; margin-right: auto;">
                        Spread your purchase costs into 4 easy interest-free installments. Explore our merchant product catalog to get started.
                    </p>
                    <a href="/views/public/catalog.php" class="btn btn-primary" style="display: inline-block; padding: 12px 24px; font-size: 0.8rem; text-transform: none; border-radius: 10px;">
                        Browse Shop Catalog
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Transactions -->
        <div class="dashboard-card" style="margin-bottom: 60px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0;">Recent Transactions</h3>
                <a href="orders.php" style="font-size: 0.8rem; font-weight: 700; color: #005a4e; text-decoration: none;">VIEW ALL</a>
            </div>

            <?php if (count($transactions) > 0): ?>
                <div class="transaction-list">
                    <?php foreach ($transactions as $tx): ?>
                        <div class="transaction-row">
                            <div class="transaction-meta">
                                <div class="transaction-icon">
                                    <?php echo ($tx['type'] === 'payment') ? '💳' : '🛒'; ?>
                                </div>
                                <div>
                                    <div class="transaction-title">
                                        <?php echo ($tx['type'] === 'payment') ? 'Installment Payment' : 'Purchase: ' . htmlspecialchars($tx['product_name']); ?>
                                    </div>
                                    <div class="transaction-date">
                                        <?php if ($tx['type'] === 'payment'): ?>
                                            Plan: <strong><?php echo htmlspecialchars($tx['product_name']); ?></strong> • Store: <strong><?php echo htmlspecialchars($tx['store_name']); ?></strong> • <?php echo date('d M Y', strtotime($tx['transaction_date'])); ?>
                                        <?php else: ?>
                                            Store: <strong><?php echo htmlspecialchars($tx['store_name']); ?></strong> • <?php echo date('d M Y', strtotime($tx['transaction_date'])); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="transaction-amount" style="color: <?php echo ($tx['type'] === 'payment') ? '#005a4e' : '#0f172a'; ?>;">
                                    <?php echo ($tx['type'] === 'payment') ? '+' : '-'; ?><?php echo number_format($tx['amount'], 2); ?> DH
                                </div>
                                <div>
                                    <?php if ($tx['type'] === 'payment'): ?>
                                        <span class="badge badge-success" style="font-size: 0.65rem; padding: 2px 6px;">Success</span>
                                    <?php else: ?>
                                        <?php if ($tx['order_status'] === 'paid'): ?>
                                            <span class="badge badge-success" style="font-size: 0.65rem; padding: 2px 6px;">Paid Off</span>
                                        <?php elseif ($tx['order_status'] === 'active'): ?>
                                            <span class="badge badge-info" style="font-size: 0.65rem; padding: 2px 6px;">Active</span>
                                        <?php elseif ($tx['order_status'] === 'pending'): ?>
                                            <span class="badge badge-warning" style="font-size: 0.65rem; padding: 2px 6px;">Pending Plan</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger" style="font-size: 0.65rem; padding: 2px 6px;">Cancelled</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 30px 20px; color: #64748b; font-size: 0.85rem;">
                    No transactions recorded yet.
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
