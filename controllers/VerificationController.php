<?php
session_start();
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/db.php";

// Protect: Only users can submit verification
requireRole("user");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $userId = $_SESSION["user_id"];
    $cin    = trim($_POST["cin"]);
    $file   = $_FILES["cin_image"];

    // 1. Basic Validation
    if (empty($cin) || empty($file["name"])) {
        die("Please fill all fields and select an image.");
    }

    // 2. File Upload Logic
    $targetDir = __DIR__ . "/../uploads/kyc/";
    
    // Create folder if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Generate a unique name for the file (to avoid overwriting)
    $fileExt = pathinfo($file["name"], PATHINFO_EXTENSION);
    $fileName = "user_" . $userId . "_" . time() . "." . $fileExt;
    $targetFile = $targetDir . $fileName;

    // 3. Check if it's a real image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        die("File is not a valid image.");
    }

    // 4. Move the file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        
        // 5. Save to Database
        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("INSERT INTO user_verifications (user_id, cin, cin_image) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $cin, $fileName);

        if ($stmt->execute()) {
            echo "<h2>✅ Verification Submitted!</h2>";
            echo "<p>Your documents have been received and are pending review.</p>";
            echo "<br><a href='../views/user/dashboard.php'>Go to Dashboard</a>";
        } else {
            echo "❌ Database Error: Could not save verification data.";
        }

        $stmt->close();
        $conn->close();

    } else {
        echo "❌ Error uploading file.";
    }

} else {
    header("Location: ../views/user/verify.php");
    exit;
}
