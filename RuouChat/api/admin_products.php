<?php
/**
 * api/admin_products.php
 * API quản lý sản phẩm cho admin (CRUD, import/export, bulk actions)
 * Hỗ trợ nhiều danh mục cho 1 sản phẩm (many-to-many)
 * LOẠI BỎ HOÀN TOÀN PRODUCT_TYPE - Phân loại qua categories
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . ($conn->connect_error ?? 'unknown')]);
    exit;
}

function createSlug($str) {
    $str = trim(mb_strtolower($str));
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

/**
 * Lấy slug danh mục đầu tiên của sản phẩm
 */
function getPrimaryCategorySlug($productId, $conn) {
    $catStmt = $conn->prepare("SELECT c.slug FROM categories c 
                               INNER JOIN product_categories pc ON c.id = pc.category_id 
                               WHERE pc.product_id = ? 
                               ORDER BY pc.id ASC LIMIT 1");
    $catStmt->bind_param("i", $productId);
    $catStmt->execute();
    $result = $catStmt->get_result();
    $category = $result->fetch_assoc();
    $catStmt->close();
    return $category ? $category['slug'] : 'san-pham';
}

/**
 * Format giá hiển thị
 */
function formatPrice($price) {
    if ($price === null || $price === 0 || $price === '') {
        return 'Liên hệ';
    }
    return number_format(floatval($price), 0, ',', '.') . 'đ';
}

/**
 * Tạo URL demo cho sản phẩm
 */
function getDemoUrl($product, $conn) {
    // Lấy slug từ danh mục đầu tiên
    $categorySlug = getPrimaryCategorySlug($product['id'], $conn);
    $productSlug = $product['slug'] ?? 'san-pham-' . $product['id'];
    return 'https://ruouchat.vn/' . $categorySlug . '/' . $productSlug;
}

function sendError($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function sendSuccess($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function bindParam(&$stmt, &$types, &$params, $value, $type) {
    if ($value === null) {
        $types .= 's';
        $params[] = null;
    } else {
        $types .= $type;
        $params[] = $value;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

// ========== EXPORT EXCEL ==========
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'export') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=products_export_'.date('Ymd_His').'.xls');
    
    $sql = "SELECT p.*, s.name as supplier_name 
            FROM products p 
            LEFT JOIN producers s ON p.producer_id = s.id 
            ORDER BY p.id DESC";
    $result = $conn->query($sql);
    $products = $result->fetch_all(MYSQLI_ASSOC);
    
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Export</title></head><body>';
    echo '<table border="1">';
    echo '<tr><th>ID</th><th>Tên</th><th>Giá</th><th>SKU</th><th>Tồn kho</th><th>Quốc gia</th><th>Vùng</th><th>Giống nho</th><th>NSX</th><th>Trạng thái</th><th>Vận chuyển đặc biệt</th><th>Tự động chuyển</th><th>Danh mục</th></tr>';
    foreach ($products as $p) {
        // Lấy danh sách danh mục
        $catStmt = $conn->prepare("SELECT GROUP_CONCAT(c.name SEPARATOR ', ') as cat_names 
                                   FROM categories c 
                                   INNER JOIN product_categories pc ON c.id = pc.category_id 
                                   WHERE pc.product_id = ?");
        $catStmt->bind_param("i", $p['id']);
        $catStmt->execute();
        $catResult = $catStmt->get_result();
        $catNames = $catResult->fetch_assoc()['cat_names'] ?? '';
        $catStmt->close();
        
        // Format giá
        $priceDisplay = formatPrice($p['price']);
        
        echo '<tr>';
        echo '<td>'.$p['id'].'</td>';
        echo '<td>'.htmlspecialchars($p['name']).'</td>';
        echo '<td>'.$priceDisplay.'</td>';
        echo '<td>'.htmlspecialchars($p['sku']??'').'</td>';
        echo '<td>'.$p['stock_quantity'].'</td>';
        echo '<td>'.htmlspecialchars($p['country']??'').'</td>';
        echo '<td>'.htmlspecialchars($p['region']??'').'</td>';
        echo '<td>'.htmlspecialchars($p['grape_type']??'').'</td>';
        echo '<td>'.htmlspecialchars($p['supplier_name']??'').'</td>';
        echo '<td>'.$p['display_status'].'</td>';
        echo '<td>'.($p['requires_special_shipping'] ? 'Có' : 'Không').'</td>';
        echo '<td>'.($p['auto_status_update'] ? 'Bật' : 'Tắt').'</td>';
        echo '<td>'.htmlspecialchars($catNames).'</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

// ========== IMPORT CSV ==========
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'import') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        sendError('File upload failed', 400);
    }
    
    $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if (!$file) sendError('Cannot read file', 500);
    
    $headers = fgetcsv($file);
    if (!$headers) sendError('Invalid CSV format', 400);
    
    $imported = 0;
    $errors = [];
    
    while (($row = fgetcsv($file)) !== false) {
        $data = array_combine($headers, $row);
        
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors[] = "Invalid data for row: " . json_encode($data);
            continue;
        }
        
        // Price có thể để trống -> Liên hệ
        $price = isset($data['price']) && $data['price'] !== '' && $data['price'] !== 'Liên hệ' ? floatval($data['price']) : null;
        
        $slug = !empty($data['slug']) ? $data['slug'] : createSlug($name);
        $sku = $data['sku'] ?? null;
        $stock_quantity = intval($data['stock_quantity'] ?? 0);
        $display_status = $data['display_status'] ?? 'visible';
        $country = $data['country'] ?? null;
        $region = $data['region'] ?? null;
        $grape_type = $data['grape_type'] ?? null;
        
        // Kiểm tra slug unique
        $originalSlug = $slug;
        $counter = 1;
        $checkStmt = $conn->prepare("SELECT id FROM products WHERE slug = ?");
        while (true) {
            $checkStmt->bind_param("s", $slug);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows == 0) break;
            $slug = $originalSlug . '-' . $counter++;
        }
        $checkStmt->close();
        
        $sql = "INSERT INTO products (name, slug, price, sku, stock_quantity, display_status, country, region, grape_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdisssss", $name, $slug, $price, $sku, $stock_quantity, $display_status, $country, $region, $grape_type);
        
        if ($stmt->execute()) {
            $imported++;
        } else {
            $errors[] = "Failed to insert product: $name - " . $stmt->error;
        }
        $stmt->close();
    }
    
    fclose($file);
    
    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'errors' => $errors
    ]);
    exit;
}

