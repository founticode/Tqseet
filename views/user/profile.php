<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Only logged in users can see a profile
if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();

// Fetch FRESH user data from DB (Session might be stale)
$stmt_u = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_u->bind_param("i", $_SESSION['user_id']);
$stmt_u->execute();
$user = $stmt_u->get_result()->fetch_assoc();

if (!$user) {
    header("Location: ../auth/login.php");
    exit;
}

$displayDate = (isset($user['created_at']) && $user['created_at']) ? date('F Y', strtotime($user['created_at'])) : 'Recently';
$displayPic = (isset($user['profile_pic']) && $user['profile_pic']) ? $user['profile_pic'] : 'default_avatar.png';

// Fetch detailed merchant data if applicable
$merchant = null;
if ($user['role'] === 'merchant') {
    $stmt = $conn->prepare("SELECT * FROM merchants WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $merchant = $stmt->get_result()->fetch_assoc();
}

// Handle Avatar Upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['avatar'])) {
    if ($_FILES['avatar']['error'] === 0) {
        $targetDir = __DIR__ . "/../../uploads/avatars/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $fileName = "AV_" . $user['id'] . "_" . time() . ".png";
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetDir . $fileName)) {
            $stmt_upd = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $stmt_upd->bind_param("si", $fileName, $user['id']);
            $stmt_upd->execute();
            $_SESSION['user']['profile_pic'] = $fileName;
            header("Location: profile.php?success=1");
            exit;
        }
    }
}

$settingsLink = ($user['role'] === 'admin') ? "../admin/settings.php" : (($user['role'] === 'merchant') ? "../merchant/settings.php" : "settings.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - TQSEET</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 900px; margin: 60px auto; padding: 0 20px;">
        
        <div style="background: white; border-radius: 40px; overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,0.05); position: relative; border: 1px solid rgba(0,0,0,0.02);">
            
            <!-- Cover Header -->
            <div style="height: 180px; background: linear-gradient(135deg, #222 0%, #444 100%);"></div>

            <!-- Profile Info Area -->
            <div style="padding: 0 50px 50px 50px; position: relative;">
                
                <!-- Avatar -->
                <div style="position: relative; margin-top: -80px; display: inline-block;">
                    <img src="../../uploads/avatars/<?php echo $displayPic; ?>" 
                         style="width: 160px; height: 160px; border-radius: 50%; border: 8px solid white; object-fit: cover; background: #eee;">
                    
                    <form action="" method="POST" enctype="multipart/form-data" id="avatarForm" style="position: absolute; bottom: 5px; right: 5px;">
                        <label for="avatarInput" style="background: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 1px solid #eee;">
                            📷
                        </label>
                        <input type="file" name="avatar" id="avatarInput" style="display: none;" onchange="document.getElementById('avatarForm').submit()">
                    </form>
                </div>

                <!-- Settings Gear Icon -->
                <a href="<?php echo $settingsLink; ?>" style="position: absolute; top: 30px; right: 50px; text-decoration: none; font-size: 1.5rem; filter: grayscale(1); opacity: 0.5;">
                    ⚙️
                </a>

                <div style="margin-top: 20px;">
                    <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <div style="display: flex; gap: 10px; align-items: center; margin-top: 5px;">
                        <span style="background: #222; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">
                            <?php echo $user['role']; ?>
                        </span>
                        <span style="color: #636e72; font-size: 0.9rem; font-weight: 500;">
                            Member since <?php echo $displayDate; ?>
                        </span>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #f1f3f5; margin: 40px 0;">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                    <div>
                        <h4 style="text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; color: #b2bec3; margin-bottom: 15px;">Contact Information</h4>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <div>
                                <div style="font-size: 0.8rem; color: #b2bec3;">Email Address</div>
                                <div style="font-weight: bold; color: #2d3436;"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.8rem; color: #b2bec3;">Phone Number</div>
                                <div style="font-weight: bold; color: #2d3436;"><?php echo htmlspecialchars($user['phone']); ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($merchant): ?>
                    <div>
                        <h4 style="text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; color: #b2bec3; margin-bottom: 15px;">Store Details</h4>
                        <div>
                            <div style="font-size: 0.8rem; color: #b2bec3;">Store Name</div>
                            <div style="font-weight: bold; color: #2d3436; font-size: 1.1rem;"><?php echo htmlspecialchars($merchant['store_name']); ?></div>
                        </div>
                        <div style="margin-top: 15px;">
                            <div style="font-size: 0.8rem; color: #b2bec3;">Status</div>
                            <div style="font-weight: bold; color: #27ae60;">✅ Verified Partner</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 50px; display: flex; gap: 20px;">
                    <a href="<?php echo ($user['role'] === 'admin') ? '../admin/dashboard.php' : (($user['role'] === 'merchant') ? '../merchant/dashboard.php' : 'dashboard.php'); ?>" 
                       style="background: #222; color: white; padding: 15px 30px; border-radius: 12px; text-decoration: none; font-weight: bold; font-size: 0.9rem;">
                       Go to Dashboard
                    </a>
                    <a href="../../controllers/AuthController.php?action=logout" 
                       style="background: #fff5f5; color: #e03131; padding: 15px 30px; border-radius: 12px; text-decoration: none; font-weight: bold; font-size: 0.9rem; border: 1px solid #ffc9c9;">
                       Logout Account
                    </a>
                </div>

            </div>

        </div>

    </div>

</body>
</html>
