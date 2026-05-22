<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Merchants allowed!
requireRole("merchant");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch Merchant Status
$stmt_m = $conn->prepare("SELECT id, status, commission_rate FROM merchants WHERE user_id = ?");
$stmt_m->bind_param("i", $user['id']);
$stmt_m->execute();
$merchantData = $stmt_m->get_result()->fetch_assoc();
$merchantId = $merchantData['id'] ?? 0;
$status = $merchantData['status'] ?? 'pending';
$rate = $merchantData['commission_rate'] ?? 0.05;

// Fetch Sales Analytics
$stmt_sales = $conn->prepare("
    SELECT COUNT(o.id) as total_orders, 
           SUM(o.total_price) as gmv, 
           SUM(o.merchant_earning) as net_earnings 
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE p.merchant_id = ? AND o.status IN ('active', 'paid')
");
$stmt_sales->bind_param("i", $merchantId);
$stmt_sales->execute();
$salesData = $stmt_sales->get_result()->fetch_assoc();
$gmv = $salesData['gmv'] ?? 0;
$netEarnings = $salesData['net_earnings'] ?? 0;
$totalOrders = $salesData['total_orders'] ?? 0;

// Fetch validation status for merchant checklist
$stmt_v = $conn->prepare("SELECT * FROM user_verifications WHERE user_id = ?");
$stmt_v->bind_param("i", $user['id']);
$stmt_v->execute();
$verification = $stmt_v->get_result()->fetch_assoc();
$hasCIN = ($verification !== null && !empty($verification['cin']));

$stmt_f = $conn->prepare("SELECT * FROM user_financials WHERE user_id = ?");
$stmt_f->bind_param("i", $user['id']);
$stmt_f->execute();
$financial = $stmt_f->get_result()->fetch_assoc();
$hasFinancial = ($financial !== null && !empty($financial['salary_proof']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Dashboard - TQSEET</title>
    <!-- Use the centralized portal stylesheet -->
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
</head>
<body>

    <!-- Dynamically include the Sidebar -->
    <?php include_once __DIR__ . "/../../includes/merchant_sidebar.php"; ?>

    <!-- Main Content -->
    <main class="main-content">
        
        <header class="page-header">
            <h1>Home</h1>
            <div style="display: flex; gap: 12px;">
                <?php if ($status === 'approved'): ?>
                    <a href="add_product.php" class="btn-black" style="background: white; color: var(--primary-black); border: 1px solid var(--border-color);">
                        Add product
                    </a>
                    <a href="payment_links.php" class="btn-black">
                        Create payment link
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($status !== 'approved'): ?>
            <div class="card" style="border: 1px solid #fde68a;">
                <h2 class="card-title" style="color: #92400e;">Finish store setup</h2>
                <p class="card-desc">Complete your business verification to unlock live checkouts and start accepting split payments.</p>
                
                <div class="checklist-row">
                    <div class="checklist-info">
                        <h4>Identity Card (CIN)</h4>
                        <p>Required for official government verification.</p>
                    </div>
                    <?php if ($hasCIN): ?>
                        <span class="status-badge success">✓ Uploaded</span>
                    <?php else: ?>
                        <a href="settings.php" class="status-badge action">Upload</a>
                    <?php endif; ?>
                </div>

                <div class="checklist-row">
                    <div class="checklist-info">
                        <h4>Financial Statements (Optional)</h4>
                        <p>Highly recommended to speed up trust & payout limits.</p>
                    </div>
                    <?php if ($hasFinancial): ?>
                        <span class="status-badge success">✓ Uploaded</span>
                    <?php else: ?>
                        <a href="settings.php" class="status-badge action">Upload</a>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                    <span class="status-badge pending">Store Status: <?php echo ucfirst(htmlspecialchars($status)); ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="stats-grid" style="margin-bottom: 24px;">
                <div class="stat-box" style="background: #111827; color: white; border: none;">
                    <div class="stat-title" style="color: #9ca3af;">Net Earnings (After Fee)</div>
                    <div class="stat-value" style="color: white;"><?php echo number_format($netEarnings, 2); ?> <span style="font-size:1rem; color:#6b7280;">DH</span></div>
                </div>
                <div class="stat-box">
                    <div class="stat-title">Gross Volume (GMV)</div>
                    <div class="stat-value"><?php echo number_format($gmv, 2); ?> <span style="font-size:1rem; color:#9ca3af;">DH</span></div>
                </div>
                <div class="stat-box">
                    <div class="stat-title">Total Orders</div>
                    <div class="stat-value"><?php echo $totalOrders; ?></div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-title">Platform Fee</div>
                    <div class="stat-value" style="color: #10b981;"><?php echo ($rate * 100); ?>%</div>
                </div>
                <div class="stat-box">
                    <div class="stat-title">Store Status</div>
                    <div class="stat-value">Active</div>
                </div>
                <div class="stat-box">
                    <div class="stat-title">Integration</div>
                    <div class="stat-value" style="font-size: 1.2rem; display: flex; align-items: center; height: 100%;">
                        <a href="../public/business_docs.php" style="color: var(--primary-black); text-decoration: underline;">View API Keys</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="card-title">Getting Started</h2>
                <p class="card-desc">Your store is approved. Here are the best ways to start generating sales.</p>

                <div class="checklist-row">
                    <div class="checklist-info">
                        <h4>Add Products to Catalog</h4>
                        <p>List your inventory on the TQSEET marketplace for instant visibility.</p>
                    </div>
                    <a href="add_product.php" class="status-badge action">Create Product</a>
                </div>

                <div class="checklist-row">
                    <div class="checklist-info">
                        <h4>Create Payment Link <span class="badge-new">New</span></h4>
                        <p>Generate a secure link to send to a customer via WhatsApp or email.</p>
                    </div>
                    <a href="payment_links.php" class="status-badge action">Create Link</a>
                </div>
                
                <div class="checklist-row">
                    <div class="checklist-info">
                        <h4>Integrate API</h4>
                        <p>Connect your WooCommerce or custom storefront to our checkout gateway.</p>
                    </div>
                    <a href="../public/business_docs.php" class="status-badge action">Read Docs</a>
                </div>
            </div>

        <?php endif; ?>

    </main>

</body>
</html>
