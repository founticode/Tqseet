<?php
require_once __DIR__ . "/config/db.php";
$db = new Database();
$conn = $db->connect();

// Add the column if it doesn't exist
$conn->query("ALTER TABLE products ADD COLUMN is_payment_link BOOLEAN DEFAULT FALSE");

// Retroactively mark old dummy products
$conn->query("UPDATE products SET is_payment_link = TRUE WHERE description LIKE 'Payment for %'");

echo "Database successfully patched!";
?>
