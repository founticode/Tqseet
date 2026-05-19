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

$query_merchants = "SELECT m.*, u.name, u.email, u.is_verified FROM merchants m JOIN users u ON m.user_id = u.id WHERE m.status = 'pending' ORDER BY m.created_at DESC";
$res_merchants = $conn->query($query_merchants);

// Query for already approved merchants so admin can view and update their commission rates!
$query_approved_merchants = "SELECT m.*, u.name, u.email, u.is_verified FROM merchants m JOIN users u ON m.user_id = u.id WHERE m.status = 'approved' ORDER BY m.store_name ASC";
$res_approved_merchants = $conn->query($query_approved_merchants);
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
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Status</th>
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_merchants->num_rows === 0): ?>
                        <tr><td colspan="4" style="padding: 30px; text-align: center; color: #b2bec3;">No pending merchant applications.</td></tr>
                      <?php endif; ?>
                      <?php while($m = $res_merchants->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td style="padding: 20px; font-weight: 800;"><?php echo htmlspecialchars($m['store_name']); ?></td>
                            <td style="padding: 20px; color: #636e72;"><?php echo htmlspecialchars($m['name']); ?></td>
                            <td style="padding: 20px; vertical-align: middle;">
                                <div style="display: flex; flex-direction: column; gap: 6px; align-items: start;">
                                    <!-- 1. Owner OTP Verification Status Badge -->
                                    <?php if ($m['is_verified']): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #eafaf1; color: #27ae60; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(39, 174, 96, 0.1);">
                                            📱 Owner OTP Verified
                                        </span>
                                    <?php else: ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #fff5f5; color: #e74c3c; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(231, 76, 60, 0.1);">
                                            📱 Owner OTP Pending
                                        </span>
                                    <?php endif; ?>

                                    <!-- 2. Store Business Status Badge -->
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: #fff9db; color: #f08c00; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(240, 140, 0, 0.1);">
                                        🏬 Business Pending Approval
                                    </span>
                                </div>
                            </td>
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
                            // Fetch financial status
                            $st = $conn->prepare("SELECT status FROM user_financials WHERE user_id = ?");
                            $st->bind_param("i", $u['id']);
                            $st->execute();
                            $f_status = $st->get_result()->fetch_assoc()['status'] ?? 'none';
                            $st->close();

                            // Fetch identity status
                            $st_i = $conn->prepare("SELECT status FROM user_verifications WHERE user_id = ?");
                            $st_i->bind_param("i", $u['id']);
                            $st_i->execute();
                            $id_status = $st_i->get_result()->fetch_assoc()['status'] ?? 'none';
                            $st_i->close();
                        ?>
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td style="padding: 20px;">
                                <div style="font-weight: 800;"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div style="font-size: 0.8rem; color: #b2bec3;"><?php echo htmlspecialchars($u['email']); ?></div>
                            </td>
                            <td style="padding: 20px; vertical-align: middle;">
                                <div style="display: flex; flex-direction: column; gap: 6px; align-items: start;">
                                    <!-- 1. OTP Verification Status Badge -->
                                    <?php if ($u['is_verified']): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #eafaf1; color: #27ae60; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(39, 174, 96, 0.1);">
                                            📱 OTP Verified
                                        </span>
                                    <?php else: ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #fff5f5; color: #e74c3c; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(231, 76, 60, 0.1);">
                                            📱 OTP Pending
                                        </span>
                                    <?php endif; ?>

                                    <!-- 2. KYC / Credit Hub Status Badge -->
                                    <?php if ($f_status === 'approved' && $id_status === 'approved'): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #eafaf1; color: #27ae60; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(39, 174, 96, 0.1);">
                                            💼 KYC Approved
                                        </span>
                                    <?php elseif ($f_status === 'rejected' || $id_status === 'rejected'): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #fff5f5; color: #e74c3c; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(231, 76, 60, 0.1);">
                                            💼 KYC Rejected
                                        </span>
                                    <?php elseif ($f_status === 'pending' || $id_status === 'pending'): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #fff9db; color: #f08c00; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(240, 140, 0, 0.1);">
                                            💼 KYC Under Review
                                        </span>
                                    <?php else: ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #f1f3f5; color: #868e96; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(134, 142, 150, 0.1);">
                                            💼 KYC Not Started
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding: 20px; text-align: right;">
                                <a href="view_user.php?id=<?php echo $u['id']; ?>" style="color: #0984e3; text-decoration: none; font-weight: bold; font-size: 0.9rem;">Audit Profile →</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Section 3: Approved Merchants (Newly Added for Commission Management) -->
        <h3 style="color: #2d3436; margin-top: 50px; margin-bottom: 15px; font-weight: 800;">Approved Partners & Merchants (Commission Settings)</h3>
        <div style="background: white; border-radius: 20px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 50px;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #fafafa; text-align: left;">
                    <tr>
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Store Name</th>
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Owner</th>
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Status</th>
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase;">Current Commission</th>
                        <th style="padding: 15px 20px; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_approved_merchants->num_rows === 0): ?>
                        <tr><td colspan="5" style="padding: 30px; text-align: center; color: #b2bec3;">No approved merchants active yet.</td></tr>
                    <?php endif; ?>
                    <?php while($am = $res_approved_merchants->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td style="padding: 20px; font-weight: 800; color: #2d3436;"><?php echo htmlspecialchars($am['store_name']); ?></td>
                            <td style="padding: 20px; color: #636e72;"><?php echo htmlspecialchars($am['name']); ?></td>
                            <td style="padding: 20px; vertical-align: middle;">
                                <div style="display: flex; flex-direction: column; gap: 6px; align-items: start;">
                                    <!-- 1. Owner OTP Verification Status Badge -->
                                    <?php if ($am['is_verified']): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #eafaf1; color: #27ae60; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(39, 174, 96, 0.1);">
                                            📱 Owner OTP Verified
                                        </span>
                                    <?php else: ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #fff5f5; color: #e74c3c; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(231, 76, 60, 0.1);">
                                            📱 Owner OTP Pending
                                        </span>
                                    <?php endif; ?>

                                    <!-- 2. Store Business Status Badge -->
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: #eafaf1; color: #27ae60; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(39, 174, 96, 0.1);">
                                        🏬 Business Approved
                                    </span>
                                </div>
                            </td>
                            <td style="padding: 20px;">
                                <span style="background: #e3faf2; color: #087f5b; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold;">
                                    <?php echo ($am['commission_rate'] * 100); ?>% commission
                                </span>
                            </td>
                            <td style="padding: 20px; text-align: right;">
                                <a href="view_user.php?id=<?php echo $am['user_id']; ?>" style="color: #0984e3; text-decoration: none; font-weight: bold; font-size: 0.9rem;">Agreement Settings →</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
