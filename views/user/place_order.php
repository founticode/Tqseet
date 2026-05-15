<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Only logged-in users can buy
requireLogin();
$user = currentUser();

$productId = $_GET['id'] ?? 0;

$db = new Database();
$conn = $db->connect();

// 1. Get Product Details and verify it exists
$stmt = $conn->prepare("SELECT p.*, m.commission_rate 
                        FROM products p 
                        JOIN merchants m ON p.merchant_id = m.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h1>Error: Product not found.</h1>
            <a href='../public/catalog.php'>Return to Catalog</a>
         </div>");
}

// 2. NEW: CHECK CREDIT LIMIT
$stmt_f = $conn->prepare("SELECT credit_limit FROM user_financials WHERE user_id = ? AND status = 'approved'");
$stmt_f->bind_param("i", $user['id']);
$stmt_f->execute();
$fin = $stmt_f->get_result()->fetch_assoc();

if (!$fin) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h1>Action Required: Setup your credit profile first.</h1>
            <p>You need an approved credit limit to shop with installments.</p>
            <a href='financial_profile.php'>Complete Profile</a>
         </div>");
}

$maxLimit = $fin['credit_limit'];

// Calculate Debt
$stmt_debt = $conn->prepare("SELECT SUM(amount) as debt FROM installments i JOIN orders o ON i.order_id = o.id WHERE o.user_id = ? AND i.status = 'unpaid'");
$stmt_debt->bind_param("i", $user['id']);
$stmt_debt->execute();
$totalDebt = $stmt_debt->get_result()->fetch_assoc()['debt'] ?? 0;

$availableCredit = $maxLimit - $totalDebt;

if ($product['price'] > $availableCredit) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h1 style='color:#e74c3c;'>Insufficient Credit Limit</h1>
            <p>This product costs <strong>" . number_format($product['price'], 2) . " DH</strong>, but your available credit is <strong>" . number_format($availableCredit, 2) . " DH</strong>.</p>
            <p>Please pay off your existing installments to free up credit.</p>
            <a href='orders.php'>View My Shopping</a> | <a href='../public/catalog.php'>Back to Catalog</a>
         </div>");
}

// 3. Financial Calculations
// Fetch the specific commission_rate from the merchants table.
$totalPrice = $product['price'];
$commissionRate = $product['commission_rate'] ?? 0.05; // Default to 5% if missing
$commission = $totalPrice * $commissionRate;
$merchantEarning = $totalPrice - $commission;

// 3. --- NEW: CHECK FOR EXISTING PENDING ORDER (Tabby/Klarna Style) ---
// If the user already has a pending order for this exact product, reuse it!
$stmt_check = $conn->prepare("SELECT id FROM orders WHERE user_id = ? AND product_id = ? AND status = 'pending' LIMIT 1");
$stmt_check->bind_param("ii", $user['id'], $productId);
$stmt_check->execute();
$existingOrder = $stmt_check->get_result()->fetch_assoc();

if ($existingOrder) {
    // Reuse the existing pending order
    header("Location: calculate_installments.php?order_id=" . $existingOrder['id']);
    exit;
}

// 4. Save a NEW Order (Only if no pending one exists)
$stmt_order = $conn->prepare("INSERT INTO orders (user_id, product_id, total_price, commission, merchant_earning, status) VALUES (?, ?, ?, ?, ?, 'pending')");
$stmt_order->bind_param("iiddd", $user['id'], $productId, $totalPrice, $commission, $merchantEarning);

if ($stmt_order->execute()) {
    $orderId = $conn->insert_id;
    
    // Success! Now move to the next step: Splitting the total into payments.
    header("Location: calculate_installments.php?order_id=" . $orderId);
    exit;
} else {
    echo "<h2 style='color:red;'>❌ Error: Could not process your order. Please try again.</h2>";
    echo "<a href='../public/product_detail.php?id=$productId'>Back to Product</a>";
}

$stmt->close();
$conn->close();
?>
