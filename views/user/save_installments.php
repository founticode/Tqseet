<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect the page
requireLogin();
$user = currentUser();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $orderId = $_POST['order_id'] ?? 0;

    $db = new Database();
    $conn = $db->connect();

    // 1. Fetch the order
    $stmt = $conn->prepare("SELECT total_price, status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $orderId, $user['id']);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if ($order) {
        // --- NEW: PREVENTION CHECK ---
        // If order is already active, it means installments were already created.
        if ($order['status'] === 'active') {
            header("Location: orders.php");
            exit;
        }

        $totalAmount = $order['total_price'];
        $installmentAmount = $totalAmount / 4;

        // 2. Prepare the 4 installment dates
        $dates = [
            date('Y-m-d'), // Today
            date('Y-m-d', strtotime('+1 month')),
            date('Y-m-d', strtotime('+2 months')),
            date('Y-m-d', strtotime('+3 months'))
        ];

        // 3. Insert the installment records
        $stmt_ins = $conn->prepare("INSERT INTO installments (order_id, amount, due_date, status) VALUES (?, ?, ?, ?)");
        
        foreach ($dates as $index => $date) {
            // As per your request: The first split is paid immediately when buying
            $status = ($index === 0) ? 'paid' : 'unpaid';
            $stmt_ins->bind_param("idss", $orderId, $installmentAmount, $date, $status);
            $stmt_ins->execute();
        }

        // 4. Update the global Order status to 'active'
        $stmt_upd = $conn->prepare("UPDATE orders SET status = 'active' WHERE id = ?");
        $stmt_upd->bind_param("i", $orderId);
        $stmt_upd->execute();

        // 5. Success!
        // We redirect to the My Orders page (which we will build next)
        header("Location: orders.php?success=1");
        exit;
    } else {
        die("Invalid Order.");
    }

    $conn->close();
} else {
    // If someone tries to access this page directly without POST
    header("Location: ../public/catalog.php");
    exit;
}
?>
