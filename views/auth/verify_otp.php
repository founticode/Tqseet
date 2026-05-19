<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/otp_helpers.php";
require_once __DIR__ . "/../../config/db.php";

// Try to get user from session (if logged in) or from temp session (if just registered)
$userId = $_SESSION["user_id"] ?? $_SESSION["temp_user_id"] ?? null;

if (!$userId) {
    header("Location: login.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // Auto-generate a fresh OTP if none is currently primed to show (e.g. landing here directly from dashboard)
    if (empty($_SESSION["show_otp_popup"])) {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt_u = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt_u->bind_param("i", $userId);
        $stmt_u->execute();
        $res_u = $stmt_u->get_result()->fetch_assoc();
        $email = $res_u['email'] ?? ($_SESSION["temp_user_email"] ?? 'user@tqseet.com');
        $stmt_u->close();
        
        // Generate, save and simulate send
        $otp = generateOTP();
        saveOTP($conn, $userId, $otp);
        sendOTP($email, $otp);
        
        $_SESSION["show_otp_popup"] = [
            'code' => $otp,
            'type' => 'email'
        ];
        
        $conn->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputCode = $_POST["otp_code"];

    $db = new Database();
    $conn = $db->connect();

    if (verifyOTP($conn, $userId, $inputCode)) {
        // SUCCESS! 
        // 1. Mark user as verified in the database
        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // 2. Update session status
        $_SESSION["user_verified"] = 1;

        // 3. Role-Aware Redirect
        $role = $_SESSION["user_role"] ?? 'user';
        header("Location: ../{$role}/dashboard.php?verified=1");
        exit;
    } else {
        $error = "Invalid or expired code. Please check your log file and try again.";
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Verify OTP</title>
</head>
<body style="font-family: sans-serif; text-align: center; padding: 50px; background: #f9f9f9;">

    <div style="max-width: 400px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h1>Verification</h1>
        <p>Enter the 6-digit code we just "sent" you.</p>
        
        <p style="color: #666; font-size: 0.85rem; background: #eee; padding: 5px; border-radius: 4px;">
            🔍 Testing tip: Open <strong>otp_sent.log</strong> in your project root to find the code.
        </p>

        <?php if ($error): ?>
            <p style="color: #dc3545; font-weight: bold;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" style="margin-top: 20px;">
            <input type="text" name="otp_code" maxlength="6" required 
                   placeholder="000000" 
                   style="font-size: 2rem; width: 100%; text-align: center; letter-spacing: 10px; padding: 10px; border: 2px solid #ddd; border-radius: 4px;">
            <br><br>
            <button type="submit" style="width: 100%; padding: 12px; font-size: 1.1rem; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px; font-weight: bold;">
                Confirm Code
            </button>
        </form>

        <p style="margin-top: 20px; font-size: 0.9rem;">
            <?php $role = $_SESSION['user_role'] ?? 'user'; ?>
            <a href="../<?php echo $role; ?>/dashboard.php" style="color: #666; text-decoration: none;">← Cancel and go back</a>
        </p>
    </div>

    <!-- Simulated Push Notification Toast (iOS/SaaS style) for Cross-Device Testing -->
    <?php if (!empty($_SESSION['show_otp_popup'])): ?>
        <?php 
        $toast = $_SESSION['show_otp_popup'];
        unset($_SESSION['show_otp_popup']); // Consume immediately
        ?>
        <div id="otp-toast-notification" style="position: fixed; top: 20px; right: 20px; z-index: 99999; width: 360px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.12); border: 1px solid rgba(0,0,0,0.06); padding: 22px; transform: translateY(-50px); opacity: 0; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); font-family: system-ui, -apple-system, sans-serif; text-align: left;">
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
                        Your TQSEET verification code is: <strong style="font-family: monospace; font-size: 1.15rem; color: #6c5ce7; background: rgba(108,92,231,0.08); padding: 2px 6px; border-radius: 6px;"><?php echo $toast['code']; ?></strong>
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
            <div id="toast-progress" style="position: absolute; bottom: 0; left: 0; height: 3px; background: #6c5ce7; width: 100%; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; transition: width 15s linear;"></div>
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
                var input = document.getElementsByName('otp_code')[0];
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
