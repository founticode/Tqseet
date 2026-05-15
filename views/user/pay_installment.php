<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect the page
requireLogin();
$user = currentUser();

$installmentId = $_GET['id'] ?? 0;

$db = new Database();
$conn = $db->connect();

// 1. SECURITY: Verify this installment belongs to the logged-in user
// We JOIN with orders to check the user_id
$stmt = $conn->prepare("SELECT i.*, o.user_id, o.id as order_id 
                        FROM installments i 
                        JOIN orders o ON i.order_id = o.id 
                        WHERE i.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $installmentId, $user['id']);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("<div style='font-family:sans-serif; text-align:center; padding:100px;'>
            <h1>Invalid payment request.</h1>
            <a href='orders.php'>Back to My Shopping</a>
         </div>");
}

// 2. LOGIC: Update installment status to 'paid'
$stmt_pay = $conn->prepare("UPDATE installments SET status = 'paid' WHERE id = ?");
$stmt_pay->bind_param("i", $installmentId);
$stmt_pay->execute();

$orderId = $data['order_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Securing Payment</title>
    <!-- Professional Redirect after 2 seconds -->
    <meta http-equiv="refresh" content="2;url=view_installments.php?order_id=<?php echo $orderId; ?>&paid_success=1">
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
    </style>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #ffffff;">

    <div style="text-align: center;">
        <!-- Premium Loader -->
        <div style="width: 60px; height: 60px; border: 6px solid #f3f3f3; border-top: 6px solid #222; border-radius: 50%; animation: spin 0.8s cubic-bezier(0.68, -0.55, 0.27, 1.55) infinite; margin: 0 auto 30px auto;"></div>
        
        <h2 style="margin: 0; font-weight: 900; font-size: 1.8rem; color: #222; letter-spacing: -1px;">Simulating Secure Payment...</h2>
        <p style="color: #95a5a6; margin-top: 12px; font-weight: 500; animation: pulse 1.5s infinite;">Processing your transaction through TQSEET Gateway</p>
        
        <div style="margin-top: 50px; font-size: 0.75rem; color: #dfe6e9; font-weight: bold; text-transform: uppercase; letter-spacing: 2px;">
            🔒 End-to-End Encrypted
        </div>
    </div>

</body>
</html>
