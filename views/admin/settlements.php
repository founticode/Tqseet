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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #fafbfc;
            margin: 0;
            color: #1e293b;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 24px;
        }

        h1, h2, h3, h4 {
            font-family: 'Outfit', sans-serif;
            color: #0f172a;
        }

        .serif-title {
            letter-spacing: -1.5px;
            color: #005a4e;
            margin: 0 0 8px 0;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .stat-title {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: #0f172a;
            font-family: 'Outfit', sans-serif;
        }
        
        .stat-value.alert { color: #f59e0b; }
        .stat-value.success { color: #10b981; }

        /* Tables */
        .data-table-container {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .data-table th {
            padding: 16px 24px;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table td {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .data-table tr:last-child td { border-bottom: none; }

        .btn-action {
            background: #0f172a;
            color: white;
            padding: 10px 20px;
            border-radius: 99px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-action:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-paid {
            background: #d1fae5;
            color: #065f46;
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="dashboard-container">
        
        <header style="margin-bottom: 40px;">
            <h1 class="serif-title" style="font-size: 2.5rem;">Finance & Settlements Hub</h1>
            <p style="color: #64748b; font-size: 1.1rem; margin: 0;">Process merchant wire transfers and manage platform liquidity.</p>
        </header>

        <!-- Alerts -->
        <?php if (isset($_GET['success'])): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 20px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; border: 1px solid #a7f3d0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                ✅ <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 20px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; border: 1px solid #fecaca; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                ❌ <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Action Required: Pending Wire Transfers</div>
                <div class="stat-value alert"><?php echo number_format($total_pending, 2); ?> <span style="font-size: 1.2rem; color: #64748b;">DH</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Lifetime Settled</div>
                <div class="stat-value success"><?php echo number_format($total_paid, 2); ?> <span style="font-size: 1.2rem; color: #64748b;">DH</span></div>
            </div>
        </div>

        <h2 style="font-size: 1.5rem; margin-bottom: 20px;">Pending Settlement Requests</h2>
        <div class="data-table-container">
            <table class="data-table">
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
                            <td colspan="5" style="text-align: center; padding: 60px; color: #64748b;">
                                🎉 No pending wire transfers! All merchants have been paid.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $pending->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 700; font-family: monospace; color: #64748b;">
                                    #<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($row['store_name']); ?></div>
                                    <div style="font-size: 0.85rem; color: #64748b;"><?php echo htmlspecialchars($row['merchant_email']); ?></div>
                                </td>
                                <td style="font-size: 1.2rem; font-weight: 700; color: #f59e0b;">
                                    <?php echo number_format($row['amount'], 2); ?> DH
                                </td>
                                <td style="color: #64748b;">
                                    <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                </td>
                                <td style="text-align: right;">
                                    <form action="../../controllers/AdminSettlementController.php" method="POST" style="margin: 0; display: inline-block;" onsubmit="return confirm('WARNING: Are you absolutely sure you have wired <?php echo number_format($row['amount'], 2); ?> DH to <?php echo htmlspecialchars($row['store_name']); ?>? This action cannot be undone.');">
                                        <input type="hidden" name="action" value="mark_paid">
                                        <input type="hidden" name="settlement_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn-action">
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

        <h2 style="font-size: 1.5rem; margin-bottom: 20px;">Payout History Ledger</h2>
        <div class="data-table-container">
            <table class="data-table">
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
                            <td colspan="5" style="text-align: center; padding: 60px; color: #64748b;">
                                No history found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $history->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 700; font-family: monospace; color: #64748b;">
                                    #<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?>
                                </td>
                                <td style="font-weight: 600; color: #0f172a;">
                                    <?php echo htmlspecialchars($row['store_name']); ?>
                                </td>
                                <td style="font-size: 1.1rem; font-weight: 700; color: #0f172a;">
                                    <?php echo number_format($row['amount'], 2); ?> DH
                                </td>
                                <td>
                                    <span class="badge-paid">● Successfully Paid</span>
                                </td>
                                <td style="color: #64748b; font-size: 0.9rem;">
                                    <?php echo date('M d, Y • h:i A', strtotime($row['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>
