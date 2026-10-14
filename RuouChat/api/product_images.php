<?php
// api/product_images.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . ($conn->connect_error ?? 'unknown')]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    if ($product_id <= 0) {
        echo json_encode([]);
        exit;
    }
    $stmt = $conn->prepare("SELECT id, product_id, image_url, is_main, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $images = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($images);
    exit;
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing image id']);
        exit;
    }
    $stmt = $conn->prepare("SELECT image_url FROM product_images WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $img = $stmt->get_result()->fetch_assoc();
    if ($img && !empty($img['image_url'])) {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . parse_url($img['image_url'], PHP_URL_PATH);
        if (file_exists($filePath)) @unlink($filePath);
    }
    $conn->query("DELETE FROM product_images WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>