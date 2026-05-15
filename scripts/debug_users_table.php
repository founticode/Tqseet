<?php
require_once __DIR__ . "/../config/db.php";
$db = new Database();
$conn = $db->connect();
$res = $conn->query("DESCRIBE users");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
