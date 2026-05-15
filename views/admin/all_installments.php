<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

$db = new Database();
$conn = $db->connect();

// Fetch all installments with User and Product info
$query = "
    SELECT i.*, u.name as user_name, u.email as user_email, p.name as product_name
    FROM installments i
    JOIN orders o ON i.order_id = o.id
    JOIN users u ON o.user_id = u.id
    JOIN products p ON o.product_id = p.id
    ORDER BY i.due_date DESC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Installment Wall - TQSEET Admin</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1200px; margin: 60px auto; padding: 0 20px;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <div>
                <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">Installment Wall</h1>
                <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Monitoring every active payment plan across the platform.</p>
            </div>
            <a href="dashboard.php" style="color: #636e72; text-decoration: none; font-weight: bold; font-size: 0.9rem;">← Back to Command Tower</a>
        </div>

        <div style="background: white; border-radius: 25px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #fafafa; text-align: left;">
                    <tr>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Customer</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Product / Plan</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Amount</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Due Date</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; text-align: right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
                            $isOverdue = (strtotime($row['due_date']) < time() && $row['status'] === 'unpaid');
                        ?>
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td style="padding: 20px;">
                                <div style="font-weight: 800;"><?php echo htmlspecialchars($row['user_name']); ?></div>
                                <div style="font-size: 0.8rem; color: #b2bec3;"><?php echo htmlspecialchars($row['user_email']); ?></div>
                            </td>
                            <td style="padding: 20px;">
                                <div style="font-weight: 600; color: #636e72;"><?php echo htmlspecialchars($row['product_name']); ?></div>
                                <div style="font-size: 0.75rem; color: #b2bec3;">Plan #<?php echo $row['order_id']; ?></div>
                            </td>
                            <td style="padding: 20px; font-weight: 900; color: #2d3436;">
                                <?php echo number_format($row['amount'], 2); ?> <span style="font-size: 0.7rem; color: #b2bec3;">DH</span>
                            </td>
                            <td style="padding: 20px;">
                                <div style="font-weight: bold; color: <?php echo $isOverdue ? '#d63031' : '#2d3436'; ?>;">
                                    <?php echo date('d M Y', strtotime($row['due_date'])); ?>
                                </div>
                            </td>
                            <td style="padding: 20px; text-align: right;">
                                <?php if ($row['status'] === 'paid'): ?>
                                    <span style="background: #eafaf1; color: #27ae60; padding: 6px 14px; border-radius: 30px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">Paid</span>
                                <?php elseif ($isOverdue): ?>
                                    <span style="background: #fff5f5; color: #d63031; padding: 6px 14px; border-radius: 30px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">Overdue</span>
                                <?php else: ?>
                                    <span style="background: #fdf9e7; color: #f39c12; padding: 6px 14px; border-radius: 30px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
