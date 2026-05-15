<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

$db = new Database();
$conn = $db->connect();

// 1. Financial Analytics
// GMV = Sum of all orders that aren't cancelled
$totalVolume = $conn->query("SELECT SUM(total_price) FROM orders WHERE status != 'cancelled'")->fetch_row()[0] ?? 0;

// Net Revenue = Sum of the actual commission stored in the orders table
$totalRevenue = $conn->query("SELECT SUM(commission) FROM orders WHERE status != 'cancelled'")->fetch_row()[0] ?? 0;

$totalOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'")->fetch_row()[0];

// 2. Installment Health
$pendingInstallments = $conn->query("SELECT SUM(amount) FROM installments WHERE status = 'unpaid'")->fetch_row()[0] ?? 0;
$collectedInstallments = $conn->query("SELECT SUM(amount) FROM installments WHERE status = 'paid'")->fetch_row()[0] ?? 0;

// 3. Top Merchants
$topMerchants = $conn->query("
    SELECT m.store_name, COUNT(o.id) as sales_count, SUM(o.total_price) as total_revenue
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN merchants m ON p.merchant_id = m.id
    WHERE o.status != 'cancelled'
    GROUP BY m.id
    ORDER BY total_revenue DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Analytics - TQSEET Admin</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1200px; margin: 60px auto; padding: 0 20px;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <div>
                <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">System Analytics</h1>
                <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Financial performance and platform health metrics.</p>
            </div>
            <a href="dashboard.php" style="color: #636e72; text-decoration: none; font-weight: bold; font-size: 0.9rem;">← Back to Command Tower</a>
        </div>

        <!-- Big Metric Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 50px;">
            <div style="background: white; padding: 40px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                <div style="color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 15px;">Gross Volume (GMV)</div>
                <div style="font-size: 2.2rem; font-weight: 900; color: #2d3436;"><?php echo number_format($totalVolume, 2); ?> DH</div>
                <div style="margin-top: 15px; color: #00b894; font-weight: bold; font-size: 0.85rem;">↑ Total movement across TQSEET</div>
            </div>

            <div style="background: #222; padding: 40px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <div style="color: rgba(255,255,255,0.4); font-size: 0.75rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 15px;">Net Platform Revenue</div>
                <div style="font-size: 2.2rem; font-weight: 900; color: white;"><?php echo number_format($totalRevenue, 2); ?> DH</div>
                <div style="margin-top: 15px; color: #fab1a0; font-weight: bold; font-size: 0.85rem;">Based on 5% standard commission</div>
            </div>

            <div style="background: white; padding: 40px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                <div style="color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 15px;">Transaction Count</div>
                <div style="font-size: 2.2rem; font-weight: 900; color: #2d3436;"><?php echo $totalOrders; ?></div>
                <div style="margin-top: 15px; color: #0984e3; font-weight: bold; font-size: 0.85rem;">Successful paid checkouts</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 40px;">
            
            <!-- Top Merchants List -->
            <div>
                <h3 style="font-weight: 900; margin-bottom: 20px;">Top Performing Merchants</h3>
                <div style="background: white; border-radius: 25px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #fafafa; text-align: left;">
                            <tr>
                                <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Store</th>
                                <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Orders</th>
                                <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; text-align: right;">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($m = $topMerchants->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid #f8f9fa;">
                                    <td style="padding: 20px; font-weight: 800;"><?php echo htmlspecialchars($m['store_name']); ?></td>
                                    <td style="padding: 20px; color: #636e72;"><?php echo $m['sales_count']; ?> Sales</td>
                                    <td style="padding: 20px; text-align: right; font-weight: 900; color: #00b894;"><?php echo number_format($m['total_revenue'], 2); ?> DH</td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Installment Health -->
            <div>
                <h3 style="font-weight: 900; margin-bottom: 20px;">Cashflow Health</h3>
                <div style="background: white; padding: 30px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                    <div style="margin-bottom: 25px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="font-weight: bold; font-size: 0.9rem;">Collected Payments</span>
                            <span style="font-weight: 900; color: #00b894;"><?php echo number_format($collectedInstallments, 2); ?> DH</span>
                        </div>
                        <div style="height: 12px; background: #f1f3f5; border-radius: 10px; overflow: hidden;">
                            <?php 
                            $total_inst = ($collectedInstallments + $pendingInstallments) ?: 1;
                            $pct = ($collectedInstallments / $total_inst) * 100;
                            ?>
                            <div style="width: <?php echo $pct; ?>%; height: 100%; background: #00b894;"></div>
                        </div>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="font-weight: bold; font-size: 0.9rem;">Pending Receivables</span>
                            <span style="font-weight: 900; color: #fdcb6e;"><?php echo number_format($pendingInstallments, 2); ?> DH</span>
                        </div>
                        <div style="height: 12px; background: #f1f3f5; border-radius: 10px; overflow: hidden;">
                            <div style="width: <?php echo 100 - $pct; ?>%; height: 100%; background: #fdcb6e;"></div>
                        </div>
                    </div>

                    <p style="font-size: 0.8rem; color: #b2bec3; line-height: 1.6; margin-top: 30px; border-top: 1px solid #f1f1f1; padding-top: 20px;">
                        * This represents the total value of installments yet to be paid by customers across all active plans.
                    </p>
                </div>
            </div>

        </div>

    </div>

</body>
</html>
