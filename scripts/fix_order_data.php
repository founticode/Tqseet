<?php
require_once __DIR__ . "/../config/db.php";

$db = new Database();
$conn = $db->connect();

echo "Starting Order Data Synchronization...\n";

// 1. Fetch all orders
$orders = $conn->query("SELECT o.*, m.commission_rate FROM orders o LEFT JOIN products p ON o.product_id = p.id LEFT JOIN merchants m ON p.merchant_id = m.id");

while ($order = $orders->fetch_assoc()) {
    $orderId = $order['id'];
    $totalPrice = $order['total_price'];
    $rate = $order['commission_rate'] ?? 0.05; // Default to 5% if merchant has no rate set

    // A. Fix Commission and Earnings if they are 0
    if ($order['commission'] == 0) {
        $commission = $totalPrice * $rate;
        $earning = $totalPrice - $commission;
        $conn->query("UPDATE orders SET commission = $commission, merchant_earning = $earning WHERE id = $orderId");
        echo "Fixed commission for Order #$orderId ($totalPrice DH at " . ($rate*100) . "%)\n";
    }

    // B. Check Installment Status to fix Order Status
    $unpaid = $conn->query("SELECT COUNT(*) FROM installments WHERE order_id = $orderId AND status = 'unpaid'")->fetch_row()[0];
    $total_ins = $conn->query("SELECT COUNT(*) FROM installments WHERE order_id = $orderId")->fetch_row()[0];

    if ($total_ins > 0) {
        if ($unpaid == 0) {
            $newStatus = 'paid';
        } else {
            $newStatus = 'active';
        }
        
        $conn->query("UPDATE orders SET status = '$newStatus' WHERE id = $orderId");
        echo "Updated Order #$orderId status to '$newStatus'\n";
    }
}

echo "\nDone! All existing orders have been synchronized.\n";
