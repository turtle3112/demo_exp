<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Sử dụng cột total_amount (decimal 10,2) và status enum
$sql = "SELECT id, order_code, total_amount as total, status, created_at 
        FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi prepare: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    // Nếu order_code bị NULL, tự sinh từ id
    if (empty($row['order_code'])) {
        $row['order_code'] = 'ORD' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
    }
    $row['created_at_formatted'] = date('d/m/Y', strtotime($row['created_at']));
    $row['total_formatted'] = number_format($row['total'], 0, ',', '.') . ' ₫';
    
    // Ánh xạ trạng thái (dựa trên enum trong DB)
    $statusMap = [
        'pending'          => 'Chờ xử lý',
        'processing'       => 'Đang xử lý',
        'completed'        => 'Hoàn thành',
        'cancelled'        => 'Đã hủy',
        'pending_payment'  => 'Chờ thanh toán'
    ];
    $row['status_text'] = $statusMap[$row['status']] ?? $row['status'];
    
    $orders[] = $row;
}

echo json_encode(['success' => true, 'orders' => $orders]);
?>