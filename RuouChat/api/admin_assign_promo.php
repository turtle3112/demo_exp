<?php
header('Content-Type: application/json');
require_once 'config.php';

$headers = getallheaders();
$secret = isset($headers['X-Admin-Secret']) ? $headers['X-Admin-Secret'] : '';
if ($secret !== 'RuouchatAdmin2024!') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['user_ids']) || !isset($input['promotion_id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

$userIds = $input['user_ids'];
$promotionId = intval($input['promotion_id']);

if (empty($userIds) || $promotionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

// Kiểm tra promotion tồn tại
$checkPromo = $conn->prepare("SELECT id FROM promotions WHERE id = ? AND active = 1");
$checkPromo->bind_param("i", $promotionId);
$checkPromo->execute();
$checkPromo->store_result();
if ($checkPromo->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi không tồn tại hoặc không hoạt động']);
    exit;
}
$checkPromo->close();

// Tạo bảng nếu chưa có
$conn->query("CREATE TABLE IF NOT EXISTS `user_promotions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `promotion_id` int(11) NOT NULL,
    `assigned_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `used_at` datetime DEFAULT NULL,
    `status` enum('active','used','expired') DEFAULT 'active',
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `promotion_id` (`promotion_id`)
)");

$successCount = 0;
foreach ($userIds as $userId) {
    $userId = intval($userId);
    if ($userId <= 0) continue;
    $stmt = $conn->prepare("INSERT INTO user_promotions (user_id, promotion_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $promotionId);
    if ($stmt->execute()) $successCount++;
    $stmt->close();
}
echo json_encode(['success' => true, 'message' => "Đã gán khuyến mãi cho $successCount khách hàng"]);
?>