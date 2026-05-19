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

// Fetch validation status for merchant checklist
$stmt_v = $conn->prepare("SELECT * FROM user_verifications WHERE user_id = ?");
$stmt_v->bind_param("i", $user['id']);
$stmt_v->execute();
$verification = $stmt_v->get_result()->fetch_assoc();
$hasCIN = ($verification !== null && !empty($verification['cin']));

$stmt_f = $conn->prepare("SELECT * FROM user_financials WHERE user_id = ?");
$stmt_f->bind_param("i", $user['id']);
$stmt_f->execute();
$financial = $stmt_f->get_result()->fetch_assoc();
$hasFinancial = ($financial !== null && !empty($financial['salary_proof']));
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
            <!-- Verification Pending Card (Strict Onboarding Checklist) -->
            <div style="background: white; border-radius: 25px; padding: 40px; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid #ffeeba; margin-bottom: 40px;">
                <div style="font-size: 3rem; margin-bottom: 20px;">⏳</div>
                <h2 style="margin: 0; font-weight: 900; color: #856404; letter-spacing: -0.5px;">Store Pending Activation</h2>
                <p style="color: #636e72; max-width: 500px; margin: 15px auto; line-height: 1.6; font-size: 0.95rem;">To activate your store and begin listing products, you must complete your business verification documents below.</p>
                
                <!-- Onboarding Checklist -->
                <div style="max-width: 450px; margin: 30px auto; text-align: left; background: #fafafa; border-radius: 20px; padding: 25px; border: 1px dashed #ffeeba;">
                    <h4 style="margin: 0 0 15px 0; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; color: #856404; font-weight: 800;">Verification Checklist</h4>
                    
                    <!-- 1. CIN Card Check -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f1f3f5;">
                        <div>
                            <div style="font-weight: bold; font-size: 0.9rem; color: #2d3436;">Identity Card (CIN) <span style="color: #d63031; font-size: 0.7rem; font-weight: 900; text-transform: uppercase;">[Required]</span></div>
                            <div style="font-size: 0.75rem; color: #b2bec3;">Required for official government verification.</div>
                        </div>
                        <?php if ($hasCIN): ?>
                            <span style="background: #e3faf2; color: #087f5b; padding: 5px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: bold;">✅ Uploaded</span>
                        <?php else: ?>
                            <a href="settings.php" style="background: #ff7675; color: white; text-decoration: none; padding: 6px 14px; border-radius: 8px; font-size: 0.75rem; font-weight: bold;">Upload Now</a>
                        <?php endif; ?>
                    </div>

                    <!-- 2. Financial Info Check -->
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: bold; font-size: 0.9rem; color: #2d3436;">Financial Statements <span style="color: #74b9ff; font-size: 0.7rem; font-weight: 900; text-transform: uppercase;">[Optional]</span></div>
                            <div style="font-size: 0.75rem; color: #b2bec3;">Highly recommended to speed up trust & payout limits.</div>
                        </div>
                        <?php if ($hasFinancial): ?>
                            <span style="background: #e3faf2; color: #087f5b; padding: 5px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: bold;">✅ Uploaded</span>
                        <?php else: ?>
                            <a href="settings.php" style="background: #f1f3f5; color: #636e72; text-decoration: none; padding: 6px 14px; border-radius: 8px; font-size: 0.75rem; font-weight: bold;">Upload Info</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display: inline-block; background: #fff3cd; color: #856404; padding: 10px 20px; border-radius: 12px; font-weight: bold; font-size: 0.9rem;">
                    Store Review Status: <span style="text-transform: uppercase;"><?php echo htmlspecialchars($status); ?></span>
                </div>
            </div>
        <?php else: ?>
            <!-- Verification Center (Rendered beautifully for APPROVED active merchants!) -->
            <div style="background: white; border-radius: 25px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid #f1f3f5; margin-bottom: 40px;">
                <h3 style="margin-top: 0; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; color: #2d3436; font-weight: 900;">📋 Store Credentials Checklist</h3>
                <p style="color: #636e72; font-size: 0.85rem; margin-top: 5px; margin-bottom: 25px; font-weight: 500;">Review your active government verification papers and optional credentials.</p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    
                    <!-- 1. CIN Card Check (Approved) -->
                    <div style="background: #fafafa; padding: 20px; border-radius: 15px; border: 1px solid #f1f3f5; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 800; font-size: 0.9rem; color: #2d3436;">Identity Card (CIN) <span style="color: #00b894; font-size: 0.65rem; font-weight: 900; text-transform: uppercase;">[Required]</span></div>
                            <div style="font-size: 0.75rem; color: #b2bec3; margin-top: 2px;">Official government identification.</div>
                        </div>
                        <span style="background: #e3faf2; color: #087f5b; padding: 5px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid #c3fae8;">✅ Verified & Active</span>
                    </div>

                    <!-- 2. Financial Info Check (Approved) -->
                    <div style="background: #fafafa; padding: 20px; border-radius: 15px; border: 1px solid #f1f3f5; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 800; font-size: 0.9rem; color: #2d3436;">Financial Statements <span style="color: #74b9ff; font-size: 0.65rem; font-weight: 900; text-transform: uppercase;">[Optional]</span></div>
                            <div style="font-size: 0.75rem; color: #b2bec3; margin-top: 2px;">Used to expedite trust and payouts.</div>
                        </div>
                        <?php if ($hasFinancial): ?>
                            <span style="background: #e3faf2; color: #087f5b; padding: 5px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid #c3fae8;">✅ Uploaded</span>
                        <?php else: ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="background: #f1f3f5; color: #636e72; padding: 5px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: bold;">Not Uploaded</span>
                                <a href="settings.php" style="color: #0984e3; font-weight: bold; font-size: 0.75rem; text-decoration: underline;">Upload Info</a>
                            </div>
                        <?php endif; ?>
                    </div>

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
