<?php
session_start();
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/db.php";

// Protect: ONLY Admins allowed!
requireRole("admin");

$action = $_GET['action'] ?? '';

if ($action === "decide" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_POST['user_id'];
    $status = $_POST['status']; // 'approved' or 'rejected'
    $commissionRate = $_POST['commission_rate'] ?? 0.05;

    $db = new Database();
    $conn = $db->connect();

    // Calculate Credit Limit automatically based on Salary
    $creditLimit = 0;
    if ($status === 'approved') {
        $stmt_f = $conn->prepare("SELECT salary FROM user_financials WHERE user_id = ?");
        $stmt_f->bind_param("i", $userId);
        $stmt_f->execute();
        $f_data = $stmt_f->get_result()->fetch_assoc();
        if ($f_data) {
            $creditLimit = $f_data['salary'] * 1.5; // Our professional 1.5x multiplier
        }
    }

    // 1. Update Identity status
    $stmt1 = $conn->prepare("UPDATE user_verifications SET status = ? WHERE user_id = ?");
    $stmt1->bind_param("si", $status, $userId);
    $stmt1->execute();

    // 2. Update Financial status and Credit Limit
    $stmt2 = $conn->prepare("UPDATE user_financials SET status = ?, credit_limit = ? WHERE user_id = ?");
    $stmt2->bind_param("sdi", $status, $creditLimit, $userId);
    $stmt2->execute();

    // 3. NEW: Update Merchant status if applicable
    $stmt_m = $conn->prepare("UPDATE merchants SET status = ?, commission_rate = ? WHERE user_id = ?");
    $stmt_m->bind_param("sdi", $status, $commissionRate, $userId);
    $stmt_m->execute();

    // 4. Update main User table
    $isVerified = ($status === 'approved') ? 1 : 0;
    $stmt3 = $conn->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
    $stmt3->bind_param("ii", $isVerified, $userId);
    $stmt3->execute();

    // Redirect back with success message
    header("Location: ../views/admin/verifications.php?status_updated=1");
    exit;
}

if ($action === "update_commission" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_POST['user_id'];
    $commissionRate = $_POST['commission_rate'];

    $db = new Database();
    $conn = $db->connect();

    $stmt = $conn->prepare("UPDATE merchants SET commission_rate = ? WHERE user_id = ?");
    $stmt->bind_param("di", $commissionRate, $userId);
    
    if ($stmt->execute()) {
        header("Location: ../views/admin/view_user.php?id=$userId&commission_updated=1");
    } else {
        header("Location: ../views/admin/view_user.php?id=$userId&error=1");
    }
    exit;
}
