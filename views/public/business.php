<?php
require_once __DIR__ . "/../../includes/auth.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET for Business - Power Your Store's Growth</title>
    <!-- Google Fonts Outfit & Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }

        h1, h2, h3, h4, .outfit-font {
            font-family: 'Outfit', sans-serif;
        }

        /* Hero Banner */
        .tabby-hero {
            background-color: #005a4e;
            color: #ffffff;
            padding: 100px 0 120px 0;
            position: relative;
        }

        .tabby-hero::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background: radial-gradient(circle at 60% 50%, rgba(0, 245, 199, 0.12) 0%, transparent 65%);
            pointer-events: none;
        }

        .tabby-hero-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 80px;
            align-items: center;
        }

        .tabby-hero-left h1 {
            font-size: 4.2rem;
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -2px;
            margin-bottom: 24px;
        }

        .tabby-hero-left p {
            font-size: 1.25rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 40px;
            max-width: 580px;
        }

        .tabby-hero-left .btn-primary {
            background-color: #00f5c7;
            color: #003a31;
            padding: 18px 45px;
            border-radius: 100px;
            font-size: 1rem;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
            box-shadow: 0 10px 25px rgba(0, 245, 199, 0.15);
        }

        .tabby-hero-left .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0, 245, 199, 0.3);
            background-color: #12ffd1;
        }

        /* Mockup Phone CSS */
        .phone-container {
            position: relative;
            width: 320px;
            height: 640px;
            background-color: #000000;
            border-radius: 44px;
            padding: 12px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            margin: 0 auto;
            border: 4px solid #1e293b;
        }

        .phone-screen {
            background-color: #ffffff;
            width: 100%;
            height: 100%;
            border-radius: 36px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            font-family: 'Inter', sans-serif;
            color: #0f172a;
        }

        .phone-notch {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 110px;
            height: 25px;
            background-color: #000000;
            border-radius: 20px;
            z-index: 10;
        }

        /* Brand Stat Bar (Klarna style) */
        .klarna-stats-section {
            background-color: #10052b;
            color: #ffffff;
            padding: 60px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .klarna-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            text-align: center;
        }

        .klarna-stat-item h3 {
            font-size: 3.5rem;
            font-weight: 800;
            color: #b098ff;
            margin: 0 0 8px 0;
            letter-spacing: -1px;
        }

        .klarna-stat-item p {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            margin: 0;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Sell Anywhere (Omnichannel) */
        .omnichannel-section {
            padding: 120px 0;
            background-color: #ffffff;
        }

        .omnichannel-grid {
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            gap: 80px;
            align-items: center;
        }

        .omnichannel-feature-item {
            margin-bottom: 35px;
            display: flex;
            gap: 20px;
        }

        .omnichannel-icon {
            font-size: 1.5rem;
            background-color: #ecfdf5;
            color: #047857;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .omnichannel-text h4 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 6px 0;
        }

        .omnichannel-text p {
            font-size: 0.95rem;
            color: #64748b;
            line-height: 1.6;
            margin: 0;
        }

        /* Shopper Favorite Ways To Pay (Interactive Tabs) */
        .tabs-section {
            padding: 120px 0;
            background-color: #f8fafc;
        }

        .tabs-layout {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 80px;
            align-items: center;
            margin-top: 60px;
        }

        .tabs-nav {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .tab-btn {
            background: none;
            border: none;
            text-align: left;
            padding: 24px;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .tab-btn.active {
            background-color: #ffffff;
            border-color: #e2e8f0;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .tab-btn h4 {
            font-size: 1.3rem;
            font-weight: 800;
            color: #64748b;
            margin: 0 0 8px 0;
            transition: color 0.2s ease;
        }

        .tab-btn.active h4 {
            color: #005a4e;
        }

        .tab-btn p {
            font-size: 0.95rem;
            color: #94a3b8;
            line-height: 1.5;
            margin: 0;
            transition: color 0.2s ease;
        }

        .tab-btn.active p {
            color: #475569;
        }

        .tab-content-display {
            background-color: #ffffff;
            border-radius: 32px;
            padding: 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.03);
            min-height: 480px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .tab-badge {
            background-color: #eafaf1;
            color: #27ae60;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }

        /* Smart Solutions Grid (Klarna style) */
        .solutions-section {
            padding: 120px 0;
            background-color: #ffffff;
        }

        .solutions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-top: 60px;
        }

        .solution-card {
            background-color: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 520px;
        }

        .solution-mockup {
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            height: 280px;
            margin-top: 30px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .solution-card h4 {
            font-size: 1.3rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 10px 0;
        }

        .solution-card p {
            font-size: 0.95rem;
            color: #64748b;
            line-height: 1.6;
            margin: 0;
        }

        /* Bottom CTA styling */
        .bottom-cta {
            background: linear-gradient(135deg, #10052b 0%, #21183c 100%);
            color: #ffffff;
            padding: 120px 0;
            text-align: center;
        }

        .bottom-cta h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }

        .bottom-cta p {
            font-size: 1.15rem;
            color: rgba(255,255,255,0.8);
            max-width: 600px;
            margin: 0 auto 40px auto;
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .tabby-hero-grid, 
            .omnichannel-grid,
            .tabs-layout {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .solutions-grid,
            .klarna-stats-grid {
                grid-template-columns: 1fr;
            }
            .solution-card {
                height: 480px;
            }
            .tabby-hero {
                padding: 60px 0 80px 0;
            }
            .tabby-hero-left h1 {
                font-size: 3rem;
                letter-spacing: -1.5px;
            }
            .tabby-hero-left p {
                font-size: 1.1rem;
                margin-bottom: 30px;
            }
        }
        @media (max-width: 600px) {
            .tabby-hero {
                padding: 30px 0 50px 0;
                text-align: center;
            }
            .tabby-hero-left h1 {
                font-size: 2.2rem;
                line-height: 1.1;
                letter-spacing: -1px;
                margin-bottom: 16px;
            }
            .tabby-hero-left p {
                font-size: 1rem;
                line-height: 1.5;
                margin-bottom: 24px;
                color: rgba(255,255,255,0.8);
            }
            .tabby-hero-left .btn-primary {
                width: 100%;
                justify-content: center;
                padding: 16px 30px;
                font-size: 0.95rem;
            }
            .hero-badge {
                font-size: 0.7rem;
                padding: 8px 18px;
            }
            
            /* Phone mockup mobile */
            .phone-container {
                width: 260px;
                height: 520px;
                max-width: 100%;
                border-radius: 38px;
                padding: 10px;
            }
            .phone-screen {
                border-radius: 30px !important;
            }

            /* Stats section */
            .klarna-stats-section {
                padding: 40px 0;
            }
            .klarna-stat-item h3 {
                font-size: 2.2rem;
            }
            .klarna-stat-item p {
                font-size: 0.85rem;
            }

            /* Omnichannel section */
            .omnichannel-section {
                padding: 50px 0;
            }
            .omnichannel-right .serif-title {
                font-size: 2rem !important;
                letter-spacing: -0.5px !important;
            }
            .omnichannel-feature-item {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }

            /* Tabs section */
            .tabs-section {
                padding: 50px 0;
            }
            .tabs-section .serif-title,
            .solutions-section .serif-title {
                font-size: 2rem !important;
                letter-spacing: -0.5px !important;
            }
            .tab-content-display {
                padding: 25px !important;
            }
            .tab-content-display h3 {
                font-size: 1.6rem !important;
            }

            /* Solutions section */
            .solutions-section {
                padding: 50px 0;
            }
            .solution-card {
                height: auto;
                padding: 28px 20px;
            }
            .solution-card h4 {
                font-size: 1.05rem;
            }

            /* Bottom CTA */
            .bottom-cta {
                padding: 50px 20px;
            }
            .bottom-cta h2 {
                font-size: 1.9rem;
                letter-spacing: -0.5px;
            }
            .bottom-cta p {
                font-size: 1rem;
            }
            .cta-btns {
                flex-direction: column !important;
                gap: 10px !important;
            }
            .cta-btns a {
                width: 100%;
                box-sizing: border-box;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <!-- 1. Hero Section (Tabby B2B Inspired) -->
    <header class="tabby-hero">
        <div class="container tabby-hero-grid">
            <div class="tabby-hero-left">
                <div class="hero-badge" style="background: rgba(0, 245, 199, 0.1); color: #00f5c7; border-color: rgba(0, 245, 199, 0.3);">TQSEET FOR BUSINESS</div>
                <h1>Boost your business with TQSEET</h1>
                <p>Connect with Morocco's best shoppers by offering a flexible way to pay online and in-person while you get paid up front, in full.</p>
                <a href="/views/auth/register.php?role=merchant" class="btn-primary">
                    Get started
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
            
            <div class="tabby-hero-right">
                <!-- CSS Interactive Smartphone Mockup -->
                <div class="phone-container">
                    <div class="phone-notch"></div>
                    <div class="phone-screen" style="padding: 40px 20px 20px 20px; justify-content: space-between;">
                        <div>
                            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 800; margin-bottom: 5px;">NeoTech Store</div>
                            <div style="font-size: 1.6rem; font-weight: 900; color: #0f172a;">Pay with TQSEET</div>
                        </div>

                        <div style="background-color: #fafafa; border: 1px solid #f1f5f9; border-radius: 20px; padding: 20px; text-align: center;">
                            <div style="font-size: 0.8rem; color: #64748b; font-weight: 600; margin-bottom: 5px;">Installment Breakdown</div>
                            <div style="font-size: 2.2rem; font-weight: 900; color: #005a4e; letter-spacing: -1px;">1,800 <span style="font-size: 1rem;">MAD</span></div>
                            <div style="width: 100%; height: 6px; background-color: #e2e8f0; border-radius: 10px; margin: 20px 0; position: relative;">
                                <div style="position: absolute; left: 0; top: 0; height: 100%; width: 25%; background-color: #00f5c7; border-radius: 10px;"></div>
                                <div style="position: absolute; left: 25%; top: -3px; width: 12px; height: 12px; border-radius: 50%; background-color: #005a4e;"></div>
                            </div>
                            <div style="font-size: 0.85rem; color: #0f172a; font-weight: 700;">4 payments of 450 MAD/mo</div>
                            <div style="font-size: 0.75rem; color: #22c55e; font-weight: 700; margin-top: 5px;">Interest-free. No extra fees.</div>
                        </div>

                        <button style="width: 100%; background-color: #005a4e; color: white; border: none; padding: 15px; border-radius: 15px; font-weight: bold; font-size: 0.95rem;">Confirm Purchase</button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- 2. Brand Stats Section (Klarna Inspired) -->
    <section class="klarna-stats-section">
        <div class="container klarna-stats-grid">
            <div class="klarna-stat-item">
                <h3>100%</h3>
                <p>Upfront Payouts (Zero Default Risk)</p>
            </div>
            <div class="klarna-stat-item">
                <h3>0.00%</h3>
                <p>Interest & Fees for Shoppers</p>
            </div>
            <div class="klarna-stat-item">
                <h3>24-Hour</h3>
                <p>Direct Settlement to Your Bank</p>
            </div>
        </div>
    </section>

    <!-- 3. Sell Anywhere (Omnichannel Tabby Inspired) -->
    <section class="omnichannel-section">
        <div class="container omnichannel-grid">
            
            <div class="omnichannel-left">
                <!-- Phone Mockup showing Payment Method Select -->
                <div class="phone-container" style="box-shadow: 0 20px 40px rgba(0,0,0,0.12);">
                    <div class="phone-notch"></div>
                    <div class="phone-screen" style="padding: 40px 20px; justify-content: space-between; background-color: #fafafa;">
                        <div>
                            <div style="font-size: 1.1rem; font-weight: 800; margin-bottom: 25px; display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 1.4rem;">🛍️</span> Checkout
                            </div>
                            
                            <div style="background-color: white; border-radius: 16px; padding: 15px; margin-bottom: 15px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="radio" disabled>
                                    <span style="font-size: 0.85rem; font-weight: 700; color: #64748b;">Pay in Full (Credit Card)</span>
                                </div>
                                <span style="font-size: 1.2rem;">💳</span>
                            </div>

                            <div style="background-color: white; border-radius: 16px; padding: 20px; border: 2px solid #005a4e; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 8px 20px rgba(0,90,78,0.05);">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="radio" checked style="accent-color: #005a4e;">
                                    <div>
                                        <span style="font-size: 0.9rem; font-weight: 800; color: #005a4e; display: block;">4 Interest-Free Payments</span>
                                        <span style="font-size: 0.75rem; color: #64748b;">Split with TQSEET</span>
                                    </div>
                                </div>
                                <span style="font-weight: 900; color: #005a4e; font-size: 0.8rem;">TQSEET</span>
                            </div>
                        </div>

                        <div style="background-color: white; padding: 15px; border-radius: 16px; border: 1px solid #e2e8f0; text-align: center;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: #64748b;">Grand Total: 2,400 MAD</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="omnichannel-right">
                <h2 class="serif-title" style="font-size: 3rem; line-height: 1.1; margin-bottom: 20px; letter-spacing: -1px;">Sell anywhere, anytime</h2>
                <p style="color: #64748b; font-size: 1.1rem; line-height: 1.6; margin-bottom: 40px;">Give your shoppers the freedom to split payments across all sales channels seamlessly, increasing conversion rates across the board.</p>
                
                <div class="omnichannel-feature-item">
                    <div class="omnichannel-icon">🌐</div>
                    <div class="omnichannel-text">
                        <h4>Online Web Storefront</h4>
                        <p>Integrate our Javascript widgets and API onto your custom e-commerce or CMS site. Customers split instantly during web checkout.</p>
                    </div>
                </div>

                <div class="omnichannel-feature-item">
                    <div class="omnichannel-icon">📱</div>
                    <div class="omnichannel-text">
                        <h4>In-Person Stores</h4>
                        <p>Generate a static storefront QR code. In-store shoppers scan, authenticate, and split physical cart items in seconds.</p>
                    </div>
                </div>

                <div class="omnichannel-feature-item" style="margin-bottom: 0;">
                    <div class="omnichannel-icon">🔗</div>
                    <div class="omnichannel-text">
                        <h4>Shareable Payment Links</h4>
                        <p>Generate checkout sessions instantly inside your dashboard and email or text secure payment split links to buyers.</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- 4. Shoppers' Favorite Way To Pay (Interactive Tabs Klarna Inspired) -->
    <section class="tabs-section">
        <div class="container">
            <div style="text-align: center; max-width: 600px; margin: 0 auto 50px auto;">
                <h2 class="serif-title" style="font-size: 3rem; letter-spacing: -1.5px;">Shoppers' favorite way to pay</h2>
                <p style="color: #64748b; font-size: 1.1rem; margin-top: 15px;">Offer flexible split solutions tailored to your unique target audience and catalog prices.</p>
            </div>

            <div class="tabs-layout">
                
                <!-- Left Tabs Menu -->
                <div class="tabs-nav">
                    <button class="tab-btn active" onclick="switchTab(event, 'pay4')">
                        <h4>Split in 4</h4>
                        <p>Automatically divide cart totals into 4 equal interest-free installments billed every two weeks.</p>
                    </button>
                    <button class="tab-btn" onclick="switchTab(event, 'pay30')">
                        <h4>Pay in 30 Days <span style="font-size: 0.7rem; background-color: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 20px; font-weight: 700; margin-left: 8px; vertical-align: middle;">Coming Soon</span></h4>
                        <p>Allow shoppers to receive items, try them, and pay the invoice balance within 30 days. No upfront cost.</p>
                    </button>
                    <button class="tab-btn" onclick="switchTab(event, 'financing')">
                        <h4>Monthly Financing <span style="font-size: 0.7rem; background-color: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 20px; font-weight: 700; margin-left: 8px; vertical-align: middle;">Coming Soon</span></h4>
                        <p>Provide longer financing payment schedules (3 to 12 months) to make high-value purchases accessible.</p>
                    </button>
                </div>

                <!-- Right Display Container -->
                <div class="tab-content-display" id="tabContent">
                    <div>
                        <span class="tab-badge" id="tabBadge">POPULAR CONVERSION BOOSTER</span>
                        <h3 style="font-size: 2.2rem; font-weight: 800; color: #005a4e; margin: 0 0 15px 0; letter-spacing: -0.5px;" id="tabTitle">Split in 4 payments</h3>
                        <p style="color: #475569; font-size: 1.05rem; line-height: 1.7; margin: 0;" id="tabDesc">Divide payments into 4 simple chunks. Perfect for clothing, consumer goods, and fashion. Merchants get paid the total amount immediately upon checkout confirmation.</p>
                    </div>
                    
                    <div style="background-color: #fafafa; border: 1px solid #f1f5f9; border-radius: 20px; padding: 25px; margin-top: 40px;">
                        <h4 style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 800; margin-top: 0; margin-bottom: 15px;">Financing Terms Summary</h4>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                            <div>
                                <span style="font-size: 0.75rem; color: #94a3b8; display: block;">Default Interest</span>
                                <span style="font-weight: 800; font-size: 1.1rem; color: #0f172a;" id="tabInterest">0.00%</span>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; color: #94a3b8; display: block;">Setup Time</span>
                                <span style="font-weight: 800; font-size: 1.1rem; color: #0f172a;" id="tabSetup">Instant</span>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; color: #94a3b8; display: block;">Average Cart Value</span>
                                <span style="font-weight: 800; font-size: 1.1rem; color: #0f172a;" id="tabCart">500 - 8,000 DH</span>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; color: #94a3b8; display: block;">Offering Status</span>
                                <span style="font-weight: 800; font-size: 1.1rem; color: #22c55e;" id="tabStatus">Active Offering</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 5. Smart Solutions (Conversion Boosters Klarna Inspired) -->
    <section class="solutions-section">
        <div class="container">
            <div style="text-align: center; max-width: 600px; margin: 0 auto 50px auto;">
                <h2 class="serif-title" style="font-size: 3rem; letter-spacing: -1.5px;">Smart tools to maximize sales</h2>
                <p style="color: #64748b; font-size: 1.1rem; margin-top: 15px;">Integrate these visual high-converting assets across your product page and checkout streams.</p>
            </div>

            <div class="solutions-grid">
                
                <!-- Solution 1 -->
                <div class="solution-card">
                    <div>
                        <h4>One-Click Authentication</h4>
                        <p>Allow shoppers to instantly sign up or log in via OTP to split their carts with zero complex forms.</p>
                    </div>
                    <div class="solution-mockup" style="background-color: #fafafa; border-radius: 20px;">
                        <div style="text-align: center;">
                            <span style="font-size: 0.8rem; font-weight: 800; color: #64748b; display: block; margin-bottom: 10px;">SIGN IN WITH OTP</span>
                            <div style="display: flex; gap: 8px; justify-content: center; margin-bottom: 15px;">
                                <div style="width: 35px; height: 35px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; font-weight: 900; line-height: 35px;">4</div>
                                <div style="width: 35px; height: 35px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; font-weight: 900; line-height: 35px;">9</div>
                                <div style="width: 35px; height: 35px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; font-weight: 900; line-height: 35px;">2</div>
                                <div style="width: 35px; height: 35px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; font-weight: 900; line-height: 35px;">-</div>
                            </div>
                            <span style="font-size: 0.7rem; color: #22c55e; font-weight: 700;">✓ Code Verified</span>
                        </div>
                    </div>
                </div>

                <!-- Solution 2 -->
                <div class="solution-card">
                    <div>
                        <h4>Product Catalog Widget</h4>
                        <p>Calculate and display fractional rates on listing pages to build immediate user purchase intent.</p>
                    </div>
                    <div class="solution-mockup" style="background-color: #ffffff; border-radius: 20px;">
                        <div style="text-align: left; width: 100%;">
                            <div style="font-weight: 800; font-size: 0.95rem; margin-bottom: 5px;">Elite Tech Headset</div>
                            <div style="font-size: 1.25rem; font-weight: 900; color: #0f172a; margin-bottom: 15px;">1,200 MAD</div>
                            
                            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; background-color: #fafafa; display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 0.75rem; font-weight: 600; color: #475569;">Or 4 payments of <b>300 DH</b> with TQSEET</span>
                                <span style="font-size: 0.8rem; font-weight: 900; color: #005a4e;">Split 4</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Solution 3 -->
                <div class="solution-card">
                    <div>
                        <h4>Native Checkout Badge</h4>
                        <p>Keep users in your store flow with high-fidelity pay button modules representing safe payment split processing.</p>
                    </div>
                    <div class="solution-mockup" style="background-color: #fafafa; border-radius: 20px;">
                        <div style="width: 100%; text-align: center;">
                            <button style="width: 90%; background-color: #10052b; color: white; border: none; padding: 14px; border-radius: 12px; font-weight: 800; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 8px; margin: 0 auto;">
                                <span>Pay with</span>
                                <span style="font-weight: 900; color: #00f5c7;">TQSEET</span>
                            </button>
                            <span style="font-size: 0.7rem; color: #64748b; display: block; margin-top: 10px;">Interest-free split checkout</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 6. Bottom CTA Section -->
    <section class="bottom-cta">
        <div class="container">
            <h2>Ready to power your growth?</h2>
            <p>Join top Moroccan merchants integrating TQSEET to maximize conversions and drive repeat consumer visits.</p>
            <div style="display: flex; gap: 15px; justify-content: center;" class="cta-btns">
                <a href="/views/auth/register.php?role=merchant" class="btn" style="background-color: #00f5c7; color: #003a31; padding: 18px 45px; border-radius: 100px; font-weight: 800; font-size: 1rem; text-decoration: none;">Apply as Partner</a>
                <a href="/views/public/business_docs.php" class="btn" style="background-color: transparent; border: 2px solid rgba(255,255,255,0.2); color: white; padding: 18px 45px; border-radius: 100px; font-weight: 800; font-size: 1rem; text-decoration: none; transition: border-color 0.2s;">Read API Docs</a>
            </div>
        </div>
    </section>


    <!-- Lightweight Interactive Tab Switcher script -->
    <script>
        const tabData = {
            pay4: {
                badge: 'POPULAR CONVERSION BOOSTER',
                title: 'Split in 4 payments',
                desc: 'Divide payments into 4 simple chunks. Perfect for clothing, electronics, and fashion. Merchants get paid the total amount immediately upon checkout confirmation.',
                interest: '0.00%',
                setup: 'Instant',
                cart: '500 - 8,000 DH',
                status: 'Active Offering',
                statusColor: '#22c55e'
            },
            pay30: {
                badge: 'HIGH TRUST OPTION',
                title: 'Pay in 30 Days',
                desc: 'Let customers try items before paying. Zero upfront payment at checkout. Billed after 30 days. Perfect for high-end boutique stores to drive customer confidence.',
                interest: '0.00%',
                setup: 'Quick SDK',
                cart: '200 - 5,000 DH',
                status: 'Coming Soon',
                statusColor: '#64748b'
            },
            financing: {
                badge: 'HIGH VALUE ENABLER',
                title: 'Monthly Financing',
                desc: 'Flexible financing periods up to 12 months. Perfect for electronics, home appliances, and high-value orders. Helps shoppers buy larger baskets by lowering monthly load.',
                interest: '1.2% - 2.5%',
                setup: 'Dashboard',
                cart: '4,000 - 30,000 DH',
                status: 'Coming Soon',
                statusColor: '#64748b'
            }
        };

        function switchTab(event, tabKey) {
            // Remove active classes
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            // Add active to current
            event.currentTarget.classList.add('active');

            // Update DOM content with transition
            const content = tabData[tabKey];
            const displayEl = document.getElementById('tabContent');
            
            displayEl.style.opacity = '0.3';
            displayEl.style.transform = 'translateY(5px)';
            
            setTimeout(() => {
                document.getElementById('tabBadge').innerText = content.badge;
                document.getElementById('tabTitle').innerText = content.title;
                document.getElementById('tabDesc').innerText = content.desc;
                document.getElementById('tabInterest').innerText = content.interest;
                document.getElementById('tabSetup').innerText = content.setup;
                document.getElementById('tabCart').innerText = content.cart;
                
                const statusEl = document.getElementById('tabStatus');
                statusEl.innerText = content.status;
                statusEl.style.color = content.statusColor;
                
                displayEl.style.opacity = '1';
                displayEl.style.transform = 'translateY(0)';
            }, 150);
        }
    </script>

</body>
</html>
