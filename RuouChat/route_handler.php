<?php
// route_handler.php - Xử lý route tự động
require_once 'config/database.php';

// Lấy slug từ URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: /');
    exit;
}

// Khởi tạo database
$db = new Database();
$conn = $db->connect();

// ====== 1. KIỂM TRA SẢN PHẨM ======
$stmt = $conn->prepare("
    SELECT id, slug, type 
    FROM products 
    WHERE slug = ? AND status = 1 
    LIMIT 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if ($product) {
    // Xác định file đích dựa theo type
    $file = 'product_detail.php';
    
    // Nếu là sản phẩm rượu vang/mạnh
    if (in_array($product['type'], ['wine', 'strong'])) {
        $file = 'sp1.php';
    } 
    // Ly pha lê
    elseif ($product['type'] == 'glass') {
        $file = 'sp2.php';
    }
    // Sản phẩm khác
    elseif ($product['type'] == 'other') {
        $file = 'sp_other.php';
    }
    
    // Chuyển hướng nội bộ
    $_GET['slug'] = $product['slug'];
    $_GET['type'] = $product['type'];
    include $file;
    exit;
}

// ====== 2. KIỂM TRA DANH MỤC ======
$stmt = $conn->prepare("
    SELECT id, slug, type, parent_id 
    FROM categories 
    WHERE slug = ? AND status = 1 
    LIMIT 1
");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if ($category) {
    // Xác định file đích
    $file = 'product_main.php';
    
    // Nếu là danh mục kiến thức
    if ($category['type'] == 'knowledge') {
        $file = 'kienthuc.php';
        $_GET['slug'] = $category['slug'];
    } 
    // Danh mục ly pha lê
    elseif ($category['slug'] == 'ly-pha-le') {
        $file = 'lyphale.php';
    }
    // Danh mục sản phẩm khác
    elseif ($category['slug'] == 'san-pham-khac') {
        $file = 'sanphamkhac.php';
    }
    // Danh mục nhà sản xuất
    elseif ($category['slug'] == 'nha-san-xuat') {
        $file = 'nhasanxuat.php';
    }
    // Danh mục sản phẩm thông thường
    else {
        $_GET['category_slug'] = $category['slug'];
        
        // Danh mục rượu vang
        if ($category['slug'] == 'ruou-vang' || $category['parent_id'] == 1) {
            $_GET['product_type'] = 1;
        }
        // Danh mục rượu mạnh
        elseif ($category['slug'] == 'ruou-manh' || $category['parent_id'] == 2) {
            $_GET['product_type'] = 2;
        }
    }
    
    include $file;
    exit;
}

// ====== 3. KIỂM TRA BÀI VIẾT (KIẾN THỨC) ======
$stmt = $conn->prepare("
    SELECT id, slug 
    FROM posts 
    WHERE slug = ? AND status = 1 
    LIMIT 1
");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if ($post) {
    $_GET['slug'] = $post['slug'];
    include 'kienthuc1.php';
    exit;
}

// ====== 4. KIỂM TRA NHÀ SẢN XUẤT ======
$stmt = $conn->prepare("
    SELECT id, slug 
    FROM manufacturers 
    WHERE slug = ? AND status = 1 
    LIMIT 1
");
$stmt->execute([$slug]);
$manufacturer = $stmt->fetch();

if ($manufacturer) {
    $_GET['slug'] = $manufacturer['slug'];
    include 'nhasanxuat.php';
    exit;
}

// ====== 5. KIỂM TRA REDIRECT ======
$stmt = $conn->prepare("
    SELECT new_url, type 
    FROM redirects 
    WHERE old_url = ? AND status = 1 
    LIMIT 1
");
$stmt->execute([$slug]);
$redirect = $stmt->fetch();

if ($redirect) {
    $status = $redirect['type'] == '302' ? 302 : 301;
    header("Location: /{$redirect['new_url']}", true, $status);
    exit;
}

// ====== 6. KHÔNG TÌM THẤY - 404 ======
header("HTTP/1.0 404 Not Found");
include '404.php';
exit;