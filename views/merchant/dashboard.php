<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Merchants allowed!
requireRole("merchant");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch Merchant Status
$stmt_m = $conn->prepare("SELECT status, commission_rate FROM merchants WHERE user_id = ?");
$stmt_m->bind_param("i", $user['id']);
$stmt_m->execute();
$merchantData = $stmt_m->get_result()->fetch_assoc();
$status = $merchantData['status'] ?? 'pending';
$rate = $merchantData['commission_rate'] ?? 0.05;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Store Manager - TQSEET</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1200px; margin: 60px auto; padding: 0 20px;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
            <div>
                <h1 style="margin: 0; font-size: 2.5rem; font-weight: 900; letter-spacing: -1.5px;">Store Manager</h1>
                <p style="color: #636e72; margin: 10px 0 0 0; font-weight: 500;">Welcome back, <strong><?php echo htmlspecialchars($user['name']); ?></strong>!</p>
            </div>
            <?php if ($status === 'approved'): ?>
                <a href="add_product.php" style="background: #222; color: white; padding: 15px 30px; text-decoration: none; border-radius: 15px; font-weight: bold; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">+ List New Product</a>
            <?php endif; ?>
        </div>

        <?php if ($status === 'approved'): ?>
            <div style="background: #fff; padding: 20px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); display: flex; gap: 30px; align-items: center; margin-bottom: 40px; border: 1px solid #f1f3f5;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 0.75rem; color: #b2bec3; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">Partner Agreement:</span>
                    <span style="background: #eafaf1; color: #27ae60; padding: 6px 14px; border-radius: 30px; font-size: 0.8rem; font-weight: bold;">
                        <?php echo ($rate * 100); ?>% Platform Fee
                    </span>
                </div>
                <div style="width: 1px; height: 20px; background: #eee;"></div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 0.75rem; color: #b2bec3; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">Store Status:</span>
                    <span style="color: #27ae60; font-size: 0.8rem; font-weight: bold;">✅ Verified & Active</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($status !== 'approved'): ?>
            <!-- Verification Pending Card -->
            <div style="background: white; border-radius: 25px; padding: 40px; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid #ffeeba; margin-bottom: 40px;">
                <div style="font-size: 3rem; margin-bottom: 20px;">⏳</div>
                <h2 style="margin: 0; font-weight: 900; color: #856404;">Application Under Review</h2>
                <p style="color: #636e72; max-width: 500px; margin: 15px auto; line-height: 1.6;">Our team is currently reviewing your merchant application. You will be able to list products and start selling as soon as your account is approved.</p>
                <div style="display: inline-block; background: #fff3cd; color: #856404; padding: 10px 20px; border-radius: 12px; font-weight: bold; font-size: 0.9rem;">
                    Status: <span style="text-transform: uppercase;"><?php echo $status; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px;">
            
            <!-- Inventory -->
            <div style="background: white; padding: 40px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: 0.3s; opacity: <?php echo $status === 'approved' ? '1' : '0.5'; ?>;">
                <h3 style="margin-top: 0; font-weight: 900; letter-spacing: -0.5px;">Inventory</h3>
                <p style="color: #636e72; line-height: 1.6; margin-bottom: 30px;">Track stock levels, update prices, and manage your live product listings.</p>
                <a href="<?php echo $status === 'approved' ? 'products.php' : '#'; ?>" 
                   style="display: block; background: #f1f3f5; color: #2d3436; padding: 15px; text-decoration: none; border-radius: 12px; font-weight: bold; text-align: center;">
                   Manage Catalog
                </a>
            </div>

            <!-- Sales -->
            <div style="background: white; padding: 40px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: 0.3s; opacity: <?php echo $status === 'approved' ? '1' : '0.5'; ?>;">
                <h3 style="margin-top: 0; font-weight: 900; letter-spacing: -0.5px;">Revenue</h3>
                <p style="color: #636e72; line-height: 1.6; margin-bottom: 30px;">Monitor your platform earnings, commissions, and transaction history.</p>
                <a href="<?php echo $status === 'approved' ? 'sales.php' : '#'; ?>" 
                   style="display: block; background: #f1f3f5; color: #2d3436; padding: 15px; text-decoration: none; border-radius: 12px; font-weight: bold; text-align: center;">
                   View Analytics
                </a>
            </div>

            <!-- Profile -->
            <div style="background: white; padding: 40px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: 0.3s;">
                <h3 style="margin-top: 0; font-weight: 900; letter-spacing: -0.5px;">Store Branding</h3>
                <p style="color: #636e72; line-height: 1.6; margin-bottom: 30px;">Customize your shop name, description, and contact information.</p>
                <a href="settings.php" 
                   style="display: block; background: #f1f3f5; color: #2d3436; padding: 15px; text-decoration: none; border-radius: 12px; font-weight: bold; text-align: center;">
                   Edit Profile
                </a>
            </div>

        </div>

    </div>

</body>
</html>
