<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

$db = new Database();
$conn = $db->connect();

// Fetch all orders with User, Product info and Installment counts
$query = "
    SELECT o.*, u.name as user_name, u.email as user_email, p.name as product_name,
           (SELECT COUNT(*) FROM installments WHERE order_id = o.id AND status = 'paid') as paid_count,
           (SELECT COUNT(*) FROM installments WHERE order_id = o.id) as total_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN products p ON o.product_id = p.id
    ORDER BY o.created_at DESC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installment Wall - TQSEET Admin</title>
    <!-- Tap into the premium CSS engine -->
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
</head>
<body>

    <!-- Premium Admin Sidebar -->
    <?php include_once __DIR__ . "/../../includes/admin_sidebar.php"; ?>

    <main class="main-content">
        
        <header class="page-header" style="justify-content: space-between; align-items: flex-end;">
            <div>
                <h1>Installment Wall</h1>
                <p style="color: #6b7280; font-size: 0.95rem; margin-top: 8px;">Monitoring every active payment plan across the platform.</p>
            </div>
        </header>

        <div class="portal-table-wrapper" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Product / Plan</th>
                        <th>Total Volume</th>
                        <th>Progress</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr><td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted);">No installments found.</td></tr>
                    <?php endif; ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
                            $isComplete = ($row['paid_count'] == $row['total_count'] && $row['total_count'] > 0);
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: #111827;"><?php echo htmlspecialchars($row['user_name']); ?></div>
                                <div style="font-size: 0.85rem; color: #6b7280;"><?php echo htmlspecialchars($row['user_email']); ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #4b5563;"><?php echo htmlspecialchars($row['product_name']); ?></div>
                                <div style="font-size: 0.8rem; color: #9ca3af;">Plan #<?php echo $row['id']; ?></div>
                            </td>
                            <td style="font-weight: 800; color: #111827;">
                                <?php echo number_format($row['total_price'], 2); ?> <span style="font-size: 0.8rem; color: #6b7280; font-weight: 600;">DH</span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="font-weight: 700; color: <?php echo $isComplete ? '#10b981' : '#f59e0b'; ?>; font-size: 0.9rem;">
                                        <?php echo $row['paid_count']; ?> / <?php echo $row['total_count']; ?> Paid
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="view_plan.php?order_id=<?php echo $row['id']; ?>" class="btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; display: inline-block; white-space: nowrap;">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </main>

</body>
</html>
