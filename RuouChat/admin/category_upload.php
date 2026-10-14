<?php
header('Content-Type: application/json');

// Thư mục lưu ảnh
$uploadDir = __DIR__ . '/../uploads/category/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_FILES['upload']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION);
    // Tạo tên file an toàn, không trùng
    $fileName = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $target = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['upload']['tmp_name'], $target)) {
        echo json_encode(['uploaded' => true, 'url' => '../uploads/category/' . $fileName]);
        exit;
    }
}
echo json_encode(['uploaded' => false, 'error' => 'Upload thất bại']);
?>