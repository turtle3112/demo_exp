<?php
// api/order.php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Tắt hiển thị lỗi HTML
header('Content-Type: application/json');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$fullname = trim($input['fullname'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$address = trim($input['address'] ?? '');
$payment_method = $input['payment_method'] ?? 'cod';
$items = $input['items'] ?? [];
$total = floatval($input['total'] ?? 0);
$original_total = floatval($input['original_total'] ?? $total);
$promo_id = isset($input['promo_id']) ? intval($input['promo_id']) : null;
$promo_code = $input['promo_code'] ?? null;

if (!$fullname || !$email || !$phone || !$address || empty($items)) {
    http_response_code(400);
    echo json_encode(['error' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

// Lấy user_id từ session (nếu có)
if (session_status() === PHP_SESSION_NONE) session_start();
$user_id = $_SESSION['user_id'] ?? null;

$order_code = 'ORD' . time();

$conn->begin_transaction();
try {
    // 1. Kiểm tra tồn kho và khóa hàng
    foreach ($items as $item) {
        $product_id = $item['id'];
        $quantity = $item['quantity'];
        $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        if (!$product) {
            throw new Exception("Sản phẩm ID {$product_id} không tồn tại");
        }
        if ($product['stock_quantity'] < $quantity) {
            throw new Exception("Sản phẩm không đủ số lượng tồn kho");
        }
    }

    // 2. Tạo đơn hàng
    $sql = "INSERT INTO orders (order_code, user_id, fullname, email, phone, address, total_amount, payment_method, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissssds", $order_code, $user_id, $fullname, $email, $phone, $address, $total, $payment_method);
    $stmt->execute();
    $order_id = $conn->insert_id;

    // 3. Lưu chi tiết đơn hàng và cập nhật tồn kho
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt_update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
    foreach ($items as $item) {
        $product_id = $item['id'];
        $quantity = $item['quantity'];
        $price = $item['price'];
        $stmt_item->bind_param("iiid", $order_id, $product_id, $quantity, $price);
        $stmt_item->execute();
        $stmt_update_stock->bind_param("ii", $quantity, $product_id);
        $stmt_update_stock->execute();
    }

    // 4. Xóa giỏ hàng trong session
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }

    $conn->commit();

    echo json_encode(['success' => true, 'order_id' => $order_id, 'order_code' => $order_code]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>