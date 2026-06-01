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
    <title>TQSEET - Login</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

    <div class="auth-page">
        <!-- Logo & Header -->
        <div class="auth-header">
            <div class="auth-logo-box">
                <!-- Premium Geometric Hexagon/Cube SVG Logo -->
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
            </div>
            <h1 class="auth-brand" style="font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 2.2rem; letter-spacing: -1.2px; color: #005a4e; text-transform: uppercase; margin: 0 0 5px 0;">TQSEET</h1>
            <p class="auth-subtitle">Precision. Agility. Security.</p>
        </div>

        <!-- Login Card -->
        <div class="auth-card">
            <h2 class="auth-card-title">Welcome back</h2>
            <p class="auth-card-subtitle">Enter your credentials to continue</p>

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

            <form action="../../controllers/AuthController.php?action=login" method="POST">
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="alex.vance@techcorp.com" required>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <div class="form-label-row">
                        <label for="password" class="form-label">Password</label>
                        <a href="forgot_password.php" style="font-size: 0.7rem; color: #005a4e; font-weight: 700;">Forgot?</a>
                    </div>
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••••••" required>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-block" style="gap: 8px; margin-top: 10px;">
                    Continue to Dashboard
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" style="stroke: #003a31; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;">
                        <path d="M3 8h10M8 3l5 5-5 5"/>
                    </svg>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="auth-footer">
            <!-- Alternative Navigation -->
            <p style="margin-bottom: 25px; font-size: 0.95rem; color: #64748b; font-weight: 500;">
                Don't have an account? <a href="register.php" style="color: #005a4e; font-weight: 700; margin-left: 4px;">Register here</a>
            </p>
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
