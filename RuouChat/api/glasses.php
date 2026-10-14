<?php
/**
 * API: Lấy danh sách hoặc chi tiết sản phẩm ly pha lê (crystal_glasses)
 * Method: GET
 * Parameters:
 *   - slug: string (lấy chi tiết 1 sản phẩm)
 *   - type: string (lọc theo type: best-seller, decanter)
 *   - limit: int (số lượng sản phẩm, mặc định 10, tối đa 50)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

// Kiểm tra kết nối database
if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Không thể kết nối cơ sở dữ liệu']);
    exit;
}

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Giới hạn limit
if ($limit > 50) $limit = 50;
if ($limit < 1) $limit = 10;

// ------------------- LẤY CHI TIẾT SẢN PHẨM THEO SLUG -------------------
if (!empty($slug)) {
    $stmt = $conn->prepare("
        SELECT id, name, slug, price, old_price, categories, type, description, main_image, status, featured, seo_title, seo_description
        FROM crystal_glasses 
        WHERE slug = ? AND status = 1 
        LIMIT 1
    ");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi truy vấn: ' . $conn->error]);
        $conn->close();
        exit;
    }
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if ($product) {
        // Xử lý ảnh mặc định nếu không có
        if (empty($product['main_image'])) {
            $product['main_image'] = 'assets/images/11.jpg';
        }
        // Ép kiểu số
        $product['price'] = (float)$product['price'];
        if ($product['old_price'] !== null) {
            $product['old_price'] = (float)$product['old_price'];
        }
        // Giải mã categories (JSON)
        if (!empty($product['categories'])) {
            $categories = json_decode($product['categories'], true);
            $product['categories'] = is_array($categories) ? $categories : [];
        } else {
            $product['categories'] = [];
        }
        echo json_encode($product);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy sản phẩm']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// ------------------- LẤY DANH SÁCH SẢN PHẨM (có thể lọc theo type) -------------------
$allowedTypes = ['best-seller', 'decanter'];
$sql = "
    SELECT id, name, slug, price, old_price, categories, type, description, main_image, status, featured
    FROM crystal_glasses 
    WHERE status = 1
";
$params = [];
$types = '';

if ($type !== '' && in_array($type, $allowedTypes)) {
    $sql .= " AND type = ?";
    $params[] = $type;
    $types .= 's';
}

$sql .= " ORDER BY id DESC LIMIT ?";
$params[] = $limit;
$types .= 'i';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi truy vấn: ' . $conn->error]);
    $conn->close();
    exit;
}

// Bind parameters nếu có
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);

// Xử lý từng sản phẩm
foreach ($products as &$p) {
    if (empty($p['main_image'])) {
        $p['main_image'] = 'assets/images/11.jpg';
    }
    $p['price'] = (float)$p['price'];
    if ($p['old_price'] !== null) {
        $p['old_price'] = (float)$p['old_price'];
    }
    if (!empty($p['categories'])) {
        $categories = json_decode($p['categories'], true);
        $p['categories'] = is_array($categories) ? $categories : [];
    } else {
        $p['categories'] = [];
    }
}

echo json_encode(['products' => $products]);

$stmt->close();
$conn->close();
?>