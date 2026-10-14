<?php
require_once 'config.php';
$user_id = requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$address = trim($data['address'] ?? '');

if (empty($address)) {
    // Xóa địa chỉ (set NULL)
    $stmt = $conn->prepare("UPDATE users SET address = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Đã xóa địa chỉ']);
} else {
    $stmt = $conn->prepare("UPDATE users SET address = ? WHERE id = ?");
    $stmt->bind_param("si", $address, $user_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Cập nhật địa chỉ thành công']);
}
?>