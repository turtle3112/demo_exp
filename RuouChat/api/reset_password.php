<?php
header('Content-Type: application/json');
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = trim($input['token'] ?? '');
$new_password = $input['new_password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Token không hợp lệ.']);
    exit;
}
if (empty($new_password) || $new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu không khớp hoặc để trống.']);
    exit;
}
if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.']);
    exit;
}

// Lấy thông tin token
$stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Token không hợp lệ.']);
    exit;
}

// So sánh bằng PHP (không phụ thuộc múi giờ MySQL)
$expires = strtotime($user['reset_expires']);
$now = time();
if ($expires < $now) {
    echo json_encode(['success' => false, 'message' => 'Token đã hết hạn. Vui lòng yêu cầu lại.']);
    exit;
}

// Cập nhật mật khẩu
$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
$update->execute([$hashed, $user['id']]);

echo json_encode(['success' => true, 'message' => 'Mật khẩu đã được đặt lại thành công.']);
?>