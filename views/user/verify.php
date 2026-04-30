<?php
require_once __DIR__ . "/../../includes/auth.php";

// Protect the page: Only "user" role allowed
requireRole("user");

$user = currentUser();

// If user is already verified, they don't need this form
if ($user['is_verified']) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Account Verification</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #fafafa;">

    <!-- Include the Navbar -->
    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 600px; margin: 40px auto; padding: 20px; background: white; border-radius: 8px; border: 1px solid #ddd;">
        <h1>Verify Your Identity</h1>
        <p>Please provide your CIN and a clear photo of your ID card to unlock BNPL features.</p>

        <hr>

        <form action="../../controllers/VerificationController.php" method="POST" enctype="multipart/form-data">
            
            <div style="margin-bottom: 15px;">
                <label for="cin"><strong>CIN (National ID Number):</strong></label><br>
                <input type="text" id="cin" name="cin" required style="width: 100%; padding: 8px; margin-top: 5px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="cin_image"><strong>Upload ID Photo (JPG, PNG):</strong></label><br>
                <input type="file" id="cin_image" name="cin_image" accept="image/*" required style="margin-top: 5px;">
            </div>

            <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                Submit for Verification
            </button>

        </form>
    </div>

</body>
</html>
