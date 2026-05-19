<?php
session_start();
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// Ensure we have a valid reset session
if (!isset($_SESSION["reset_user_id"])) {
    $_SESSION['error'] = "Please submit your email first.";
    header("Location: forgot_password.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Reset Password</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

    <div class="auth-page">
        <!-- Logo & Header -->
        <div class="auth-header">
            <div class="auth-logo-box">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
            </div>
            <h1 class="auth-brand serif-title">TQSEET</h1>
            <p class="auth-subtitle">Precision. Agility. Security.</p>
        </div>

        <!-- Reset Password Card -->
        <div class="auth-card">
            <h2 class="auth-card-title">New Credentials</h2>
            <p class="auth-card-subtitle">Verify your code and enter your new password</p>

            <!-- Error and Success Banners -->
            <?php if ($error): ?>
                <div class="badge badge-danger" style="display: block; padding: 12px; margin-bottom: 20px; border-radius: 12px; font-size: 0.85rem; text-align: center; text-transform: none; font-weight: 500; line-height: 1.4;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="badge badge-success" style="display: block; padding: 12px; margin-bottom: 20px; border-radius: 12px; font-size: 0.85rem; text-align: center; text-transform: none; font-weight: 500; line-height: 1.4;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="../../controllers/AuthController.php?action=reset_password" method="POST">
                <!-- Verification Code -->
                <div class="form-group">
                    <label for="code" class="form-label">Verification Code (OTP)</label>
                    <input type="text" id="code" name="code" class="form-input" placeholder="000000" maxlength="6" style="text-align: center; font-size: 1.4rem; letter-spacing: 4px;" required>
                </div>

                <!-- New Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••••••" required>
                </div>

                <!-- Confirm Password Field -->
                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="••••••••••••" required>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-block" style="gap: 8px;">
                    Reset Password
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" style="stroke: #003a31; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;">
                        <path d="M3 8h10M8 3l5 5-5 5"/>
                    </svg>
                </button>
            </form>

            <!-- Back link -->
            <p style="margin-top: 25px; font-size: 0.85rem; color: #64748b; font-weight: 500;">
                <a href="forgot_password.php" style="color: #005a4e; font-weight: 700;">← Cancel and go back</a>
            </p>
        </div>

        <!-- Footer -->
        <div class="auth-footer">
            <div class="auth-footer-links">
                <a href="#">Privacy</a>
                <a href="#">Compliance</a>
                <a href="#">Contact</a>
            </div>
            <p class="auth-copyright">© 2026 TQSEET Financial Systems. Secured with Enterprise-grade encryption.</p>
        </div>
    </div>

    <!-- Simulated Push Notification Toast (iOS/SaaS style) for Cross-Device Testing -->
    <?php if (!empty($_SESSION['show_otp_popup'])): ?>
        <?php 
        $toast = $_SESSION['show_otp_popup'];
        unset($_SESSION['show_otp_popup']); // Consume immediately
        ?>
        <div id="otp-toast-notification" style="position: fixed; top: 20px; right: 20px; z-index: 99999; width: 360px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.12); border: 1px solid rgba(0,0,0,0.06); padding: 22px; transform: translateY(-50px); opacity: 0; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); font-family: 'Outfit', sans-serif; text-align: left;">
            <div style="display: flex; align-items: start; gap: 15px;">
                <div style="font-size: 2rem; line-height: 1; padding-top: 2px;">
                    📧
                </div>
                <div style="flex-grow: 1;">
                    <div style="font-weight: 800; font-size: 0.95rem; color: #1e272e; margin: 0 0 5px 0; display: flex; align-items: center; justify-content: space-between;">
                        <span>Simulated Reset Email</span>
                        <span style="font-size: 0.65rem; color: #aaa; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">now</span>
                    </div>
                    <div style="font-size: 0.85rem; color: #57606f; line-height: 1.45; margin-bottom: 15px;">
                        Your TQSEET password reset code is: <strong style="font-family: monospace; font-size: 1.15rem; color: #005a4e; background: rgba(0,90,78,0.08); padding: 2px 6px; border-radius: 6px;"><?php echo $toast['code']; ?></strong>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="autofillOTP('<?php echo $toast['code']; ?>')" style="background: #005a4e; color: white; border: none; padding: 8px 14px; border-radius: 8px; font-size: 0.75rem; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: 0.2s;">
                            ⚡ Autofill Code
                        </button>
                        <button onclick="dismissToast()" style="background: transparent; color: #57606f; border: 1px solid #ced6e0; padding: 8px 14px; border-radius: 8px; font-size: 0.75rem; font-weight: bold; cursor: pointer; transition: 0.2s;">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
            <!-- Progress Bar -->
            <div id="toast-progress" style="position: absolute; bottom: 0; left: 0; height: 3px; background: #00f5c7; width: 100%; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; transition: width 15s linear;"></div>
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
                var input = document.getElementById('code');
                if (input) {
                    input.value = code;
                    input.focus();
                }
                dismissToast();
            }

            // Auto-dismiss after 15 seconds
            setTimeout(dismissToast, 15000);
        </script>
    <?php endif; ?>

</body>
</html>
