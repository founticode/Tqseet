<?php
require_once __DIR__ . "/../config/db.php";

$db = new Database();
$conn = $db->connect();

echo "Updating merchants table...\n";

$sql = "ALTER TABLE merchants 
        ADD COLUMN IF NOT EXISTS store_name VARCHAR(150) AFTER user_id,
        ADD COLUMN IF NOT EXISTS description TEXT AFTER store_name";

if ($conn->query($sql)) {
    echo "Success! Columns added.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Also update existing merchants to use their user name as default store name
$conn->query("UPDATE merchants m JOIN users u ON m.user_id = u.id SET m.store_name = u.name WHERE m.store_name IS NULL OR m.store_name = ''");

echo "Done.\n";
