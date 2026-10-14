<?php
/**
 * api/admin_pages.php
 * API quản lý trang tĩnh (CRUD, clone, import/export, bulk actions)
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

// Kiểm tra quyền admin
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $headers = getallheaders();
    if (!isset($headers['X-Admin-Secret']) || $headers['X-Admin-Secret'] !== 'RuouchatAdmin2024!') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

// ========== EXPORT EXCEL ==========
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'export') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=pages_export_'.date('Ymd_His').'.xls');
    
    $sql = "SELECT * FROM pages ORDER BY id DESC";
    $result = $conn->query($sql);
    $pages = $result->fetch_all(MYSQLI_ASSOC);
    
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Export Pages</title></head><body>';
    echo '<table border="1">';
    echo '<tr><th>ID</th><th>Tiêu đề</th><th>Slug</th><th>Loại trang</th><th>Trạng thái</th><th>Ngày tạo</th></tr>';
    foreach ($pages as $p) {
        echo '<tr>';
        echo '<td>'.$p['id'].'</td>';
        echo '<td>'.htmlspecialchars($p['title']).'</td>';
        echo '<td>'.htmlspecialchars($p['slug']).'</td>';
        echo '<td>'.($p['page_type'] == 'product_list' ? 'Danh sách sản phẩm' : 'Nội dung').'</td>';
        echo '<td>'.($p['status'] == 'published' ? 'Xuất bản' : 'Nháp').'</td>';
        echo '<td>'.$p['created_at'].'</td>';
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
        
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            $errors[] = "Invalid data for row: " . json_encode($data);
            continue;
        }
        
        $slug = !empty($data['slug']) ? $data['slug'] : createSlug($title);
        $content = $data['content'] ?? '';
        $page_type = $data['page_type'] ?? 'content';
        $status = $data['status'] ?? 'published';
        $seo_title = $data['seo_title'] ?? '';
        $seo_description = $data['seo_description'] ?? '';
        $gallery = $data['gallery'] ?? '[]';
        
        // Kiểm tra slug unique
        $originalSlug = $slug;
        $counter = 1;
        $checkStmt = $conn->prepare("SELECT id FROM pages WHERE slug = ?");
        while (true) {
            $checkStmt->bind_param("s", $slug);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows == 0) break;
            $slug = $originalSlug . '-' . $counter++;
        }
        $checkStmt->close();
        
        $sql = "INSERT INTO pages (slug, title, content, status, page_type, seo_title, seo_description, gallery) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $slug, $title, $content, $status, $page_type, $seo_title, $seo_description, $gallery);
        
        if ($stmt->execute()) {
            $imported++;
        } else {
            $errors[] = "Failed to insert page: $title - " . $stmt->error;
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
        sendError('No pages selected', 400);
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    
    switch ($action) {
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM pages WHERE id IN ($placeholders)");
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
            $stmt = $conn->prepare("UPDATE pages SET status = ? WHERE id IN ($placeholders)");
            $stmt->bind_param('s' . $types, $newStatus, ...$ids);
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
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Lấy chi tiết 1 trang theo ID hoặc SLUG
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM pages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $page = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($page) {
            // Xử lý gallery
            if (!empty($page['gallery'])) {
                $gallery = json_decode($page['gallery'], true);
                $page['gallery'] = is_array($gallery) ? $gallery : [];
            } else {
                $page['gallery'] = [];
            }
            
            // SEO data
            $page['seo_title'] = $page['seo_title'] ?? $page['title'];
            $page['seo_description'] = $page['seo_description'] ?? '';
            
            // Demo URL
            $page['demo_url'] = 'https://ruouchat.vn/page.php?slug=' . $page['slug'];
        }
        
        echo json_encode($page);
        exit;
    }
    
    if ($slug) {
        $stmt = $conn->prepare("SELECT * FROM pages WHERE slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $page = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($page) {
            if (!empty($page['gallery'])) {
                $gallery = json_decode($page['gallery'], true);
                $page['gallery'] = is_array($gallery) ? $gallery : [];
            } else {
                $page['gallery'] = [];
            }
            $page['seo_title'] = $page['seo_title'] ?? $page['title'];
            $page['seo_description'] = $page['seo_description'] ?? '';
            $page['demo_url'] = 'https://ruouchat.vn/page.php?slug=' . $page['slug'];
        }
        
        echo json_encode($page);
        exit;
    }
    
    // ========== LẤY DANH SÁCH ==========
    if ($action === 'list') {
        $where = [];
        $params = [];
        $types = "";
        
        if (isset($_GET['status']) && $_GET['status'] != '') {
            $where[] = "status = ?";
            $params[] = $_GET['status'];
            $types .= "s";
        }
        
        if (isset($_GET['search']) && $_GET['search'] != '') {
            $where[] = "(title LIKE ? OR slug LIKE ?)";
            $params[] = "%".$_GET['search']."%";
            $params[] = "%".$_GET['search']."%";
            $types .= "ss";
        }
        
        if (isset($_GET['page_type']) && $_GET['page_type'] != '') {
            $where[] = "page_type = ?";
            $params[] = $_GET['page_type'];
            $types .= "s";
        }
        
        $whereClause = empty($where) ? "1=1" : implode(" AND ", $where);
        
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        $orderBy = "id DESC";
        if (isset($_GET['sort'])) {
            switch ($_GET['sort']) {
                case 'title_asc':  $orderBy = "title ASC"; break;
                case 'title_desc': $orderBy = "title DESC"; break;
                case 'id_asc':     $orderBy = "id ASC"; break;
                default:           $orderBy = "id DESC";
            }
        }
        
        $sql = "SELECT id, title, slug, page_type, status, created_at, updated_at, seo_title, seo_description 
                FROM pages 
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
        $pages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($pages as &$p) {
            $p['demo_url'] = 'https://ruouchat.vn/page.php?slug=' . $p['slug'];
        }
        unset($p);
        
        // Đếm tổng số
        $countSql = "SELECT COUNT(*) as total FROM pages WHERE $whereClause";
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
            'data' => $pages,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
        exit;
    }
    
    sendError('Invalid action', 400);
}

// ========== DELETE ==========
if ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) sendError('Missing ID');
    
    $conn->query("DELETE FROM pages WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

// ========== CLONE PAGE ==========
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'clone') {
    $originalId = intval($_GET['id'] ?? 0);
    if (!$originalId) sendError('Missing original page ID');
    
    $stmt = $conn->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->bind_param("i", $originalId);
    $stmt->execute();
    $original = $stmt->get_result()->fetch_assoc();
    if (!$original) sendError('Original page not found', 404);
    
    $newTitle = $original['title'] . ' (Copy)';
    $newSlug = createSlug($newTitle);
    $counter = 1;
    $tempSlug = $newSlug;
    while (true) {
        $check = $conn->prepare("SELECT id FROM pages WHERE slug = ?");
        $check->bind_param("s", $tempSlug);
        $check->execute();
        $check->store_result();
        if ($check->num_rows == 0) break;
        $tempSlug = $newSlug . '-' . $counter++;
        $check->close();
    }
    $newSlug = $tempSlug;
    
    $insertSql = "INSERT INTO pages (
        slug, title, content, status, page_type, seo_title, seo_description, gallery
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?
    )";
    
    $stmtIns = $conn->prepare($insertSql);
    if (!$stmtIns) {
        sendError('Clone prepare failed: ' . $conn->error, 500);
    }
    
    $seo_title = $original['seo_title'] ?? $original['title'];
    $seo_description = $original['seo_description'] ?? '';
    $gallery = $original['gallery'] ?? '[]';
    
    $stmtIns->bind_param(
        "ssssssss",
        $newSlug, $newTitle,
        $original['content'],
        $original['status'],
        $original['page_type'],
        $seo_title, $seo_description, $gallery
    );
    
    if (!$stmtIns->execute()) {
        sendError('Clone insert failed: ' . $stmtIns->error, 500);
    }
    $newId = $conn->insert_id;
    $stmtIns->close();
    
    // Clone page products nếu có
    if ($original['page_type'] === 'product_list') {
        $prodStmt = $conn->prepare("SELECT product_id, sort_order FROM page_products WHERE page_id = ?");
        $prodStmt->bind_param("i", $originalId);
        $prodStmt->execute();
        $products = $prodStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $prodStmt->close();
        
        $insertProd = $conn->prepare("INSERT INTO page_products (page_id, product_id, sort_order) VALUES (?, ?, ?)");
        foreach ($products as $prod) {
            $insertProd->bind_param("iii", $newId, $prod['product_id'], $prod['sort_order']);
            $insertProd->execute();
        }
        $insertProd->close();
    }
    
    echo json_encode(['success' => true, 'new_id' => $newId]);
    exit;
}

// ========== POST (CREATE) & PUT (UPDATE) ==========
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendError('Invalid JSON input: ' . json_last_error_msg(), 400);
}

$id = intval($input['id'] ?? 0);
$title = trim($input['title'] ?? '');
if (!$title) {
    sendError('Title is required');
}

// Xử lý slug
$slug = !empty($input['slug']) ? $input['slug'] : createSlug($title);
$originalSlug = $slug;
$counter = 1;
if ($method === 'PUT') {
    $checkStmt = $conn->prepare("SELECT id FROM pages WHERE slug = ? AND id != ?");
    while (true) {
        $checkStmt->bind_param("si", $slug, $id);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows == 0) break;
        $slug = $originalSlug . '-' . $counter++;
    }
    $checkStmt->close();
} else {
    $checkStmt = $conn->prepare("SELECT id FROM pages WHERE slug = ?");
    while (true) {
        $checkStmt->bind_param("s", $slug);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows == 0) break;
        $slug = $originalSlug . '-' . $counter++;
    }
    $checkStmt->close();
}

$content = $input['content'] ?? '';
$status = isset($input['status']) && in_array($input['status'], ['published', 'draft']) ? $input['status'] : 'draft';
$page_type = isset($input['page_type']) && in_array($input['page_type'], ['content', 'product_list']) ? $input['page_type'] : 'content';
$seo_title = $input['seo_title'] ?? '';
$seo_description = $input['seo_description'] ?? '';
$gallery = isset($input['gallery']) ? $input['gallery'] : '[]';

// ========== CREATE ==========
if ($method === 'POST') {
    $sql = "INSERT INTO pages (
        slug, title, content, status, page_type, seo_title, seo_description, gallery
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error, 500);
    }
    
    $stmt->bind_param("ssssssss", 
        $slug, $title, $content, $status, 
        $page_type, $seo_title, $seo_description, $gallery
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
    
    $sql = "UPDATE pages SET
        slug=?, title=?, content=?, status=?, page_type=?, 
        seo_title=?, seo_description=?, gallery=?
        WHERE id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error, 500);
    }
    
    $stmt->bind_param("ssssssssi", 
        $slug, $title, $content, $status, $page_type,
        $seo_title, $seo_description, $gallery, $id
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