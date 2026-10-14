<?php
// index.php - Quản lý menu với drag-and-drop và collapse/expand
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// Kiểm tra file config tồn tại
$configPath = __DIR__ . '/../api/config.php';
if (!file_exists($configPath)) {
    die('Lỗi: Không tìm thấy file config tại: ' . $configPath);
}

require_once $configPath;

// Kiểm tra kết nối database
if (!isset($conn) || $conn->connect_error) {
    die('Lỗi kết nối database: ' . ($conn->connect_error ?? 'Không thể kết nối'));
}

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.html');
    exit;
}

$message = '';
$messageType = '';

// Kiểm tra bảng menu_items và tạo nếu chưa có
$tableCheck = $conn->query("SHOW TABLES LIKE 'menu_items'");
if (!$tableCheck) {
    die('Lỗi truy vấn kiểm tra bảng: ' . $conn->error);
}

if ($tableCheck->num_rows == 0) {
    $createTable = "
    CREATE TABLE IF NOT EXISTS `menu_items` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `label` varchar(255) NOT NULL,
      `link` varchar(500) DEFAULT NULL,
      `link_type` enum('custom','category','country','region','grape','wine_type') DEFAULT 'custom',
      `link_value` varchar(255) DEFAULT NULL,
      `parent_id` int(11) DEFAULT 0,
      `sort_order` int(11) DEFAULT 0,
      `column_group` int(11) DEFAULT 1,
      `is_heading` tinyint(1) DEFAULT 0,
      `is_highlight` tinyint(1) DEFAULT 0,
      `slug` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `parent_id` (`parent_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    if (!$conn->query($createTable)) {
        die('Lỗi tạo bảng: ' . $conn->error);
    }
} else {
    $columnCheck = $conn->query("SHOW COLUMNS FROM menu_items LIKE 'slug'");
    if (!$columnCheck) {
        die('Lỗi kiểm tra cột: ' . $conn->error);
    }
    if ($columnCheck->num_rows == 0) {
        $alterTable = "ALTER TABLE `menu_items` ADD COLUMN `slug` varchar(255) DEFAULT NULL AFTER `is_highlight`";
        if (!$conn->query($alterTable)) {
            die('Lỗi thêm cột slug: ' . $conn->error);
        }
    }
}

// ================= LẤY DANH MỤC CÓ PHÂN CẤP =================
function getCategoryTree($conn, $parentId = 0, $level = 0, &$result = []) {
    $stmt = $conn->prepare("SELECT id, name, slug, parent_id FROM categories WHERE parent_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $parentId);
    $stmt->execute();
    $categories = $stmt->get_result();
    
    while ($cat = $categories->fetch_assoc()) {
        $indent = str_repeat('— ', $level);
        if ($level > 0) {
            $indent = '&nbsp;&nbsp;' . str_repeat('&nbsp;&nbsp;', $level - 1) . '└─ ';
        }
        $result[] = [
            'id' => $cat['id'],
            'name' => $indent . htmlspecialchars($cat['name']),
            'slug' => $cat['slug'],
            'level' => $level,
            'parent_id' => $cat['parent_id']
        ];
        getCategoryTree($conn, $cat['id'], $level + 1, $result);
    }
    $stmt->close();
    return $result;
}

// Lấy danh sách danh mục có phân cấp
$categoryTree = [];
getCategoryTree($conn, 0, 0, $categoryTree);

// ================= CRUD =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // API SORT DRAG-DROP
    $input = file_get_contents('php://input');
    if (!empty($input) && strpos($input, 'action') !== false) {
        $data = json_decode($input, true);
        if ($data && isset($data['action']) && $data['action'] === 'update_order') {
            header('Content-Type: application/json');
            
            if (!isset($data['items'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid data: missing items']);
                exit;
            }
            
            $conn->begin_transaction();
            try {
                foreach ($data['items'] as $item) {
                    $id = intval($item['id']);
                    $parent_id = intval($item['parent_id']);
                    $sort_order = intval($item['sort_order']);
                    
                    $stmt = $conn->prepare("UPDATE menu_items SET parent_id = ?, sort_order = ? WHERE id = ?");
                    if (!$stmt) {
                        throw new Exception('Prepare failed: ' . $conn->error);
                    }
                    $stmt->bind_param("iii", $parent_id, $sort_order, $id);
                    if (!$stmt->execute()) {
                        throw new Exception('Execute failed: ' . $stmt->error);
                    }
                    $stmt->close();
                }
                
                $affectedParents = array_unique(array_column($data['items'], 'parent_id'));
                foreach ($affectedParents as $parentId) {
                    reorderMenuItems($conn, $parentId);
                }
                
                $conn->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $conn->rollback();
                error_log('Update order error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }
    
    // Xử lý các action khác
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $redirectUrl = 'index.php?msg=';
        
        // Lấy scroll position từ POST
        $scrollPos = isset($_POST['scroll_pos']) ? intval($_POST['scroll_pos']) : 0;
        if ($scrollPos > 0) {
            $redirectUrl .= '&scroll=' . $scrollPos;
        }
        
        if ($action === 'add') {
            $link_type = $_POST['link_type'] ?? 'custom';
            $link = '';
            $link_value = '';
            $slug = '';
            
            if ($link_type === 'category') {
                $cat_id = intval($_POST['category_id'] ?? 0);
                $link_value = $cat_id;
                
                if ($cat_id > 0) {
                    $catStmt = $conn->prepare("SELECT slug, name FROM categories WHERE id = ?");
                    if ($catStmt) {
                        $catStmt->bind_param("i", $cat_id);
                        $catStmt->execute();
                        $catResult = $catStmt->get_result();
                        if ($catRow = $catResult->fetch_assoc()) {
                            $slug = $catRow['slug'];
                            $link = '/' . $slug;
                        } else {
                            $link = '#';
                        }
                        $catStmt->close();
                    } else {
                        $link = '#';
                    }
                } else {
                    $link = '#';
                }
            } else {
                $link = trim($_POST['link'] ?? '#');
                $link_value = '';
                $slug = trim($_POST['slug'] ?? '');
                if (!empty($slug)) {
                    $link = '/' . ltrim($slug, '/');
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO menu_items (label, link, link_type, link_value, parent_id, sort_order, column_group, is_heading, is_highlight, slug) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                die('Lỗi prepare insert: ' . $conn->error);
            }
            
            $parent_id = intval($_POST['parent_id']);
            $sort_order = intval($_POST['sort_order']);
            $column_group = intval($_POST['column_group']);
            $is_heading = isset($_POST['is_heading']) ? 1 : 0;
            $is_highlight = isset($_POST['is_highlight']) ? 1 : 0;
            
            $stmt->bind_param("ssssiiiiis", 
                $_POST['label'], 
                $link, 
                $link_type,
                $link_value,
                $parent_id, 
                $sort_order,
                $column_group, 
                $is_heading, 
                $is_highlight,
                $slug
            );
            
            if ($stmt->execute()) {
                reorderMenuItems($conn, $parent_id);
                header('Location: ' . $redirectUrl . 'added');
            } else {
                header('Location: ' . $redirectUrl . 'error&error=' . urlencode($stmt->error));
            }
            $stmt->close();
            exit;
        }

        if ($action === 'edit') {
            $id = intval($_POST['id']);
            $link_type = $_POST['link_type'] ?? 'custom';
            $link = '';
            $link_value = '';
            $slug = '';
            
            if ($link_type === 'category') {
                $cat_id = intval($_POST['category_id'] ?? 0);
                $link_value = $cat_id;
                
                if ($cat_id > 0) {
                    $catStmt = $conn->prepare("SELECT slug, name FROM categories WHERE id = ?");
                    if ($catStmt) {
                        $catStmt->bind_param("i", $cat_id);
                        $catStmt->execute();
                        $catResult = $catStmt->get_result();
                        if ($catRow = $catResult->fetch_assoc()) {
                            $slug = $catRow['slug'];
                            $link = '/' . $slug;
                        } else {
                            $link = '#';
                        }
                        $catStmt->close();
                    } else {
                        $link = '#';
                    }
                } else {
                    $link = '#';
                }
            } else {
                $link = trim($_POST['link'] ?? '#');
                $link_value = '';
                $slug = trim($_POST['slug'] ?? '');
                if (!empty($slug)) {
                    $link = '/' . ltrim($slug, '/');
                }
            }
            
            $oldParentStmt = $conn->prepare("SELECT parent_id FROM menu_items WHERE id = ?");
            $oldParentStmt->bind_param("i", $id);
            $oldParentStmt->execute();
            $oldParentResult = $oldParentStmt->get_result();
            $oldParent = $oldParentResult->fetch_assoc();
            $oldParentId = $oldParent ? $oldParent['parent_id'] : 0;
            $oldParentStmt->close();
            
            $stmt = $conn->prepare("UPDATE menu_items SET label=?, link=?, link_type=?, link_value=?, parent_id=?, sort_order=?, column_group=?, is_heading=?, is_highlight=?, slug=? WHERE id=?");
            if (!$stmt) {
                die('Lỗi prepare update: ' . $conn->error);
            }
            
            $parent_id = intval($_POST['parent_id']);
            $sort_order = intval($_POST['sort_order']);
            $column_group = intval($_POST['column_group']);
            $is_heading = isset($_POST['is_heading']) ? 1 : 0;
            $is_highlight = isset($_POST['is_highlight']) ? 1 : 0;
            
            $stmt->bind_param("ssssiiiiisi", 
                $_POST['label'],
                $link,
                $link_type,
                $link_value,
                $parent_id,
                $sort_order,
                $column_group,
                $is_heading,
                $is_highlight,
                $slug,
                $id
            );
            
            if ($stmt->execute()) {
                if ($oldParentId > 0) {
                    reorderMenuItems($conn, $oldParentId);
                }
                if ($parent_id > 0 && $parent_id != $oldParentId) {
                    reorderMenuItems($conn, $parent_id);
                }
                if ($oldParentId == 0) {
                    reorderMenuItems($conn, 0);
                }
                if ($parent_id == 0 && $oldParentId != 0) {
                    reorderMenuItems($conn, 0);
                }
                header('Location: ' . $redirectUrl . 'updated');
            } else {
                header('Location: ' . $redirectUrl . 'error&error=' . urlencode($stmt->error));
            }
            $stmt->close();
            exit;
        }

        if ($action === 'delete') {
            $id = intval($_POST['id']);
            
            $parentStmt = $conn->prepare("SELECT parent_id FROM menu_items WHERE id = ?");
            $parentStmt->bind_param("i", $id);
            $parentStmt->execute();
            $parentResult = $parentStmt->get_result();
            $parentRow = $parentResult->fetch_assoc();
            $parentId = $parentRow ? $parentRow['parent_id'] : 0;
            $parentStmt->close();
            
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM menu_items WHERE parent_id = ?");
            if (!$checkStmt) {
                die('Lỗi prepare check: ' . $conn->error);
            }
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $childCount = $checkResult->fetch_assoc()['count'];
            $checkStmt->close();
            
            if ($childCount > 0) {
                header('Location: ' . $redirectUrl . 'has_children');
                exit;
            }
            
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
            if (!$stmt) {
                die('Lỗi prepare delete: ' . $conn->error);
            }
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                reorderMenuItems($conn, $parentId);
                header('Location: ' . $redirectUrl . 'deleted');
            } else {
                header('Location: ' . $redirectUrl . 'error&error=' . urlencode($stmt->error));
            }
            $stmt->close();
            exit;
        }
    }
}

// Hàm reorder menu items theo parent_id
function reorderMenuItems($conn, $parentId) {
    $stmt = $conn->prepare("SELECT id FROM menu_items WHERE parent_id = ? ORDER BY sort_order, id");
    $stmt->bind_param("i", $parentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $order = 0;
    while ($row = $result->fetch_assoc()) {
        $updateStmt = $conn->prepare("UPDATE menu_items SET sort_order = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $order, $row['id']);
        $updateStmt->execute();
        $updateStmt->close();
        $order++;
    }
    $stmt->close();
}

// Xử lý message từ URL
if (isset($_GET['msg'])) {
    switch($_GET['msg']) {
        case 'added': $message = 'Thêm menu thành công!'; $messageType = 'success'; break;
        case 'updated': $message = 'Cập nhật menu thành công!'; $messageType = 'success'; break;
        case 'deleted': $message = 'Xóa menu thành công!'; $messageType = 'success'; break;
        case 'has_children': $message = 'Không thể xóa menu này vì còn menu con!'; $messageType = 'danger'; break;
        case 'error': $message = 'Có lỗi: ' . ($_GET['error'] ?? 'Unknown'); $messageType = 'danger'; break;
        case 'reordered': $message = 'Đã cập nhật thứ tự menu!'; $messageType = 'success'; break;
    }
}

// Lấy scroll position từ URL
$scrollPosition = isset($_GET['scroll']) ? intval($_GET['scroll']) : 0;

// ================= LẤY DỮ LIỆU =================
$result = $conn->query("SELECT * FROM menu_items ORDER BY parent_id, sort_order, id");
if (!$result) {
    die('Lỗi truy vấn menu_items: ' . $conn->error);
}
$items = $result->fetch_all(MYSQLI_ASSOC);

function buildTree($items, $parent = 0) {
    $tree = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parent) {
            $children = buildTree($items, $item['id']);
            if ($children) $item['children'] = $children;
            $tree[] = $item;
        }
    }
    return $tree;
}
$menuTree = buildTree($items);

// ================= HÀM XÂY DỰNG DANH SÁCH MENU CHA CÓ CẤP BẬC =================
function buildMenuParentList($items, $parent = 0, $level = 0, &$result = [], $excludeId = null) {
    foreach ($items as $item) {
        if ($item['parent_id'] == $parent) {
            // Bỏ qua item đang được chỉnh sửa (để tránh vòng lặp)
            if ($excludeId !== null && $item['id'] == $excludeId) {
                continue;
            }
            $indent = str_repeat('— ', $level);
            if ($level > 0) {
                $indent = '&nbsp;&nbsp;' . str_repeat('&nbsp;&nbsp;', $level - 1) . '└─ ';
            }
            $result[] = [
                'id' => $item['id'],
                'label' => $indent . htmlspecialchars($item['label']),
                'level' => $level
            ];
            // Đệ quy để lấy các menu con
            buildMenuParentList($items, $item['id'], $level + 1, $result, $excludeId);
        }
    }
    return $result;
}

// Xây dựng danh sách menu cha cho select
$parentList = [];
buildMenuParentList($items, 0, 0, $parentList);

// Tạo mảng ánh xạ id => children để biết node nào có con
$hasChildrenMap = [];
foreach ($items as $item) {
    if ($item['parent_id'] > 0) {
        $hasChildrenMap[$item['parent_id']] = true;
    }
}

// Lấy trạng thái collapse từ session
if (!isset($_SESSION['collapsed_menus'])) {
    $_SESSION['collapsed_menus'] = [];
}
$collapsedMenus = $_SESSION['collapsed_menus'];

// Xử lý toggle collapse
if (isset($_GET['toggle_collapse']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $key = array_search($id, $_SESSION['collapsed_menus']);
    if ($key !== false) {
        unset($_SESSION['collapsed_menus'][$key]);
    } else {
        $_SESSION['collapsed_menus'][] = $id;
    }
    $_SESSION['collapsed_menus'] = array_values($_SESSION['collapsed_menus']);
    
    // Giữ scroll position khi toggle
    $redirectUrl = 'index.php';
    if (isset($_GET['scroll'])) {
        $redirectUrl .= '?scroll=' . intval($_GET['scroll']);
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// Hàm kiểm tra xem node có đang bị collapse không
function isCollapsed($id, $collapsedMenus) {
    return in_array($id, $collapsedMenus);
}

// Tạo flat list với thông tin collapse
$flatItems = [];
function flattenTreeWithCollapse($items, $level = 0, &$flatItems, $collapsedMenus, $allItems) {
    foreach ($items as $item) {
        $item['level'] = $level;
        $item['has_children'] = !empty($item['children']);
        $item['is_collapsed'] = isCollapsed($item['id'], $collapsedMenus);
        
        // Kiểm tra xem có parent nào đang bị collapse không
        $isParentCollapsed = false;
        $parentId = $item['parent_id'];
        while ($parentId > 0) {
            if (isCollapsed($parentId, $collapsedMenus)) {
                $isParentCollapsed = true;
                break;
            }
            // Tìm parent tiếp theo
            foreach ($allItems as $parent) {
                if ($parent['id'] == $parentId) {
                    $parentId = $parent['parent_id'];
                    break;
                }
            }
        }
        $item['is_hidden_by_parent'] = $isParentCollapsed;
        
        $flatItems[] = $item;
        
        if (!empty($item['children']) && !$item['is_collapsed']) {
            flattenTreeWithCollapse($item['children'], $level + 1, $flatItems, $collapsedMenus, $allItems);
        }
    }
}
flattenTreeWithCollapse($menuTree, 0, $flatItems, $collapsedMenus, $items);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Ruouchat Admin | Quản lý Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        /* ===== RESET & LAYOUT ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; overflow-x: hidden; height: 100vh; }

        .app-wrapper {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ===== SIDEBAR (giống product_form) ===== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 200px;
            height: 100vh;
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
            box-shadow: 12px 0 28px -12px rgba(0,0,0,0.25);
            border-right: 1px solid rgba(255,255,255,0.05);
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1000;
            transition: transform 0.3s ease;
            padding: 0 4px;
        }
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        .sidebar::-webkit-scrollbar-thumb { background: #eab308; border-radius: 10px; }

        .sidebar .brand {
            font-size: 1.1rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #FFFFFF 0%, #e2e8f0 100%);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
            padding: 0.9rem 0.5rem !important;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .sidebar a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 8px 14px;
            margin: 3px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            font-size: 0.8rem;
            border-radius: 20px;
            transition: all 0.2s;
            background: transparent;
            white-space: nowrap;
        }
        .sidebar a i {
            width: 24px;
            font-size: 1rem;
            text-align: center;
            color: #94a3b8;
            transition: all 0.2s;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.08);
            color: white;
            transform: translateX(4px);
        }
        .sidebar a:hover i {
            color: #facc15;
            transform: scale(1.05);
        }
        .sidebar a.active {
            background: linear-gradient(95deg, rgba(234,179,8,0.18), rgba(234,179,8,0.05));
            color: white;
            border-left: 3px solid #eab308;
        }
        .sidebar a.active i { color: #facc15; }
        #logoutBtn {
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            color: #facc15;
            font-weight: 600;
        }
        #logoutBtn i { color: #facc15; }
        #logoutBtn:hover {
            background: rgba(250,204,21,0.15);
            color: #fde047;
            transform: translateX(4px);
        }

        /* ===== NỘI DUNG CHÍNH ===== */
        .main-content {
            margin-left: 200px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            height: 100vh;
            background: #f8fafc;
            padding: 24px 28px;
        }

        /* ===== MOBILE TOGGLE & OVERLAY ===== */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1060;
            background: #eab308;
            border: none;
            padding: 10px 15px;
            border-radius: 10px;
            color: #0f172a;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
        }
        .sidebar-overlay.active { display: block; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 260px;
                z-index: 1050;
            }
            .sidebar.open { transform: translateX(0); }
            .main-content {
                margin-left: 0;
                padding-top: 70px;
                padding-left: 16px;
                padding-right: 16px;
            }
            .mobile-menu-toggle { display: block; }
        }

        @media (min-width: 769px) {
            .mobile-menu-toggle { display: none; }
            .sidebar-overlay { display: none !important; }
        }

        @media (max-width: 576px) {
            .main-content { padding: 70px 12px 12px 12px; }
        }

        /* ===== MENU STYLES (co nhỏ) ===== */
        .content { padding: 0; }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 1.2rem;
            gap: 12px;
        }
        .page-header h2 {
            font-weight: 500;
            font-size: 2rem;
            background: linear-gradient(135deg, #eab308, #f59e0b, #d97706);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            border-left: 5px solid #eab308;
            padding-left: 18px;
            margin: 0;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(234, 179, 8, 0.2);
        }

        .btn-gold {
            background: linear-gradient(135deg, #eab308, #ca8a04);
            border: none;
            padding: 6px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #0f172a;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            cursor: pointer;
        }
        .btn-gold:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #ca8a04, #a16207);
            color: white;
            box-shadow: 0 8px 18px rgba(0,0,0,0.1);
        }

        .card-modern {
            background: white;
            border-radius: 16px;
            box-shadow: 0 12px 24px -8px rgba(0,0,0,0.08);
            border: none;
            overflow: hidden;
            margin-bottom: 1.2rem;
        }
        .card-modern .card-header {
            background: #f8fafc;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 10px 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .card-modern .card-body {
            padding: 0;
        }

        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background: #f8fafc;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            padding: 10px 10px;
            color: #334155;
            border-bottom: 2px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }
        .table td {
            vertical-align: middle;
            padding: 10px 10px;
            font-size: 0.78rem;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Tất cả nút border-radius: 10px */
        .btn, .btn-gold, .btn-edit, .btn-delete,
        .btn-outline-secondary, .btn-primary, .btn-sm {
            border-radius: 10px !important;
        }

        .btn-edit {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            margin: 1px;
        }
        .btn-edit:hover { background: #d97706; color: white; transform: translateY(-1px); }
        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            margin: 1px;
        }
        .btn-delete:hover { background: #dc2626; color: white; transform: translateY(-1px); }

        .badge {
            font-size: 0.65rem;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .badge-heading {
            background: #eab30820;
            color: #b45309;
            font-size: 0.6rem;
            padding: 2px 8px;
            border-radius: 16px;
            margin-left: 6px;
        }
        .badge-highlight {
            background: #fef3c7;
            color: #d97706;
            font-size: 0.6rem;
            padding: 2px 8px;
            border-radius: 16px;
            margin-left: 4px;
        }
        .badge-child-count {
            background: #e2e8f0;
            color: #475569;
            font-size: 0.6rem;
            padding: 1px 8px;
            border-radius: 16px;
            margin-left: 4px;
            font-weight: 600;
        }

        .drag-handle {
            cursor: grab;
            color: #94a3b8;
            padding: 2px 6px;
            border-radius: 4px;
            transition: all 0.2s;
            user-select: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        .drag-handle:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .drag-handle:active { cursor: grabbing; }

        .menu-row.dragging { opacity: 0.3; background: #fef3c7 !important; }
        .menu-row.drop-target { border-left: 3px solid #eab308 !important; background: #fffbeb !important; }
        .menu-row.drop-target-below { border-bottom: 3px solid #eab308 !important; background: #fffbeb !important; }
        .menu-row.drag-over { background: #fef3c7 !important; }
        .menu-row:hover { background: #f8fafc; }
        .menu-row.hidden-row { display: none !important; }

        .collapse-btn {
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 4px;
            background: transparent;
            color: #64748b;
            font-size: 10px;
            transition: all 0.25s;
            user-select: none;
            border: 1px solid transparent;
            margin-right: 2px;
            text-decoration: none !important;
        }
        .collapse-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
            border-color: #cbd5e1;
        }
        .collapse-btn .fa-chevron-down {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 10px;
        }
        .collapse-btn.collapsed .fa-chevron-down { transform: rotate(-90deg); }
        .collapse-btn.has-children { color: #eab308; }
        .collapse-btn.has-children:hover {
            background: #fef3c7;
            border-color: #eab308;
            color: #ca8a04;
        }

        .level-indent {
            display: inline-block;
            width: 16px;
            flex-shrink: 0;
        }
        .level-0 .level-indent { display: none; }
        .level-1 .level-indent { width: 20px; }
        .level-2 .level-indent { width: 36px; }
        .level-3 .level-indent { width: 52px; }
        .level-4 .level-indent { width: 68px; }
        .level-5 .level-indent { width: 84px; }
        .level-6 .level-indent { width: 100px; }

        .menu-label-wrapper {
            display: flex;
            align-items: center;
            gap: 3px;
            flex-wrap: wrap;
        }
        .menu-label-wrapper .label-text { font-weight: 500; font-size: 0.85rem; }
        .tree-line { color: #cbd5e1; font-size: 0.6rem; margin-right: 2px; font-weight: 300; }

        .link-seo-preview {
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 10px;
            font-size: 0.8rem;
            color: #0f172a;
            margin-top: 4px;
            display: inline-block;
        }
        .link-seo-preview i { color: #eab308; margin-right: 4px; }

        /* Modal co nhỏ */
        .modal-content {
            border-radius: 16px;
            border: none;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .modal-header {
            background: linear-gradient(105deg, #fffaf5, #fff7ed);
            border-bottom: 1px solid #fed7aa;
            padding: 0.8rem 1.2rem;
        }
        .modal-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #7c2d12;
        }
        .modal-footer { border-top: 1px solid #f1f5f9; padding: 0.8rem 1.2rem; }
        .modal .form-control, .modal .form-select {
            border-radius: 10px;
            font-size: 0.85rem;
            padding: 6px 12px;
        }
        .modal .form-label {
            font-weight: 600;
            font-size: 0.75rem;
            color: #334155;
            margin-bottom: 2px;
        }
        .modal .form-check { font-size: 0.85rem; }

        .link-type-group {
            background: #f8fafc;
            padding: 14px 16px;
            border-radius: 12px;
            margin-top: 12px;
        }

        .form-check-inline-group {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 16px;
        }
        .loading-overlay.active { display: flex; }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-top-color: #eab308;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        .loading-text {
            color: #0f172a;
            font-weight: 600;
            font-size: 0.9rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .scroll-top-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #eab308;
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(234, 179, 8, 0.4);
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.3s;
            z-index: 100;
        }
        .scroll-top-btn:hover { transform: scale(1.1); background: #ca8a04; }
        .scroll-top-btn.show { display: flex; }

        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }
        .toast-custom {
            background: #0f172a;
            color: white;
            border-radius: 12px;
            padding: 12px 20px;
            font-size: 0.85rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        .toast-custom.success { border-left: 4px solid #22c55e; }
        .toast-custom.error { border-left: 4px solid #ef4444; }
        .toast-custom .toast-icon { font-size: 1.1rem; }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        .alert {
            border-radius: 12px;
            border: none;
            font-size: 0.9rem;
            padding: 10px 16px;
        }
        .alert .btn-close { font-size: 0.75rem; }

        .category-select-option, .parent-select-option {
            font-family: 'Inter', sans-serif;
        }
        .category-select-option.level-0 { font-weight: 600; }
        .category-select-option.level-1 { padding-left: 20px; }
        .category-select-option.level-2 { padding-left: 40px; }
        .category-select-option.level-3 { padding-left: 60px; }
        .category-select-option.level-4 { padding-left: 80px; }
        .category-select-option.level-5 { padding-left: 100px; }
        .parent-select-option.level-0 { font-weight: 600; }
        .parent-select-option.level-1 { padding-left: 20px; }
        .parent-select-option.level-2 { padding-left: 40px; }
        .parent-select-option.level-3 { padding-left: 60px; }
        .parent-select-option.level-4 { padding-left: 80px; }
        .parent-select-option.level-5 { padding-left: 100px; }

        @media (max-width: 768px) {
            .table td, .table th { padding: 6px 6px; font-size: 0.7rem; }
            .page-header h2 { font-size: 1.2rem; }
            .btn-gold { font-size: 0.8rem; padding: 5px 12px; }
            .btn-edit, .btn-delete { font-size: 0.65rem; padding: 3px 8px; }
            .menu-label-wrapper .label-text { font-size: 0.75rem; }
            .collapse-btn { width: 20px; height: 20px; font-size: 9px; }
            .level-1 .level-indent { width: 16px; }
            .level-2 .level-indent { width: 28px; }
            .level-3 .level-indent { width: 40px; }
            .modal .form-control, .modal .form-select { font-size: 0.8rem; padding: 5px 10px; }
        }

        @media (max-width: 480px) {
            .table td, .table th { padding: 4px 4px; font-size: 0.6rem; }
            .btn-edit, .btn-delete { font-size: 0.55rem; padding: 2px 6px; }
            .drag-handle { font-size: 0.7rem; padding: 1px 4px; }
            .collapse-btn { width: 18px; height: 18px; font-size: 8px; }
        }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">Đang cập nhật thứ tự menu...</div>
</div>

<!-- Scroll to top button -->
<button class="scroll-top-btn" id="scrollTopBtn" title="Lên đầu trang">
    <i class="fas fa-chevron-up"></i>
</button>

<div class="app-wrapper">
    <!-- SIDEBAR (giống product_form) -->
    <div class="sidebar" id="sidebar">
        <div class="brand">Ruouchat Admin</div>
        <div class="d-flex flex-column">
            <a href="index.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="products.html"><i class="fas fa-wine-bottle"></i> Sản phẩm</a>
            <a href="flash_sales.html"><i class="fas fa-bolt"></i> Flash sale</a>
            <a href="categories.html"><i class="fas fa-folder"></i> Danh mục</a>
            <a href="orders.html"><i class="fas fa-shopping-cart"></i> Đơn hàng</a>
            <a href="customers.html"><i class="fas fa-users"></i> Khách hàng</a>
            <a href="contacts.html"><i class="fas fa-envelope"></i> Liên hệ</a>
            <a href="posts.html"><i class="fas fa-newspaper"></i> Bài viết</a>
            <a href="gift_products.html"><i class="fas fa-gift"></i> Quà tặng</a>
            <a href="glasses.html"><i class="fas fa-glass-martini"></i> Ly pha lê</a>
            <a href="other_products.html"><i class="fas fa-box"></i> Sản phẩm khác</a>
            <a href="promotions.html"><i class="fas fa-bullhorn"></i> Khuyến mãi</a>
            <a href="producers.php"><i class="fas fa-building"></i> Nhà sản xuất</a>
            <a href="policies.html"><i class="fas fa-file-contract"></i> Chính sách</a>
            <a href="index.php" class="active"><i class="fas fa-list"></i> Menu</a>
            <a href="pages.php"><i class="fas fa-file-alt"></i> Trang mới</a>
            <a href="banners.html"><i class="fas fa-image"></i> Banner</a>
            <a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </div>

    <!-- OVERLAY mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- NÚT TOGGLE mobile -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i> Menu
    </button>

    <!-- NỘI DUNG CHÍNH -->
    <div class="main-content" id="mainContent">
        <div class="content">
            <div class="page-header">
                <h2>Quản lý Menu</h2>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus-circle"></i> Thêm menu mới
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show mb-3" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Danh sách menu -->
            <div class="card-modern">
                <div class="card-header">
                    <i class="fas fa-sitemap me-2" style="color:#eab308;"></i> Cấu trúc menu
                    <span class="text-muted ms-2 small">
                        <i class="fas fa-arrows-alt text-muted"></i> Kéo thả để sắp xếp |
                        <i class="fas fa-chevron-down text-muted"></i> Click để mở/rút gọn
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="menuTable">
                            <thead>
                                <tr>
                                    <th style="width: 32px;"></th>
                                    <th style="width: 40px;">ID</th>
                                    <th>Tên menu</th>
                                    <th>Link SEO</th>
                                    <th>Loại</th>
                                    <th>Parent</th>
                                    <th style="width: 150px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="menuBody">
                            <?php
                            if (!empty($flatItems)) {
                                foreach ($flatItems as $index => $item) {
                                    // Bỏ qua các item bị ẩn bởi parent collapse
                                    if ($item['is_hidden_by_parent']) {
                                        continue;
                                    }
                                    
                                    $level = $item['level'] ?? 0;
                                    $hasChildren = isset($hasChildrenMap[$item['id']]) && $hasChildrenMap[$item['id']];
                                    $isCollapsed = isset($item['is_collapsed']) ? $item['is_collapsed'] : false;
                                    
                                    // Đếm số lượng con trực tiếp
                                    $childCount = 0;
                                    foreach ($items as $subItem) {
                                        if ($subItem['parent_id'] == $item['id']) {
                                            $childCount++;
                                        }
                                    }
                                    
                                    $parentName = 'Gốc';
                                    if ($item['parent_id'] > 0) {
                                        foreach ($items as $it) {
                                            if ($it['id'] == $item['parent_id']) {
                                                $parentName = htmlspecialchars($it['label']);
                                                break;
                                            }
                                        }
                                    }
                                    
                                    $linkTypeLabels = [
                                        'custom' => 'Tùy chỉnh',
                                        'category' => 'Danh mục',
                                        'country' => 'Quốc gia',
                                        'region' => 'Vùng',
                                        'grape' => 'Giống nho',
                                        'wine_type' => 'Loại rượu'
                                    ];
                                    $linkTypeLabel = $linkTypeLabels[$item['link_type']] ?? $item['link_type'];
                                    
                                    $seoLink = $item['link'];
                                    if ($item['link_type'] === 'category' && !empty($item['slug'])) {
                                        $seoLink = '/' . $item['slug'];
                                    }
                                    
                                    $label = htmlspecialchars($item['label']);
                                    if ($item['is_heading']) $label .= ' <span class="badge-heading"><i class="fas fa-heading"></i> Heading</span>';
                                    if ($item['is_highlight']) $label .= ' <span class="badge-highlight"><i class="fas fa-star"></i> Nổi bật</span>';
                                    if ($hasChildren) {
                                        $label .= ' <span class="badge-child-count"><i class="fas fa-folder"></i> ' . $childCount . '</span>';
                                    }
                                    
                                    $levelClass = 'level-' . min($level, 6);
                                    $treeLines = '';
                                    for ($i = 0; $i < $level; $i++) {
                                        if ($i == $level - 1) {
                                            $treeLines .= '<span class="tree-line">└──</span>';
                                        } else {
                                            $treeLines .= '<span class="tree-line">│&nbsp;&nbsp;</span>';
                                        }
                                    }
                                    
                                    // Collapse button
                                    $collapseBtn = '';
                                    if ($hasChildren) {
                                        $scrollParam = $scrollPosition > 0 ? '&scroll=' . $scrollPosition : '';
                                        $collapseBtn = '<a href="?toggle_collapse=1&id=' . $item['id'] . $scrollParam . '" class="collapse-btn ' . ($isCollapsed ? 'collapsed' : '') . ' has-children" title="' . ($isCollapsed ? 'Mở rộng' : 'Thu gọn') . '">
                                            <i class="fas fa-chevron-down"></i>
                                        </a>';
                                    }
                                    
                                    // Kiểm tra xem có parent nào đang collapsed không
                                    $hasCollapsedParent = false;
                                    $parentId = $item['parent_id'];
                                    while ($parentId > 0) {
                                        if (in_array($parentId, $collapsedMenus)) {
                                            $hasCollapsedParent = true;
                                            break;
                                        }
                                        foreach ($items as $parent) {
                                            if ($parent['id'] == $parentId) {
                                                $parentId = $parent['parent_id'];
                                                break;
                                            }
                                        }
                                    }
                                    
                                    $hiddenClass = $hasCollapsedParent ? ' hidden-row' : '';
                                    
                                    echo "<tr class='menu-row level-{$level}{$hiddenClass}' data-id='{$item['id']}' data-parent='{$item['parent_id']}' data-sort='{$item['sort_order']}' data-level='{$level}' draggable='true'>";
                                    echo "<td><span class='drag-handle'><i class='fas fa-grip-vertical'></i></span></td>";
                                    echo "<td>{$item['id']}</td>";
                                    echo "<td class='{$levelClass}'>
                                            <div class='menu-label-wrapper'>
                                                <span class='level-indent'></span>
                                                {$treeLines}
                                                {$collapseBtn}
                                                <span class='label-text'>{$label}</span>
                                            </div>
                                          </td>";
                                    echo "<td><code class='text-primary' style='font-size:0.7rem;'>" . htmlspecialchars($seoLink) . "</code></td>";
                                    echo "<td><span class='badge bg-light text-dark px-2 py-1'>" . $linkTypeLabel . "</span></td>";
                                    echo "<td>{$parentName}</td>";
                                    echo "<td>
                                            <button class='btn-edit btn-sm' onclick='editMenu(" . json_encode($item) . ")'><i class='fas fa-edit'></i></button>
                                            <form method='POST' style='display:inline-block' onsubmit='return confirm(\"Bạn có chắc muốn xóa menu này?\")'>
                                                <input type='hidden' name='action' value='delete'>
                                                <input type='hidden' name='id' value='{$item['id']}'>
                                                <input type='hidden' name='scroll_pos' value='{$scrollPosition}'>
                                                <button type='submit' class='btn-delete btn-sm'><i class='fas fa-trash'></i></button>
                                            </form>
                                           </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center py-4 text-muted">Chưa có menu nào. Nhấn "Thêm menu mới" để bắt đầu.</td></tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Modal Thêm menu -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2" style="color:#eab308;"></i> Thêm menu mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="addForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="scroll_pos" id="add_scroll_pos" value="<?= $scrollPosition ?>">
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Tên menu *</label>
                            <input type="text" name="label" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Menu cha</label>
                            <select name="parent_id" class="form-select" id="add_parent_select">
                                <option value="0">— Gốc —</option>
                                <?php foreach($parentList as $pItem): ?>
                                    <option value="<?= $pItem['id'] ?>" class="parent-select-option level-<?= $pItem['level'] ?>">
                                        <?= $pItem['label'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Chọn menu cha để tạo cấp bậc</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Thứ tự</label>
                            <input type="number" name="sort_order" class="form-control" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cột</label>
                            <input type="number" name="column_group" class="form-control" value="1">
                        </div>
                        <div class="col-md-4">
                            <div class="form-check-inline-group mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_heading" id="add_is_heading">
                                    <label class="form-check-label" for="add_is_heading">Heading</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_highlight" id="add_is_highlight">
                                    <label class="form-check-label" for="add_is_highlight">Nổi bật</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="link-type-group">
                        <label class="form-label fw-bold mb-2">Loại liên kết</label>
                        <div class="d-flex flex-wrap gap-3 mb-2">
                            <div class="form-check"><input class="form-check-input" type="radio" name="link_type" value="custom" id="add_link_custom" checked><label for="add_link_custom">Tùy chỉnh</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="link_type" value="category" id="add_link_category"><label for="add_link_category">Danh mục</label></div>
                        </div>

                        <div id="add_customField">
                            <label class="form-label">Link</label>
                            <input type="text" name="link" class="form-control" placeholder="/san-pham" value="#">
                            <small class="text-muted">Ví dụ: /san-pham, /gioi-thieu, /lien-he</small>
                            
                            <div class="mt-2">
                                <label class="form-label">Slug SEO (tùy chọn)</label>
                                <input type="text" name="slug" class="form-control" placeholder="san-pham">
                                <small class="text-muted">Đường dẫn thân thiện, chỉ dùng cho link tùy chỉnh</small>
                            </div>
                        </div>
                        
                        <div id="add_categoryField" style="display:none">
                            <label class="form-label">Chọn danh mục</label>
                            <select name="category_id" id="add_category_select" class="form-select">
                                <option value="0">-- Chọn danh mục --</option>
                                <?php foreach($categoryTree as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" data-slug="<?= htmlspecialchars($cat['slug']) ?>" class="category-select-option level-<?= $cat['level'] ?>">
                                        <?= $cat['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mt-2">
                                <span class="link-seo-preview">
                                    <i class="fas fa-link"></i> Link SEO: <strong id="add_seo_preview">/chon-danh-muc</strong>
                                </span>
                            </div>
                            <small class="text-muted">Link sẽ tự động tạo theo slug của danh mục</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-gold px-4"><i class="fas fa-save"></i> Lưu lại</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa menu -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2" style="color:#eab308;"></i> Sửa menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="scroll_pos" id="edit_scroll_pos" value="<?= $scrollPosition ?>">
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Tên menu *</label>
                            <input type="text" name="label" id="edit_label" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Menu cha</label>
                            <select name="parent_id" id="edit_parent_id" class="form-select">
                                <option value="0">— Gốc —</option>
                                <?php 
                                // Xây dựng danh sách parent cho edit, loại bỏ item đang edit
                                $editParentList = [];
                                buildMenuParentList($items, 0, 0, $editParentList, isset($_POST['id']) ? intval($_POST['id']) : null);
                                foreach($editParentList as $pItem): 
                                ?>
                                    <option value="<?= $pItem['id'] ?>" class="parent-select-option level-<?= $pItem['level'] ?>">
                                        <?= $pItem['label'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Chọn menu cha để tạo cấp bậc</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Thứ tự</label>
                            <input type="number" name="sort_order" id="edit_sort_order" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cột</label>
                            <input type="number" name="column_group" id="edit_column_group" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <div class="form-check-inline-group mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_heading" id="edit_is_heading">
                                    <label for="edit_is_heading">Heading</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_highlight" id="edit_is_highlight">
                                    <label for="edit_is_highlight">Nổi bật</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="link-type-group">
                        <label class="form-label fw-bold mb-2">Loại liên kết</label>
                        <div class="d-flex flex-wrap gap-3 mb-2">
                            <div class="form-check"><input class="form-check-input" type="radio" name="link_type" value="custom" id="edit_link_custom"><label for="edit_link_custom">Tùy chỉnh</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="link_type" value="category" id="edit_link_category"><label for="edit_link_category">Danh mục</label></div>
                        </div>

                        <div id="edit_customField">
                            <label class="form-label">Link</label>
                            <input type="text" name="link" id="edit_link" class="form-control" placeholder="/san-pham">
                            
                            <div class="mt-2">
                                <label class="form-label">Slug SEO (tùy chọn)</label>
                                <input type="text" name="slug" id="edit_slug" class="form-control" placeholder="san-pham">
                                <small class="text-muted">Đường dẫn thân thiện, chỉ dùng cho link tùy chỉnh</small>
                            </div>
                        </div>
                        
                        <div id="edit_categoryField" style="display:none">
                            <label class="form-label">Chọn danh mục</label>
                            <select name="category_id" id="edit_category_id" class="form-select">
                                <option value="0">-- Chọn danh mục --</option>
                                <?php foreach($categoryTree as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" data-slug="<?= htmlspecialchars($cat['slug']) ?>" class="category-select-option level-<?= $cat['level'] ?>">
                                        <?= $cat['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mt-2">
                                <span class="link-seo-preview">
                                    <i class="fas fa-link"></i> Link SEO: <strong id="edit_seo_preview">/chon-danh-muc</strong>
                                </span>
                            </div>
                            <small class="text-muted">Link sẽ tự động tạo theo slug của danh mục</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-gold px-4"><i class="fas fa-save"></i> Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ================= LƯU VÀ KHÔI PHỤC SCROLL POSITION =================
document.addEventListener('DOMContentLoaded', function() {
    const mainContent = document.getElementById('mainContent');
    const scrollPosition = <?= $scrollPosition ?>;
    
    if (scrollPosition > 0 && mainContent) {
        mainContent.scrollTop = scrollPosition;
    }
    
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const scrollPos = mainContent ? mainContent.scrollTop : 0;
            const scrollInput = this.querySelector('input[name="scroll_pos"]');
            if (scrollInput) {
                scrollInput.value = scrollPos;
            }
        });
    });
    
    const collapseLinks = document.querySelectorAll('.collapse-btn');
    collapseLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const scrollPos = mainContent ? mainContent.scrollTop : 0;
            const href = this.getAttribute('href');
            if (href && !href.includes('scroll=')) {
                const separator = href.includes('?') ? '&' : '?';
                this.setAttribute('href', href + separator + 'scroll=' + scrollPos);
            }
        });
    });
});

// ================= DRAG AND DROP =================
let draggedRow = null;

document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.menu-row[draggable="true"]:not(.hidden-row)');
    const mainContent = document.getElementById('mainContent');
    
    rows.forEach(row => {
        row.addEventListener('dragstart', function(e) {
            draggedRow = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.id);
        });
        
        row.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
            document.querySelectorAll('.menu-row.drop-target, .menu-row.drop-target-below').forEach(el => {
                el.classList.remove('drop-target', 'drop-target-below');
            });
        });
        
        row.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            if (this === draggedRow) return;
            if (this.classList.contains('hidden-row')) return;
            
            const target = this;
            const rect = target.getBoundingClientRect();
            const mouseY = e.clientY;
            const threshold = rect.top + rect.height * 0.3;
            
            document.querySelectorAll('.menu-row.drop-target, .menu-row.drop-target-below').forEach(el => {
                if (el !== target) {
                    el.classList.remove('drop-target', 'drop-target-below');
                }
            });
            
            if (mouseY < threshold) {
                target.classList.remove('drop-target-below');
                target.classList.add('drop-target');
            } else {
                target.classList.remove('drop-target');
                target.classList.add('drop-target-below');
            }
        });
        
        row.addEventListener('dragleave', function(e) {
            if (!this.contains(e.relatedTarget)) {
                this.classList.remove('drop-target', 'drop-target-below');
            }
        });
        
        row.addEventListener('drop', function(e) {
            e.preventDefault();
            
            const targetRow = this;
            const draggedId = parseInt(e.dataTransfer.getData('text/plain'));
            const draggedRowElement = document.querySelector(`.menu-row[data-id="${draggedId}"]`);
            
            if (!draggedRowElement || draggedRowElement === targetRow || targetRow.classList.contains('hidden-row')) {
                cleanDropStyles();
                return;
            }
            
            const rect = targetRow.getBoundingClientRect();
            const mouseY = e.clientY;
            const threshold = rect.top + rect.height * 0.3;
            
            let newParent, newSort;
            const targetId = parseInt(targetRow.dataset.id);
            const targetParent = parseInt(targetRow.dataset.parent);
            
            const allRows = Array.from(document.querySelectorAll('.menu-row[data-id]:not(.hidden-row)'));
            
            if (mouseY < threshold) {
                newParent = targetId;
                const children = allRows.filter(row => parseInt(row.dataset.parent) === targetId);
                newSort = children.length;
            } else {
                newParent = targetParent;
                const siblings = allRows.filter(row => parseInt(row.dataset.parent) === targetParent);
                siblings.sort((a, b) => parseInt(a.dataset.sort) - parseInt(b.dataset.sort));
                
                const targetIndex = siblings.findIndex(row => parseInt(row.dataset.id) === targetId);
                if (targetIndex !== -1) {
                    newSort = targetIndex;
                } else {
                    newSort = siblings.length;
                }
            }
            
            document.getElementById('loadingOverlay').classList.add('active');
            updateMenuOrder(draggedId, newParent, newSort);
            cleanDropStyles();
        });
    });
    
    function cleanDropStyles() {
        document.querySelectorAll('.menu-row.drop-target, .menu-row.drop-target-below').forEach(el => {
            el.classList.remove('drop-target', 'drop-target-below');
        });
        draggedRow = null;
    }
});

// ================= UPDATE ORDER VIA API =================
async function updateMenuOrder(draggedId, newParent, newSort) {
    try {
        const mainContent = document.getElementById('mainContent');
        const scrollPos = mainContent ? mainContent.scrollTop : 0;
        
        const allRows = Array.from(document.querySelectorAll('.menu-row[data-id]:not(.hidden-row)'));
        
        const draggedElement = allRows.find(row => parseInt(row.dataset.id) === draggedId);
        if (!draggedElement) {
            throw new Error('Không tìm thấy item được kéo');
        }
        
        const oldParent = parseInt(draggedElement.dataset.parent);
        let itemsToUpdate = [];
        
        // 1. Cập nhật cho parent mới
        let siblingsInNewParent = allRows.filter(row => 
            parseInt(row.dataset.parent) === newParent && 
            parseInt(row.dataset.id) !== draggedId
        );
        siblingsInNewParent.sort((a, b) => parseInt(a.dataset.sort) - parseInt(b.dataset.sort));
        
        if (newSort >= siblingsInNewParent.length) {
            siblingsInNewParent.push(draggedElement);
        } else {
            siblingsInNewParent.splice(newSort, 0, draggedElement);
        }
        
        siblingsInNewParent.forEach((row, index) => {
            itemsToUpdate.push({
                id: parseInt(row.dataset.id),
                parent_id: newParent,
                sort_order: index
            });
        });
        
        // 2. Nếu parent cũ khác parent mới, cập nhật lại parent cũ
        if (oldParent !== newParent && oldParent > 0) {
            const oldSiblings = allRows.filter(row => 
                parseInt(row.dataset.parent) === oldParent && 
                parseInt(row.dataset.id) !== draggedId
            );
            oldSiblings.sort((a, b) => parseInt(a.dataset.sort) - parseInt(b.dataset.sort));
            oldSiblings.forEach((row, index) => {
                itemsToUpdate.push({
                    id: parseInt(row.dataset.id),
                    parent_id: oldParent,
                    sort_order: index
                });
            });
        }
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_order',
                items: itemsToUpdate
            })
        });
        
        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Response not JSON:', text.substring(0, 200));
            throw new Error('Server trả về dữ liệu không hợp lệ');
        }
        
        document.getElementById('loadingOverlay').classList.remove('active');
        
        if (result.success) {
            showToast('Đã cập nhật thứ tự menu thành công!', 'success');
            setTimeout(() => {
                window.location.href = window.location.pathname + '?msg=reordered&scroll=' + scrollPos;
            }, 500);
        } else {
            showToast('Lỗi: ' + (result.error || 'Không thể cập nhật thứ tự'), 'error');
        }
    } catch (error) {
        document.getElementById('loadingOverlay').classList.remove('active');
        console.error('Error updating menu order:', error);
        showToast('Lỗi: ' + error.message, 'error');
    }
}

// ================= TOAST NOTIFICATION =================
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast-custom ${type}`;
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    toast.innerHTML = `
        <span class="toast-icon"><i class="fas ${icon}"></i></span>
        <span>${message}</span>
    `;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ================= SCROLL TO TOP BUTTON =================
document.addEventListener('DOMContentLoaded', function() {
    const mainContent = document.getElementById('mainContent');
    const scrollBtn = document.getElementById('scrollTopBtn');
    
    if (mainContent && scrollBtn) {
        mainContent.addEventListener('scroll', function() {
            if (this.scrollTop > 300) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
        });
        
        scrollBtn.addEventListener('click', function() {
            mainContent.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});

// ================= MODAL FUNCTIONS =================
function toggleAddFields(type) {
    document.getElementById('add_customField').style.display = 'none';
    document.getElementById('add_categoryField').style.display = 'none';
    if (type === 'custom') {
        document.getElementById('add_customField').style.display = 'block';
        document.getElementById('add_seo_preview').textContent = '/tuy-chinh';
    } else if (type === 'category') {
        document.getElementById('add_categoryField').style.display = 'block';
        updateAddSeoPreview();
    }
}

function updateAddSeoPreview() {
    const select = document.getElementById('add_category_select');
    const selectedOption = select.options[select.selectedIndex];
    const slug = selectedOption ? selectedOption.dataset.slug : '';
    document.getElementById('add_seo_preview').textContent = slug ? '/' + slug : '/chon-danh-muc';
}

document.addEventListener('DOMContentLoaded', function() {
    const addSelect = document.getElementById('add_category_select');
    if (addSelect) {
        addSelect.addEventListener('change', updateAddSeoPreview);
        updateAddSeoPreview();
    }
});

const addRadios = document.querySelectorAll('#addModal input[name="link_type"]');
addRadios.forEach(r => r.addEventListener('change', () => toggleAddFields(r.value)));
toggleAddFields('custom');

function toggleEditFields(type) {
    document.getElementById('edit_customField').style.display = 'none';
    document.getElementById('edit_categoryField').style.display = 'none';
    if (type === 'custom') {
        document.getElementById('edit_customField').style.display = 'block';
    } else if (type === 'category') {
        document.getElementById('edit_categoryField').style.display = 'block';
        updateEditSeoPreview();
    }
}

function updateEditSeoPreview() {
    const select = document.getElementById('edit_category_id');
    const selectedOption = select.options[select.selectedIndex];
    const slug = selectedOption ? selectedOption.dataset.slug : '';
    document.getElementById('edit_seo_preview').textContent = slug ? '/' + slug : '/chon-danh-muc';
}

document.addEventListener('DOMContentLoaded', function() {
    const editSelect = document.getElementById('edit_category_id');
    if (editSelect) {
        editSelect.addEventListener('change', updateEditSeoPreview);
    }
});

const editRadios = document.querySelectorAll('#editModal input[name="link_type"]');
editRadios.forEach(r => r.addEventListener('change', () => toggleEditFields(r.value)));

let editModal;
function editMenu(data) {
    const mainContent = document.getElementById('mainContent');
    const scrollPos = mainContent ? mainContent.scrollTop : 0;
    
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_label').value = data.label;
    document.getElementById('edit_parent_id').value = data.parent_id;
    document.getElementById('edit_sort_order').value = data.sort_order;
    document.getElementById('edit_column_group').value = data.column_group;
    document.getElementById('edit_is_heading').checked = data.is_heading == 1;
    document.getElementById('edit_is_highlight').checked = data.is_highlight == 1;
    document.getElementById('edit_scroll_pos').value = scrollPos;
    
    document.getElementById('edit_slug').value = data.slug || '';
    
    const linkType = data.link_type || 'custom';
    const radio = document.querySelector(`#editModal input[name="link_type"][value="${linkType}"]`);
    if (radio) {
        radio.checked = true;
        toggleEditFields(linkType);
    }
    
    if (linkType === 'custom') {
        document.getElementById('edit_link').value = data.link || '#';
    } else if (linkType === 'category') {
        const sel = document.getElementById('edit_category_id');
        if (sel && data.link_value) {
            sel.value = data.link_value;
            updateEditSeoPreview();
        }
    }
    
    if (editModal) editModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const addModal = document.getElementById('addModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', function() {
            const mainContent = document.getElementById('mainContent');
            const scrollPos = mainContent ? mainContent.scrollTop : 0;
            document.getElementById('add_scroll_pos').value = scrollPos;
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    editModal = new bootstrap.Modal(document.getElementById('editModal'));
});

// ================= MOBILE MENU =================
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const toggleBtn = document.getElementById('mobileMenuToggle');

function closeMobileMenu() {
    if (window.innerWidth <= 768) {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    }
}

function openMobileMenu() {
    if (window.innerWidth <= 768) {
        sidebar.classList.add('open');
        overlay.classList.add('active');
    }
}

if (toggleBtn) {
    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (sidebar.classList.contains('open')) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    });
}

if (overlay) {
    overlay.addEventListener('click', closeMobileMenu);
}

window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    }
});

// ================= LOGOUT =================
document.getElementById('logoutBtn')?.addEventListener('click', async (e) => {
    e.preventDefault();
    if(confirm('Bạn có chắc muốn đăng xuất?')) {
        try {
            await fetch('/api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' })
            });
        } catch(e) {}
        window.location.href = 'login.html';
    }
});
</script>
</body>
</html>