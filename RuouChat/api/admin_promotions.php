<?php
// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';


// Kiểm tra quyền admin


$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

header('Content-Type: application/json');

if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM promotions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode($result);
    } else {
        $result = $conn->query("SELECT * FROM promotions ORDER BY id DESC");
        $promos = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($promos);
    }
    exit;
}

if ($method === 'POST') {
    $code = trim($input['code'] ?? '');
    $description = trim($input['description'] ?? '');
    $discount_type = $input['discount_type'] ?? 'percent';
    $discount_value = floatval($input['discount_value'] ?? 0);
    $min_order_value = floatval($input['min_order_value'] ?? 0);
    $expiry_date = !empty($input['expiry_date']) ? $input['expiry_date'] : null;
    $active = isset($input['active']) ? intval($input['active']) : 1;

    if (empty($code) || $discount_value <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Mã code và giá trị giảm là bắt buộc']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO promotions (code, description, discount_type, discount_value, min_order_value, expiry_date, active) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssddsi", $code, $description, $discount_type, $discount_value, $min_order_value, $expiry_date, $active);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi database: ' . $stmt->error]);
    }
    exit;
}

if ($method === 'PUT') {
    $id = intval($input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID']);
        exit;
    }
    $code = trim($input['code'] ?? '');
    $description = trim($input['description'] ?? '');
    $discount_type = $input['discount_type'] ?? 'percent';
    $discount_value = floatval($input['discount_value'] ?? 0);
    $min_order_value = floatval($input['min_order_value'] ?? 0);
    $expiry_date = !empty($input['expiry_date']) ? $input['expiry_date'] : null;
    $active = isset($input['active']) ? intval($input['active']) : 1;

    $stmt = $conn->prepare("UPDATE promotions SET code=?, description=?, discount_type=?, discount_value=?, min_order_value=?, expiry_date=?, active=? WHERE id=?");
    $stmt->bind_param("sssddssi", $code, $description, $discount_type, $discount_value, $min_order_value, $expiry_date, $active, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi cập nhật: ' . $stmt->error]);
    }
    exit;
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM promotions WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Xóa thất bại: ' . $stmt->error]);
    }
    exit;
}
?>