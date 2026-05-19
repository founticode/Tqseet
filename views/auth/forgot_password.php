<?php
session_start();
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Forgot Password</title>
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

        <!-- Forgot Password Card -->
        <div class="auth-card">
            <h2 class="auth-card-title">Reset password</h2>
            <p class="auth-card-subtitle">Enter your email and we'll send you a verification code</p>

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

            <form action="../../controllers/AuthController.php?action=forgot_password" method="POST">
                <!-- Email Field -->
                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="alex.vance@techcorp.com" required>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-block" style="gap: 8px;">
                    Send Reset Code
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" style="stroke: #003a31; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;">
                        <path d="M3 8h10M8 3l5 5-5 5"/>
                    </svg>
                </button>
            </form>

            <!-- Back to Login Link -->
            <p style="margin-top: 25px; font-size: 0.85rem; color: #64748b; font-weight: 500;">
                <a href="login.php" style="color: #005a4e; font-weight: 700;">← Back to Login</a>
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

</body>
</html>
