<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

$db = new Database();
$conn = $db->connect();

// Queries for customers and merchants
$query_users = "SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC";
$res_users = $conn->query($query_users);

$query_merchants = "SELECT m.*, u.name, u.email FROM merchants m JOIN users u ON m.user_id = u.id WHERE m.status = 'pending' ORDER BY m.created_at DESC";
$res_merchants = $conn->query($query_merchants);
?>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1100px; margin: 60px auto; padding: 0 20px;">
        
        <div style="margin-bottom: 40px;">
            <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">Pending Approvals</h1>
            <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Manage credit applications and merchant onboardings.</p>
        </div>

        <!-- Section 1: Merchants -->
        <h3 style="color: #2d3436; margin-bottom: 15px; font-weight: 800;">Merchant Applications</h3>
        <div style="background: white; border-radius: 20px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 50px;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #fafafa; text-align: left;">
                    <tr>
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Store Name</th>
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Owner</th>
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_merchants->num_rows === 0): ?>
                        <tr><td colspan="3" style="padding: 30px; text-align: center; color: #b2bec3;">No pending merchant applications.</td></tr>
                    <?php endif; ?>
                    <?php while($m = $res_merchants->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td style="padding: 20px; font-weight: 800;"><?php echo htmlspecialchars($m['store_name']); ?></td>
                            <td style="padding: 20px; color: #636e72;"><?php echo htmlspecialchars($m['name']); ?></td>
                            <td style="padding: 20px; text-align: right;">
                                <a href="view_user.php?id=<?php echo $m['user_id']; ?>" style="color: #0984e3; text-decoration: none; font-weight: bold; font-size: 0.9rem;">Review Business →</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Section 2: Customers -->
        <h3 style="color: #2d3436; margin-bottom: 15px; font-weight: 800;">Customer Credit Reviews</h3>
        <div style="background: white; border-radius: 20px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #fafafa; text-align: left;">
                    <tr>
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">User</th>
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Status</th>
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($u = $res_users->fetch_assoc()): ?>
                        <?php 
                            // Fetch financial status for better badge
                            $st = $conn->prepare("SELECT status FROM user_financials WHERE user_id = ?");
                            $st->bind_param("i", $u['id']);
                            $st->execute();
                            $f_status = $st->get_result()->fetch_assoc()['status'] ?? 'none';
                        ?>
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td style="padding: 20px;">
                                <div style="font-weight: 800;"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div style="font-size: 0.8rem; color: #b2bec3;"><?php echo htmlspecialchars($u['email']); ?></div>
                            </td>
                            <td style="padding: 20px;">
                                <?php if ($u['is_verified']): ?>
                                    <span style="color: #00b894; font-weight: bold; font-size: 0.85rem;">✅ Verified</span>
                                <?php elseif ($f_status === 'rejected'): ?>
                                    <span style="color: #d63031; font-weight: bold; font-size: 0.85rem;">❌ Rejected</span>
                                <?php else: ?>
                                    <span style="color: #b2bec3; font-weight: bold; font-size: 0.85rem;">⏳ Pending</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 20px; text-align: right;">
                                <a href="view_user.php?id=<?php echo $u['id']; ?>" style="color: #0984e3; text-decoration: none; font-weight: bold; font-size: 0.9rem;">Audit Profile →</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