// ========== BULK ACTIONS ==========
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'bulk') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['bulk_action']) || !isset($input['ids'])) {
        sendError('Invalid bulk action request', 400);
    }
    
    $action = $input['bulk_action'];
    $ids = $input['ids'];
    
    if (empty($ids) || !is_array($ids)) {
        sendError('No products selected', 400);
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    
    switch ($action) {
        case 'delete':
            $stmtCat = $conn->prepare("DELETE FROM product_categories WHERE product_id IN ($placeholders)");
            $stmtCat->bind_param($types, ...$ids);
            $stmtCat->execute();
            $stmtCat->close();
            
            $stmtImg = $conn->prepare("DELETE FROM product_images WHERE product_id IN ($placeholders)");
            $stmtImg->bind_param($types, ...$ids);
            $stmtImg->execute();
            $stmtImg->close();
            
            $stmt = $conn->prepare("DELETE FROM products WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$ids);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'deleted' => $stmt->affected_rows]);
            } else {
                sendError('Delete failed: ' . $stmt->error, 500);
            }
            $stmt->close();
            break;
            
        case 'change_status':
            if (!isset($input['new_status'])) sendError('Missing new_status', 400);
            $newStatus = $input['new_status'];
            $stmt = $conn->prepare("UPDATE products SET display_status = ? WHERE id IN ($placeholders)");
            $stmt->bind_param('s' . $types, $newStatus, ...$ids);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'updated' => $stmt->affected_rows]);
            } else {
                sendError('Update failed: ' . $stmt->error, 500);
            }
            $stmt->close();
            break;
            
        case 'change_price':
            if (!isset($input['new_price'])) sendError('Missing new_price', 400);
            $newPrice = $input['new_price'] !== '' && $input['new_price'] !== 'Liên hệ' ? floatval($input['new_price']) : null;
            $stmt = $conn->prepare("UPDATE products SET price = ? WHERE id IN ($placeholders)");
            $stmt->bind_param('d' . $types, $newPrice, ...$ids);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'updated' => $stmt->affected_rows]);
            } else {
                sendError('Update failed: ' . $stmt->error, 500);
            }
            $stmt->close();
            break;
            
        case 'add_categories':
            if (!isset($input['category_ids']) || !is_array($input['category_ids'])) {
                sendError('Missing category_ids', 400);
            }
            $categoryIds = $input['category_ids'];
            $added = 0;
            foreach ($ids as $productId) {
                foreach ($categoryIds as $catId) {
                    $checkStmt = $conn->prepare("SELECT id FROM product_categories WHERE product_id = ? AND category_id = ?");
                    $checkStmt->bind_param("ii", $productId, $catId);
                    $checkStmt->execute();
                    $checkStmt->store_result();
                    if ($checkStmt->num_rows == 0) {
                        $insertStmt = $conn->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
                        $insertStmt->bind_param("ii", $productId, $catId);
                        if ($insertStmt->execute()) $added++;
                        $insertStmt->close();
                    }
                    $checkStmt->close();
                }
            }
            echo json_encode(['success' => true, 'added' => $added]);
            break;
            
        case 'remove_categories':
            if (!isset($input['category_ids']) || !is_array($input['category_ids'])) {
                sendError('Missing category_ids', 400);
            }
            $categoryIds = $input['category_ids'];
            $removed = 0;
            foreach ($ids as $productId) {
                foreach ($categoryIds as $catId) {
                    $stmt = $conn->prepare("DELETE FROM product_categories WHERE product_id = ? AND category_id = ?");
                    $stmt->bind_param("ii", $productId, $catId);
                    if ($stmt->execute() && $stmt->affected_rows > 0) $removed++;
                    $stmt->close();
                }
            }
            echo json_encode(['success' => true, 'removed' => $removed]);
            break;
            
        case 'change_supplier':
            if (!isset($input['supplier_id'])) sendError('Missing supplier_id', 400);
            $supplierId = intval($input['supplier_id']);
            $stmt = $conn->prepare("UPDATE products SET producer_id = ? WHERE id IN ($placeholders)");
            $stmt->bind_param('i' . $types, $supplierId, ...$ids);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'updated' => $stmt->affected_rows]);
            } else {
                sendError('Update failed: ' . $stmt->error, 500);
            }
            $stmt->close();
            break;
            
        default:
            sendError('Unknown bulk action: ' . $action, 400);
    }
    exit;
}

