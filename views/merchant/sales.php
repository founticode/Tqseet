<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Only merchants can see this
requireRole('merchant');
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// NEW: Lookup actual Merchant ID and Profile Data
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
                             WHERE p.merchant_id = ? AND o.status != 'cancelled'");
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
    <title>TQSEET Merchant - Sales Analytics</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1100px; margin: 60px auto; padding: 0 20px;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
            <div>
                <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">Sales Analytics</h1>
                <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Monitor your business performance and revenue.</p>
            </div>
            <div style="background: white; padding: 10px 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); font-weight: bold; font-size: 0.9rem;">
                Store: <?php echo htmlspecialchars($merchantData['store_name'] ?: $user['name']); ?>
            </div>
        </div>

        <!-- Stats Overview Grid -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 50px;">
            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                <div style="font-size: 0.75rem; color: #b2bec3; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Total Sales Volume</div>
                <div style="font-size: 2rem; font-weight: 900; color: #222;"><?php echo number_format($stats['total_volume'] ?? 0, 2); ?> <span style="font-size: 1rem; color: #b2bec3;">DH</span></div>
                <div style="margin-top: 10px; font-size: 0.85rem; color: #00b894; font-weight: bold;">↑ Tracking <?php echo $stats['total_orders'] ?? 0; ?> orders</div>
            </div>

            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                <div style="font-size: 0.75rem; color: #b2bec3; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Platform Fees (<?php echo $commissionRate * 100; ?>%)</div>
                <div style="font-size: 2rem; font-weight: 900; color: #d63031;">-<?php echo number_format($totalCommission, 2); ?> <span style="font-size: 1rem; color: #b2bec3;">DH</span></div>
                <div style="margin-top: 10px; font-size: 0.85rem; color: #636e72; font-weight: 500;">TQSEET Service Fee</div>
            </div>

            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); background: #222; color: white;">
                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5); font-weight: 900; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Net Revenue</div>
                <div style="font-size: 2rem; font-weight: 900;"><?php echo number_format($netRevenue, 2); ?> <span style="font-size: 1rem; opacity: 0.5;">DH</span></div>
                <div style="margin-top: 10px; font-size: 0.85rem; color: #00b894; font-weight: bold;">Ready for Payout</div>
            </div>
        </div>

        <!-- Sales Table Section -->
        <div style="background: white; border-radius: 25px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
            <h3 style="margin: 0 0 30px 0; font-weight: 900; font-size: 1.4rem;">Transaction History</h3>

            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid #f8f9fa;">
                        <th style="padding: 15px 0; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Customer</th>
                        <th style="padding: 15px 0; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Product</th>
                        <th style="padding: 15px 0; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Amount</th>
                        <th style="padding: 15px 0; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Date</th>
                        <th style="padding: 15px 0; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; text-align: right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($salesRows)): ?>
                        <tr>
                            <td colspan="5" style="padding: 40px; text-align: center; color: #b2bec3; font-style: italic;">No sales recorded yet.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($salesRows as $sale): ?>
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td style="padding: 20px 0;">
                                <div style="font-weight: bold; color: #222;"><?php echo htmlspecialchars($sale['customer_name']); ?></div>
                                <div style="font-size: 0.8rem; color: #b2bec3;"><?php echo htmlspecialchars($sale['customer_email']); ?></div>
                            </td>
                            <td style="padding: 20px 0; font-weight: 600;"><?php echo htmlspecialchars($sale['product_name']); ?></td>
                            <td style="padding: 20px 0; font-weight: 900; color: #222;"><?php echo number_format($sale['total_price'], 2); ?> DH</td>
                            <td style="padding: 20px 0; color: #636e72; font-size: 0.9rem;"><?php echo date('d M Y', strtotime($sale['created_at'])); ?></td>
                            <td style="padding: 20px 0; text-align: right;">
                                <?php 
                                    $displayStatus = $sale['status'] ?: 'processing';
                                    $statusColor = '#636e72'; // Default grey
                                    if ($displayStatus === 'active') $statusColor = '#00b894';   // Green
                                    if ($displayStatus === 'paid') $statusColor = '#0984e3';     // Blue
                                    if ($displayStatus === 'pending') $statusColor = '#f39c12';  // Orange
                                    if ($displayStatus === 'cancelled') $statusColor = '#d63031'; // Red
                                ?>
                                <span style="background: <?php echo $statusColor; ?>15; color: <?php echo $statusColor; ?>; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                                    <?php echo $displayStatus; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
