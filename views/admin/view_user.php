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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review User: <?php echo htmlspecialchars($user['name']); ?> - TQSEET Admin</title>
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
    <style>
        .review-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 32px; align-items: start; }
        .doc-section { background: white; border-radius: 20px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 32px; border: 1px solid #e5e7eb; }
        .doc-label { display: block; color: #6b7280; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        .doc-value { font-size: 1.15rem; font-weight: 800; color: #111827; }
        @media (max-width: 1024px) {
            .review-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Premium Admin Sidebar -->
    <?php include_once __DIR__ . "/../../includes/admin_sidebar.php"; ?>

    <main class="main-content">
        
        <div class="page-header" style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; padding: 24px 32px; background: white; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div>
                <h1 style="font-size: 2rem; font-weight: 900; margin: 0 0 8px 0; color: #111827; letter-spacing: -0.5px;">Review Profile</h1>
                <p style="color: #6b7280; margin: 0; font-size: 1rem; font-weight: 500;">Auditing documents for <strong style="color: #111827;"><?php echo htmlspecialchars($user['name']); ?></strong> (ID: #<?php echo $userId; ?>)</p>
            </div>
            <a href="verifications.php" class="btn-secondary" style="font-size: 0.9rem; padding: 12px 20px;">
                ← Back to List
            </a>
        </div>

        <div class="review-grid">
            
            <!-- Left Column: Documents -->
            <div>
                <!-- NEW: Merchant Profile Section -->
                <?php if ($merchant): ?>
                    <div class="doc-section" style="border: 1px solid #bfdbfe;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #2563eb;">Business Profile</h3>
                            <span style="background: #eff6ff; color: #1d4ed8; padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo $merchant['status']; ?></span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr; gap: 24px;">
                            <div>
                                <label class="doc-label">Store Name</label>
                                <div class="doc-value"><?php echo htmlspecialchars($merchant['store_name']); ?></div>
                            </div>

                            <div>
                                <label class="doc-label">Description</label>
                                <div style="color: #4b5563; font-size: 0.95rem; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($merchant['description'] ?: 'No description provided.')); ?></div>
                            </div>
                        </div>

                        <div style="margin-top: 32px; background: #f8fafc; padding: 24px; border-radius: 16px; border: 1px dashed #93c5fd;">
                            <label class="doc-label" style="color: #2563eb; margin-bottom: 16px;">🤝 Partner Agreement</label>
                            
                            <form action="../../controllers/VerificationController.php?action=update_commission" method="POST" style="display: flex; align-items: flex-end; gap: 16px;">
                                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                <div style="flex: 1;">
                                    <label style="display: block; color: #6b7280; font-size: 0.7rem; font-weight: 800; margin-bottom: 8px;">COMMISSION RATE (%)</label>
                                    <input type="number" name="commission_rate" step="0.01" min="0" max="1" 
                                           value="<?php echo $merchant['commission_rate']; ?>" 
                                           style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #d1d5db; outline: none; font-weight: 800; font-size: 1rem;">
                                </div>
                                <button type="submit" style="padding: 12px 24px; background: #2563eb; color: white; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; transition: background 0.2s;">
                                    Save
                                </button>
                            </form>
                            <p style="margin: 12px 0 0 0; font-size: 0.8rem; color: #6b7280; font-weight: 500;">Note: 0.05 = 5%. Currently: <strong style="color: #111827;"><?php echo ($merchant['commission_rate'] * 100); ?>%</strong></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Identity Card Section -->
                <div class="doc-section">
                    <h3 style="margin: 0 0 24px 0; font-size: 1.1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af;">Identity Document (CIN)</h3>
                    
                    <?php if ($verification): ?>
                        <div style="margin-bottom: 24px;">
                            <label class="doc-label">CIN Number</label>
                            <div class="doc-value" style="font-size: 1.4rem; font-family: monospace; letter-spacing: 2px; color: #111827;"><?php echo htmlspecialchars($verification['cin']); ?></div>
                        </div>
                        <div style="background: #f9fafb; padding: 16px; border-radius: 16px; border: 1px solid #e5e7eb;">
                            <img src="../../uploads/verifications/<?php echo $verification['cin_image']; ?>" 
                                 style="width: 100%; border-radius: 12px; display: block;" onerror="this.src='https://via.placeholder.com/600x300?text=No+Image'">
                        </div>
                    <?php else: ?>
                        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 12px; font-weight: 600; font-size: 0.9rem;">
                            No identity documents uploaded yet.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Financial Section -->
                <div class="doc-section">
                    <h3 style="margin: 0 0 24px 0; font-size: 1.1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af;">
                        <?php echo $user['role'] === 'merchant' ? 'Business Financials' : 'Financial Profile'; ?>
                    </h3>
                    
                    <?php if ($financial): ?>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                            <div>
                                <label class="doc-label">
                                    <?php echo $user['role'] === 'merchant' ? 'Business Structure' : 'Profession'; ?>
                                </label>
                                <div class="doc-value"><?php echo htmlspecialchars($financial['profession']); ?></div>
                            </div>
                            <div>
                                <label class="doc-label">
                                    <?php echo $user['role'] === 'merchant' ? 'Estimated Monthly Revenue' : 'Monthly Salary'; ?>
                                </label>
                                <div class="doc-value" style="color: #10b981; font-size: 1.3rem;"><?php echo number_format($financial['salary'], 2); ?> DH</div>
                            </div>
                        </div>
                        
                        <?php if ($financial['salary_proof']): ?>
                            <div style="padding: 20px; background: #f8fafc; border-radius: 16px; border: 1px solid #e2e8f0;">
                                <label class="doc-label" style="margin-bottom: 12px;">Verification Document</label>
                                <a href="../../uploads/financials/<?php echo $financial['salary_proof']; ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 10px; text-decoration: none; color: #3b82f6; font-weight: 800; background: white; padding: 12px 20px; border-radius: 10px; border: 1px solid #bfdbfe; transition: all 0.2s;">
                                    <span>📄</span>
                                    <span><?php echo $user['role'] === 'merchant' ? 'View Business Bank Statement / Tax Document' : 'View Salary Slip / Bank Statement'; ?></span>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 12px; font-weight: 600; font-size: 0.9rem;">
                            No financial data submitted yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Decision Actions -->
            <div style="position: sticky; top: 24px;">
                <div style="background: #111827; color: white; border-radius: 24px; padding: 32px; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                    <h3 style="margin: 0 0 12px 0; font-size: 1.2rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af;">Decision Center</h3>
                    <p style="font-size: 0.95rem; color: #d1d5db; line-height: 1.6; margin: 0 0 32px 0;">Carefully review the documents on the left. Once approved, the user will be able to apply for installment plans.</p>

                    <form action="../../controllers/VerificationController.php?action=decide" method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                        
                        <?php if ($user['role'] === 'merchant'): ?>
                            <div style="margin-bottom: 32px; background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #9ca3af; margin-bottom: 12px; letter-spacing: 1px;">Platform Commission (%)</label>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <input type="number" name="commission_rate" step="0.01" min="0" max="1" value="<?php echo $merchant['commission_rate'] ?: '0.05'; ?>" 
                                           style="flex: 1; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); padding: 14px; border-radius: 12px; color: white; outline: none; font-weight: 800; font-size: 1.1rem;">
                                    <span style="color: #9ca3af; font-size: 0.85rem; font-weight: 600;">(0.05 = 5%)</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($user['role'] === 'user' && $financial): ?>
                            <div style="margin-bottom: 32px; background: rgba(16, 185, 129, 0.1); padding: 24px; border-radius: 16px; border: 1px solid rgba(16, 185, 129, 0.2);">
                                <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #10b981; margin-bottom: 8px; letter-spacing: 1px;">Calculated Buying Power</label>
                                <div style="font-size: 1.8rem; font-weight: 900; color: white;">
                                    <?php echo number_format($financial['salary'] * 1.5, 2); ?> <span style="font-size: 1rem; color: #9ca3af;">DH</span>
                                </div>
                                <p style="font-size: 0.8rem; color: #6ee7b7; margin: 8px 0 0 0; font-weight: 500;">Automated Limit: 1.5x Monthly Income</p>
                            </div>
                        <?php endif; ?>

                        <!-- Dynamic Verification Banners & Sandbox Bypass Info -->
                        <?php if ($isStrictMissing): ?>
                            <div style="margin-bottom: 32px; background: rgba(245, 158, 11, 0.1); padding: 20px; border-radius: 16px; border: 1px solid rgba(245, 158, 11, 0.3);">
                                <div style="font-size: 0.9rem; font-weight: 700; color: #fbbf24; line-height: 1.5;">
                                    <?php echo $warningMessage; ?>
                                </div>
                            </div>
                        <?php elseif ($user['role'] === 'merchant' && !$hasFinancial): ?>
                            <div style="margin-bottom: 32px; background: rgba(59, 130, 246, 0.1); padding: 20px; border-radius: 16px; border: 1px solid rgba(59, 130, 246, 0.3);">
                                <div style="font-size: 0.9rem; font-weight: 700; color: #60a5fa; line-height: 1.5;">
                                    💡 Note: No financial documents submitted. (This is optional for Merchants - they can be approved with CIN only).
                                </div>
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <button type="submit" name="status" value="approved" 
                                    style="width: 100%; padding: 18px; background: #10b981; color: white; border: none; border-radius: 16px; font-weight: 800; font-size: 1.05rem; cursor: pointer; transition: background 0.2s;">
                                ✅ <?php echo ($user['role'] === 'merchant') ? 'Approve Merchant' : 'Approve User'; ?>
                            </button>
                            
                            <button type="submit" name="status" value="rejected" 
                                    style="width: 100%; padding: 18px; background: transparent; color: #ef4444; border: 1px solid #ef4444; border-radius: 16px; font-weight: 800; font-size: 1.05rem; cursor: pointer; transition: background 0.2s;">
                                ❌ Reject Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </main>

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
