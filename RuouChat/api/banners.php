<?php
/**
 * api/banners.php
 * API quản lý banner quảng cáo
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'config.php';

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

define('ADMIN_SECRET', 'RuouchatAdmin2024!');

function sendError($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function sendSuccess($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}


$method = $_SERVER['REQUEST_METHOD'];

// GET - Lấy danh sách hoặc chi tiết banner theo id
if ($method === 'GET') {
    // Nếu có tham số id -> lấy chi tiết 1 banner
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
        if (!$stmt) sendError('Prepare failed: ' . $conn->error, 500);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $banner = $result->fetch_assoc();
        $stmt->close();
        
        if ($banner) {
            sendSuccess(['banner' => $banner]);
        } else {
            sendError('Banner not found', 404);
        }
        exit;
    }
    
    // Nếu không có id -> lấy danh sách banners
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    $position = isset($_GET['position']) ? trim($_GET['position']) : '';
    $active = isset($_GET['active']) ? intval($_GET['active']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

    $sql = "SELECT * FROM banners WHERE active = ?";
    $params = [$active];
    $types = "i";

    if ($category_id > 0) {
        $sql .= " AND (category_id = ? OR category_id IS NULL)";
        $params[] = $category_id;
        $types .= "i";
    }
    if (!empty($position)) {
        $sql .= " AND position = ?";
        $params[] = $position;
        $types .= "s";
    }
    $sql .= " ORDER BY sort_order ASC, id DESC LIMIT ?";
    $params[] = $limit;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    if (!$stmt) sendError('Prepare failed: ' . $conn->error, 500);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $banners = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    sendSuccess(['banners' => $banners]);
    exit;
}

// POST (thêm mới)
if ($method === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) sendError('Invalid JSON input', 400);

    $title = isset($input['title']) ? trim($input['title']) : '';
    $image_url = isset($input['image_url']) ? trim($input['image_url']) : '';
    $link = isset($input['link']) && $input['link'] !== '' ? trim($input['link']) : null;
    $category_id = isset($input['category_id']) && $input['category_id'] !== '' && $input['category_id'] !== null ? intval($input['category_id']) : null;
    $position = isset($input['position']) ? trim($input['position']) : 'hero_slider';
    $active = isset($input['active']) ? intval($input['active']) : 1;
    $sort_order = isset($input['sort_order']) ? intval($input['sort_order']) : 0;

    if (empty($image_url)) sendError('Image URL is required', 400);
    if (empty($position)) sendError('Position is required', 400);
    
    $validPositions = ['hero_slider', 'home_sidebar', 'home_top', 'product_sidebar', 'wine_sidebar', 'lyphale_hero', 'gift_hero'];
    if (!in_array($position, $validPositions)) {
        sendError('Invalid position value', 400);
    }

    $sql = "INSERT INTO banners (title, image_url, link, category_id, position, active, sort_order, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) sendError('Prepare failed: ' . $conn->error, 500);

    $stmt->bind_param("sssssii", $title, $image_url, $link, $category_id, $position, $active, $sort_order);
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        sendSuccess(['id' => $new_id]);
    } else {
        sendError('DB error: ' . $stmt->error, 500);
    }
    $stmt->close();
    exit;
}

// PUT (cập nhật)
if ($method === 'PUT') {

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['id'])) sendError('Missing banner id', 400);

    $id = intval($input['id']);
    $title = isset($input['title']) ? trim($input['title']) : '';
    $image_url = isset($input['image_url']) ? trim($input['image_url']) : '';
    $link = isset($input['link']) && $input['link'] !== '' ? trim($input['link']) : null;
    $category_id = isset($input['category_id']) && $input['category_id'] !== '' && $input['category_id'] !== null ? intval($input['category_id']) : null;
    $position = isset($input['position']) ? trim($input['position']) : 'hero_slider';
    $active = isset($input['active']) ? intval($input['active']) : 1;
    $sort_order = isset($input['sort_order']) ? intval($input['sort_order']) : 0;

    if (empty($image_url)) sendError('Image URL is required', 400);
    if (empty($position)) sendError('Position is required', 400);

    $sql = "UPDATE banners SET title=?, image_url=?, link=?, category_id=?, position=?, active=?, sort_order=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) sendError('Prepare failed: ' . $conn->error, 500);
    $stmt->bind_param("sssssiii", $title, $image_url, $link, $category_id, $position, $active, $sort_order, $id);
    if ($stmt->execute()) {
        sendSuccess();
    } else {
        sendError('DB error: ' . $stmt->error, 500);
    }
    $stmt->close();
    exit;
}

// DELETE
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) sendError('Missing or invalid banner id', 400);
    $stmt = $conn->prepare("DELETE FROM banners WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        sendSuccess();
    } else {
        sendError('DB error: ' . $stmt->error, 500);
    }
    $stmt->close();
    exit;
}

sendError('Method not allowed', 405);
?>