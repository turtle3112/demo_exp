<?php
// category_form.php - Quản lý danh mục sản phẩm (Hỗ trợ phân cấp 4 cấp)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

require_once __DIR__ . '/../api/config.php';

// Kiểm tra đăng nhập admin
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.html');
    exit;
}

$message = '';
$messageType = '';
$category = null;

// Cấp độ tối đa cho phép
define('MAX_CATEGORY_LEVEL', 4);

// Hàm tạo slug
function createSlugFromName($name) {
    $slug = strtolower(trim($name));
    // Chuyển đổi tiếng Việt có dấu thành không dấu
    $slug = str_replace(
        ['á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ',
         'đ', 'é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ',
         'í', 'ì', 'ỉ', 'ĩ', 'ị', 'ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ',
         'ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự',
         'ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ'],
        ['a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
         'd', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
         'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
         'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
         'y', 'y', 'y', 'y', 'y'],
        $slug
    );
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = preg_replace('/^-+|-+$/', '', $slug);
    return $slug;
}

// Hàm kiểm tra tên danh mục đã tồn tại
function isCategoryNameExists($name, $conn, $excludeId = 0) {
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
    $stmt->bind_param("si", $name, $excludeId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Hàm kiểm tra slug đã tồn tại
function isCategorySlugExists($slug, $conn, $excludeId = 0) {
    $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
    $stmt->bind_param("si", $slug, $excludeId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Hàm tạo slug unique
function getUniqueSlug($slug, $conn, $excludeId = 0) {
    $originalSlug = $slug;
    $counter = 1;
    while (isCategorySlugExists($slug, $conn, $excludeId)) {
        $slug = $originalSlug . '-' . $counter++;
    }
    return $slug;
}

// Hàm kiểm tra có tạo vòng lặp vô hạn không
function isCircularParent($parentId, $childId, $conn) {
    if ($parentId == 0) return false;
    if ($parentId == $childId) return true;
    
    $currentId = $parentId;
    $visited = [];
    while ($currentId != 0) {
        if (in_array($currentId, $visited)) break;
        $visited[] = $currentId;
        
        $stmt = $conn->prepare("SELECT parent_id FROM categories WHERE id = ?");
        $stmt->bind_param("i", $currentId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $currentId = $row['parent_id'] ?? 0;
        } else {
            $currentId = 0;
        }
        $stmt->close();
        
        if ($currentId == $childId) return true;
    }
    return false;
}

// Hàm kiểm tra cấp độ của danh mục
function getCategoryLevel($categoryId, $conn) {
    if ($categoryId == 0) return -1;
    $stmt = $conn->prepare("SELECT level FROM categories WHERE id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return (int)$row['level'];
    }
    $stmt->close();
    return -1;
}

// Hàm kiểm tra có thể đặt danh mục này làm cha không (không vượt quá MAX_CATEGORY_LEVEL)
function canSetAsParent($parentId, $conn) {
    if ($parentId == 0) return true;
    $level = getCategoryLevel($parentId, $conn);
    return $level < MAX_CATEGORY_LEVEL - 1;
}

// Hàm tính level mới cho danh mục
function calculateNewLevel($parentId, $conn) {
    if ($parentId == 0) return 0;
    $level = getCategoryLevel($parentId, $conn);
    return $level + 1;
}

// Xử lý upload ảnh
function uploadImage($file, $type = 'category') {
    $targetDir = __DIR__ . '/../uploads/' . $type . '/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    if (!in_array($ext, $allowedExts)) {
        return false;
    }
    
    $fileName = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $targetFile = $targetDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return 'uploads/' . $type . '/' . $fileName;
    }
    return false;
}

// Xử lý xóa ảnh cũ
function deleteOldImage($imagePath) {
    if (!empty($imagePath) && file_exists(__DIR__ . '/../' . $imagePath)) {
        unlink(__DIR__ . '/../' . $imagePath);
    }
}

// Hàm cập nhật level cho tất cả danh mục con (đệ quy)
function updateChildrenLevels($parentId, $newLevel, $conn) {
    $stmt = $conn->prepare("SELECT id FROM categories WHERE parent_id = ?");
    $stmt->bind_param("i", $parentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $childId = $row['id'];
        $updateStmt = $conn->prepare("UPDATE categories SET level = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $newLevel, $childId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Đệ quy cập nhật cho các cấp con tiếp theo
        updateChildrenLevels($childId, $newLevel + 1, $conn);
    }
    $stmt->close();
}

// HÀM LẤY ẢNH SẢN PHẨM TỪ BẢNG product_images
function getProductImage($productId, $conn) {
    // Ưu tiên lấy ảnh chính (is_main = 1)
    $stmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 1 ORDER BY id LIMIT 1");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['image_url'];
    }
    $stmt->close();
    
    // Nếu không có ảnh chính, lấy ảnh đầu tiên
    $stmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order, id LIMIT 1");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['image_url'];
    }
    $stmt->close();
    
    return null;
}

// Hàm lấy danh sách ảnh sản phẩm (tối đa 5 ảnh)
function getProductImages($productId, $conn, $limit = 5) {
    $stmt = $conn->prepare("SELECT image_url, is_main, sort_order FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order, id LIMIT ?");
    $stmt->bind_param("ii", $productId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $images = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $images;
}

// Xử lý submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Xử lý xóa danh mục
    if ($action === 'delete' && isset($_POST['delete_id'])) {
        $deleteId = (int)$_POST['delete_id'];
        
        // Kiểm tra xem danh mục có danh mục con không
        $childStmt = $conn->prepare("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?");
        $childStmt->bind_param("i", $deleteId);
        $childStmt->execute();
        $childResult = $childStmt->get_result();
        $childCount = $childResult->fetch_assoc()['count'];
        $childStmt->close();
        
        if ($childCount > 0) {
            $message = 'Không thể xóa danh mục này vì đang có ' . $childCount . ' danh mục con. Vui lòng xóa hoặc chuyển danh mục con trước.';
            $messageType = 'danger';
        } else {
            // Kiểm tra xem danh mục có sản phẩm nào không
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM product_categories WHERE category_id = ?");
            $checkStmt->bind_param("i", $deleteId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $productCount = $checkResult->fetch_assoc()['count'];
            $checkStmt->close();
            
            if ($productCount > 0) {
                $message = 'Không thể xóa danh mục này vì đang có ' . $productCount . ' sản phẩm thuộc danh mục. Vui lòng chuyển sản phẩm sang danh mục khác trước.';
                $messageType = 'danger';
            } else {
                // Lấy ảnh để xóa
                $imgStmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
                $imgStmt->bind_param("i", $deleteId);
                $imgStmt->execute();
                $imgResult = $imgStmt->get_result();
                $oldImage = $imgResult->fetch_assoc()['image'] ?? '';
                $imgStmt->close();
                
                // Xóa danh mục
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->bind_param("i", $deleteId);
                if ($stmt->execute()) {
                    deleteOldImage($oldImage);
                    $message = 'Xóa danh mục thành công!';
                    $messageType = 'success';
                } else {
                    $message = 'Lỗi xóa: ' . $conn->error;
                    $messageType = 'danger';
                }
                $stmt->close();
            }
        }
        // Redirect để tránh submit lại form khi refresh
        header('Location: category_form.php?msg=' . urlencode($message) . '&type=' . $messageType);
        exit;
    }
    
    // Xử lý thêm/sửa danh mục
    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
        $description = $_POST['description'] ?? '';
        $seo_title = trim($_POST['seo_title'] ?? '');
        $seo_description = trim($_POST['seo_description'] ?? '');
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        $is_producer = isset($_POST['is_producer']) ? (int)$_POST['is_producer'] : 0; // THÊM
        
        $image = $_POST['current_image'] ?? '';
        if (isset($_FILES['imageUpload']) && $_FILES['imageUpload']['error'] === UPLOAD_ERR_OK) {
            $uploaded = uploadImage($_FILES['imageUpload'], 'category');
            if ($uploaded) {
                if (!empty($image)) {
                    deleteOldImage($image);
                }
                $image = $uploaded;
            }
        }
        
        // Tạo slug nếu để trống
        if (empty($slug)) {
            $slug = createSlugFromName($name);
        }
        
        // Validate dữ liệu
        $errors = [];
        if (empty($name)) {
            $errors[] = 'Tên danh mục không được để trống';
        }
        
        if ($id > 0 && $parent_id > 0 && isCircularParent($parent_id, $id, $conn)) {
            $errors[] = 'Không thể đặt danh mục con làm danh mục cha (tạo vòng lặp)';
        }
        
        if ($parent_id > 0) {
            $parentLevel = getCategoryLevel($parent_id, $conn);
            if ($parentLevel >= MAX_CATEGORY_LEVEL - 1) {
                $errors[] = 'Không thể thêm danh mục con cho danh mục cấp ' . $parentLevel . ' (đã đạt giới hạn ' . MAX_CATEGORY_LEVEL . ' cấp)';
            }
        }
        
        if (isCategoryNameExists($name, $conn, $id)) {
            $errors[] = 'Tên danh mục "' . htmlspecialchars($name) . '" đã tồn tại. Vui lòng nhập tên khác.';
        }
        
        if (!empty($errors)) {
            $message = implode('<br>', $errors);
            $messageType = 'danger';
        } else {
            // Đảm bảo slug unique
            $slug = getUniqueSlug($slug, $conn, $id);
            
            // Tính level dựa trên parent_id
            $level = calculateNewLevel($parent_id, $conn);
            
            // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
            $conn->begin_transaction();
            
            try {
                if ($id > 0) {
                    // Cập nhật danh mục
                    $stmt = $conn->prepare("UPDATE categories SET name=?, slug=?, parent_id=?, level=?, description=?, image=?, seo_title=?, seo_description=?, status=?, is_producer=? WHERE id=?");
                    $stmt->bind_param("ssiissssiii", $name, $slug, $parent_id, $level, $description, $image, $seo_title, $seo_description, $status, $is_producer, $id);
                    
                    if ($stmt->execute()) {
                        // Cập nhật lại level cho tất cả danh mục con nếu cần
                        updateChildrenLevels($id, $level + 1, $conn);
                        
                        $conn->commit();
                        $message = 'Cập nhật danh mục thành công!';
                        $messageType = 'success';
                        
                        // Redirect về trang danh sách sau khi update thành công
                        header('Location: category_form.php?msg=' . urlencode($message) . '&type=' . $messageType);
                        exit;
                    } else {
                        throw new Exception('Lỗi cập nhật: ' . $conn->error);
                    }
                    $stmt->close();
                } else {
                    // Thêm mới danh mục
                    $stmt = $conn->prepare("INSERT INTO categories (name, slug, parent_id, level, description, image, seo_title, seo_description, status, is_producer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssiissssii", $name, $slug, $parent_id, $level, $description, $image, $seo_title, $seo_description, $status, $is_producer);
                    
                    if ($stmt->execute()) {
                        $newCategoryId = $conn->insert_id;
                        $conn->commit();
                        $message = 'Thêm danh mục thành công!';
                        $messageType = 'success';
                        
                        // Redirect về trang danh sách sau khi thêm thành công
                        header('Location: category_form.php?id=' . $newCategoryId . '&msg=' . urlencode($message) . '&type=' . $messageType);
                        exit;
                    } else {
                        throw new Exception('Lỗi thêm mới: ' . $conn->error);
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                $conn->rollback();
                $message = $e->getMessage();
                $messageType = 'danger';
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = $messageType;
                header('Location: category_form.php');
                exit;
            }
        }
    }
    
    // Xử lý thêm sản phẩm vào danh mục hiện tại
    if ($action === 'add_product_to_category' && isset($_POST['category_id']) && isset($_POST['product_id'])) {
        $categoryId = (int)$_POST['category_id'];
        $productId = (int)$_POST['product_id'];
        
        // Kiểm tra sản phẩm đã có trong danh mục chưa
        $checkStmt = $conn->prepare("SELECT id FROM product_categories WHERE product_id = ? AND category_id = ?");
        $checkStmt->bind_param("ii", $productId, $categoryId);
        $checkStmt->execute();
        $checkStmt->store_result();
        $exists = $checkStmt->num_rows > 0;
        $checkStmt->close();
        
        if ($exists) {
            $message = 'Sản phẩm đã có trong danh mục này!';
            $messageType = 'warning';
        } else {
            $stmt = $conn->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $productId, $categoryId);
            if ($stmt->execute()) {
                $message = 'Đã thêm sản phẩm vào danh mục thành công!';
                $messageType = 'success';
            } else {
                $message = 'Lỗi thêm sản phẩm: ' . $conn->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
        
        header('Location: category_form.php?id=' . $categoryId . '&msg=' . urlencode($message) . '&type=' . $messageType . '#add-product-section');
        exit;
    }
    
    // Xử lý xóa sản phẩm khỏi danh mục hiện tại
    if ($action === 'remove_product_from_category' && isset($_POST['category_id']) && isset($_POST['product_id'])) {
        $categoryId = (int)$_POST['category_id'];
        $productId = (int)$_POST['product_id'];
        
        $stmt = $conn->prepare("DELETE FROM product_categories WHERE product_id = ? AND category_id = ?");
        $stmt->bind_param("ii", $productId, $categoryId);
        if ($stmt->execute()) {
            $message = 'Đã xóa sản phẩm khỏi danh mục!';
            $messageType = 'success';
        } else {
            $message = 'Lỗi xóa sản phẩm: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
        
        header('Location: category_form.php?id=' . $categoryId . '&msg=' . urlencode($message) . '&type=' . $messageType . '#category-products-section');
        exit;
    }
}

// Lấy message từ GET hoặc Session
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $messageType = $_GET['type'] ?? 'info';
} elseif (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'] ?? 'info';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// Lấy dữ liệu nếu có id (chỉnh sửa)
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $category = $result->fetch_assoc();
    }
    $stmt->close();
}

// Lấy danh sách tất cả danh mục (sắp xếp theo cây)
$categoriesResult = $conn->query("SELECT * FROM categories ORDER BY parent_id ASC, id ASC");
$allCategories = $categoriesResult->fetch_all(MYSQLI_ASSOC);

// Hàm đệ quy xây dựng cây danh mục để hiển thị dropdown với giới hạn cấp
function buildCategoryOptions($categories, $parentId = 0, $prefix = '', $excludeId = 0, $currentLevel = 0, $maxLevel = MAX_CATEGORY_LEVEL) {
    $html = '';
    foreach ($categories as $cat) {
        if ($cat['parent_id'] == $parentId && $cat['id'] != $excludeId) {
            $canBeParent = $currentLevel < $maxLevel - 1;
            $levelDisplay = $currentLevel + 1;
            
            global $currentParentId;
            $selected = (isset($currentParentId) && $cat['id'] == $currentParentId) ? ' selected' : '';
            
            $html .= '<option value="' . $cat['id'] . '" data-level="' . $levelDisplay . '"' . $selected . '>' . 
                     $prefix . htmlspecialchars($cat['name']) . 
                     ' (Cấp ' . $levelDisplay . ')' .
                     (!$canBeParent ? ' 🔒' : '') .
                     '</option>';
            
            if ($canBeParent) {
                $html .= buildCategoryOptions($categories, $cat['id'], $prefix . '&nbsp;&nbsp;&nbsp;├─ ', $excludeId, $currentLevel + 1, $maxLevel);
            }
        }
    }
    return $html;
}

// Lấy danh sách sản phẩm theo danh mục (có kèm ảnh từ product_images)
function getProductsByCategory($categoryId, $conn) {
    if ($categoryId == 0) return [];
    
    $stmt = $conn->prepare("
        SELECT p.*, pc.category_id 
        FROM products p 
        INNER JOIN product_categories pc ON p.id = pc.product_id 
        WHERE pc.category_id = ? 
        ORDER BY p.id DESC
    ");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Lấy ảnh cho từng sản phẩm
    foreach ($products as &$product) {
        $product['image_url'] = getProductImage($product['id'], $conn);
        $product['images'] = getProductImages($product['id'], $conn, 3);
    }
    
    return $products;
}

// Lấy danh sách sản phẩm chưa thuộc danh mục hiện tại
function getProductsNotInCategory($conn, $categoryId, $search = '', $page = 1, $limit = 20) {
    $offset = ($page - 1) * $limit;
    $where = "p.id NOT IN (SELECT product_id FROM product_categories WHERE category_id = ?)";
    $params = [$categoryId];
    $types = "i";
    
    if (!empty($search)) {
        $where .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.slug LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        $types .= "sss";
    }
    
    // Đếm tổng số
    $countSql = "SELECT COUNT(*) as total FROM products p WHERE $where";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // Lấy dữ liệu
    $sql = "SELECT p.* 
            FROM products p 
            WHERE $where
            ORDER BY p.id DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $params[] = $limit;
    $params[] = $offset;
    $stmt->bind_param($types . "ii", ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Lấy ảnh cho từng sản phẩm
    foreach ($products as &$product) {
        $product['image_url'] = getProductImage($product['id'], $conn);
        $product['images'] = getProductImages($product['id'], $conn, 3);
    }
    
    return [
        'data' => $products,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ];
}

// Xử lý tìm kiếm và phân trang
$productSearchQuery = isset($_GET['search_product']) ? trim($_GET['search_product']) : '';
$productPage = isset($_GET['product_page']) ? (int)$_GET['product_page'] : 1;
if ($productPage < 1) $productPage = 1;

// Lấy sản phẩm theo danh mục đang chỉnh sửa
$categoryProducts = [];
if ($editId > 0) {
    $categoryProducts = getProductsByCategory($editId, $conn);
}

// Lấy sản phẩm chưa thuộc danh mục
$availableProductsData = [];
if ($editId > 0) {
    $availableProductsData = getProductsNotInCategory($conn, $editId, $productSearchQuery, $productPage);
}

// Lấy parent_id hiện tại của danh mục đang chỉnh sửa
$currentParentId = $category['parent_id'] ?? 0;
$currentLevel = $category['level'] ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Ruouchat Admin | Quản lý Danh mục</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
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

        /* ===== CUSTOM STYLES (co nhỏ) ===== */
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
        .page-header .btn-secondary {
            border-radius: 10px !important;
            font-size: 0.85rem;
            padding: 6px 16px;
        }

        .card-modern {
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.04);
            padding: 16px 20px;
            margin-bottom: 16px;
            transition: box-shadow 0.2s;
        }
        .card-modern:hover {
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
        }
        .card-modern h3 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            border-left: 3px solid #eab308;
            padding-left: 12px;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.75rem;
            color: #334155;
            margin-bottom: 2px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.15);
        }
        .form-control-lg {
            font-size: 1rem;
            padding: 8px 14px;
        }

        /* Tất cả nút border-radius: 10px */
        .btn, .btn-primary, .btn-secondary, .btn-save,
        .btn-remove, .btn-add-to-category, .btn-sm-custom,
        .btn-edit, .btn-delete, .btn-action,
        .btn-outline-primary, .btn-outline-secondary {
            border-radius: 10px !important;
        }

        .btn-save {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            padding: 10px 20px;
            font-weight: 700;
            color: white;
            box-shadow: 0 4px 10px rgba(5,150,105,0.3);
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5,150,105,0.4);
            background: linear-gradient(135deg, #059669, #047857);
        }
        .btn-secondary {
            background: #64748b;
            border: none;
            padding: 6px 16px;
            color: white;
            transition: background 0.2s;
            font-size: 0.85rem;
        }
        .btn-secondary:hover {
            background: #475569;
        }
        .btn-remove {
            background: #ef4444;
            color: white;
            padding: 4px 12px;
            border: none;
            font-size: 0.75rem;
            transition: background 0.2s;
        }
        .btn-remove:hover {
            background: #dc2626;
        }
        .btn-sm-custom {
            padding: 4px 12px;
            font-size: 0.7rem;
        }
        .btn-add-to-category {
            background: #10b981;
            color: white;
            border: none;
            padding: 4px 12px;
            font-size: 0.75rem;
            transition: background 0.2s;
        }
        .btn-add-to-category:hover {
            background: #059669;
        }

        .alert {
            border-radius: 12px;
            border: none;
            font-size: 0.9rem;
            padding: 12px 16px;
        }

        .level-info {
            background: #f1f5f9;
            border-radius: 10px;
            padding: 8px 14px;
            margin-bottom: 12px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .level-info .badge {
            font-size: 0.7rem;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .level-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 3px;
            margin-right: 4px;
        }
        .level-0 { background: #3b82f6; }
        .level-1 { background: #22c55e; }
        .level-2 { background: #eab308; }
        .level-3 { background: #ef4444; }
        .level-4 { background: #8b5cf6; }

        .parent-warning {
            background: #fef3c7;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.8rem;
            color: #92400e;
            margin-top: 6px;
            display: none;
        }
        .parent-warning.show { display: block; }

        .char-count {
            font-size: 0.7rem;
            color: #6b7280;
            margin-top: 2px;
        }
        .char-count .text-danger { color: #dc2626 !important; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-draft {
            background: #fef3c7;
            color: #92400e;
        }
        .status-coming_soon {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-discontinued {
            background: #f3e8ff;
            color: #6b21a8;
        }
        .status-visible {
            background: #d1fae5;
            color: #065f46;
        }
        .status-hidden {
            background: #fee2e2;
            color: #991b1b;
        }

        .category-image {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .product-thumbnail {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .img-placeholder {
            width: 40px;
            height: 40px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            font-size: 1rem;
            border: 2px dashed #d1d5db;
        }

        .table-custom {
            border-radius: 12px;
            overflow: hidden;
        }
        .table-custom th {
            background: #0f172a;
            color: white;
            padding: 10px 10px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .table-custom td {
            vertical-align: middle;
            padding: 8px 10px;
            font-size: 0.78rem;
        }
        .table-custom tr:hover {
            background: #fefce8;
        }

        .product-price {
            font-weight: 600;
            color: #0f172a;
        }

        .producer-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        .producer-badge i { margin-right: 4px; }

        .product-count-badge {
            background: #eab308;
            color: white;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .search-box {
            background: white;
            border-radius: 10px;
            padding: 3px 3px 3px 16px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }
        .search-box:focus-within {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.15);
        }
        .search-box input {
            border: none;
            outline: none;
            background: transparent;
            padding: 6px 0;
            flex: 1;
            font-size: 0.85rem;
        }
        .search-box button {
            background: #f59e0b;
            border: none;
            color: white;
            padding: 6px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.8rem;
            transition: background 0.2s;
        }
        .search-box button:hover {
            background: #d97706;
        }

        .pagination-custom .page-link {
            border-radius: 10px !important;
            border: none;
            color: #0f172a;
            padding: 6px 14px;
            margin: 0 2px;
            background: #f1f5f9;
            font-size: 0.8rem;
        }
        .pagination-custom .page-link:hover {
            background: #eab308;
            color: white;
        }
        .pagination-custom .page-item.active .page-link {
            background: #0f172a;
            color: white;
        }

        .add-product-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px 16px;
            margin-top: 12px;
            border: 1px dashed #cbd5e1;
        }

        .product-action-cell {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            align-items: center;
        }

        .table-header-icon {
            margin-right: 4px;
        }

        .empty-products {
            text-align: center;
            padding: 30px 20px;
            color: #6b7280;
        }
        .empty-products i {
            font-size: 36px;
            margin-bottom: 12px;
            display: block;
            color: #d1d5db;
        }
        .empty-products h5 {
            color: #374151;
            margin-bottom: 6px;
            font-size: 1rem;
        }
        .empty-products p {
            font-size: 0.85rem;
        }

        .switch-producer {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }
        .switch-producer input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider-producer {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #cbd5e1;
            transition: .4s;
            border-radius: 34px;
        }
        .slider-producer:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            transition: .4s;
            border-radius: 50%;
        }
        .switch-producer input:checked + .slider-producer {
            background: #eab308;
        }
        .switch-producer input:checked + .slider-producer:before {
            transform: translateX(20px);
        }
        .producer-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        .producer-label .label-text {
            font-weight: 500;
            font-size: 0.85rem;
        }
        .producer-label .label-hint {
            font-size: 0.7rem;
            color: #94a3b8;
            display: block;
        }

        .table-product-image {
            width: 50px;
            text-align: center;
        }

        /* Responsive table */
        @media (max-width: 768px) {
            .table-custom th, .table-custom td {
                padding: 6px 6px;
                font-size: 0.7rem;
            }
            .product-thumbnail, .img-placeholder {
                width: 32px;
                height: 32px;
            }
            .card-modern {
                padding: 12px 14px;
            }
            .card-modern h3 {
                font-size: 0.95rem;
            }
            .page-header h2 {
                font-size: 1.2rem;
            }
            .search-box input { font-size: 0.8rem; }
            .search-box button { font-size: 0.75rem; padding: 4px 12px; }
            .btn-save { font-size: 0.8rem; padding: 8px 16px; }
        }

        @media (max-width: 480px) {
            .table-custom th, .table-custom td {
                padding: 4px 4px;
                font-size: 0.6rem;
            }
            .product-thumbnail, .img-placeholder {
                width: 28px;
                height: 28px;
            }
            .card-modern {
                padding: 8px 10px;
            }
            .btn-sm-custom, .btn-add-to-category, .btn-remove {
                font-size: 0.6rem;
                padding: 2px 8px;
            }
            .page-header h2 {
                font-size: 1rem;
                padding-left: 10px;
            }
            .level-info {
                font-size: 0.7rem;
                padding: 6px 10px;
                gap: 6px;
            }
            .level-info .badge {
                font-size: 0.6rem;
                padding: 2px 8px;
            }
        }

        /* Summernote override */
        .note-editor {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .note-toolbar {
            background: #f8fafc;
            border-radius: 12px 12px 0 0;
        }
        .note-editor.note-frame.fullscreen {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 1050 !important;
            background: white !important;
            margin: 0 !important;
            border-radius: 0 !important;
        }
        .note-editor.note-frame.fullscreen .note-editable {
            background: white !important;
            padding: 20px !important;
        }
        .note-editor.note-frame.fullscreen .note-toolbar {
            border-radius: 0 !important;
        }
        body.fullscreen-active {
            overflow: hidden;
        }
    </style>
</head>
<body>
<div class="app-wrapper">
    <!-- SIDEBAR (giống product_form) -->
    <div class="sidebar" id="sidebar">
        <div class="brand">Ruouchat Admin</div>
        <div class="d-flex flex-column">
            <a href="index.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="products.html"><i class="fas fa-wine-bottle"></i> Sản phẩm</a>
            <a href="flash_sales.html"><i class="fas fa-bolt"></i> Flash sale</a>
            <a href="categories.html" class="active"><i class="fas fa-folder"></i> Danh mục</a>
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
            <a href="index.php"><i class="fas fa-list"></i> Menu</a>
            <a href="pages.php"><i class="fas fa-file-alt"></i> Trang mới</a>
            <a href="banners.html"><i class="fas fa-image"></i> Banner</a>
            <a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </div>

    <!-- OVERLAY mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- NÚT TOGGLE mobile -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle"><i class="fas fa-bars"></i> Menu</button>

    <!-- NỘI DUNG CHÍNH -->
    <div class="main-content">
        <div class="content">
            <div class="page-header">
                <h2>Quản lý danh mục</h2>
                <a href="categories.html" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show shadow-sm border-0" role="alert">
                    <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : ($messageType == 'warning' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle'); ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Form thêm/sửa danh mục -->
            <div class="card-modern">
                <h3><?php echo $category ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới'; ?></h3>

                <div class="level-info">
                    <i class="fas fa-layer-group text-primary"></i>
                    <span><strong>Giới hạn cấp độ:</strong> Tối đa <?php echo MAX_CATEGORY_LEVEL; ?> cấp</span>
                    <span class="badge bg-primary">Cấp 0 <span class="level-indicator level-0"></span></span>
                    <span class="badge bg-success">Cấp 1 <span class="level-indicator level-1"></span></span>
                    <span class="badge bg-warning">Cấp 2 <span class="level-indicator level-2"></span></span>
                    <span class="badge bg-danger">Cấp 3 <span class="level-indicator level-3"></span></span>
                    <?php if ($category): ?>
                        <span class="badge bg-secondary ms-2">Danh mục hiện tại: <strong>Cấp <?php echo $category['level']; ?></strong></span>
                    <?php endif; ?>
                </div>

                <form method="post" enctype="multipart/form-data" id="categoryForm">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo $category['id'] ?? ''; ?>">
                    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($category['image'] ?? ''); ?>">

                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="mb-2">
                                <label class="form-label">
                                    <i class="fas fa-tag text-danger me-1"></i>
                                    Tên danh mục <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name" class="form-control form-control-lg" 
                                       required value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>"
                                       placeholder="Nhập tên danh mục">
                                <small class="text-muted">Tên danh mục phải là duy nhất, không được trùng với danh mục khác</small>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">
                                    <i class="fas fa-sitemap text-info me-1"></i>
                                    Danh mục cha
                                </label>
                                <select name="parent_id" id="parent_id" class="form-select">
                                    <option value="0">-- Không có (danh mục gốc - Cấp 0) --</option>
                                    <?php 
                                    $excludeId = $category['id'] ?? 0;
                                    echo buildCategoryOptions($allCategories, 0, '', $excludeId, 0, MAX_CATEGORY_LEVEL); 
                                    ?>
                                </select>
                                <div id="parentWarning" class="parent-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <span id="parentWarningText"></span>
                                </div>
                                <small class="text-muted">
                                    Chọn danh mục cha để tạo cấp con. 
                                    <strong>Lưu ý:</strong> Danh mục cấp <?php echo MAX_CATEGORY_LEVEL - 1; ?> không thể có danh mục con.
                                    <?php if ($category): ?>
                                        <br>Danh mục hiện tại đang ở <strong>Cấp <?php echo $category['level']; ?></strong>
                                    <?php endif; ?>
                                </small>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">
                                    <i class="fas fa-link text-info me-1"></i>
                                    Slug (URL)
                                </label>
                                <input type="text" name="slug" id="slug" class="form-control" 
                                       placeholder="Để trống sẽ tự động tạo từ tên danh mục"
                                       value="<?php echo htmlspecialchars($category['slug'] ?? ''); ?>">
                                <small class="text-muted">Slug là phiên bản thân thiện với URL, chỉ chứa chữ cái thường, số và dấu gạch ngang</small>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">
                                    <i class="fas fa-align-left me-1"></i>
                                    Mô tả
                                </label>
                                <textarea name="description" id="description" class="form-control" rows="4" placeholder="Mô tả chi tiết về danh mục..."><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">
                                    <i class="fab fa-searchengin text-warning me-1"></i>
                                    SEO Title
                                </label>
                                <input type="text" name="seo_title" id="seo_title" class="form-control" maxlength="70" 
                                       value="<?php echo htmlspecialchars($category['seo_title'] ?? ''); ?>"
                                       placeholder="Tiêu đề SEO (tối đa 70 ký tự)">
                                <div class="char-count" id="seo_title_count">0/70 ký tự</div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">
                                    <i class="fas fa-description text-warning me-1"></i>
                                    SEO Description
                                </label>
                                <textarea name="seo_description" id="seo_description" class="form-control" rows="2" maxlength="300"
                                          placeholder="Mô tả SEO (tối đa 300 ký tự)"><?php echo htmlspecialchars($category['seo_description'] ?? ''); ?></textarea>
                                <div class="char-count" id="seo_desc_count">0/300 ký tự</div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="mb-2">
                                <label class="form-label">
                                    <i class="fas fa-image text-success me-1"></i>
                                    Ảnh đại diện
                                </label>
                                <input type="file" name="imageUpload" id="imageUpload" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml">
                                <small class="text-muted">Hỗ trợ: JPG, PNG, GIF, WEBP, SVG</small>
                                <div class="mt-2 text-center">
                                    <img id="imagePreview" 
                                         src="<?php 
                                            if (!empty($category['image'])) {
                                                echo '../' . htmlspecialchars($category['image']);
                                            } else {
                                                echo 'https://placehold.co/400x300/f1f5f9/94a3b8?text=Ch%E1%BB%8Dn+%E1%BA%A3nh';
                                            }
                                         ?>" 
                                         style="max-width:100%; max-height:160px; border-radius:10px; object-fit:cover; border:1px solid #e2e8f0;">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">
                                    <i class="fas fa-eye-slash me-1"></i>
                                    Trạng thái
                                </label>
                                <select name="status" class="form-select">
                                    <option value="1" <?php echo (isset($category['status']) && $category['status'] == 1) ? 'selected' : ''; ?>>
                                        ✅ Hiển thị
                                    </option>
                                    <option value="0" <?php echo (isset($category['status']) && $category['status'] == 0) ? 'selected' : ''; ?>>
                                        🔒 Ẩn
                                    </option>
                                </select>
                            </div>

                            <!-- THÊM: Checkbox Đánh dấu là Nhà sản xuất -->
                            <div class="mb-2">
                                <label class="form-label">
                                    <i class="fas fa-building text-warning me-1"></i>
                                    Nhà sản xuất
                                </label>
                                <div class="producer-label">
                                    <label class="switch-producer">
                                        <input type="checkbox" name="is_producer" id="is_producer" value="1" 
                                               <?php echo (isset($category['is_producer']) && $category['is_producer'] == 1) ? 'checked' : ''; ?>>
                                        <span class="slider-producer"></span>
                                    </label>
                                    <div>
                                        <span class="label-text">Đánh dấu là nhà sản xuất</span>
                                        <span class="label-hint">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Danh mục này sẽ hiển thị trong trang quản lý nhà sản xuất
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-save w-100 py-2" id="submitBtn">
                                <i class="fas fa-save me-2"></i>
                                <?php echo $category ? 'Cập nhật danh mục' : 'Thêm danh mục'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Danh sách sản phẩm trong danh mục đang chỉnh sửa -->
            <?php if ($category && $editId > 0): ?>
            <div class="card-modern" id="category-products-section">
                <h3 class="mb-2">
                    <i class="fas fa-boxes me-2"></i>
                    Sản phẩm trong danh mục: 
                    <span class="text-warning"><?php echo htmlspecialchars($category['name']); ?></span>
                    <span class="product-count-badge ms-2"><?php echo count($categoryProducts); ?></span>
                    <?php if (isset($category['is_producer']) && $category['is_producer'] == 1): ?>
                        <span class="producer-badge ms-2"><i class="fas fa-building"></i> Nhà sản xuất</span>
                    <?php endif; ?>
                </h3>

                <?php if (empty($categoryProducts)): ?>
                    <div class="empty-products">
                        <i class="fas fa-box-open"></i>
                        <h5>Chưa có sản phẩm nào trong danh mục này</h5>
                        <p class="text-muted">Hãy thêm sản phẩm bằng cách gán sản phẩm hiện có vào danh mục này</p>
                        <a href="#add-product-section" class="btn btn-save btn-sm">
                            <i class="fas fa-plus me-1"></i> Gán sản phẩm hiện có
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px;">ID</th>
                                    <th style="width:55px;">Ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>SKU</th>
                                    <th>Giá</th>
                                    <th>Trạng thái</th>
                                    <th style="width:100px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoryProducts as $product): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $product['id']; ?></td>
                                        <td class="table-product-image">
                                            <?php if (!empty($product['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                     class="product-thumbnail" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                     onerror="this.onerror=null; this.src='https://placehold.co/100x100/e2e8f0/94a3b8?text=No+Image';">
                                            <?php else: ?>
                                                <div class="img-placeholder">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></code></td>
                                        <td class="product-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</td>
                                        <td>
                                            <?php 
                                            $statusMap = [
                                                'visible' => 'Hiển thị',
                                                'hidden' => 'Ẩn',
                                                'coming_soon' => 'Sắp ra mắt',
                                                'discontinued' => 'Ngừng bán',
                                                'draft' => 'Nháp'
                                            ];
                                            $statusClass = 'status-' . ($product['display_status'] ?? 'draft');
                                            $statusText = $statusMap[$product['display_status'] ?? 'draft'] ?? 'Nháp';
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này khỏi danh mục?');">
                                                <input type="hidden" name="action" value="remove_product_from_category">
                                                <input type="hidden" name="category_id" value="<?php echo $editId; ?>">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-remove btn-sm-custom" title="Xóa khỏi danh mục">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Phần thêm sản phẩm vào danh mục -->
            <?php if ($category && $editId > 0): ?>
            <div class="card-modern" id="add-product-section">
                <h3>
                    <i class="fas fa-plus-circle me-2 text-success"></i>
                    Thêm sản phẩm vào danh mục
                </h3>

                <div class="add-product-section">
                    <form method="get" action="" class="search-box w-100 mb-2" onsubmit="return true;">
                        <input type="hidden" name="id" value="<?php echo $editId; ?>">
                        <input type="hidden" name="product_page" value="1">
                        <input type="text" name="search_product" placeholder="Tìm kiếm sản phẩm để thêm vào danh mục..." 
                               value="<?php echo htmlspecialchars($productSearchQuery); ?>">
                        <button type="submit"><i class="fas fa-search me-1"></i> Tìm</button>
                        <?php if (!empty($productSearchQuery)): ?>
                            <a href="?id=<?php echo $editId; ?>#add-product-section" class="btn btn-link text-muted ms-2" style="padding: 4px 12px; text-decoration: none; font-size:0.8rem;">
                                <i class="fas fa-times"></i> Xóa
                            </a>
                        <?php endif; ?>
                    </form>

                    <?php if (empty($availableProductsData['data'])): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                            <p>Tất cả sản phẩm đã được thêm vào danh mục này hoặc không có sản phẩm nào trong hệ thống.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">ID</th>
                                        <th style="width:55px;">Ảnh</th>
                                        <th>Tên sản phẩm</th>
                                        <th>SKU</th>
                                        <th>Giá</th>
                                        <th>Trạng thái</th>
                                        <th style="width:100px;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($availableProductsData['data'] as $product): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $product['id']; ?></td>
                                            <td class="table-product-image">
                                                <?php if (!empty($product['image_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                         class="product-thumbnail" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                         onerror="this.onerror=null; this.src='https://placehold.co/100x100/e2e8f0/94a3b8?text=No+Image';">
                                                <?php else: ?>
                                                    <div class="img-placeholder">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></code></td>
                                            <td class="product-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</td>
                                            <td>
                                                <?php 
                                                $statusMap = [
                                                    'visible' => 'Hiển thị',
                                                    'hidden' => 'Ẩn',
                                                    'coming_soon' => 'Sắp ra mắt',
                                                    'discontinued' => 'Ngừng bán',
                                                    'draft' => 'Nháp'
                                                ];
                                                $statusClass = 'status-' . ($product['display_status'] ?? 'draft');
                                                $statusText = $statusMap[$product['display_status'] ?? 'draft'] ?? 'Nháp';
                                                ?>
                                                <span class="status-badge <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="add_product_to_category">
                                                    <input type="hidden" name="category_id" value="<?php echo $editId; ?>">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" class="btn btn-add-to-category btn-sm-custom" title="Thêm vào danh mục">
                                                        <i class="fas fa-plus"></i> Thêm
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($availableProductsData['total_pages'] > 1): ?>
                            <nav aria-label="Page navigation" class="mt-2">
                                <ul class="pagination pagination-custom justify-content-center">
                                    <li class="page-item <?php echo ($productPage <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?id=<?php echo $editId; ?>&product_page=<?php echo $productPage - 1; ?>&search_product=<?php echo urlencode($productSearchQuery); ?>#add-product-section">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php for ($i = 1; $i <= $availableProductsData['total_pages']; $i++): ?>
                                        <li class="page-item <?php echo ($i == $productPage) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?id=<?php echo $editId; ?>&product_page=<?php echo $i; ?>&search_product=<?php echo urlencode($productSearchQuery); ?>#add-product-section">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo ($productPage >= $availableProductsData['total_pages']) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?id=<?php echo $editId; ?>&product_page=<?php echo $productPage + 1; ?>&search_product=<?php echo urlencode($productSearchQuery); ?>#add-product-section">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // ========== TOGGLE SIDEBAR MOBILE ==========
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
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (sidebar.classList.contains('open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });
    }

    overlay.addEventListener('click', closeMobileMenu);

    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
    });

    // ========== CÁC HÀM XỬ LÝ ==========
    $(document).ready(function() {
        // Khởi tạo Summernote
        $('#description').summernote({
            height: 250,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onImageUpload: function(files) {
                    for (let file of files) {
                        let formData = new FormData();
                        formData.append('upload', file);
                        formData.append('type', 'category');
                        fetch('../api/upload_image.php', {
                            method: 'POST',
                            headers: {
                                'X-Admin-Secret': 'RuouchatAdmin2024!'
                            },
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.uploaded) {
                                $('#description').summernote('insertImage', data.url);
                            } else {
                                alert('Upload ảnh thất bại: ' + (data.error?.message || 'Unknown error'));
                            }
                        })
                        .catch(err => alert('Lỗi upload: ' + err.message));
                    }
                },
                onFullscreen: function(isFullscreen) {
                    if (isFullscreen) {
                        $('body').addClass('fullscreen-active');
                    } else {
                        $('body').removeClass('fullscreen-active');
                    }
                },
                onChange: function() {
                    markDirty();
                }
            }
        });

        // Đếm ký tự SEO
        updateSeoCounters();
        $('#seo_title').on('input', function() {
            if (this.value.length > 70) this.value = this.value.substring(0, 70);
            updateSeoCounters();
        });
        $('#seo_description').on('input', function() {
            if (this.value.length > 300) this.value = this.value.substring(0, 300);
            updateSeoCounters();
        });

        // Xử lý submit form - disable nút submit để tránh submit nhiều lần
        $('#categoryForm').on('submit', function() {
            $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...');
        });
    });

    function updateSeoCounters() {
        const titleLen = $('#seo_title').val().length;
        const descLen = $('#seo_description').val().length;
        $('#seo_title_count').html(`${titleLen}/70 ký tự ${titleLen > 70 ? '<span class="text-danger">(Vượt quá giới hạn)</span>' : ''}`);
        $('#seo_desc_count').html(`${descLen}/300 ký tự ${descLen > 300 ? '<span class="text-danger">(Vượt quá giới hạn)</span>' : ''}`);
    }

    let isDirty = false;
    function markDirty() { 
        isDirty = true; 
    }

    // Tự động tạo slug từ tên
    document.getElementById('name').addEventListener('blur', function() {
        let slugField = document.getElementById('slug');
        if (slugField.value.trim() === '') {
            let name = this.value;
            let slug = name.toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[đĐ]/g, 'd')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            slugField.value = slug;
        }
    });

    // Preview ảnh khi chọn
    document.getElementById('imageUpload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('imagePreview').src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Xác nhận trước khi submit form thêm/sửa
    document.getElementById('categoryForm').addEventListener('submit', function(e) {
        const nameField = document.getElementById('name');
        const name = nameField.value.trim();
        if (!name) {
            e.preventDefault();
            alert('Vui lòng nhập tên danh mục');
            nameField.focus();
            return false;
        }
    });

    // Cảnh báo khi rời trang chưa lưu
    window.addEventListener('beforeunload', function(e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = 'Bạn có thay đổi chưa lưu. Bạn có chắc muốn rời khỏi trang?';
            return e.returnValue;
        }
    });

    // Reset dirty flag khi submit thành công
    document.getElementById('categoryForm').addEventListener('submit', function() {
        isDirty = false;
    });

    // Xử lý logout
    document.getElementById('logoutBtn').addEventListener('click', async (e) => {
        e.preventDefault();
        if (confirm('Bạn có chắc muốn đăng xuất?')) {
            try {
                await fetch('../api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' }),
                    credentials: 'include'
                });
            } catch(e) {
                console.warn(e);
            }
            localStorage.removeItem('admin');
            window.location.href = 'login.html';
        }
    });

    // Kiểm tra cấp độ khi chọn danh mục cha
    document.getElementById('parent_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const level = selectedOption.dataset.level;
        const warningEl = document.getElementById('parentWarning');
        const warningText = document.getElementById('parentWarningText');

        if (level && parseInt(level) >= <?php echo MAX_CATEGORY_LEVEL - 1; ?>) {
            warningEl.classList.add('show');
            warningText.textContent = 'Danh mục này đã ở cấp ' + level + ' (cấp tối đa), không thể thêm danh mục con!';
        } else if (level) {
            warningEl.classList.add('show');
            warningText.textContent = 'Danh mục con sẽ ở cấp ' + (parseInt(level) + 1) + ' (tối đa ' + <?php echo MAX_CATEGORY_LEVEL; ?> + ' cấp)';
        } else {
            warningEl.classList.remove('show');
        }
    });

    // Kích hoạt sự kiện change ban đầu
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('parent_id');
        if (select) {
            const event = new Event('change');
            select.dispatchEvent(event);
        }
    });
</script>
</body>
</html>