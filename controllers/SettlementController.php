<?php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";

requireRole("merchant");

$db = new Database();
$conn = $db->connect();
$user = currentUser();

// 1. Get Merchant Profile ID
$stmt_m = $conn->prepare("SELECT id FROM merchants WHERE user_id = ?");
$stmt_m->bind_param("i", $user['id']);
$stmt_m->execute();
$merchantData = $stmt_m->get_result()->fetch_assoc();

if (!$merchantData) {
    die("Merchant profile not found.");
}
$merchantId = $merchantData['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_payout'])) {
        
        // 2. Find all 'active' or 'paid' orders that haven't been settled yet
        $sql = "SELECT o.id, o.merchant_earning 
                FROM orders o 
                JOIN products p ON o.product_id = p.id 
                WHERE p.merchant_id = ? AND o.status IN ('active', 'paid') AND o.settlement_id IS NULL";
        
        $stmt_avail = $conn->prepare($sql);
        $stmt_avail->bind_param("i", $merchantId);
        $stmt_avail->execute();
        $result = $stmt_avail->get_result();
        
        $totalAmount = 0;
        $orderIds = [];
        
        while ($row = $result->fetch_assoc()) {
            $totalAmount += $row['merchant_earning'];
            $orderIds[] = $row['id'];
        }
        
        // 3. If funds exist, create the settlement batch
        if ($totalAmount > 0 && !empty($orderIds)) {
            
            // Create the settlement record
            $stmt_settle = $conn->prepare("INSERT INTO settlements (merchant_id, amount, status) VALUES (?, ?, 'pending')");
            $stmt_settle->bind_param("id", $merchantId, $totalAmount);
            $stmt_settle->execute();
            $settlementId = $conn->insert_id;
            
            // Update all related orders to link them to this settlement batch
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $types = 'i' . str_repeat('i', count($orderIds));
            $params = array_merge([$settlementId], $orderIds);
            
            $stmt_update = $conn->prepare("UPDATE orders SET settlement_id = ? WHERE id IN ($placeholders)");
            $stmt_update->bind_param($types, ...$params);
            $stmt_update->execute();
            
            header("Location: ../views/merchant/settlements.php?success=Payout request of " . number_format($totalAmount, 2) . " DH submitted securely.");
            exit;
        } else {
            header("Location: ../views/merchant/settlements.php?error=No cleared funds available for payout.");
            exit;
        }
    }
}

header("Location: ../views/merchant/settlements.php");
exit;
