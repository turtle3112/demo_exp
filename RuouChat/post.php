<?php
// post.php - Trang xem trước bài viết

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===== GỌI FILE CONFIG =====
require_once __DIR__ . '/api/config.php';

// Kiểm tra kết nối database
if (!isset($conn) || $conn->connect_error) {
    die('Lỗi kết nối database: ' . ($conn->connect_error ?? 'Unknown error'));
}

// Lấy ID từ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$preview = isset($_GET['preview']) ? (int)$_GET['preview'] : 1;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Lấy bài viết từ database
try {
    $sql = "SELECT slug FROM posts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    $stmt->close();
    
    if (!$post) {
        header('Location: kienthuc.php');
        exit;
    }
    
    // Chuyển hướng sang kienthuc1.php với slug
    header('Location: kienthuc1.php?slug=' . urlencode($post['slug']) . '&preview=1');
    exit;
    
} catch (Exception $e) {
    die('Lỗi: ' . $e->getMessage());
}
?>