<?php

$uploadDir = '/uploads/posts/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload'])) {
    $file = $_FILES['upload'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . uniqid() . '.' . $ext;
        $destination = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $url = '/uploads/posts/' . $filename;
            // CKEditor yêu cầu JSON response dạng: { "uploaded": true, "url": "..." }
            echo json_encode(['uploaded' => true, 'url' => $url]);
        } else {
            echo json_encode(['uploaded' => false, 'error' => ['message' => 'Không thể lưu file']]);
        }
    } else {
        echo json_encode(['uploaded' => false, 'error' => ['message' => 'Lỗi upload']]);
    }
} else {
    http_response_code(400);
    echo json_encode(['uploaded' => false, 'error' => ['message' => 'No file']]);
}
?>