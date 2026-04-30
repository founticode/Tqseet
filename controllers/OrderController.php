<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/db.php";

requireLogin();
$user = currentUser();

$action = $_GET['action'] ?? '';
$orderId = $_GET['id'] ?? 0;

$db = new Database();
$conn = $db->connect();

if ($action === 'cancel' && $orderId > 0) {
    // SECURITY: Ensure the order belongs to the logged-in user
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $orderId, $user['id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        // 1. Delete associated installments first (Foreign Key constraint)
        $stmt_del_ins = $conn->prepare("DELETE FROM installments WHERE order_id = ?");
        $stmt_del_ins->bind_param("i", $orderId);
        $stmt_del_ins->execute();

        // 2. Delete the order
        $stmt_del_order = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt_del_order->bind_param("i", $orderId);
        $stmt_del_order->execute();

        header("Location: ../views/user/orders.php?cancelled=1");
    } else {
        die("Unauthorized action.");
    }
}

$conn->close();
?>
