<?php
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - TQSEET</title>
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
        <h1 class="page-title">Privacy Policy</h1>
        <p class="page-subtitle">Your privacy is critically important to us. Discover how we protect your data.</p>
    </div>

    <div class="content-container">
        <p><strong>Last Updated: June 2026</strong></p>

        <h2>1. Information We Collect</h2>
        <p>At TQSEET, we collect information that is necessary to provide you with secure Buy Now, Pay Later services. This includes:</p>
        <ul>
            <li><strong>Personal Information:</strong> Name, email address, phone number, and physical address.</li>
            <li><strong>Identity Verification Data:</strong> Government-issued ID scans and selfies (processed securely via our KYC providers).</li>
            <li><strong>Financial Information:</strong> Income details, employment status, and bank account/card details (processed securely via Stripe/Checkout.com).</li>
            <li><strong>Usage Data:</strong> How you interact with our website, including device IP, browser type, and navigation paths.</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <p>We use the data we collect primarily to process your BNPL applications and manage your installments. Specifically, we use your data to:</p>
        <ul>
            <li>Evaluate your creditworthiness and approve credit limits.</li>
            <li>Process payments, refunds, and collections.</li>
            <li>Prevent fraud, money laundering, and unauthorized account access.</li>
            <li>Send you payment reminders via email and SMS.</li>
        </ul>

        <h2>3. Data Sharing</h2>
        <p>We never sell your data to third parties. We only share data with trusted partners required to run our service:</p>
        <ul>
            <li>Payment gateways to process your installments.</li>
            <li>Credit bureaus to report positive repayment behavior or defaults.</li>
            <li>Legal authorities if compelled by a court order.</li>
        </ul>

        <h2>4. Your Rights</h2>
        <p>Under applicable data protection laws, you have the right to access, correct, or delete your personal data. You may request an export of your data or account deletion by contacting our Support team. Please note that active financial accounts cannot be deleted until all debts are settled.</p>

        <h2>5. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please reach out to us at privacy@tqseet.ma.</p>
    </div>

    <?php include_once __DIR__ . "/../../includes/footer.php"; ?>

</body>
</html>
