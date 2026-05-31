<?php
require_once __DIR__ . "/config/db.php";
$db = new Database();
$conn = $db->connect();

// Add the column if it doesn't exist
try {
    $conn->query("ALTER TABLE products ADD COLUMN is_payment_link BOOLEAN DEFAULT FALSE");
} catch (mysqli_sql_exception $e) {
    // Column might already exist
}

// Retroactively mark old dummy products
try {
    $conn->query("UPDATE products SET is_payment_link = TRUE WHERE description LIKE 'Payment for %'");
} catch (mysqli_sql_exception $e) {
    // Query might fail or no-op
}

// Create settlements table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS settlements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    merchant_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
)");

// Add settlement_id to orders table if not exists
try {
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'settlement_id'");
    if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE orders ADD COLUMN settlement_id INT NULL");
        $conn->query("ALTER TABLE orders ADD CONSTRAINT fk_orders_settlements FOREIGN KEY (settlement_id) REFERENCES settlements(id) ON DELETE SET NULL");
    }
} catch (mysqli_sql_exception $e) {
    // Might already exist or fail
}

// Create payment_links table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS payment_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    merchant_id INT NOT NULL,
    link_hash VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'paid', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
)");

echo "Database successfully patched!";
?>
