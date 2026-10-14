<?php
/**
 * api/product_types.php
 * API quản lý loại sản phẩm
 * CRUD: GET, POST, PUT, DELETE
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

function createSlug($str) {
    $str = trim(mb_strtolower($str));
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

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

// ========== GET ==========
if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id) {
        // Lấy chi tiết 1 loại
        $stmt = $conn->prepare("SELECT * FROM product_types WHERE id = ? AND status = 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $type = $result->fetch_assoc();
        $stmt->close();
        
        if ($type) {
            echo json_encode($type);
        } else {
            sendError('Không tìm thấy loại sản phẩm', 404);
        }
        exit;
    }
    
    // Lấy danh sách tất cả
    $sql = "SELECT * FROM product_types WHERE status = 1 ORDER BY sort_order ASC, name ASC";
    $result = $conn->query($sql);
    $types = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($types);
    exit;
}

// ========== POST (CREATE) ==========
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendError('Dữ liệu JSON không hợp lệ');
    }
    
    $name = trim($input['name'] ?? '');
    if (empty($name)) {
        sendError('Tên loại sản phẩm là bắt buộc');
    }
    
    // Tạo slug
    $slug = !empty($input['slug']) ? trim($input['slug']) : createSlug($name);
    
    // Kiểm tra slug unique
    $checkStmt = $conn->prepare("SELECT id FROM product_types WHERE slug = ?");
    $checkStmt->bind_param("s", $slug);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        sendError('Slug "' . $slug . '" đã tồn tại');
    }
    $checkStmt->close();
    
    $description = trim($input['description'] ?? '');
    $icon = trim($input['icon'] ?? '');
    $sort_order = isset($input['sort_order']) ? intval($input['sort_order']) : 0;
    $status = isset($input['status']) ? intval($input['status']) : 1;
    
    $stmt = $conn->prepare("INSERT INTO product_types (name, slug, description, icon, sort_order, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", $name, $slug, $description, $icon, $sort_order, $status);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        
        sendSuccess([
            'id' => $newId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'icon' => $icon,
            'sort_order' => $sort_order
        ]);
    } else {
        sendError('Lỗi khi thêm loại sản phẩm: ' . $stmt->error, 500);
    }
    exit;
}

// ========== PUT (UPDATE) ==========
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendError('Dữ liệu JSON không hợp lệ');
    }
    
    $id = intval($input['id'] ?? 0);
    if (!$id) {
        sendError('Thiếu ID loại sản phẩm');
    }
    
    $name = trim($input['name'] ?? '');
    if (empty($name)) {
        sendError('Tên loại sản phẩm là bắt buộc');
    }
    
    $slug = !empty($input['slug']) ? trim($input['slug']) : createSlug($name);
    
    // Kiểm tra slug unique (trừ chính nó)
    $checkStmt = $conn->prepare("SELECT id FROM product_types WHERE slug = ? AND id != ?");
    $checkStmt->bind_param("si", $slug, $id);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        sendError('Slug "' . $slug . '" đã tồn tại');
    }
    $checkStmt->close();
    
    $description = trim($input['description'] ?? '');
    $icon = trim($input['icon'] ?? '');
    $sort_order = isset($input['sort_order']) ? intval($input['sort_order']) : 0;
    $status = isset($input['status']) ? intval($input['status']) : 1;
    
    $stmt = $conn->prepare("UPDATE product_types SET name = ?, slug = ?, description = ?, icon = ?, sort_order = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssssiii", $name, $slug, $description, $icon, $sort_order, $status, $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        sendSuccess([
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'icon' => $icon,
            'sort_order' => $sort_order
        ]);
    } else {
        sendError('Lỗi khi cập nhật loại sản phẩm: ' . $stmt->error, 500);
    }
    exit;
}

// ========== DELETE ==========
if ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        sendError('Thiếu ID loại sản phẩm');
    }
    
    // Kiểm tra xem có sản phẩm nào đang sử dụng loại này không
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE product_type = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $checkStmt->close();
    
    if ($count > 0) {
        sendError('Không thể xóa loại sản phẩm này vì đang có ' . $count . ' sản phẩm sử dụng');
    }
    
    // Soft delete (cập nhật status = 0)
    $stmt = $conn->prepare("UPDATE product_types SET status = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        sendSuccess(['deleted' => true]);
    } else {
        sendError('Lỗi khi xóa loại sản phẩm: ' . $stmt->error, 500);
    }
    exit;
}

sendError('Phương thức không được hỗ trợ', 405);
?>