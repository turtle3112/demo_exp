<?php
// api/product_detail.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$preview = isset($_GET['preview']) && $_GET['preview'] == '1';

if (empty($slug)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing slug']);
    exit;
}

// Lấy thông tin sản phẩm - JOIN với categories để lấy tên giống nho (parent_id = 46)
$sql = "SELECT 
    p.*,
    c.name as grape_name,
    r.name as region_name,
    co.name as country_name
    FROM products p
    LEFT JOIN categories c ON p.grape_type = c.id AND c.parent_id = 46
    LEFT JOIN regions r ON p.region = r.id
    LEFT JOIN countries co ON p.country = co.id
    WHERE p.slug = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $slug);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

// Lấy danh sách cột để kiểm tra sự tồn tại
$columns = [];
$colResult = $conn->query("SHOW COLUMNS FROM products");
while ($row = $colResult->fetch_assoc()) $columns[] = $row['Field'];

$isVisible = true;
$displayStatus = null;

// Xác định trạng thái hiển thị
if (in_array('display_status', $columns)) {
    $displayStatus = $product['display_status'];
    
    if ($preview) {
        $isVisible = true;
    } else {
        if ($displayStatus === 'hidden') {
            $isVisible = false;
        } elseif ($displayStatus === 'visible') {
            if (in_array('schedule_start', $columns) && in_array('schedule_end', $columns)) {
                $now = date('Y-m-d H:i:s');
                if (($product['schedule_start'] && $product['schedule_start'] > $now) ||
                    ($product['schedule_end'] && $product['schedule_end'] < $now)) {
                    $isVisible = false;
                }
            }
        } elseif ($displayStatus === 'coming_soon' || $displayStatus === 'discontinued') {
            $isVisible = true;
        } elseif ($displayStatus === 'draft') {
            $isVisible = false;
        } else {
            $isVisible = false;
        }
    }
} elseif (in_array('status', $columns)) {
    if ($product['status'] != 1 && !$preview) {
        $isVisible = false;
    } else {
        $isVisible = true;
    }
} else {
    $isVisible = true;
}

if (!$isVisible && !$preview) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not available']);
    exit;
}

// Lấy ảnh chính
$stmtImg = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1");
$stmtImg->bind_param("i", $product['id']);
$stmtImg->execute();
$mainImg = $stmtImg->get_result()->fetch_assoc();
$product['main_image'] = $mainImg['image_url'] ?? null;

// Lấy toàn bộ gallery
$stmtGallery = $conn->prepare("SELECT id, image_url, is_main, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
$stmtGallery->bind_param("i", $product['id']);
$stmtGallery->execute();
$product['gallery'] = $stmtGallery->get_result()->fetch_all(MYSQLI_ASSOC);

// ====== LẤY DANH MỤC CỦA SẢN PHẨM ======
$categories = [];
$hasProductCategories = $conn->query("SHOW TABLES LIKE 'product_categories'")->num_rows > 0;
$hasCategories = $conn->query("SHOW TABLES LIKE 'categories'")->num_rows > 0;

if ($hasProductCategories && $hasCategories) {
    $catSql = "
        SELECT 
            c.id, 
            c.name, 
            c.slug,
            c.parent_id,
            c.level,
            c.description
        FROM categories c 
        INNER JOIN product_categories pc ON c.id = pc.category_id 
        WHERE pc.product_id = ?
        ORDER BY c.parent_id ASC, c.id ASC
    ";
    $catStmt = $conn->prepare($catSql);
    $catStmt->bind_param("i", $product['id']);
    $catStmt->execute();
    $catResult = $catStmt->get_result();
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'parent_id' => (int)$row['parent_id'],
            'level' => isset($row['level']) ? (int)$row['level'] : 0,
            'description' => $row['description'] ?? ''
        ];
    }
    $catStmt->close();
}

// ====== NẾU KHÔNG CÓ CATEGORIES, THỬ LẤY TỪ PRODUCT_TYPE (FALLBACK) ======
if (empty($categories)) {
    $defaultCatId = null;
    
    // Map product_type sang category_id (chỉ fallback khi không có categories)
    if (isset($product['product_type'])) {
        switch ($product['product_type']) {
            case 1:
                $defaultCatId = 16; // Rượu vang
                break;
            case 2:
                $defaultCatId = 17; // Rượu mạnh
                break;
            case 3:
                $defaultCatId = 38; // Bia ngoại
                break;
            case 4:
                $defaultCatId = 39; // Quà tặng
                break;
            default:
                $defaultCatId = null;
                break;
        }
    }
    
    // Nếu vẫn chưa có, thử tìm category dựa trên tên sản phẩm
    if (!$defaultCatId) {
        $productName = strtolower($product['name'] ?? '');
        if (strpos($productName, 'bia') !== false || strpos($productName, 'beer') !== false) {
            $defaultCatId = 38;
        } elseif (strpos($productName, 'whisky') !== false || strpos($productName, 'cognac') !== false || 
                  strpos($productName, 'rum') !== false || strpos($productName, 'gin') !== false ||
                  strpos($productName, 'brandy') !== false || strpos($productName, 'vodka') !== false) {
            $defaultCatId = 17;
        } elseif (strpos($productName, 'quà tặng') !== false || strpos($productName, 'gift') !== false ||
                  strpos($productName, 'hộp quà') !== false) {
            $defaultCatId = 39;
        }
    }
    
    if ($defaultCatId) {
        $defaultStmt = $conn->prepare("SELECT id, name, slug, parent_id, level FROM categories WHERE id = ?");
        $defaultStmt->bind_param("i", $defaultCatId);
        $defaultStmt->execute();
        $defaultCat = $defaultStmt->get_result()->fetch_assoc();
        if ($defaultCat) {
            $categories[] = [
                'id' => (int)$defaultCat['id'],
                'name' => $defaultCat['name'],
                'slug' => $defaultCat['slug'],
                'parent_id' => (int)$defaultCat['parent_id'],
                'level' => isset($defaultCat['level']) ? (int)$defaultCat['level'] : 0,
                'description' => ''
            ];
        }
        $defaultStmt->close();
    }
}

