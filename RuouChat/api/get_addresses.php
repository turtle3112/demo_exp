<?php
require_once 'config.php';
$user_id = requireLogin();

$stmt = $conn->prepare("SELECT address FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo json_encode(['success' => true, 'address' => $user['address'] ?? '']);
?>