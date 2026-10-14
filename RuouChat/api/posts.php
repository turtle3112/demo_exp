<?php
// api/posts.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once 'config.php';

function normalizeImageUrl($image) {
    $defaultImage = '/assets/images/default-post.jpg';
    if (empty($image)) return $defaultImage;
    if (preg_match('#^https?://#', $image)) return $image;
    if (strpos($image, '/') === 0) return $image;
    return '/' . $image;
}

if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 6;
$offset = ($page - 1) * $limit;
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$post_type = isset($_GET['post_type']) ? trim($_GET['post_type']) : '';

// Xây dựng câu lệnh lấy danh sách bài viết kèm thông tin danh mục
$sql = "SELECT p.id, p.title, p.slug, p.image, p.short_description, p.created_at, p.views,
               GROUP_CONCAT(DISTINCT pc.slug SEPARATOR ',') as category_slugs,
               GROUP_CONCAT(DISTINCT pc.name SEPARATOR ',') as category_names,
               p.post_type
        FROM posts p
        LEFT JOIN post_category_relations pcr ON p.id = pcr.post_id
        LEFT JOIN post_categories pc ON pcr.category_id = pc.id
        WHERE p.status = 1";
$params = [];
$types = "";

// Ưu tiên lọc theo category slug (nếu có)
if (!empty($category_slug)) {
    $sql .= " AND EXISTS (SELECT 1 FROM post_category_relations pcr2 
                          JOIN post_categories pc2 ON pcr2.category_id = pc2.id 
                          WHERE pcr2.post_id = p.id AND pc2.slug = ?)";
    $params[] = $category_slug;
    $types .= "s";
} 
// Fallback: lọc theo post_type cũ (nếu có và không có category slug)
elseif (!empty($post_type)) {
    $sql .= " AND p.post_type = ?";
    $params[] = $post_type;
    $types .= "s";
}

$sql .= " GROUP BY p.id ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare error: ' . $conn->error]);
    exit;
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$posts = [];
while ($row = $result->fetch_assoc()) {
    // Lấy slug danh mục đầu tiên để hiển thị (nếu có)
    $row['category_slug'] = '';
    if (!empty($row['category_slugs'])) {
        $slugs = explode(',', $row['category_slugs']);
        $row['category_slug'] = trim($slugs[0]);
    }
    $row['image'] = normalizeImageUrl($row['image']);
    $posts[] = $row;
}
$stmt->close();

// Đếm tổng số để phân trang
$countSql = "SELECT COUNT(DISTINCT p.id) as total
             FROM posts p
             LEFT JOIN post_category_relations pcr ON p.id = pcr.post_id
             LEFT JOIN post_categories pc ON pcr.category_id = pc.id
             WHERE p.status = 1";
$countParams = [];
$countTypes = "";
if (!empty($category_slug)) {
    $countSql .= " AND EXISTS (SELECT 1 FROM post_category_relations pcr2 
                               JOIN post_categories pc2 ON pcr2.category_id = pc2.id 
                               WHERE pcr2.post_id = p.id AND pc2.slug = ?)";
    $countParams[] = $category_slug;
    $countTypes .= "s";
} elseif (!empty($post_type)) {
    $countSql .= " AND p.post_type = ?";
    $countParams[] = $post_type;
    $countTypes .= "s";
}
$countStmt = $conn->prepare($countSql);
$total = 0;
if ($countStmt) {
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
    $total = $totalResult->fetch_assoc()['total'] ?? 0;
    $countStmt->close();
}
$conn->close();

echo json_encode([
    'posts'       => $posts,
    'totalCount'  => (int)$total,
    'totalPages'  => ceil($total / $limit),
    'currentPage' => $page
]);
?>