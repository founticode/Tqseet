<?php
require_once __DIR__ . "/../../../config/db.php";

$db = new Database();
$conn = $db->connect();

echo "=== DESCRIBE ORDERS ===\n";
$res = $conn->query("DESCRIBE orders");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== DESCRIBE INSTALLMENTS ===\n";
$res = $conn->query("DESCRIBE installments");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

$conn->close();