$product['categories'] = $categories;

// ================================================================
// ====== XỬ LÝ GIỐNG NHO ======
// ================================================================

$grapeDisplay = '';
if (!empty($product['grape_name'])) {
    $grapeDisplay = $product['grape_name'];
} elseif (!empty($product['grape_type'])) {
    if (is_numeric($product['grape_type'])) {
        // Tìm trong categories có parent_id = 46 (danh mục giống nho)
        $grapeStmt = $conn->prepare("SELECT name FROM categories WHERE id = ? AND parent_id = 46");
        $grapeStmt->bind_param("i", $product['grape_type']);
        $grapeStmt->execute();
        $grapeResult = $grapeStmt->get_result();
        $grapeRow = $grapeResult->fetch_assoc();
        if ($grapeRow) {
            $grapeDisplay = $grapeRow['name'];
        } else {
            // Thử lấy từ categories không cần parent_id
            $grapeStmt2 = $conn->prepare("SELECT name FROM categories WHERE id = ?");
            $grapeStmt2->bind_param("i", $product['grape_type']);
            $grapeStmt2->execute();
            $grapeResult2 = $grapeStmt2->get_result();
            $grapeRow2 = $grapeResult2->fetch_assoc();
            if ($grapeRow2) {
                $grapeDisplay = $grapeRow2['name'];
            } else {
                $grapeDisplay = 'Giống nho #' . $product['grape_type'];
            }
            $grapeStmt2->close();
        }
        $grapeStmt->close();
    } else {
        $grapeDisplay = $product['grape_type'];
    }
}

// Xử lý vùng
$regionDisplay = '';
if (!empty($product['region_name'])) {
    $regionDisplay = $product['region_name'];
} elseif (!empty($product['region'])) {
    if (is_numeric($product['region'])) {
        $regionStmt = $conn->prepare("SELECT name FROM regions WHERE id = ?");
        $regionStmt->bind_param("i", $product['region']);
        $regionStmt->execute();
        $regionResult = $regionStmt->get_result();
        $regionRow = $regionResult->fetch_assoc();
        if ($regionRow) {
            $regionDisplay = $regionRow['name'];
        } else {
            $regionDisplay = 'Vùng #' . $product['region'];
        }
        $regionStmt->close();
    } else {
        $regionDisplay = $product['region'];
    }
}

// Xử lý quốc gia
$countryDisplay = '';
if (!empty($product['country_name'])) {
    $countryDisplay = $product['country_name'];
} elseif (!empty($product['country'])) {
    if (is_numeric($product['country'])) {
        $countryStmt = $conn->prepare("SELECT name FROM countries WHERE id = ?");
        $countryStmt->bind_param("i", $product['country']);
        $countryStmt->execute();
        $countryResult = $countryStmt->get_result();
        $countryRow = $countryResult->fetch_assoc();
        if ($countryRow) {
            $countryDisplay = $countryRow['name'];
        } else {
            $countryDisplay = 'Quốc gia #' . $product['country'];
        }
        $countryStmt->close();
    } else {
        $countryDisplay = $product['country'];
    }
}

// Gán lại vào product
$product['grape_type_display'] = $grapeDisplay;
$product['region_display'] = $regionDisplay;
$product['country_display'] = $countryDisplay;

// ================================================================

// Parse JSON fields
if (!empty($product['dynamic_attributes'])) {
    $product['dynamic_attributes'] = json_decode($product['dynamic_attributes'], true);
} else {
    $product['dynamic_attributes'] = [];
}
if (!empty($product['variants'])) {
    $product['variants'] = json_decode($product['variants'], true);
} else {
    $product['variants'] = [];
}

// Thêm trường desc_gallery nếu có
if (in_array('desc_gallery', $columns) && !empty($product['desc_gallery'])) {
    $product['desc_gallery'] = json_decode($product['desc_gallery'], true);
} else {
    $product['desc_gallery'] = [];
}

// Thêm trường brand (từ producers nếu có)
if (in_array('brand', $columns) && empty($product['brand']) && in_array('producer_id', $columns) && $product['producer_id']) {
    $stmtBrand = $conn->prepare("SELECT name FROM producers WHERE id = ?");
    $stmtBrand->bind_param("i", $product['producer_id']);
    $stmtBrand->execute();
    $brandResult = $stmtBrand->get_result()->fetch_assoc();
    if ($brandResult) {
        $product['brand'] = $brandResult['name'];
    }
    $stmtBrand->close();
}

$stmt->close();
$stmtImg->close();
$stmtGallery->close();
$conn->close();

echo json_encode($product);
?>