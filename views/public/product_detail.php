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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - TQSEET</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #fff; color: #333;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1200px; margin: 60px auto; padding: 0 20px;">
        
        <a href="catalog.php" style="text-decoration: none; color: #666; font-size: 0.9rem;">← Back to Catalog</a>

        <div style="display: flex; gap: 60px; margin-top: 30px; flex-wrap: wrap;">
            
            <!-- Left Side: Visuals -->
            <div style="flex: 1.2; min-width: 400px;">
                <div style="background: #fdfdfd; padding: 20px; border-radius: 20px; border: 1px solid #f1f1f1; text-align: center;">
                    <img src="../../uploads/products/<?php echo $product['image']; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         style="max-width: 100%; max-height: 500px; object-fit: contain;">
                </div>
            </div>

            <!-- Right Side: Interaction -->
            <div style="flex: 1; min-width: 350px;">
                <div style="margin-bottom: 10px; color: #007bff; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">
                    Verified Merchant: <?php echo htmlspecialchars($product['merchant_name']); ?>
                </div>
                <h1 style="font-size: 2.8rem; margin: 0; line-height: 1.1;"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div style="font-size: 2.2rem; font-weight: 800; color: #2ecc71; margin: 25px 0;">
                    <?php echo number_format($product['price'], 2); ?> DH
                </div>

                <div style="padding: 25px; background: #f0f7ff; border-radius: 12px; border-left: 5px solid #007bff; margin-bottom: 40px;">
                    <div style="font-weight: bold; color: #004085; font-size: 1.1rem; margin-bottom: 5px;">Buy now, pay later!</div>
                    <div style="font-size: 1rem; color: #0056b3;">
                        Split into 3 monthly payments of <strong><?php echo number_format($monthlyPayment, 2); ?> DH</strong>.
                    </div>
                </div>

                <div style="margin-bottom: 40px;">
                    <h3 style="border-bottom: 2px solid #f1f1f1; padding-bottom: 10px;">Product Description</h3>
                    <p style="line-height: 1.8; color: #555; font-size: 1.05rem;">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </p>
                </div>

                <!-- Call to Action -->
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <a href="../user/place_order.php?id=<?php echo $product['id']; ?>" 
                       style="background: #222; color: white; text-align: center; padding: 18px; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 1.1rem; transition: 0.2s;">
                        Proceed to Checkout
                    </a>
                    <div style="text-align: center; font-size: 0.85rem; color: #999;">
                        Secure Payment Powered by TQSEET
                    </div>
                </div>

            </div>
        </div>

    </div>

    <footer style="margin-top: 100px; border-top: 1px solid #eee; padding: 40px 0; text-align: center; color: #999; font-size: 0.9rem;">
        &copy; 2026 TQSEET Platform. All products are verified by our team.
    </footer>

</body>
</html>
