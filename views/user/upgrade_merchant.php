<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: Only logged-in users
requireLogin();
$user = currentUser();

// If they are already a merchant or admin, they shouldn't be here
if ($user['role'] !== 'user') {
    header("Location: dashboard.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $db = new Database();
    $conn = $db->connect();

    // 1. Update the user role to 'merchant' in the users table
    $stmt = $conn->prepare("UPDATE users SET role = 'merchant' WHERE id = ?");
    $stmt->bind_param("i", $user['id']);

    if ($stmt->execute()) {
        // 2. Create the merchant profile in the merchants table
        // We set a default commission rate of 10.00%
        $stmt_merchant = $conn->prepare("INSERT INTO merchants (user_id, commission_rate) VALUES (?, 10.00)");
        $stmt_merchant->bind_param("i", $user['id']);
        $stmt_merchant->execute();
        
        // Save the new merchant ID to the session
        $_SESSION['merchant_id'] = $conn->insert_id;
        
        $stmt_merchant->close();

        // 3. Update the session
        $_SESSION['user_role'] = 'merchant';
        
        $message = "✅ Success! You are now a Merchant. Redirecting to your new dashboard...";
        header("refresh:2;url=../merchant/dashboard.php");
    } else {
        $message = "❌ Error: Could not upgrade your account.";
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Become a Merchant</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #f4f4f4;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 500px; margin: 50px auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center;">
        <h1>Start Selling on TQSEET</h1>
        <p>Ready to reach more customers? Upgrade to a Merchant account to list your products and offer BNPL installments.</p>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

        <?php if ($message): ?>
            <p style="padding: 15px; background: #e7f3ff; color: #004085; border-radius: 4px; font-weight: bold; border: 1px solid #b8daff;">
                <?php echo $message; ?>
            </p>
        <?php else: ?>
            <form method="POST">
                <p style="color: #666; margin-bottom: 25px;">By clicking below, your account will be upgraded immediately.</p>
                <button type="submit" style="background: #28a745; color: white; border: none; padding: 15px 40px; font-size: 1.2rem; border-radius: 5px; cursor: pointer; font-weight: bold; width: 100%;">
                    Upgrade to Merchant
                </button>
            </form>
        <?php endif; ?>

        <p style="margin-top: 30px; font-size: 0.9rem;">
            <a href="dashboard.php" style="color: #666; text-decoration: none;">← Maybe later, go back</a>
        </p>
    </div>

</body>
</html>
