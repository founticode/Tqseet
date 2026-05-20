<?php
// Start session at the absolute top to avoid header errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

$hash = $_GET['hash'] ?? '';
if (empty($hash)) {
    die("Invalid or missing payment link.");
}

$db = new Database();
$conn = $db->connect();

// Fetch the payment link and associated merchant store name
$stmt = $conn->prepare("
    SELECT pl.*, m.store_name, m.description 
    FROM payment_links pl 
    JOIN merchants m ON pl.merchant_id = m.id 
    WHERE pl.link_hash = ?
");
$stmt->bind_param("s", $hash);
$stmt->execute();
$link = $stmt->get_result()->fetch_assoc();

if (!$link) {
    die("Payment link not found.");
}

if ($link['status'] !== 'active') {
    die("This payment link is no longer active (Status: " . htmlspecialchars($link['status']) . ").");
}

$installment = $link['amount'] / 4;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout - TQSEET</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f7f7f9;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #111827;
        }

        .checkout-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            box-sizing: border-box;
            text-align: center;
        }

        .brand-logo {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 900;
            color: #047857;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 40px;
        }

        .merchant-badge {
            background: #f3f4f6;
            color: #6b7280;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-block;
            margin-bottom: 16px;
        }

        .checkout-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin: 0 0 8px 0;
            line-height: 1.4;
        }

        .checkout-amount {
            font-family: 'Outfit', sans-serif;
            font-size: 3rem;
            font-weight: 800;
            color: #111827;
            margin: 0 0 40px 0;
            letter-spacing: -1px;
        }

        .split-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            text-align: left;
            margin-bottom: 30px;
        }

        .split-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed #cbd5e1;
        }
        .split-row:last-child {
            border-bottom: none;
        }

        .split-date {
            font-size: 0.9rem;
            font-weight: 600;
            color: #475569;
        }

        .split-amt {
            font-weight: 700;
            color: #111827;
        }

        .btn-pay {
            background: #111827;
            color: white;
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            display: block;
            box-sizing: border-box;
        }
        .btn-pay:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .secure-badge {
            margin-top: 20px;
            font-size: 0.75rem;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="checkout-container">
        
        <div class="brand-logo">
            <svg viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="#047857" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
            tqseet
        </div>

        <div class="merchant-badge">
            Paying <?php echo htmlspecialchars($link['store_name']); ?>
        </div>

        <h1 class="checkout-title">
            <?php echo htmlspecialchars($link['title']); ?>
        </h1>
        
        <?php if (!empty($link['description'])): ?>
            <p style="font-size: 0.9rem; color: #6b7280; margin: 0 0 24px 0; line-height: 1.5;">
                <?php echo htmlspecialchars($link['description']); ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($link['image'])): ?>
            <div style="margin-bottom: 30px;">
                <img src="../../uploads/products/<?php echo htmlspecialchars($link['image']); ?>" style="max-width: 100%; height: auto; border-radius: 16px; border: 1px solid #e2e8f0; max-height: 250px; object-fit: cover;">
            </div>
        <?php endif; ?>
        
        <div class="checkout-amount">
            <?php echo number_format($link['amount'], 2); ?> <span style="font-size: 1.5rem; color: #6b7280;">DH</span>
        </div>

        <div class="split-box">
            <div style="font-size: 0.8rem; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">
                Split in 4, interest-free
            </div>
            
            <div class="split-row">
                <span class="split-date">Today</span>
                <span class="split-amt"><?php echo number_format($installment, 2); ?> DH</span>
            </div>
            <div class="split-row">
                <span class="split-date">In 1 month</span>
                <span class="split-amt"><?php echo number_format($installment, 2); ?> DH</span>
            </div>
            <div class="split-row">
                <span class="split-date">In 2 months</span>
                <span class="split-amt"><?php echo number_format($installment, 2); ?> DH</span>
            </div>
            <div class="split-row">
                <span class="split-date">In 3 months</span>
                <span class="split-amt"><?php echo number_format($installment, 2); ?> DH</span>
            </div>
        </div>

        <?php
        $isLoggedIn = isLoggedIn();
        // If they are logged in, we send them to the processor. If not, they must login first.
        $checkoutUrl = $isLoggedIn 
            ? "../../controllers/PaymentLinkController.php?action=checkout&hash=" . urlencode($hash)
            : "../../views/auth/login.php?redirect=pay_link&hash=" . urlencode($hash);
        ?>

        <a href="<?php echo $checkoutUrl; ?>" class="btn-pay" style="text-align: center;">
            Continue to Payment
        </a>

        <div class="secure-badge">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            Encrypted & Secure Checkout
        </div>

    </div>

</body>
</html>
