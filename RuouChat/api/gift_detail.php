<?php
/**
 * api/gift_detail.php
 * API lấy chi tiết sản phẩm quà tặng theo slug hoặc ID
 */

header('Content-Type: application/json');
require_once 'config.php';

// Lấy tham số
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$preview = isset($_GET['preview']) && $_GET['preview'] == 1;

// Kiểm tra xem có phải admin preview không
$is_admin_preview = false;
if ($preview) {
    session_start();
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        $is_admin_preview = true;
    }
    if (!$is_admin_preview && isset($_COOKIE['admin_preview']) && $_COOKIE['admin_preview'] === '1') {
        $is_admin_preview = true;
    }
}

// Nếu không có slug và không có id
if (empty($slug) && !$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu tham số slug hoặc id']);
    exit;
}

// Xây dựng câu truy vấn
if (!empty($slug)) {
    if ($is_admin_preview) {
        // Admin preview: lấy cả sản phẩm ẩn
        $sql = "SELECT * FROM gift_products WHERE slug = ?";
    } else {
        $sql = "SELECT * FROM gift_products WHERE slug = ? AND status = 1";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $slug);
} else {
    if ($is_admin_preview) {
        $sql = "SELECT * FROM gift_products WHERE id = ?";
    } else {
        $sql = "SELECT * FROM gift_products WHERE id = ? AND status = 1";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
}

$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if ($product) {
    // Xử lý gallery
    if (!empty($product['gallery'])) {
        $gallery = json_decode($product['gallery'], true);
        $product['gallery'] = is_array($gallery) ? $gallery : [];
    } else {
        $product['gallery'] = [];
    }
    
    // Đảm bảo các trường cần thiết
    $product['price'] = floatval($product['price']);
    $product['old_price'] = $product['old_price'] ? floatval($product['old_price']) : null;
    
    if (empty($product['main_image'])) {
        $product['main_image'] = 'assets/images/default-gift.jpg';
    }
    
    // Bổ sung các trường để tương thích với sp1.php
    $product['grape_type'] = '';
    $product['wine_type'] = 'Quà tặng';
    $product['producer_name'] = '';
    $product['region'] = '';
    $product['type'] = 'gift';
    $product['categories'] = ['Quà tặng'];
    
    // Thêm flag preview nếu là admin preview
    if ($is_admin_preview && $product['status'] == 0) {
        $product['is_preview'] = true;
        $product['preview_status'] = 'hidden';
    }
    
    echo json_encode($product);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Không tìm thấy sản phẩm quà tặng']);
}

$stmt->close();
$conn->close();
?>