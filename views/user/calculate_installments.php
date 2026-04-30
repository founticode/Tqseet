<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Only logged-in users
requireLogin();
$user = currentUser();

$orderId = $_GET['order_id'] ?? 0;

$db = new Database();
$conn = $db->connect();

// 1. Fetch Order and Product Details
$stmt = $conn->prepare("SELECT o.*, p.name as product_name 
                        FROM orders o 
                        JOIN products p ON o.product_id = p.id 
                        WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $orderId, $user['id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h1>Order session expired or not found.</h1>
            <a href='../public/catalog.php'>Return to Catalog</a>
         </div>");
}

// 2. Logic: Split into 4 installments (Pay in 4 Model)
$totalAmount = $order['total_price'];
$installmentAmount = $totalAmount / 4;

// 3. Define Due Dates (Today, +1 month, +2 months, +3 months)
$dates = [
    date('Y-m-d'), // TODAY (Downpayment)
    date('Y-m-d', strtotime('+1 month')),
    date('Y-m-d', strtotime('+2 months')),
    date('Y-m-d', strtotime('+3 months'))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Payment Plan</title>
</head>
<body style="font-family: sans-serif; background: #f4f7f6; margin: 0; color: #333;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 650px; margin: 50px auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="margin: 0; font-size: 1.8rem; color: #222;">Confirm Your Payment Plan</h1>
            <p style="color: #666; margin-top: 10px;">Review the breakdown for: <strong><?php echo htmlspecialchars($order['product_name']); ?></strong></p>
        </div>
        
        <div style="background: #e9ecef; padding: 20px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <span style="font-weight: bold; color: #495057;">Total Amount</span>
            <span style="font-size: 1.5rem; font-weight: 900; color: #2ecc71;"><?php echo number_format($totalAmount, 2); ?> DH</span>
        </div>

        <div style="margin-bottom: 40px;">
            <h3 style="border-bottom: 2px solid #f8f9fa; padding-bottom: 15px; margin-bottom: 20px;">Monthly Payments</h3>
            
            <?php foreach ($dates as $index => $date): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid #f1f1f1;">
                    <div>
                        <div style="font-weight: bold; font-size: 1.1rem; color: #333;">
                            Installment #<?php echo $index + 1; ?>
                            <?php if ($index === 0): ?>
                                <span style="font-size: 0.7rem; background: #222; color: white; padding: 2px 6px; border-radius: 10px; vertical-align: middle; margin-left: 5px;">PAY NOW</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.9rem; color: #999; margin-top: 5px;">
                            Due: <?php echo ($index === 0) ? "Today" : date('d M Y', strtotime($date)); ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.2rem; font-weight: bold; color: #007bff;"><?php echo number_format($installmentAmount, 2); ?> DH</div>
                        <div style="font-size: 0.75rem; color: #28a745; font-weight: bold; margin-top: 3px;">0% Interest</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Information Box -->
        <div style="background: #fff8e1; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 8px; font-size: 0.9rem; margin-bottom: 30px;">
            ℹ️ By confirming, you agree to pay these installments on or before the due dates listed above.
        </div>

        <!-- Confirm Form -->
        <form action="save_installments.php" method="POST">
            <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
            <button type="submit" style="width: 100%; background: #222; color: white; padding: 20px; border: none; border-radius: 12px; font-weight: bold; font-size: 1.2rem; cursor: pointer; transition: 0.3s;">
                Confirm My Plan & Finalize Order
            </button>
        </form>

        <p style="text-align: center; margin-top: 25px;">
            <a href="../public/catalog.php" style="color: #666; text-decoration: none; font-size: 0.9rem;">Cancel and return to catalog</a>
        </p>
    </div>

</body>
</html>
