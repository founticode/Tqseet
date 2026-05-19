<?php
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/auth.php";

$db = new Database();
$conn = $db->connect();

// Fetch all merchants to showcase as partner stores
$merchantsQuery = "
    SELECT DISTINCT m.id, m.store_name 
    FROM merchants m
    JOIN products p ON p.merchant_id = m.id
    ORDER BY m.store_name ASC
";
$merchantsResult = $conn->query($merchantsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Split Payments, Zero Interest</title>
</head>
<body style="background-color: #f8fafc;">

    <?php include_once __DIR__ . "/includes/navbar.php"; ?>

    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        <!-- Hero Landing Section -->
        <section class="hero-section">
            <div class="hero-content">
                <div class="hero-badge">Zero Interest, Always.</div>
                <h1 class="hero-title">Split your purchases in <span>4 easy payments</span></h1>
                <p class="hero-body">
                    TQSEET allows you to buy what you love today and spread the cost over time without any hidden fees, interest, or surprises. Simply pay in 4.
                </p>
                <div class="hero-buttons">
                    <a href="views/public/shop.php" class="hero-btn-solid">Explore Market</a>
                    <a href="#why-tqseet" class="hero-btn-outline">How It Works</a>
                </div>
            </div>
            
            <div class="hero-showcase">
                <div class="showcase-device">
                    <div class="showcase-screen">
                        <div class="mockup-header">
                            <div class="mockup-logo">TQSEET</div>
                            <div>LTE 🔋</div>
                        </div>
                        
                        <div class="mockup-purchase-card">
                            <div class="mockup-card-label">Latest Purchase</div>
                            <div class="mockup-card-title">iPhone 15 Pro Max</div>
                            <div class="mockup-card-price">12,450.00 <span>DH</span></div>
                            <div style="display: flex; justify-content: space-between; margin-top: 15px; font-size: 0.7rem; color: rgba(255,255,255,0.7);">
                                <span>Pay in 4</span>
                                <span style="color:#00f5c7; font-weight: 700;">3,112.50 DH / mo</span>
                            </div>
                        </div>
                        
                        <div class="mockup-btn">Confirm Payment Plan</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Partner Stores Section (Klarna Style) -->
        <section class="partner-section">
            <div class="partner-header-row">
                <h2 class="partner-heading">Pay with TQSEET at your favorite stores</h2>
                <a href="views/public/shop.php" class="partner-see-all">See all</a>
            </div>
            
            <div class="partner-grid">
                <?php if ($merchantsResult && $merchantsResult->num_rows > 0): ?>
                    <?php while($row = $merchantsResult->fetch_assoc()): ?>
                        <?php 
                        $storeName = $row['store_name'];
                        $merchantId = $row['id'];
                        
                        // Curate category cover images based on store names
                        $coverImage = "https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=400&auto=format&fit=crop&q=80"; // Default
                        if (stripos($storeName, 'game') !== false) {
                            $coverImage = "https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400&auto=format&fit=crop&q=80";
                        } elseif (stripos($storeName, 'kit') !== false) {
                            $coverImage = "https://images.unsplash.com/photo-1517841905240-472988babdf9?w=400&auto=format&fit=crop&q=80";
                        } elseif (stripos($storeName, 'design') !== false) {
                            $coverImage = "https://images.unsplash.com/photo-1626785774573-4b799315345d?w=400&auto=format&fit=crop&q=80";
                        }
                        ?>
                        <a href="views/public/shop.php?merchants[]=<?php echo $merchantId; ?>" class="partner-card" style="background-image: url('<?php echo $coverImage; ?>');">
                            <div class="partner-logo-circle">
                                <div class="partner-logo-text"><?php echo htmlspecialchars($storeName); ?></div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: white; border-radius: 24px; border: 1px solid rgba(15, 23, 42, 0.04);">
                        <div style="font-size: 3rem; margin-bottom: 20px;">🤝</div>
                        <h3 style="font-weight: 800; color: #0f172a; margin: 0 0 10px 0;">No partner stores yet</h3>
                        <p style="color: #64748b; font-size: 0.9rem; max-width: 400px; margin: 0 auto 20px auto;">
                            We are currently onboarding merchants. Check back shortly to shop at your favorite brand stores.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Why choose Tqseet Section -->
        <section id="why-tqseet" class="features-section">
            <h2 style="font-size: 2rem; font-weight: 900; color: #005a4e; margin: 0; text-align: center;">Why Choose TQSEET?</h2>
            <p style="color: #64748b; font-size: 0.95rem; text-align: center; margin: 10px 0 0 0;">Simplifying credit financing with customer-friendly values.</p>
            
            <div class="features-grid">
                <div class="feature-card-large">
                    <div style="font-size: 3rem; margin-bottom: 20px;">⚡</div>
                    <div>
                        <h3 style="font-size: 1.5rem; font-weight: 900; color: #0f172a; margin: 0 0 15px 0;">Instant Decisions</h3>
                        <p style="color: #64748b; font-size: 0.95rem; line-height: 1.6; margin: 0;">
                            Know in seconds if your credit profile is approved. Fill in your salary details, verify your identity card, and immediately access your BNPL limit without complex paperwork or banking queues.
                        </p>
                    </div>
                </div>
                
                <div class="feature-cards-stack">
                    <div class="feature-card-cyan">
                        <div style="font-size: 2rem; margin-bottom: 15px;">🗓️</div>
                        <h4 style="font-size: 1.15rem; font-weight: 800; color: #ffffff; margin: 0 0 8px 0;">Flexible Scheduling</h4>
                        <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.85rem; line-height: 1.5; margin: 0;">
                            Repayments are split automatically into 4 parts. The downpayment is paid on checkout, and the remaining 3 are charged monthly, with automatic notifications keeping you in check.
                        </p>
                    </div>
                    
                    <div class="feature-card-lavender">
                        <div style="font-size: 2rem; margin-bottom: 15px;">🔒</div>
                        <h4 style="font-size: 1.15rem; font-weight: 800; color: #3b2b85; margin: 0 0 8px 0;">Secure & Private</h4>
                        <p style="color: rgba(59, 43, 133, 0.8); font-size: 0.85rem; line-height: 1.5; margin: 0;">
                            We use bank-grade security protocols and encryption to safeguard your credit profile details, verifying your information strictly to secure your digital transactions.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Separator CTA Banner Section -->
        <section style="padding: 40px 0;">
            <div style="background: linear-gradient(135deg, #005a4e 0%, #013b33 100%); border-radius: 32px; padding: 60px; color: #ffffff; display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 90, 78, 0.1);">
                <div style="max-width: 60%; z-index: 2;">
                    <h2 style="font-size: 2.2rem; font-weight: 900; margin: 0 0 10px 0; letter-spacing: -1px;">Ready to split your first payment?</h2>
                    <p style="color: rgba(255, 255, 255, 0.85); font-size: 1.05rem; margin: 0; line-height: 1.5; font-weight: 550;">
                        Join thousands of smart shoppers in Morocco using TQSEET to purchase instantly and pay in 4 interest-free installments.
                    </p>
                </div>
                <div style="display: flex; gap: 15px; z-index: 2;">
                    <a href="views/auth/register.php" style="background-color: #00f5c7; color: #002d27; padding: 16px 28px; border-radius: 14px; font-weight: 800; font-size: 0.95rem; text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">Get Started</a>
                    <a href="views/public/shop.php" style="border: 2px solid #ffffff; color: #ffffff; padding: 14px 26px; border-radius: 14px; font-weight: 800; font-size: 0.95rem; text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">Browse Shop</a>
                </div>
                <!-- Abstract glowing green circle background -->
                <div style="position: absolute; right: -50px; bottom: -50px; width: 250px; height: 250px; border-radius: 50%; background: radial-gradient(circle, rgba(0,245,199,0.15) 0%, rgba(0,0,0,0) 70%); z-index: 1;"></div>
            </div>
        </section>

        <!-- Testimonials Section (Klarna Style) -->
        <section class="testimonials-section">
            <div class="testimonials-container">
                
                <div class="testimonials-info">
                    <div class="trustpilot-stars">
                        <div class="trustpilot-star-box">★</div>
                        <div class="trustpilot-star-box">★</div>
                        <div class="trustpilot-star-box">★</div>
                        <div class="trustpilot-star-box">★</div>
                        <div class="trustpilot-star-box trustpilot-star-half">★</div>
                    </div>
                    <div class="trustpilot-score-line">
                        TrustScore <span>4.8</span> | <span>12,450 reviews</span>
                    </div>
                    <h2 class="testimonials-title">People love TQSEET</h2>
                    <p class="testimonials-desc">
                        Over 250k people in Morocco love to shop and split payments with TQSEET anywhere.
                    </p>
                </div>
                
                <div class="testimonials-cards-row">
                    <!-- Card 1 (Dark purple) -->
                    <div class="testimonial-card testimonial-card-dark">
                        <div class="testimonial-card-stars">★★★★★</div>
                        <p class="testimonial-quote">
                            "Love the convenience and auto payments to repay back quickly."
                        </p>
                        <div class="testimonial-author">Rosa - 10th of June 2025</div>
                    </div>
                    
                    <!-- Card 2 (Lavender) -->
                    <div class="testimonial-card testimonial-card-light">
                        <div class="testimonial-card-stars">★★★★★</div>
                        <p class="testimonial-quote">
                            "Super convenient and easy!! Haven't had any issues getting approved, set-up or making payments. Would recommend"
                        </p>
                        <div class="testimonial-author">JC - 6th of June 2025</div>
                    </div>
                </div>
                
            </div>
        </section>

    </div>

    <!-- Premium Footer -->
    <footer class="footer-premium" style="margin-top: 0;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div class="footer-grid">
                <div class="footer-column">
                    <h2 style="font-weight: 900; font-size: 1.6rem; color: #00f5c7; margin: 0 0 20px 0; letter-spacing: -1px;">TQSEET</h2>
                    <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.85rem; line-height: 1.6; margin: 0;">
                        Spread costs, buy instantly, and stay in control of your budget. TQSEET is Morocco's modern BNPL solution matching your financial needs.
                    </p>
                </div>
                
                <div class="footer-column">
                    <h4>Platform</h4>
                    <ul>
                        <li><a href="views/public/shop.php">Browse Shop</a></li>
                        <li><a href="views/user/dashboard.php">My Dashboard</a></li>
                        <li><a href="views/auth/login.php">Become Merchant</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Use</a></li>
                        <li><a href="#">BNPL Agreement</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact Support</a></li>
                        <li><a href="#">Merchant Portal</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div>&copy; 2026 TQSEET Platform. Designed with premium user experience standards.</div>
                <div style="display: flex; gap: 10px; font-size: 1.2rem;">
                    <span>💳</span> <span>🔒</span> <span>🛡️</span>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
<?php
$conn->close();
?>
