<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY "user" role allowed
requireRole("user");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch Credit Info
$stmt_f = $conn->prepare("SELECT * FROM user_financials WHERE user_id = ?");
$stmt_f->bind_param("i", $user['id']);
$stmt_f->execute();
$financial = $stmt_f->get_result()->fetch_assoc();

// Fetch Identity Info
$stmt_i = $conn->prepare("SELECT * FROM user_verifications WHERE user_id = ?");
$stmt_i->bind_param("i", $user['id']);
$stmt_i->execute();
$identity = $stmt_i->get_result()->fetch_assoc();

// Fetch Real-time Verification Status
$stmt_u = $conn->prepare("SELECT is_verified FROM users WHERE id = ?");
$stmt_u->bind_param("i", $user['id']);
$stmt_u->execute();
$userData = $stmt_u->get_result()->fetch_assoc();
$isVerified = $userData['is_verified'] ?? 0;
$stmt_u->close();

$status = $financial['status'] ?? 'none';
$maxLimit = $financial['credit_limit'] ?? 0;
$id_status = $identity['status'] ?? 'none';

// --- NEW: Calculate Available Credit (Limit - Unpaid installments) ---
$stmt_debt = $conn->prepare("
    SELECT SUM(amount) as debt 
    FROM installments i 
    JOIN orders o ON i.order_id = o.id 
    WHERE o.user_id = ? AND i.status = 'unpaid'
");
$stmt_debt->bind_param("i", $user['id']);
$stmt_debt->execute();
$debtResult = $stmt_debt->get_result()->fetch_assoc();
$totalDebt = $debtResult['debt'] ?? 0;

$availableLimit = $maxLimit - $totalDebt;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TQSEET</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1200px; margin: 60px auto; padding: 0 20px;">
        
        <div style="margin-bottom: 40px;">
            <h1 style="margin: 0; font-size: 2.5rem; font-weight: 900; letter-spacing: -1.5px;">My Dashboard</h1>
            <p style="color: #636e72; margin: 10px 0 0 0; font-weight: 500;">Welcome back, <strong><?php echo htmlspecialchars($user['name']); ?></strong>!</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px;">
            
            <!-- Credit Card Section -->
            <div style="background: #222; color: white; padding: 40px; border-radius: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); position: relative; overflow: hidden;">
                <div style="position: absolute; width: 300px; height: 300px; background: rgba(255,255,255,0.03); border-radius: 50%; top: -150px; right: -100px;"></div>
                
                <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; opacity: 0.5; margin-bottom: 10px;">Available Credit Limit</div>
                <div style="font-size: 2.5rem; font-weight: 900; margin-bottom: 30px;"><?php echo number_format($availableLimit, 2); ?> <span style="font-size: 1rem; opacity: 0.5;">DH</span></div>
                
                <div style="display: flex; gap: 40px; margin-bottom: 40px;">
                    <div>
                        <div style="font-size: 0.6rem; opacity: 0.5; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 5px;">Status</div>
                        <div style="font-weight: bold; font-size: 0.9rem;">
                            <?php if ($status === 'approved'): ?>
                                ✅ Approved
                            <?php elseif ($status === 'pending'): ?>
                                ⏳ Under Review
                            <?php else: ?>
                                ❌ Incomplete
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.6rem; opacity: 0.5; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 5px;">Member Since</div>
                        <div style="font-weight: bold; font-size: 0.9rem;"><?php echo isset($user['created_at']) ? date('Y', strtotime($user['created_at'])) : '2024'; ?></div>
                    </div>
                </div>

                <a href="settings.php#verification-section" style="display: block; background: rgba(255,255,255,0.1); color: white; padding: 15px; text-decoration: none; border-radius: 12px; font-weight: bold; text-align: center; border: 1px solid rgba(255,255,255,0.1);">
                    <?php echo ($status === 'none') ? 'Setup Credit Profile' : 'Update Info'; ?>
                </a>
            </div>

            <!-- Profile Info Section -->
            <div style="background: white; padding: 40px; border-radius: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                <h3 style="margin-top: 0; font-weight: 900; letter-spacing: -0.5px;">Account Details</h3>
                <hr style="border: 0; border-top: 1px solid #f1f3f5; margin: 25px 0;">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div>
                        <label style="display: block; color: #b2bec3; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; margin-bottom: 5px;">Full Name</label>
                        <div style="font-weight: 700;"><?php echo htmlspecialchars($user['name']); ?></div>
                    </div>
                    <div>
                        <label style="display: block; color: #b2bec3; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; margin-bottom: 5px;">Email Address</label>
                        <div style="font-weight: 700;"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div>
                        <label style="display: block; color: #b2bec3; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; margin-bottom: 5px;">Phone Number</label>
                        <div style="font-weight: 700;"><?php echo htmlspecialchars($user['phone']); ?></div>
                    </div>
                     <div>
                        <label style="display: block; color: #b2bec3; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; margin-bottom: 5px;">OTP Verified</label>
                        <div style="font-weight: 700; color: <?php echo ($isVerified == 1) ? '#00b894' : '#e74c3c'; ?>;">
                            <?php if ($isVerified == 1): ?>
                                ✅ Yes
                            <?php else: ?>
                                ❌ No <a href="../auth/verify_otp.php" style="color: #0984e3; text-decoration: none; font-size: 0.8rem; margin-left: 5px; font-weight: bold;">(Verify Now)</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <!-- Verification Checklist -->
                <h3 style="margin: 30px 0 15px 0; font-weight: 900; letter-spacing: -0.5px;">Verification Status</h3>
                <div style="display: flex; gap: 15px;">
                    <a href="settings.php#verification-section" style="flex: 1; text-decoration: none; background: #fafafa; border: 1px solid #eee; padding: 15px; border-radius: 15px; display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-weight: bold; font-size: 0.85rem; color: #2d3436;">Identity (CIN)</span>
                        <span style="font-size: 0.7rem; font-weight: 900; padding: 4px 10px; border-radius: 10px; text-transform: uppercase; 
                              <?php echo ($id_status === 'approved') ? 'background: #eafaf1; color: #27ae60;' : 'background: #fff4e6; color: #d9480f;'; ?>">
                            <?php echo ($id_status === 'approved') ? 'Verified' : 'Required'; ?>
                        </span>
                    </a>
                    <a href="settings.php#verification-section" style="flex: 1; text-decoration: none; background: #fafafa; border: 1px solid #eee; padding: 15px; border-radius: 15px; display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-weight: bold; font-size: 0.85rem; color: #2d3436;">Income Proof</span>
                        <span style="font-size: 0.7rem; font-weight: 900; padding: 4px 10px; border-radius: 10px; text-transform: uppercase; 
                              <?php echo ($status === 'approved') ? 'background: #eafaf1; color: #27ae60;' : 'background: #fff4e6; color: #d9480f;'; ?>">
                            <?php echo ($status === 'approved') ? 'Verified' : 'Required'; ?>
                        </span>
                    </a>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
