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

// Action Required: Count of actual pending Customer KYC documents + pending Merchant store onboardings
$pendingCustomers = $conn->query("SELECT COUNT(DISTINCT user_id) FROM (SELECT user_id FROM user_verifications WHERE status = 'pending' UNION SELECT user_id FROM user_financials WHERE status = 'pending') AS p")->fetch_row()[0];
$pendingMerchants = $conn->query("SELECT COUNT(*) FROM merchants WHERE status = 'pending'")->fetch_row()[0];
$pendingVerif = $pendingCustomers + $pendingMerchants;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Admin Control Tower</title>
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

        /* Typography styling */
        h1, h2, h3, h4 {
            font-family: 'Outfit', sans-serif;
            color: #0f172a;
        }

        .serif-title {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -1.5px;
            color: #005a4e;
        }

        /* Top header layout */
        .welcome-header {
            margin-bottom: 40px;
        }

        .welcome-header h1 {
            margin: 0;
            font-size: 2.6rem;
            font-weight: 900;
        }

        .welcome-header p {
            color: #64748b;
            margin: 8px 0 0 0;
            font-size: 1.05rem;
        }

        /* Quick Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-bottom: 45px;
        }

        .stat-card {
            background: white;
            padding: 28px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            border-color: #cbd5e1;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 900;
            color: #0f172a;
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.5px;
        }

        .stat-value span {
            font-size: 1.1rem;
            color: #94a3b8;
            font-weight: 700;
            margin-left: 4px;
        }

        /* Action Required Stat Card */
        .stat-card.action-required {
            border-color: #fecaca;
            background-color: #fffafb;
        }

        .stat-card.action-required .stat-value {
            color: #ef4444;
        }

        .stat-pill {
            font-size: 0.75rem;
            color: #475569;
            margin-top: 10px;
            font-weight: 600;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
            border: 1px solid #e2e8f0;
        }

        .stat-card.action-required .stat-pill {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fca5a5;
        }

        /* Admin Panels Cards Grid */
        .panels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 30px;
        }

        .panel-card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .panel-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.04);
            border-color: #cbd5e1;
        }

        .panel-card h3 {
            margin: 0;
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-card p {
            color: #64748b;
            line-height: 1.6;
            margin: 12px 0 30px 0;
            font-size: 0.95rem;
            flex-grow: 1;
        }

        /* Panel Card Button styling with colored themes */
        .btn-panel {
            display: block;
            padding: 15px;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            text-align: center;
            transition: all 0.2s ease;
            font-family: 'Outfit', sans-serif;
        }

        /* Buttons color logic */
        .btn-verifications {
            background: #2563eb;
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }
        .btn-verifications:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .btn-merchants {
            background: #1e293b;
            color: white;
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.15);
        }
        .btn-merchants:hover {
            background: #0f172a;
            transform: translateY(-2px);
        }

        .btn-reports {
            background: #eab308;
            color: #854d0e;
            box-shadow: 0 4px 12px rgba(234, 179, 8, 0.15);
        }
        .btn-reports:hover {
            background: #ca8a04;
            transform: translateY(-2px);
        }

        .btn-analytics {
            background: #005a4e;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 90, 78, 0.15);
        }
        .btn-analytics:hover {
            background: #00443b;
            transform: translateY(-2px);
        }

        .btn-installments {
            background: #6366f1;
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        }
        .btn-installments:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }

        /* Responsive stack breakpoints */
        @media (max-width: 600px) {
            .welcome-header h1 {
                font-size: 2.1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .panels-grid {
                grid-template-columns: 1fr;
            }

            .panel-card {
                padding: 30px 24px;
            }
        }
    </style>
</head>
<body>

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="dashboard-container">
        
        <!-- Welcome Header section -->
        <div class="welcome-header">
            <h1 class="serif-title">Control Tower</h1>
            <p>Welcome back, <strong>Admin</strong>. Here is your platform overview.</p>
        </div>

        <!-- Quick Stats Row -->
        <div class="stats-grid">
            
            <div class="stat-card">
                <div class="stat-label">Total Sales Volume</div>
                <div class="stat-value"><?php echo number_format($totalSales, 2); ?> <span>DH</span></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Platform Reach</div>
                <div class="stat-value"><?php echo $totalUsers; ?> <span>Users</span></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Active Partners</div>
                <div class="stat-value"><?php echo $totalMerchants; ?> <span>Shops</span></div>
            </div>

            <div class="stat-card action-required">
                <div class="stat-label">Action Required</div>
                <div class="stat-value"><?php echo $pendingVerif; ?> <span>Reviews</span></div>
                <div class="stat-pill">
                    <?php echo $pendingCustomers; ?> Customer KYC • <?php echo $pendingMerchants; ?> Merchant
                </div>
            </div>

        </div>

        <!-- Admin Panels Grid -->
        <div class="panels-grid">
            
            <!-- Verification Card -->
            <div class="panel-card">
                <div>
                    <h3>👤 User Verifications</h3>
                    <p>Review customer identity documents and financial profiles to approve new credit limit applications.</p>
                </div>
                <a href="verifications.php" class="btn-panel btn-verifications">Manage Verifications →</a>
            </div>

            <!-- Merchant Card -->
            <div class="panel-card">
                <div>
                    <h3>🏢 Merchant Partners</h3>
                    <p>Onboard new stores, adjust platform commission agreements, and view retail channel parameters.</p>
                </div>
                <a href="merchants.php" class="btn-panel btn-merchants">Manage Merchants →</a>
            </div>

            <!-- Commission Card -->
            <div class="panel-card">
                <div>
                    <h3>💰 Revenue Reports</h3>
                    <p>Track accrued earnings, platform fee commission summaries, and settlement transfer metrics.</p>
                </div>
                <a href="commission_reports.php" class="btn-panel btn-reports">Payout & Commissions →</a>
            </div>

            <!-- Analytics Card -->
            <div class="panel-card">
                <div>
                    <h3>📊 System Analytics</h3>
                    <p>Analyze transaction history volumes, merchant performance distributions, and credit default ratios.</p>
                </div>
                <a href="analytics.php" class="btn-panel btn-analytics">Full System Analytics →</a>
            </div>

            <!-- Monitoring Card -->
            <div class="panel-card">
                <div>
                    <h3>🗓️ Installment Wall</h3>
                    <p>Monitor the payment schedule log. Review payment milestones, capture paid segments, and flag default risk.</p>
                </div>
                <a href="all_installments.php" class="btn-panel btn-installments">Monitor All Payments →</a>
            </div>

        </div>

    </div>

</body>
</html>
