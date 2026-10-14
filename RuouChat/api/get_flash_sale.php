<?php
/**
 * api/get_flash_sale.php
 * API lấy thông tin flash sale đang diễn ra cho frontend
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);

require_once __DIR__ . '/config.php';

if (!$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$now = date('Y-m-d H:i:s');

// Lấy flash sale đang hoạt động
$sql = "
    SELECT fs.* 
    FROM flash_sales fs
    WHERE fs.status = 'active' 
        AND fs.start_time <= ? 
        AND fs.end_time >= ?
    ORDER BY fs.display_order ASC, fs.id ASC
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $now, $now);
$stmt->execute();
$flashSale = $stmt->get_result()->fetch_assoc();

if (!$flashSale) {
    echo json_encode(['success' => true, 'data' => null]);
    exit;
}

// Lấy sản phẩm flash sale
$stmt2 = $conn->prepare("
    SELECT 
        fp.*,
        p.id, p.name, p.slug, p.price as original_price, p.description,
        (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image,
        (SELECT alt_text FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image_alt
    FROM flash_sale_products fp
    JOIN products p ON fp.product_id = p.id
    WHERE fp.flash_sale_id = ? 
        AND fp.flash_stock > fp.sold_count
        AND p.display_status IN ('visible', 'coming_soon')
    ORDER BY fp.display_order ASC, fp.id ASC
    LIMIT 20
");
$stmt2->bind_param("i", $flashSale['id']);
$stmt2->execute();
$products = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($products as &$p) {
    $p['flash_price'] = (float)$p['flash_price'];
    $p['original_price'] = (float)$p['original_price'];
    $p['discount_percent'] = round((1 - $p['flash_price'] / $p['original_price']) * 100);
    $p['remaining_stock'] = $p['flash_stock'] - $p['sold_count'];
    $p['flash_price_formatted'] = number_format($p['flash_price'], 0, ',', '.') . 'đ';
    $p['original_price_formatted'] = number_format($p['original_price'], 0, ',', '.') . 'đ';
    $p['main_image'] = $p['main_image'] ?? '';
}

$flashSale['products'] = $products;
$flashSale['end_timestamp'] = strtotime($flashSale['end_time']) * 1000; // milliseconds for JS

echo json_encode(['success' => true, 'data' => $flashSale]);
?>