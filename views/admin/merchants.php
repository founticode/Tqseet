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
    <title>Manage Merchants - TQSEET Admin</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1200px; margin: 60px auto; padding: 0 20px;">
        
        <div style="margin-bottom: 40px;">
            <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">Platform Merchants</h1>
            <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Review and manage all business partners on the TQSEET network.</p>
        </div>

        <!-- Merchants Table -->
        <div style="background: white; border-radius: 25px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); overflow: hidden;">
            
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; background: #fafafa; border-bottom: 1px solid #f1f1f1;">
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Merchant / Store</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Owner Details</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Commission</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Inventory</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Joined</th>
                        <th style="padding: 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; text-align: right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="6" style="padding: 60px; text-align: center; color: #b2bec3; font-style: italic;">No merchants registered yet.</td>
                        </tr>
                    <?php endif; ?>

                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #f8f9fa; transition: 0.2s;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='white'">
                            <td style="padding: 20px;">
                                <div style="font-weight: 800; color: #2d3436; font-size: 1rem;"><?php echo htmlspecialchars($row['store_name'] ?: 'Unnamed Store'); ?></div>
                                <div style="font-size: 0.8rem; color: #b2bec3; margin-top: 4px;">Merchant ID: #<?php echo $row['merchant_id']; ?></div>
                            </td>
                            <td style="padding: 20px;">
                                <div style="font-weight: 600; color: #2d3436;"><?php echo htmlspecialchars($row['owner_name']); ?></div>
                                <div style="font-size: 0.85rem; color: #636e72;"><?php echo htmlspecialchars($row['email']); ?></div>
                            </td>
                            <td style="padding: 20px;">
                                <div style="font-weight: 900; color: #0984e3;"><?php echo number_format($row['commission_rate'], 2); ?>%</div>
                                <div style="font-size: 0.7rem; color: #b2bec3; text-transform: uppercase; font-weight: bold;">Platform Cut</div>
                            </td>
                            <td style="padding: 20px;">
                                <div style="font-weight: 800; color: #2d3436;"><?php echo $row['product_count']; ?> Items</div>
                                <div style="font-size: 0.7rem; color: #b2bec3; text-transform: uppercase; font-weight: bold;">Listed Products</div>
                            </td>
                            <td style="padding: 20px; color: #636e72; font-size: 0.9rem;">
                                <?php echo date("M d, Y", strtotime($row['joined_at'])); ?>
                            </td>
                            <td style="padding: 20px; text-align: right;">
                                <span style="background: rgba(0, 184, 148, 0.1); color: #00b894; padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">Active Business</span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
