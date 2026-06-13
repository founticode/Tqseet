<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

requireRole("merchant");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch actual Merchant ID
$merchantData = ensureMerchantRecord($conn);
$merchantId = $merchantData['id'];

// Fetch all payment links for this merchant
$stmt = $conn->prepare("SELECT * FROM payment_links WHERE merchant_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $merchantId);
$stmt->execute();
$links = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Links - TQSEET</title>
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
    <style>
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px; letter-spacing: 0.05em; }
        .form-input { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.95rem; outline: none; transition: 0.2s; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        .form-input:focus { border-color: var(--primary-black); box-shadow: 0 0 0 3px rgba(17,24,39,0.1); }
        .alert { padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; font-size: 0.9rem; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        .copy-btn {
            background: #f3f4f6;
            color: var(--primary-black);
            border: 1px solid var(--border-color);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .copy-btn:hover { background: #e5e7eb; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include_once __DIR__ . "/../../includes/merchant_sidebar.php"; ?>

    <!-- Main Content -->
    <main class="main-content">
        
        <header class="page-header">
            <h1>Payment Links</h1>
            <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-muted);">
                Generate custom checkout URLs for WhatsApp or Email.
            </div>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2 class="card-title">Create a new link</h2>
            <form action="../../controllers/PaymentLinkController.php" method="POST" enctype="multipart/form-data" style="margin-top: 24px;">
                <input type="hidden" name="action" value="create">
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Order Description / Title</label>
                        <input type="text" name="title" class="form-input" required placeholder="e.g. Custom Furniture Order #1234">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Amount (DH)</label>
                        <input type="number" name="amount" class="form-input" required placeholder="0.00" min="50" step="0.01">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: flex-end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Additional Details (Optional)</label>
                        <input type="text" name="description" class="form-input" placeholder="e.g. Includes delivery and installation">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Product Image (Optional)</label>
                        <input type="file" name="image" class="form-input" style="padding: 9px 12px; font-size: 0.85rem;">
                    </div>
                </div>
                
                <div style="margin-top: 24px; text-align: right;">
                    <button type="submit" class="btn-black" style="padding: 12px 30px;">
                        Generate Secure Link
                    </button>
                </div>
            </form>
        </div>

        <h2 style="font-family: 'Outfit', sans-serif; margin: 32px 0 16px 0; font-size: 1.25rem;">Active Links</h2>

        <div class="portal-table-wrapper">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($links)): ?>
                        <tr>
                            <td colspan="5" style="padding: 60px; text-align: center; color: var(--text-muted);">
                                You haven't generated any payment links yet.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($links as $link): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--primary-black);"><?php echo htmlspecialchars($link['title']); ?></div>
                            </td>
                            <td style="font-weight: 700;">
                                <?php echo number_format($link['amount'], 2); ?> DH
                            </td>
                            <td>
                                <?php 
                                    $badgeClass = 'pending'; // Default
                                    if ($link['status'] === 'active') $badgeClass = 'success';
                                    if ($link['status'] === 'paid') $badgeClass = 'action';
                                    if ($link['status'] === 'expired') $badgeClass = '';
                                ?>
                                <?php if ($link['status'] === 'expired'): ?>
                                    <span class="status-badge" style="background: #fef2f2; color: #ef4444;">Expired</span>
                                <?php else: ?>
                                    <span class="status-badge <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($link['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                <?php echo date('M d, Y', strtotime($link['created_at'])); ?>
                            </td>
                            <td class="action-cell">
                                <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                    <?php 
                                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                                        $host = $_SERVER['HTTP_HOST'];
                                        $payUrl = $protocol . '://' . $host . '/views/public/pay_link.php?hash=' . $link['link_hash'];
                                    ?>
                                    <button onclick="copyToClipboard('<?php echo htmlspecialchars($payUrl); ?>')" class="copy-btn">
                                        🔗 Copy Link
                                    </button>
                                    
                                    <form action="../../controllers/PaymentLinkController.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="action" value="publish">
                                        <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                                        <button type="submit" style="background: #005a4e; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer;">+ Catalog</button>
                                    </form>
                                    
                                    <form action="../../controllers/PaymentLinkController.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                                        <button type="submit" class="btn-danger" style="padding: 6px 12px;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>

    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Payment link copied to clipboard!\n\n' + text);
        }, function(err) {
            alert('Could not copy text: ', err);
        });
    }
    </script>
</body>
</html>
