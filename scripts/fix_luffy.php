<?php
require_once __DIR__ . "/../config/db.php";
$db = new Database();
$conn = $db->connect();
$conn->query("UPDATE products SET merchant_id = 1 WHERE id = 1");
echo "Fixed Luffy product merchant association.";
