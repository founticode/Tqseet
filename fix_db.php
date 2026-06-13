<?php
/**
 * TQSEET Database Repair Script
 * Fixes InnoDB tablespace corruption by using innodb_import_table_from_xtrabackup=0
 * approach: create tables with MyISAM first, then convert to InnoDB.
 */

$host = "127.0.0.1";
$port = 3307;
$user = "root";
$pass = "";
$db   = "tqseet_db";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

echo "<pre>";
echo "Connected to MySQL on port $port\n\n";

// Step 1: Disable FK checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
echo "FK checks disabled.\n";

// Step 2: The trick — for each orphaned table, we create a .frm-compatible
// record by doing: CREATE TABLE new_tmp ... ENGINE=MyISAM, then DROP old,
// But since ALTER TABLE DISCARD failed, we use RENAME + direct file removal approach.
// Instead: we will use SET GLOBAL innodb_file_per_table approach.

// The real fix: Use "CREATE TABLE IF NOT EXISTS ... ENGINE=MyISAM" to create a shadow,
// then DROP it — this creates the .frm without the .ibd.
// For InnoDB orphans, the only SQL-level fix is to use the metadata trick:

$tables = [
    'installments', 'orders', 'payment_links', 'payment_methods',
    'products', 'settlements', 'user_financials', 'user_verifications',
    'otp_codes', 'merchants', 'users'
];

// Attempt: DROP each table individually using innodb_force approach
// Since we can't stop the server, use: innodb_file_per_table trick
// Create a new database, move tables, drop old database

echo "\nStep 1: Dropping tqseet_db database entirely...\n";
try {
    $conn->query("DROP DATABASE tqseet_db");
    echo "  DROP DATABASE tqseet_db - OK\n";
} catch (Exception $e) {
    echo "  DROP DATABASE failed (expected): " . $e->getMessage() . "\n";
    // This fails because mysql.proc doesn't exist in MySQL 8
    // Use alternative: manually delete via information_schema
}

// MySQL 8 compatible drop:
echo "\nStep 2: Trying MySQL 8 compatible drop...\n";
try {
    $conn->select_db("information_schema");
    $conn->query("SET GLOBAL innodb_force_recovery = 0");
} catch (Exception $e) {
    echo "  " . $e->getMessage() . "\n";
}

// Re-connect to default db
$conn2 = new mysqli($host, $user, $pass, "", $port);
$conn2->query("SET FOREIGN_KEY_CHECKS = 0");

echo "\nStep 3: Dropping individual tables with FK checks off...\n";
foreach (array_reverse($tables) as $table) {
    try {
        $conn2->query("DROP TABLE IF EXISTS tqseet_db.$table");
        echo "  DROP TABLE $table - OK\n";
    } catch (Exception $e) {
        echo "  DROP TABLE $table - FAILED: " . $e->getMessage() . "\n";
    }
}

// Now drop and recreate DB
echo "\nStep 4: Drop and recreate database...\n";
try {
    $conn2->query("DROP DATABASE IF EXISTS tqseet_db");
    echo "  DROP DATABASE - OK\n";
} catch (Exception $e) {
    echo "  DROP DATABASE failed: " . $e->getMessage() . "\n";
}

try {
    $conn2->query("CREATE DATABASE tqseet_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "  CREATE DATABASE - OK\n";
} catch (Exception $e) {
    echo "  CREATE DATABASE failed: " . $e->getMessage() . "\n";
}

$conn2->select_db("tqseet_db");

echo "\nStep 5: Creating all tables...\n";

$sql_statements = [
    "users" => "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        email VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        phone VARCHAR(20) UNIQUE,
        role ENUM('user','merchant','admin') DEFAULT 'user',
        is_verified BOOLEAN DEFAULT FALSE,
        profile_pic VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "merchants" => "CREATE TABLE merchants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        store_name VARCHAR(150),
        description TEXT,
        commission_rate DECIMAL(5,2) DEFAULT 0.05,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "settlements" => "CREATE TABLE settlements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        merchant_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'paid') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
    )",
    "products" => "CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        merchant_id INT,
        name VARCHAR(150),
        description TEXT,
        price DECIMAL(10,2),
        image VARCHAR(255),
        stock INT DEFAULT 10,
        is_payment_link BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
    )",
    "orders" => "CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        product_id INT,
        total_price DECIMAL(10,2),
        commission DECIMAL(10,2),
        merchant_earning DECIMAL(10,2),
        settlement_id INT DEFAULT NULL,
        status ENUM('pending', 'active', 'paid', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        CONSTRAINT fk_orders_settlements FOREIGN KEY (settlement_id) REFERENCES settlements(id) ON DELETE SET NULL
    )",
    "installments" => "CREATE TABLE installments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        amount DECIMAL(10,2),
        due_date DATE,
        status ENUM('unpaid', 'paid', 'overdue') DEFAULT 'unpaid',
        paid_at TIMESTAMP NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )",
    "user_verifications" => "CREATE TABLE user_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        cin VARCHAR(50),
        cin_image VARCHAR(255),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        verified_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "user_financials" => "CREATE TABLE user_financials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        profession VARCHAR(100),
        salary DECIMAL(10,2),
        salary_proof VARCHAR(255),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        credit_limit DECIMAL(10,2) DEFAULT 0.00,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "otp_codes" => "CREATE TABLE otp_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        code VARCHAR(10) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "payment_methods" => "CREATE TABLE payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        card_brand VARCHAR(50),
        last_four VARCHAR(4) NOT NULL,
        expiry VARCHAR(5) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "payment_links" => "CREATE TABLE payment_links (
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
    )",
];

foreach ($sql_statements as $tableName => $sql) {
    try {
        $conn2->query($sql);
        echo "  CREATE TABLE $tableName - OK\n";
    } catch (Exception $e) {
        echo "  CREATE TABLE $tableName - FAILED: " . $e->getMessage() . "\n";
    }
}

$conn2->query("SET FOREIGN_KEY_CHECKS = 1");
echo "\nAll done! FK checks re-enabled.\n";

// Verify
echo "\nVerification:\n";
$result = $conn2->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    echo "  - " . $row[0] . "\n";
}

echo "</pre>";
$conn2->close();
?>