// ========== GET danh sách hoặc chi tiết ==========
if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Lấy chi tiết 1 sản phẩm
    if ($id) {
        $stmt = $conn->prepare("SELECT p.*, 
                                        (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image,
                                        (SELECT alt_text FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image_alt,
                                        s.name as supplier_name
                                FROM products p
                                LEFT JOIN producers s ON p.producer_id = s.id
                                WHERE p.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if ($product) {
            // Lấy danh sách danh mục
            $catStmt = $conn->prepare("SELECT c.id, c.name, c.slug FROM categories c 
                                       INNER JOIN product_categories pc ON c.id = pc.category_id 
                                       WHERE pc.product_id = ?");
            $catStmt->bind_param("i", $id);
            $catStmt->execute();
            $product['categories'] = $catStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $catStmt->close();
            
            // Lấy gallery images
            $imgStmt = $conn->prepare("SELECT id, image_url, thumb_url, alt_text, is_main, sort_order 
                                      FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
            $imgStmt->bind_param("i", $id);
            $imgStmt->execute();
            $product['gallery_images'] = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $imgStmt->close();
            
            // Tạo URL demo
            $categorySlug = getPrimaryCategorySlug($product['id'], $conn);
            $productSlug = $product['slug'] ?? 'san-pham-' . $product['id'];
            $product['demo_url'] = 'https://ruouchat.vn/' . $categorySlug . '/' . $productSlug;
            
            // SEO data
            $product['seo_title'] = $product['seo_title'] ?? $product['name'];
            $product['seo_description'] = $product['seo_description'] ?? '';
            $product['seo_url'] = $product['demo_url'];
            
            // Thêm price_formatted để frontend hiển thị
            $product['price_formatted'] = formatPrice($product['price']);
        }
        
        echo json_encode($product);
        exit;
    }
    
    // ========== LẤY DANH SÁCH ==========
    $where = [];
    $params = [];
    $types = "";
    
    if (isset($_GET['category_ids']) && $_GET['category_ids'] != '') {
        $catIds = explode(',', $_GET['category_ids']);
        $placeholders = implode(',', array_fill(0, count($catIds), '?'));
        $where[] = "p.id IN (SELECT DISTINCT product_id FROM product_categories WHERE category_id IN ($placeholders))";
        foreach ($catIds as $catId) {
            $params[] = intval($catId);
            $types .= "i";
        }
    }
    
    if (isset($_GET['display_status']) && $_GET['display_status'] != '') {
        $where[] = "p.display_status = ?";
        $params[] = $_GET['display_status'];
        $types .= "s";
    } else {
        $where[] = "p.display_status IN ('visible', 'coming_soon')";
    }
    
    $where[] = "p.status = 1";
    
    if (isset($_GET['search']) && $_GET['search'] != '') {
        $where[] = "p.name LIKE ?";
        $params[] = "%".$_GET['search']."%";
        $types .= "s";
    }
    
    if (isset($_GET['country']) && $_GET['country'] != '') {
        $where[] = "p.country = ?";
        $params[] = $_GET['country'];
        $types .= "s";
    }
    
    if (isset($_GET['region']) && $_GET['region'] != '') {
        $where[] = "p.region = ?";
        $params[] = $_GET['region'];
        $types .= "s";
    }
    
    if (isset($_GET['grape_type']) && $_GET['grape_type'] != '') {
        $where[] = "p.grape_type = ?";
        $params[] = $_GET['grape_type'];
        $types .= "s";
    }
    
    if (isset($_GET['wine_type']) && $_GET['wine_type'] != '') {
        $where[] = "p.wine_type = ?";
        $params[] = $_GET['wine_type'];
        $types .= "s";
    }
    
    if (isset($_GET['spirit_type']) && $_GET['spirit_type'] != '') {
        $where[] = "p.spirit_type = ?";
        $params[] = $_GET['spirit_type'];
        $types .= "s";
    }
    
    if (isset($_GET['brand']) && $_GET['brand'] != '') {
        $where[] = "(p.brand = ? OR p.producer LIKE ?)";
        $params[] = $_GET['brand'];
        $params[] = "%".$_GET['brand']."%";
        $types .= "ss";
    }
    
    if (isset($_GET['producer_id']) && $_GET['producer_id'] != '') {
        $where[] = "p.producer_id = ?";
        $params[] = intval($_GET['producer_id']);
        $types .= "i";
    }
    
    if (isset($_GET['stock_status']) && $_GET['stock_status'] != '') {
        $stockStatus = $_GET['stock_status'];
        if ($stockStatus === 'in_stock') {
            $where[] = "p.stock_quantity > 5";
        } elseif ($stockStatus === 'low_stock') {
            $where[] = "p.stock_quantity <= 5 AND p.stock_quantity > 0";
        } elseif ($stockStatus === 'out_of_stock') {
            $where[] = "p.stock_quantity = 0";
        }
    }
    
    if (isset($_GET['min_price']) && $_GET['min_price'] != '') {
        $where[] = "p.price >= ?";
        $params[] = floatval($_GET['min_price']);
        $types .= "d";
    }
    if (isset($_GET['max_price']) && $_GET['max_price'] != '') {
        $where[] = "p.price <= ?";
        $params[] = floatval($_GET['max_price']);
        $types .= "d";
    }
    
    if (isset($_GET['tag']) && $_GET['tag'] != '') {
        $where[] = "p.tags LIKE ?";
        $params[] = "%".$_GET['tag']."%";
        $types .= "s";
    }
    
    $whereClause = empty($where) ? "1=1" : implode(" AND ", $where);
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 1000;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    $orderBy = "p.id DESC";
    if (isset($_GET['sort'])) {
        switch ($_GET['sort']) {
            case 'price_asc':  $orderBy = "p.price ASC"; break;
            case 'price_desc': $orderBy = "p.price DESC"; break;
            case 'name_asc':   $orderBy = "p.name ASC"; break;
            case 'name_desc':  $orderBy = "p.name DESC"; break;
            case 'id_asc':     $orderBy = "p.id ASC"; break;
            case 'stock_asc':  $orderBy = "p.stock_quantity ASC"; break;
            case 'stock_desc': $orderBy = "p.stock_quantity DESC"; break;
            default:           $orderBy = "p.id DESC";
        }
    }
    
    $sql = "SELECT p.*, 
                    (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image,
                    (SELECT alt_text FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image_alt,
                    s.name as supplier_name
            FROM products p
            LEFT JOIN producers s ON p.producer_id = s.id
            WHERE $whereClause
            ORDER BY $orderBy
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) sendError('Prepare error: ' . $conn->error, 500);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($products as &$p) {
        $catStmt = $conn->prepare("SELECT c.id, c.name FROM categories c 
                                   INNER JOIN product_categories pc ON c.id = pc.category_id 
                                   WHERE pc.product_id = ?");
        $catStmt->bind_param("i", $p['id']);
        $catStmt->execute();
        $p['categories'] = $catStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $catStmt->close();
        
        $p['price'] = $p['price'] !== null ? floatval($p['price']) : null;
        $p['old_price'] = $p['old_price'] !== null ? floatval($p['old_price']) : null;
        $p['stock_quantity'] = intval($p['stock_quantity']);
        $p['main_image'] = $p['main_image'] ?? '';
        $p['country'] = $p['country'] ?? '';
        $p['region'] = $p['region'] ?? '';
        $p['grape_type'] = $p['grape_type'] ?? '';
        $p['wine_type'] = $p['wine_type'] ?? '';
        $p['spirit_type'] = $p['spirit_type'] ?? '';
        $p['brand'] = $p['brand'] ?? '';
        $p['abv'] = $p['abv'] ?? $p['alcohol'] ?? '13%';
        $p['slug'] = $p['slug'] ?? 'san-pham-' . $p['id'];
        
        // Thêm price_formatted để frontend hiển thị
        $p['price_formatted'] = formatPrice($p['price']);
        
        // Tạo demo_url
        $categorySlug = getPrimaryCategorySlug($p['id'], $conn);
        $p['demo_url'] = 'https://ruouchat.vn/' . $categorySlug . '/' . $p['slug'];
        
        // SEO data
        $p['seo_title'] = $p['seo_title'] ?? $p['name'];
        $p['seo_description'] = $p['seo_description'] ?? '';
        $p['seo_url'] = $p['demo_url'];
    }
    unset($p);
    
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
        $total = $countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    exit;
}

// ========== DELETE ==========
if ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) sendError('Missing ID');
    
    $conn->query("DELETE FROM product_categories WHERE product_id = $id");
    $conn->query("DELETE FROM product_images WHERE product_id = $id");
    $conn->query("DELETE FROM products WHERE id = $id");
    
    echo json_encode(['success' => true]);
    exit;
}

// ========== CLONE PRODUCT ==========
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'clone') {
    $originalId = intval($_GET['id'] ?? 0);
    if (!$originalId) sendError('Missing original product ID');
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $originalId);
    $stmt->execute();
    $original = $stmt->get_result()->fetch_assoc();
    if (!$original) sendError('Original product not found', 404);
    
    $newName = $original['name'] . ' (Copy)';
    $newSlug = createSlug($newName);
    $counter = 1;
    $tempSlug = $newSlug;
    while (true) {
        $check = $conn->prepare("SELECT id FROM products WHERE slug = ?");
        $check->bind_param("s", $tempSlug);
        $check->execute();
        $check->store_result();
        if ($check->num_rows == 0) break;
        $tempSlug = $newSlug . '-' . $counter++;
        $check->close();
    }
    $newSlug = $tempSlug;
    
    $insertSql = "INSERT INTO products (
        name, slug, price, old_price, description, sku, barcode, weight_gram, stock_quantity,
        display_status, featured, schedule_start, schedule_end, dynamic_attributes, variants,
        wine_type, grape_type, country, region, spirit_type, brand, age, producer_id,
        seo_title, seo_description, requires_special_shipping, auto_status_update, status
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, 1
    )";
    
    $stmtIns = $conn->prepare($insertSql);
    if (!$stmtIns) {
        sendError('Clone prepare failed: ' . $conn->error, 500);
    }
    
    $newStock = 0;
    $newDisplay = 'draft';
    $stmtIns->bind_param(
        "ssddsississsssssssssssisssii",
        $newName, $newSlug,
        $original['price'], $original['old_price'], $original['description'],
        $original['sku'], $original['barcode'], $original['weight_gram'], $newStock,
        $newDisplay, $original['featured'], $original['schedule_start'], $original['schedule_end'],
        $original['dynamic_attributes'], $original['variants'],
        $original['wine_type'], $original['grape_type'], $original['country'], $original['region'],
        $original['spirit_type'], $original['brand'], $original['age'],
        $original['producer_id'],
        $original['seo_title'], $original['seo_description'],
        $original['requires_special_shipping'], $original['auto_status_update']
    );
    if (!$stmtIns->execute()) {
        sendError('Clone insert failed: ' . $stmtIns->error, 500);
    }
    $newId = $conn->insert_id;
    $stmtIns->close();
    
    // Clone danh mục
    $catStmt = $conn->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
    $catStmt->bind_param("i", $originalId);
    $catStmt->execute();
    $catResult = $catStmt->get_result();
    $insertCat = $conn->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
    while ($cat = $catResult->fetch_assoc()) {
        $insertCat->bind_param("ii", $newId, $cat['category_id']);
        $insertCat->execute();
    }
    $catStmt->close();
    $insertCat->close();
    
    // Clone ảnh
    $imgQuery = $conn->prepare("SELECT image_url, thumb_url, alt_text, is_main, sort_order FROM product_images WHERE product_id = ?");
    $imgQuery->bind_param("i", $originalId);
    $imgQuery->execute();
    $imgResult = $imgQuery->get_result();
    $imgStmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, thumb_url, alt_text, is_main, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    while ($row = $imgResult->fetch_assoc()) {
        $imgStmt->bind_param("isssii", $newId, $row['image_url'], $row['thumb_url'], $row['alt_text'], $row['is_main'], $row['sort_order']);
        $imgStmt->execute();
    }
    $imgQuery->close();
    $imgStmt->close();
    
    echo json_encode(['success' => true, 'new_id' => $newId]);
    exit;
}

// ========== POST (CREATE) & PUT (UPDATE) ==========
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendError('Invalid JSON input: ' . json_last_error_msg(), 400);
}

