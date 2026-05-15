<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect the page
requireLogin();
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// NEW: Check for saved card
$stmt_card = $conn->prepare("SELECT last_four FROM payment_methods WHERE user_id = ? LIMIT 1");
$stmt_card->bind_param("i", $user['id']);
$stmt_card->execute();
$savedCard = $stmt_card->get_result()->fetch_assoc();

// If user wants to use a new card, ignore the saved one
if (isset($_GET['use_new'])) {
    $savedCard = null;
}

// --- NEW: FINAL STATUS CHECK ---
$stmt_check = $conn->prepare("SELECT status FROM user_financials WHERE user_id = ?");
$stmt_check->bind_param("i", $user['id']);
$stmt_check->execute();
$currentStatus = $stmt_check->get_result()->fetch_assoc()['status'] ?? 'none';

if ($currentStatus !== 'approved') {
    header("Location: dashboard.php?error=not_approved");
    exit;
}

// Get the context: Are we paying the first downpayment or a later installment?
$orderId = $_GET['order_id'] ?? 0;
$installmentId = $_GET['installment_id'] ?? 0;
$amount = $_GET['amount'] ?? 0;

// Type of payment for the title
$type = ($installmentId > 0) ? "Installment Payment" : "Downpayment (1/4)";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Secure Payment</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f0f2f5; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 40px 0;">

    <div style="width: 450px; background: white; padding: 45px; border-radius: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.08); margin: 20px 0;">
        
        <div style="text-align: center; margin-bottom: 35px;">
            <div style="font-size: 0.75rem; background: #222; color: white; display: inline-block; padding: 4px 12px; border-radius: 20px; font-weight: 800; letter-spacing: 1px; margin-bottom: 15px; text-transform: uppercase;">Secure Checkout</div>
            <h1 style="margin: 0; font-size: 1.8rem; font-weight: 900; letter-spacing: -1px;"><?php echo $type; ?></h1>
            <p style="color: #7f8c8d; margin-top: 10px; font-weight: 500;">Total to pay: <strong style="color: #222;"><?php echo number_format($amount, 2); ?> DH</strong></p>
        </div>

        <!-- Visual Credit Card Mockup (Klarna Style) -->
        <div style="width: 100%; height: 210px; background: linear-gradient(135deg, #1e1e1e 0%, #3a3a3a 100%); border-radius: 20px; margin-bottom: 35px; padding: 30px; color: white; position: relative; box-sizing: border-box; box-shadow: 0 10px 20px rgba(0,0,0,0.15); overflow: hidden;">
            <!-- Abstract background shape -->
            <div style="position: absolute; width: 300px; height: 300px; background: rgba(255,255,255,0.03); border-radius: 50%; top: -150px; right: -100px;"></div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="font-size: 0.9rem; font-weight: 900; letter-spacing: 2px; text-transform: uppercase;">TQSEET</div>
                <div style="width: 45px; height: 30px; background: rgba(255,255,255,0.2); border-radius: 5px;"></div> <!-- Chip mockup -->
            </div>

            <div style="margin-top: 45px; font-size: 1.5rem; letter-spacing: 4px; font-family: monospace; font-weight: bold;">
                <?php echo $savedCard ? "**** **** **** " . $savedCard['last_four'] : "**** **** **** 4242"; ?>
            </div>

            <div style="position: absolute; bottom: 30px; left: 30px; display: flex; gap: 40px;">
                <div>
                    <div style="font-size: 0.6rem; opacity: 0.6; text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Card Holder</div>
                    <div style="font-size: 0.85rem; font-weight: bold; margin-top: 4px;"><?php echo strtoupper(htmlspecialchars($user['name'])); ?></div>
                </div>
                <div>
                    <div style="font-size: 0.6rem; opacity: 0.6; text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Expires</div>
                    <div style="font-size: 0.85rem; font-weight: bold; margin-top: 4px;">12/28</div>
                </div>
            </div>
            
            <!-- Visa/Mastercard Logo Mockup -->
            <div style="position: absolute; bottom: 30px; right: 30px; display: flex; gap: 5px;">
                <div style="width: 25px; height: 25px; background: #eb001b; border-radius: 50%; opacity: 0.8;"></div>
                <div style="width: 25px; height: 25px; background: #f79e1b; border-radius: 50%; margin-left: -12px; opacity: 0.8;"></div>
            </div>
        </div>

        <?php if ($savedCard): ?>
            <!-- Quick Pay Form -->
            <form action="process_simulated_payment.php" method="POST" onsubmit="return confirm('Use your saved card ending in <?php echo $savedCard['last_four']; ?> to pay <?php echo number_format($amount, 2); ?> DH?')">
                <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                <input type="hidden" name="installment_id" value="<?php echo $installmentId; ?>">
                <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                
                <div style="background: #f8f9fa; border: 1px dashed #ced4da; padding: 20px; border-radius: 15px; margin-bottom: 30px; text-align: center;">
                    <p style="margin: 0; color: #636e72; font-size: 0.9rem;">Paying with saved <strong>Visa</strong> ending in <strong><?php echo $savedCard['last_four']; ?></strong></p>
                </div>

                <button type="submit" style="width: 100%; background: #222; color: white; padding: 20px; border: none; border-radius: 15px; font-weight: 900; font-size: 1.1rem; cursor: pointer; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                    Confirm One-Click Payment
                </button>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="?order_id=<?php echo $orderId; ?>&installment_id=<?php echo $installmentId; ?>&amount=<?php echo $amount; ?>&use_new=1" style="color: #007bff; text-decoration: none; font-size: 0.8rem; font-weight: bold;">Use a different card</a>
                </div>
            </form>
        <?php else: ?>
            <!-- Full Card Entry Form -->
            <form action="process_simulated_payment.php" method="POST" onsubmit="return confirm('Do you authorize TQSEET to charge <?php echo number_format($amount, 2); ?> DH from this card?')">
                <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                <input type="hidden" name="installment_id" value="<?php echo $installmentId; ?>">
                <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #95a5a6; margin-bottom: 8px; letter-spacing: 1px;">Card Number</label>
                    <input type="text" name="card_number" placeholder="4242 4242 4242 4242" required style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; box-sizing: border-box; font-size: 1rem; outline: none; transition: border-color 0.3s;" onfocus="this.style.borderColor='#222'">
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 35px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #95a5a6; margin-bottom: 8px; letter-spacing: 1px;">Expiry Date</label>
                        <input type="text" name="expiry" placeholder="MM/YY" required style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; box-sizing: border-box; font-size: 1rem; outline: none;" onfocus="this.style.borderColor='#222'">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #95a5a6; margin-bottom: 8px; letter-spacing: 1px;">Security Code</label>
                        <input type="password" name="cvv" placeholder="CVV" required style="width: 100%; padding: 15px; border: 2px solid #f1f3f5; border-radius: 12px; box-sizing: border-box; font-size: 1rem; outline: none;" onfocus="this.style.borderColor='#222'">
                    </div>
                </div>

                <button type="submit" style="width: 100%; background: #222; color: white; padding: 20px; border: none; border-radius: 15px; font-weight: 900; font-size: 1.1rem; cursor: pointer; transition: transform 0.2s, background 0.3s; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                    Authorize Payment
                </button>
            </form>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 25px; font-size: 0.8rem; color: #bdc3c7; font-weight: 600;">
            <span style="margin-right: 5px;">🛡️</span> Your transaction is end-to-end encrypted.
        </p>
    </div>

</body>
</html>
