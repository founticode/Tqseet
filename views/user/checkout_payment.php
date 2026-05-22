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

// --- FINAL STATUS CHECK ---
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
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 40px 20px;
            color: #0f172a;
        }

        .payment-container {
            width: 100%;
            max-width: 480px;
            background: white;
            padding: 48px;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.05), 0 0 0 1px rgba(0,0,0,0.02);
            box-sizing: border-box;
        }

        .header-badge {
            font-size: 0.75rem;
            background: #111827;
            color: white;
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 900;
            letter-spacing: 1px;
            margin-bottom: 16px;
            text-transform: uppercase;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .mockup-card {
            width: 100%;
            height: 220px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 24px;
            margin-bottom: 40px;
            padding: 32px;
            color: white;
            position: relative;
            box-sizing: border-box;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.4);
            overflow: hidden;
        }

        .mockup-card::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            top: -200px;
            right: -150px;
        }

        .mockup-brand { font-size: 1rem; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; color: #f8fafc; }
        .mockup-chip { width: 45px; height: 32px; background: linear-gradient(135deg, rgba(255,255,255,0.4), rgba(255,255,255,0.1)); border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); }
        .mockup-number { margin-top: 35px; font-size: 1.4rem; letter-spacing: 3px; font-family: 'Courier New', Courier, monospace; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.5); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .mockup-label { font-size: 0.65rem; opacity: 0.7; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; margin-bottom: 4px; }
        .mockup-value { font-size: 0.9rem; font-weight: 700; text-transform: uppercase; }

        .input-group { margin-bottom: 24px; }
        .input-label { display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px; letter-spacing: 1px; }
        
        .premium-input {
            width: 100%;
            padding: 16px 20px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            box-sizing: border-box;
            font-size: 1.05rem;
            font-family: inherit;
            font-weight: 600;
            color: #0f172a;
            outline: none;
            transition: all 0.2s;
        }

        .premium-input:focus {
            background: white;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .premium-input::placeholder { color: #cbd5e1; font-weight: 500; }

        .btn-submit {
            width: 100%;
            background: #111827;
            color: white;
            padding: 20px;
            border: none;
            border-radius: 16px;
            font-weight: 900;
            font-size: 1.15rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
            margin-top: 10px;
        }

        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.2); }
        .btn-submit:active { transform: translateY(0); }

        .secure-badge { text-align: center; margin-top: 32px; font-size: 0.85rem; color: #94a3b8; font-weight: 600; display: flex; justify-content: center; align-items: center; gap: 8px; }

        @media (max-width: 600px) {
            .payment-container { padding: 32px 24px; border-radius: 24px; }
            .mockup-card { height: 200px; padding: 24px; }
            .mockup-number { font-size: 1.4rem; letter-spacing: 3px; }
        }
    </style>
</head>
<body>

    <div class="payment-container">
        
        <div style="text-align: center; margin-bottom: 40px;">
            <div class="header-badge">Secure Checkout</div>
            <h1 style="margin: 0; font-size: 2.2rem; font-weight: 900; letter-spacing: -1px; color: #0f172a;"><?php echo $type; ?></h1>
            <p style="color: #64748b; margin-top: 12px; font-size: 1.1rem; font-weight: 500;">Total to pay: <strong style="color: #0f172a; font-weight: 900;"><?php echo number_format($amount, 2); ?> DH</strong></p>
        </div>

        <!-- Visual Credit Card Mockup -->
        <div class="mockup-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 2;">
                <div class="mockup-brand">TQSEET</div>
                <div class="mockup-chip"></div>
            </div>

            <div class="mockup-number" id="card-mockup-number" style="position: relative; z-index: 2;">
                <?php echo $savedCard ? "**** **** **** " . $savedCard['last_four'] : "**** **** **** 4242"; ?>
            </div>

            <div style="position: absolute; bottom: 32px; left: 32px; display: flex; gap: 40px; z-index: 2;">
                <div>
                    <div class="mockup-label">Card Holder</div>
                    <div class="mockup-value"><?php echo strtoupper(htmlspecialchars($user['name'])); ?></div>
                </div>
                <div>
                    <div class="mockup-label">Expires</div>
                    <div class="mockup-value" id="card-mockup-expiry">12/28</div>
                </div>
            </div>
            
            <!-- Mastercard Logo Mockup -->
            <div style="position: absolute; bottom: 32px; right: 32px; display: flex; z-index: 2;">
                <div style="width: 28px; height: 28px; background: #eb001b; border-radius: 50%; opacity: 0.9;"></div>
                <div style="width: 28px; height: 28px; background: #f79e1b; border-radius: 50%; margin-left: -14px; opacity: 0.9;"></div>
            </div>
        </div>

        <?php if ($savedCard): ?>
            <!-- Quick Pay Form -->
            <form action="process_simulated_payment.php" method="POST" onsubmit="return confirm('Use your saved card ending in <?php echo $savedCard['last_four']; ?> to pay <?php echo number_format($amount, 2); ?> DH?')">
                <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                <input type="hidden" name="installment_id" value="<?php echo $installmentId; ?>">
                <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 24px; border-radius: 16px; margin-bottom: 32px; text-align: center;">
                    <p style="margin: 0; color: #475569; font-size: 1rem; font-weight: 500;">Paying with saved <strong>Mastercard</strong> ending in <strong style="color: #0f172a;"><?php echo $savedCard['last_four']; ?></strong></p>
                </div>

                <button type="submit" class="btn-submit">
                    Confirm One-Click Payment
                </button>
                
                <div style="text-align: center; margin-top: 24px;">
                    <a href="?order_id=<?php echo $orderId; ?>&installment_id=<?php echo $installmentId; ?>&amount=<?php echo $amount; ?>&use_new=1" style="color: #3b82f6; text-decoration: none; font-size: 0.9rem; font-weight: 700; transition: color 0.2s;">Use a different card</a>
                </div>
            </form>
        <?php else: ?>
            <!-- Full Card Entry Form -->
            <form action="process_simulated_payment.php" method="POST" onsubmit="return confirm('Do you authorize TQSEET to charge <?php echo number_format($amount, 2); ?> DH from this card?')">
                <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                <input type="hidden" name="installment_id" value="<?php echo $installmentId; ?>">
                <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                
                <div class="input-group">
                    <label class="input-label">Card Number</label>
                    <input type="text" id="cc-number" name="card_number" placeholder="4242 4242 4242 4242" required class="premium-input" maxlength="19">
                </div>

                <div style="display: flex; gap: 24px; margin-bottom: 32px;">
                    <div style="flex: 1;">
                        <label class="input-label">Expiry Date</label>
                        <input type="text" id="cc-expiry" name="expiry" placeholder="MM/YY" required class="premium-input" maxlength="5">
                    </div>
                    <div style="flex: 1;">
                        <label class="input-label">Security Code</label>
                        <input type="password" name="cvv" placeholder="CVV" required class="premium-input" maxlength="4">
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    Authorize Payment
                </button>
            </form>
        <?php endif; ?>

        <div class="secure-badge">
            <span>🛡️</span> Your transaction is end-to-end encrypted.
        </div>
    </div>

    <!-- JavaScript Auto-Formatting for Inputs -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ccNumberInput = document.getElementById('cc-number');
            const ccExpiryInput = document.getElementById('cc-expiry');
            const mockupNumber = document.getElementById('card-mockup-number');
            const mockupExpiry = document.getElementById('card-mockup-expiry');

            // --- Card Number Auto-Spacing ---
            if(ccNumberInput) {
                ccNumberInput.addEventListener('input', function (e) {
                    // Remove all non-digits
                    let value = e.target.value.replace(/\D/g, '');
                    
                    // Add space after every 4 digits
                    let formattedValue = value.replace(/(.{4})/g, '$1 ').trim();
                    e.target.value = formattedValue;
                    
                    // Update mockup live
                    mockupNumber.textContent = formattedValue || '**** **** **** 4242';
                });
            }

            // --- Expiry Date Auto-Slash ---
            if(ccExpiryInput) {
                ccExpiryInput.addEventListener('input', function (e) {
                    // Remove all non-digits
                    let value = e.target.value.replace(/\D/g, '');
                    
                    // Auto add slash after MM
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    e.target.value = value;
                    
                    // Update mockup live
                    mockupExpiry.textContent = value || 'MM/YY';
                });

                // Handle backspace properly over the slash
                ccExpiryInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value.length === 3 && this.value.includes('/')) {
                        this.value = this.value.substring(0, 1);
                    }
                });
            }
        });
    </script>

</body>
</html>
