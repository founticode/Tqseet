<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

$db = new Database();
$conn = $db->connect();

// 1. Financial Analytics
// GMV = Sum of all orders that are successfully active or completed
$totalVolume = $conn->query("SELECT SUM(total_price) FROM orders WHERE status IN ('active', 'paid')")->fetch_row()[0] ?? 0;

// Net Revenue = Sum of the actual commission stored in the orders table
$totalRevenue = $conn->query("SELECT SUM(commission) FROM orders WHERE status IN ('active', 'paid')")->fetch_row()[0] ?? 0;

$totalOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status IN ('active', 'paid')")->fetch_row()[0];

// 2. Installment Health
$pendingInstallments = $conn->query("SELECT SUM(amount) FROM installments WHERE status = 'unpaid'")->fetch_row()[0] ?? 0;
$collectedInstallments = $conn->query("SELECT SUM(amount) FROM installments WHERE status = 'paid'")->fetch_row()[0] ?? 0;

// 3. Top Merchants
$topMerchants = $conn->query("
    SELECT m.store_name, COUNT(o.id) as sales_count, SUM(o.total_price) as total_revenue
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN merchants m ON p.merchant_id = m.id
    WHERE o.status IN ('active', 'paid')
    GROUP BY m.id
    ORDER BY total_revenue DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Analytics - TQSEET Admin</title>
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
    <style>
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 40px; }
        .data-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 32px; align-items: start; }
        @media (max-width: 1024px) {
            .data-grid { grid-template-columns: 1fr; }
        }
        .metric-card {
            background: white; padding: 32px; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;
        }
        .metric-card.dark {
            background: #111827; color: white; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .metric-label { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
        .metric-value { font-size: 2.2rem; font-weight: 900; letter-spacing: -0.5px; margin-bottom: 8px; }
        .metric-sub { font-size: 0.85rem; font-weight: 600; }
        
        .table-container { background: white; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 20px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left; }
        td { padding: 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        tr:last-child td { border-bottom: none; }
        
        .progress-bar-bg { height: 12px; background: #f1f5f9; border-radius: 10px; overflow: hidden; margin-top: 10px; }
        .progress-bar-fill { height: 100%; }
    </style>
</head>
<body>

    <!-- Premium Admin Sidebar -->
    <?php include_once __DIR__ . "/../../includes/admin_sidebar.php"; ?>

    <main class="main-content">
        
        <header class="page-header" style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 2rem; font-weight: 900; margin: 0 0 8px 0; color: #111827; letter-spacing: -0.5px;">System Analytics</h1>
                <p style="color: #6b7280; margin: 0; font-size: 1rem; font-weight: 500;">Financial performance and platform health metrics.</p>
            </div>
            <a href="dashboard.php" class="btn-secondary" style="font-size: 0.9rem; padding: 10px 16px;">
                ← Back to Tower
            </a>
        </header>

        <!-- Big Metric Cards -->
        <div class="analytics-grid">
            <div class="metric-card">
                <div class="metric-label" style="color: #6b7280;">Gross Volume (GMV)</div>
                <div class="metric-value" style="color: #111827;"><?php echo number_format($totalVolume, 2); ?> <span style="font-size: 1rem; color: #9ca3af;">DH</span></div>
                <div class="metric-sub" style="color: #10b981;">↑ Total movement across TQSEET</div>
            </div>

            <div class="metric-card dark">
                <div class="metric-label" style="color: #9ca3af;">Net Platform Revenue</div>
                <div class="metric-value"><?php echo number_format($totalRevenue, 2); ?> <span style="font-size: 1rem; color: #6b7280;">DH</span></div>
                <div class="metric-sub" style="color: #3b82f6;">Based on dynamic commissions</div>
            </div>

            <div class="metric-card">
                <div class="metric-label" style="color: #6b7280;">Transaction Count</div>
                <div class="metric-value" style="color: #111827;"><?php echo $totalOrders; ?></div>
                <div class="metric-sub" style="color: #3b82f6;">Successful paid checkouts</div>
            </div>
        </div>

        <div class="data-grid">
            
            <!-- Top Merchants List -->
            <div>
                <h3 style="font-weight: 800; font-size: 1.2rem; color: #111827; margin: 0 0 20px 0;">Top Performing Merchants</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Store</th>
                                <th>Orders</th>
                                <th style="text-align: right;">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($m = $topMerchants->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 800; color: #0f172a;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; justify-content: center; align-items: center; font-size: 0.8rem; font-weight: 900; color: #64748b;">
                                                <?php echo strtoupper(substr($m['store_name'], 0, 2)); ?>
                                            </div>
                                            <?php echo htmlspecialchars($m['store_name']); ?>
                                        </div>
                                    </td>
                                    <td style="color: #64748b; font-weight: 600;"><?php echo $m['sales_count']; ?> Sales</td>
                                    <td style="text-align: right; font-weight: 900; color: #10b981;"><?php echo number_format($m['total_revenue'], 2); ?> DH</td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if($topMerchants->num_rows === 0): ?>
                                <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 40px;">No sales data available yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Installment Health -->
            <div>
                <h3 style="font-weight: 800; font-size: 1.2rem; color: #111827; margin: 0 0 20px 0;">Cashflow Health</h3>
                <div class="card" style="padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <div style="margin-bottom: 32px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                            <div>
                                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #6b7280; letter-spacing: 1px; margin-bottom: 4px;">Collected Payments</div>
                                <div style="font-weight: 900; color: #10b981; font-size: 1.5rem;"><?php echo number_format($collectedInstallments, 2); ?> DH</div>
                            </div>
                        </div>
                        <?php 
                        $total_inst = ($collectedInstallments + $pendingInstallments) ?: 1;
                        $pct = ($collectedInstallments / $total_inst) * 100;
                        ?>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?php echo $pct; ?>%; background: #10b981;"></div>
                        </div>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                            <div>
                                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #6b7280; letter-spacing: 1px; margin-bottom: 4px;">Pending Receivables</div>
                                <div style="font-weight: 900; color: #f59e0b; font-size: 1.5rem;"><?php echo number_format($pendingInstallments, 2); ?> DH</div>
                            </div>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?php echo 100 - $pct; ?>%; background: #f59e0b;"></div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 12px; margin-top: 32px;">
                        <p style="font-size: 0.8rem; color: #64748b; line-height: 1.6; margin: 0; font-weight: 500;">
                            ℹ️ Pending Receivables represents the total value of installments yet to be paid by customers across all active plans.
                        </p>
                    </div>
                </div>
            </div>

        </div>

    </main>

</body>
</html>
