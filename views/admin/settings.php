<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

requireRole("admin");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newName = $_POST['name'];
    
    $stmt_upd = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt_upd->bind_param("si", $newName, $user['id']);
    
    if ($stmt_upd->execute()) {
        $message = "Admin profile updated!";
        $_SESSION['user']['name'] = $newName;
        $user = currentUser();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Settings - TQSEET</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 600px; margin: 60px auto; padding: 0 20px;">
        
        <div style="margin-bottom: 40px; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 15px;">🛡️</div>
            <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">Admin Security</h1>
            <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Manage your platform administrator credentials.</p>
        </div>

        <?php if ($message): ?>
            <div style="background: #eafaf1; color: #27ae60; padding: 15px; border-radius: 12px; margin-bottom: 30px; text-align: center; border: 1px solid #d4edda; font-weight: bold;">
                ✅ <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div style="background: #222; color: white; border-radius: 30px; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <form action="" method="POST">
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: rgba(255,255,255,0.4); margin-bottom: 10px; letter-spacing: 1px;">Admin Display Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                           style="width: 100%; padding: 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; outline: none; font-size: 1rem;">
                </div>

                <div style="margin-bottom: 35px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: rgba(255,255,255,0.4); margin-bottom: 10px; letter-spacing: 1px;">System Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                           style="width: 100%; padding: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; color: rgba(255,255,255,0.3); cursor: not-allowed;">
                </div>

                <button type="submit" style="width: 100%; background: #00b894; color: white; padding: 18px; border: none; border-radius: 12px; font-weight: 900; font-size: 1rem; cursor: pointer; margin-bottom: 15px;">
                    Update Admin Profile
                </button>

                <a href="../user/update_password.php" style="display: block; text-align: center; color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.9rem; font-weight: bold; padding: 10px;">
                    🔐 Change Security Password
                </a>
            </form>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" style="color: #636e72; text-decoration: none; font-weight: bold; font-size: 0.9rem;">← Back to Command Tower</a>
        </div>
    </div>

</body>
</html>
