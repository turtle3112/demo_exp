<?php
header('Content-Type: application/json');
require_once 'config.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
$offset = ($page - 1) * $limit;
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

$allowedCategories = ['phat-tai', 'phat-loc'];

// Truy vấn lấy sản phẩm (dùng prepared statement)
$sql = "SELECT id, name, slug, price, old_price, abv, volume, category, description, main_image, status
        FROM gift_products 
        WHERE status = 1";
$params = [];
$types = '';

if ($category !== '' && in_array($category, $allowedCategories)) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= 's';
}
$sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);

// Đếm tổng số
$countSql = "SELECT COUNT(*) as total FROM gift_products WHERE status = 1";
$countParams = [];
$countTypes = '';
if ($category !== '' && in_array($category, $allowedCategories)) {
    $countSql .= " AND category = ?";
    $countParams[] = $category;
    $countTypes .= 's';
}
$countStmt = $conn->prepare($countSql);
if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$total = $countResult->fetch_assoc()['total'];

echo json_encode([
    'products' => $products,
    'totalCount' => (int)$total,
    'totalPages' => ceil($total / $limit),
    'currentPage' => $page,
    'limit' => $limit
]);

$stmt->close();
$countStmt->close();
$conn->close();
?>