<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Merchants allowed!
requireRole("merchant");

$merchantId = $_SESSION["merchant_id"] ?? null;

$db = new Database();
$conn = $db->connect();

// Join Orders with Products to get orders belonging to this merchant
// We also join with Users to see the customer name
$query = "SELECT o.*, p.name as product_name, u.name as customer_name 
          FROM orders o
          JOIN products p ON o.product_id = p.id
          JOIN users u ON o.user_id = u.id
          WHERE p.merchant_id = ?
          ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $merchantId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - My Sales</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #f8f9fa;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1000px; margin: 30px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h1 style="border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">Sales & Order Tracking</h1>
        
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f1f1f1;">
                <tr>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Order #</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Customer</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Product</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Total Price</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Status</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px; font-weight: bold; color: #666;">#<?php echo $row['id']; ?></td>
                            <td style="padding: 12px;"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                            <td style="padding: 12px;"><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td style="padding: 12px; font-weight: bold;"><?php echo number_format($row['total_price'], 2); ?> DH</td>
                            <td style="padding: 12px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; 
                                    background: <?php echo $row['status'] === 'paid' ? '#d4edda' : ($row['status'] === 'cancelled' ? '#f8d7da' : '#fff3cd'); ?>;
                                    color: <?php echo $row['status'] === 'paid' ? '#155724' : ($row['status'] === 'cancelled' ? '#721c24' : '#856404'); ?>;">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td style="padding: 12px; color: #666; font-size: 0.9rem;">
                                <?php echo date("M d, Y", strtotime($row['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 60px; color: #666;">
                            <p style="font-size: 1.2rem; margin-bottom: 0;">You haven't received any orders yet.</p>
                            <p style="margin-top: 10px;">Keep adding products to increase your chances!</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border-radius: 6px; border: 1px solid #b8daff; color: #004085; font-size: 0.9rem;">
            <strong>Note:</strong> Sales earnings are calculated after deducting the platform commission.
        </div>
    </div>

</body>
</html>
