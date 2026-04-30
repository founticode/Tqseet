<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

$db = new Database();
$conn = $db->connect();

// Fetch users who have submitted verification (We will build the submit part in Phase 12)
// For now, let's just list all users to see if the page works
$query = "SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET Admin - User Verifications</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #f4f4f4;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1100px; margin: 40px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h1 style="border-bottom: 2px solid #eee; padding-bottom: 15px;">Pending Verifications</h1>
        
        <p style="color: #666; margin-bottom: 25px;">Review the financial profiles and identity documents of users applying for BNPL credit.</p>

        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8f9fa;">
                <tr>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;">User ID</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;">Name</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;">Email</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;">Contact Verified</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px;">#<?php echo $row['id']; ?></td>
                            <td style="padding: 12px; font-weight: bold;"><?php echo $row['name']; ?></td>
                            <td style="padding: 12px;"><?php echo $row['email']; ?></td>
                            <td style="padding: 12px;">
                                <?php if ($row['is_verified']): ?>
                                    <span style="color: green;">✅ Yes</span>
                                <?php else: ?>
                                    <span style="color: red;">❌ No</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px;">
                                <a href="view_user.php?id=<?php echo $row['id']; ?>" style="color: #007bff; text-decoration: none; font-weight: bold;">Review Details →</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 50px; color: #999;">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
