<?php
require_once __DIR__ . "/../config/db.php";
$db = new Database();
$conn = $db->connect();

$sql = "ALTER TABLE user_financials ADD COLUMN credit_limit DECIMAL(10,2) DEFAULT 0.00 AFTER status";
if ($conn->query($sql)) {
    echo "Successfully added credit_limit column.\n";
} else {
    echo "Error or column already exists: " . $conn->error . "\n";
}
