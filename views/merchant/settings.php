<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

requireRole("merchant");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch Merchant Data
$stmt = $conn->prepare("SELECT * FROM merchants WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$merchant = $stmt->get_result()->fetch_assoc();

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $storeName = $_POST['store_name'];
    $description = $_POST['description'];
    
    $stmt_upd = $conn->prepare("UPDATE merchants SET store_name = ?, description = ? WHERE user_id = ?");
    $stmt_upd->bind_param("ssi", $storeName, $description, $user['id']);
    
    if ($stmt_upd->execute()) {
        $message = "Store profile updated successfully!";
        // Refresh merchant data
        $stmt->execute();
        $merchant = $stmt->get_result()->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Store Settings - TQSEET</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; margin: 0; color: #2d3436;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 800px; margin: 60px auto; padding: 0 20px;">
        
        <div style="margin-bottom: 40px;">
            <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px;">Store Management</h1>
            <p style="color: #636e72; margin: 8px 0 0 0; font-weight: 500;">Branding and business information for your shop.</p>
        </div>

        <?php if ($message): ?>
            <div style="background: #eafaf1; color: #27ae60; padding: 15px 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #d4edda; font-weight: bold;">
                ✅ <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
            
            <!-- Left: Store Form -->
            <div style="background: white; border-radius: 25px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02);">
                <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 25px;">Store Identity</h3>
                
                <form action="" method="POST">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Public Store Name</label>
                        <input type="text" name="store_name" value="<?php echo htmlspecialchars($merchant['store_name']); ?>" required
                               style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; font-size: 0.95rem; outline: none;">
                    </div>
                    
                    <div style="margin-bottom: 30px;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #b2bec3; margin-bottom: 8px;">Store Description</label>
                        <textarea name="description" rows="5" required
                                  style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; font-size: 0.95rem; outline: none; resize: none;"><?php echo htmlspecialchars($merchant['description']); ?></textarea>
                        <small style="color: #b2bec3; margin-top: 5px; display: block;">This will be visible on your product pages.</small>
                    </div>

                    <button type="submit" style="background: #222; color: white; padding: 15px 30px; border: none; border-radius: 12px; font-weight: 900; cursor: pointer;">
                        Update Store Profile
                    </button>
                </form>
            </div>

            <!-- Right: Account Info -->
            <div>
                <div style="background: white; border-radius: 25px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); margin-bottom: 30px;">
                    <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 20px; font-size: 1rem;">Partner Agreement</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: bold; color: #636e72;">Commission Rate</span>
                            <span style="font-size: 0.75rem; font-weight: 900; color: #2d3436; background: #f1f3f5; padding: 4px 10px; border-radius: 8px;">
                                <?php echo ($merchant['commission_rate'] * 100); ?>%
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; font-weight: bold; color: #636e72;">Status</span>
                            <span style="font-size: 0.65rem; font-weight: 900; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; background: #eafaf1; color: #27ae60;">
                                <?php echo $merchant['status']; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div style="background: #222; border-radius: 25px; padding: 30px; color: white;">
                    <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 15px; font-size: 1rem;">Security</h3>
                    <p style="font-size: 0.8rem; opacity: 0.6; line-height: 1.5; margin-bottom: 20px;">Update your login credentials to stay safe.</p>
                    <a href="../user/update_password.php" style="display: block; background: rgba(255,255,255,0.1); color: white; text-align: center; padding: 12px; text-decoration: none; border-radius: 10px; font-size: 0.85rem; font-weight: bold; border: 1px solid rgba(255,255,255,0.1);">
                        Change Password
                    </a>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
