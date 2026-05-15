<?php
require_once __DIR__ . "/../config/db.php";
$db = new Database();
$conn = $db->connect();

$sql = "ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT 'default_avatar.png' AFTER is_verified";
if ($conn->query($sql)) {
    echo "Successfully added profile_pic column.\n";
} else {
    echo "Error or column already exists: " . $conn->error . "\n";
}
