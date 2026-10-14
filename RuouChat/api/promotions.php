<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Lấy tất cả khuyến mãi còn hiệu lực (active = 1, chưa hết hạn)
$today = date('Y-m-d');
$sql = "SELECT * FROM promotions 
        WHERE active = 1 
        AND (expiry_date IS NULL OR expiry_date >= '$today')
        ORDER BY discount_value DESC";

$result = $conn->query($sql);
$promos = $result->fetch_all(MYSQLI_ASSOC);

// Chuẩn hóa dữ liệu trả về
foreach ($promos as &$p) {
    $p['discount_percent'] = ($p['discount_type'] == 'percent') ? (float)$p['discount_value'] : 0;
    $p['discount_fixed'] = ($p['discount_type'] == 'fixed') ? (float)$p['discount_value'] : 0;
    $p['min_order_value'] = (float)$p['min_order_value'];
}
echo json_encode($promos);
?>