<?php
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";

$db = new Database();
$conn = $db->connect();

// Get filter inputs
$search = $_GET['search'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$selectedMerchants = $_GET['merchants'] ?? [];

// Fetch list of merchants who have products
$merchantsQuery = "
    SELECT DISTINCT m.id, m.store_name 
    FROM merchants m
    JOIN products p ON p.merchant_id = m.id
    ORDER BY m.store_name ASC
";
$merchantsResult = $conn->query($merchantsQuery);

// Fetch counts of products per merchant
$countsQuery = "
    SELECT merchant_id, COUNT(*) as count 
    FROM products 
    GROUP BY merchant_id
";
$countsResult = $conn->query($countsQuery);
$merchantCounts = [];
if ($countsResult) {
    while ($row = $countsResult->fetch_assoc()) {
        $merchantCounts[$row['merchant_id']] = $row['count'];
    }
}

// Build SQL conditions
$conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if (!empty($minPrice)) {
    $conditions[] = "p.price >= ?";
    $params[] = floatval($minPrice);
    $types .= "d";
}

if (!empty($maxPrice)) {
    $conditions[] = "p.price <= ?";
    $params[] = floatval($maxPrice);
    $types .= "d";
}

if (!empty($selectedMerchants)) {
    $placeholders = implode(',', array_fill(0, count($selectedMerchants), '?'));
    $conditions[] = "p.merchant_id IN ($placeholders)";
    foreach ($selectedMerchants as $mid) {
        $params[] = intval($mid);
        $types .= "i";
    }
}

// Main query
$sql = "
    SELECT p.*, m.store_name 
    FROM products p 
    JOIN merchants m ON p.merchant_id = m.id
";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Apply sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $productsResult = $stmt->get_result();
} else {
    $productsResult = $conn->query($sql);
}

