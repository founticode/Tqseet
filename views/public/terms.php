<?php
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Use - TQSEET</title>
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
        <h1 class="page-title">Terms of Use</h1>
        <p class="page-subtitle">The rules and guidelines for using the TQSEET platform.</p>
    </div>

    <div class="content-container">
        <p><strong>Last Updated: June 2026</strong></p>

        <h2>1. Acceptance of Terms</h2>
        <p>By accessing and using the TQSEET platform, you accept and agree to be bound by the terms and provision of this agreement. In addition, when using TQSEET's specific services, you shall be subject to any posted guidelines or rules applicable to such services.</p>

        <h2>2. User Accounts</h2>
        <p>To use our services, you must register for an account. You are responsible for maintaining the confidentiality of your account password and are fully responsible for all activities that occur under your account. You must notify us immediately of any unauthorized use of your account.</p>

        <h2>3. Eligibility</h2>
        <p>To use TQSEET, you must be at least 18 years old, reside in a supported region (Morocco), and possess a valid mobile number and email address. We reserve the right to refuse service to anyone at any time.</p>

        <h2>4. Prohibited Conduct</h2>
        <p>You agree not to:</p>
        <ul>
            <li>Use the platform for any illegal activities or to purchase illegal goods.</li>
            <li>Attempt to bypass our security measures or identity verification processes.</li>
            <li>Provide false, inaccurate, or misleading financial information.</li>
            <li>Use the service to deliberately accumulate debt with no intention of repayment.</li>
        </ul>

        <h2>5. Limitation of Liability</h2>
        <p>TQSEET provides a payment platform. We are not responsible for the quality, safety, or legality of the goods or services purchased from our partnered merchants. Any disputes regarding the product itself must be resolved directly with the merchant.</p>
        
        <h2>6. Modifications</h2>
        <p>We may revise these terms of use for our platform at any time without notice. By using this website you are agreeing to be bound by the then current version of these Terms and Conditions of Use.</p>
    </div>

    <?php include_once __DIR__ . "/../../includes/footer.php"; ?>

</body>
</html>
