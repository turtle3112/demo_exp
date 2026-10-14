<?php
require_once 'config.php';
$user_id = requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)$data['id'];

$stmt = $conn->prepare("DELETE FROM favorites WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Đã xóa khỏi yêu thích']);
?>