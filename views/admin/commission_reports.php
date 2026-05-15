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
    <title>Commission Reports - TQSEET</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1200px; margin: 60px auto; padding: 0 20px;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <div>
                <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">Revenue & Payouts</h1>
                <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Financial reconciliation for all merchant partners.</p>
            </div>
            <a href="dashboard.php" style="color: #636e72; text-decoration: none; font-weight: bold; font-size: 0.9rem;">← Back to Command Tower</a>
        </div>

        <div style="background: white; border-radius: 25px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #222; text-align: left; color: white;">
                    <tr>
                        <th style="padding: 25px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Merchant Partner</th>
                        <th style="padding: 25px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Rate</th>
                        <th style="padding: 25px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Gross Volume</th>
                        <th style="padding: 25px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Our Commission</th>
                        <th style="padding: 25px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; text-align: right;">Merchant Payout</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($m = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td style="padding: 25px;">
                                <div style="font-weight: 800; font-size: 1.1rem;"><?php echo htmlspecialchars($m['store_name']); ?></div>
                                <div style="font-size: 0.8rem; color: #b2bec3;"><?php echo $m['order_count']; ?> Total Orders</div>
                            </td>
                            <td style="padding: 25px;">
                                <span style="background: #f1f3f5; padding: 5px 10px; border-radius: 6px; font-weight: bold; font-size: 0.85rem; color: #636e72;">
                                    <?php echo ($m['commission_rate'] * 100); ?>%
                                </span>
                            </td>
                            <td style="padding: 25px; font-weight: 700; color: #2d3436;">
                                <?php echo number_format($m['gross_volume'] ?? 0, 2); ?> DH
                            </td>
                            <td style="padding: 25px; font-weight: 900; color: #00b894;">
                                + <?php echo number_format($m['total_commission'] ?? 0, 2); ?> DH
                            </td>
                            <td style="padding: 25px; text-align: right; font-weight: 900; color: #0984e3;">
                                <?php echo number_format($m['total_payout'] ?? 0, 2); ?> DH
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 40px; background: #eef9ff; border-radius: 20px; padding: 30px; border: 1px solid #d1e9ff; display: flex; align-items: center; gap: 20px;">
            <div style="font-size: 2rem;">💡</div>
            <p style="margin: 0; color: #004085; font-size: 0.95rem; line-height: 1.6;">
                <strong>Admin Tip:</strong> This report shows the "Net Earning" for merchants. In a standard payout cycle, you should wire the <strong>Merchant Payout</strong> amount to their bank account and keep the <strong>Commission</strong> as platform profit.
            </p>
        </div>
    </div>

</body>
</html>