$id = intval($input['id'] ?? 0);
$name = trim($input['name'] ?? '');

if (!$name) {
    sendError('Tên sản phẩm là bắt buộc');
}

// Price có thể là null (Liên hệ) hoặc số dương
$price = isset($input['price']) && $input['price'] !== '' && $input['price'] !== null && $input['price'] !== 'Liên hệ' ? floatval($input['price']) : null;
if ($price === 0) {
    $price = null;
}

// Xử lý slug
$slug = !empty($input['slug']) ? $input['slug'] : createSlug($name);
$originalSlug = $slug;
$counter = 1;
if ($method === 'PUT') {
    $checkStmt = $conn->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
    while (true) {
        $checkStmt->bind_param("si", $slug, $id);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows == 0) break;
        $slug = $originalSlug . '-' . $counter++;
    }
    $checkStmt->close();
} else {
    $checkStmt = $conn->prepare("SELECT id FROM products WHERE slug = ?");
    while (true) {
        $checkStmt->bind_param("s", $slug);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows == 0) break;
        $slug = $originalSlug . '-' . $counter++;
    }
    $checkStmt->close();
}

$description = $input['description'] ?? '';
$old_price = isset($input['old_price']) && $input['old_price'] !== '' && $input['old_price'] !== null ? floatval($input['old_price']) : null;
$sku = $input['sku'] ?? null;
$barcode = $input['barcode'] ?? null;
$weight_gram = intval($input['weight_gram'] ?? 0);
$stock_quantity = intval($input['stock_quantity'] ?? 0);
$display_status = $input['display_status'] ?? 'visible';
$featured = intval($input['featured'] ?? 0);
$main_image = $input['main_image'] ?? '';
$main_image_alt = $input['main_image_alt'] ?? '';
$schedule_start = !empty($input['schedule_start']) ? date('Y-m-d H:i:s', strtotime($input['schedule_start'])) : null;
$schedule_end = !empty($input['schedule_end']) ? date('Y-m-d H:i:s', strtotime($input['schedule_end'])) : null;
$dynamic_attributes = json_encode($input['dynamic_attributes'] ?? []);
$variants = json_encode($input['variants'] ?? []);
$wine_type = $input['wine_type'] ?? null;
$grape_type = $input['grape_type'] ?? null;
$country = $input['country'] ?? null;
$region = $input['region'] ?? null;
$spirit_type = $input['spirit_type'] ?? null;
$brand = $input['brand'] ?? null;
$age = $input['age'] ?? null;
$seo_title = $input['seo_title'] ?? null;
$seo_description = $input['seo_description'] ?? null;
$requires_special_shipping = isset($input['requires_special_shipping']) ? (int)$input['requires_special_shipping'] : 0;
$auto_status_update = isset($input['auto_status_update']) ? (int)$input['auto_status_update'] : 1;
$category_ids = $input['category_ids'] ?? [];
$abv = $input['abv'] ?? null;
$volume = $input['volume'] ?? null;

