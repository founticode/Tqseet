<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

requireRole("user");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch current verification status
$stmt = $conn->prepare("SELECT * FROM user_verifications WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$verification = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cin = $_POST['cin'];
    $cin_image = $verification['cin_image'] ?? '';

    if (isset($_FILES['cin_image']) && $_FILES['cin_image']['error'] === 0) {
        $targetDir = __DIR__ . "/../../uploads/verifications/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $fileName = "CIN_" . time() . "_" . basename($_FILES['cin_image']['name']);
        if (move_uploaded_file($_FILES['cin_image']['tmp_name'], $targetDir . $fileName)) {
            $cin_image = $fileName;
        }
    }

    if ($verification) {
        $stmt_upd = $conn->prepare("UPDATE user_verifications SET cin = ?, cin_image = ?, status = 'pending' WHERE user_id = ?");
        $stmt_upd->bind_param("ssi", $cin, $cin_image, $user['id']);
        $stmt_upd->execute();
    } else {
        $stmt_ins = $conn->prepare("INSERT INTO user_verifications (user_id, cin, cin_image, status) VALUES (?, ?, ?, 'pending')");
        $stmt_ins->bind_param("iss", $user['id'], $cin, $cin_image);
        $stmt_ins->execute();
    }
    
    header("Location: dashboard.php?id_updated=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Identity - TQSEET</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 600px; margin: 60px auto; padding: 0 20px;">
        
        <div style="text-align: center; margin-bottom: 40px;">
            <div style="font-size: 3rem; margin-bottom: 20px;">🪪</div>
            <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">Identity Verification</h1>
            <p style="color: #636e72; margin: 10px 0 0 0; font-weight: 500;">Secure your account by verifying your identity.</p>
        </div>

        <div style="background: white; border-radius: 30px; padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);">
            
            <?php if ($verification && $verification['status'] === 'approved'): ?>
                <div style="background: #eafaf1; color: #27ae60; padding: 20px; border-radius: 15px; margin-bottom: 30px; text-align: center; border: 1px solid #d4edda;">
                    <div style="font-weight: 900; font-size: 1.1rem;">✅ Identity Verified</div>
                    <p style="margin: 5px 0 0 0; font-size: 0.9rem;">Your identity documents have been approved.</p>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 10px; letter-spacing: 1px;">CIN Number</label>
                    <input type="text" name="cin" required value="<?php echo htmlspecialchars($verification['cin'] ?? ''); ?>" 
                           placeholder="e.g. AB123456"
                           style="width: 100%; padding: 18px; border: 2px solid #f1f3f5; border-radius: 15px; box-sizing: border-box; font-size: 1rem; outline: none; transition: 0.3s;"
                           onfocus="this.style.borderColor='#222'">
                </div>

                <div style="margin-bottom: 35px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 10px; letter-spacing: 1px;">Photo of your ID card (CIN)</label>
                    <div style="position: relative; background: #fafafa; border: 2px dashed #eee; padding: 30px; border-radius: 20px; text-align: center;">
                        <input type="file" name="cin_image" <?php echo $verification ? '' : 'required'; ?> 
                               style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer;">
                        <div style="font-size: 0.9rem; color: #636e72;">
                            <?php if ($verification && $verification['cin_image']): ?>
                                <img src="../../uploads/verifications/<?php echo $verification['cin_image']; ?>" style="max-width: 100%; max-height: 150px; border-radius: 10px; margin-bottom: 10px;">
                                <br>📄 <span style="font-weight: bold;">Change Document</span>
                            <?php else: ?>
                                📸 Click to upload Front of your ID
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <button type="submit" style="width: 100%; background: #222; color: white; padding: 20px; border: none; border-radius: 15px; font-weight: 900; font-size: 1.1rem; cursor: pointer; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                    Save & Submit →
                </button>
            </form>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" style="color: #636e72; text-decoration: none; font-weight: bold; font-size: 0.9rem;">Back to Dashboard</a>
        </div>
    </div>

</body>
</html>
