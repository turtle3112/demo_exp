<?php
// api/categories.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

function createSlug($str) {
    $str = trim(mb_strtolower($str));
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

function getUniqueSlug($slug, $conn, $excludeId = 0) {
    $original = $slug;
    $counter = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
        $stmt->bind_param("si", $slug, $excludeId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0) break;
        $slug = $original . '-' . $counter++;
    }
    return $slug;
}

// Kiểm tra tên danh mục đã tồn tại chưa
function checkNameExists($name, $conn, $excludeId = 0) {
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
    $stmt->bind_param("si", $name, $excludeId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

// GET: danh sách hoặc chi tiết
if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        echo json_encode($category);
    } else {
        $result = $conn->query("SELECT * FROM categories ORDER BY id DESC");
        $categories = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($categories);
    }
    exit;
}

// DELETE
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing ID']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    echo json_encode(['success' => $success]);
    exit;
}

// POST & PUT
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$name = trim($input['name'] ?? '');
if (!$name) {
    echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống']);
    exit;
}

$slug = !empty($input['slug']) ? createSlug($input['slug']) : createSlug($name);
$description = $input['description'] ?? '';
$image = $input['image'] ?? '';
$seo_title = $input['seo_title'] ?? '';
$seo_description = $input['seo_description'] ?? '';
$status = isset($input['status']) ? (int)$input['status'] : 1;

if ($method === 'POST') {
    // Kiểm tra tên đã tồn tại
    if (checkNameExists($name, $conn)) {
        echo json_encode(['success' => false, 'message' => 'Tên danh mục đã tồn tại. Vui lòng nhập tên khác.']);
        exit;
    }
    
    $slug = getUniqueSlug($slug, $conn);
    $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, image, seo_title, seo_description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $name, $slug, $description, $image, $seo_title, $seo_description, $status);
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Thêm danh mục thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    exit;
}

if ($method === 'PUT') {
    $id = intval($input['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing ID for update']);
        exit;
    }
    
    // Kiểm tra tên đã tồn tại (trừ chính nó)
    if (checkNameExists($name, $conn, $id)) {
        echo json_encode(['success' => false, 'message' => 'Tên danh mục đã tồn tại. Vui lòng nhập tên khác.']);
        exit;
    }
    
    $slug = getUniqueSlug($slug, $conn, $id);
    $stmt = $conn->prepare("UPDATE categories SET name=?, slug=?, description=?, image=?, seo_title=?, seo_description=?, status=? WHERE id=?");
    $stmt->bind_param("ssssssii", $name, $slug, $description, $image, $seo_title, $seo_description, $status, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật danh mục thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>