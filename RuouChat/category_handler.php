<?php
// category_handler.php - Xử lý danh mục
require_once 'config/database.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: /');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Kiểm tra danh mục
$stmt = $conn->prepare("
    SELECT id, slug, type, parent_id 
    FROM categories 
    WHERE slug = ? AND status = 1 
    LIMIT 1
");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Xử lý theo loại danh mục
switch ($category['type']) {
    case 'wine':
        $_GET['product_type'] = 1;
        include 'product_main.php';
        break;
        
    case 'strong':
        $_GET['product_type'] = 2;
        include 'product_main.php';
        break;
        
    case 'glass':
        include 'lyphale.php';
        break;
        
    case 'knowledge':
        $_GET['slug'] = $category['slug'];
        include 'kienthuc.php';
        break;
        
    default:
        $_GET['category_slug'] = $category['slug'];
        include 'product_main.php';
        break;
}

exit;