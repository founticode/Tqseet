<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

requireRole(["user", "merchant", "admin"]);
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch KYC Statuses
$stmt_i = $conn->prepare("SELECT status FROM user_verifications WHERE user_id = ?");
$stmt_i->bind_param("i", $user['id']);
$stmt_i->execute();
$id_status = $stmt_i->get_result()->fetch_assoc()['status'] ?? 'none';

$stmt_f = $conn->prepare("SELECT status FROM user_financials WHERE user_id = ?");
$stmt_f->bind_param("i", $user['id']);
$stmt_f->execute();
$fin_status = $stmt_f->get_result()->fetch_assoc()['status'] ?? 'none';

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Basic Info Update
    $newName = $_POST['name'];
    $newPhone = $_POST['phone'];
    
    $stmt_upd = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmt_upd->bind_param("ssi", $newName, $newPhone, $user['id']);
    
    if ($stmt_upd->execute()) {
        $message = "Profile updated successfully!";
        // Refresh session data
        $_SESSION['user']['name'] = $newName;
        $_SESSION['user']['phone'] = $newPhone;
        $user = currentUser();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Settings - TQSEET</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 800px; margin: 60px auto; padding: 0 20px;">
        
        <div style="margin-bottom: 40px;">
            <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">Settings</h1>
            <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Manage your personal information and security.</p>
        </div>

        <?php if ($message): ?>
            <div style="background: #eafaf1; color: #27ae60; padding: 15px 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #d4edda; font-weight: bold;">
                ✅ <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
            
            <!-- Left: Profile Form -->
            <div style="background: white; border-radius: 25px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 25px;">Personal Details</h3>
                
                <form action="" method="POST">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                               style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; font-size: 0.95rem; outline: none;">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                               style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; font-size: 0.95rem; background: #fafafa; color: #999; cursor: not-allowed;">
                        <small style="color: #b2bec3; margin-top: 5px; display: block;">Email cannot be changed for security reasons.</small>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required
                               style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; font-size: 0.95rem; outline: none;">
                    </div>

                    <button type="submit" style="background: #222; color: white; padding: 15px 30px; border: none; border-radius: 12px; font-weight: 900; cursor: pointer;">
                        Save Changes
                    </button>
                </form>
            </div>

            <!-- Right: Account Status -->
            <div>
                <div style="background: white; border-radius: 25px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); margin-bottom: 30px;">
                    <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 20px; font-size: 1rem;">Verification</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: bold; color: #636e72;">Identity</span>
                            <span style="font-size: 0.65rem; font-weight: 900; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; 
                                  <?php echo ($id_status === 'approved') ? 'background: #eafaf1; color: #27ae60;' : 'background: #fff4e6; color: #d9480f;'; ?>">
                                <?php echo $id_status; ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: bold; color: #636e72;">Financials</span>
                            <span style="font-size: 0.65rem; font-weight: 900; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; 
                                  <?php echo ($fin_status === 'approved') ? 'background: #eafaf1; color: #27ae60;' : 'background: #fff4e6; color: #d9480f;'; ?>">
                                <?php echo $fin_status; ?>
                            </span>
                        </div>
                    </div>
                    
                    <hr style="border: 0; border-top: 1px solid #f1f3f5; margin: 20px 0;">
                    <a href="dashboard.php" style="font-size: 0.85rem; color: #0984e3; text-decoration: none; font-weight: bold;">View Credit Limit →</a>
                </div>

                <div style="background: #222; border-radius: 25px; padding: 30px; color: white;">
                    <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 15px; font-size: 1rem;">Security</h3>
                    <p style="font-size: 0.8rem; opacity: 0.6; line-height: 1.5; margin-bottom: 20px;">Protect your account by using a strong password.</p>
                    <a href="update_password.php" style="display: block; background: rgba(255,255,255,0.1); color: white; text-align: center; padding: 12px; text-decoration: none; border-radius: 10px; font-size: 0.85rem; font-weight: bold; border: 1px solid rgba(255,255,255,0.1);">
                        Update Password
                    </a>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
