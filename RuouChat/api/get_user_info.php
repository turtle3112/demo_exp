<?php
require_once 'config.php';
$user_id = requireLogin();

$stmt = $conn->prepare("SELECT id, email, fullname, phone, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy user']);
}
?>