<?php
require_once __DIR__ . "/../config/db.php";

$db = new Database();
$conn = $db->connect();

echo "Updating merchants table with status column...\n";

$sql = "ALTER TABLE merchants 
        ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER commission_rate";

if ($conn->query($sql)) {
    echo "Success! Status column added.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "Done.\n";
