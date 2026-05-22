<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect the page
requireLogin();
$user = currentUser();

// Get the POST data from the card form
$orderId = $_POST['order_id'] ?? 0;
$installmentId = $_POST['installment_id'] ?? 0;
$amount = $_POST['amount'] ?? 0;

$db = new Database();
$conn = $db->connect();

// Helper to show premium errors
function showPremiumError($title, $message, $primaryBtnText, $primaryBtnLink) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Error - TQSEET</title>
        <link rel="stylesheet" href="../../assets/css/style.css">
        <style>
            .error-container { max-width: 500px; margin: 80px auto; background: white; padding: 48px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.05); text-align: center; border: 1px solid #e2e8f0; }
            .error-icon { width: 80px; height: 80px; background: #fef2f2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 24px auto; }
            .error-title { font-size: 1.8rem; font-weight: 900; color: #0f172a; margin-bottom: 16px; letter-spacing: -0.5px; }
            .error-message { color: #64748b; font-size: 1.05rem; line-height: 1.6; margin-bottom: 32px; }
            .btn-primary { display: block; width: 100%; background: #0f172a; color: white; padding: 16px; border-radius: 12px; text-decoration: none; font-weight: 800; font-size: 1.05rem; transition: transform 0.2s, box-shadow 0.2s; margin-bottom: 12px; }
            .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        </style>
    </head>
    <body style="background: #f8fafc;">
        <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>
        <div class="error-container">
            <div class="error-icon">❌</div>
            <div class="error-title"><?php echo htmlspecialchars($title); ?></div>
            <div class="error-message"><?php echo $message; ?></div>
            <a href="<?php echo $primaryBtnLink; ?>" class="btn-primary"><?php echo htmlspecialchars($primaryBtnText); ?></a>
        </div>
        <?php include_once __DIR__ . "/../../includes/footer.php"; ?>
    </body>
    </html>
    <?php
    exit;
}

// --- NEW: ABSOLUTE FINAL SECURITY CHECK ---
$stmt_sec = $conn->prepare("SELECT status FROM user_financials WHERE user_id = ?");
$stmt_sec->bind_param("i", $user['id']);
$stmt_sec->execute();
$secStatus = $stmt_sec->get_result()->fetch_assoc()['status'] ?? 'none';

if ($secStatus !== 'approved') {
    showPremiumError("Unauthorized", "Your account is not fully verified yet. Please complete your identity and financial profile.", "Go to Settings", "settings.php");
}

// --- NEW: SAVE CARD LOGIC ---
if (isset($_POST['card_number'])) {
    $cardNumber = $_POST['card_number'];
    $lastFour = substr(str_replace(' ', '', $cardNumber), -4);
    $expiry = $_POST['expiry'] ?? '';

    // Check if user already has this card saved
    $stmt_check_card = $conn->prepare("SELECT id FROM payment_methods WHERE user_id = ? AND last_four = ?");
    $stmt_check_card->bind_param("is", $user['id'], $lastFour);
    $stmt_check_card->execute();
    
    if ($stmt_check_card->get_result()->num_rows === 0) {
        $stmt_save_card = $conn->prepare("INSERT INTO payment_methods (user_id, last_four, expiry) VALUES (?, ?, ?)");
        $stmt_save_card->bind_param("iss", $user['id'], $lastFour, $expiry);
        $stmt_save_card->execute();
    }
}
// Here we would normally call a Payment Gateway API (like Stripe or Paypal).
// Since this is a simulation, we just wait 2 seconds and update the DB.
// Note: We use the "Processing" animation page as an intermediate step for better UX.

// LOGIC A: If it's an existing installment being paid manually from the dashboard
if ($installmentId > 0) {
    $stmt = $conn->prepare("UPDATE installments SET status = 'paid' WHERE id = ?");
    $stmt->bind_param("i", $installmentId);
    $stmt->execute();
    
    // --- NEW: Check if ALL installments for this order are now paid ---
    $stmt_check = $conn->prepare("SELECT id FROM installments WHERE order_id = ? AND status = 'unpaid'");
    $stmt_check->bind_param("i", $orderId);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows === 0) {
        // Everything is paid! Update the Order status
        $stmt_order = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
        $stmt_order->bind_param("i", $orderId);
        $stmt_order->execute();
    }

    // Redirect back to the timeline
    header("Location: view_installments.php?order_id=$orderId&paid_success=1");
    exit;
}

// LOGIC B: If it's the initial checkout (the first downpayment)
if ($orderId > 0 && $installmentId == 0) {
    // --- NEW: CREDIT LIMIT GUARD (FINAL CHECK) ---
    $stmt_f = $conn->prepare("SELECT credit_limit FROM user_financials WHERE user_id = ? AND status = 'approved'");
    $stmt_f->bind_param("i", $user['id']);
    $stmt_f->execute();
    $fin = $stmt_f->get_result()->fetch_assoc();
    $maxLimit = $fin['credit_limit'] ?? 0;

    $stmt_debt = $conn->prepare("SELECT SUM(amount) as debt FROM installments i JOIN orders o ON i.order_id = o.id WHERE o.user_id = ? AND i.status = 'unpaid'");
    $stmt_debt->bind_param("i", $user['id']);
    $stmt_debt->execute();
    $totalDebt = $stmt_debt->get_result()->fetch_assoc()['debt'] ?? 0;

    $availableCredit = $maxLimit - $totalDebt;

    // Fetch the total price from the order to compare
    $stmt_p = $conn->prepare("SELECT total_price FROM orders WHERE id = ?");
    $stmt_p->bind_param("i", $orderId);
    $stmt_p->execute();
    $orderData = $stmt_p->get_result()->fetch_assoc();
    
    if (!$orderData) {
        showPremiumError("Order Not Found", "We couldn't process this transaction because the order no longer exists.", "Back to Shop", "../public/shop.php");
    }
    $total = $orderData['total_price'];

    if ($total > $availableCredit) {
        showPremiumError("Transaction Declined", "Insufficient available credit limit to complete this purchase. Please pay off active installments first.", "View Active Plans", "orders.php");
    }

    $installmentAmount = $total / 4;

    // 2. Create the 4 installments in the database
    $dates = [
        date('Y-m-d'), // Today
        date('Y-m-d', strtotime('+1 month')),
        date('Y-m-d', strtotime('+2 months')),
        date('Y-m-d', strtotime('+3 months'))
    ];

    $stmt_ins = $conn->prepare("INSERT INTO installments (order_id, amount, due_date, status) VALUES (?, ?, ?, ?)");
    
    foreach ($dates as $index => $date) {
        // First one is 'paid' because the user just authorized the payment in the form
        $status = ($index === 0) ? 'paid' : 'unpaid';
        $stmt_ins->bind_param("idss", $orderId, $installmentAmount, $date, $status);
        $stmt_ins->execute();
    }

    // 3. Mark the overall order as 'active'
    $stmt_upd = $conn->prepare("UPDATE orders SET status = 'active' WHERE id = ?");
    $stmt_upd->bind_param("i", $orderId);
    $stmt_upd->execute();

    // Redirect to the success screen
    header("Location: orders.php?success=1");
    exit;
}

$conn->close();
?>
