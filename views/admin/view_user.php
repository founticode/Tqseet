<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

$userId = $_GET['id'] ?? 0;
$db = new Database();
$conn = $db->connect();

// 1. Fetch User Basic Info
$stmt_u = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_u->bind_param("i", $userId);
$stmt_u->execute();
$user = $stmt_u->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// 2. Fetch Identity Verification Data
$stmt_v = $conn->prepare("SELECT * FROM user_verifications WHERE user_id = ?");
$stmt_v->bind_param("i", $userId);
$stmt_v->execute();
$verification = $stmt_v->get_result()->fetch_assoc();

// 3. Fetch Financial Data
$stmt_f = $conn->prepare("SELECT * FROM user_financials WHERE user_id = ?");
$stmt_f->bind_param("i", $userId);
$stmt_f->execute();
$financial = $stmt_f->get_result()->fetch_assoc();

// 4. NEW: Fetch Merchant Profile if they are a merchant
$merchant = null;
if ($user['role'] === 'merchant') {
    $stmt_m = $conn->prepare("SELECT * FROM merchants WHERE user_id = ?");
    $stmt_m->bind_param("i", $userId);
    $stmt_m->execute();
    $merchant = $stmt_m->get_result()->fetch_assoc();
}

// Check verification requirements
$hasCIN = ($verification !== null && !empty($verification['cin']));
$hasFinancial = ($financial !== null && !empty($financial['salary_proof']));

$isStrictMissing = false;
$warningMessage = "";

