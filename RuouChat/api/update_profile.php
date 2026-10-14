<?php
require_once 'config.php';
$user_id = requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$fullname = trim($data['fullname'] ?? '');
$phone = trim($data['phone'] ?? '');
$current_password = $data['current_password'] ?? '';
$new_password = $data['new_password'] ?? '';

// Lấy thông tin cũ
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$updateFields = [];
$params = [];
$types = "";

if (!empty($fullname)) {
    $updateFields[] = "fullname = ?";
    $params[] = $fullname;
    $types .= "s";
}
if (!empty($phone)) {
    $updateFields[] = "phone = ?";
    $params[] = $phone;
    $types .= "s";
}
if (!empty($new_password)) {
    if (empty($current_password) || !password_verify($current_password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng']);
        exit;
    }
    if (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự']);
        exit;
    }
    $updateFields[] = "password = ?";
    $params[] = password_hash($new_password, PASSWORD_DEFAULT);
    $types .= "s";
}

if (!empty($updateFields)) {
    $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $params[] = $user_id;
    $types .= "i";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
}

echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
?>