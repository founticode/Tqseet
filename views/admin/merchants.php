<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

$db = new Database();
$conn = $db->connect();

// Fetch merchants
$query = "SELECT m.*, u.name, u.email 
          FROM merchants m 
          JOIN users u ON m.user_id = u.id 
          ORDER BY m.created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TQSEET Admin - Manage Merchants</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #f4f4f4;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1000px; margin: 40px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h1>Registered Merchants</h1>
        
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead style="background: #eee;">
                <tr>
                    <th style="padding: 10px; text-align: left;">Merchant ID</th>
                    <th style="padding: 10px; text-align: left;">Name</th>
                    <th style="padding: 10px; text-align: left;">Email</th>
                    <th style="padding: 10px; text-align: left;">Commission</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 10px;">#<?php echo $row['id']; ?></td>
                        <td style="padding: 10px;"><?php echo $row['name']; ?></td>
                        <td style="padding: 10px;"><?php echo $row['email']; ?></td>
                        <td style="padding: 10px;"><?php echo $row['commission_rate']; ?>%</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
