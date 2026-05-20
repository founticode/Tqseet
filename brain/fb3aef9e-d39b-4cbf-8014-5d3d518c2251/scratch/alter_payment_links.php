<?php
require_once "c:/xampp/htdocs/Tqseet/config/db.php";
$db = new Database();
$conn = $db->connect();

$sql = "ALTER TABLE payment_links ADD COLUMN description TEXT NULL AFTER title, ADD COLUMN image VARCHAR(255) NULL AFTER description";

if ($conn->query($sql)) {
    echo "Columns added successfully.\n";
} else {
    echo "Error altering table: " . $conn->error . "\n";
}
