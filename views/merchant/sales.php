<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Only merchants can see this
requireRole('merchant');
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch actual Merchant ID and Profile Data
$stmt_m = $conn->prepare("SELECT * FROM merchants WHERE user_id = ?");
$stmt_m->bind_param("i", $user['id']);
$stmt_m->execute();
$merchantData = $stmt_m->get_result()->fetch_assoc();
$merchantId = $merchantData['id'] ?? 0;

// 1. Fetch Sales Analytics for this merchant
// Total Revenue (Total value of all active orders)
$stmt_stats = $conn->prepare("SELECT 
                                SUM(o.total_price) as total_volume,
                                COUNT(o.id) as total_orders
                             FROM orders o 
                             JOIN products p ON o.product_id = p.id 
                             WHERE p.merchant_id = ? AND o.status IN ('active', 'paid')");
$stmt_stats->bind_param("i", $merchantId);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// 2. Fetch Recent Sales (Orders)
$stmt_sales = $conn->prepare("SELECT 
                                o.*, 
                                p.name as product_name, 
                                u.name as customer_name,
                                u.email as customer_email
                             FROM orders o 
                             JOIN products p ON o.product_id = p.id 
                             JOIN users u ON o.user_id = u.id 
                             WHERE p.merchant_id = ? 
                             AND EXISTS (SELECT 1 FROM installments i WHERE i.order_id = o.id)
                             ORDER BY o.created_at DESC");
$stmt_sales->bind_param("i", $merchantId);
$stmt_sales->execute();
$salesRows = $stmt_sales->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate Platform Commission (Actual rate from merchant profile)
$commissionRate = $merchantData['commission_rate'] ?? 0.05;
$totalCommission = ($stats['total_volume'] ?? 0) * $commissionRate;
$netRevenue = ($stats['total_volume'] ?? 0) - $totalCommission;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - TQSEET</title>
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
</head>
<body>

    <!-- Sidebar -->
    <?php include_once __DIR__ . "/../../includes/merchant_sidebar.php"; ?>

    <!-- Main Content -->
    <main class="main-content">
        
        <header class="page-header">
            <h1>Orders</h1>
            <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-muted);">
                Store: <span style="color: var(--primary-black);"><?php echo htmlspecialchars($merchantData['store_name'] ?: $user['name']); ?></span>
            </div>
        </header>

        <!-- Stats Overview Grid -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-title">Total Sales Volume</div>
                <div class="stat-value"><?php echo number_format($stats['total_volume'] ?? 0, 2); ?> <span style="font-size: 1.1rem; color: var(--text-muted); font-weight: 600;">DH</span></div>
                <div style="margin-top: 8px; font-size: 0.8rem; color: #10b981; font-weight: 600;">↑ Tracking <?php echo $stats['total_orders'] ?? 0; ?> orders</div>
            </div>

            <div class="stat-box">
                <div class="stat-title">Platform Fees (<?php echo $commissionRate * 100; ?>%)</div>
                <div class="stat-value" style="color: #ef4444;">-<?php echo number_format($totalCommission, 2); ?> <span style="font-size: 1.1rem; color: var(--text-muted); font-weight: 600;">DH</span></div>
                <div style="margin-top: 8px; font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">TQSEET Service Fee</div>
            </div>

            <div class="stat-box" style="background: var(--primary-black); color: white;">
                <div class="stat-title" style="color: #9ca3af;">Net Revenue</div>
                <div class="stat-value" style="color: white;"><?php echo number_format($netRevenue, 2); ?> <span style="font-size: 1.1rem; color: #9ca3af; font-weight: 600;">DH</span></div>
                <div style="margin-top: 8px; font-size: 0.8rem; color: #d1fae5; font-weight: 600;">Ready for Payout</div>
            </div>
        </div>

        <h2 style="font-family: 'Outfit', sans-serif; margin: 32px 0 16px 0; font-size: 1.25rem;">Transaction History</h2>

        <!-- Sales Table Section -->
        <div class="portal-table-wrapper">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th style="text-align: right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($salesRows)): ?>
                        <tr>
                            <td colspan="5" style="padding: 60px; text-align: center; color: var(--text-muted);">
                                No sales recorded yet.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($salesRows as $sale): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--primary-black);"><?php echo htmlspecialchars($sale['customer_name']); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;"><?php echo htmlspecialchars($sale['customer_email']); ?></div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($sale['product_name']); ?>
                            </td>
                            <td style="font-weight: 700;">
                                <?php echo number_format($sale['total_price'], 2); ?> DH
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                <?php echo date('M d, Y', strtotime($sale['created_at'])); ?>
                            </td>
                            <td class="action-cell">
                                <?php 
                                    $displayStatus = $sale['status'] ?: 'processing';
                                    
                                    // Map old status names to cleaner Tabby-style pills
                                    $badgeClass = 'pending'; // Default
                                    
                                    if ($displayStatus === 'active') {
                                        $badgeClass = 'success';
                                    } elseif ($displayStatus === 'paid') {
                                        $badgeClass = 'action'; // gray badge for finished/paid
                                    } elseif ($displayStatus === 'cancelled') {
                                        $badgeClass = ''; // custom inline for red
                                    }
                                ?>
                                <?php if ($displayStatus === 'cancelled'): ?>
                                    <span class="status-badge" style="background: #fef2f2; color: #ef4444;">Cancelled</span>
                                <?php else: ?>
                                    <span class="status-badge <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($displayStatus); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>

</body>
</html>
