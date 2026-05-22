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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Verifications Hub</title>
    <!-- Tap into the premium CSS engine -->
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
</head>
<body>

    <!-- Premium Admin Sidebar -->
    <?php include_once __DIR__ . "/../../includes/admin_sidebar.php"; ?>

    <main class="main-content">
        
        <header class="page-header">
            <div>
                <h1>Verifications Hub</h1>
                <p style="color: #6b7280; font-size: 0.95rem; margin-top: 8px;">Manage credit applications and merchant onboardings.</p>
            </div>
        </header>

        <!-- Section 1: Merchants -->
        <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 16px;">Merchant Applications</h2>
        <div class="portal-table-wrapper" style="margin-bottom: 40px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Store Name</th>
                        <th>Owner</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_merchants->num_rows === 0): ?>
                        <tr><td colspan="4" style="padding: 40px; text-align: center; color: var(--text-muted);">No pending merchant applications.</td></tr>
                    <?php endif; ?>
                    <?php while($m = $res_merchants->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight: 600; color: #111827;"><?php echo htmlspecialchars($m['store_name']); ?></td>
                            <td style="color: #6b7280;"><?php echo htmlspecialchars($m['name']); ?></td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 6px; align-items: start;">
                                    <?php if ($m['is_verified']): ?>
                                        <span class="status-badge status-active">📱 Owner OTP Verified</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending" style="color: #ef4444; background: #fef2f2;">📱 Owner OTP Pending</span>
                                    <?php endif; ?>
                                    <span class="status-badge status-pending" style="color: #f59e0b; background: #fffbeb;">🏬 Business Pending Approval</span>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <a href="view_user.php?id=<?php echo $m['user_id']; ?>" style="color: #3b82f6; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Review Business →</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Section 2: Customers -->
        <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 16px;">Customer Credit Reviews</h2>
        <div class="portal-table-wrapper" style="margin-bottom: 40px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($u = $res_users->fetch_assoc()): ?>
                        <?php 
                            $st = $conn->prepare("SELECT status FROM user_financials WHERE user_id = ?");
                            $st->bind_param("i", $u['id']);
                            $st->execute();
                            $f_status = $st->get_result()->fetch_assoc()['status'] ?? 'none';
                            $st->close();

                            $st_i = $conn->prepare("SELECT status FROM user_verifications WHERE user_id = ?");
                            $st_i->bind_param("i", $u['id']);
                            $st_i->execute();
                            $id_status = $st_i->get_result()->fetch_assoc()['status'] ?? 'none';
                            $st_i->close();
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: #111827;"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div style="font-size: 0.85rem; color: #6b7280;"><?php echo htmlspecialchars($u['email']); ?></div>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 6px; align-items: start;">
                                    <?php if ($u['is_verified']): ?>
                                        <span class="status-badge status-active">📱 OTP Verified</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending" style="color: #ef4444; background: #fef2f2;">📱 OTP Pending</span>
                                    <?php endif; ?>

                                    <?php if ($f_status === 'approved' && $id_status === 'approved'): ?>
                                        <span class="status-badge status-active">💼 KYC Approved</span>
                                    <?php elseif ($f_status === 'rejected' || $id_status === 'rejected'): ?>
                                        <span class="status-badge status-pending" style="color: #ef4444; background: #fef2f2;">💼 KYC Rejected</span>
                                    <?php elseif ($f_status === 'pending' || $id_status === 'pending'): ?>
                                        <span class="status-badge status-pending" style="color: #f59e0b; background: #fffbeb;">💼 KYC Under Review</span>
                                    <?php else: ?>
                                        <span class="status-badge" style="color: #6b7280; background: #f3f4f6;">💼 KYC Not Started</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <a href="view_user.php?id=<?php echo $u['id']; ?>" style="color: #3b82f6; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Audit Profile →</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Section 3: Approved Merchants -->
        <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 16px;">Approved Partners (Commission Setup)</h2>
        <div class="portal-table-wrapper" style="margin-bottom: 40px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Store Name</th>
                        <th>Owner</th>
                        <th>Status</th>
                        <th>Commission</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_approved_merchants->num_rows === 0): ?>
                        <tr><td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted);">No approved merchants active yet.</td></tr>
                    <?php endif; ?>
                    <?php while($am = $res_approved_merchants->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight: 600; color: #111827;"><?php echo htmlspecialchars($am['store_name']); ?></td>
                            <td style="color: #6b7280;"><?php echo htmlspecialchars($am['name']); ?></td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 6px; align-items: start;">
                                    <?php if ($am['is_verified']): ?>
                                        <span class="status-badge status-active">📱 OTP Verified</span>
                                    <?php endif; ?>
                                    <span class="status-badge status-active">🏬 Business Approved</span>
                                </div>
                            </td>
                            <td>
                                <span style="background: #ecfdf5; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">
                                    <?php echo ($am['commission_rate'] * 100); ?>% commission
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="view_user.php?id=<?php echo $am['user_id']; ?>" style="color: #3b82f6; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Agreement Settings →</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </main>

</body>
</html>
