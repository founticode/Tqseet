<?php
require_once __DIR__ . "/../../../config/db.php";

$db = new Database();
$conn = $db->connect();

echo "Altering orders table...\n";
$q1 = "ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'active', 'paid', 'cancelled') DEFAULT 'pending'";
if ($conn->query($q1)) {
    echo "Successfully altered orders.status ENUM!\n";
} else {
    echo "Failed to alter orders table: " . $conn->error . "\n";
}

echo "Fixing blank/null order statuses...\n";
$q2 = "UPDATE orders SET status = 'active' WHERE status = '' OR status IS NULL";
if ($conn->query($q2)) {
    echo "Successfully updated order statuses to 'active'!\n";
} else {
    echo "Failed to update order statuses: " . $conn->error . "\n";
}

$conn->close();
echo "Migration complete.\n";
