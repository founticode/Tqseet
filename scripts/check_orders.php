<?php
require_once __DIR__ . "/../config/db.php";
$db = new Database();
$conn = $db->connect();
$res = $conn->query("SELECT o.id, o.status, p.name FROM orders o JOIN products p ON o.product_id = p.id");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
