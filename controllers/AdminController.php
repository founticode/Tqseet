<?php
session_start();
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $userId = $_POST["user_id"];
    $action = $_POST["action"];

    $db = new Database();
    $conn = $db->connect();

    if ($action === "approve") {
        // 1. Mark user as verified
        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // 2. Remove the verification record (it's no longer pending)
        $stmt2 = $conn->prepare("DELETE FROM user_verifications WHERE user_id = ?");
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();

        echo "<h2 style='color:green;'>✅ User #$userId Approved!</h2>";

    } elseif ($action === "reject") {
        // Just remove the record so the user can try again with better photos
        $stmt = $conn->prepare("DELETE FROM user_verifications WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        echo "<h2 style='color:red;'>❌ User #$userId Rejected!</h2>";
    }

    echo "<p>The list has been updated.</p>";
    echo "<br><a href='../views/admin/verifications.php'>← Back to Verifications</a>";
    
    $conn->close();

} else {
    header("Location: ../views/admin/verifications.php");
    exit;
}
