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
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h1>Error: Product not found.</h1>
            <a href='../public/catalog.php'>Return to Catalog</a>
         </div>");
}

// 2. Financial Calculations
// In a real app, we would fetch the specific commission_rate from the merchants table.
// For now, we use a standard 10% platform commission.
$totalPrice = $product['price'];
$commissionRate = 0.10; 
$commission = $totalPrice * $commissionRate;
$merchantEarning = $totalPrice - $commission;

// 3. Save the Initial Order
// The status starts as 'pending' until the installments are confirmed.
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