$totalProducts = $productsResult ? $productsResult->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Shop and Compare Prices</title>
</head>
<body style="background-color: #ffffff;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        
        <form method="GET" action="shop.php">
            <div class="shop-layout">
                
                <!-- Left Sidebar Filters -->
                <aside class="shop-sidebar">
                    
                    <!-- Search Input inside Sidebar -->
                    <div class="filter-section">
                        <h4 class="filter-title">Search</h4>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Keywords..." 
                               style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-family: inherit; font-size: 0.85rem; outline: none; box-sizing: border-box;">
                    </div>

                    <!-- Store Merchant Filters -->
                    <div class="filter-section">
                        <h4 class="filter-title">Stores</h4>
                        <div class="filter-option-list">
                            <?php if ($merchantsResult && $merchantsResult->num_rows > 0): ?>
                                <?php while($merchant = $merchantsResult->fetch_assoc()): ?>
                                    <?php 
                                    $mId = $merchant['id'];
                                    $isChecked = in_array($mId, $selectedMerchants) ? 'checked' : '';
                                    $count = $merchantCounts[$mId] ?? 0;
                                    ?>
                                    <label class="filter-checkbox-label">
                                        <div style="display: flex; align-items: center;">
                                            <input type="checkbox" name="merchants[]" value="<?php echo $mId; ?>" <?php echo $isChecked; ?> onchange="this.form.submit()">
                                            <span><?php echo htmlspecialchars($merchant['store_name']); ?></span>
                                        </div>
                                        <span class="filter-option-count"><?php echo $count; ?></span>
                                    </label>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p style="font-size: 0.8rem; color: #94a3b8; margin: 0;">No active stores.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Price Filter -->
                    <div class="filter-section">
                        <h4 class="filter-title">Price Range</h4>
                        <div class="price-inputs">
                            <div class="price-input-box">
                                <span>DH</span>
                                <input type="number" name="min_price" value="<?php echo htmlspecialchars($minPrice); ?>" placeholder="Min">
                            </div>
                            <div style="color: #94a3b8; font-weight: bold;">-</div>
                            <div class="price-input-box">
                                <span>DH</span>
                                <input type="number" name="max_price" value="<?php echo htmlspecialchars($maxPrice); ?>" placeholder="Max">
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px; display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1; padding: 10px; border-radius: 10px; font-size: 0.75rem; font-weight: 700; text-transform: none; box-shadow: none;">
                                Apply
                            </button>
                            <?php if (!empty($minPrice) || !empty($maxPrice) || !empty($search) || !empty($selectedMerchants)): ?>
                                <a href="shop.php" style="flex: 1; padding: 10px; border: 1.5px solid #cbd5e1; border-radius: 10px; text-decoration: none; color: #64748b; font-weight: 700; font-size: 0.75rem; text-align: center; background-color: #f8fafc; box-sizing: border-box;">
                                    Clear All
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </aside>

                <!-- Right Main Catalog Content -->
                <main class="shop-main">
                    
                    <!-- Klarna Hero Banner -->
                    <div class="shop-hero-banner">
                        <div class="shop-banner-content">
                            <h1 class="shop-banner-title">Get the best price</h1>
                            <p class="shop-banner-subtitle">
                                Find all the latest deals in one place and choose how you pay with TQSEET interest-free installments.
                            </p>
                        </div>
                        <div class="shop-banner-illustration">
                            <div class="shop-illustration-circle"></div>
                            <div class="shop-illustration-assets">
                                <span>👕</span>
                                <span>🧢</span>
                                <span>👟</span>
                            </div>
                        </div>
                    </div>

                    <!-- Top Controls Bar -->
                    <div class="shop-controls">
                        <div class="results-count">
                            Filter <span><?php echo $totalProducts; ?> products available</span>
                        </div>
                        <div>
                            <select name="sort" class="sort-select" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Sort by: Newest</option>
                                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Sort by price: Low to High</option>
                                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Sort by price: High to Low</option>
                            </select>
                        </div>
                    </div>

                    <!-- Klarna Product Grid -->
                    <div class="klarna-grid">
                        <?php if ($totalProducts > 0): ?>
                            <?php while($row = $productsResult->fetch_assoc()): ?>
                                <?php 
                                $price = $row['price'];
                                $origPrice = $price * 1.25; // Simulated discount crossed out price
                                $installmentAmount = $price / 4;
                                $rating = number_format(4.2 + (float)substr(md5($row['name']), 0, 1) * 0.05, 1); // Simulating realistic rating based on hash
                                ?>
                                <a href="product_detail.php?id=<?php echo $row['id']; ?>" class="klarna-card">
                                    <div class="klarna-img-box">
                                        <div class="klarna-discount-tag">-20%</div>
                                        <img src="../../uploads/products/<?php echo $row['image']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                             onerror="this.src='https://via.placeholder.com/220?text=Product'">
                                    </div>

                                    <h3 class="klarna-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                                    <div class="klarna-store"><?php echo htmlspecialchars(substr($row['description'], 0, 45)) . '...'; ?></div>

                                    <div class="klarna-price-row">
                                        <span class="klarna-current-price"><?php echo number_format($price, 2); ?> DH</span>
                                        <span class="klarna-original-price"><?php echo number_format($origPrice, 2); ?> DH</span>
                                        <span class="klarna-rating">★ <?php echo $rating; ?></span>
                                    </div>

                                    <div class="klarna-bnpl-text">
                                        Or 4 payments of <?php echo number_format($installmentAmount, 2); ?> DH
                                    </div>
                                    
                                    <div class="klarna-btn-row">
                                        <span class="klarna-store-count"><?php echo htmlspecialchars($row['store_name']); ?></span>
                                        <span style="color: #005a4e; font-weight: bold; font-size: 1.1rem;">→</span>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="grid-column: 1 / -1; text-align: center; padding: 80px 20px; background: #fafbfc; border-radius: 20px; border: 1px dashed #cbd5e1;">
                                <div style="font-size: 3rem; margin-bottom: 20px;">🔍</div>
                                <h3 style="font-weight: 800; color: #0f172a; margin: 0 0 10px 0;">No deals match your filters</h3>
                                <p style="color: #64748b; font-size: 0.9rem; max-width: 400px; margin: 0 auto 20px auto;">
                                    Try clearing your price range or selected stores to see the complete inventory list.
                                </p>
                                <a href="shop.php" class="btn btn-primary" style="display: inline-block; padding: 12px 24px; font-size: 0.8rem; border-radius: 10px;">
                                    Clear Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                </main>

            </div>
        </form>

    </div>

    <!-- Footer Premium -->
    <footer class="footer-premium" style="margin-top: 100px;">
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
                        <li><a href="shop.php">Browse Shop</a></li>
                        <li><a href="../user/dashboard.php">My Dashboard</a></li>
                        <li><a href="../auth/login.php">Become Merchant</a></li>
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
