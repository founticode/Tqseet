<?php
require_once __DIR__ . "/auth.php"; // Make sure we have access to helpers

// Define the base path of the project (Leave empty if project is at root)
$base = ""; 
?>

<nav style="background: #f4f4f4; padding: 10px; margin-bottom: 20px; border-bottom: 1px solid #ccc;">
    <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: auto;">
        
        <!-- Logo / Brand -->
        <div>
            <a href="<?php echo $base; ?>/index.php" style="font-weight: bold; font-size: 1.2rem; text-decoration: none; color: #333;">TQSEET</a>
        </div>

        <!-- Links -->
        <div>
            <a href="<?php echo $base; ?>/index.php" style="margin-right: 15px; text-decoration: none; color: #555;">Home</a>
            <a href="<?php echo $base; ?>/views/public/catalog.php" style="margin-right: 15px; text-decoration: none; color: #555;">Shop Catalog</a>

            <?php if (isLoggedIn()): ?>
                <?php $user = currentUser(); ?>
                
                <!-- Merchant Specific Links -->
                <?php if ($user['role'] === 'merchant'): ?>
                    <a href="<?php echo $base; ?>/views/merchant/products.php" style="margin-right: 15px; text-decoration: none; color: #555;">My Inventory</a>
                    <a href="<?php echo $base; ?>/views/merchant/sales.php" style="margin-right: 15px; text-decoration: none; color: #555;">My Sales</a>
                    <a href="<?php echo $base; ?>/views/merchant/settings.php" style="margin-right: 15px; text-decoration: none; color: #555;">Store Settings</a>
                <?php endif; ?>

                <!-- User Specific Links -->
                <?php if ($user['role'] === 'user'): ?>
                    <a href="<?php echo $base; ?>/views/user/orders.php" style="margin-right: 15px; text-decoration: none; color: #555;">My Shopping</a>
                <?php endif; ?>

                <!-- Admin Specific Links -->
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?php echo $base; ?>/views/admin/verifications.php" style="margin-right: 15px; text-decoration: none; color: #856404;">Verifications</a>
                <?php endif; ?>

                <a href="<?php echo $base; ?>/views/user/profile.php" style="margin-right: 15px; text-decoration: none; color: #636e72;">
                    👋 <?php echo htmlspecialchars($user['name']); ?>
                </a>

                <a href="<?php echo $base; ?>/views/<?php echo $user['role']; ?>/dashboard.php" style="margin-right: 15px; text-decoration: none; color: #333; font-weight: bold;">Dashboard</a>
                <a href="<?php echo $base; ?>/controllers/AuthController.php?action=logout" style="text-decoration: none; color: #d9534f; font-weight: bold;">Logout</a>
            <?php else: ?>
                <a href="<?php echo $base; ?>/views/auth/login.php" style="margin-right: 15px; text-decoration: none; color: #555;">Login</a>
                <a href="<?php echo $base; ?>/views/auth/register.php" style="text-decoration: none; background: #007bff; color: white; padding: 5px 10px; border-radius: 4px;">Register</a>
            <?php endif; ?>
        </div>

    </div>
</nav>
