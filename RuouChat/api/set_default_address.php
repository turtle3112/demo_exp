<?php
require_once 'config.php';
$user_id = requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)$data['id'];

$conn->begin_transaction();
$reset = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
$reset->bind_param("i", $user_id);
$reset->execute();

$set = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
$set->bind_param("ii", $id, $user_id);
$set->execute();
$conn->commit();

echo json_encode(['success' => true, 'message' => 'Đã đặt làm mặc định']);
?>