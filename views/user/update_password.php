<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

requireRole(["user", "merchant", "admin"]);
$user = currentUser();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    $db = new Database();
    $conn = $db->connect();

    // 1. Verify Current Password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();

    if (password_verify($currentPassword, $userData['password'])) {
        if ($newPassword === $confirmPassword) {
            // 2. Update Password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt_upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_upd->bind_param("si", $hashedPassword, $user['id']);
            $stmt_upd->execute();
            $success = "Password updated successfully!";
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Password - TQSEET</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 500px; margin: 80px auto; padding: 0 20px;">
        
        <div style="text-align: center; margin-bottom: 40px;">
            <div style="font-size: 3rem; margin-bottom: 20px;">🔒</div>
            <h1 style="margin: 0; font-size: 2rem; font-weight: 900; letter-spacing: -1px;">Update Password</h1>
            <p style="color: #636e72; margin: 10px 0 0 0; font-weight: 500;">Keep your account secure with a new password.</p>
        </div>

        <div style="background: white; border-radius: 25px; padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);">
            
            <?php if ($error): ?>
                <div style="background: #fff5f5; color: #e03131; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: bold; border: 1px solid #ffc9c9; font-size: 0.9rem;">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div style="background: #eafaf1; color: #27ae60; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: bold; border: 1px solid #d4edda; font-size: 0.9rem;">
                    ✅ <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Current Password</label>
                    <input type="password" name="current_password" required
                           style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; outline: none; font-size: 1rem;">
                </div>

                <hr style="border: 0; border-top: 1px solid #f1f3f5; margin: 30px 0;">

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">New Password</label>
                    <input type="password" name="new_password" required
                           style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; outline: none; font-size: 1rem;">
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Confirm New Password</label>
                    <input type="password" name="confirm_password" required
                           style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; outline: none; font-size: 1rem;">
                </div>

                <button type="submit" style="width: 100%; background: #222; color: white; padding: 20px; border: none; border-radius: 15px; font-weight: 900; font-size: 1rem; cursor: pointer;">
                    Update Security Key →
                </button>
            </form>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <?php 
                $backLink = ($user['role'] === 'admin') ? "../admin/settings.php" : (($user['role'] === 'merchant') ? "../merchant/settings.php" : "settings.php");
            ?>
            <a href="<?php echo $backLink; ?>" style="color: #636e72; text-decoration: none; font-weight: bold; font-size: 0.9rem;">Back to Settings</a>
        </div>
    </div>

</body>
</html>
