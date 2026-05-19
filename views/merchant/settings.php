<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

requireRole("merchant");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch Merchant Data
$stmt = $conn->prepare("SELECT * FROM merchants WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$merchant = $stmt->get_result()->fetch_assoc();

// Fetch Verification data
$stmt_v = $conn->prepare("SELECT * FROM user_verifications WHERE user_id = ?");
$stmt_v->bind_param("i", $user['id']);
$stmt_v->execute();
$verification = $stmt_v->get_result()->fetch_assoc();
$hasCIN = ($verification !== null && !empty($verification['cin']));
$cinApproved = ($verification !== null && $verification['status'] === 'approved');

// Fetch Financial data
$stmt_f = $conn->prepare("SELECT * FROM user_financials WHERE user_id = ?");
$stmt_f->bind_param("i", $user['id']);
$stmt_f->execute();
$financial = $stmt_f->get_result()->fetch_assoc();
$hasFinancial = ($financial !== null && !empty($financial['salary_proof']));
$financialApproved = ($financial !== null && $financial['status'] === 'approved');

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $storeName = $_POST['store_name'];
        $description = $_POST['description'];
        
        $stmt_upd = $conn->prepare("UPDATE merchants SET store_name = ?, description = ? WHERE user_id = ?");
        $stmt_upd->bind_param("ssi", $storeName, $description, $user['id']);
        
        if ($stmt_upd->execute()) {
            $message = "Store profile updated successfully!";
            $stmt->execute();
            $merchant = $stmt->get_result()->fetch_assoc();
        }
    }
    
    elseif ($action === 'upload_cin') {
        if ($cinApproved) {
            $error = "Action Denied: Verified identity documents are sealed.";
        } else {
            $cin = $_POST['cin'];
            $cin_image = $_FILES['cin_image']['name'] ?? '';
            
            if ($cin_image) {
                $target = __DIR__ . "/../../uploads/verifications/" . basename($cin_image);
                if (!is_dir(dirname($target))) {
                    mkdir(dirname($target), 0777, true);
                }
                move_uploaded_file($_FILES['cin_image']['tmp_name'], $target);
            } else {
                $cin_image = $verification['cin_image'] ?? '';
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
            $message = "Identity (CIN) document submitted successfully for review!";
            $stmt_v->execute();
            $verification = $stmt_v->get_result()->fetch_assoc();
            $hasCIN = true;
            $cinApproved = false;
        }
    }
    
    elseif ($action === 'upload_financials') {
        if ($financialApproved) {
            $error = "Action Denied: Verified financials are sealed.";
        } else {
            $profession = $_POST['profession'];
            $salary = $_POST['salary'];
            $salary_proof = $_FILES['salary_proof']['name'] ?? '';
            
            if ($salary_proof) {
                $target = __DIR__ . "/../../uploads/financials/" . basename($salary_proof);
                if (!is_dir(dirname($target))) {
                    mkdir(dirname($target), 0777, true);
                }
                move_uploaded_file($_FILES['salary_proof']['tmp_name'], $target);
            } else {
                $salary_proof = $financial['salary_proof'] ?? '';
            }
            
            if ($financial) {
                $stmt_upd = $conn->prepare("UPDATE user_financials SET profession = ?, salary = ?, salary_proof = ?, status = 'pending' WHERE user_id = ?");
                $stmt_upd->bind_param("sdsi", $profession, $salary, $salary_proof, $user['id']);
                $stmt_upd->execute();
            } else {
                $stmt_ins = $conn->prepare("INSERT INTO user_financials (user_id, profession, salary, salary_proof, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt_ins->bind_param("isds", $user['id'], $profession, $salary, $salary_proof);
                $stmt_ins->execute();
            }
            $message = "Business Financials submitted successfully for review!";
            $stmt_f->execute();
            $financial = $stmt_f->get_result()->fetch_assoc();
            $hasFinancial = true;
            $financialApproved = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Store Settings - TQSEET</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 800px; margin: 60px auto; padding: 0 20px;">
        
        <div style="margin-bottom: 40px;">
            <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">Store Management</h1>
            <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Branding and business information for your shop.</p>
        </div>

        <?php if ($message): ?>
            <div style="background: #eafaf1; color: #27ae60; padding: 15px 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #d4edda; font-weight: bold;">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #fdf2f2; color: #de350b; padding: 15px 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #fde8e8; font-weight: bold;">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
            
            <!-- Left: Store Form -->
            <div style="background: white; border-radius: 25px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 25px;">Store Identity</h3>
                
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Public Store Name</label>
                        <input type="text" name="store_name" value="<?php echo htmlspecialchars($merchant['store_name']); ?>" required
                               style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; font-size: 0.95rem; outline: none;">
                    </div>
                    
                    <div style="margin-bottom: 30px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Store Description</label>
                        <textarea name="description" rows="5" required
                                  style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; font-size: 0.95rem; outline: none; resize: none;"><?php echo htmlspecialchars($merchant['description']); ?></textarea>
                        <small style="color: #b2bec3; margin-top: 5px; display: block;">This will be visible on your product pages.</small>
                    </div>

                    <button type="submit" style="background: #222; color: white; padding: 15px 30px; border: none; border-radius: 12px; font-weight: 900; cursor: pointer;">
                        Update Store Profile
                    </button>
                </form>
            </div>

            <!-- Right: Account Info -->
            <div>
                <div style="background: white; border-radius: 25px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); margin-bottom: 30px;">
                    <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 20px; font-size: 1rem;">Partner Agreement</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: bold; color: #636e72;">Commission Rate</span>
                            <span style="font-size: 0.75rem; font-weight: 900; color: #2d3436; background: #f1f3f5; padding: 4px 10px; border-radius: 8px;">
                                <?php echo ($merchant['commission_rate'] * 100); ?>%
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: bold; color: #636e72;">Status</span>
                            <span style="font-size: 0.65rem; font-weight: 900; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; background: #eafaf1; color: #27ae60;">
                                <?php echo $merchant['status']; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div style="background: #222; border-radius: 25px; padding: 30px; color: white;">
                    <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 15px; font-size: 1rem;">Security</h3>
                    <p style="font-size: 0.8rem; opacity: 0.6; line-height: 1.5; margin-bottom: 20px;">Protect your credentials and update contact details.</p>
                    <a href="../user/update_password.php" style="display: block; background: rgba(255,255,255,0.1); color: white; text-align: center; padding: 12px; text-decoration: none; border-radius: 10px; font-size: 0.85rem; font-weight: bold; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 10px;">
                        Change Password
                    </a>
                    <a href="../user/settings.php" style="display: block; background: rgba(255,255,255,0.05); color: #00b894; text-align: center; padding: 12px; text-decoration: none; border-radius: 10px; font-size: 0.85rem; font-weight: bold; border: 1px solid rgba(0,184,148,0.2);">
                        Change Email / Phone (2FA)
                    </a>
                </div>
            </div>

        </div>

        <!-- Section: Business Verification & Credentials (Klarna / Tabby Style) -->
        <div style="background: white; border-radius: 25px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); margin-top: 40px;">
            <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 8px;">Business Verification & Documents</h3>
            <p style="color: #636e72; font-size: 0.9rem; margin: 0 0 35px 0;">Submit compliance credentials to activate store trust features.</p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                
                <!-- Left Column: Identity Card (CIN) -->
                <div style="padding-right: 20px; border-right: 1px solid #f1f3f5;">
                    <h4 style="margin-top: 0; font-size: 1.1rem; font-weight: 900; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <span>🪪</span> Identity Verification (CIN)
                    </h4>
                    
                    <?php if ($cinApproved): ?>
                        <div style="background: #eafaf1; color: #27ae60; padding: 20px; border-radius: 15px; border: 1px solid #d4edda; margin-bottom: 20px;">
                            <div style="font-weight: 900; font-size: 0.95rem;">✅ ID Verified & Sealed</div>
                            <p style="margin: 5px 0 0 0; font-size: 0.8rem; line-height: 1.4;">CIN: <strong><?php echo htmlspecialchars($verification['cin']); ?></strong></p>
                        </div>
                        <?php if (!empty($verification['cin_image'])): ?>
                            <img src="../../uploads/verifications/<?php echo $verification['cin_image']; ?>" style="max-width: 100%; border-radius: 12px; margin-top: 10px; border: 1px solid #eee;">
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($verification && $verification['status'] === 'pending'): ?>
                            <div style="background: #fff9db; color: #856404; padding: 15px; border-radius: 12px; border: 1px solid #ffeeba; margin-bottom: 20px; font-size: 0.85rem; font-weight: bold;">
                                ⏳ Review in progress: Identity document submitted.
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_cin">
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">CIN Number</label>
                                <input type="text" name="cin" required value="<?php echo htmlspecialchars($verification['cin'] ?? ''); ?>" 
                                       placeholder="e.g. AB123456"
                                       style="width: 100%; padding: 12px; border: 2px solid #f1f3f5; border-radius: 10px; font-size: 0.9rem; outline: none;">
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Upload CIN Document (Photo/PDF)</label>
                                <input type="file" name="cin_image" <?php echo $verification ? '' : 'required'; ?> style="font-size: 0.85rem; color: #636e72;">
                                <?php if ($verification && $verification['cin_image']): ?>
                                    <small style="display: block; margin-top: 5px; color: #0984e3; font-weight: bold;">📄 Current: <?php echo htmlspecialchars($verification['cin_image']); ?></small>
                                <?php endif; ?>
                            </div>
                            <button type="submit" style="background: #222; color: white; padding: 12px 25px; border: none; border-radius: 10px; font-weight: bold; font-size: 0.85rem; cursor: pointer;">
                                Submit Identity Card
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Business Financials -->
                <div>
                    <h4 style="margin-top: 0; font-size: 1.1rem; font-weight: 900; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <span>📈</span> Business Financials
                    </h4>
                    
                    <?php if ($financialApproved): ?>
                        <div style="background: #eafaf1; color: #27ae60; padding: 20px; border-radius: 15px; border: 1px solid #d4edda; margin-bottom: 20px;">
                            <div style="font-weight: 900; font-size: 0.95rem;">✅ Financials Verified & Sealed</div>
                            <p style="margin: 5px 0 0 0; font-size: 0.8rem; line-height: 1.4;">
                                Structure: <strong><?php echo htmlspecialchars($financial['profession']); ?></strong><br>
                                Est. Monthly Revenue: <strong><?php echo number_format($financial['salary'], 2); ?> DH</strong>
                            </p>
                        </div>
                    <?php else: ?>
                        <?php if ($financial && $financial['status'] === 'pending'): ?>
                            <div style="background: #fff9db; color: #856404; padding: 15px; border-radius: 12px; border: 1px solid #ffeeba; margin-bottom: 20px; font-size: 0.85rem; font-weight: bold;">
                                ⏳ Review in progress: Store financials submitted.
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_financials">
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Legal Business Structure</label>
                                <input type="text" name="profession" required value="<?php echo htmlspecialchars($financial['profession'] ?? ''); ?>" 
                                       placeholder="e.g. LLC, SARL, Sole Proprietorship"
                                       style="width: 100%; padding: 12px; border: 2px solid #f1f3f5; border-radius: 10px; font-size: 0.9rem; outline: none;">
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Estimated Monthly Revenue (DH)</label>
                                <input type="number" name="salary" required value="<?php echo htmlspecialchars($financial['salary'] ?? ''); ?>" 
                                       placeholder="e.g. 50000"
                                       style="width: 100%; padding: 12px; border: 2px solid #f1f3f5; border-radius: 10px; font-size: 0.9rem; outline: none;">
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Upload Bank Statement / Tax Paper</label>
                                <input type="file" name="salary_proof" <?php echo $financial ? '' : 'required'; ?> style="font-size: 0.85rem; color: #636e72;">
                                <?php if ($financial && $financial['salary_proof']): ?>
                                    <small style="display: block; margin-top: 5px; color: #0984e3; font-weight: bold;">📄 Current: <?php echo htmlspecialchars($financial['salary_proof']); ?></small>
                                <?php endif; ?>
                            </div>
                            <button type="submit" style="background: #222; color: white; padding: 12px 25px; border: none; border-radius: 10px; font-weight: bold; font-size: 0.85rem; cursor: pointer;">
                                Submit Business Financials
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>

</body>
</html>
