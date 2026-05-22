<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

$db = new Database();
$conn = $db->connect();

// Fetch all merchants with their user details and product counts
$sql = "SELECT 
            m.id as merchant_id,
            m.store_name,
            m.commission_rate,
            u.name as owner_name,
            u.email,
            u.created_at as joined_at,
            (SELECT COUNT(*) FROM products WHERE merchant_id = m.id) as product_count
        FROM merchants m
        JOIN users u ON m.user_id = u.id
        ORDER BY m.created_at DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Merchants - TQSEET Admin</title>
    <!-- Tap into the premium CSS engine -->
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
</head>
<body>

    <!-- Premium Admin Sidebar -->
    <?php include_once __DIR__ . "/../../includes/admin_sidebar.php"; ?>

    <main class="main-content">
        
        <header class="page-header" style="justify-content: space-between; align-items: flex-end;">
            <div>
                <h1>Platform Merchants</h1>
                <p style="color: #6b7280; font-size: 0.95rem; margin-top: 8px;">Review and manage all business partners on the TQSEET network.</p>
            </div>
        </header>

        <!-- Merchants Table -->
        <div class="portal-table-wrapper" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Merchant / Store</th>
                        <th>Owner Details</th>
                        <th>Commission</th>
                        <th>Inventory</th>
                        <th>Joined</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: var(--text-muted);">No merchants registered yet.</td>
                        </tr>
                    <?php endif; ?>

                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: #111827;"><?php echo htmlspecialchars($row['store_name'] ?: 'Unnamed Store'); ?></div>
                                <div style="font-size: 0.85rem; color: #6b7280;">Merchant ID: #<?php echo $row['merchant_id']; ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #4b5563;"><?php echo htmlspecialchars($row['owner_name']); ?></div>
                                <div style="font-size: 0.85rem; color: #6b7280;"><?php echo htmlspecialchars($row['email']); ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 800; color: #3b82f6;"><?php echo number_format($row['commission_rate'] * 100, 2); ?>%</div>
                                <div style="font-size: 0.75rem; color: #9ca3af; font-weight: 600;">PLATFORM CUT</div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #111827;"><?php echo $row['product_count']; ?> Items</div>
                                <div style="font-size: 0.75rem; color: #9ca3af; font-weight: 600;">LISTED</div>
                            </td>
                            <td style="color: #6b7280; font-size: 0.9rem;">
                                <?php echo date("M d, Y", strtotime($row['joined_at'])); ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <span class="status-badge status-active">Active Business</span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </main>

</body>
</html>
