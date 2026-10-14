<?php
/**
 * api/admin_gift_products.php
 * API quản lý quà tặng (CRUD, clone, import/export, bulk actions)
 * Hỗ trợ giá không bắt buộc (null = Liên hệ)
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

function sendError($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function sendSuccess($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ========== EXPORT EXCEL ==========
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'export') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=gift_products_export_'.date('Ymd_His').'.xls');
    
    $sql = "SELECT * FROM gift_products ORDER BY id DESC";
    $result = $conn->query($sql);
    $products = $result->fetch_all(MYSQLI_ASSOC);
    
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Export Gift Products</title></head><body>';
    echo '<table border="1">';
    echo '<tr><th>ID</th><th>Tên</th><th>Slug</th><th>Giá</th><th>Giá cũ</th><th>Danh mục</th><th>ABV</th><th>Dung tích</th><th>Trạng thái</th><th>Nổi bật</th></tr>';
    foreach ($products as $p) {
        echo '<tr>';
        echo '<td>'.$p['id'].'</td>';
        echo '<td>'.htmlspecialchars($p['name']).'</td>';
        echo '<td>'.htmlspecialchars($p['slug']).'</td>';
        // Hiển thị "Liên hệ" nếu giá là null hoặc 0
        $priceDisplay = ($p['price'] === null || $p['price'] == 0) ? 'Liên hệ' : number_format($p['price'], 0, ',', '.');
        echo '<td>'.$priceDisplay.'</td>';
        echo '<td>'.($p['old_price'] ? number_format($p['old_price'],0,',','.') : '').'</td>';
        echo '<td>'.($p['category'] == 'phat-tai' ? 'Phát Tài' : 'Phát Lộc').'</td>';
        echo '<td>'.htmlspecialchars($p['abv'] ?? '').'</td>';
        echo '<td>'.htmlspecialchars($p['volume'] ?? '').'</td>';
        echo '<td>'.($p['status'] == 1 ? 'Hiển thị' : 'Ẩn').'</td>';
        echo '<td>'.($p['featured'] ? 'Có' : 'Không').'</td>';
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
            $errors[] = "Missing name for row: " . json_encode($data);
            continue;
        }
        
        // Giá có thể để trống -> null
        $price = isset($data['price']) && $data['price'] !== '' ? floatval($data['price']) : null;
        
        $slug = !empty($data['slug']) ? $data['slug'] : createSlug($name);
        $old_price = isset($data['old_price']) && $data['old_price'] !== '' ? floatval($data['old_price']) : null;
        $category = $data['category'] ?? 'phat-tai';
        $abv = $data['abv'] ?? '1% ABV*';
        $volume = $data['volume'] ?? '750ml';
        $status = ($data['status'] ?? '1') == '1' ? 1 : 0;
        $featured = ($data['featured'] ?? '0') == '1' ? 1 : 0;
        $main_image = $data['main_image'] ?? 'assets/images/5.jpg';
        $description = $data['description'] ?? '';
        $seo_title = $data['seo_title'] ?? '';
        $seo_description = $data['seo_description'] ?? '';
        $gallery = $data['gallery'] ?? '[]';
        
        // Kiểm tra slug unique
        $originalSlug = $slug;
        $counter = 1;
        $checkStmt = $conn->prepare("SELECT id FROM gift_products WHERE slug = ?");
        while (true) {
            $checkStmt->bind_param("s", $slug);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows == 0) break;
            $slug = $originalSlug . '-' . $counter++;
        }
        $checkStmt->close();
        
        $sql = "INSERT INTO gift_products (name, slug, price, old_price, category, abv, volume, description, main_image, status, featured, seo_title, seo_description, gallery) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssddsssssiisss", $name, $slug, $price, $old_price, $category, $abv, $volume, $description, $main_image, $status, $featured, $seo_title, $seo_description, $gallery);
        
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
            $stmt = $conn->prepare("DELETE FROM gift_products WHERE id IN ($placeholders)");
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
            $newStatus = (int)$input['new_status'];
            $stmt = $conn->prepare("UPDATE gift_products SET status = ? WHERE id IN ($placeholders)");
            $stmt->bind_param('i' . $types, $newStatus, ...$ids);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'updated' => $stmt->affected_rows]);
            } else {
                sendError('Update failed: ' . $stmt->error, 500);
            }
            $stmt->close();
            break;
            
        case 'change_price':
            if (!isset($input['new_price'])) sendError('Missing new_price', 400);
            $newPrice = $input['new_price'] !== '' ? floatval($input['new_price']) : null;
            $stmt = $conn->prepare("UPDATE gift_products SET price = ? WHERE id IN ($placeholders)");
            $stmt->bind_param('d' . $types, $newPrice, ...$ids);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'updated' => $stmt->affected_rows]);
            } else {
                sendError('Update failed: ' . $stmt->error, 500);
            }
            $stmt->close();
            break;
            
        case 'change_category':
            if (!isset($input['new_category'])) sendError('Missing new_category', 400);
            $newCategory = $input['new_category'];
            $stmt = $conn->prepare("UPDATE gift_products SET category = ? WHERE id IN ($placeholders)");
            $stmt->bind_param('s' . $types, $newCategory, ...$ids);
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
    $slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
    
    // Lấy chi tiết 1 sản phẩm theo ID hoặc SLUG
    if ($id || $slug) {
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM gift_products WHERE id = ?");
            $stmt->bind_param("i", $id);
        } else {
            $stmt = $conn->prepare("SELECT * FROM gift_products WHERE slug = ?");
            $stmt->bind_param("s", $slug);
        }
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($product) {
            // Xử lý gallery
            if (!empty($product['gallery'])) {
                $gallery = json_decode($product['gallery'], true);
                $product['gallery'] = is_array($gallery) ? $gallery : [];
            } else {
                $product['gallery'] = [];
            }
            
            // Giá có thể là null (Liên hệ)
            $product['price'] = $product['price'] !== null ? floatval($product['price']) : null;
            $product['old_price'] = $product['old_price'] !== null ? floatval($product['old_price']) : null;
            
            // Tạo URL demo
            $product['demo_url'] = 'https://ruouchat.vn/sp1.php?slug=' . $product['slug'] . '&type=gift';
            
            // SEO data
            $product['seo_title'] = $product['seo_title'] ?? $product['name'];
            $product['seo_description'] = $product['seo_description'] ?? '';
            $product['seo_url'] = $product['demo_url'];
        }
        
        echo json_encode($product);
        exit;
    }
    
    // ========== LẤY DANH SÁCH ==========
    $where = [];
    $params = [];
    $types = "";
    
    if (isset($_GET['category']) && $_GET['category'] != '') {
        $where[] = "category = ?";
        $params[] = $_GET['category'];
        $types .= "s";
    }
    
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $where[] = "status = ?";
        $params[] = (int)$_GET['status'];
        $types .= "i";
    }
    
    if (isset($_GET['featured']) && $_GET['featured'] !== '') {
        $where[] = "featured = ?";
        $params[] = (int)$_GET['featured'];
        $types .= "i";
    }
    
    if (isset($_GET['search']) && $_GET['search'] != '') {
        $where[] = "name LIKE ?";
        $params[] = "%".$_GET['search']."%";
        $types .= "s";
    }
    
    if (isset($_GET['min_price']) && $_GET['min_price'] != '') {
        $where[] = "(price >= ? OR price IS NULL)";
        $params[] = floatval($_GET['min_price']);
        $types .= "d";
    }
    if (isset($_GET['max_price']) && $_GET['max_price'] != '') {
        $where[] = "(price <= ? OR price IS NULL)";
        $params[] = floatval($_GET['max_price']);
        $types .= "d";
    }
    
    $whereClause = empty($where) ? "1=1" : implode(" AND ", $where);
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 1000;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    $orderBy = "id DESC";
    if (isset($_GET['sort'])) {
        switch ($_GET['sort']) {
            case 'price_asc':  $orderBy = "price ASC"; break;
            case 'price_desc': $orderBy = "price DESC"; break;
            case 'name_asc':   $orderBy = "name ASC"; break;
            case 'name_desc':  $orderBy = "name DESC"; break;
            case 'id_asc':     $orderBy = "id ASC"; break;
            default:           $orderBy = "id DESC";
        }
    }
    
    $sql = "SELECT * FROM gift_products 
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
    $stmt->close();
    
    foreach ($products as &$p) {
        if (!empty($p['gallery'])) {
            $gallery = json_decode($p['gallery'], true);
            $p['gallery'] = is_array($gallery) ? $gallery : [];
        } else {
            $p['gallery'] = [];
        }
        $p['price'] = $p['price'] !== null ? floatval($p['price']) : null;
        $p['old_price'] = $p['old_price'] !== null ? floatval($p['old_price']) : null;
        $p['main_image'] = $p['main_image'] ?? '';
        
        // Tạo demo_url
        $p['demo_url'] = 'https://ruouchat.vn/sp1.php?slug=' . $p['slug'] . '&type=gift';
        
        // SEO data
        $p['seo_title'] = $p['seo_title'] ?? $p['name'];
        $p['seo_description'] = $p['seo_description'] ?? '';
        $p['seo_url'] = $p['demo_url'];
    }
    unset($p);
    
    // Đếm tổng số
    $countSql = "SELECT COUNT(*) as total FROM gift_products WHERE $whereClause";
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
    
    $conn->query("DELETE FROM gift_products WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

// ========== CLONE PRODUCT ==========
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'clone') {
    $originalId = intval($_GET['id'] ?? 0);
    if (!$originalId) sendError('Missing original product ID');
    
    $stmt = $conn->prepare("SELECT * FROM gift_products WHERE id = ?");
    $stmt->bind_param("i", $originalId);
    $stmt->execute();
    $original = $stmt->get_result()->fetch_assoc();
    if (!$original) sendError('Original product not found', 404);
    
    $newName = $original['name'] . ' (Copy)';
    $newSlug = createSlug($newName);
    $counter = 1;
    $tempSlug = $newSlug;
    while (true) {
        $check = $conn->prepare("SELECT id FROM gift_products WHERE slug = ?");
        $check->bind_param("s", $tempSlug);
        $check->execute();
        $check->store_result();
        if ($check->num_rows == 0) break;
        $tempSlug = $newSlug . '-' . $counter++;
        $check->close();
    }
    $newSlug = $tempSlug;
    
    $insertSql = "INSERT INTO gift_products (
        name, slug, price, old_price, category, abv, volume, description, main_image, 
        status, featured, seo_title, seo_description, gallery
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )";
    
    $stmtIns = $conn->prepare($insertSql);
    if (!$stmtIns) {
        sendError('Clone prepare failed: ' . $conn->error, 500);
    }
    
    $seo_title = $original['seo_title'] ?? $original['name'];
    $seo_description = $original['seo_description'] ?? '';
    $gallery = $original['gallery'] ?? '[]';
    
    $stmtIns->bind_param(
        "ssddsssssiisss",
        $newName, $newSlug,
        $original['price'], $original['old_price'],
        $original['category'], $original['abv'], $original['volume'],
        $original['description'], $original['main_image'],
        $original['status'], $original['featured'],
        $seo_title, $seo_description, $gallery
    );
    
    if (!$stmtIns->execute()) {
        sendError('Clone insert failed: ' . $stmtIns->error, 500);
    }
    $newId = $conn->insert_id;
    $stmtIns->close();
    
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
// Giá có thể là null (Liên hệ)
$price = isset($input['price']) && $input['price'] !== null && $input['price'] !== '' ? floatval($input['price']) : null;

if (!$name) {
    sendError('Name is required');
}

// Xử lý slug
$slug = !empty($input['slug']) ? $input['slug'] : createSlug($name);
$originalSlug = $slug;
$counter = 1;
if ($method === 'PUT') {
    $checkStmt = $conn->prepare("SELECT id FROM gift_products WHERE slug = ? AND id != ?");
    while (true) {
        $checkStmt->bind_param("si", $slug, $id);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows == 0) break;
        $slug = $originalSlug . '-' . $counter++;
    }
    $checkStmt->close();
} else {
    $checkStmt = $conn->prepare("SELECT id FROM gift_products WHERE slug = ?");
    while (true) {
        $checkStmt->bind_param("s", $slug);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows == 0) break;
        $slug = $originalSlug . '-' . $counter++;
    }
    $checkStmt->close();
}

$old_price = isset($input['old_price']) && $input['old_price'] !== '' && $input['old_price'] !== null ? floatval($input['old_price']) : null;
$category = $input['category'] ?? 'phat-tai';
$abv = $input['abv'] ?? '1% ABV*';
$volume = $input['volume'] ?? '750ml';
$description = $input['description'] ?? '';
$main_image = $input['main_image'] ?? 'assets/images/5.jpg';
$status = (int)($input['status'] ?? 1);
$featured = (int)($input['featured'] ?? 0);
$seo_title = $input['seo_title'] ?? '';
$seo_description = $input['seo_description'] ?? '';
$gallery = isset($input['gallery']) ? $input['gallery'] : '[]';

// ========== CREATE ==========
if ($method === 'POST') {
    $sql = "INSERT INTO gift_products (
        name, slug, price, old_price, category, abv, volume, description, main_image, 
        status, featured, seo_title, seo_description, gallery
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error, 500);
    }
    
    $stmt->bind_param("ssddsssssiisss", 
        $name, $slug, $price, $old_price, $category, 
        $abv, $volume, $description, $main_image, 
        $status, $featured, $seo_title, $seo_description, $gallery
    );
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
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
    
    $sql = "UPDATE gift_products SET
        name=?, slug=?, price=?, old_price=?, category=?, 
        abv=?, volume=?, description=?, main_image=?, 
        status=?, featured=?, seo_title=?, seo_description=?, gallery=?
        WHERE id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error, 500);
    }
    
    $stmt->bind_param("ssddsssssiisssi", 
        $name, $slug, $price, $old_price, $category,
        $abv, $volume, $description, $main_image,
        $status, $featured, $seo_title, $seo_description, $gallery, $id
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        sendError('DB error: ' . $stmt->error, 500);
    }
    $stmt->close();
    exit;
}

sendError('Method not allowed', 405);
?>