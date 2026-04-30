<?php
require_once __DIR__ . "/../../includes/auth.php";

// Protect the page: Only "user" role allowed
requireRole("user");

$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - My Dashboard</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #fafafa;">

    <!-- This is our dynamic navbar -->
    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1200px; margin: auto; padding: 20px;">
        <h1>Welcome to TQSEET, <?php echo $user['name']; ?>!</h1>
        <p>This is your personal dashboard where you can manage your installments and orders.</p>

        <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-top: 20px;">
            <h3>Account Overview</h3>
            <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
            <p><strong>Phone:</strong> <?php echo $user['phone']; ?></p>
            <p><strong>Account Type:</strong> <?php echo ucfirst($user['role']); ?></p>
            <p>
                <strong>Contact Status:</strong> 
                <?php if ($user['is_verified']): ?>
                    <span style="color: green; font-weight: bold;">✅ Verified (OTP)</span>
                <?php else: ?>
                    <span style="color: #d9534f; font-weight: bold;">❌ Missing OTP Verification</span>
                <?php endif; ?>
            </p>
            <p>
                <strong>BNPL Account Status:</strong> 
                <span style="color: #8a6d3b; background: #fcf8e3; padding: 2px 8px; border-radius: 4px; font-weight: bold;">
                    ⏳ Pending Financial Profile
                </span>
            </p>
        </div>
    </div>

</body>
</html>
