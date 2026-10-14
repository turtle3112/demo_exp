<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$fullname = $input['fullname'] ?? '';
$email = $input['email'] ?? '';
$phone = $input['phone'] ?? '';
$address = $input['address'] ?? '';
$payment_method = $input['payment_method'] ?? 'cod';
$items = $input['items'] ?? [];
$total = $input['total'] ?? 0;

if (!$fullname || !$email || !$phone || !$address || empty($items)) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu thông tin bắt buộc']);
    exit;
}

$user_id = $_SESSION['user']['id'] ?? null;
$status = ($payment_method === 'bank') ? 'pending_payment' : 'pending';

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO orders (user_id, fullname, email, phone, address, total_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssdss", $user_id, $fullname, $email, $phone, $address, $total, $payment_method, $status);
    $stmt->execute();
    $order_id = $conn->insert_id;
    
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt_item->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
        $stmt_item->execute();
    }
    
    $conn->commit();
    
    // Xóa giỏ hàng trong session (gửi yêu cầu xóa)
    $_SESSION['cart'] = [];
    
    echo json_encode(['success' => true, 'order_id' => $order_id]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi tạo đơn hàng: ' . $e->getMessage()]);
}
?>