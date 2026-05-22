<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/db.php";

// Protect: ONLY Merchants allowed!
requireRole("merchant");
$user = currentUser();

$db = new Database();
$conn = $db->connect();

// Fetch actual Merchant ID and Profile
$stmt_m = $conn->prepare("SELECT * FROM merchants WHERE user_id = ?");
$stmt_m->bind_param("i", $user['id']);
$stmt_m->execute();
$merchantData = $stmt_m->get_result()->fetch_assoc();
$merchantId = $merchantData['id'] ?? 0;
$merchantStatus = $merchantData['status'] ?? 'pending';

// SECURITY GATE
if ($merchantStatus !== 'approved') {
    header("Location: dashboard.php?error=pending_approval");
    exit;
}

// Fetch products for THIS merchant only, filtering out Payment Link temporary items
$stmt = $conn->prepare("SELECT * FROM products WHERE merchant_id = ? AND is_payment_link = FALSE ORDER BY created_at DESC");
$stmt->bind_param("i", $merchantId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog - TQSEET</title>
    <link rel="stylesheet" href="../../assets/css/merchant_portal.css">
</head>
<body>

    <!-- Sidebar -->
    <?php include_once __DIR__ . "/../../includes/merchant_sidebar.php"; ?>

    <!-- Main Content -->
    <main class="main-content">
        
        <header class="page-header">
            <h1>Catalog</h1>
            <a href="add_product.php" class="btn-black">
                Add Product
            </a>
        </header>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'product_has_orders'): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; font-size: 0.9rem; border: 1px solid #fecaca;">
                ❌ Cannot delete this product because it is tied to active or past customer orders.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; font-size: 0.9rem; border: 1px solid #a7f3d0;">
                ✅ Product deleted successfully.
            </div>
        <?php endif; ?>

        <div class="portal-table-wrapper">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" style="padding: 60px; text-align: center; color: var(--text-muted);">
                                Your inventory is empty. Start adding products!
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 16px;">
                                    <img src="../../uploads/products/<?php echo htmlspecialchars($row['image']); ?>" style="width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-color);">
                                    <div>
                                        <div style="font-weight: 700; color: var(--primary-black); font-size: 0.95rem;"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">ID: #<?php echo $row['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                Electronics <!-- Placeholder for now -->
                            </td>
                            <td style="font-weight: 700;">
                                <?php echo number_format($row['price'], 2); ?> DH
                            </td>
                            <td>
                                <span class="status-badge success" style="font-size: 0.7rem;">Active</span>
                            </td>
                            <td class="action-cell">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn-secondary">Edit</a>
                                    
                                    <form action="../../controllers/ProductController.php?action=delete" method="POST" onsubmit="return confirm('Are you sure you want to remove this product?')" style="margin: 0;">
                                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </main>

</body>
</html>
