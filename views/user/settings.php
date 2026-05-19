<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

requireRole(["user", "merchant", "admin"]);
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch KYC Statuses & Full Records
$stmt_i = $conn->prepare("SELECT * FROM user_verifications WHERE user_id = ?");
$stmt_i->bind_param("i", $user['id']);
$stmt_i->execute();
$verification = $stmt_i->get_result()->fetch_assoc();
$id_status = $verification['status'] ?? 'none';
$hasCIN = ($verification !== null && !empty($verification['cin_image']));
$cinApproved = ($id_status === 'approved');

$stmt_f = $conn->prepare("SELECT * FROM user_financials WHERE user_id = ?");
$stmt_f->bind_param("i", $user['id']);
$stmt_f->execute();
$financial = $stmt_f->get_result()->fetch_assoc();
$fin_status = $financial['status'] ?? 'none';
$hasFinancial = ($financial !== null && !empty($financial['salary_proof']));
$financialApproved = ($fin_status === 'approved');

$message = "";
$error = "";

// Helper to log simulated OTP to otp_sent.log
function logOTP($otp, $recipient) {
    $logFile = __DIR__ . "/../../otp_sent.log";
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[$timestamp] Sending OTP $otp to $recipient\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    
    // 1. Basic Name Update
    if ($action === 'update_name') {
        $newName = trim($_POST['name']);
        if (!empty($newName)) {
            $stmt_upd = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt_upd->bind_param("si", $newName, $user['id']);
            if ($stmt_upd->execute()) {
                $message = "Profile name updated successfully!";
                $_SESSION['user_name'] = $newName;
                $user = currentUser();
            } else {
                $error = "Failed to update profile name.";
            }
        }
    }
    
    // 2. Request OTP to Phone (to update Email)
    elseif ($action === 'request_phone_otp_for_email') {
        $otp = strval(rand(100000, 999999));
        $_SESSION['phone_otp_for_email'] = $otp;
        unset($_SESSION['phone_verified_for_email']); // Clear any active verify
        
        logOTP($otp, $user['phone']);
        $_SESSION['show_otp_popup'] = [
            'code' => $otp,
            'recipient' => $user['phone'],
            'type' => 'phone'
        ];
        $message = "✅ Simulated SMS verification code sent! Copy from the screen notification or check otp_sent.log.";
    }
    
    // 3. Verify Phone OTP (for Email update)
    elseif ($action === 'verify_phone_otp_for_email') {
        $code = trim($_POST['otp_code']);
        if (isset($_SESSION['phone_otp_for_email']) && $code === $_SESSION['phone_otp_for_email']) {
            $_SESSION['phone_verified_for_email'] = true;
            unset($_SESSION['phone_otp_for_email']);
            $message = "🔓 Phone identity verified! You can now type your new email address.";
        } else {
            $error = "Invalid 6-digit verification code. Please check your otp_sent.log file.";
        }
    }
    
    // 4. Save New Email Address
    elseif ($action === 'update_email') {
        if (!empty($_SESSION['phone_verified_for_email'])) {
            $newEmail = trim($_POST['new_email']);
            
            // Check if email already exists
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check->bind_param("si", $newEmail, $user['id']);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $error = "This email address is already in use by another account.";
            } else {
                $stmt_upd = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt_upd->bind_param("si", $newEmail, $user['id']);
                if ($stmt_upd->execute()) {
                    $message = "✅ Email address successfully updated!";
                    $_SESSION['user_email'] = $newEmail;
                    $user = currentUser();
                    unset($_SESSION['phone_verified_for_email']);
                } else {
                    $error = "Failed to update email address.";
                }
            }
        } else {
            $error = "Unauthorized attempt. Please verify your phone identity first.";
        }
    }
    
    // 5. Request OTP to Email (to update Phone)
    elseif ($action === 'request_email_otp_for_phone') {
        $otp = strval(rand(100000, 999999));
        $_SESSION['email_otp_for_phone'] = $otp;
        unset($_SESSION['email_verified_for_phone']); // Clear any active verify
        
        logOTP($otp, $user['email']);
        $_SESSION['show_otp_popup'] = [
            'code' => $otp,
            'recipient' => $user['email'],
            'type' => 'email'
        ];
        $message = "✅ Simulated Email verification code sent! Copy from the screen notification or check otp_sent.log.";
    }
    
    // 6. Verify Email OTP (for Phone update)
    elseif ($action === 'verify_email_otp_for_phone') {
        $code = trim($_POST['otp_code']);
        if (isset($_SESSION['email_otp_for_phone']) && $code === $_SESSION['email_otp_for_phone']) {
            $_SESSION['email_verified_for_phone'] = true;
            unset($_SESSION['email_otp_for_phone']);
            $message = "🔓 Email identity verified! You can now type your new phone number.";
        } else {
            $error = "Invalid 6-digit verification code. Please check your otp_sent.log file.";
        }
    }
    
    // 7. Save New Phone Number
    elseif ($action === 'update_phone') {
        if (!empty($_SESSION['email_verified_for_phone'])) {
            $newPhone = trim($_POST['new_phone']);
            $stmt_upd = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
            $stmt_upd->bind_param("si", $newPhone, $user['id']);
            if ($stmt_upd->execute()) {
                $message = "✅ Phone number successfully updated!";
                $_SESSION['user_phone'] = $newPhone;
                $user = currentUser();
                unset($_SESSION['email_verified_for_phone']);
            } else {
                $error = "Failed to update phone number.";
            }
        } else {
            $error = "Unauthorized attempt. Please verify your email identity first.";
        }
    }

    // 8. Upload CIN Document (Identity Verification)
    elseif ($action === 'upload_cin') {
        if ($cinApproved) {
            $error = "Action Denied: Verified identity documents are sealed.";
        } else {
            $cin = trim($_POST['cin']);
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
            
            // Re-fetch verification
            $stmt_i->execute();
            $verification = $stmt_i->get_result()->fetch_assoc();
            $id_status = $verification['status'] ?? 'none';
            $hasCIN = ($verification !== null && !empty($verification['cin_image']));
            $cinApproved = ($id_status === 'approved');
        }
    }
    
    // 9. Upload Financials (Income Proof)
    elseif ($action === 'upload_financials') {
        if ($financialApproved) {
            $error = "Action Denied: Verified financials are sealed.";
        } else {
            $profession = trim($_POST['profession']);
            $salary = floatval($_POST['salary']);
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
            $message = "Financial proof submitted successfully for review!";
            
            // Re-fetch financials
            $stmt_f->execute();
            $financial = $stmt_f->get_result()->fetch_assoc();
            $fin_status = $financial['status'] ?? 'none';
            $hasFinancial = ($financial !== null && !empty($financial['salary_proof']));
            $financialApproved = ($fin_status === 'approved');
        }
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
            <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Manage your personal information and security parameters.</p>
        </div>

        <!-- Sandbox Simulation Alert -->
        <div style="background: #e1f5fe; color: #0288d1; padding: 18px 25px; border-radius: 15px; margin-bottom: 30px; border: 1px solid #b3e5fc; font-size: 0.85rem; line-height: 1.5; font-weight: 500; display: flex; align-items: center; gap: 15px;">
            <span style="font-size: 1.5rem;">⚙️</span>
            <div>
                <strong>Sandbox Simulator Active:</strong> Since this is a development build, all simulated SMS & Email 2FA verification codes are written to your local logs. Check <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; font-weight: bold; font-family: monospace;">otp_sent.log</code> in your root directory to view the codes!
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: #eafaf1; color: #27ae60; padding: 15px 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #d4edda; font-weight: bold; font-size: 0.9rem;">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #fdf2f2; color: #de350b; padding: 15px 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #fde8e8; font-weight: bold; font-size: 0.9rem;">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
            
            <!-- Left: Profile Form Hub -->
            <div style="display: flex; flex-direction: column; gap: 30px;">
                
                <!-- Card 1: Name Update -->
                <div style="background: white; border-radius: 25px; padding: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                    <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 25px; font-size: 1.2rem;">Store Identity</h3>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="update_name">
                        <div style="margin-bottom: 25px;">
                            <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                                   style="width: 100%; padding: 14px; border: 2px solid #f1f3f5; border-radius: 12px; font-size: 0.95rem; outline: none; transition: 0.3s;"
                                   onfocus="this.style.borderColor='#222'">
                        </div>
                        <button type="submit" style="background: #222; color: white; padding: 12px 25px; border: none; border-radius: 10px; font-weight: bold; font-size: 0.85rem; cursor: pointer;">
                            Update Name
                        </button>
                    </form>
                </div>

                <!-- Card 2: Secure Email Update Panel (2FA Protected) -->
                <div style="background: white; border-radius: 25px; padding: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0; font-weight: 900; font-size: 1.2rem;">Email Address</h3>
                        <span style="font-size: 0.65rem; font-weight: 900; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; background: #eafaf1; color: #27ae60; display: flex; align-items: center; gap: 4px;">
                            🔒 Locked
                        </span>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Current Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                               style="width: 100%; padding: 14px; border: 2px solid #f1f3f5; border-radius: 12px; font-size: 0.95rem; background: #fafafa; color: #999; cursor: not-allowed;">
                    </div>

                    <!-- OTP Unlock Panel -->
                    <?php if (!empty($_SESSION['phone_verified_for_email'])): ?>
                        <div style="background: #eafaf1; color: #27ae60; padding: 15px; border-radius: 12px; border: 1px solid #d4edda; margin-bottom: 20px; font-size: 0.85rem; font-weight: bold;">
                            🔓 SMS Verification Complete! You can now update your email address.
                        </div>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_email">
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">New Email Address</label>
                                <input type="email" name="new_email" required placeholder="e.g. newemail@domain.com"
                                       style="width: 100%; padding: 14px; border: 2px solid #f1f3f5; border-radius: 12px; font-size: 0.95rem; outline: none;">
                            </div>
                            <button type="submit" style="background: #27ae60; color: white; padding: 12px 25px; border: none; border-radius: 10px; font-weight: bold; font-size: 0.85rem; cursor: pointer;">
                                Confirm & Update Email
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Request OTP Form -->
                        <?php if (isset($_SESSION['phone_otp_for_email'])): ?>
                            <div style="background: #f8f9fa; border: 1px solid #eee; border-radius: 15px; padding: 20px; margin-bottom: 20px;">
                                <div style="font-size: 0.85rem; font-weight: 800; margin-bottom: 8px; color: #2d3436;">🔒 Identity Verification Challenge</div>
                                <p style="margin: 0 0 15px 0; font-size: 0.75rem; color: #636e72; line-height: 1.4;">
                                    Enter the 6-digit SMS verification code simulated and sent to +212 ***-***-<?php echo substr($user['phone'], -2); ?>.
                                </p>
                                <form action="" method="POST">
                                    <input type="hidden" name="action" value="verify_phone_otp_for_email">
                                    <div style="margin-bottom: 15px;">
                                        <input type="text" name="otp_code" required maxlength="6" placeholder="Enter 6-digit code"
                                               style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 10px; font-size: 0.95rem; text-align: center; letter-spacing: 4px; font-family: monospace; outline: none;">
                                    </div>
                                    <button type="submit" style="width: 100%; background: #222; color: white; padding: 12px; border: none; border-radius: 10px; font-weight: bold; font-size: 0.85rem; cursor: pointer;">
                                        Verify SMS Code
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="request_phone_otp_for_email">
                            <button type="submit" style="background: #222; color: white; padding: 12px 25px; border: none; border-radius: 10px; font-weight: bold; font-size: 0.85rem; cursor: pointer;">
                                <?php echo isset($_SESSION['phone_otp_for_email']) ? '🔄 Resend Verification Code' : 'Request Email Update'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Card 3: Secure Phone Update Panel (2FA Protected) -->
                <div style="background: white; border-radius: 25px; padding: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0; font-weight: 900; font-size: 1.2rem;">Phone Number</h3>
                        <span style="font-size: 0.65rem; font-weight: 900; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; background: #eafaf1; color: #27ae60; display: flex; align-items: center; gap: 4px;">
                            🔒 Locked
                        </span>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Current Phone Number</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['phone']); ?>" disabled
                               style="width: 100%; padding: 14px; border: 2px solid #f1f3f5; border-radius: 12px; font-size: 0.95rem; background: #fafafa; color: #999; cursor: not-allowed;">
                    </div>

                    <!-- OTP Unlock Panel -->
                    <?php if (!empty($_SESSION['email_verified_for_phone'])): ?>
                        <div style="background: #eafaf1; color: #27ae60; padding: 15px; border-radius: 12px; border: 1px solid #d4edda; margin-bottom: 20px; font-size: 0.85rem; font-weight: bold;">
                            🔓 Email Verification Complete! You can now update your phone number.
                        </div>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_phone">
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">New Phone Number</label>
                                <input type="text" name="new_phone" required placeholder="e.g. +212 612345678"
                                       style="width: 100%; padding: 14px; border: 2px solid #f1f3f5; border-radius: 12px; font-size: 0.95rem; outline: none;">
                            </div>
                            <button type="submit" style="background: #27ae60; color: white; padding: 12px 25px; border: none; border-radius: 10px; font-weight: bold; font-size: 0.85rem; cursor: pointer;">
                                Confirm & Update Phone
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Request OTP Form -->
                        <?php if (isset($_SESSION['email_otp_for_phone'])): ?>
                            <div style="background: #f8f9fa; border: 1px solid #eee; border-radius: 15px; padding: 20px; margin-bottom: 20px;">
                                <div style="font-size: 0.85rem; font-weight: 800; margin-bottom: 8px; color: #2d3436;">🔒 Identity Verification Challenge</div>
                                <p style="margin: 0 0 15px 0; font-size: 0.75rem; color: #636e72; line-height: 1.4;">
                                    Enter the 6-digit Email verification code simulated and sent to <?php echo substr($user['email'], 0, 3) . '***@domain.com'; ?>.
                                </p>
                                <form action="" method="POST">
                                    <input type="hidden" name="action" value="verify_email_otp_for_phone">
                                    <div style="margin-bottom: 15px;">
                                        <input type="text" name="otp_code" required maxlength="6" placeholder="Enter 6-digit code"
                                               style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 10px; font-size: 0.95rem; text-align: center; letter-spacing: 4px; font-family: monospace; outline: none;">
                                    </div>
                                    <button type="submit" style="width: 100%; background: #222; color: white; padding: 12px; border: none; border-radius: 10px; font-weight: bold; font-size: 0.85rem; cursor: pointer;">
                                        Verify Email Code
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="request_email_otp_for_phone">
                            <button type="submit" style="background: #222; color: white; padding: 12px 25px; border: none; border-radius: 10px; font-weight: bold; font-size: 0.85rem; cursor: pointer;">
                                <?php echo isset($_SESSION['email_otp_for_phone']) ? '🔄 Resend Verification Code' : 'Request Phone Update'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Right Column: Verification & Security Stats -->
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

        <!-- Section: Identity & Financial Verification (Klarna / Tabby Style) -->
        <div id="verification-section" style="background: white; border-radius: 25px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); margin-top: 40px;">
            <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 8px;">Identity & Credit Verification Documents</h3>
            <p style="color: #636e72; font-size: 0.9rem; margin: 0 0 35px 0;">Submit compliance documents to unlock or increase your purchase credit limit.</p>
            
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
                        
                        <form action="#verification-section" method="POST" enctype="multipart/form-data">
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

                <!-- Right Column: Financial Profile & Income Proof -->
                <div>
                    <h4 style="margin-top: 0; font-size: 1.1rem; font-weight: 900; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <span>📈</span> Income & Financial Profile
                    </h4>
                    
                    <?php if ($financialApproved): ?>
                        <div style="background: #eafaf1; color: #27ae60; padding: 20px; border-radius: 15px; border: 1px solid #d4edda; margin-bottom: 20px;">
                            <div style="font-weight: 900; font-size: 0.95rem;">✅ Financials Verified & Sealed</div>
                            <p style="margin: 5px 0 0 0; font-size: 0.8rem; line-height: 1.4;">
                                Profession: <strong><?php echo htmlspecialchars($financial['profession']); ?></strong><br>
                                Monthly Salary: <strong><?php echo number_format($financial['salary'], 2); ?> DH</strong>
                            </p>
                        </div>
                    <?php else: ?>
                        <?php if ($financial && $financial['status'] === 'pending'): ?>
                            <div style="background: #fff9db; color: #856404; padding: 15px; border-radius: 12px; border: 1px solid #ffeeba; margin-bottom: 20px; font-size: 0.85rem; font-weight: bold;">
                                ⏳ Review in progress: Income documents submitted.
                            </div>
                        <?php endif; ?>
                        
                        <form action="#verification-section" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_financials">
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Profession / Job Title</label>
                                <input type="text" name="profession" required value="<?php echo htmlspecialchars($financial['profession'] ?? ''); ?>" 
                                       placeholder="e.g. Software Engineer, Manager, Doctor"
                                       style="width: 100%; padding: 12px; border: 2px solid #f1f3f5; border-radius: 10px; font-size: 0.9rem; outline: none;">
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Monthly Net Salary (DH)</label>
                                <input type="number" name="salary" required value="<?php echo htmlspecialchars($financial['salary'] ?? ''); ?>" 
                                       placeholder="e.g. 8500"
                                       style="width: 100%; padding: 12px; border: 2px solid #f1f3f5; border-radius: 10px; font-size: 0.9rem; outline: none;">
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Upload Proof of Income (Salary Slip / Statement)</label>
                                <input type="file" name="salary_proof" <?php echo $financial ? '' : 'required'; ?> style="font-size: 0.85rem; color: #636e72;">
                                <?php if ($financial && $financial['salary_proof']): ?>
                                    <small style="display: block; margin-top: 5px; color: #0984e3; font-weight: bold;">📄 Current: <?php echo htmlspecialchars($financial['salary_proof']); ?></small>
                                <?php endif; ?>
                            </div>
                            <button type="submit" style="background: #222; color: white; padding: 12px 25px; border: none; border-radius: 10px; font-weight: bold; font-size: 0.85rem; cursor: pointer;">
                                Submit Financial Profile
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Simulated Push Notification Toast (iOS/SaaS style) for Cross-Device Testing -->
    <?php if (!empty($_SESSION['show_otp_popup'])): ?>
        <?php 
        $toast = $_SESSION['show_otp_popup'];
        unset($_SESSION['show_otp_popup']); // Consume immediately
        ?>
        <div id="otp-toast-notification" style="position: fixed; top: 20px; right: 20px; z-index: 99999; width: 360px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.12); border: 1px solid rgba(0,0,0,0.06); padding: 22px; transform: translateY(-50px); opacity: 0; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); font-family: system-ui, -apple-system, sans-serif;">
            <div style="display: flex; align-items: start; gap: 15px;">
                <div style="font-size: 2rem; line-height: 1; padding-top: 2px;">
                    <?php echo ($toast['type'] === 'phone') ? '💬' : '📧'; ?>
                </div>
                <div style="flex-grow: 1;">
                    <div style="font-weight: 850; font-size: 0.95rem; color: #1e272e; margin: 0 0 5px 0; display: flex; align-items: center; justify-content: space-between;">
                        <span><?php echo ($toast['type'] === 'phone') ? 'Simulated SMS' : 'Simulated Email'; ?></span>
                        <span style="font-size: 0.65rem; color: #aaa; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">now</span>
                    </div>
                    <div style="font-size: 0.85rem; color: #57606f; line-height: 1.45; margin-bottom: 15px;">
                        <?php if ($toast['type'] === 'phone'): ?>
                            Your TQSEET email update verification code is: <strong style="font-family: monospace; font-size: 1.15rem; color: #2e86de; background: rgba(46,134,222,0.08); padding: 2px 6px; border-radius: 6px;"><?php echo $toast['code']; ?></strong>
                        <?php else: ?>
                            Your TQSEET phone update verification code is: <strong style="font-family: monospace; font-size: 1.15rem; color: #6c5ce7; background: rgba(108,92,231,0.08); padding: 2px 6px; border-radius: 6px;"><?php echo $toast['code']; ?></strong>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="autofillOTP('<?php echo $toast['code']; ?>')" style="background: #2f3542; color: white; border: none; padding: 8px 14px; border-radius: 8px; font-size: 0.75rem; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: 0.2s;">
                            ⚡ Autofill Code
                        </button>
                        <button onclick="dismissToast()" style="background: transparent; color: #57606f; border: 1px solid #ced6e0; padding: 8px 14px; border-radius: 8px; font-size: 0.75rem; font-weight: bold; cursor: pointer; transition: 0.2s;">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
            <!-- Progress Bar -->
            <div id="toast-progress" style="position: absolute; bottom: 0; left: 0; height: 3px; background: <?php echo ($toast['type'] === 'phone') ? '#2e86de' : '#6c5ce7'; ?>; width: 100%; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; transition: width 15s linear;"></div>
        </div>
        
        <script>
            // Slide Down Entrance
            setTimeout(function() {
                var toast = document.getElementById('otp-toast-notification');
                var progress = document.getElementById('toast-progress');
                if (toast) {
                    toast.style.transform = 'translateY(0)';
                    toast.style.opacity = '1';
                }
                if (progress) {
                    setTimeout(function() {
                        progress.style.width = '0%';
                    }, 50);
                }
            }, 100);

            function dismissToast() {
                var toast = document.getElementById('otp-toast-notification');
                if (toast) {
                    toast.style.transform = 'translateY(-50px)';
                    toast.style.opacity = '0';
                    setTimeout(function() {
                        toast.remove();
                    }, 400);
                }
            }

            function autofillOTP(code) {
                var inputs = document.getElementsByName('otp_code');
                for (var i = 0; i < inputs.length; i++) {
                    if (inputs[i].offsetWidth > 0 || inputs[i].offsetHeight > 0) {
                        inputs[i].value = code;
                        inputs[i].focus();
                        break;
                    }
                }
                dismissToast();
            }

            // Auto-dismiss after 15 seconds
            setTimeout(dismissToast, 15000);
        </script>
    <?php endif; ?>

</body>
</html>

