<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

$db = new Database();
$conn = $db->connect();

// Fetch Quick Stats
$totalUsers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetch_row()[0];
$totalMerchants = $conn->query("SELECT COUNT(*) FROM merchants")->fetch_row()[0];
$totalSales = $conn->query("SELECT SUM(total_price) FROM orders WHERE status = 'paid'")->fetch_row()[0] ?? 0;

$pendingCustomers = $conn->query("SELECT COUNT(DISTINCT user_id) FROM (SELECT user_id FROM user_verifications WHERE status = 'pending' UNION SELECT user_id FROM user_financials WHERE status = 'pending') AS p")->fetch_row()[0];
$pendingMerchants = $conn->query("SELECT COUNT(*) FROM merchants WHERE status = 'pending'")->fetch_row()[0];
$pendingVerif = $pendingCustomers + $pendingMerchants;

// Recent Activity Feed
$recent_orders = $conn->query("SELECT o.id, o.total_price, o.created_at, u.name as customer_name, m.store_name 
                               FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               JOIN products p ON o.product_id = p.id 
                               JOIN merchants m ON p.merchant_id = m.id 
                               ORDER BY o.created_at DESC LIMIT 5");

$pending_docs = $conn->query("SELECT user_id, cin as name, 'Customer KYC' as type FROM user_verifications WHERE status = 'pending' LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Admin Control Tower</title>
    <!-- Tap into the premium CSS engine -->
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
</head>
<body>

    <!-- Premium Admin Sidebar -->
    <?php include_once __DIR__ . "/../../includes/admin_sidebar.php"; ?>

    <main class="main-content">
        
        <header class="page-header" style="justify-content: space-between; align-items: flex-end;">
            <div>
                <h1>Control Tower</h1>
                <p style="color: #6b7280; font-size: 0.95rem; margin-top: 8px;">System-wide overview and operational commands.</p>
            </div>
            
            <a href="verifications.php" class="btn-black">
                Review Pending KYC
            </a>
        </header>

        <!-- Top Stats Row -->
        <div class="stats-grid" style="margin-bottom: 40px;">
            <div class="card" style="margin: 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                <h3 style="color: #6b7280; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Platform Volume</h3>
                <div style="font-size: 1.8rem; font-weight: 800; color: #111827;">
                    <?php echo number_format($totalSales, 2); ?> <span style="font-size: 1rem; color: #6b7280;">DH</span>
                </div>
            </div>
            <div class="card" style="margin: 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: <?php echo $pendingVerif > 0 ? '1px solid #fecaca' : '1px solid #e2e8f0'; ?>; background: <?php echo $pendingVerif > 0 ? '#fffafb' : 'white'; ?>;">
                <h3 style="color: #6b7280; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Action Required</h3>
                <div style="font-size: 1.8rem; font-weight: 800; color: <?php echo $pendingVerif > 0 ? '#ef4444' : '#111827'; ?>;">
                    <?php echo $pendingVerif; ?> <span style="font-size: 1rem; color: #6b7280;">Reviews</span>
                </div>
            </div>
            <div class="card" style="margin: 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                <h3 style="color: #6b7280; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Active Partners</h3>
                <div style="font-size: 1.8rem; font-weight: 800; color: #111827;">
                    <?php echo $totalMerchants; ?> <span style="font-size: 1rem; color: #6b7280;">Stores</span>
                </div>
            </div>
            <div class="card" style="margin: 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                <h3 style="color: #6b7280; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Registered Users</h3>
                <div style="font-size: 1.8rem; font-weight: 800; color: #111827;">
                    <?php echo $totalUsers; ?> <span style="font-size: 1rem; color: #6b7280;">Accounts</span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <!-- Main Feed -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin: 0;">Recent Platform Activity</h2>
                    <a href="all_installments.php" style="font-size: 0.9rem; color: #3b82f6; text-decoration: none; font-weight: 600;">View All →</a>
                </div>
                
                <div class="portal-table-wrapper" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    <table class="portal-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Store</th>
                                <th>Customer</th>
                                <th>Volume</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_orders->num_rows === 0): ?>
                                <tr>
                                    <td colspan="5" style="padding: 60px; text-align: center; color: var(--text-muted);">No recent orders on the platform.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-family: monospace; font-weight: 600; color: #6b7280;">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td style="font-weight: 600; color: #111827;"><?php echo htmlspecialchars($order['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td style="font-weight: 600; color: #10b981;"><?php echo number_format($order['total_price'], 2); ?> DH</td>
                                        <td style="color: #6b7280; font-size: 0.85rem;"><?php echo date('M d, h:i A', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Side Queue -->
            <div>
                <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 16px;">Pending Approvals</h2>
                
                <div class="card" style="padding: 0; overflow: hidden; margin: 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    <?php if ($pendingVerif == 0): ?>
                        <div style="padding: 40px; text-align: center; color: #10b981; font-weight: 600;">
                            ✅ Inbox Zero. No pending KYC.
                        </div>
                    <?php else: ?>
                        <?php while ($doc = $pending_docs->fetch_assoc()): ?>
                            <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight: 600; font-size: 0.95rem; color: #111827;">ID: <?php echo htmlspecialchars($doc['name']); ?></div>
                                    <div style="font-size: 0.8rem; color: #ef4444; font-weight: 600; margin-top: 4px;"><?php echo $doc['type']; ?></div>
                                </div>
                                <a href="verifications.php" style="background: #f1f5f9; color: #475569; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; text-decoration: none;">Review</a>
                            </div>
                        <?php endwhile; ?>
                        
                        <div style="padding: 12px; text-align: center; background: #f8fafc;">
                            <a href="verifications.php" style="font-size: 0.85rem; color: #3b82f6; text-decoration: none; font-weight: 600;">View All Queue</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>

</body>
</html>
