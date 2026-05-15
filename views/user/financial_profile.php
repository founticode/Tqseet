<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Users allowed!
requireRole("user");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Check if user already has a profile
$stmt = $conn->prepare("SELECT * FROM user_financials WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Handling form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $profession = $_POST['profession'];
    $salary = $_POST['salary'];
    $proof_image = $profile['salary_proof'] ?? '';

    // Handle File Upload
    if (isset($_FILES['salary_proof']) && $_FILES['salary_proof']['error'] === 0) {
        $targetDir = __DIR__ . "/../../uploads/financials/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $fileName = time() . "_" . basename($_FILES['salary_proof']['name']);
        $targetFilePath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['salary_proof']['tmp_name'], $targetFilePath)) {
            $proof_image = $fileName;
        }
    }

    if ($profile) {
        // Update existing
        $stmt_upd = $conn->prepare("UPDATE user_financials SET profession = ?, salary = ?, salary_proof = ?, status = 'pending' WHERE user_id = ?");
        $stmt_upd->bind_param("sdsi", $profession, $salary, $proof_image, $user['id']);
        $stmt_upd->execute();
    } else {
        // Insert new
        $stmt_ins = $conn->prepare("INSERT INTO user_financials (user_id, profession, salary, salary_proof, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt_ins->bind_param("isds", $user['id'], $profession, $salary, $proof_image);
        $stmt_ins->execute();
    }
    
    header("Location: dashboard.php?profile_updated=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Profile - TQSEET</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 600px; margin: 60px auto; padding: 0 20px;">
        
        <div style="text-align: center; margin-bottom: 40px;">
            <div style="font-size: 3rem; margin-bottom: 20px;">📈</div>
            <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">Financial Profile</h1>
            <p style="color: #636e72; margin: 10px 0 0 0; font-weight: 500;">Unlock your credit limit by providing your info.</p>
        </div>

        <div style="background: white; border-radius: 30px; padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);">
            
            <?php if ($profile && $profile['status'] === 'approved'): ?>
                <div style="background: #eafaf1; color: #27ae60; padding: 20px; border-radius: 15px; margin-bottom: 30px; text-align: center; border: 1px solid #d4edda;">
                    <div style="font-weight: 900; font-size: 1.1rem;">✅ Profile Verified</div>
                    <p style="margin: 5px 0 0 0; font-size: 0.9rem;">Your credit limit is active. You can still update your info below.</p>
                </div>
            <?php elseif ($profile && $profile['status'] === 'pending'): ?>
                <div style="background: #fff9db; color: #856404; padding: 20px; border-radius: 15px; margin-bottom: 30px; text-align: center; border: 1px solid #ffeeba;">
                    <div style="font-weight: 900; font-size: 1.1rem;">⏳ Review in Progress</div>
                    <p style="margin: 5px 0 0 0; font-size: 0.9rem;">Our team is reviewing your data to set your limit.</p>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 10px; letter-spacing: 1px;">Profession / Job Title</label>
                    <input type="text" name="profession" required value="<?php echo htmlspecialchars($profile['profession'] ?? ''); ?>" 
                           placeholder="e.g. Software Engineer, Doctor, Store Manager"
                           style="width: 100%; padding: 18px; border: 2px solid #f1f3f5; border-radius: 15px; box-sizing: border-box; font-size: 1rem; outline: none; transition: 0.3s;"
                           onfocus="this.style.borderColor='#222'">
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 10px; letter-spacing: 1px;">Monthly Net Salary (DH)</label>
                    <input type="number" name="salary" required value="<?php echo htmlspecialchars($profile['salary'] ?? ''); ?>" 
                           placeholder="e.g. 8500"
                           style="width: 100%; padding: 18px; border: 2px solid #f1f3f5; border-radius: 15px; box-sizing: border-box; font-size: 1rem; outline: none; transition: 0.3s;"
                           onfocus="this.style.borderColor='#222'">
                </div>

                <div style="margin-bottom: 35px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 10px; letter-spacing: 1px;">Proof of Income (Salary Slip / Bank Statement)</label>
                    <div style="position: relative; background: #fafafa; border: 2px dashed #eee; padding: 20px; border-radius: 15px; text-align: center;">
                        <input type="file" name="salary_proof" <?php echo $profile ? '' : 'required'; ?> 
                               style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer;">
                        <div style="font-size: 0.9rem; color: #636e72;">
                            <?php if ($profile && $profile['salary_proof']): ?>
                                📄 Current: <span style="font-weight: bold;"><?php echo $profile['salary_proof']; ?></span><br>
                                <span style="font-size: 0.75rem;">Click to upload a new one</span>
                            <?php else: ?>
                                📁 Click to upload PDF or Image
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <button type="submit" style="width: 100%; background: #222; color: white; padding: 20px; border: none; border-radius: 15px; font-weight: 900; font-size: 1.1rem; cursor: pointer; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                    Submit for Review →
                </button>
            </form>

            <p style="text-align: center; color: #b2bec3; font-size: 0.8rem; margin-top: 30px; line-height: 1.6;">
                * We use this data strictly for credit scoring. Your info is encrypted and never shared with merchants.
            </p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" style="color: #636e72; text-decoration: none; font-weight: bold; font-size: 0.9rem;">Back to Dashboard</a>
        </div>
    </div>

</body>
</html>