// BỎ product_type hoàn toàn

// Tự động chuyển trạng thái
if ($auto_status_update && $stock_quantity <= 0 && $display_status === 'visible') {
    $display_status = 'discontinued';
}

// Xử lý producer_id
$producer_id = null;
if (!empty($input['producer_name'])) {
    $producer_name = trim($input['producer_name']);
    $stmt = $conn->prepare("SELECT id FROM producers WHERE name = ?");
    $stmt->bind_param("s", $producer_name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $producer_id = $row['id'];
    } else {
        $stmtIns = $conn->prepare("INSERT INTO producers (name) VALUES (?)");
        $stmtIns->bind_param("s", $producer_name);
        $stmtIns->execute();
        $producer_id = $conn->insert_id;
        $stmtIns->close();
    }
    $stmt->close();
} elseif (!empty($input['producer_id']) && is_numeric($input['producer_id'])) {
    $producer_id = intval($input['producer_id']);
}

// ========== CREATE ==========
if ($method === 'POST') {
    $sql = "INSERT INTO products (
        name, slug, price, old_price, abv, volume, description, sku, barcode, 
        weight_gram, stock_quantity, display_status, featured, schedule_start, 
        schedule_end, dynamic_attributes, variants, wine_type, grape_type, 
        country, region, spirit_type, brand, age, producer_id,
        seo_title, seo_description, seo_keywords, requires_special_shipping, 
        auto_status_update, status
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1
    )";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error, 500);
    }
    
    $types = '';
    $params = [];
    bindParam($stmt, $types, $params, $name, 's');
    bindParam($stmt, $types, $params, $slug, 's');
    bindParam($stmt, $types, $params, $price, 'd');
    bindParam($stmt, $types, $params, $old_price, 'd');
    bindParam($stmt, $types, $params, $abv, 's');
    bindParam($stmt, $types, $params, $volume, 's');
    bindParam($stmt, $types, $params, $description, 's');
    bindParam($stmt, $types, $params, $sku, 's');
    bindParam($stmt, $types, $params, $barcode, 's');
    bindParam($stmt, $types, $params, $weight_gram, 'i');
    bindParam($stmt, $types, $params, $stock_quantity, 'i');
    bindParam($stmt, $types, $params, $display_status, 's');
    bindParam($stmt, $types, $params, $featured, 'i');
    bindParam($stmt, $types, $params, $schedule_start, 's');
    bindParam($stmt, $types, $params, $schedule_end, 's');
    bindParam($stmt, $types, $params, $dynamic_attributes, 's');
    bindParam($stmt, $types, $params, $variants, 's');
    bindParam($stmt, $types, $params, $wine_type, 's');
    bindParam($stmt, $types, $params, $grape_type, 's');
    bindParam($stmt, $types, $params, $country, 's');
    bindParam($stmt, $types, $params, $region, 's');
    bindParam($stmt, $types, $params, $spirit_type, 's');
    bindParam($stmt, $types, $params, $brand, 's');
    bindParam($stmt, $types, $params, $age, 's');
    bindParam($stmt, $types, $params, $producer_id, 'i');
    bindParam($stmt, $types, $params, $seo_title, 's');
    bindParam($stmt, $types, $params, $seo_description, 's');
    bindParam($stmt, $types, $params, $input['seo_keywords'] ?? null, 's');
    bindParam($stmt, $types, $params, $requires_special_shipping, 'i');
    bindParam($stmt, $types, $params, $auto_status_update, 'i');
    
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        
        if (!empty($category_ids)) {
            $catStmt = $conn->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
            foreach ($category_ids as $catId) {
                $catStmt->bind_param("ii", $new_id, $catId);
                $catStmt->execute();
            }
            $catStmt->close();
        }
        
        if (!empty($main_image)) {
            $stmt_img = $conn->prepare("INSERT INTO product_images (product_id, image_url, alt_text, is_main, sort_order) VALUES (?, ?, ?, 1, 0)");
            $stmt_img->bind_param("iss", $new_id, $main_image, $main_image_alt);
            $stmt_img->execute();
            $stmt_img->close();
        }
        
        if (isset($input['gallery_images']) && is_array($input['gallery_images'])) {
            $imgStmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, thumb_url, alt_text, is_main, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($input['gallery_images'] as $idx => $img) {
                $url = $img['url'] ?? '';
                $thumbUrl = $img['thumb_url'] ?? null;
                $alt = $img['alt'] ?? '';
                $isMainImg = ($url === $main_image) ? 1 : 0;
                if ($url) {
                    $imgStmt->bind_param("isssii", $new_id, $url, $thumbUrl, $alt, $isMainImg, $idx);
                    $imgStmt->execute();
                }
            }
            $imgStmt->close();
        }
        
        echo json_encode(['success' => true, 'id' => $new_id]);
    } else {
        sendError('DB error: ' . $stmt->error, 500);
    }
    $stmt->close();
    exit;
}

