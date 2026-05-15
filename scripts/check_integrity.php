<?php
require_once __DIR__ . "/../config/db.php";
$db = new Database();
$conn = $db->connect();

echo "--- PRODUCTS ---\n";
$res = $conn->query("SELECT id, name, merchant_id FROM products");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n--- MERCHANTS ---\n";
$res2 = $conn->query("SELECT id, user_id, store_name FROM merchants");
while($row = $res2->fetch_assoc()) {
    print_r($row);
}
