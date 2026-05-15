<?php
require_once __DIR__ . "/../config/db.php";
$db = new Database();
$conn = $db->connect();
$conn->query("UPDATE orders SET status = 'active' WHERE id = 20");
echo "Fixed order ID 20 status to 'active'.";
