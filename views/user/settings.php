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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - TQSEET</title>
    <style>
        .settings-container { max-width: 1000px; margin: 60px auto; padding: 0 20px; }
        .settings-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 32px; }
        .kyc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; }
        
        @media (max-width: 900px) {
            .settings-grid { grid-template-columns: 1fr; }
            .kyc-grid { grid-template-columns: 1fr; gap: 32px; }
        }
    </style>
</head>
<body style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #fafafa; margin: 0; color: #111827;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="settings-container">
        
        <div style="margin-bottom: 40px; text-align: center;">
            <h1 style="font-weight: 900; font-size: 2.8rem; margin: 0; letter-spacing: -1px; color: #111827;">Account Settings</h1>
            <p style="color: #6b7280; margin-top: 8px; font-size: 1.1rem;">Manage your personal information and security parameters.</p>
        </div>

        <!-- Sandbox Simulation Alert -->
        <div style="background: #eff6ff; color: #1d4ed8; padding: 20px; border-radius: 16px; margin-bottom: 32px; border: 1px solid #bfdbfe; font-size: 0.9rem; font-weight: 500; display: flex; align-items: flex-start; gap: 16px;">
            <span style="font-size: 1.5rem;">⚙️</span>
            <div>
                <strong>Sandbox Simulator Active:</strong> Since this is a development build, all simulated SMS & Email 2FA verification codes are written to your local logs. Check <code style="background: white; padding: 2px 6px; border-radius: 6px; font-weight: bold; font-family: monospace; border: 1px solid #bfdbfe;">otp_sent.log</code> in your root directory to view the codes!
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: #ecfdf5; color: #065f46; padding: 16px 20px; border-radius: 16px; margin-bottom: 30px; border: 1px solid #a7f3d0; font-weight: 600; font-size: 0.95rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 16px 20px; border-radius: 16px; margin-bottom: 30px; border: 1px solid #fecaca; font-weight: 600; font-size: 0.95rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid">
            
            <!-- Left: Profile Form Hub -->
            <div style="display: flex; flex-direction: column; gap: 32px;">
                
                <!-- Card 1: Name Update -->
                <div style="background: white; border-radius: 20px; padding: 32px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border: 1px solid #f3f4f6;">
                    <h3 style="margin-top: 0; font-weight: 800; margin-bottom: 24px; font-size: 1.25rem;">Personal Identity</h3>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="update_name">
                        <div style="margin-bottom: 24px;">
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                                   style="width: 100%; padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 1rem; color: #111827; outline: none; background: #f9fafb; transition: all 0.2s;"
                                   onfocus="this.style.borderColor='#111827'; this.style.background='white';">
                        </div>
                        <button type="submit" style="background: #111827; color: white; padding: 14px 24px; border: none; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: background 0.2s;">
                            Update Name
                        </button>
                    </form>
                </div>

                <!-- Card 2: Secure Email Update Panel (2FA Protected) -->
                <div style="background: white; border-radius: 20px; padding: 32px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border: 1px solid #f3f4f6;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3 style="margin: 0; font-weight: 800; font-size: 1.25rem;">Email Address</h3>
                        <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 6px 12px; border-radius: 20px; background: #f3f4f6; color: #4b5563; display: flex; align-items: center; gap: 6px; letter-spacing: 0.5px;">
                            🔒 Locked
                        </span>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">Current Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                               style="width: 100%; padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 1rem; background: #f3f4f6; color: #9ca3af; cursor: not-allowed;">
                    </div>

                    <!-- OTP Unlock Panel -->
                    <?php if (!empty($_SESSION['phone_verified_for_email'])): ?>
                        <div style="background: #ecfdf5; color: #065f46; padding: 16px; border-radius: 12px; border: 1px solid #a7f3d0; margin-bottom: 24px; font-size: 0.9rem; font-weight: 600;">
                            🔓 SMS Verification Complete! You can now update your email address.
                        </div>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_email">
                            <div style="margin-bottom: 24px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">New Email Address</label>
                                <input type="email" name="new_email" required placeholder="e.g. newemail@domain.com"
                                       style="width: 100%; padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 1rem; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#111827';">
                            </div>
                            <button type="submit" style="background: #10b981; color: white; padding: 14px 24px; border: none; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer;">
                                Confirm & Update Email
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Request OTP Form -->
                        <?php if (isset($_SESSION['phone_otp_for_email'])): ?>
                            <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 16px; padding: 24px; margin-bottom: 24px;">
                                <div style="font-size: 0.9rem; font-weight: 800; margin-bottom: 8px; color: #6b21a8;">🔒 Identity Challenge</div>
                                <p style="margin: 0 0 16px 0; font-size: 0.85rem; color: #7e22ce; line-height: 1.5;">
                                    Enter the 6-digit SMS code simulated and sent to +212 ***-***-<?php echo substr($user['phone'], -2); ?>.
                                </p>
                                <form action="" method="POST">
                                    <input type="hidden" name="action" value="verify_phone_otp_for_email">
                                    <div style="margin-bottom: 16px;">
                                        <input type="text" name="otp_code" required maxlength="6" placeholder="Enter 6-digit code"
                                               style="width: 100%; padding: 14px; border: 1px solid #d8b4fe; border-radius: 12px; font-size: 1.1rem; text-align: center; letter-spacing: 8px; font-family: monospace; outline: none;">
                                    </div>
                                    <button type="submit" style="width: 100%; background: #6b21a8; color: white; padding: 14px; border: none; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer;">
                                        Verify SMS Code
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="request_phone_otp_for_email">
                            <button type="submit" style="background: #111827; color: white; padding: 14px 24px; border: none; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer;">
                                <?php echo isset($_SESSION['phone_otp_for_email']) ? '🔄 Resend Verification Code' : 'Request Email Update'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Card 3: Secure Phone Update Panel (2FA Protected) -->
                <div style="background: white; border-radius: 20px; padding: 32px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border: 1px solid #f3f4f6;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3 style="margin: 0; font-weight: 800; font-size: 1.25rem;">Phone Number</h3>
                        <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 6px 12px; border-radius: 20px; background: #f3f4f6; color: #4b5563; display: flex; align-items: center; gap: 6px; letter-spacing: 0.5px;">
                            🔒 Locked
                        </span>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">Current Phone Number</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['phone']); ?>" disabled
                               style="width: 100%; padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 1rem; background: #f3f4f6; color: #9ca3af; cursor: not-allowed;">
                    </div>

                    <!-- OTP Unlock Panel -->
                    <?php if (!empty($_SESSION['email_verified_for_phone'])): ?>
                        <div style="background: #ecfdf5; color: #065f46; padding: 16px; border-radius: 12px; border: 1px solid #a7f3d0; margin-bottom: 24px; font-size: 0.9rem; font-weight: 600;">
                            🔓 Email Verification Complete! You can now update your phone number.
                        </div>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_phone">
                            <div style="margin-bottom: 24px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">New Phone Number</label>
                                <input type="text" name="new_phone" required placeholder="e.g. +212 612345678"
                                       style="width: 100%; padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 1rem; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#111827';">
                            </div>
                            <button type="submit" style="background: #10b981; color: white; padding: 14px 24px; border: none; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer;">
                                Confirm & Update Phone
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Request OTP Form -->
                        <?php if (isset($_SESSION['email_otp_for_phone'])): ?>
                            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 16px; padding: 24px; margin-bottom: 24px;">
                                <div style="font-size: 0.9rem; font-weight: 800; margin-bottom: 8px; color: #1e40af;">🔒 Identity Challenge</div>
                                <p style="margin: 0 0 16px 0; font-size: 0.85rem; color: #1e3a8a; line-height: 1.5;">
                                    Enter the 6-digit Email code simulated and sent to <?php echo substr($user['email'], 0, 3) . '***@domain.com'; ?>.
                                </p>
                                <form action="" method="POST">
                                    <input type="hidden" name="action" value="verify_email_otp_for_phone">
                                    <div style="margin-bottom: 16px;">
                                        <input type="text" name="otp_code" required maxlength="6" placeholder="Enter 6-digit code"
                                               style="width: 100%; padding: 14px; border: 1px solid #93c5fd; border-radius: 12px; font-size: 1.1rem; text-align: center; letter-spacing: 8px; font-family: monospace; outline: none;">
                                    </div>
                                    <button type="submit" style="width: 100%; background: #1e40af; color: white; padding: 14px; border: none; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer;">
                                        Verify Email Code
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="request_email_otp_for_phone">
                            <button type="submit" style="background: #111827; color: white; padding: 14px 24px; border: none; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer;">
                                <?php echo isset($_SESSION['email_otp_for_phone']) ? '🔄 Resend Verification Code' : 'Request Phone Update'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Right Column: Verification & Security Stats -->
            <div style="display: flex; flex-direction: column; gap: 32px;">
                <div style="background: white; border-radius: 20px; padding: 32px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border: 1px solid #f3f4f6;">
                    <h3 style="margin-top: 0; font-weight: 800; margin-bottom: 24px; font-size: 1.1rem; color: #111827;">Verification Status</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.9rem; font-weight: 700; color: #6b7280;">Identity</span>
                            <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 4px 12px; border-radius: 20px; letter-spacing: 0.5px;
                                  <?php echo ($id_status === 'approved') ? 'background: #dcfce7; color: #166534;' : 'background: #fef3c7; color: #92400e;'; ?>">
                                <?php echo htmlspecialchars($id_status); ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.9rem; font-weight: 700; color: #6b7280;">Financials</span>
                            <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 4px 12px; border-radius: 20px; letter-spacing: 0.5px;
                                  <?php echo ($fin_status === 'approved') ? 'background: #dcfce7; color: #166534;' : 'background: #fef3c7; color: #92400e;'; ?>">
                                <?php echo htmlspecialchars($fin_status); ?>
                            </span>
                        </div>
                    </div>
                    
                    <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 24px 0;">
                    <a href="#verification-section" style="font-size: 0.9rem; color: #3b82f6; text-decoration: none; font-weight: 700;">Complete Verification →</a>
                </div>

                <div style="background: #111827; border-radius: 20px; padding: 32px; color: white; box-shadow: 0 10px 25px -5px rgba(17, 24, 39, 0.2);">
                    <h3 style="margin-top: 0; font-weight: 800; margin-bottom: 16px; font-size: 1.1rem;">Account Security</h3>
                    <p style="font-size: 0.85rem; color: #9ca3af; line-height: 1.5; margin-bottom: 24px;">Protect your account by using a strong password. Enable 2FA for maximum protection.</p>
                    <a href="update_password.php" style="display: block; background: white; color: #111827; text-align: center; padding: 14px; text-decoration: none; border-radius: 12px; font-size: 0.9rem; font-weight: 800; transition: opacity 0.2s;">
                        Update Password
                    </a>
                </div>
            </div>

        </div>

        <!-- Section: Identity & Financial Verification -->
        <div id="verification-section" style="background: white; border-radius: 24px; padding: 40px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border: 1px solid #f3f4f6; margin-top: 40px;">
            <h3 style="margin-top: 0; font-weight: 900; font-size: 1.5rem; margin-bottom: 12px; color: #111827;">Compliance & KYC Documents</h3>
            <p style="color: #6b7280; font-size: 1rem; margin: 0 0 40px 0;">Submit compliance documents to unlock or increase your purchase credit limit.</p>
            
            <div class="kyc-grid">
                
                <!-- Left Column: Identity Card (CIN) -->
                <div>
                    <h4 style="margin-top: 0; font-size: 1.15rem; font-weight: 800; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; color: #111827;">
                        <span>🪪</span> Identity Verification (CIN)
                    </h4>
                    
                    <?php if ($cinApproved): ?>
                        <div style="background: #f0fdf4; color: #166534; padding: 24px; border-radius: 16px; border: 1px solid #bbf7d0; margin-bottom: 24px;">
                            <div style="font-weight: 800; font-size: 1rem;">✅ ID Verified & Sealed</div>
                            <p style="margin: 8px 0 0 0; font-size: 0.85rem; line-height: 1.5;">CIN: <strong style="color: #14532d;"><?php echo htmlspecialchars($verification['cin']); ?></strong></p>
                        </div>
                        <?php if (!empty($verification['cin_image'])): ?>
                            <img src="../../uploads/verifications/<?php echo $verification['cin_image']; ?>" style="max-width: 100%; border-radius: 12px; border: 1px solid #e5e7eb; margin-top: 8px;">
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($verification && $verification['status'] === 'pending'): ?>
                            <div style="background: #fffbeb; color: #92400e; padding: 16px 20px; border-radius: 12px; border: 1px solid #fde68a; margin-bottom: 24px; font-size: 0.9rem; font-weight: 700;">
                                ⏳ Review in progress: Identity document submitted.
                            </div>
                        <?php endif; ?>
                        
                        <form action="#verification-section" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_cin">
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">CIN Number</label>
                                <input type="text" name="cin" required value="<?php echo htmlspecialchars($verification['cin'] ?? ''); ?>" 
                                       placeholder="e.g. AB123456"
                                       style="width: 100%; padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 1rem; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#111827';">
                            </div>
                            <div style="margin-bottom: 24px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">Upload Document (Photo/PDF)</label>
                                <input type="file" name="cin_image" <?php echo $verification ? '' : 'required'; ?> style="font-size: 0.9rem; color: #4b5563; padding: 10px 0;">
                                <?php if ($verification && $verification['cin_image']): ?>
                                    <small style="display: block; margin-top: 8px; color: #3b82f6; font-weight: 600;">📄 Current: <?php echo htmlspecialchars($verification['cin_image']); ?></small>
                                <?php endif; ?>
                            </div>
                            <button type="submit" style="background: #111827; color: white; padding: 14px 24px; border: none; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer; width: 100%;">
                                Submit Identity Card
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Financial Profile & Income Proof -->
                <div>
                    <h4 style="margin-top: 0; font-size: 1.15rem; font-weight: 800; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; color: #111827;">
                        <span>📈</span> Financial Profile
                    </h4>
                    
                    <?php if ($financialApproved): ?>
                        <div style="background: #f0fdf4; color: #166534; padding: 24px; border-radius: 16px; border: 1px solid #bbf7d0; margin-bottom: 24px;">
                            <div style="font-weight: 800; font-size: 1rem;">✅ Financials Verified & Sealed</div>
                            <p style="margin: 8px 0 0 0; font-size: 0.85rem; line-height: 1.5;">
                                Profession: <strong style="color: #14532d;"><?php echo htmlspecialchars($financial['profession']); ?></strong><br>
                                Net Salary: <strong style="color: #14532d;"><?php echo number_format($financial['salary'], 2); ?> DH / month</strong>
                            </p>
                        </div>
                    <?php else: ?>
                        <?php if ($financial && $financial['status'] === 'pending'): ?>
                            <div style="background: #fffbeb; color: #92400e; padding: 16px 20px; border-radius: 12px; border: 1px solid #fde68a; margin-bottom: 24px; font-size: 0.9rem; font-weight: 700;">
                                ⏳ Review in progress: Income documents submitted.
                            </div>
                        <?php endif; ?>
                        
                        <form action="#verification-section" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_financials">
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">Job Title</label>
                                <input type="text" name="profession" required value="<?php echo htmlspecialchars($financial['profession'] ?? ''); ?>" 
                                       placeholder="e.g. Software Engineer"
                                       style="width: 100%; padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 1rem; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#111827';">
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">Net Salary (DH)</label>
                                <input type="number" name="salary" required value="<?php echo htmlspecialchars($financial['salary'] ?? ''); ?>" 
                                       placeholder="e.g. 8500"
                                       style="width: 100%; padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 1rem; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#111827';">
                            </div>
                            <div style="margin-bottom: 24px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">Proof of Income (Slip/Statement)</label>
                                <input type="file" name="salary_proof" <?php echo $financial ? '' : 'required'; ?> style="font-size: 0.9rem; color: #4b5563; padding: 10px 0;">
                                <?php if ($financial && $financial['salary_proof']): ?>
                                    <small style="display: block; margin-top: 8px; color: #3b82f6; font-weight: 600;">📄 Current: <?php echo htmlspecialchars($financial['salary_proof']); ?></small>
                                <?php endif; ?>
                            </div>
                            <button type="submit" style="background: #111827; color: white; padding: 14px 24px; border: none; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer; width: 100%;">
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
        <div id="otp-toast-notification" style="position: fixed; top: 24px; right: 24px; z-index: 99999; width: 380px; background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid rgba(0,0,0,0.08); padding: 24px; transform: translateY(-100px); opacity: 0; transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); font-family: 'Inter', sans-serif;">
            <div style="display: flex; align-items: flex-start; gap: 16px;">
                <div style="font-size: 2.5rem; line-height: 1;">
                    <?php echo ($toast['type'] === 'phone') ? '💬' : '📧'; ?>
                </div>
                <div style="flex-grow: 1;">
                    <div style="font-weight: 800; font-size: 1.05rem; color: #111827; margin: 0 0 6px 0; display: flex; align-items: center; justify-content: space-between;">
                        <span><?php echo ($toast['type'] === 'phone') ? 'Simulated SMS' : 'Simulated Email'; ?></span>
                        <span style="font-size: 0.65rem; color: #9ca3af; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">now</span>
                    </div>
                    <div style="font-size: 0.9rem; color: #4b5563; line-height: 1.5; margin-bottom: 20px;">
                        <?php if ($toast['type'] === 'phone'): ?>
                            Your verification code is: <br><strong style="display: inline-block; margin-top: 8px; font-family: monospace; font-size: 1.3rem; color: #4f46e5; background: #e0e7ff; padding: 4px 10px; border-radius: 8px; letter-spacing: 2px;"><?php echo $toast['code']; ?></strong>
                        <?php else: ?>
                            Your verification code is: <br><strong style="display: inline-block; margin-top: 8px; font-family: monospace; font-size: 1.3rem; color: #9333ea; background: #f3e8ff; padding: 4px 10px; border-radius: 8px; letter-spacing: 2px;"><?php echo $toast['code']; ?></strong>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button onclick="autofillOTP('<?php echo $toast['code']; ?>')" style="flex: 1; background: #111827; color: white; border: none; padding: 10px; border-radius: 10px; font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: background 0.2s;">
                            ⚡ Autofill
                        </button>
                        <button onclick="dismissToast()" style="background: transparent; color: #6b7280; border: 1px solid #d1d5db; padding: 10px 16px; border-radius: 10px; font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: background 0.2s;">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
            <!-- Progress Bar -->
            <div id="toast-progress" style="position: absolute; bottom: 0; left: 0; height: 4px; background: <?php echo ($toast['type'] === 'phone') ? '#4f46e5' : '#9333ea'; ?>; width: 100%; border-bottom-left-radius: 24px; border-bottom-right-radius: 24px; transition: width 15s linear;"></div>
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
                    toast.style.transform = 'translateY(-100px)';
                    toast.style.opacity = '0';
                    setTimeout(function() {
                        toast.remove();
                    }, 500);
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
