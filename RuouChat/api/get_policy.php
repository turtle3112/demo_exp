<?php
require_once 'config.php';

header('Content-Type: application/json');

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu tham số slug']);
    exit;
}

$stmt = $conn->prepare("SELECT title, content FROM policies WHERE slug = ? AND status = 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$policy = $result->fetch_assoc();

if (!$policy) {
    http_response_code(404);
    echo json_encode(['error' => 'Không tìm thấy nội dung chính sách']);
    exit;
}

echo json_encode([
    'success' => true,
    'title' => $policy['title'],
    'content' => $policy['content']
]);
?>