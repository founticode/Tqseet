<?php
require_once "c:/xampp/htdocs/Tqseet/config/db.php";
$db = new Database();
$conn = $db->connect();

$sql1 = "
CREATE TABLE IF NOT EXISTS settlements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    merchant_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
)";

if ($conn->query($sql1)) {
    echo "Settlements table created.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$sql2 = "ALTER TABLE orders ADD COLUMN settlement_id INT NULL AFTER merchant_earning";
if ($conn->query($sql2)) {
    echo "Added settlement_id to orders.\n";
} else {
    echo "Error altering orders: " . $conn->error . "\n";
}

$sql3 = "ALTER TABLE orders ADD CONSTRAINT fk_order_settlement FOREIGN KEY (settlement_id) REFERENCES settlements(id) ON DELETE SET NULL";
if ($conn->query($sql3)) {
    echo "Added foreign key constraint.\n";
} else {
    echo "Error adding constraint: " . $conn->error . "\n";
}
