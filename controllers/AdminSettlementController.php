<?php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";

// Strict Security: Only Admins can execute payouts
requireRole("admin");

$db = new Database();
$conn = $db->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Action: Mark Settlement as Paid
    if (isset($_POST['action']) && $_POST['action'] === 'mark_paid' && isset($_POST['settlement_id'])) {
        $settlementId = intval($_POST['settlement_id']);
        
        // Update the settlement status to 'paid'
        $stmt = $conn->prepare("UPDATE settlements SET status = 'paid' WHERE id = ?");
        $stmt->bind_param("i", $settlementId);
        
        if ($stmt->execute()) {
            header("Location: ../views/admin/settlements.php?success=Settlement Batch #" . str_pad($settlementId, 6, '0', STR_PAD_LEFT) . " marked as successfully transferred.");
            exit;
        } else {
            header("Location: ../views/admin/settlements.php?error=Database error while processing payout.");
            exit;
        }
    }
}

header("Location: ../views/admin/settlements.php");
exit;
