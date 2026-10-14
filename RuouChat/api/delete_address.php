<?php
require_once 'config.php';
$user_id = requireLogin();

$stmt = $conn->prepare("UPDATE users SET address = NULL WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
echo json_encode(['success' => true, 'message' => 'Đã xóa địa chỉ']);
?>