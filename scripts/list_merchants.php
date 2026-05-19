<?php
require_once __DIR__ . "/../config/db.php";
$db = new Database();
$conn = $db->connect();
$res = $conn->query("SELECT m.user_id, m.store_name, u.name, m.status FROM merchants m JOIN users u ON m.user_id = u.id");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
