<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Strict Security: Only Admins can view this
requireRole("admin");

$db = new Database();
$conn = $db->connect();

// 1. Fetch Pending Settlements (Action Required)
$sql_pending = "SELECT s.*, m.store_name, u.email as merchant_email
                FROM settlements s 
                JOIN merchants m ON s.merchant_id = m.id 
                JOIN users u ON m.user_id = u.id
                WHERE s.status = 'pending' 
                ORDER BY s.created_at ASC";
$pending = $conn->query($sql_pending);

// 2. Fetch Paid Settlements (History)
$sql_history = "SELECT s.*, m.store_name 
                FROM settlements s 
                JOIN merchants m ON s.merchant_id = m.id 
                WHERE s.status = 'paid' 
                ORDER BY s.created_at DESC 
                LIMIT 25";
$history = $conn->query($sql_history);

// 3. Calculate Financial Stats
$total_pending = $conn->query("SELECT SUM(amount) FROM settlements WHERE status = 'pending'")->fetch_row()[0] ?? 0;
$total_paid = $conn->query("SELECT SUM(amount) FROM settlements WHERE status = 'paid'")->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Finance Settlements</title>
    <!-- Tap into the premium CSS engine -->
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
</head>
<body>

    <!-- Premium Admin Sidebar -->
    <?php include_once __DIR__ . "/../../includes/admin_sidebar.php"; ?>

    <main class="main-content">
        
        <header class="page-header" style="justify-content: space-between; align-items: flex-end;">
            <div>
                <h1>Finance & Settlements Hub</h1>
                <p style="color: #6b7280; font-size: 0.95rem; margin-top: 8px;">Process merchant wire transfers and manage platform liquidity.</p>
            </div>
        </header>

        <!-- Alerts -->
        <?php if (isset($_GET['success'])): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 20px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; border: 1px solid #a7f3d0;">
                ✅ <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 20px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; border: 1px solid #fecaca;">
                ❌ <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 40px;">
            <div class="card" style="margin: 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: <?php echo $total_pending > 0 ? '1px solid #fecaca' : '1px solid #e2e8f0'; ?>;">
                <h3 style="color: #6b7280; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Action Required: Pending Wire Transfers</h3>
                <div style="font-size: 2.5rem; font-weight: 800; color: <?php echo $total_pending > 0 ? '#ef4444' : '#111827'; ?>;">
                    <?php echo number_format($total_pending, 2); ?> <span style="font-size: 1.2rem; color: #6b7280;">DH</span>
                </div>
            </div>
            <div class="card" style="margin: 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                <h3 style="color: #6b7280; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Total Lifetime Settled</h3>
                <div style="font-size: 2.5rem; font-weight: 800; color: #10b981;">
                    <?php echo number_format($total_paid, 2); ?> <span style="font-size: 1.2rem; color: #6b7280;">DH</span>
                </div>
            </div>
        </div>

        <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 16px;">Pending Settlement Requests</h2>
        <div class="portal-table-wrapper" style="margin-bottom: 40px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Batch ID</th>
                        <th>Merchant Store</th>
                        <th>Amount Due</th>
                        <th>Requested Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 60px; color: #6b7280;">
                                🎉 No pending wire transfers! All merchants have been paid.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $pending->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 700; font-family: monospace; color: #6b7280;">
                                    #<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #111827;"><?php echo htmlspecialchars($row['store_name']); ?></div>
                                    <div style="font-size: 0.85rem; color: #6b7280;"><?php echo htmlspecialchars($row['merchant_email']); ?></div>
                                </td>
                                <td style="font-size: 1.1rem; font-weight: 800; color: #ef4444;">
                                    <?php echo number_format($row['amount'], 2); ?> DH
                                </td>
                                <td style="color: #6b7280;">
                                    <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                </td>
                                <td style="text-align: right;">
                                    <form action="../../controllers/AdminSettlementController.php" method="POST" style="margin: 0; display: inline-block;" onsubmit="return confirm('WARNING: Are you absolutely sure you have wired <?php echo number_format($row['amount'], 2); ?> DH to <?php echo htmlspecialchars($row['store_name']); ?>? This action cannot be undone.');">
                                        <input type="hidden" name="action" value="mark_paid">
                                        <input type="hidden" name="settlement_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn-black">
                                            Mark as Transferred
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 16px;">Payout History Ledger</h2>
        <div class="portal-table-wrapper" style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Batch ID</th>
                        <th>Merchant Store</th>
                        <th>Amount Sent</th>
                        <th>Status</th>
                        <th>Processed Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 60px; color: #6b7280;">
                                No history found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $history->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 700; font-family: monospace; color: #6b7280;">
                                    #<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?>
                                </td>
                                <td style="font-weight: 600; color: #111827;">
                                    <?php echo htmlspecialchars($row['store_name']); ?>
                                </td>
                                <td style="font-size: 1.1rem; font-weight: 800; color: #111827;">
                                    <?php echo number_format($row['amount'], 2); ?> DH
                                </td>
                                <td>
                                    <span class="status-badge status-active">● Successfully Paid</span>
                                </td>
                                <td style="color: #6b7280; font-size: 0.9rem;">
                                    <?php echo date('M d, Y • h:i A', strtotime($row['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</body>
</html>
