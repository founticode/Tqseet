<?php
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";

$productId = $_GET['id'] ?? 0;

$db = new Database();
$conn = $db->connect();

// Fetch product details and link to the merchant's user name
$stmt = $conn->prepare("SELECT p.*, u.name as merchant_name 
                        FROM products p 
                        LEFT JOIN merchants m ON p.merchant_id = m.id 
                        LEFT JOIN users u ON m.user_id = u.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("<div style='font-family:sans-serif; text-align:center; padding:100px;'>
            <h1>Product not found.</h1>
            <a href='catalog.php'>Return to Catalog</a>
         </div>");
}

// Pay in 4 Model: Split into 4 payments
$monthlyPayment = $product['price'] / 4;

// KYC Check for logged in users
$is_fully_verified = false;
if (isLoggedIn() && currentUser()['role'] === 'user') {
    $userId = currentUser()['id'];
    
    // Check Identity
    $stmt_i = $conn->prepare("SELECT status FROM user_verifications WHERE user_id = ?");
    $stmt_i->bind_param("i", $userId);
    $stmt_i->execute();
    $id_status = $stmt_i->get_result()->fetch_assoc()['status'] ?? 'none';
    
    // Check Financials
    $stmt_f = $conn->prepare("SELECT status FROM user_financials WHERE user_id = ?");
    $stmt_f->bind_param("i", $userId);
    $stmt_f->execute();
    $fin_status = $stmt_f->get_result()->fetch_assoc()['status'] ?? 'none';
    
    if ($id_status === 'approved' && $fin_status === 'approved') {
        $is_fully_verified = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - TQSEET</title>
    <style>
        .product-container { max-width: 1200px; margin: 60px auto; padding: 0 20px; }
        .product-grid { display: flex; gap: 60px; margin-top: 30px; align-items: flex-start; }
        .product-image-box { flex: 1.2; min-width: 0; background: #f9fafb; padding: 40px; border-radius: 24px; border: 1px solid #e5e7eb; text-align: center; }
        .product-info-box { flex: 1; min-width: 0; }
        
        .product-image { max-width: 100%; max-height: 500px; object-fit: contain; border-radius: 12px; }
        
        @media (max-width: 900px) {
            .product-grid { flex-direction: column; gap: 30px; }
            .product-image-box { padding: 20px; }
            .product-info-box { width: 100%; }
        }
    </style>
</head>
<body style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; background: #fff; color: #111827;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="product-container">
        
        <a href="shop.php" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: #6b7280; font-weight: 600; font-size: 0.95rem; transition: color 0.2s;">
            <span style="font-size: 1.2rem;">←</span> Back to Shop
        </a>

        <div class="product-grid">
            
            <!-- Left Side: Visuals -->
            <div class="product-image-box">
                <img src="../../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="product-image"
                     onerror="this.src='https://via.placeholder.com/500?text=Product'">
            </div>

            <!-- Right Side: Interaction -->
            <div class="product-info-box">
                <div style="margin-bottom: 12px; color: #0ea5e9; font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; display: inline-flex; align-items: center; gap: 6px; background: #f0f9ff; padding: 6px 12px; border-radius: 20px;">
                    <span style="font-size: 1rem;">🛡️</span> Verified Merchant: <?php echo htmlspecialchars($product['merchant_name']); ?>
                </div>
                
                <h1 style="font-size: 2.8rem; font-weight: 900; margin: 0 0 16px 0; line-height: 1.15; letter-spacing: -1px; color: #111827;">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>
                
                <div style="font-size: 2.4rem; font-weight: 900; color: #111827; margin: 0 0 32px 0;">
                    <?php echo number_format($product['price'], 2); ?> <span style="font-size: 1.4rem; color: #6b7280;">DH</span>
                </div>

                <!-- BNPL Promo Banner -->
                <div style="padding: 24px; background: #f0fdf4; border-radius: 20px; border: 1px solid #bbf7d0; margin-bottom: 40px; display: flex; gap: 20px; align-items: center;">
                    <div style="width: 50px; height: 50px; background: #16a34a; border-radius: 14px; display: flex; justify-content: center; align-items: center; font-size: 1.5rem; color: white; flex-shrink: 0;">
                        🛍️
                    </div>
                    <div>
                        <div style="font-weight: 800; color: #166534; font-size: 1.1rem; margin-bottom: 4px; letter-spacing: -0.5px;">Buy now, pay later!</div>
                        <div style="font-size: 1rem; color: #15803d; font-weight: 500;">
                            Split into 4 monthly payments of <strong style="font-weight: 900;"><?php echo number_format($monthlyPayment, 2); ?> DH</strong>. No hidden fees.
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 40px;">
                    <h3 style="border-bottom: 2px solid #f3f4f6; padding-bottom: 12px; font-weight: 800; font-size: 1.1rem; color: #111827;">Product Description</h3>
                    <p style="line-height: 1.8; color: #4b5563; font-size: 1.05rem; margin-top: 20px;">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </p>
                </div>

                <!-- Call to Action -->
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php if (!isLoggedIn()): ?>
                        <a href="../auth/login.php" 
                           style="background: #111827; color: white; text-align: center; padding: 20px; text-decoration: none; border-radius: 16px; font-weight: 800; font-size: 1.1rem; transition: transform 0.2s; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);">
                            Login to Buy Now
                        </a>
                    <?php elseif (!$is_fully_verified): ?>
                        <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 24px; border-radius: 20px; text-align: center;">
                            <div style="font-weight: 900; color: #92400e; margin-bottom: 8px; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <span>⚠️</span> Verification Required
                            </div>
                            <p style="margin: 0 0 20px 0; font-size: 0.95rem; color: #92400e; line-height: 1.5; font-weight: 500;">To use TQSEET installments, you must complete your Identity and Financial profile.</p>
                            <a href="../user/settings.php#verification-section" 
                               style="display: block; background: #d97706; color: white; text-align: center; padding: 16px; text-decoration: none; border-radius: 12px; font-weight: 800; font-size: 1rem; transition: background 0.2s;">
                                Complete Verification →
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="../user/place_order.php?id=<?php echo $product['id']; ?>" 
                           style="background: #10b981; color: white; text-align: center; padding: 20px; text-decoration: none; border-radius: 16px; font-weight: 900; font-size: 1.2rem; box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4); transition: transform 0.2s, box-shadow 0.2s;">
                            Proceed to Checkout
                        </a>
                    <?php endif; ?>
                    
                    <div style="text-align: center; font-size: 0.9rem; color: #9ca3af; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span>🛡️</span> 100% Secure & Regulated BNPL
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Centralized Footer -->
    <div style="margin-top: 100px;">
        <?php include_once __DIR__ . "/../../includes/footer.php"; ?>
    </div>

</body>
</html>
