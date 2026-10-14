<?php
/**
 * api/products.php
 * API lấy danh sách sản phẩm cho frontend
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);

require_once __DIR__ . '/config.php';

$response = ['success' => false, 'products' => [], 'totalCount' => 0];

if (!$conn || $conn->connect_error) {
    $response['error'] = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
    echo json_encode($response);
    exit;
}

// ---------- Lấy danh sách cột trong bảng products ----------
$columns = [];
$colResult = $conn->query("SHOW COLUMNS FROM products");
if ($colResult) {
    while ($row = $colResult->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
}

// ---------- Kiểm tra bảng ----------
$hasProductCategories = $conn->query("SHOW TABLES LIKE 'product_categories'")->num_rows > 0;
$hasCategories = $conn->query("SHOW TABLES LIKE 'categories'")->num_rows > 0;
$hasProductImages = $conn->query("SHOW TABLES LIKE 'product_images'")->num_rows > 0;

// ---------- Tham số đầu vào ----------
$all = isset($_GET['all']) && $_GET['all'] == '1';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = $all ? 999999 : (isset($_GET['limit']) ? min(100, intval($_GET['limit'])) : 20);
$offset = ($page - 1) * $limit;

$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$orderBy = "p.id DESC";
switch ($sort) {
    case 'price_asc':  $orderBy = "p.price ASC, p.id ASC"; break;
    case 'price_desc': $orderBy = "p.price DESC, p.id DESC"; break;
    case 'name_asc':   $orderBy = "p.name ASC, p.id ASC"; break;
    case 'name_desc':  $orderBy = "p.name DESC, p.id DESC"; break;
    case 'newest':     $orderBy = "p.created_at DESC, p.id DESC"; break;
    default:           $orderBy = "p.id DESC";
}

// Bộ lọc cơ bản
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price    = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price    = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;

// Bộ lọc danh mục
$cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
$category_slug = isset($_GET['category_slug']) ? trim($_GET['category_slug']) : '';

if (!empty($category_slug) && $cat_id == 0 && $hasCategories) {
    $slugStmt = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND status = 1");
    if ($slugStmt) {
        $slugStmt->bind_param("s", $category_slug);
        $slugStmt->execute();
        $slugResult = $slugStmt->get_result();
        if ($row = $slugResult->fetch_assoc()) {
            $cat_id = (int)$row['id'];
        }
        $slugStmt->close();
    }
}

if ($cat_id == 0 && !empty($category_slug) && $hasCategories) {
    $slugStmt = $conn->prepare("SELECT id FROM categories WHERE slug LIKE ? AND status = 1 LIMIT 1");
    if ($slugStmt) {
        $likeSlug = '%' . $category_slug . '%';
        $slugStmt->bind_param("s", $likeSlug);
        $slugStmt->execute();
        $slugResult = $slugStmt->get_result();
        if ($row = $slugResult->fetch_assoc()) {
            $cat_id = (int)$row['id'];
        }
        $slugStmt->close();
    }
}

// Bộ lọc rượu vang
$country   = isset($_GET['country']) ? trim($_GET['country']) : '';
$region    = isset($_GET['region']) ? trim($_GET['region']) : '';
$grape     = isset($_GET['grape']) ? trim($_GET['grape']) : '';
$wine_type = isset($_GET['wine_type']) ? trim($_GET['wine_type']) : '';
$spirit_type = isset($_GET['spirit_type']) ? trim($_GET['spirit_type']) : '';
$brand       = isset($_GET['brand']) ? trim($_GET['brand']) : '';

// ---------- Xây dựng câu lệnh WHERE ----------
$where = [];
$params = [];
$types = "";

$where[] = "p.status = 1";
$where[] = "p.display_status IN ('visible', 'coming_soon')";

// Điều kiện danh mục
if ($cat_id > 0 && $hasProductCategories && $hasCategories) {
    $allCategoryIds = [$cat_id];
    
    $childSql = "SELECT id FROM categories WHERE parent_id = ? AND status = 1";
    $childStmt = $conn->prepare($childSql);
    if ($childStmt) {
        $childStmt->bind_param("i", $cat_id);
        $childStmt->execute();
        $childResult = $childStmt->get_result();
        while ($row = $childResult->fetch_assoc()) {
            $allCategoryIds[] = (int)$row['id'];
        }
        $childStmt->close();
    }
    
    $grandChildIds = [];
    foreach ($allCategoryIds as $parentId) {
        $grandStmt = $conn->prepare("SELECT id FROM categories WHERE parent_id = ? AND status = 1");
        if ($grandStmt) {
            $grandStmt->bind_param("i", $parentId);
            $grandStmt->execute();
            $grandResult = $grandStmt->get_result();
            while ($row = $grandResult->fetch_assoc()) {
                $grandChildIds[] = (int)$row['id'];
            }
            $grandStmt->close();
        }
    }
    $allCategoryIds = array_merge($allCategoryIds, $grandChildIds);
    $allCategoryIds = array_unique($allCategoryIds);
    
    if (count($allCategoryIds) > 1) {
        $placeholders = implode(',', array_fill(0, count($allCategoryIds), '?'));
        $where[] = "p.id IN (SELECT DISTINCT product_id FROM product_categories WHERE category_id IN ($placeholders))";
        foreach ($allCategoryIds as $cid) {
            $params[] = $cid;
            $types .= "i";
        }
    } else {
        $where[] = "p.id IN (SELECT DISTINCT product_id FROM product_categories WHERE category_id = ?)";
        $params[] = $cat_id;
        $types .= "i";
    }
}

if (!empty($search)) {
    $where[] = "p.name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if ($min_price > 0) {
    $where[] = "p.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}
if ($max_price > 0) {
    $where[] = "p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

if (!empty($country) && in_array('country', $columns)) {
    $where[] = "p.country = ?";
    $params[] = $country;
    $types .= "s";
}
if (!empty($region) && in_array('region', $columns)) {
    $where[] = "p.region = ?";
    $params[] = $region;
    $types .= "s";
}
if (!empty($grape) && in_array('grape_type', $columns)) {
    $where[] = "p.grape_type = ?";
    $params[] = $grape;
    $types .= "s";
}
if (!empty($wine_type) && in_array('wine_type', $columns)) {
    $where[] = "p.wine_type = ?";
    $params[] = $wine_type;
    $types .= "s";
}
if (!empty($spirit_type) && in_array('spirit_type', $columns)) {
    $where[] = "p.spirit_type = ?";
    $params[] = $spirit_type;
    $types .= "s";
}
if (!empty($brand) && in_array('brand', $columns)) {
    $where[] = "p.brand = ?";
    $params[] = $brand;
    $types .= "s";
}

$whereClause = empty($where) ? "1=1" : implode(" AND ", $where);

// ===== QUAN TRỌNG: LẤY ẢNH ĐẠI DIỆN TỪ BẢNG product_images + TÊN THẬT GIỐNG NHO/VÙNG/QUỐC GIA =====
$sql = "SELECT p.*, 
        (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image,
        (SELECT alt_text FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image_alt,
        gc.name as grape_name,
        r.name as region_name,
        co.name as country_name
        FROM products p 
        LEFT JOIN categories gc ON p.grape_type = gc.id AND gc.parent_id = 46
        LEFT JOIN regions r ON p.region = r.id
        LEFT JOIN countries co ON p.country = co.id
        WHERE $whereClause 
        ORDER BY $orderBy 
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $response['error'] = 'Prepare failed: ' . $conn->error;
    echo json_encode($response);
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Đếm tổng số
$countSql = "SELECT COUNT(*) as total FROM products p WHERE $whereClause";
$countStmt = $conn->prepare($countSql);
$total = 0;
if ($countStmt) {
    $countParams = array_slice($params, 0, -2);
    $countTypes = substr($types, 0, -2);
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
    $countStmt->close();
}

// Lấy danh mục cho từng sản phẩm
$productIds = array_column($products, 'id');
$categoryMap = [];

if ($hasProductCategories && $hasCategories && !empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $catSql = "SELECT pc.product_id, c.id, c.name, c.slug, c.parent_id, c.level
               FROM categories c 
               INNER JOIN product_categories pc ON c.id = pc.category_id 
               WHERE pc.product_id IN ($placeholders)
               ORDER BY c.level ASC, c.id ASC";
    $catStmt = $conn->prepare($catSql);
    if ($catStmt) {
        $typesIds = str_repeat('i', count($productIds));
        $catStmt->bind_param($typesIds, ...$productIds);
        $catStmt->execute();
        $catResult = $catStmt->get_result();
        while ($row = $catResult->fetch_assoc()) {
            $productId = $row['product_id'];
            if (!isset($categoryMap[$productId])) {
                $categoryMap[$productId] = [];
            }
            $categoryMap[$productId][] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'parent_id' => (int)$row['parent_id'],
                'level' => (int)$row['level']
            ];
        }
        $catStmt->close();
    }
}

// Xử lý từng sản phẩm
foreach ($products as &$p) {
    $p['id'] = (int)$p['id'];
    $p['price'] = isset($p['price']) ? (float)$p['price'] : 0;
    $p['old_price'] = isset($p['old_price']) && $p['old_price'] !== null && $p['old_price'] > 0 ? (float)$p['old_price'] : null;
    $p['stock_quantity'] = isset($p['stock_quantity']) ? (int)$p['stock_quantity'] : 0;
    $p['product_type'] = 1;
    
    $p['country'] = $p['country'] ?? '';
    $p['region'] = $p['region'] ?? '';
    $p['grape_type'] = $p['grape_type'] ?? '';
    $p['wine_type'] = $p['wine_type'] ?? '';
    $p['spirit_type'] = $p['spirit_type'] ?? '';
    $p['brand'] = $p['brand'] ?? '';
    $p['producer'] = $p['producer'] ?? '';
    $p['abv'] = $p['abv'] ?: '13%';
    $p['alcohol'] = $p['abv'];
    $p['supplier_name'] = $p['brand'] ?: ($p['producer'] ?: '');

    // ====== TÊN GIỐNG NHO (ưu tiên tên JOIN được, giống logic product_detail.php) ======
    $grapeDisplay = '';
    if (!empty($p['grape_name'])) {
        $grapeDisplay = $p['grape_name'];
    } elseif (!empty($p['grape_type'])) {
        if (is_numeric($p['grape_type'])) {
            $grapeDisplay = 'Giống nho #' . $p['grape_type'];
        } else {
            $grapeDisplay = $p['grape_type'];
        }
    }
    $p['grape_type_display'] = $grapeDisplay;

    // ====== TÊN VÙNG ======
    $regionDisplay = '';
    if (!empty($p['region_name'])) {
        $regionDisplay = $p['region_name'];
    } elseif (!empty($p['region'])) {
        if (is_numeric($p['region'])) {
            $regionDisplay = 'Vùng #' . $p['region'];
        } else {
            $regionDisplay = $p['region'];
        }
    }
    $p['region_display'] = $regionDisplay;

    // ====== TÊN QUỐC GIA ======
    $countryDisplay = '';
    if (!empty($p['country_name'])) {
        $countryDisplay = $p['country_name'];
    } elseif (!empty($p['country'])) {
        if (is_numeric($p['country'])) {
            $countryDisplay = 'Quốc gia #' . $p['country'];
        } else {
            $countryDisplay = $p['country'];
        }
    }
    $p['country_display'] = $countryDisplay;
    
    // ===== ĐẢM BẢO main_image KHÔNG BỊ UNDEFINED =====
    if (!isset($p['main_image']) || $p['main_image'] === null || $p['main_image'] === '') {
        // Thử tìm ảnh bất kỳ trong product_images
        $imgStmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? LIMIT 1");
        if ($imgStmt) {
            $imgStmt->bind_param("i", $p['id']);
            $imgStmt->execute();
            $imgResult = $imgStmt->get_result();
            if ($imgRow = $imgResult->fetch_assoc()) {
                $p['main_image'] = $imgRow['image_url'];
            }
            $imgStmt->close();
        }
    }
    
    // Nếu vẫn không có ảnh, set về null để frontend xử lý
    if (!isset($p['main_image']) || $p['main_image'] === '') {
        $p['main_image'] = null;
    }
    
    // Gán danh mục
    $categories = $categoryMap[$p['id']] ?? [];
    $categoryIds = array_column($categories, 'id');
    
    if (!empty($categories)) {
        $p['categories'] = $categories;
        $p['category_ids'] = implode(',', $categoryIds);
        
        $categoryLevels = array_column($categories, 'level');
        $minLevel = min($categoryLevels);
        $topCategories = array_filter($categories, function($cat) use ($minLevel) {
            return $cat['level'] == $minLevel;
        });
        $topCategory = reset($topCategories);
        
        if ($topCategory && $topCategory['parent_id'] == 17) {
            $p['product_type'] = 2;
        } elseif ($topCategory && $topCategory['parent_id'] == 16) {
            $p['product_type'] = 1;
        } else {
            $hasWineParent = false;
            $hasSpiritParent = false;
            foreach ($categories as $cat) {
                if ($cat['parent_id'] == 16) $hasWineParent = true;
                if ($cat['parent_id'] == 17) $hasSpiritParent = true;
            }
            if ($hasSpiritParent) {
                $p['product_type'] = 2;
            } elseif ($hasWineParent) {
                $p['product_type'] = 1;
            }
        }
    } else {
        $p['category_ids'] = '16';
        $p['categories'] = [['id' => 16, 'name' => 'Rượu vang', 'slug' => 'ruou-vang', 'parent_id' => 0, 'level' => 0]];
        $p['product_type'] = 1;
    }
    
    $p['formatted_price'] = $p['price'] > 0 ? number_format($p['price'], 0, ',', '.') . 'đ' : 'Liên hệ';
    if ($p['old_price']) {
        $p['formatted_old_price'] = number_format($p['old_price'], 0, ',', '.') . 'đ';
    }
    
    $statusMap = [
        'visible' => 'Hiển thị',
        'hidden' => 'Ẩn',
        'coming_soon' => 'Sắp về',
        'discontinued' => 'Ngừng bán',
        'draft' => 'Nháp'
    ];
    $p['display_status_text'] = $statusMap[$p['display_status']] ?? $p['display_status'];
}

unset($p);

$conn->close();

$response['success'] = true;
$response['products'] = $products;
$response['totalCount'] = (int)$total;
$response['totalPages'] = $all ? 1 : ceil($total / $limit);
$response['currentPage'] = $page;
$response['limit'] = $limit;
$response['category_id'] = $cat_id;
$response['category_slug'] = $category_slug;

echo json_encode($response);
?>