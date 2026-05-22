<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Only logged-in users can buy
requireLogin();
$user = currentUser();

$productId = $_GET['id'] ?? 0;

$db = new Database();
$conn = $db->connect();

// Helper to show premium errors
function showPremiumError($title, $message, $primaryBtnText, $primaryBtnLink, $secondaryBtnText = null, $secondaryBtnLink = null) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Action Required - TQSEET</title>
        <link rel="stylesheet" href="../../assets/css/style.css">
        <style>
            .error-container {
                max-width: 500px;
                margin: 80px auto;
                background: white;
                padding: 48px;
                border-radius: 24px;
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.05);
                text-align: center;
                border: 1px solid #e2e8f0;
            }
            .error-icon {
                width: 80px;
                height: 80px;
                background: #fef2f2;
                color: #ef4444;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2.5rem;
                margin: 0 auto 24px auto;
            }
            .error-title {
                font-size: 1.8rem;
                font-weight: 900;
                color: #0f172a;
                margin-bottom: 16px;
                letter-spacing: -0.5px;
            }
            .error-message {
                color: #64748b;
                font-size: 1.05rem;
                line-height: 1.6;
                margin-bottom: 32px;
            }
            .btn-primary {
                display: block;
                width: 100%;
                background: #0f172a;
                color: white;
                padding: 16px;
                border-radius: 12px;
                text-decoration: none;
                font-weight: 800;
                font-size: 1.05rem;
                transition: transform 0.2s, box-shadow 0.2s;
                margin-bottom: 12px;
            }
            .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
            .btn-secondary {
                display: block;
                width: 100%;
                background: transparent;
                color: #64748b;
                padding: 16px;
                border-radius: 12px;
                text-decoration: none;
                font-weight: 700;
                font-size: 1.05rem;
                transition: background 0.2s;
            }
            .btn-secondary:hover { background: #f8fafc; color: #0f172a; }
        </style>
    </head>
    <body style="background: #f8fafc;">
        <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>
        
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <div class="error-title"><?php echo htmlspecialchars($title); ?></div>
            <div class="error-message"><?php echo $message; ?></div>
            
            <a href="<?php echo $primaryBtnLink; ?>" class="btn-primary"><?php echo htmlspecialchars($primaryBtnText); ?></a>
            
            <?php if($secondaryBtnText): ?>
                <a href="<?php echo $secondaryBtnLink; ?>" class="btn-secondary"><?php echo htmlspecialchars($secondaryBtnText); ?></a>
            <?php endif; ?>
        </div>

        <?php include_once __DIR__ . "/../../includes/footer.php"; ?>
    </body>
    </html>
    <?php
    exit;
}

// 1. Get Product Details and verify it exists
$stmt = $conn->prepare("SELECT p.*, m.commission_rate 
                        FROM products p 
                        JOIN merchants m ON p.merchant_id = m.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    showPremiumError("Product Not Found", "The product you are trying to purchase no longer exists or has been removed from the catalog.", "Browse Catalog", "../public/shop.php");
}

// 2. NEW: CHECK CREDIT LIMIT
$stmt_f = $conn->prepare("SELECT credit_limit FROM user_financials WHERE user_id = ? AND status = 'approved'");
$stmt_f->bind_param("i", $user['id']);
$stmt_f->execute();
$fin = $stmt_f->get_result()->fetch_assoc();

if (!$fin) {
    showPremiumError(
        "Action Required", 
        "You need an approved credit limit to shop with installments. Please complete your verification profile.", 
        "Complete Verification", "settings.php#verification-section",
        "Back to Shop", "../public/shop.php"
    );
}

$maxLimit = $fin['credit_limit'];

// Calculate Debt
$stmt_debt = $conn->prepare("SELECT SUM(amount) as debt FROM installments i JOIN orders o ON i.order_id = o.id WHERE o.user_id = ? AND i.status = 'unpaid'");
$stmt_debt->bind_param("i", $user['id']);
$stmt_debt->execute();
$totalDebt = $stmt_debt->get_result()->fetch_assoc()['debt'] ?? 0;

$availableCredit = $maxLimit - $totalDebt;

if ($product['price'] > $availableCredit) {
    showPremiumError(
        "Insufficient Credit Limit", 
        "This product costs <strong>" . number_format($product['price'], 2) . " DH</strong>, but your available credit is only <strong>" . number_format($availableCredit, 2) . " DH</strong>.<br><br>Please pay off your existing active installments to free up your purchasing power.", 
        "View Active Plans", "orders.php",
        "Back to Shop", "../public/shop.php"
    );
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
    showPremiumError("System Error", "Could not process your checkout request due to a technical error. Please try again later.", "Return to Shop", "../public/shop.php");
}

$stmt->close();
$conn->close();
?>
