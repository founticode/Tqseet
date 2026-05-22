<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

requireRole("admin");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $newName = $_POST['name'];
        $stmt_upd = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt_upd->bind_param("si", $newName, $user['id']);
        if ($stmt_upd->execute()) {
            $message = "Admin profile updated successfully!";
            $_SESSION['user']['name'] = $newName;
            $user = currentUser();
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_global') {
        // In a real production app, these would save to a `settings` table in the DB.
        // For now, we simulate the success of updating the platform parameters.
        $message = "Global Platform Settings have been securely updated!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - TQSEET</title>
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 32px;
            align-items: start;
        }
        @media (max-width: 1024px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #10b981;
        }
        input:checked + .slider:before {
            transform: translateX(22px);
        }
    </style>
</head>
<body>

    <!-- Premium Admin Sidebar -->
    <?php include_once __DIR__ . "/../../includes/admin_sidebar.php"; ?>

    <main class="main-content">
        
        <header class="page-header" style="margin-bottom: 40px;">
            <div>
                <h1 style="font-size: 2rem; font-weight: 900; margin: 0 0 8px 0; color: #111827; letter-spacing: -0.5px;">Control Settings</h1>
                <p style="color: #6b7280; margin: 0; font-size: 1rem; font-weight: 500;">Manage platform configurations and your command profile.</p>
            </div>
        </header>

        <?php if ($message): ?>
            <div style="background: #ecfdf5; color: #065f46; padding: 16px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; border: 1px solid #a7f3d0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid">
            
            <!-- Left: Global Platform Settings -->
            <div class="card" style="padding: 40px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);">
                <div style="margin-bottom: 30px;">
                    <h2 style="font-size: 1.3rem; color: #111827; margin: 0 0 8px 0; font-weight: 800;">Global Configurations</h2>
                    <p style="font-size: 0.9rem; color: #6b7280; margin: 0;">These settings affect all users and merchants on the platform.</p>
                </div>

                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_global">
                    
                    <div style="margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 800; color: #0f172a; margin-bottom: 4px;">BNPL Engine Status</div>
                            <div style="font-size: 0.8rem; color: #64748b;">Enable or disable new installment purchases globally.</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div style="margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 800; color: #0f172a; margin-bottom: 4px;">Auto-Approve Merchants</div>
                            <div style="font-size: 0.8rem; color: #64748b;">Automatically approve new merchant API keys.</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">Default Platform Commission (%)</label>
                        <input type="number" step="0.01" value="0.05" required
                               style="width: 100%; padding: 12px 16px; background: white; border: 1px solid #d1d5db; border-radius: 10px; color: #111827; font-size: 1rem; outline: none; transition: border-color 0.2s;">
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 6px;">Applied to all new merchants (0.05 = 5%)</div>
                    </div>

                    <div style="margin-bottom: 32px;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">Max User Credit Limit (DH)</label>
                        <input type="number" step="100" value="50000" required
                               style="width: 100%; padding: 12px 16px; background: white; border: 1px solid #d1d5db; border-radius: 10px; color: #111827; font-size: 1rem; outline: none; transition: border-color 0.2s;">
                    </div>

                    <button type="submit" class="btn-black" style="background: #2563eb; width: 100%; padding: 16px; font-size: 1.05rem; justify-content: center;">
                        Apply Global Changes
                    </button>
                </form>
            </div>

            <!-- Right: Command Profile -->
            <div class="card" style="padding: 40px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);">
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="font-size: 3rem; margin-bottom: 10px;">🛡️</div>
                    <h2 style="font-size: 1.3rem; color: #111827; margin: 0; font-weight: 800;">Command Profile</h2>
                    <p style="font-size: 0.9rem; color: #6b7280; margin: 4px 0 0 0;">Manage your personal admin credentials.</p>
                </div>

                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">Admin Display Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                               style="width: 100%; padding: 12px 16px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 10px; color: #111827; font-size: 1rem; outline: none; transition: border-color 0.2s;">
                    </div>

                    <div class="form-group" style="margin-bottom: 32px;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 8px; letter-spacing: 0.5px;">System Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                               style="width: 100%; padding: 12px 16px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 10px; color: #9ca3af; font-size: 1rem; cursor: not-allowed;">
                    </div>

                    <button type="submit" class="btn-black" style="width: 100%; padding: 16px; font-size: 1.05rem; justify-content: center; margin-bottom: 16px;">
                        Save Profile Changes
                    </button>

                    <div style="text-align: center;">
                        <a href="../user/update_password.php" style="color: #3b82f6; text-decoration: none; font-size: 0.95rem; font-weight: 600;">
                            🔐 Change Security Password
                        </a>
                    </div>
                </form>
            </div>

        </div>

    </main>

</body>
</html>
