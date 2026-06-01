<?php
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BNPL Agreement - TQSEET</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .page-header-container { background: #0f172a; color: white; padding: 80px 20px; text-align: center; }
        .page-title { font-size: 3rem; font-weight: 900; margin-bottom: 16px; }
        .page-subtitle { color: #94a3b8; font-size: 1.1rem; max-width: 600px; margin: 0 auto; }
        .content-container { max-width: 800px; margin: 60px auto; padding: 0 20px; font-family: 'Inter', sans-serif; color: #334155; line-height: 1.8; font-size: 1.05rem; }
        .content-container h2 { color: #0f172a; font-size: 1.8rem; margin-top: 40px; margin-bottom: 20px; font-weight: 800; }
        .content-container p { margin-bottom: 20px; }
        .content-container ul { padding-left: 20px; margin-bottom: 20px; }
        .content-container li { margin-bottom: 10px; }
    </style>
</head>
<body style="background-color: #f8fafc;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="page-header-container">
        <h1 class="page-title">BNPL Financial Agreement</h1>
        <p class="page-subtitle">Understanding your rights and obligations when splitting payments with TQSEET.</p>
    </div>

    <div class="content-container">
        <p><strong>Last Updated: June 2026</strong></p>

        <h2>1. The Credit Agreement</h2>
        <p>This BNPL (Buy Now, Pay Later) Agreement is a formal financial contract between you (the Customer) and TQSEET. By selecting TQSEET at checkout, you agree to repay the total cost of your purchase divided into four (4) equal installments.</p>

        <h2>2. Interest & Fees</h2>
        <p>TQSEET is proud to offer <strong>0% interest</strong> on all purchases. We do not charge interest on your installments. However, failure to pay an installment by its due date may result in a late fee of 50.00 DH per missed payment, up to a maximum of 25% of the total order value.</p>

        <h2>3. Payment Schedule</h2>
        <p>The standard payment schedule is as follows:</p>
        <ul>
            <li><strong>25% Downpayment:</strong> Paid immediately at the time of checkout.</li>
            <li><strong>2nd Installment:</strong> Due 30 days after checkout.</li>
            <li><strong>3rd Installment:</strong> Due 60 days after checkout.</li>
            <li><strong>4th Installment:</strong> Due 90 days after checkout.</li>
        </ul>
        <p>You may log into your dashboard at any time to pay off your installments early with no prepayment penalties.</p>

        <h2>4. Missed Payments & Default</h2>
        <p>If you fail to make a payment, your account will be temporarily suspended, meaning you cannot make new purchases. If an account remains unpaid for over 90 days, it will be marked as in Default. Defaulted accounts may be reported to credit bureaus and transferred to third-party collection agencies.</p>

        <h2>5. Refunds & Cancellations</h2>
        <p>If you return a product to the merchant according to their return policy, we will cancel your remaining unpaid installments. Any installments you have already paid will be refunded directly to your original payment method within 5-10 business days.</p>
    </div>

    <?php include_once __DIR__ . "/../../includes/footer.php"; ?>

</body>
</html>
