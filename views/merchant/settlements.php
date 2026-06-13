<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Merchants allowed!
requireRole("merchant");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// 1. Fetch Merchant Profile
$merchantData = ensureMerchantRecord($conn);
$merchantId = $merchantData['id'];

// SECURITY GATE
if ($merchantData['status'] !== 'approved') {
    header("Location: dashboard.php?error=pending_approval");
    exit;
}

// 2. Calculate Available for Payout (Active/Paid orders not yet settled)
$sql_avail = "SELECT SUM(o.merchant_earning) as available 
              FROM orders o JOIN products p ON o.product_id = p.id 
              WHERE p.merchant_id = ? AND o.status IN ('active', 'paid') AND o.settlement_id IS NULL";
$stmt_avail = $conn->prepare($sql_avail);
$stmt_avail->bind_param("i", $merchantId);
$stmt_avail->execute();
$available = $stmt_avail->get_result()->fetch_assoc()['available'] ?? 0;

// 3. Calculate Pending Settlements (Requested but not yet paid to bank)
$sql_pend = "SELECT SUM(amount) as pending FROM settlements WHERE merchant_id = ? AND status = 'pending'";
$stmt_pend = $conn->prepare($sql_pend);
$stmt_pend->bind_param("i", $merchantId);
$stmt_pend->execute();
$pending = $stmt_pend->get_result()->fetch_assoc()['pending'] ?? 0;

// 4. Calculate Lifetime Paid
$sql_paid = "SELECT SUM(amount) as paid FROM settlements WHERE merchant_id = ? AND status = 'paid'";
$stmt_paid = $conn->prepare($sql_paid);
$stmt_paid->bind_param("i", $merchantId);
$stmt_paid->execute();
$paid = $stmt_paid->get_result()->fetch_assoc()['paid'] ?? 0;

// 5. Get Settlement History
$stmt_hist = $conn->prepare("SELECT * FROM settlements WHERE merchant_id = ? ORDER BY created_at DESC LIMIT 15");
$stmt_hist->bind_param("i", $merchantId);
$stmt_hist->execute();
$history = $stmt_hist->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlements - TQSEET</title>
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
</head>
<body>

    <!-- Sidebar -->
    <?php include_once __DIR__ . "/../../includes/merchant_sidebar.php"; ?>

    <!-- Main Content -->
    <main class="main-content">
        
        <header class="page-header" style="justify-content: space-between; align-items: flex-end;">
            <div>
                <h1>Settlements</h1>
                <p style="color: #6b7280; font-size: 0.95rem; margin-top: 8px;">Withdraw your earnings directly to your bank account.</p>
            </div>
            
            <?php if ($available > 0): ?>
                <form action="../../controllers/SettlementController.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="request_payout" value="1">
                    <button type="submit" class="btn-black" style="padding: 12px 24px; font-size: 1rem;">
                        Request Payout
                    </button>
                </form>
            <?php else: ?>
                <button class="btn-black" style="padding: 12px 24px; font-size: 1rem; opacity: 0.5; cursor: not-allowed;" disabled>
                    Request Payout
                </button>
            <?php endif; ?>
        </header>

        <!-- Alerts -->
        <?php if (isset($_GET['success'])): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; border: 1px solid #a7f3d0;">
                ✅ <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; border: 1px solid #fecaca;">
                ❌ <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Financial Overview Cards -->
        <div class="stats-grid" style="margin-bottom: 40px;">
            <div class="card" style="margin: 0;">
                <h3 style="color: #6b7280; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Available to Payout</h3>
                <div style="font-size: 2rem; font-weight: 800; color: #10b981;">
                    <?php echo number_format($available, 2); ?> <span style="font-size: 1rem; color: #6b7280;">DH</span>
                </div>
            </div>
            <div class="card" style="margin: 0;">
                <h3 style="color: #6b7280; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Pending Bank Transfer</h3>
                <div style="font-size: 2rem; font-weight: 800; color: #f59e0b;">
                    <?php echo number_format($pending, 2); ?> <span style="font-size: 1rem; color: #6b7280;">DH</span>
                </div>
            </div>
            <div class="card" style="margin: 0;">
                <h3 style="color: #6b7280; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Total Settled Lifetime</h3>
                <div style="font-size: 2rem; font-weight: 800; color: #111827;">
                    <?php echo number_format($paid, 2); ?> <span style="font-size: 1rem; color: #6b7280;">DH</span>
                </div>
            </div>
        </div>

        <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 16px;">Payout History</h2>
        
        <div class="portal-table-wrapper">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Batch ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Requested Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history->num_rows === 0): ?>
                        <tr>
                            <td colspan="4" style="padding: 60px; text-align: center; color: var(--text-muted);">
                                You have not requested any payouts yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($batch = $history->fetch_assoc()): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 600; color: #6b7280;">
                                    #<?php echo str_pad($batch['id'], 6, '0', STR_PAD_LEFT); ?>
                                </td>
                                <td style="font-weight: 600; color: #111827;">
                                    <?php echo number_format($batch['amount'], 2); ?> DH
                                </td>
                                <td>
                                    <?php if ($batch['status'] === 'paid'): ?>
                                        <span style="background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; display: inline-flex; align-items: center; gap: 4px;">
                                            ● Paid
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; display: inline-flex; align-items: center; gap: 4px;">
                                            ● Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: #6b7280; font-size: 0.9rem;">
                                    <?php echo date('M d, Y • h:i A', strtotime($batch['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

</body>
</html>
