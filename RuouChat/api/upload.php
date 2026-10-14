<?php
/**
 * API upload ảnh cho banner, sản phẩm, bài viết
 * Hỗ trợ kéo thả, nhận file từ FormData
 * Yêu cầu header X-Admin-Secret để xác thực
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'config.php';

define('ADMIN_SECRET', 'RuouchatAdmin2024!');



// Kiểm tra có file upload không
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Không có file hoặc upload lỗi']);
    exit;
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (jpeg, png, webp, gif)']);
    exit;
}

$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kích thước ảnh tối đa 5MB']);
    exit;
}

// Tạo thư mục uploads/banners nếu chưa có
$uploadDir = __DIR__ . '/../uploads/banners/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Tạo tên file an toàn
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'banner_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$targetPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Trả về đường dẫn URL tương đối (từ gốc web)
    $baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/..';
    $imageUrl = $baseUrl . $basePath . '/uploads/banners/' . $filename;
    
    echo json_encode(['success' => true, 'url' => $imageUrl]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể lưu file']);
}