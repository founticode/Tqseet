<?php
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - TQSEET</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .page-header-container { background: #0f172a; color: white; padding: 80px 20px; text-align: center; }
        .page-title { font-size: 3rem; font-weight: 900; margin-bottom: 16px; }
        .page-subtitle { color: #94a3b8; font-size: 1.1rem; max-width: 600px; margin: 0 auto; }
        
        .contact-container { max-width: 600px; margin: 60px auto; padding: 40px; background: white; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .contact-item { display: flex; align-items: center; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #f1f5f9; }
        .contact-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .contact-icon { font-size: 2rem; margin-right: 20px; background: #f8fafc; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
        .contact-details h3 { margin: 0 0 4px 0; font-size: 1.1rem; color: #0f172a; }
        .contact-details p { margin: 0; color: #64748b; font-size: 0.95rem; }
        .contact-details a { color: #005a4e; font-weight: 700; text-decoration: none; }
    </style>
</head>
<body style="background-color: #f8fafc;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="page-header-container">
        <h1 class="page-title">Contact Support</h1>
        <p class="page-subtitle">We are here to help you 24/7. Reach out to our team via the channels below.</p>
    </div>

    <div class="contact-container">
        
        <div class="contact-item">
            <div class="contact-icon">📧</div>
            <div class="contact-details">
                <h3>Email Support</h3>
                <p>For general inquiries and account help:</p>
                <a href="mailto:support@tqseet.ma">support@tqseet.ma</a>
            </div>
        </div>

        <div class="contact-item">
            <div class="contact-icon">💼</div>
            <div class="contact-details">
                <h3>Merchant Support</h3>
                <p>For store integrations and payouts:</p>
                <a href="mailto:merchants@tqseet.ma">merchants@tqseet.ma</a>
            </div>
        </div>

        <div class="contact-item">
            <div class="contact-icon">📍</div>
            <div class="contact-details">
                <h3>Headquarters</h3>
                <p>TQSEET Financial Services<br>
                Casablanca Finance City, Tour CFC<br>
                Casablanca, Morocco</p>
            </div>
        </div>

    </div>

    <?php include_once __DIR__ . "/../../includes/footer.php"; ?>

</body>
</html>
