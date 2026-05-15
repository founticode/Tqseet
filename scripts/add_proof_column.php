<?php
require_once __DIR__ . "/../config/db.php";
$db = new Database();
$conn = $db->connect();

$sql = "ALTER TABLE user_financials ADD COLUMN salary_proof VARCHAR(255) AFTER salary";
if ($conn->query($sql)) {
    echo "Successfully added salary_proof column.\n";
} else {
    echo "Error or column already exists: " . $conn->error . "\n";
}