// ========== UPDATE ==========
if ($method === 'PUT') {
    if (!$id) sendError('Missing ID for update');
    
    $sql = "UPDATE products SET
        name=?, slug=?, price=?, old_price=?, abv=?, volume=?, description=?, sku=?, barcode=?, weight_gram=?, stock_quantity=?,
        display_status=?, featured=?, schedule_start=?, schedule_end=?, dynamic_attributes=?, variants=?,
        wine_type=?, grape_type=?, country=?, region=?, spirit_type=?, brand=?, age=?, producer_id=?,
        seo_title=?, seo_description=?, requires_special_shipping=?, auto_status_update=?
        WHERE id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error, 500);
    }
    
    $types = '';
    $params = [];
    bindParam($stmt, $types, $params, $name, 's');
    bindParam($stmt, $types, $params, $slug, 's');
    bindParam($stmt, $types, $params, $price, 'd');
    bindParam($stmt, $types, $params, $old_price, 'd');
    bindParam($stmt, $types, $params, $abv, 's');
    bindParam($stmt, $types, $params, $volume, 's');
    bindParam($stmt, $types, $params, $description, 's');
    bindParam($stmt, $types, $params, $sku, 's');
    bindParam($stmt, $types, $params, $barcode, 's');
    bindParam($stmt, $types, $params, $weight_gram, 'i');
    bindParam($stmt, $types, $params, $stock_quantity, 'i');
    bindParam($stmt, $types, $params, $display_status, 's');
    bindParam($stmt, $types, $params, $featured, 'i');
    bindParam($stmt, $types, $params, $schedule_start, 's');
    bindParam($stmt, $types, $params, $schedule_end, 's');
    bindParam($stmt, $types, $params, $dynamic_attributes, 's');
    bindParam($stmt, $types, $params, $variants, 's');
    bindParam($stmt, $types, $params, $wine_type, 's');
    bindParam($stmt, $types, $params, $grape_type, 's');
    bindParam($stmt, $types, $params, $country, 's');
    bindParam($stmt, $types, $params, $region, 's');
    bindParam($stmt, $types, $params, $spirit_type, 's');
    bindParam($stmt, $types, $params, $brand, 's');
    bindParam($stmt, $types, $params, $age, 's');
    bindParam($stmt, $types, $params, $producer_id, 'i');
    bindParam($stmt, $types, $params, $seo_title, 's');
    bindParam($stmt, $types, $params, $seo_description, 's');
    bindParam($stmt, $types, $params, $requires_special_shipping, 'i');
    bindParam($stmt, $types, $params, $auto_status_update, 'i');
    bindParam($stmt, $types, $params, $id, 'i');
    
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $conn->query("DELETE FROM product_categories WHERE product_id = $id");
        if (!empty($category_ids)) {
            $catStmt = $conn->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
            foreach ($category_ids as $catId) {
                $catStmt->bind_param("ii", $id, $catId);
                $catStmt->execute();
            }
            $catStmt->close();
        }
        
        if (!empty($main_image)) {
            $checkImg = $conn->prepare("SELECT id FROM product_images WHERE product_id = ? AND image_url = ?");
            $checkImg->bind_param("is", $id, $main_image);
            $checkImg->execute();
            $checkImg->store_result();
            if ($checkImg->num_rows > 0) {
                $updateImg = $conn->prepare("UPDATE product_images SET alt_text = ?, is_main = 1 WHERE product_id = ? AND image_url = ?");
                $updateImg->bind_param("sis", $main_image_alt, $id, $main_image);
                $updateImg->execute();
                $updateImg->close();
            } else {
                $insertImg = $conn->prepare("INSERT INTO product_images (product_id, image_url, alt_text, is_main, sort_order) VALUES (?, ?, ?, 1, 0)");
                $insertImg->bind_param("iss", $id, $main_image, $main_image_alt);
                $insertImg->execute();
                $insertImg->close();
            }
            $checkImg->close();
            $conn->query("UPDATE product_images SET is_main = 0 WHERE product_id = $id AND image_url != '$main_image'");
        }
        
        echo json_encode(['success' => true]);
    } else {
        sendError('DB error: ' . $stmt->error, 500);
    }
    $stmt->close();
    exit;
}

sendError('Method not allowed', 405);
?>