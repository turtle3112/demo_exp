<?php
// api/post_detail.php
// API lấy chi tiết bài viết theo slug hoặc id

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

/**
 * Chuẩn hóa URL ảnh
 */
function normalizeImageUrl($image) {
    if (empty($image)) return '';
    // Nếu là URL đầy đủ (http/https)
    if (preg_match('#^https?://#', $image)) return $image;
    // Nếu bắt đầu bằng / thì giữ nguyên
    if (strpos($image, '/') === 0) return $image;
    // Nếu chứa uploads/posts thì thêm / vào đầu
    if (strpos($image, 'uploads/posts') !== false) return '/' . $image;
    return '/' . $image;
}

// Kiểm tra kết nối database
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . ($conn->connect_error ?? 'unknown')]);
    exit;
}

// Lấy tham số từ request
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$preview = isset($_GET['preview']) ? (int)$_GET['preview'] : 0;

// Kiểm tra tham số bắt buộc
if (empty($slug) && $id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Slug or ID is required']);
    exit;
}

try {
    // Xây dựng câu truy vấn dựa trên tham số
    if ($id > 0) {
        $sql = "SELECT p.*, 
                       GROUP_CONCAT(DISTINCT pc.name SEPARATOR ', ') as category_names,
                       GROUP_CONCAT(DISTINCT pc.slug SEPARATOR ',') as category_slugs,
                       GROUP_CONCAT(DISTINCT pc.id SEPARATOR ',') as category_ids
                FROM posts p
                LEFT JOIN post_category_relations pcr ON p.id = pcr.post_id
                LEFT JOIN post_categories pc ON pcr.category_id = pc.id
                WHERE p.id = ?";
        $paramType = 'i';
        $paramValue = $id;
    } else {
        $sql = "SELECT p.*, 
                       GROUP_CONCAT(DISTINCT pc.name SEPARATOR ', ') as category_names,
                       GROUP_CONCAT(DISTINCT pc.slug SEPARATOR ',') as category_slugs,
                       GROUP_CONCAT(DISTINCT pc.id SEPARATOR ',') as category_ids
                FROM posts p
                LEFT JOIN post_category_relations pcr ON p.id = pcr.post_id
                LEFT JOIN post_categories pc ON pcr.category_id = pc.id
                WHERE p.slug = ?";
        $paramType = 's';
        $paramValue = $slug;
    }
    
    // Nếu không phải preview, chỉ lấy bài viết có status = 1 (Hiển thị)
    if (!$preview) {
        $sql .= " AND p.status = 1";
    }
    
    $sql .= " GROUP BY p.id";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param($paramType, $paramValue);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    $stmt->close();

    // Kiểm tra bài viết tồn tại
    if (!$post) {
        http_response_code(404);
        echo json_encode(['error' => 'Bài viết không tồn tại']);
        exit;
    }

    // Lấy danh mục chi tiết của bài viết
    $stmtCat = $conn->prepare("
        SELECT c.id, c.name, c.slug, c.description
        FROM post_categories c
        INNER JOIN post_category_relations pcr ON c.id = pcr.category_id
        WHERE pcr.post_id = ?
        ORDER BY c.name ASC
    ");
    $stmtCat->bind_param("i", $post['id']);
    $stmtCat->execute();
    $post['categories'] = $stmtCat->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtCat->close();

    // Lấy gallery ảnh
    $stmtGal = $conn->prepare("
        SELECT id, image_url, thumb_url, alt_text, sort_order, is_cover 
        FROM post_images 
        WHERE post_id = ? 
        ORDER BY sort_order ASC, id ASC
    ");
    $stmtGal->bind_param("i", $post['id']);
    $stmtGal->execute();
    $post['gallery'] = $stmtGal->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtGal->close();

    // Chuẩn hóa URL ảnh
    $post['image'] = normalizeImageUrl($post['image']);
    foreach ($post['gallery'] as &$img) {
        $img['image_url'] = normalizeImageUrl($img['image_url']);
        if (!empty($img['thumb_url'])) {
            $img['thumb_url'] = normalizeImageUrl($img['thumb_url']);
        }
    }
    unset($img); // break reference

    // Xử lý category_ids từ chuỗi thành mảng
    $post['category_ids'] = [];
    if (!empty($post['category_ids'])) {
        $post['category_ids'] = array_map('intval', explode(',', $post['category_ids']));
    }

    // Nếu không có category_ids từ GROUP_CONCAT, lấy từ categories
    if (empty($post['category_ids']) && !empty($post['categories'])) {
        $post['category_ids'] = array_column($post['categories'], 'id');
    }

    // Tăng số lượt xem (chỉ khi không phải preview)
    if (!$preview) {
        $stmtView = $conn->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
        $stmtView->bind_param("i", $post['id']);
        $stmtView->execute();
        $stmtView->close();
    }

    // Xử lý SEO - ưu tiên các giá trị đã nhập
    $post['seo_title'] = !empty($post['seo_title']) ? $post['seo_title'] : $post['title'];
    $post['seo_description'] = !empty($post['seo_description']) 
        ? $post['seo_description'] 
        : (!empty($post['short_description']) ? $post['short_description'] : (!empty($post['excerpt']) ? $post['excerpt'] : ''));

    // Đảm bảo short_description có dữ liệu (dùng cho hiển thị)
    if (empty($post['short_description']) && !empty($post['excerpt'])) {
        $post['short_description'] = $post['excerpt'];
    }

    // Thêm thông tin bổ sung
    $post['formatted_date'] = date('d/m/Y', strtotime($post['created_at']));
    $post['preview_mode'] = (bool)$preview;

    // Xóa các trường không cần thiết nếu có
    unset($post['seo_slug']); // nếu có

    // Trả về kết quả
    echo json_encode($post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>