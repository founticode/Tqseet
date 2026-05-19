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
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1200px; margin: 60px auto; padding: 0 20px;">
        
        <div style="margin-bottom: 40px;">
            <h1 style="margin: 0; font-size: 2.5rem; font-weight: 900; letter-spacing: -1.5px;">Control Tower</h1>
            <p style="color: #636e72; margin: 10px 0 0 0; font-weight: 500;">Welcome back, <strong>Admin</strong>. Here is your platform overview.</p>
        </div>

        <!-- Quick Stats Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 40px;">
            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                <div style="color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 10px;">Total Sales Volume</div>
                <div style="font-size: 1.8rem; font-weight: 900; color: #2d3436;"><?php echo number_format($totalSales, 2); ?> <span style="font-size: 1rem; color: #b2bec3;">DH</span></div>
            </div>
            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                <div style="color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 10px;">Platform Reach</div>
                <div style="font-size: 1.8rem; font-weight: 900; color: #2d3436;"><?php echo $totalUsers; ?> <span style="font-size: 1rem; color: #b2bec3;">Users</span></div>
            </div>
            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                <div style="color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 10px;">Active Partners</div>
                <div style="font-size: 1.8rem; font-weight: 900; color: #2d3436;"><?php echo $totalMerchants; ?> <span style="font-size: 1rem; color: #b2bec3;">Shops</span></div>
            </div>
            <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                <div style="color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 10px;">Action Required</div>
                <div style="font-size: 1.8rem; font-weight: 900; color: #d63031;"><?php echo $pendingVerif; ?> <span style="font-size: 1rem; color: #b2bec3;">Reviews</span></div>
                <div style="font-size: 0.75rem; color: #636e72; margin-top: 5px; font-weight: 600;">
                    <?php echo $pendingCustomers; ?> Customer KYC • <?php echo $pendingMerchants; ?> Merchant
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px;">
            <!-- Verification Card -->
            <div style="background: white; padding: 40px; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);">
                <h3 style="margin-top: 0; font-weight: 900; letter-spacing: -0.5px;">User Verifications</h3>
                <p style="color: #636e72; line-height: 1.6; margin-bottom: 30px;">Review identity documents and financial profiles to unlock credit limits for new shoppers.</p>
                <a href="verifications.php" style="display: block; background: #0984e3; color: white; padding: 15px; text-decoration: none; border-radius: 12px; font-weight: bold; text-align: center; box-shadow: 0 10px 20px rgba(9, 132, 227, 0.2);">Manage Verifications →</a>
            </div>

            <!-- Merchant Card -->
            <div style="background: white; padding: 40px; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);">
                <h3 style="margin-top: 0; font-weight: 900; letter-spacing: -0.5px;">Merchant Partners</h3>
                <p style="color: #636e72; line-height: 1.6; margin-bottom: 30px;">View business performance and manage store partnerships across the network.</p>
                <a href="merchants.php" style="display: block; background: #2d3436; color: white; padding: 15px; text-decoration: none; border-radius: 12px; font-weight: bold; text-align: center; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">Manage Merchants →</a>
            </div>

            <!-- Commission Card -->
            <div style="background: white; padding: 40px; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);">
                <h3 style="margin-top: 0; font-weight: 900; letter-spacing: -0.5px;">Revenue Reports</h3>
                <p style="color: #636e72; line-height: 1.6; margin-bottom: 30px;">Track platform earnings and merchant payouts. See exactly how much commission is generated per store.</p>
                <a href="commission_reports.php" style="display: block; background: #fdcb6e; color: #2d3436; padding: 15px; text-decoration: none; border-radius: 12px; font-weight: bold; text-align: center; box-shadow: 0 10px 20px rgba(253, 203, 110, 0.2);">Payout & Commissions →</a>
            </div>

            <!-- Analytics Card -->
            <div style="background: white; padding: 40px; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);">
                <h3 style="margin-top: 0; font-weight: 900; letter-spacing: -0.5px;">System Analytics</h3>
                <p style="color: #636e72; line-height: 1.6; margin-bottom: 30px;">Deep dive into platform revenue, transaction trends, and financial health of the TQSEET ecosystem.</p>
                <a href="analytics.php" style="display: block; background: #00b894; color: white; padding: 15px; text-decoration: none; border-radius: 12px; font-weight: bold; text-align: center; box-shadow: 0 10px 20px rgba(0, 184, 148, 0.2);">Full System Analytics →</a>
            </div>

            <!-- Monitoring Card -->
            <div style="background: white; padding: 40px; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);">
                <h3 style="margin-top: 0; font-weight: 900; letter-spacing: -0.5px;">Installment Wall</h3>
                <p style="color: #636e72; line-height: 1.6; margin-bottom: 30px;">Monitor every payment plan on the platform. Track on-time payments and identify overdue receivables.</p>
                <a href="all_installments.php" style="display: block; background: #6c5ce7; color: white; padding: 15px; text-decoration: none; border-radius: 12px; font-weight: bold; text-align: center; box-shadow: 0 10px 20px rgba(108, 92, 231, 0.2);">Monitor All Payments →</a>
            </div>
        </div>
    </div>

</body>
</html>