if ($user['role'] === 'merchant') {
    // Merchants require a CIN. Financials are optional.
    if (!$hasCIN) {
        $isStrictMissing = true;
        $warningMessage = "⚠️ Identity (CIN) Card is missing. (Bypass enabled for sandbox testing)";
    }
} else {
    // Customers (users) require both CIN and Financial papers.
    if (!$hasCIN || !$hasFinancial) {
        $isStrictMissing = true;
        if (!$hasCIN && !$hasFinancial) {
            $warningMessage = "⚠️ Identity (CIN) & Financial papers are missing. (Bypass enabled for sandbox testing)";
        } elseif (!$hasCIN) {
            $warningMessage = "⚠️ Identity (CIN) Card is missing. (Bypass enabled for sandbox testing)";
        } else {
            $warningMessage = "⚠️ Financial papers are missing. (Bypass enabled for sandbox testing)";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review User: <?php echo htmlspecialchars($user['name']); ?> - TQSEET</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 900px; margin: 60px auto; padding: 0 20px;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <div>
                <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">Review Profile</h1>
                <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Auditing documents for <strong><?php echo htmlspecialchars($user['name']); ?></strong> (ID: #<?php echo $userId; ?>)</p>
            </div>
            <a href="verifications.php" style="color: #636e72; text-decoration: none; font-weight: bold; font-size: 0.9rem;">← Back to List</a>
        </div>

        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
            
            <!-- Left Column: Documents -->
            <div>
                <!-- NEW: Merchant Profile Section -->
                <?php if ($merchant): ?>
                    <div style="background: white; border-radius: 25px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); margin-bottom: 30px; border: 1px solid #0984e3;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="margin: 0; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; color: #0984e3;">Business Profile</h3>
                            <span style="background: #fff4e6; color: #d9480f; padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase;"><?php echo $merchant['status']; ?></span>
                        </div>
                        <hr style="border: 0; border-top: 1px solid #f1f1f1; margin: 20px 0;">
                        
                        <div style="margin-bottom: 25px;">
                            <label style="display: block; color: #b2bec3; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">Store Name</label>
                            <div style="font-size: 1.2rem; font-weight: 800; color: #2d3436;"><?php echo htmlspecialchars($merchant['store_name']); ?></div>
                        </div>

                        <div style="margin-bottom: 25px;">
                            <label style="display: block; color: #b2bec3; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">Description</label>
                            <div style="color: #636e72; font-size: 0.9rem;"><?php echo nl2br(htmlspecialchars($merchant['description'] ?: 'No description provided.')); ?></div>
                        </div>

                        <div style="background: #f8f9fa; padding: 20px; border-radius: 15px; border: 1px dashed #0984e3;">
                            <label style="display: block; color: #0984e3; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; margin-bottom: 15px;">🤝 Partner Agreement</label>
                            
                            <form action="../../controllers/VerificationController.php?action=update_commission" method="POST" style="display: flex; align-items: flex-end; gap: 10px;">
                                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                <div style="flex: 1;">
                                    <label style="display: block; color: #636e72; font-size: 0.65rem; font-weight: bold; margin-bottom: 5px;">COMMISSION RATE (%)</label>
                                    <input type="number" name="commission_rate" step="0.01" min="0" max="1" 
                                           value="<?php echo $merchant['commission_rate']; ?>" 
                                           style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-weight: bold;">
                                </div>
                                <button type="submit" style="padding: 10px 20px; background: #0984e3; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">
                                    Save
                                </button>
                            </form>
                            <p style="margin: 10px 0 0 0; font-size: 0.7rem; color: #b2bec3;">Note: 0.05 = 5%. Currently: <strong><?php echo ($merchant['commission_rate'] * 100); ?>%</strong></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Identity Card Section -->
                <div style="background: white; border-radius: 25px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); margin-bottom: 30px; border: 1px solid rgba(0,0,0,0.02);">
                    <h3 style="margin-top: 0; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; color: #b2bec3;">Identity Document (CIN)</h3>
                    <hr style="border: 0; border-top: 1px solid #f1f1f1; margin: 20px 0;">
                    
                    <?php if ($verification): ?>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; color: #b2bec3; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">CIN Number</label>
                            <div style="font-size: 1.2rem; font-weight: 800; color: #2d3436; margin-top: 5px;"><?php echo htmlspecialchars($verification['cin']); ?></div>
                        </div>
                        <img src="../../uploads/verifications/<?php echo $verification['cin_image']; ?>" 
                             style="width: 100%; border-radius: 15px; border: 1px solid #f1f1f1; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                    <?php else: ?>
                        <p style="color: #d63031; font-style: italic;">No identity documents uploaded yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Financial Section -->
                <div style="background: white; border-radius: 25px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                    <h3 style="margin-top: 0; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; color: #b2bec3;">
                        <?php echo $user['role'] === 'merchant' ? 'Business Financials' : 'Financial Profile'; ?>
                    </h3>
                    <hr style="border: 0; border-top: 1px solid #f1f1f1; margin: 20px 0;">
                    
                    <?php if ($financial): ?>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <label style="display: block; color: #b2bec3; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                                    <?php echo $user['role'] === 'merchant' ? 'Business Structure' : 'Profession'; ?>
                                </label>
                                <div style="font-weight: 700; color: #2d3436;"><?php echo htmlspecialchars($financial['profession']); ?></div>
                            </div>
                            <div>
                                <label style="display: block; color: #b2bec3; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                                    <?php echo $user['role'] === 'merchant' ? 'Estimated Monthly Revenue' : 'Monthly Salary'; ?>
                                </label>
                                <div style="font-weight: 900; color: #00b894; font-size: 1.1rem;"><?php echo number_format($financial['salary'], 2); ?> DH</div>
                            </div>
                        </div>
                        
                        <?php if ($financial['salary_proof']): ?>
                            <div style="margin-top: 25px; padding: 20px; background: #fafafa; border-radius: 15px; border: 1px solid #eee;">
                                <label style="display: block; color: #b2bec3; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; margin-bottom: 10px;">Verification Document</label>
                                <a href="../../uploads/financials/<?php echo $financial['salary_proof']; ?>" target="_blank" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: #0984e3; font-weight: bold;">
                                    <span>
                                        <?php echo $user['role'] === 'merchant' ? '📄 View Business Bank Statement / Tax Document' : '📄 View Salary Slip / Bank Statement'; ?>
                                    </span>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="color: #d63031; font-style: italic;">No financial data submitted yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Decision Actions -->
            <div style="position: sticky; top: 20px;">
                <div style="background: #222; color: white; border-radius: 25px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.4);">Decision Center</h3>
                    <p style="font-size: 0.9rem; color: rgba(255,255,255,0.6); line-height: 1.6;">Carefully review the documents on the left. Once approved, the user will be able to apply for installment plans.</p>
                    
                    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 25px 0;">

                    <form action="../../controllers/VerificationController.php?action=decide" method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                        
                        <?php if ($user['role'] === 'merchant'): ?>
                            <div style="margin-bottom: 25px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: rgba(255,255,255,0.4); margin-bottom: 8px; letter-spacing: 1px;">Platform Commission (%)</label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="number" name="commission_rate" step="0.01" min="0" max="1" value="<?php echo $merchant['commission_rate'] ?: '0.05'; ?>" 
                                           style="flex: 1; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 8px; color: white; outline: none; font-weight: bold;">
                                    <span style="color: rgba(255,255,255,0.4); font-size: 0.8rem;">(0.05 = 5%)</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($user['role'] === 'user' && $financial): ?>
                            <div style="margin-bottom: 25px; background: rgba(0,184,148,0.1); padding: 20px; border-radius: 15px; border: 1px solid rgba(0,184,148,0.2);">
                                <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #00b894; margin-bottom: 8px; letter-spacing: 1px;">Calculated Buying Power</label>
                                <div style="font-size: 1.5rem; font-weight: 900; color: white;">
                                    <?php echo number_format($financial['salary'] * 1.5, 2); ?> <span style="font-size: 0.8rem; opacity: 0.5;">DH</span>
                                </div>
                                <p style="font-size: 0.7rem; color: rgba(255,255,255,0.4); margin: 8px 0 0 0;">Automated Limit: 1.5x Monthly Income</p>
                            </div>
                        <?php endif; ?>

                        <!-- Dynamic Verification Banners & Sandbox Bypass Info -->
                        <?php if ($isStrictMissing): ?>
                            <div style="margin-bottom: 25px; background: rgba(230, 126, 34, 0.1); padding: 18px; border-radius: 15px; border: 1px solid rgba(230, 126, 34, 0.3);">
                                <div style="font-size: 0.85rem; font-weight: bold; color: #e67e22; line-height: 1.5;">
                                    <?php echo $warningMessage; ?>
                                </div>
                            </div>
                        <?php elseif ($user['role'] === 'merchant' && !$hasFinancial): ?>
                            <div style="margin-bottom: 25px; background: rgba(9, 132, 227, 0.1); padding: 18px; border-radius: 15px; border: 1px solid rgba(9, 132, 227, 0.3);">
                                <div style="font-size: 0.85rem; font-weight: bold; color: #74b9ff; line-height: 1.5;">
                                    💡 Note: No financial documents submitted. (This is optional for Merchants - they can be approved with CIN only).
                                </div>
                            </div>
                        <?php endif; ?>

                        <div style="margin-bottom: 15px;">
                            <button type="submit" name="status" value="approved" 
                                    style="width: 100%; padding: 15px; background: #00b894; color: white; border: none; border-radius: 12px; font-weight: bold; font-size: 1rem; cursor: pointer; transition: 0.3s;">
                                ✅ <?php echo ($user['role'] === 'merchant') ? 'Approve Merchant' : 'Approve User'; ?>
                            </button>
                        </div>
                        
                        <button type="submit" name="status" value="rejected" 
                                style="width: 100%; padding: 15px; background: transparent; color: #ff7675; border: 1px solid #ff7675; border-radius: 12px; font-weight: bold; font-size: 1rem; cursor: pointer; transition: 0.3s;">
                            ❌ Reject Application
                        </button>
                    </form>
                </div>
            </div>

        </div>

    </div>

    <script>
    // Simple verification check to confirm before final actions
    document.querySelector('form').addEventListener('submit', function(e) {
        let action = e.submitter ? e.submitter.value : '';
        const isStrictMissing = <?php echo $isStrictMissing ? 'true' : 'false'; ?>;
        const role = '<?php echo $user['role']; ?>';

        if (action === 'approved') {
            if (isStrictMissing) {
                let warning = `⚠️ WARNING: This ${role === 'merchant' ? 'Merchant' : 'Customer'} has NOT uploaded all required verification documents.\n\n`;
                if (role === 'merchant') {
                    warning += "Approving them now will activate their store for testing without a government CIN card.\n\n";
                } else {
                    warning += "Approving them now will grant them a 0.00 DH credit limit since no salary slips exist.\n\n";
                }
                warning += "Are you sure you want to bypass validation and approve them anyway?";
                return confirm(warning);
            }
            return confirm("Are you sure you want to approve this profile?");
        } else if (action === 'rejected') {
            return confirm("Are you sure you want to reject this application?");
        }
    });
    </script>

</body>
</html>
