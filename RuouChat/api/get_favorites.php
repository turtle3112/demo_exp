<?php
require_once 'config.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Vui lòng đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($method === 'GET') {
    // Lấy danh sách sản phẩm yêu thích
    $stmt = $conn->prepare("SELECT p.* FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ? ORDER BY w.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $wishlist = [];
    while ($row = $result->fetch_assoc()) {
        $wishlist[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $wishlist]);
} 
elseif ($method === 'POST') {
    // Thêm sản phẩm yêu thích
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = $input['product_id'] ?? 0;
    if (!$product_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu product_id']);
        exit;
    }
    // Kiểm tra đã tồn tại chưa
    $check = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $user_id, $product_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Sản phẩm đã có trong danh sách yêu thích']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $user_id, $product_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã thêm yêu thích']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi database']);
    }
}
elseif ($method === 'DELETE') {
    // Xóa sản phẩm yêu thích
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = $input['product_id'] ?? 0;
    if (!$product_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu product_id']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã xóa yêu thích']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi database']);
    }
}
else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}