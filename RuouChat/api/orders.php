<?php
require_once 'config.php';

$result = $conn->query("SELECT * FROM orders ORDER BY id DESC");
$orders = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($orders);
?>