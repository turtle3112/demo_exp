<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

function createSlug($str) {
    $str = trim(mb_strtolower($str));
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if (!$conn || $conn->connect_error) sendError('DB error', 500);

$method = $_SERVER['REQUEST_METHOD'];

// GET
if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $featured = isset($_GET['featured']) ? intval($_GET['featured']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM other_products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        echo json_encode($product);
    } else {
        $sql = "SELECT * FROM other_products WHERE status = 1";
        if ($category) $sql .= " AND category = '$category'";
        if ($featured) $sql .= " AND featured = 1";
        $sql .= " ORDER BY created_at DESC LIMIT $limit";
        $result = $conn->query($sql);
        $products = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['products' => $products]);
    }
    exit;
}

// DELETE
if ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) sendError('Thiếu ID');
    $conn->query("DELETE FROM other_products WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

// POST & PUT
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) sendError('Dữ liệu không hợp lệ');

$id = intval($input['id'] ?? 0);
$name = trim($input['name'] ?? '');
$price = floatval($input['price'] ?? 0);
if ($name === '' || $price <= 0) sendError('Tên và giá bắt buộc');

$slug = !empty($input['slug']) ? $input['slug'] : createSlug($name);
$old_price = !empty($input['old_price']) ? floatval($input['old_price']) : null;
$category = $input['category'] ?? null;
$description = $input['description'] ?? '';
$status = intval($input['status'] ?? 1);
$featured = intval($input['featured'] ?? 0);
$main_image = $input['main_image'] ?? '';

if ($method === 'POST') {
    $stmt = $conn->prepare("
        INSERT INTO other_products (name, slug, price, old_price, category, description, main_image, status, featured)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssddsssii", $name, $slug, $price, $old_price, $category, $description, $main_image, $status, $featured);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else sendError('Lỗi DB: ' . $conn->error, 500);
    exit;
}

if ($method === 'PUT') {
    if (!$id) sendError('Thiếu ID');
    $stmt = $conn->prepare("
        UPDATE other_products SET
            name=?, slug=?, price=?, old_price=?, category=?, description=?, main_image=?, status=?, featured=?
        WHERE id=?
    ");
    $stmt->bind_param("ssddsssiii", $name, $slug, $price, $old_price, $category, $description, $main_image, $status, $featured, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else sendError('Lỗi DB: ' . $conn->error, 500);
    exit;
}

sendError('Method not allowed', 405);
?>