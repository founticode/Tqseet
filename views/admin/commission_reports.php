<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

$db = new Database();
$conn = $db->connect();

// Fetch commission breakdown per merchant
$query = "
    SELECT 
        m.store_name, 
        m.commission_rate,
        COUNT(o.id) as order_count,
        SUM(o.total_price) as gross_volume,
        SUM(o.commission) as total_commission,
        SUM(o.merchant_earning) as total_payout
    FROM merchants m
    LEFT JOIN products p ON m.id = p.merchant_id
    LEFT JOIN orders o ON p.id = o.product_id AND o.status != 'cancelled'
    GROUP BY m.id
    ORDER BY gross_volume DESC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission Reports - TQSEET</title>
    <!-- Tap into the premium CSS engine -->
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
</head>
<body>

    <!-- Premium Admin Sidebar -->
    <?php include_once __DIR__ . "/../../includes/admin_sidebar.php"; ?>

    <main class="main-content">
        
        <header class="page-header" style="justify-content: space-between; align-items: flex-end;">
            <div>
                <h1>Revenue & Payouts</h1>
                <p style="color: #6b7280; font-size: 0.95rem; margin-top: 8px;">Financial reconciliation for all merchant partners.</p>
            </div>
        </header>

        <div class="portal-table-wrapper" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <table class="portal-table">
                <thead style="background: #111827; color: white;">
                    <tr>
                        <th style="color: #9ca3af;">Merchant Partner</th>
                        <th style="color: #9ca3af;">Rate</th>
                        <th style="color: #9ca3af;">Gross Volume</th>
                        <th style="color: #9ca3af;">Our Commission</th>
                        <th style="text-align: right; color: #9ca3af;">Merchant Payout</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr><td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted);">No revenue data generated yet.</td></tr>
                    <?php endif; ?>
                    <?php while($m = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; font-size: 1.1rem; color: #111827;"><?php echo htmlspecialchars($m['store_name']); ?></div>
                                <div style="font-size: 0.85rem; color: #6b7280;"><?php echo $m['order_count']; ?> Total Orders</div>
                            </td>
                            <td>
                                <span style="background: #f3f4f6; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; color: #4b5563;">
                                    <?php echo ($m['commission_rate'] * 100); ?>%
                                </span>
                            </td>
                            <td style="font-weight: 700; color: #111827;">
                                <?php echo number_format($m['gross_volume'] ?? 0, 2); ?> DH
                            </td>
                            <td style="font-weight: 800; color: #10b981;">
                                + <?php echo number_format($m['total_commission'] ?? 0, 2); ?> DH
                            </td>
                            <td style="text-align: right; font-weight: 800; color: #3b82f6;">
                                <?php echo number_format($m['total_payout'] ?? 0, 2); ?> DH
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 40px; background: #eff6ff; border-radius: 12px; padding: 24px; border: 1px solid #bfdbfe; display: flex; align-items: flex-start; gap: 16px;">
            <div style="font-size: 1.5rem;">💡</div>
            <p style="margin: 0; color: #1e3a8a; font-size: 0.95rem; line-height: 1.6;">
                <strong>Admin Tip:</strong> This report shows the "Net Earning" for merchants. In a standard payout cycle, you should wire the <strong>Merchant Payout</strong> amount to their bank account and keep the <strong>Our Commission</strong> as platform profit.
            </p>
        </div>
        
    </main>

</body>
</html>
