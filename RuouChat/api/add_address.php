<?php
require_once 'config.php';
$user_id = requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$fullname = trim($data['fullname'] ?? '');
$phone = trim($data['phone'] ?? '');
$address = trim($data['address'] ?? '');
$is_default = (int)($data['is_default'] ?? 0);

if (empty($fullname) || empty($phone) || empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

if ($is_default) {
    $update = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
    $update->bind_param("i", $user_id);
    $update->execute();
}

$stmt = $conn->prepare("INSERT INTO addresses (user_id, fullname, phone, address, is_default) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isssi", $user_id, $fullname, $phone, $address, $is_default);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Thêm địa chỉ thành công']);
?>