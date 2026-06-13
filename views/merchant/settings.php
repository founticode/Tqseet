<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

requireRole("merchant");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch Merchant Data
$merchant = ensureMerchantRecord($conn);

// Fetch Verification data
$stmt_v = $conn->prepare("SELECT * FROM user_verifications WHERE user_id = ?");
$stmt_v->bind_param("i", $user['id']);
$stmt_v->execute();
$verification = $stmt_v->get_result()->fetch_assoc();
$hasCIN = ($verification !== null && !empty($verification['cin']));
$cinApproved = ($verification !== null && $verification['status'] === 'approved');

// Fetch Financial data
$stmt_f = $conn->prepare("SELECT * FROM user_financials WHERE user_id = ?");
$stmt_f->bind_param("i", $user['id']);
$stmt_f->execute();
$financial = $stmt_f->get_result()->fetch_assoc();
$hasFinancial = ($financial !== null && !empty($financial['salary_proof']));
$financialApproved = ($financial !== null && $financial['status'] === 'approved');

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $storeName = $_POST['store_name'];
        $description = $_POST['description'];
        
        $stmt_upd = $conn->prepare("UPDATE merchants SET store_name = ?, description = ? WHERE user_id = ?");
        $stmt_upd->bind_param("ssi", $storeName, $description, $user['id']);
        
        if ($stmt_upd->execute()) {
            $message = "Store profile updated successfully!";
            $merchant = ensureMerchantRecord($conn);
        }
    }
    
    elseif ($action === 'upload_cin') {
        if ($cinApproved) {
            $error = "Action Denied: Verified identity documents are sealed.";
        } else {
            $cin = $_POST['cin'];
            $cin_image = $_FILES['cin_image']['name'] ?? '';
            
            if ($cin_image) {
                $target = __DIR__ . "/../../uploads/verifications/" . basename($cin_image);
                if (!is_dir(dirname($target))) {
                    mkdir(dirname($target), 0777, true);
                }
                move_uploaded_file($_FILES['cin_image']['tmp_name'], $target);
            } else {
                $cin_image = $verification['cin_image'] ?? '';
            }
            
            if ($verification) {
                $stmt_upd = $conn->prepare("UPDATE user_verifications SET cin = ?, cin_image = ?, status = 'pending' WHERE user_id = ?");
                $stmt_upd->bind_param("ssi", $cin, $cin_image, $user['id']);
                $stmt_upd->execute();
            } else {
                $stmt_ins = $conn->prepare("INSERT INTO user_verifications (user_id, cin, cin_image, status) VALUES (?, ?, ?, 'pending')");
                $stmt_ins->bind_param("iss", $user['id'], $cin, $cin_image);
                $stmt_ins->execute();
            }
            $message = "Identity (CIN) document submitted successfully for review!";
            $stmt_v->execute();
            $verification = $stmt_v->get_result()->fetch_assoc();
            $hasCIN = true;
            $cinApproved = false;
        }
    }
    
    elseif ($action === 'upload_financials') {
        if ($financialApproved) {
            $error = "Action Denied: Verified financials are sealed.";
        } else {
            $profession = $_POST['profession'];
            $salary = $_POST['salary'];
            $salary_proof = $_FILES['salary_proof']['name'] ?? '';
            
            if ($salary_proof) {
                $target = __DIR__ . "/../../uploads/financials/" . basename($salary_proof);
                if (!is_dir(dirname($target))) {
                    mkdir(dirname($target), 0777, true);
                }
                move_uploaded_file($_FILES['salary_proof']['tmp_name'], $target);
            } else {
                $salary_proof = $financial['salary_proof'] ?? '';
            }
            
            if ($financial) {
                $stmt_upd = $conn->prepare("UPDATE user_financials SET profession = ?, salary = ?, salary_proof = ?, status = 'pending' WHERE user_id = ?");
                $stmt_upd->bind_param("sdsi", $profession, $salary, $salary_proof, $user['id']);
                $stmt_upd->execute();
            } else {
                $stmt_ins = $conn->prepare("INSERT INTO user_financials (user_id, profession, salary, salary_proof, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt_ins->bind_param("isds", $user['id'], $profession, $salary, $salary_proof);
                $stmt_ins->execute();
            }
            $message = "Business Financials submitted successfully for review!";
            $stmt_f->execute();
            $financial = $stmt_f->get_result()->fetch_assoc();
            $hasFinancial = true;
            $financialApproved = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Settings - TQSEET</title>
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
    <style>
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px; letter-spacing: 0.05em; }
        .form-input { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.95rem; outline: none; transition: 0.2s; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        .form-input:focus { border-color: var(--primary-black); box-shadow: 0 0 0 3px rgba(17,24,39,0.1); }
        .alert { padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; font-size: 0.9rem; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        
        .settings-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
        @media (max-width: 900px) { .settings-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include_once __DIR__ . "/../../includes/merchant_sidebar.php"; ?>

    <!-- Main Content -->
    <main class="main-content">
        
        <header class="page-header">
            <h1>Store Settings</h1>
            <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-muted);">
                Manage your profile, compliance, and security.
            </div>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="settings-grid">
            
            <!-- Left: Store Form -->
            <div class="card">
                <h2 class="card-title" style="margin-bottom: 24px;">Store Identity</h2>
                
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label class="form-label">Public Store Name</label>
                        <input type="text" name="store_name" class="form-input" value="<?php echo htmlspecialchars($merchant['store_name'] ?? 'My Store'); ?>" required placeholder="e.g. Zara Morocco">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Store Description</label>
                        <textarea name="description" class="form-input" rows="4" required placeholder="Describe your store..." style="resize: none;"><?php echo htmlspecialchars($merchant['description'] ?? ''); ?></textarea>
                        <small style="color: var(--text-muted); margin-top: 6px; display: block; font-size: 0.8rem;">This will be visible on your checkout pages.</small>
                    </div>

                    <button type="submit" class="btn-black" style="padding: 12px 24px;">
                        Save Profile
                    </button>
                </form>
            </div>

            <!-- Right: Account Info & Security -->
            <div>
                <div class="card" style="margin-bottom: 24px; border: 1px solid var(--border-color); box-shadow: none;">
                    <h2 class="card-title" style="font-size: 1rem; margin-bottom: 16px;">Partner Agreement</h2>
                    
                    <div class="checklist-row" style="padding: 12px 0;">
                        <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted);">Commission Rate</span>
                        <span style="font-size: 0.85rem; font-weight: 700; color: var(--primary-black); background: #f3f4f6; padding: 4px 10px; border-radius: 8px;">
                            <?php echo (($merchant['commission_rate'] ?? 0.05) * 100); ?>%
                        </span>
                    </div>
                    <div class="checklist-row" style="padding: 12px 0; border-bottom: none;">
                        <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted);">Status</span>
                        <?php 
                            $statusClass = 'pending';
                            if (($merchant['status'] ?? 'pending') === 'approved') $statusClass = 'success';
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo ucfirst($merchant['status'] ?? 'pending'); ?>
                        </span>
                    </div>
                </div>

                <div class="card" style="background: var(--primary-black); color: white;">
                    <h2 class="card-title" style="font-size: 1rem; margin-bottom: 12px;">Security</h2>
                    <p style="font-size: 0.85rem; color: #9ca3af; line-height: 1.5; margin-bottom: 20px;">Protect your credentials and update contact details.</p>
                    <a href="../user/update_password.php" style="display: block; background: rgba(255,255,255,0.1); color: white; text-align: center; padding: 10px; text-decoration: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; transition: background 0.2s; margin-bottom: 12px;">
                        Change Password
                    </a>
                    <a href="../user/settings.php" style="display: block; background: rgba(16,185,129,0.1); color: #34d399; text-align: center; padding: 10px; text-decoration: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; transition: background 0.2s;">
                        Two-Factor / Email Settings
                    </a>
                </div>
            </div>

        </div>

        <!-- Section: Business Verification & Credentials -->
        <div class="card" style="margin-top: 24px;">
            <h2 class="card-title">Business Verification</h2>
            <p class="card-desc">Submit compliance credentials to activate store trust features and unlock live checkouts.</p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-top: 24px;">
                
                <!-- Left Column: Identity Card (CIN) -->
                <div>
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 700; margin: 0 0 20px 0; display: flex; align-items: center; gap: 8px;">
                        <span>🪪</span> Identity Verification (CIN)
                    </h3>
                    
                    <?php if ($cinApproved): ?>
                        <div class="alert alert-success">
                            <div style="font-weight: 700; margin-bottom: 4px;">✅ ID Verified & Sealed</div>
                            <div style="font-size: 0.85rem; font-weight: 500;">CIN: <?php echo htmlspecialchars($verification['cin']); ?></div>
                        </div>
                        <?php if (!empty($verification['cin_image'])): ?>
                            <img src="../../uploads/verifications/<?php echo $verification['cin_image']; ?>" style="max-width: 100%; border-radius: 8px; margin-top: 12px; border: 1px solid var(--border-color);">
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($verification && $verification['status'] === 'pending'): ?>
                            <div class="alert alert-pending">
                                ⏳ Review in progress: Identity document submitted.
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_cin">
                            <div class="form-group">
                                <label class="form-label">CIN Number</label>
                                <input type="text" name="cin" class="form-input" required value="<?php echo htmlspecialchars($verification['cin'] ?? ''); ?>" placeholder="e.g. AB123456">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Upload CIN Document (Photo/PDF)</label>
                                <input type="file" name="cin_image" <?php echo $verification ? '' : 'required'; ?> style="font-size: 0.9rem; color: var(--text-muted); width: 100%; padding: 8px 0;">
                                <?php if ($verification && $verification['cin_image']): ?>
                                    <small style="display: block; margin-top: 6px; color: #3b82f6; font-weight: 600;">📄 Current: <?php echo htmlspecialchars($verification['cin_image']); ?></small>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn-black">
                                Submit Identity Card
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Business Financials -->
                <div style="border-left: 1px solid var(--border-color); padding-left: 32px;">
                    <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 700; margin: 0 0 20px 0; display: flex; align-items: center; gap: 8px;">
                        <span>📈</span> Business Financials
                    </h3>
                    
                    <?php if ($financialApproved): ?>
                        <div class="alert alert-success">
                            <div style="font-weight: 700; margin-bottom: 4px;">✅ Financials Verified & Sealed</div>
                            <div style="font-size: 0.85rem; font-weight: 500; line-height: 1.5;">
                                Structure: <?php echo htmlspecialchars($financial['profession']); ?><br>
                                Est. Revenue: <?php echo number_format($financial['salary'], 2); ?> DH
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if ($financial && $financial['status'] === 'pending'): ?>
                            <div class="alert alert-pending">
                                ⏳ Review in progress: Store financials submitted.
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_financials">
                            <div class="form-group">
                                <label class="form-label">Legal Business Structure</label>
                                <input type="text" name="profession" class="form-input" required value="<?php echo htmlspecialchars($financial['profession'] ?? ''); ?>" placeholder="e.g. LLC, SARL, Sole Proprietorship">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Estimated Monthly Revenue (DH)</label>
                                <input type="number" name="salary" class="form-input" required value="<?php echo htmlspecialchars($financial['salary'] ?? ''); ?>" placeholder="e.g. 50000">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Upload Bank Statement / Tax Paper</label>
                                <input type="file" name="salary_proof" <?php echo $financial ? '' : 'required'; ?> style="font-size: 0.9rem; color: var(--text-muted); width: 100%; padding: 8px 0;">
                                <?php if ($financial && $financial['salary_proof']): ?>
                                    <small style="display: block; margin-top: 6px; color: #3b82f6; font-weight: 600;">📄 Current: <?php echo htmlspecialchars($financial['salary_proof']); ?></small>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn-black">
                                Submit Financials
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </main>

</body>
</html>
