<?php
require_once __DIR__ . "/../../includes/auth.php";
requireRole("admin");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Admin Dashboard</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #f4f4f4;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1000px; margin: 40px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h1>Admin Control Panel</h1>
        <p>Welcome back, <strong><?php echo $_SESSION["user_name"]; ?></strong>!</p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
            <!-- Verification Section -->
            <div style="padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #eef9ff;">
                <h3>User Verifications</h3>
                <p>Approve or reject user identities and financial documents.</p>
                <a href="verifications.php" style="display: inline-block; padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    Manage Verifications →
                </a>
            </div>

            <!-- Merchant Section -->
            <div style="padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #fffdf0;">
                <h3>Merchant Management</h3>
                <p>View active merchants and platform commissions.</p>
                <a href="merchants.php" style="display: inline-block; padding: 10px 15px; background: #856404; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    View Merchants →
                </a>
            </div>
        </div>
    </div>

</body>
</html>
