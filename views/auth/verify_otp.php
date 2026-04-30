<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/otp_helpers.php";
require_once __DIR__ . "/../../config/db.php";

// Try to get user from session (if logged in) or from temp session (if just registered)
$userId = $_SESSION["user_id"] ?? $_SESSION["temp_user_id"] ?? null;

if (!$userId) {
    header("Location: login.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputCode = $_POST["otp_code"];

    $db = new Database();
    $conn = $db->connect();

    if (verifyOTP($conn, $userId, $inputCode)) {
        // SUCCESS! 
        // 1. Mark user as verified in the database
        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // 2. Redirect to dashboard
        header("Location: ../user/dashboard.php?verified=1");
        exit;
    } else {
        $error = "Invalid or expired code. Please check your log file and try again.";
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Verify OTP</title>
</head>
<body style="font-family: sans-serif; text-align: center; padding: 50px; background: #f9f9f9;">

    <div style="max-width: 400px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h1>Verification</h1>
        <p>Enter the 6-digit code we just "sent" you.</p>
        
        <p style="color: #666; font-size: 0.85rem; background: #eee; padding: 5px; border-radius: 4px;">
            🔍 Testing tip: Open <strong>otp_sent.log</strong> in your project root to find the code.
        </p>

        <?php if ($error): ?>
            <p style="color: #dc3545; font-weight: bold;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" style="margin-top: 20px;">
            <input type="text" name="otp_code" maxlength="6" required 
                   placeholder="000000" 
                   style="font-size: 2rem; width: 100%; text-align: center; letter-spacing: 10px; padding: 10px; border: 2px solid #ddd; border-radius: 4px;">
            <br><br>
            <button type="submit" style="width: 100%; padding: 12px; font-size: 1.1rem; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px; font-weight: bold;">
                Confirm Code
            </button>
        </form>

        <p style="margin-top: 20px; font-size: 0.9rem;">
            <a href="../user/dashboard.php" style="color: #666; text-decoration: none;">← Cancel and go back</a>
        </p>
    </div>

</body>
</html>
