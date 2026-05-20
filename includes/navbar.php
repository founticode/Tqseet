<?php
require_once __DIR__ . "/auth.php"; // Make sure we have access to helpers

$logoText = "TQSEET";
$logoLink = "/index.php";
if (isLoggedIn()) {
    $currUser = currentUser();
    if ($currUser['role'] === 'merchant') {
        $logoText = "TQSEET Business";
        $logoLink = "/views/merchant/dashboard.php";
    } elseif ($currUser['role'] === 'admin') {
        $logoText = "TQSEET Control Tower";
        $logoLink = "/views/admin/dashboard.php";
    }
}
?>
<!-- Centralized style sheet for the entire platform -->
<link rel="stylesheet" href="/assets/css/style.css">

<nav class="navbar">
    <div class="navbar-container">
        
        <!-- Logo / Brand (Dynamic Context based on Role) -->
        <a href="<?php echo $logoLink; ?>" class="navbar-brand">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
            <?php echo $logoText; ?>
        </a>

        <!-- Center Links (Aesthetic Cleanliness, role-segregated) -->
        <div class="navbar-links">
            <?php if (isLoggedIn()): ?>
                <?php $user = currentUser(); ?>
                
                <?php if ($user['role'] === 'merchant'): ?>
                    <!-- Merchant Portal Links -->
                    <a href="/views/merchant/dashboard.php" class="navbar-link">Dashboard</a>
                    <a href="/views/merchant/settings.php" class="navbar-link">Store Settings</a>
                    <a href="/views/public/business_docs.php" class="navbar-link">Integration Docs</a>
                    
                <?php elseif ($user['role'] === 'admin'): ?>
                    <!-- Admin Control Links -->
                    <a href="/views/admin/dashboard.php" class="navbar-link">Dashboard</a>
                    <a href="/views/admin/verifications.php" class="navbar-link">Verifications Hub</a>
                    
                <?php else: ?>
                    <!-- Regular Consumer Links -->
                    <a href="/index.php" class="navbar-link">Home</a>
                    <a href="/views/public/shop.php" class="navbar-link">Shop</a>
                    <a href="/views/user/orders.php" class="navbar-link">My Shopping</a>
                    <a href="/index.php#why-tqseet" class="navbar-link">How It Works</a>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Not Logged In Public Links -->
                <a href="/index.php" class="navbar-link">Home</a>
                <a href="/views/public/shop.php" class="navbar-link">Shop</a>
                <a href="/index.php#why-tqseet" class="navbar-link">How It Works</a>
                <a href="/views/public/business.php" class="navbar-link">For Business</a>
            <?php endif; ?>
        </div>

        <!-- Right Side: Session Actions (Sign In / Capsule Dropdown Menu) -->
        <div class="navbar-right">
            <?php if (isLoggedIn()): ?>
                <?php $user = currentUser(); ?>
                <div class="navbar-dropdown-wrapper">
                    <button class="navbar-user-capsule">
                        <span>👋 <?php echo htmlspecialchars(explode(" ", $user['name'])[0]); ?></span>
                        <span class="navbar-user-role-badge"><?php echo htmlspecialchars($user['role']); ?></span>
                        <span style="font-size: 0.65rem; margin-left: 2px; opacity: 0.7;">▼</span>
                    </button>
                    
                    <div class="navbar-dropdown-menu">
                        <a href="/views/<?php echo $user['role']; ?>/dashboard.php" class="navbar-dropdown-item">
                            <span>📊</span> Dashboard
                        </a>
                        
                        <?php if ($user['role'] === 'user'): ?>
                            <a href="/views/user/profile.php" class="navbar-dropdown-item">
                                <span>⚙️</span> Settings & KYC
                            </a>
                            <a href="/views/user/orders.php" class="navbar-dropdown-item">
                                <span>💳</span> My Installments
                            </a>
                        <?php elseif ($user['role'] === 'merchant'): ?>
                            <a href="/views/merchant/products.php" class="navbar-dropdown-item">
                                <span>📦</span> Catalog
                            </a>
                            <a href="/views/merchant/sales.php" class="navbar-dropdown-item">
                                <span>🛒</span> Orders
                            </a>
                            <a href="#" class="navbar-dropdown-item">
                                <span>🔗</span> Payment Links
                            </a>
                            <a href="#" class="navbar-dropdown-item">
                                <span>🏦</span> Settlements
                            </a>
                            <a href="/views/merchant/settings.php" class="navbar-dropdown-item">
                                <span>⚙️</span> Store Settings
                            </a>
                        <?php elseif ($user['role'] === 'admin'): ?>
                            <a href="/views/admin/verifications.php" class="navbar-dropdown-item">
                                <span>🛡️</span> Verifications Hub
                            </a>
                        <?php endif; ?>
                        
                        <div class="navbar-dropdown-divider"></div>
                        
                        <a href="/controllers/AuthController.php?action=logout" class="navbar-dropdown-item" style="color: #ef4444;">
                            <span>🚪</span> Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/views/auth/login.php" class="navbar-login-link">Login</a>
                <a href="/views/auth/register.php" class="navbar-signup-btn">Get Started</a>
            <?php endif; ?>
        </div>

    </div>
</nav>
