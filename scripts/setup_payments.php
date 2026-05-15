<?php
require_once __DIR__ . "/../config/db.php";

$db = new Database();
$conn = $db->connect();

$sql = "CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_brand VARCHAR(50) DEFAULT 'Visa',
    last_four VARCHAR(4) NOT NULL,
    expiry VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'payment_methods' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
