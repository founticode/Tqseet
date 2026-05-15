<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/db.php";

requireLogin();
$user = currentUser();

$orderId = $_POST['order_id'] ?? 0;

if ($orderId > 0) {
    $db = new Database();
    $conn = $db->connect();

    // Verify the order belongs to the user and ZERO installments are paid
    $stmt_c = $conn->prepare("SELECT COUNT(*) as paid_count FROM installments WHERE order_id = ? AND status = 'paid'");
    $stmt_c->bind_param("i", $orderId);
    $stmt_c->execute();
    $paidCount = $stmt_c->get_result()->fetch_assoc()['paid_count'] ?? 0;

    if ($paidCount == 0) {
        // Safe to delete
        // 1. Delete Installments
        $stmt_i = $conn->prepare("DELETE FROM installments WHERE order_id = ?");
        $stmt_i->bind_param("i", $orderId);
        $stmt_i->execute();

        // 2. Delete Order
        $stmt_o = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt_o->bind_param("i", $orderId);
        $stmt_o->execute();

        header("Location: ../views/user/orders.php?cancelled=1");
        exit;
    } else {
        header("Location: ../views/user/orders.php?error=already_paid");
        exit;
    }
}

header("Location: ../views/user/orders.php?error=cancel_failed");
exit;
