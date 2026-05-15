<?php
require_once __DIR__ . "/../config/db.php";
$db = new Database();
$conn = $db->connect();
$conn->query("DELETE FROM installments WHERE order_id = 18");
$conn->query("DELETE FROM orders WHERE id = 18");
echo "Cleaned up Order 18 and its installments.";
