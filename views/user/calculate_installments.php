<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Only logged-in users
requireLogin();
$user = currentUser();

$orderId = $_GET['order_id'] ?? 0;

$db = new Database();
$conn = $db->connect();

// NEW: Check for saved card (Tabby style)
$stmt_card = $conn->prepare("SELECT last_four FROM payment_methods WHERE user_id = ? LIMIT 1");
$stmt_card->bind_param("i", $user['id']);
$stmt_card->execute();
$savedCard = $stmt_card->get_result()->fetch_assoc();

// 1. Fetch Order and Product Details
$stmt = $conn->prepare("SELECT o.*, p.name as product_name, p.image as product_image, m.store_name 
                        FROM orders o 
                        JOIN products p ON o.product_id = p.id 
                        LEFT JOIN merchants m ON p.merchant_id = m.id
                        WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $orderId, $user['id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h1>Order session expired or not found.</h1>
            <a href='../public/shop.php'>Return to Catalog</a>
         </div>");
}

// --- NEW: CREDIT GUARD (RESUME CHECK) ---
$stmt_f = $conn->prepare("SELECT credit_limit FROM user_financials WHERE user_id = ? AND status = 'approved'");
$stmt_f->bind_param("i", $user['id']);
$stmt_f->execute();
$fin = $stmt_f->get_result()->fetch_assoc();

if (!$fin) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h1>Action Required: Setup your credit profile.</h1>
            <a href='settings.php#verification-section'>Complete Profile</a>
         </div>");
}

$maxLimit = $fin['credit_limit'];

// Calculate Debt (excluding THIS order if it already has installments - but drafts don't)
$stmt_debt = $conn->prepare("SELECT SUM(amount) as debt FROM installments i JOIN orders o ON i.order_id = o.id WHERE o.user_id = ? AND i.status = 'unpaid' AND o.id != ?");
$stmt_debt->bind_param("ii", $user['id'], $orderId);
$stmt_debt->execute();
$totalDebt = $stmt_debt->get_result()->fetch_assoc()['debt'] ?? 0;

$availableCredit = $maxLimit - $totalDebt;

if ($order['total_price'] > $availableCredit) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h1 style='color:#e74c3c;'>Credit Limit Exceeded</h1>
            <p>Your available credit is <strong>" . number_format($availableCredit, 2) . " DH</strong>, but this order is <strong>" . number_format($order['total_price'], 2) . " DH</strong>.</p>
            <p>Please pay off other plans first or cancel this draft.</p>
            <a href='orders.php'>Back to Shopping</a>
         </div>");
}

// 2. Logic: Split into 4 installments (Pay in 4 Model)
$totalAmount = $order['total_price'];
$installmentAmount = $totalAmount / 4;

