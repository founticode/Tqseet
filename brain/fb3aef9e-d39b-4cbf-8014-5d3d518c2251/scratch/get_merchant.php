<?php
require_once __DIR__ . "/../../../config/db.php";
$db = new Database();
$conn = $db->connect();
$res = $conn->query("SELECT email FROM users WHERE role='merchant'");
while ($r = $res->fetch_assoc()) {
    echo "Email: " . $r['email'] . "\n";
}
$conn->close();
?>
