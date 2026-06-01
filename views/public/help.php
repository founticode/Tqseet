<?php
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - TQSEET</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .page-header-container { background: #0f172a; color: white; padding: 80px 20px; text-align: center; }
        .page-title { font-size: 3rem; font-weight: 900; margin-bottom: 16px; }
        .page-subtitle { color: #94a3b8; font-size: 1.1rem; max-width: 600px; margin: 0 auto; }
        
        .faq-container { max-width: 800px; margin: 60px auto; padding: 0 20px; }
        .faq-item { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .faq-question { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin-bottom: 12px; }
        .faq-answer { font-size: 1rem; color: #475569; line-height: 1.6; }
    </style>
</head>
<body style="background-color: #f8fafc;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="page-header-container">
        <h1 class="page-title">Help Center</h1>
        <p class="page-subtitle">Answers to the most common questions about splitting your payments.</p>
    </div>

    <div class="faq-container">
        <div class="faq-item">
            <div class="faq-question">What is TQSEET?</div>
            <div class="faq-answer">TQSEET is a payment service that allows you to buy what you need today and pay for it in four equal, interest-free installments over time.</div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">Is there really 0% interest?</div>
            <div class="faq-answer">Yes! We do not charge interest on your purchases. The price you see at the store is exactly the price you pay. We make our money by charging the merchant a small fee, not by charging you interest.</div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">How do I verify my account?</div>
            <div class="faq-answer">To get an approved credit limit, you must go to your User Settings and upload a valid government ID (like a CIN or Passport) along with a selfie. Our system will securely verify your identity.</div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">What happens if I miss a payment?</div>
            <div class="faq-answer">If you miss a scheduled payment, your account will be temporarily frozen to prevent further purchases, and a late fee may be applied to your balance. Please make sure you have sufficient funds on your scheduled payment dates.</div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">How do refunds work?</div>
            <div class="faq-answer">If you return an item, the merchant will notify us. We will instantly cancel your remaining installments and refund any money you've already paid back to your original card within 5-10 business days.</div>
        </div>
    </div>

    <?php include_once __DIR__ . "/../../includes/footer.php"; ?>

</body>
</html>