// 3. Define Due Dates (Today, +1 month, +2 months, +3 months)
$dates = [
    date('Y-m-d'), // TODAY (Downpayment)
    date('Y-m-d', strtotime('+1 month')),
    date('Y-m-d', strtotime('+2 months')),
    date('Y-m-d', strtotime('+3 months'))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Wall - TQSEET</title>
    <style>
        .checkout-wall-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .checkout-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px; align-items: start; }
        
        /* Left: Order Summary */
        .order-summary-card { background: #f8fafc; padding: 40px; border-radius: 24px; border: 1px solid #e2e8f0; }
        .product-preview { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; }
        .product-img-box { width: 80px; height: 80px; background: white; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; justify-content: center; align-items: center; overflow: hidden; flex-shrink: 0; }
        .product-img-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
        
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; color: #64748b; font-size: 0.95rem; }
        .summary-row.total { font-size: 1.4rem; font-weight: 900; color: #0f172a; margin-top: 20px; padding-top: 20px; border-top: 2px dashed #cbd5e1; }
        
        /* Right: Payment Plan */
        .payment-plan-card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); border: 1px solid #f1f5f9; }
        
        .installment-row { display: flex; align-items: flex-start; position: relative; padding-bottom: 30px; }
        .installment-row:last-child { padding-bottom: 0; }
        
        /* Timeline line */
        .installment-row:not(:last-child)::before {
            content: ''; position: absolute; left: 11px; top: 24px; bottom: -5px; width: 2px; background: #e2e8f0;
        }
        
        .timeline-dot { width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0; border: 4px solid white; box-shadow: 0 0 0 1px #e2e8f0; margin-right: 20px; position: relative; z-index: 1; flex-shrink: 0; margin-top: 2px; }
        .installment-row.active .timeline-dot { background: #10b981; box-shadow: 0 0 0 1px #10b981; }
        
        .timeline-content { flex: 1; display: flex; justify-content: space-between; align-items: center; }
        .timeline-date { font-weight: 800; color: #0f172a; font-size: 1.1rem; margin-bottom: 4px; }
        .timeline-desc { font-size: 0.85rem; color: #64748b; font-weight: 500; }
        .timeline-amount { font-weight: 900; font-size: 1.2rem; color: #0f172a; text-align: right; }
        .timeline-fee { font-size: 0.75rem; color: #10b981; font-weight: 700; margin-top: 4px; }
        
        .pay-now-badge { background: #111827; color: white; font-size: 0.65rem; font-weight: 900; padding: 4px 8px; border-radius: 8px; vertical-align: middle; margin-left: 8px; letter-spacing: 0.5px; }

        @media (max-width: 900px) {
            .checkout-grid { grid-template-columns: 1fr; gap: 30px; }
            .order-summary-card, .payment-plan-card { padding: 24px; }
        }
    </style>
</head>
<body style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #fafafa; margin: 0; color: #0f172a;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="checkout-wall-container">
        
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="margin: 0 0 10px 0; font-size: 2.5rem; font-weight: 900; letter-spacing: -1px; color: #0f172a;">Confirm Your Payment Plan</h1>
            <p style="color: #64748b; margin: 0; font-size: 1.1rem;">You're almost there! Review your split payments below.</p>
        </div>

        <div class="checkout-grid">
            
            <!-- Left: Order Summary -->
            <div class="order-summary-card">
                <h3 style="margin: 0 0 24px 0; font-weight: 900; font-size: 1.2rem; color: #0f172a;">Order Summary</h3>
                
                <div class="product-preview">
                    <div class="product-img-box">
                        <img src="../../uploads/products/<?php echo htmlspecialchars($order['product_image']); ?>" alt="Product" onerror="this.src='https://via.placeholder.com/80?text=Item'">
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 1.1rem; color: #0f172a; margin-bottom: 4px;"><?php echo htmlspecialchars($order['product_name']); ?></div>
                        <div style="font-size: 0.85rem; color: #64748b; font-weight: 600;">Store: <?php echo htmlspecialchars($order['store_name'] ?? 'TQSEET Merchant'); ?></div>
                    </div>
                </div>
                
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span style="font-weight: 700; color: #0f172a;"><?php echo number_format($totalAmount, 2); ?> DH</span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span style="font-weight: 700; color: #10b981;">Free</span>
                </div>
                <div class="summary-row">
                    <span>Interest & Fees</span>
                    <span style="font-weight: 700; color: #10b981;">0.00 DH</span>
                </div>
                
                <div class="summary-row total">
                    <span>Total Amount</span>
                    <span><?php echo number_format($totalAmount, 2); ?> DH</span>
                </div>
            </div>

            <!-- Right: Payment Plan & Confirmation -->
            <div class="payment-plan-card">
                <h3 style="margin: 0 0 30px 0; font-weight: 900; font-size: 1.4rem; color: #0f172a; display: flex; justify-content: space-between; align-items: center;">
                    <span>4 Interest-Free Payments</span>
                    <span style="font-size: 1rem; color: #10b981; background: #ecfdf5; padding: 6px 12px; border-radius: 20px;">0% APR</span>
                </h3>
                
                <div style="margin-bottom: 40px;">
                    <?php foreach ($dates as $index => $date): ?>
                        <div class="installment-row <?php echo ($index === 0) ? 'active' : ''; ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div>
                                    <div class="timeline-date">
                                        <?php echo ($index === 0) ? "Pay Today" : date('M d, Y', strtotime($date)); ?>
                                        <?php if ($index === 0): ?>
                                            <span class="pay-now-badge">PAY NOW</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timeline-desc">Installment <?php echo $index + 1; ?> of 4</div>
                                </div>
                                <div style="text-align: right;">
                                    <div class="timeline-amount"><?php echo number_format($installmentAmount, 2); ?> DH</div>
                                    <?php if ($index !== 0): ?>
                                        <div class="timeline-fee">No fees</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; padding: 16px; border-radius: 12px; font-size: 0.85rem; line-height: 1.5; margin-bottom: 30px; font-weight: 500;">
                    <span style="font-size: 1rem;">ℹ️</span> By confirming, you agree to TQSEET's terms of service and authorize us to charge your payment method for these installments on the specified dates.
                </div>

                <!-- Confirm Form (Redirects to Credit Card Entry) -->
                <form action="checkout_payment.php" method="GET">
                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                    <input type="hidden" name="amount" value="<?php echo $installmentAmount; ?>">
                    <button type="submit" style="width: 100%; background: #0f172a; color: white; padding: 22px; border: none; border-radius: 16px; font-weight: 900; font-size: 1.15rem; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15); display: flex; justify-content: center; align-items: center; gap: 10px;">
                        <span>🔒</span> <?php echo $savedCard ? "Pay with card ending in " . $savedCard['last_four'] : "Confirm & Enter Payment Details"; ?>
                    </button>
                </form>

                <p style="text-align: center; margin-top: 24px;">
                    <a href="orders.php" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: color 0.2s;">Cancel checkout</a>
                </p>
            </div>

        </div>
    </div>

    <!-- Centralized Footer -->
    <div style="margin-top: 80px;">
        <?php include_once __DIR__ . "/../../includes/footer.php"; ?>
    </div>

</body>
</html>
