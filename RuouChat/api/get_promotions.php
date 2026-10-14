<?php
require_once 'config.php';

$sql = "SELECT * FROM promotions WHERE active = 1 AND (expiry_date IS NULL OR expiry_date >= CURDATE()) ORDER BY id DESC";
$result = $conn->query($sql);
$promotions = [];
while ($row = $result->fetch_assoc()) {
    $promotions[] = $row;
}
echo json_encode(['success' => true, 'promotions' => $promotions]);
?>