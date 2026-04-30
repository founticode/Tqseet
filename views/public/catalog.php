<?php
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";

$search = $_GET['search'] ?? '';

$db = new Database();
$conn = $db->connect();

// Fetch products (with search filter if provided)
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY created_at DESC");
    $searchTerm = "%$search%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT * FROM products ORDER BY created_at DESC";
    $result = $conn->query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Browse Products</title>
</head>
<body style="font-family: sans-serif; margin: 0; background: #f8f9fa; color: #333;">

    <?php include_once __DIR__ . "/../../includes/navbar.php"; ?>

    <div style="max-width: 1200px; margin: 50px auto; padding: 0 20px;">
        
        <div style="text-align: center; margin-bottom: 50px;">
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;">Explore Our Marketplace</h1>
            <p style="color: #666; font-size: 1.1rem;">Quality products with easy installment plans.</p>
            
            <!-- Search Form -->
            <form method="GET" style="margin-top: 30px; display: flex; justify-content: center; gap: 10px;">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search for products (e.g. iPhone, Laptop...)" 
                       style="width: 400px; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                <button type="submit" style="background: #222; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer;">
                    Search
                </button>
                <?php if (!empty($search)): ?>
                    <a href="catalog.php" style="padding: 12px; color: #666; text-decoration: none;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px;">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee;">
                        <!-- Product Image -->
                        <div style="height: 220px; background: #fff; overflow: hidden; border-bottom: 1px solid #f1f1f1;">
                            <img src="../../uploads/products/<?php echo $row['image']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                 style="width: 100%; height: 100%; object-fit: contain; padding: 10px;">
                        </div>

                        <!-- Product Content -->
                        <div style="padding: 20px;">
                            <h3 style="margin: 0; font-size: 1.2rem; color: #222;"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p style="color: #777; font-size: 0.9rem; line-height: 1.4; margin: 12px 0; height: 40px; overflow: hidden;">
                                <?php echo htmlspecialchars(substr($row['description'], 0, 75)) . '...'; ?>
                            </p>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                                <div>
                                    <span style="font-size: 1.4rem; font-weight: 800; color: #2ecc71;">
                                        <?php echo number_format($row['price'], 2); ?> DH
                                    </span>
                                    <div style="font-size: 0.75rem; color: #999; margin-top: 2px;">Available for BNPL</div>
                                </div>
                                <a href="product_detail.php?id=<?php echo $row['id']; ?>" 
                                   style="background: #007bff; color: white; padding: 10px 18px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 0.9rem; transition: 0.2s;">
                                    Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 100px; background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <img src="https://cdn-icons-png.flaticon.com/512/1170/1170577.png" alt="Empty" style="width: 80px; opacity: 0.3; margin-bottom: 20px;">
                    <h2 style="color: #666;">No products listed yet.</h2>
                    <p style="color: #999;">Check back later or become a merchant to sell your products!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer Simulation -->
    <footer style="margin-top: 100px; background: #333; color: white; padding: 50px 0; text-align: center;">
        <p>&copy; 2026 TQSEET BNPL Platform. All rights reserved.</p>
    </footer>

</body>
</html>
