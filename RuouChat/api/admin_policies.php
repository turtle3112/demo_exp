<?php
/**
 * api/admin_policies.php
 * API quản lý chính sách (CRUD, clone, import/export, bulk actions)
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
    // Fallback: kiểm tra header
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
    header('Content-Disposition: attachment; filename=policies_export_'.date('Ymd_His').'.xls');
    
    $sql = "SELECT * FROM policies ORDER BY id DESC";
    $result = $conn->query($sql);
    $policies = $result->fetch_all(MYSQLI_ASSOC);
    
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Export Policies</title></head><body>';
    echo '<table border="1">';
    echo '<tr><th>ID</th><th>Slug</th><th>Tiêu đề</th><th>Trạng thái</th><th>Ngày tạo</th></tr>';
    foreach ($policies as $p) {
        echo '<tr>';
        echo '<td>'.$p['id'].'</td>';
        echo '<td>'.htmlspecialchars($p['slug']).'</td>';
        echo '<td>'.htmlspecialchars($p['title']).'</td>';
        echo '<td>'.($p['status'] == 1 ? 'Kích hoạt' : 'Tắt').'</td>';
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
        $status = ($data['status'] ?? '1') == '1' ? 1 : 0;
        $seo_title = $data['seo_title'] ?? '';
        $seo_description = $data['seo_description'] ?? '';
        $gallery = $data['gallery'] ?? '[]';
        
        // Kiểm tra slug unique
        $originalSlug = $slug;
        $counter = 1;
        $checkStmt = $conn->prepare("SELECT id FROM policies WHERE slug = ?");
        while (true) {
            $checkStmt->bind_param("s", $slug);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows == 0) break;
            $slug = $originalSlug . '-' . $counter++;
        }
        $checkStmt->close();
        
        $sql = "INSERT INTO policies (slug, title, content, status, seo_title, seo_description, gallery) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssisss", $slug, $title, $content, $status, $seo_title, $seo_description, $gallery);
        
        if ($stmt->execute()) {
            $imported++;
        } else {
            $errors[] = "Failed to insert policy: $title - " . $stmt->error;
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
        sendError('No policies selected', 400);
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    
    switch ($action) {
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM policies WHERE id IN ($placeholders)");
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
            $stmt = $conn->prepare("UPDATE policies SET status = ? WHERE id IN ($placeholders)");
            $stmt->bind_param('i' . $types, $newStatus, ...$ids);
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
    
    // Lấy chi tiết 1 chính sách theo ID hoặc SLUG
    if ($id || $slug) {
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM policies WHERE id = ?");
            $stmt->bind_param("i", $id);
        } else {
            $stmt = $conn->prepare("SELECT * FROM policies WHERE slug = ?");
            $stmt->bind_param("s", $slug);
        }
        $stmt->execute();
        $policy = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($policy) {
            // Xử lý gallery
            if (!empty($policy['gallery'])) {
                $gallery = json_decode($policy['gallery'], true);
                $policy['gallery'] = is_array($gallery) ? $gallery : [];
            } else {
                $policy['gallery'] = [];
            }
            
            // SEO data
            $policy['seo_title'] = $policy['seo_title'] ?? $policy['title'];
            $policy['seo_description'] = $policy['seo_description'] ?? '';
            
            // Demo URL
            $policy['demo_url'] = 'https://ruouchat.vn/chinh-sach.php?slug=' . $policy['slug'];
        }
        
        echo json_encode($policy);
        exit;
    }
    
    // ========== LẤY DANH SÁCH ==========
    $where = [];
    $params = [];
    $types = "";
    
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $where[] = "status = ?";
        $params[] = (int)$_GET['status'];
        $types .= "i";
    }
    
    if (isset($_GET['search']) && $_GET['search'] != '') {
        $where[] = "(title LIKE ? OR slug LIKE ?)";
        $params[] = "%".$_GET['search']."%";
        $params[] = "%".$_GET['search']."%";
        $types .= "ss";
    }
    
    $whereClause = empty($where) ? "1=1" : implode(" AND ", $where);
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 1000;
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
    
    $sql = "SELECT * FROM policies 
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
    $policies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    foreach ($policies as &$p) {
        if (!empty($p['gallery'])) {
            $gallery = json_decode($p['gallery'], true);
            $p['gallery'] = is_array($gallery) ? $gallery : [];
        } else {
            $p['gallery'] = [];
        }
        $p['demo_url'] = 'https://ruouchat.vn/chinh-sach.php?slug=' . $p['slug'];
        $p['seo_title'] = $p['seo_title'] ?? $p['title'];
        $p['seo_description'] = $p['seo_description'] ?? '';
    }
    unset($p);
    
    // Đếm tổng số
    $countSql = "SELECT COUNT(*) as total FROM policies WHERE $whereClause";
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
        'policies' => $policies,
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
    
    $conn->query("DELETE FROM policies WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

// ========== CLONE POLICY ==========
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'clone') {
    $originalId = intval($_GET['id'] ?? 0);
    if (!$originalId) sendError('Missing original policy ID');
    
    $stmt = $conn->prepare("SELECT * FROM policies WHERE id = ?");
    $stmt->bind_param("i", $originalId);
    $stmt->execute();
    $original = $stmt->get_result()->fetch_assoc();
    if (!$original) sendError('Original policy not found', 404);
    
    $newTitle = $original['title'] . ' (Copy)';
    $newSlug = createSlug($newTitle);
    $counter = 1;
    $tempSlug = $newSlug;
    while (true) {
        $check = $conn->prepare("SELECT id FROM policies WHERE slug = ?");
        $check->bind_param("s", $tempSlug);
        $check->execute();
        $check->store_result();
        if ($check->num_rows == 0) break;
        $tempSlug = $newSlug . '-' . $counter++;
        $check->close();
    }
    $newSlug = $tempSlug;
    
    $insertSql = "INSERT INTO policies (
        slug, title, content, status, seo_title, seo_description, gallery
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?
    )";
    
    $stmtIns = $conn->prepare($insertSql);
    if (!$stmtIns) {
        sendError('Clone prepare failed: ' . $conn->error, 500);
    }
    
    $seo_title = $original['seo_title'] ?? $original['title'];
    $seo_description = $original['seo_description'] ?? '';
    $gallery = $original['gallery'] ?? '[]';
    
    $stmtIns->bind_param(
        "sssisss",
        $newSlug, $newTitle,
        $original['content'],
        $original['status'],
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
$title = trim($input['title'] ?? '');
if (!$title) {
    sendError('Title is required');
}

// Xử lý slug
$slug = !empty($input['slug']) ? $input['slug'] : createSlug($title);
$originalSlug = $slug;
$counter = 1;
if ($method === 'PUT') {
    $checkStmt = $conn->prepare("SELECT id FROM policies WHERE slug = ? AND id != ?");
    while (true) {
        $checkStmt->bind_param("si", $slug, $id);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows == 0) break;
        $slug = $originalSlug . '-' . $counter++;
    }
    $checkStmt->close();
} else {
    $checkStmt = $conn->prepare("SELECT id FROM policies WHERE slug = ?");
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
$status = (int)($input['status'] ?? 1);
$seo_title = $input['seo_title'] ?? '';
$seo_description = $input['seo_description'] ?? '';
$gallery = isset($input['gallery']) ? $input['gallery'] : '[]';

// ========== CREATE ==========
if ($method === 'POST') {
    $sql = "INSERT INTO policies (
        slug, title, content, status, seo_title, seo_description, gallery
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error, 500);
    }
    
    $stmt->bind_param("sssisss", 
        $slug, $title, $content, $status, 
        $seo_title, $seo_description, $gallery
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
    
    $sql = "UPDATE policies SET
        slug=?, title=?, content=?, status=?, 
        seo_title=?, seo_description=?, gallery=?
        WHERE id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error, 500);
    }
    
    $stmt->bind_param("sssisssi", 
        $slug, $title, $content, $status,
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