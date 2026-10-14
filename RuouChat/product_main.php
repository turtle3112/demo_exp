<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ========== KẾT NỐI DATABASE ==========
require_once 'api/config.php';

// ========== KIỂM TRA CỘT TỒN TẠI ==========
function columnExists($table, $column) {
    global $conn;
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

$hasSlug = columnExists('categories', 'slug');

// ========== LẤY THÔNG TIN TỪ URL ==========
$categoryId = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$categorySlug = isset($_GET['category_slug']) ? trim($_GET['category_slug']) : '';
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$brandFilter = isset($_GET['brand']) ? trim($_GET['brand']) : ''; // THÊM DÒNG NÀY
$productType = isset($_GET['product_type']) ? (int)$_GET['product_type'] : 0;

// ===== XÁC ĐỊNH DANH MỤC CHA MẶC ĐỊNH =====
$defaultCategoryId = 16;
$defaultCategorySlug = 'ruou-vang';
$parentCategoryId = 0;

// Nếu có brandFilter, ưu tiên hiển thị danh mục rượu vang (product_type=1)
if (!empty($brandFilter) && $categoryId == 0) {
    $defaultCategoryId = 16;
    $defaultCategorySlug = 'ruou-vang';
}

// Kiểm tra URL để xác định danh mục mặc định
$requestUri = $_SERVER['REQUEST_URI'];
if (strpos($requestUri, '/ruou-manh') === 0 && $categoryId == 0 && empty($categorySlug) && empty($brandFilter)) {
    $defaultCategoryId = 17;
    $defaultCategorySlug = 'ruou-manh';
    $parentCategoryId = 17;
}

// ===== XỬ LÝ CATEGORY_SLUG TỪ SEO URL =====
if (!empty($categorySlug) && $categoryId == 0) {
    if ($hasSlug) {
        $stmt = $conn->prepare("SELECT id, name, description, meta_description, image, parent_id FROM categories WHERE slug = ? AND status = 1");
        if ($stmt) {
            $stmt->bind_param("s", $categorySlug);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $catData = $result->fetch_assoc();
                $categoryId = (int)$catData['id'];
                $parentCategoryId = (int)$catData['parent_id'];
            }
            $stmt->close();
        }
    }
}

// Nếu không có categoryId, sử dụng mặc định
if ($categoryId == 0 && empty($searchKeyword) && empty($brandFilter)) {
    $categoryId = $defaultCategoryId;
    if ($defaultCategoryId == 17) {
        $parentCategoryId = 17;
    }
}

// ===== XÁC ĐỊNH THÔNG TIN DANH MỤC =====
$category = null;
$showDefaultDesc = true;
$pageTitle = 'Rượu Vang Cao Cấp | RUOUCHAT.vn';
$breadcrumbName = 'Sản phẩm';
$baseCategorySlug = $defaultCategorySlug;
$isWine = true;

if ($categoryId > 0) {
    $fields = "id, name, description, meta_description, image, parent_id";
    if ($hasSlug) $fields .= ", slug";
    
    $stmt = $conn->prepare("SELECT $fields FROM categories WHERE id = ? AND status = 1");
    if ($stmt) {
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $category = $result->fetch_assoc();
            $showDefaultDesc = false;
            $pageTitle ='RUOUCHAT.vn | ' . htmlspecialchars($category['name']) ;
            $breadcrumbName = $category['name'];
            $baseCategorySlug = $category['slug'] ?? $defaultCategorySlug;
            
            $parentId = (int)$category['parent_id'];
            if ($parentId == 17 || $categoryId == 17) {
                $isWine = false;
            } elseif ($parentId == 16 || $categoryId == 16 || $parentId == 0) {
                $isWine = true;
            }
        }
        $stmt->close();
    }
}

// Nếu lọc theo brand, cập nhật tiêu đề và breadcrumb
if (!empty($brandFilter)) {
    $pageTitle = htmlspecialchars($brandFilter) . ' | Rượu Vang Cao Cấp | RUOUCHAT.vn';
    $breadcrumbName = 'Thương hiệu: ' . htmlspecialchars($brandFilter);
    $showDefaultDesc = true;
}

// ===== NẾU CÓ TỪ KHÓA TÌM KIẾM, CẬP NHẬT TIÊU ĐỀ =====
if (!empty($searchKeyword)) {
    $pageTitle = 'Tìm kiếm: ' . htmlspecialchars($searchKeyword) . ' | RUOUCHAT.vn';
    $breadcrumbName = 'Tìm kiếm: ' . htmlspecialchars($searchKeyword);
}

// ===== LẤY DANH SÁCH TẤT CẢ DANH MỤC (để map ID -> tên) =====
$categoryMap = [];
$catAllResult = $conn->query("SELECT id, name FROM categories WHERE status = 1");
if ($catAllResult) {
    while ($row = $catAllResult->fetch_assoc()) {
        $categoryMap[$row['id']] = $row['name'];
    }
}

// ===== HÀM HỖ TRỢ =====
function fixDescriptionImages($content) {
    $content = str_replace('../uploads/', 'uploads/', $content);
    return $content;
}

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = preg_replace('/^-+|-+$/', '', $text);
    return $text;
}

function getCategoryLink($catId) {
    global $conn, $hasSlug;
    if ($hasSlug) {
        $stmt = $conn->prepare("SELECT slug FROM categories WHERE id = ? AND status = 1");
        if ($stmt) {
            $stmt->bind_param("i", $catId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stmt->close();
                return '/' . $row['slug'];
            }
            $stmt->close();
        }
    }
    return 'product_main.php?cat_id=' . (int)$catId;
}

// ========== LẤY MENU ==========
$menuItems = [];
$menuResult = $conn->query("SELECT * FROM menu_items ORDER BY parent_id, sort_order, id");
if ($menuResult && $menuResult->num_rows > 0) {
    while ($row = $menuResult->fetch_assoc()) {
        $menuItems[] = $row;
    }
}

// ========== LẤY DANH SÁCH DANH MỤC CON ==========
$childCategories = [];
if ($categoryId > 0) {
    $stmt = $conn->prepare("SELECT id, name, slug FROM categories WHERE parent_id = ? AND status = 1 ORDER BY id ASC");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $childCategories[] = $row;
    }
    $stmt->close();
}

// ========== LẤY DANH SÁCH BỘ LỌC ==========
$countries = [];
$countryResult = $conn->query("SELECT DISTINCT country FROM products WHERE country IS NOT NULL AND country != '' AND status = 1 AND display_status IN ('visible', 'coming_soon') ORDER BY country ASC");
if ($countryResult && $countryResult->num_rows > 0) {
    while ($row = $countryResult->fetch_assoc()) {
        $countries[] = $row['country'];
    }
}

$regions = [];
$regionResult = $conn->query("SELECT DISTINCT region FROM products WHERE region IS NOT NULL AND region != '' AND status = 1 AND display_status IN ('visible', 'coming_soon') ORDER BY region ASC");
if ($regionResult && $regionResult->num_rows > 0) {
    while ($row = $regionResult->fetch_assoc()) {
        $regions[] = $row['region'];
    }
}

$grapeTypes = [];
$grapeResult = $conn->query("SELECT DISTINCT grape_type FROM products WHERE grape_type IS NOT NULL AND grape_type != '' AND status = 1 AND display_status IN ('visible', 'coming_soon') ORDER BY grape_type ASC");
if ($grapeResult && $grapeResult->num_rows > 0) {
    while ($row = $grapeResult->fetch_assoc()) {
        $grapeTypes[] = $row['grape_type'];
    }
}

$spiritTypes = [];
$spiritResult = $conn->query("SELECT DISTINCT spirit_type FROM products WHERE spirit_type IS NOT NULL AND spirit_type != '' AND status = 1 AND display_status IN ('visible', 'coming_soon') ORDER BY spirit_type ASC");
if ($spiritResult && $spiritResult->num_rows > 0) {
    while ($row = $spiritResult->fetch_assoc()) {
        $spiritTypes[] = $row['spirit_type'];
    }
}

$brands = [];
$brandResult = $conn->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' AND status = 1 AND display_status IN ('visible', 'coming_soon') ORDER BY brand ASC");
if ($brandResult && $brandResult->num_rows > 0) {
    while ($row = $brandResult->fetch_assoc()) {
        $brands[] = $row['brand'];
    }
}

// ========== LẤY DANH MỤC RƯỢU VANG (parent_id = 16) ==========
$wineCategories = [];
$sql = "SELECT id, name, slug FROM categories WHERE status = 1 AND parent_id = 16 ORDER BY id ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $wineCategories[] = $row;
    }
}

// ========== LẤY DANH MỤC RƯỢU MẠNH (parent_id = 17) ==========
$spiritCategories = [];
$sql = "SELECT id, name, slug FROM categories WHERE status = 1 AND parent_id = 17 ORDER BY id ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $spiritCategories[] = $row;
    }
}

// ========== CHUYỂN ĐỔI LINK MENU ==========
function convertLink($link, $link_type, $link_value, $category_id, $slug = null) {
    global $conn, $hasSlug;
    
    if (!empty($slug) && !empty($link) && strpos($link, '/') === 0) {
        if (preg_match('/^\/[a-z0-9-]+$/', $link)) {
            return $link;
        }
        if (strpos($link, 'product_main.php') !== false) {
            return '/' . $slug;
        }
    }
    
    if ($link_type === 'category' && !empty($slug)) {
        return '/' . $slug;
    }
    
    if (!empty($category_id) && $category_id > 0) {
        return getCategoryLink($category_id);
    }
    
    if ($link_type === 'category' && !empty($link_value)) {
        $catId = (int)$link_value;
        if ($catId > 0) {
            return getCategoryLink($catId);
        }
    }
    
    if (!empty($link) && preg_match('/^\/[a-z0-9-]+$/', $link)) {
        return $link;
    }
    
    if (preg_match('/product_main\.php\?cat_id=([0-9]+)/', $link, $matches)) {
        $catId = (int)$matches[1];
        if ($catId > 0) {
            return getCategoryLink($catId);
        }
        return '/ruou-vang';
    }
    
    $staticLinks = [
        'product_main.php' => '/ruou-vang',
        'lyphale.php' => '/ly-pha-le',
        'kienthuc.php' => '/kien-thuc',
        'lienhe.php' => '/lien-he',
        'sanphamkhac.php' => '/san-pham-khac',
        'quatangdoanhnghiep.php' => '/quatangdoanhnghiep',
        'nhasanxuat.php' => '/nha-san-xuat',
        'login.php' => '/dang-nhap',
        'register.php' => '/dang-ky',
        'cart.php' => '/gio-hang',
        'account.php' => '/tai-khoan',
        'checkout.php' => '/thanh-toan',
        'chinh-sach.php' => '/chinh-sach'
    ];
    
    if (isset($staticLinks[$link])) {
        return $staticLinks[$link];
    }
    
    return $link;
}

// ========== XÂY DỰNG MENU HTML ==========
function buildMenuHTML($menuItems) {
    global $categoryId;
    
    if (empty($menuItems)) {
        return '<li><a href="index.php" class="nav-link">Trang chủ</a></li>';
    }
    
    $rootItems = array_filter($menuItems, function($item) {
        return $item['parent_id'] == 0;
    });
    
    usort($rootItems, function($a, $b) {
        return ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0);
    });
    
    $html = '';
    foreach ($rootItems as $root) {
        $children = array_filter($menuItems, function($item) use ($root) {
            return $item['parent_id'] == $root['id'];
        });
        
        usort($children, function($a, $b) {
            return ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0);
        });
        
        $hasChildren = !empty($children);
        $link = convertLink($root['link'] ?? '#', $root['link_type'] ?? 'custom', $root['link_value'] ?? null, $root['category_id'] ?? null, $root['slug'] ?? null);
        $isActive = false;
        
        $currentPath = $_SERVER['REQUEST_URI'];
        $linkPath = parse_url($link, PHP_URL_PATH);
        if ($linkPath && strpos($currentPath, $linkPath) !== false) {
            $isActive = true;
        }
        
        if (!empty($root['category_id']) && $categoryId == $root['category_id']) {
            $isActive = true;
        }
        
        if (!empty($root['slug']) && strpos($currentPath, '/' . $root['slug']) !== false) {
            $isActive = true;
        }
        
        $activeClass = $isActive ? ' active' : '';
        $liClass = $hasChildren ? 'class="nav-item has-dropdown"' : 'class="nav-item"';
        
        $html .= '<li ' . $liClass . '>';
        $html .= '<a href="' . htmlspecialchars($link) . '" class="nav-link' . $activeClass . '">' . htmlspecialchars($root['label']) . '</a>';
        
        if ($hasChildren) {
            $html .= '<span class="mobile-arrow"><i class="fas fa-chevron-down"></i></span>';
            $html .= '<div class="dropdown-menu">';
            
            $grouped = [];
            foreach ($children as $child) {
                $group = !empty($child['column_group']) ? (int)$child['column_group'] : 1;
                if (!isset($grouped[$group])) {
                    $grouped[$group] = [];
                }
                $grouped[$group][] = $child;
            }
            ksort($grouped);
            
            foreach ($grouped as $groupItems) {
                $html .= '<div class="dropdown-column">';
                foreach ($groupItems as $child) {
                    $childLink = convertLink($child['link'] ?? '#', $child['link_type'] ?? 'custom', $child['link_value'] ?? null, $child['category_id'] ?? null, $child['slug'] ?? null);
                    $isChildActive = false;
                    
                    if (!empty($child['category_id']) && $categoryId == $child['category_id']) {
                        $isChildActive = true;
                    }
                    
                    if (!empty($child['is_heading'])) {
                        $html .= '<h3>' . htmlspecialchars($child['label']) . '</h3>';
                    } else {
                        $activeChild = $isChildActive ? ' active' : '';
                        $html .= '<a href="' . htmlspecialchars($childLink) . '" class="' . $activeChild . '">' . htmlspecialchars($child['label']) . '</a>';
                    }
                }
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        $html .= '</li>';
    }
    
    return $html;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo $pageTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="https://winevn.com/images/favicon.png" type="image/x-icon">
    <style>
        /* ===== RESET & BASE (giữ nguyên CSS của bạn) ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary-red: #990D23; --dark-red: #670011; --deep-red: #55000D; --gold: #FFBD27; --light-bg: #FBF8EE; --cream: #F2EFE2; --border-color: #E8E8E8; --text-dark: #212529; --text-light: #6C757D; --plum-red: #8B1E1E; }
        body { font-family: 'Inter', sans-serif; color: var(--text-dark); background: white; line-height: 1.6; overflow-x: hidden; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; width: 100%; }

        /* ===== TOAST ===== */
        .toast-notification {
            position: fixed; bottom: 100px; right: 20px; background: white; padding: 16px 24px;
            border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); z-index: 99999;
            display: none; max-width: 400px; border-left: 5px solid var(--primary-red);
            animation: slideUp 0.3s ease;
        }
        .toast-notification.show { display: block; }
        .toast-notification .toast-title { font-weight: 700; color: var(--primary-red); font-size: 16px; margin-bottom: 4px; }
        .toast-notification .toast-message { font-size: 14px; color: #555; }
        .toast-notification .toast-close { position: absolute; top: 8px; right: 12px; background: none; border: none; font-size: 20px; cursor: pointer; color: #999; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* ===== HOTLINE ===== */
        .hotline-float { position: fixed; right: 15px; bottom: 80px; z-index: 9999; display: flex; flex-direction: column; align-items: center; gap: 8px; }
        .hotline-item { display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: var(--primary-red); color: white; text-decoration: none; font-size: 20px; box-shadow: 0 3px 12px rgba(153,13,35,0.35); transition: all 0.3s ease; position: relative; cursor: pointer; }
        .hotline-item:hover { transform: scale(1.1); box-shadow: 0 5px 20px rgba(153,13,35,0.5); }
        .hotline-item .tooltip { position: absolute; right: 58px; background: rgba(0,0,0,0.85); color: white; padding: 5px 12px; border-radius: 6px; font-size: 12px; white-space: nowrap; opacity: 0; visibility: hidden; transition: all 0.3s ease; font-weight: 500; }
        .hotline-item .tooltip::after { content: ''; position: absolute; right: -6px; top: 50%; transform: translateY(-50%); border-left: 6px solid rgba(0,0,0,0.85); border-top: 6px solid transparent; border-bottom: 6px solid transparent; }
        .hotline-item:hover .tooltip { opacity: 1; visibility: visible; right: 62px; }
        .hotline-item.zalo { background: #0068FF; box-shadow: 0 3px 12px rgba(0,104,255,0.35); }
        .hotline-item.zalo:hover { box-shadow: 0 5px 20px rgba(0,104,255,0.5); }
        .hotline-item.zalo .zalo-icon { width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; }
        .hotline-item.zalo .zalo-icon svg { width: 100%; height: 100%; fill: white; }
        .hotline-item.messenger { background: #0084FF; box-shadow: 0 3px 12px rgba(0,132,255,0.35); }
        .hotline-item.messenger:hover { box-shadow: 0 5px 20px rgba(0,132,255,0.5); }
        .hotline-item.messenger i { font-size: 22px; }
        .hotline-item.phone { background: #25D366; box-shadow: 0 3px 12px rgba(37,211,102,0.35); }
        .hotline-item.phone:hover { box-shadow: 0 5px 20px rgba(37,211,102,0.5); }
        .hotline-item.phone i { font-size: 18px; }
        .hotline-item.pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(153,13,35,0.3); } 70% { box-shadow: 0 0 0 12px rgba(153,13,35,0); } 100% { box-shadow: 0 0 0 0 rgba(153,13,35,0); } }
        .hotline-item.zalo.pulse { animation: pulseZalo 2s infinite; }
        @keyframes pulseZalo { 0% { box-shadow: 0 0 0 0 rgba(0,104,255,0.3); } 70% { box-shadow: 0 0 0 12px rgba(0,104,255,0); } 100% { box-shadow: 0 0 0 0 rgba(0,104,255,0); } }
        .hotline-item.messenger.pulse { animation: pulseMessenger 2s infinite; }
        @keyframes pulseMessenger { 0% { box-shadow: 0 0 0 0 rgba(0,132,255,0.3); } 70% { box-shadow: 0 0 0 12px rgba(0,132,255,0); } 100% { box-shadow: 0 0 0 0 rgba(0,132,255,0); } }
        @media (max-width:768px) { .hotline-float { right:10px; bottom:70px; gap:6px; } .hotline-item { width:40px; height:40px; font-size:16px; } .hotline-item.zalo .zalo-icon { width:22px; height:22px; } .hotline-item.messenger i { font-size:18px; } .hotline-item.phone i { font-size:15px; } .hotline-item .tooltip { font-size:11px; padding:4px 10px; right:48px; } .hotline-item:hover .tooltip { right:50px; } }
        @media (max-width:480px) { .hotline-float { right:6px; bottom:60px; gap:5px; } .hotline-item { width:36px; height:36px; font-size:14px; } .hotline-item.zalo .zalo-icon { width:20px; height:20px; } .hotline-item.messenger i { font-size:16px; } .hotline-item.phone i { font-size:13px; } .hotline-item .tooltip { font-size:10px; padding:3px 8px; right:42px; } .hotline-item:hover .tooltip { right:44px; } .hotline-item .tooltip::after { right:-5px; border-left:5px solid rgba(0,0,0,0.85); border-top:5px solid transparent; border-bottom:5px solid transparent; } }

        /* ===== BREADCRUMB ===== */
        .category-breadcrumb { font-size: 16px; margin-bottom: 16px; padding: 8px 0; background: transparent; font-family: 'Inter', sans-serif; }
        .category-breadcrumb a { color: var(--primary-red); text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .category-breadcrumb a:hover { text-decoration: underline; }
        .category-breadcrumb span { color: var(--text-dark); font-weight: 500; }

        /* ===== HEADER (giữ nguyên) ===== */
        .header { background: #ffffff; position: fixed; top: 0; left: 0; right: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 0; transition: all 0.3s cubic-bezier(0.25,0.46,0.45,0.94); }
        .header.hide .header-top { display: none; }
        .header.hide .header-bottom { margin-top: 0; border-top: none; }
        .header.show .header-top { display: flex; }
        .header.show .header-bottom { margin-top: 0; border-top: none; }
        .header-top { display: flex; align-items: center; justify-content: space-between; gap: 25px; flex-wrap: wrap; transition: all 0.2s ease; padding: 10px 0; }
        .logo { flex-shrink: 0; }
        .logo img { height: 42px; width: auto; display: block; max-width: 100%; }
        .search-bar { flex: 1; max-width: 700px; min-width: 200px; position: relative; }
        .search-form { display: flex; width: 100%; background: white; border-radius: 60px; border: 2px solid #000000; transition: all 0.2s; }
        .search-form:focus-within { border-color: #000000; box-shadow: 0 0 0 3px rgba(0,0,0,0.1); }
        .search-form input { flex: 1; padding: 12px 18px; border: none; background: transparent; border-radius: 60px 0 0 60px; font-size: 14px; outline: none; }
        .search-form button { background: transparent; border: none; padding: 0 18px; border-radius: 0 60px 60px 0; cursor: pointer; color: var(--text-dark); font-size: 18px; }
        .search-form button:hover { color: var(--primary-red); }
        .search-suggestions { position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.15); border: 1px solid var(--border-color); z-index: 1050; max-height: 500px; overflow-y: auto; display: none; margin-top: 8px; padding: 16px 20px; }
        .search-suggestions.active { display: block; }
        .suggestions-header { font-size: 14px; font-weight: 700; color: var(--text-dark); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--primary-red); }
        .suggestions-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 20px; }
        .suggestion-item { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 30px; padding: 8px 12px; font-size: 14px; text-align: center; cursor: pointer; transition: all 0.2s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .suggestion-item:hover { background: var(--primary-red); border-color: var(--primary-red); color: white; }
        .suggestion-search-example { background: #fff3e0; border: 1px solid var(--gold); border-radius: 30px; padding: 10px 16px; font-size: 14px; display: inline-block; cursor: pointer; transition: 0.2s; }
        .suggestion-search-example i { margin-right: 8px; color: var(--gold); }
        .suggestion-search-example:hover { background: var(--gold); color: var(--text-dark); }
        @media (max-width:992px) { .suggestions-grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width:576px) { .suggestions-grid { grid-template-columns: 1fr; } }

        .header-right { display: flex; align-items: center; gap: 20px; flex-shrink: 0; }
        .nav-icon-link { display: inline-flex; align-items: center; gap: 6px; color: var(--text-dark); text-decoration: none; font-size: 22px; font-weight: 500; transition: color 0.2s; }
        .nav-icon-link span { font-size: 14px; font-weight: 500; }
        .nav-icon-link:hover { color: var(--primary-red); }
        .cart-icon { position: relative; font-size: 24px; cursor: pointer; }
        .cart-icon a { color: var(--text-dark); text-decoration: none; }
        .cart-count { position: absolute; top: -10px; right: -12px; background: var(--primary-red); color: white; font-size: 11px; width: 19px; height: 19px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .wishlist-icon { position: relative; font-size: 24px; cursor: pointer; }
        .wishlist-icon a { color: var(--text-dark); text-decoration: none; }
        .wishlist-count { position: absolute; top: -10px; right: -12px; background: var(--primary-red); color: white; font-size: 11px; width: 19px; height: 19px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .user-dropdown { position: relative; cursor: pointer; }
        .user-icon { font-size: 26px; color: var(--text-dark); transition: 0.2s; }
        .user-icon:hover { color: var(--primary-red); }
        .dropdown-menu-user { position: absolute; top: 130%; right: 0; background: white; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.15); min-width: 190px; list-style: none; padding: 10px 0; opacity: 0; visibility: hidden; transition: all 0.2s ease; border: 1px solid var(--border-color); z-index: 1050; }
        .user-dropdown:hover .dropdown-menu-user { opacity: 1; visibility: visible; top: 100%; }
        .dropdown-menu-user li { padding: 10px 22px; }
        .dropdown-menu-user li a { text-decoration: none; color: var(--text-dark); display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500; transition: 0.2s; }
        .dropdown-menu-user li a:hover { color: var(--primary-red); transform: translateX(4px); }
        .mobile-menu-toggle { display: none; background: transparent; border: none; font-size: 28px; color: var(--text-dark); cursor: pointer; padding: 0 5px; transition: color 0.2s; }
        .mobile-menu-toggle:hover { color: var(--primary-red); }

        .header-bottom { background: var(--dark-red); margin-top: 0; border-top: none; padding-top: 4px; padding-bottom: 4px; position: relative; width: 100vw; left: 50%; right: 50%; margin-left: -50vw; margin-right: -50vw; transition: margin-top 0.2s ease, border-top 0.2s ease; }
        .main-nav { width: 100%; }
        .nav-list { display: flex; list-style: none; gap: 4px; justify-content: center; flex-wrap: wrap; margin: 0; padding: 0 8px; }
        .nav-item { position: static; display: flex; align-items: center; flex-wrap: wrap; }
        .nav-link { color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 8px 14px; display: inline-flex; align-items: center; transition: all 0.2s ease; border-radius: 40px; white-space: nowrap; }
        .nav-link:hover { color: var(--gold); background: rgba(255,255,255,0.1); }
        .nav-link.highlight-text { color: var(--gold); font-weight: 700; }
        .nav-link.active { color: var(--gold) !important; background: rgba(255,255,255,0.12); }
        .mobile-arrow { display: none; }

        /* ===== DROPDOWN ===== */
        .dropdown-menu { position: absolute; top: 100%; left: 50%; transform: translateX(-50%); width: 90vw; max-width: 1200px; background: white; padding: 25px 30px; display: none; grid-template-columns: repeat(4,1fr); gap: 25px; margin-top: 0px; box-shadow: 0 20px 35px rgba(0,0,0,0.15); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); z-index: 1000; overflow-y: auto; overflow-x: hidden; border-radius: 0; }
        @media (min-width:993px) { .has-dropdown:hover .dropdown-menu { display: grid; } }
        .dropdown-menu::before { content: ""; position: absolute; inset: 0; background: white; z-index: -1; }
        .dropdown-column { min-width: 0; padding: 0 10px; }
        .dropdown-column h3 { color: var(--primary-red); font-size: 14px; font-weight: 800; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid var(--primary-red); text-transform: uppercase; letter-spacing: 0.5px; }
        .dropdown-column a { color: var(--text-dark); text-decoration: none; font-size: 13px; font-weight: 400; display: block; padding: 5px 0; transition: color 0.2s, padding-left 0.2s; word-break: break-word; }
        .dropdown-column a:hover { color: var(--primary-red); padding-left: 6px; }
        @media (max-width:1200px) { .dropdown-menu { grid-template-columns: repeat(2,1fr); gap: 20px; width: 95vw; } }
        @media (max-width:768px) { .dropdown-menu { grid-template-columns: 1fr; gap: 15px; } }

        /* ===== MOBILE ===== */
        @media (max-width:992px) {
            .header-bottom { position: relative; overflow: visible; }
            .mobile-menu-toggle { display: inline-flex; align-items: center; }
            .main-nav .nav-list { display: none; position: absolute; top: calc(100% + 10px); left: 0; right: 0; width: 100%; background: white; flex-direction: column; padding: 12px 0; gap: 6px; box-shadow: 0 15px 30px rgba(0,0,0,0.12); border-radius: 0 0 18px 18px; z-index: 2000; max-height: 85vh; overflow-y: auto; overflow-x: hidden; border-top: 1px solid var(--border-color); }
            .main-nav .nav-list.show-mobile { display: flex; }
            .main-nav .nav-list .nav-item { width: 100%; margin: 0; background: #f8f9fa; border-radius: 12px; overflow: visible; }
            .has-dropdown { position: relative; display: flex !important; flex-wrap: wrap; align-items: stretch; background: #f8f9fa; border-radius: 12px; overflow: visible; }
            .main-nav .nav-list .nav-link { flex: 1; min-height: 52px; padding: 12px 16px; display: flex; align-items: center; font-size: 15px; font-weight: 600; border-radius: 12px; background: #f8f9fa; color: var(--text-dark); text-decoration: none; }
            .mobile-arrow { display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; flex-shrink: 0; cursor: pointer; border-left: 1px solid #e0e0e0; transition: 0.2s; color: var(--text-dark); }
            .mobile-arrow i { transition: transform 0.25s ease; font-size: 14px; }
            .mobile-arrow.active i { transform: rotate(180deg); }
            .dropdown-menu { position: relative; width: 100%; min-width: 100%; margin-top: 0; padding: 10px 12px 12px 12px; background: #ffffff; border: none; box-shadow: none; border-radius: 0 0 12px 12px; display: none; grid-template-columns: 1fr; gap: 10px; order: 2; left: 0; transform: none; overflow-x: hidden; max-height: 60vh; overflow-y: auto; }
            .dropdown-menu.show-mobile-dropdown { display: grid !important; animation: dropdownFade 0.25s ease; }
            @keyframes dropdownFade { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
            .dropdown-column { background: #fefefe; border-radius: 12px; padding: 12px; border: 1px solid #ececec; margin: 0; word-break: break-word; overflow-wrap: break-word; }
            .dropdown-column h3 { font-size: 14px; font-weight: 800; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid var(--primary-red); color: var(--primary-red); }
            .dropdown-column a { display: block; padding: 8px 0; font-size: 14px; color: var(--text-dark); text-decoration: none; word-break: break-word; }
            .dropdown-column a:hover { color: var(--primary-red); padding-left: 6px; }
            .header-top { gap: 10px; }
            .header-bottom { margin-top: 0 !important; padding-top: 3px !important; }
            .desktop-only { display: none !important; }
        }
        @media (max-width:680px) {
            .header-top { gap: 8px !important; padding: 6px 0 !important; }
            .logo img { height: 34px !important; }
            .search-bar { order: 3; width: 100%; max-width: 100%; margin-top: 6px !important; }
            .header-right { gap: 10px !important; }
            .mobile-menu-toggle { font-size: 24px !important; }
            .search-form input { padding: 10px 14px !important; font-size: 13px !important; }
            .search-form button { padding: 0 14px !important; font-size: 16px !important; }
            .cart-icon { font-size: 22px !important; }
            .user-icon { font-size: 24px !important; }
            .cart-count { top: -8px !important; right: -10px !important; width: 17px !important; height: 17px !important; font-size: 10px !important; }
            .main-nav .nav-list .nav-link { min-height: 46px !important; padding: 10px 14px !important; font-size: 14px !important; }
            .mobile-arrow { width: 46px !important; height: 46px !important; }
        }
        @media (max-width:480px) {
            .header { padding: 0 !important; }
            .header-top { gap: 6px !important; padding: 4px 0 !important; }
            .logo img { height: 32px !important; }
            .search-bar { margin-top: 4px !important; }
            .header-right { gap: 8px !important; }
            .container { padding: 0 12px; }
        }

        /* ===== FLOATING PROMO ===== */
        .floating-promo { position: fixed; bottom: 0; left: 0; right: 0; background: white; box-shadow: 0 -6px 25px rgba(0,0,0,0.18); padding: 12px 16px; display: flex; justify-content: center; gap: 12px; z-index: 1000; transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); border-top: 4px solid #8B1E1E; flex-wrap: wrap; }
        .floating-promo.show { transform: translateY(0); }
        @media (max-width:768px) { .floating-promo { display: none !important; } }
        .floating-item { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: linear-gradient(135deg,#8B1E1E 0%,#A61C2A 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 4px 12px rgba(139,30,30,0.3); white-space: nowrap; flex: 1; max-width: 160px; justify-content: center; }
        .floating-item i { font-size: 16px; }
        .floating-item:hover { background: linear-gradient(135deg,#6b1717 0%,#8B1E1E 100%); transform: translateY(-2px) scale(1.05); box-shadow: 0 8px 20px rgba(139,30,30,0.4); }

        /* ===== PRODUCT CARD ===== */
        .product-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; height: 100%; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .product-image-link { display: block; overflow: hidden; position: relative; background-color: #f9f9f9; aspect-ratio: 6/7; padding: 8px; }
        .product-image { width: 100%; height: 100%; object-fit: contain; transition: transform 0.5s ease; }
        .product-card:hover .product-image { transform: scale(1.02); }
        .product-badge { position: absolute; top: 10px; left: 10px; background: var(--primary-red); color: white; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; z-index: 2; }
        .product-badge-stock { position: absolute; top: 10px; right: 10px; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; z-index: 2; background: #ffc107; color: #333; }
        .product-badge-stock.discontinued { background: #6c757d; color: white; }
        .product-badge-stock.outstock { background: #dc3545; color: white; }
        .product-badge-stock.soon { background: #17a2b8; color: white; }
        .product-info { padding: 8px 10px 4px; flex: 1; display: flex; flex-direction: column; }
        .product-info h3 { font-size: 14px; margin-bottom: 4px; font-weight: 600; line-height: 1.4; }
        .product-info h3 a { text-decoration: none; color: inherit; transition: color 0.2s; }
        .product-info h3 a:hover { color: var(--primary-red); }
        .product-details { background: #fafafa; padding: 6px 8px; margin: 6px 8px 8px 8px; border-radius: 8px; border: 1px solid #eee; display: grid; grid-template-columns: 1fr 1fr; gap: 5px 8px; }
        .detail-item { display: flex; align-items: center; gap: 5px; padding: 3px 0; border-bottom: 1px solid #eaeaea; overflow: hidden; }
        .detail-item i { width: 18px; color: var(--primary-red); font-size: 11px; flex-shrink: 0; }
        .detail-item span { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #4a4a4a; font-size: 11px; }
        .product-bottom-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; border-top: 1px solid #eee; margin-top: auto; }
        .btn-buy-now { background: var(--primary-red); color: white; border: none; padding: 5px 12px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-buy-now:hover { background: var(--dark-red); transform: translateY(-2px); }
        .product-bottom-price { text-align: left; display: flex; flex-direction: column; align-items: flex-start; }
        .current-price-bottom { font-size: 14px; font-weight: 700; color: var(--primary-red); }
        .original-price-bottom { font-size: 11px; color: #6c757d; text-decoration: line-through; margin-bottom: 2px; }
        .contact-price-badge { font-size: 13px; font-weight: 600; color: #b45309; background: #fef3c7; padding: 2px 12px; border-radius: 20px; display: inline-block; }

        /* ===== MAIN LAYOUT ===== */
        .main-container { display: flex; width: 100%; position: relative; margin-top: 35px; align-items: flex-start; }
        .filter-sidebar { width: 280px; flex-shrink: 0; background: white; padding: 25px 20px; box-shadow: 2px 0 15px rgba(0,0,0,0.05); height: fit-content; position: sticky; top: 120px; align-self: flex-start; border-right: 1px solid #e0e0e0; max-height: calc(100vh - 140px); overflow-y: auto; z-index: 99; }
        .product-listing-container { flex: 1; padding: 0 20px 0 40px; width: calc(100% - 280px); display: flex; justify-content: center; }
        .product-listing { width: 100%; max-width: 1200px; }
        @media (max-width:1200px) { .product-grid { grid-template-columns: repeat(4,1fr) !important; gap: 20px; } }
        @media (max-width:992px) { .main-container { margin-top: 30px; } .filter-sidebar { width:260px; padding:20px 15px; top:110px; max-height:calc(100vh - 130px); } .product-listing-container { padding:0 15px 0 25px; width:calc(100% - 260px); } .product-grid { grid-template-columns: repeat(3,1fr) !important; gap:18px; } }
        @media (max-width:768px) { .main-container { margin-top:15px; flex-direction:column; } .filter-sidebar { display:none !important; } .product-listing-container { padding:0 12px !important; width:100% !important; flex:auto; } .product-grid { grid-template-columns: repeat(2,1fr) !important; gap:14px; } .listing-header { flex-direction:column; align-items:flex-start; margin-bottom:20px; } .view-controls { width:100%; justify-content:space-between; } .result-count { font-size:14px; } .product-info h3 { font-size:13px; } .detail-item i { font-size:11px; } .btn-buy-now { padding:5px 10px; font-size:11px; } .current-price-bottom { font-size:13px; } }
        @media (max-width:480px) { .product-grid { grid-template-columns: repeat(2,1fr) !important; gap:12px; } .product-listing-container { padding:0 8px !important; } .product-info { padding:6px 8px; } .product-info h3 { font-size:12px; } .product-details { padding:5px 6px; margin:4px 6px 6px 6px; gap:4px 6px; } .detail-item { gap:4px; } .detail-item i { width:18px; font-size:10px; } .detail-item span { font-size:10px; } .btn-buy-now { padding:4px 8px; font-size:10px; } .current-price-bottom { font-size:12px; } }

        /* ===== FILTER ===== */
        .filter-sidebar h3 { font-size:16px; margin-bottom:15px; color:var(--primary-red); display:flex; align-items:center; gap:8px; text-transform:uppercase; }
        .price-range-group { margin-bottom:25px; padding-bottom:20px; border-bottom:1px solid #e0e0e0; }
        .price-range-checkboxes { display:flex; flex-direction:column; gap:12px; margin-top:10px; }
        .price-range-item { display:flex; align-items:center; gap:10px; cursor:pointer; font-size:14px; }
        .price-range-item input { width:18px; height:18px; cursor:pointer; }
        .filter-btn { width:100%; padding:12px; background:var(--primary-red); color:white; border:none; border-radius:6px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:background 0.3s; margin-top:8px; }
        .filter-btn:hover { background:var(--dark-red); }
        .reset-all-filters { background:#6c757d; margin-top:15px; }
        .accordion-item { margin-bottom:10px; border-bottom:1px solid #e0e0e0; padding-bottom:10px; }
        .accordion-header { width:100%; background:none; border:none; padding:12px 0; text-align:left; cursor:pointer; display:flex; justify-content:space-between; align-items:center; font-weight:700; font-size:15px; }
        .accordion-header:hover { color:var(--primary-red); }
        .accordion-icon { transition:transform 0.3s; font-size:12px; color:#6c757d; }
        .accordion-content { max-height:0; overflow:hidden; transition:max-height 0.3s ease-out; }
        .accordion-item.active .accordion-content { max-height:500px; overflow-y:auto; }
        .accordion-item.active .accordion-icon { transform:rotate(180deg); }
        .filter-list { padding:10px 0; display:flex; flex-direction:column; gap:8px; }
        .filter-item { display:flex; align-items:center; justify-content:space-between; padding:6px 0; cursor:pointer; font-size:14px; }
        .filter-item input { margin-right:12px; width:18px; height:18px; cursor:pointer; }
        .filter-text { flex:1; font-weight:500; }
        .count { color:#6c757d; font-size:12px; min-width:40px; text-align:right; }

        /* ===== LISTING ===== */
        .listing-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; padding-bottom:15px; border-bottom:1px solid #e0e0e0; flex-wrap:wrap; gap:10px; }
        .view-controls { display:flex; align-items:center; gap:15px; }
        .view-toggle { display:flex; gap:8px; background:#f5f5f5; padding:5px; border-radius:8px; }
        .view-toggle button { background:none; border:none; padding:8px 12px; cursor:pointer; border-radius:6px; color:#666; transition:all 0.2s; }
        .view-toggle button.active { background:var(--primary-red); color:white; }
        .result-count { font-size:15px; color:#6c757d; font-weight:500; }
        .result-count strong { color:var(--primary-red); font-weight:700; }
        .sort-options select { padding:10px 15px; border:1px solid #e0e0e0; border-radius:6px; background:white; font-size:14px; cursor:pointer; }
        .product-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:24px; margin-bottom:40px; }
        .pagination { display:flex; justify-content:center; gap:8px; margin-top:30px; }
        .page-number { width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:6px; background:white; color:var(--text-dark); border:1px solid #e0e0e0; font-weight:500; cursor:pointer; }
        .page-number.active { background:var(--primary-red); color:white; border-color:var(--primary-red); }

        /* ===== CATEGORY DESCRIPTION ===== */
        .category-description { margin-bottom:40px; background:#fff; border-radius:16px; padding:24px 28px; box-shadow:0 2px 12px rgba(0,0,0,0.05); border:1px solid #eee; }
        .category-description h2 { font-size:28px; font-weight:700; color:var(--primary-red); margin-bottom:16px; font-family:'Merriweather',serif; }
        .description-content-wrapper { position:relative; }
        .description-content { font-size:16px; line-height:1.65; color:#3a3a3a; }
        .description-content .full-text { display:none; }
        .description-content.expanded .full-text { display:inline; }
        .description-content.expanded .preview-text { display:none; }
        .description-content .read-more-btn { background:none; border:none; color:var(--primary-red); font-weight:600; font-size:15px; cursor:pointer; display:inline-flex; align-items:center; gap:8px; padding:0; margin-top:8px; }
        .description-content .read-more-btn:hover { text-decoration:underline; }
        .description-content .read-more-btn i { font-size:14px; transition:transform 0.3s ease; }
        .description-content.expanded .read-more-btn i { transform:rotate(180deg); }

        /* ===== PROMO ===== */
        .promo-actions { display:flex; justify-content:center; gap:20px; margin:40px 0 30px; flex-wrap:wrap; padding:0 10px; }
        .promo-item { display:inline-flex; align-items:center; gap:10px; padding:14px 24px; background-color:#f8f8f8; border:2px solid #8B1E1E; border-radius:50px; color:#8B1E1E; font-weight:700; font-size:16px; text-decoration:none; transition:all 0.3s; box-shadow:0 4px 12px rgba(139,30,30,0.12); white-space:nowrap; flex:1; max-width:220px; justify-content:center; }
        .promo-item i { font-size:20px; }
        .promo-item:hover { background-color:#8B1E1E; color:white; transform:translateY(-3px); box-shadow:0 8px 20px rgba(139,30,30,0.3); }
        @media (max-width:768px) { .promo-actions { gap:12px; margin:30px 0 20px; } .promo-item { padding:12px 20px; font-size:15px; max-width:200px; flex:1 1 45%; min-width:140px; } }
        @media (max-width:480px) { .promo-actions { flex-direction:column; align-items:center; } .promo-item { width:280px; max-width:280px; } }

        /* ===== FOOTER ===== */
        .main-footer { background:var(--dark-red) !important; color:white; padding-top:50px; margin-top:0; width:100%; }
        .footer-container { display:grid; grid-template-columns:repeat(4,1fr); gap:40px; padding-bottom:40px; }
        .footer-section h4 { font-size:18px; margin-bottom:20px; display:flex; align-items:center; gap:8px; color:white; }
        .footer-section p, .footer-section a { margin-bottom:10px; font-size:14px; opacity:0.9; color:rgba(255,255,255,0.9); text-decoration:none; display:flex; align-items:center; gap:6px; transition:color 0.2s; }
        .footer-section a:hover { color:white; }
        .social-links { display:flex; gap:15px; margin-top:20px; }
        .social-links a { width:36px; height:36px; background:rgba(255,255,255,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; transition:background 0.3s; }
        .social-links a:hover { background:var(--primary-red); }
        .newsletter { display:flex; margin-top:15px; margin-bottom:25px; }
        .newsletter input { flex:1; padding:12px 15px; border:none; border-radius:6px 0 0 6px; font-size:14px; outline:none; }
        .newsletter button { background:var(--gold); color:var(--dark-red); border:none; padding:0 20px; border-radius:0 6px 6px 0; cursor:pointer; font-weight:600; }
        .payment-methods { display:flex; gap:15px; font-size:24px; margin-top:20px; color:rgba(255,255,255,0.8); }
        .footer-bottom { text-align:center; padding:20px 0; border-top:1px solid rgba(255,255,255,0.1); font-size:13px; opacity:0.8; }
        @media (max-width:992px) { .footer-container { grid-template-columns:repeat(2,1fr); gap:30px; } }
        @media (max-width:580px) { .footer-container { grid-template-columns:1fr; gap:30px; text-align:left !important; } .footer-section { text-align:left !important; padding-left:0; } .footer-section p, .footer-section a { justify-content:flex-start !important; } .social-links, .payment-methods { justify-content:flex-start !important; } }

        /* ===== MODAL ===== */
        .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5); backdrop-filter:blur(5px); }
        .modal-content { background-color:#fff; margin:5% auto; padding:30px; border-radius:24px; width:90%; max-width:900px; box-shadow:0 20px 40px rgba(0,0,0,0.3); position:relative; animation:slideDown 0.3s ease; }
        @keyframes slideDown { from { transform:translateY(-50px); opacity:0; } to { transform:translateY(0); opacity:1; } }
        .close-modal { position:absolute; right:25px; top:15px; font-size:35px; font-weight:300; color:#888; cursor:pointer; transition:color 0.2s; line-height:1; }
        .close-modal:hover { color:var(--primary-red); }
        .modal-body { display:flex; gap:30px; flex-wrap:wrap; }
        .form-col { flex:1 1 300px; }
        .form-col h3 { font-size:24px; color:var(--primary-red); margin-bottom:5px; }
        .form-col p { color:var(--text-light); margin-bottom:20px; }
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; font-weight:500; margin-bottom:5px; color:var(--text-dark); }
        .form-group input { width:100%; padding:12px 15px; border:2px solid var(--border-color); border-radius:12px; font-size:16px; transition:border-color 0.2s; }
        .form-group input:focus { outline:none; border-color:var(--primary-red); }
        .submit-btn { background:var(--primary-red); color:white; border:none; padding:14px 30px; border-radius:50px; font-size:18px; font-weight:700; cursor:pointer; width:100%; transition:background 0.3s, transform 0.2s; box-shadow:0 5px 15px rgba(139,0,0,0.3); border:2px solid var(--primary-red); }
        .submit-btn:hover { background:var(--dark-red); transform:translateY(-2px); }
        #modalSuccessState { text-align:center; padding:20px 0; }
        #modalSuccessState i { font-size:70px; color:#2ecc71; margin-bottom:15px; }
        #modalSuccessState h3 { font-size:26px; color:var(--text-dark); margin-bottom:10px; }
        #modalSuccessState p { color:var(--text-light); margin-bottom:25px; }
        .close-success-btn { background:var(--primary-red); color:white; border:none; padding:12px 40px; border-radius:50px; font-size:18px; font-weight:600; cursor:pointer; transition:background 0.3s; border:2px solid var(--primary-red); }
        .close-success-btn:hover { background:var(--dark-red); }

        .loading-spinner { text-align:center; padding:40px; font-size:16px; color:var(--primary-red); }
        .error-message { text-align:center; padding:40px; color:#dc3545; }
        
        .nav-list .nav-item .nav-link { transition: all 0.2s ease; }
        .nav-list .nav-item .nav-link:hover { color: var(--gold) !important; background: rgba(255, 255, 255, 0.1) !important; }
        .nav-list .nav-item .nav-link .fa-home { font-size: 16px; margin-right: 4px; }
        @media (max-width: 992px) { .nav-list .nav-item .nav-link .fa-home { font-size: 18px; } }
    </style>
</head>
<body>
    <!-- Toast Notification -->
    <div class="toast-notification" id="toastNotification">
        <button class="toast-close" onclick="closeToast()">&times;</button>
        <div class="toast-title" id="toastTitle">Thành công!</div>
        <div class="toast-message" id="toastMessage">Đã thêm vào giỏ hàng</div>
    </div>

    <!-- ===== HOTLINE FLOATING ===== -->
<div class="hotline-float">
    <a href="https://zalo.me/0977869066" target="_blank" class="hotline-item zalo pulse" title="Chat Zalo">
        <span class="zalo-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="12" fill="#0091FF" />
                <text x="12" y="14.5" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif" font-weight="700" font-size="10" fill="#FFFFFF" text-anchor="middle" letter-spacing="-0.3">Zalo</text>
            </svg>
        </span>
        <span class="tooltip">Chat Zalo</span>
    </a>
    <a href="#" target="_blank" class="hotline-item messenger pulse" title="Chat Messenger">
        <i class="fab fa-facebook-messenger"></i>
        <span class="tooltip">Chat Messenger</span>
    </a>
    <a href="tel:0977869066" class="hotline-item phone pulse" title="Gọi ngay">
        <i class="fas fa-phone-alt"></i>
        <span class="tooltip">Gọi ngay: 0977 869 066</span>
    </a>
</div>

    <header class="header show">
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <a href="index.php">
                        <img src="assets/images/logo.jpg" alt="Wine VN - Rượu Vang Nhập Khẩu">
                    </a>
                </div>
                <div class="search-bar">
                    <form class="search-form" action="product_main.php" method="GET" id="searchForm">
                        <input type="text" name="search" id="searchInput" placeholder="Tìm kiếm rượu vang, whisky, quà tặng..." aria-label="Tìm kiếm" autocomplete="off" value="<?php echo htmlspecialchars($searchKeyword); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </div>
                <div class="header-right">
                    <a href="lienhe.php" class="nav-icon-link"><i class="fa-solid fa-location-dot"></i></a>
                    <div class="wishlist-icon">
                        <a href="account.php?tab=wishlist"><i class="fa-solid fa-heart"></i></a>
                        <span class="wishlist-count">0</span>
                    </div>
                    <div class="cart-icon">
                        <a href="cart.php"><i class="fas fa-shopping-cart"></i></a>
                        <span class="cart-count">0</span>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-icon"><i class="fa-solid fa-circle-user"></i></div>
                        <ul class="dropdown-menu-user" id="userMenu"></ul>
                    </div>
                    <button class="mobile-menu-toggle" id="mobileMenuToggle"><i class="fas fa-bars"></i></button>
                </div>
            </div>
            <div class="header-bottom">
                <nav class="main-nav">
                    <ul class="nav-list" id="navList">
                        <li class="nav-item desktop-only">
                            <a href="index.php" class="nav-link" style="display: flex; align-items: center; gap: 6px;"><i class="fas fa-home"></i></a>
                        </li>
                        <?php echo buildMenuHTML($menuItems); ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="main-container">
        <aside class="filter-sidebar">
            <div class="price-range-group">
                <h3><i class="fas fa-tags"></i> CHỌN MỨC GIÁ</h3>
                <div class="price-range-checkboxes" id="priceRangeCheckboxes">
                    <label class="price-range-item"><input type="checkbox" value="under2"> Dưới 2 triệu</label>
                    <label class="price-range-item"><input type="checkbox" value="2to6"> Từ 2 triệu - 6 triệu</label>
                    <label class="price-range-item"><input type="checkbox" value="6to15"> Từ 6 triệu - 15 triệu</label>
                    <label class="price-range-item"><input type="checkbox" value="15to20"> Từ 15 triệu - 20 triệu</label>
                    <label class="price-range-item"><input type="checkbox" value="over20"> Trên 20 triệu</label>
                </div>
                <button id="resetAllFiltersBtn" class="filter-btn reset-all-filters"><i class="fas fa-undo-alt"></i> ĐẶT LẠI BỘ LỌC</button>
            </div>
            <div class="filter-accordion">
                <div id="wineFilterGroup">
                    <div class="accordion-item">
                        <button class="accordion-header"><span><i class="fa-solid fa-wine-glass" style="margin-right:20px;"></i> LOẠI RƯỢU</span><i class="fas fa-chevron-down accordion-icon"></i></button>
                        <div class="accordion-content" id="wineTypeFilterList"></div>
                    </div>
                    <div class="accordion-item"><button class="accordion-header"><span><i class="fas fa-globe" style="margin-right:20px;"></i> QUỐC GIA</span><i class="fas fa-chevron-down accordion-icon"></i></button><div class="accordion-content" id="countryFilterList"></div></div>
                    <div class="accordion-item"><button class="accordion-header"><span><i class="fas fa-map-marker-alt" style="margin-right:20px;"></i> VÙNG LÀM VANG</span><i class="fas fa-chevron-down accordion-icon"></i></button><div class="accordion-content" id="regionFilterList"></div></div>
                    <div class="accordion-item"><button class="accordion-header"><span><i class="fa-solid fa-list" style="margin-right:20px;"></i> GIỐNG NHO</span><i class="fas fa-chevron-down accordion-icon"></i></button><div class="accordion-content" id="grapeFilterList"></div></div>
                </div>
                <div id="spiritFilterGroup" style="display:none;">
                    <div class="accordion-item"><button class="accordion-header"><span><i class="fas fa-whiskey-glass"></i> LOẠI RƯỢU MẠNH</span><i class="fas fa-chevron-down accordion-icon"></i></button><div class="accordion-content" id="spiritTypeFilterList"></div></div>
                    <div class="accordion-item"><button class="accordion-header"><span><i class="fas fa-trademark"></i> THƯƠNG HIỆU</span><i class="fas fa-chevron-down accordion-icon"></i></button><div class="accordion-content" id="brandFilterList"></div></div>
                </div>
            </div>
        </aside>

        <div class="product-listing-container">
            <main class="product-listing">
                <div class="category-breadcrumb">
                    <a href="index.php">Trang chủ</a> / 
                    <?php if (!empty($searchKeyword)): ?>
                        <span>Tìm kiếm: <?php echo htmlspecialchars($searchKeyword); ?></span>
                    <?php elseif (!empty($brandFilter)): ?>
                        <span>Thương hiệu: <?php echo htmlspecialchars($brandFilter); ?></span>
                    <?php elseif ($categoryId > 0 && $category): ?>
                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                    <?php else: ?>
                        <span>Tất cả sản phẩm</span>
                    <?php endif; ?>
                </div>
                
                <div class="category-description">
                    <?php if (!empty($searchKeyword)): ?>
                        <h2>Kết quả tìm kiếm cho "<?php echo htmlspecialchars($searchKeyword); ?>"</h2>
                        <p>Hiển thị sản phẩm phù hợp với từ khóa của bạn.</p>
                    <?php elseif (!empty($brandFilter)): ?>
                        <h2>Thương hiệu: <?php echo htmlspecialchars($brandFilter); ?></h2>
                        <p>Danh sách các sản phẩm thuộc thương hiệu <?php echo htmlspecialchars($brandFilter); ?>.</p>
                    <?php elseif (!$showDefaultDesc && $category): ?>
                        <h2><?php echo htmlspecialchars($category['name']); ?></h2>
                        <div class="description-content-wrapper">
                            <div class="description-content" id="catDescContent">
                                <?php 
                                $fullContent = fixDescriptionImages($category['description'] ?? '');
                                $plainText = strip_tags($fullContent);
                                $previewText = mb_substr($plainText, 0, 300);
                                $hasMore = mb_strlen($plainText) > 300;
                                ?>
                                <?php if ($hasMore): ?>
                                    <span class="preview-text"><?php echo htmlspecialchars($previewText); ?><span class="dots">...</span></span>
                                    <span class="full-text"><?php echo $fullContent; ?></span>
                                    <button class="read-more-btn" onclick="toggleDescription()">
                                        <span class="btn-text">Xem thêm</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                <?php else: ?>
                                    <?php echo $fullContent; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <h2>RƯỢU VANG</h2>
                        <div class="description-content-wrapper">
                            <div class="description-content" id="catDescContent">
                                <span class="preview-text">Rượu vang không chỉ là một món đồ uống, mà là một biểu tượng của sự tinh tế, văn hóa và lịch sử kéo dài hàng thiên niên kỷ. Trong thế giới đồ uống hiện đại, <strong>Wine</strong> chiếm một vị trí độc tôn nhờ sự đa dạng đến kinh ngạc về vùng trồng nho, giống nho và phong cách chế tác.<span class="dots">...</span></span>
                                <span class="full-text">Rượu vang không chỉ là một món đồ uống, mà là một biểu tượng của sự tinh tế, văn hóa và lịch sử kéo dài hàng thiên niên kỷ. Trong thế giới đồ uống hiện đại, <strong>Wine</strong> chiếm một vị trí độc tôn nhờ sự đa dạng đến kinh ngạc về vùng trồng nho, giống nho và phong cách chế tác. Từ những vườn nho cổ kính ở Pháp, Ý cho đến những vùng đất mới như Chile, Úc hay Mỹ, mỗi chai rượu vang đều mang trong mình câu chuyện riêng của đất và người nơi đó. Hương vị của rượu vang được hình thành từ vô vàn yếu tố: khí hậu, thổ nhưỡng, cách chăm sóc nho, và nghệ thuật ủ trong thùng gỗ sồi. Chính sự phong phú đó đã làm nên một thế giới rượu vang đầy màu sắc và hấp dẫn, thu hút hàng triệu tín đồ trên khắp thế giới.</span>
                                <button class="read-more-btn" onclick="toggleDescription()">
                                    <span class="btn-text">Xem thêm</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="listing-header">
                    <p class="result-count">Hiển thị <strong id="productCount">0</strong> trên tổng số <strong id="totalCount">0</strong> sản phẩm</p>
                    <div class="view-controls">
                        <div class="sort-options">
                            <select id="sort">
                                <option value="default">Mặc định</option>
                                <option value="price_asc">Giá thấp đến cao</option>
                                <option value="price_desc">Giá cao xuống thấp</option>
                                <option value="name_asc">Tên A-Z</option>
                                <option value="name_desc">Tên Z-A</option>
                                <option value="newest">Mới nhất</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div id="productsContainer" class="product-grid"><div class="loading-spinner"><i class="fas fa-spinner fa-pulse"></i> Đang tải sản phẩm...</div></div>
                <div class="pagination" id="pagination"></div>
            </main>
        </div>
    </div>

    <div class="promo-actions">
        <a href="lienhe.php" class="promo-item"><i class="fas fa-map-marker-alt"></i> TÌM CỬA HÀNG</a>
        <a href="#" class="promo-item open-offer-modal"><i class="fas fa-gift"></i> NHẬN ƯU ĐÃI</a>
        <a href="quatangdoanhnghiep.php" class="promo-item"><i class="fas fa-briefcase"></i> QUÀ DOANH NGHIỆP</a>
    </div>

    <footer class="main-footer">
        <div class="footer-container container">
            <div class="footer-section">
                <h4><i class="fas fa-store"></i> Ruouchat.vn</h4>
                <p>Địa chỉ: 600A Điện Biên Phủ, TP.HCM</p>
                <p>Điện thoại: <strong>0977 869 066</strong></p>
                <p>Email: vangchat.vn@gmail.com</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h4><i class="fas fa-list"></i> Danh Mục Nổi Bật</h4>
                <a href="/ruou-vang"><i class="fas fa-caret-right"></i> Rượu Vang</a>
                <a href="/ruou-manh"><i class="fas fa-caret-right"></i> Rượu Mạnh</a>
                <a href="/ruou-vang-phap"><i class="fas fa-caret-right"></i> Rượu Vang Pháp</a>
                <a href="/ruou-vang-y"><i class="fas fa-caret-right"></i> Rượu Vang Ý</a>
                <a href="/ruou-whisky"><i class="fas fa-caret-right"></i> Rượu Whisky</a>
                <a href="/ruou-cognac"><i class="fas fa-caret-right"></i> Rượu Cognac</a>
                <a href="/bia-ngoai"><i class="fas fa-caret-right"></i> Bia Ngoại</a>
            </div>
            <div class="footer-section">
                <h4><i class="fas fa-user-shield"></i> Chính Sách</h4>
                <a href="chinh-sach.php?slug=chinh-sach-bao-mat"><i class="fas fa-caret-right"></i> Chính sách bảo mật</a>
                <a href="chinh-sach.php?slug=chinh-sach-van-chuyen"><i class="fas fa-caret-right"></i> Chính sách vận chuyển</a>
                <a href="chinh-sach.php?slug=chinh-sach-doi-tra"><i class="fas fa-caret-right"></i> Chính sách đổi trả</a>
                <a href="chinh-sach.php?slug=dieu-khoan-su-dung"><i class="fas fa-caret-right"></i> Điều khoản sử dụng</a>
            </div>
            <div class="footer-section">
                <h4><i class="fas fa-envelope"></i> Đăng Ký Nhận Tin</h4>
                <p>Nhận thông tin khuyến mãi và sản phẩm mới từ Ruouchat.vn</p>
                <div class="newsletter">
                    <input type="email" placeholder="Email của bạn">
                    <button><i class="fas fa-paper-plane"></i></button>
                </div>
                <div class="payment-methods">
                    <a href="#"><i class="fab fa-cc-visa"></i></a>
                    <a href="#"><i class="fab fa-cc-mastercard"></i></a>
                    <a href="#"><i class="fab fa-cc-paypal"></i></a>
                    <a href="#"><i class="fas fa-credit-card"></i></a>
                    <a href="#"><i class="fas fa-qrcode"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2026 Ruouchat.vn - Chuyên Rượu Vang & Rượu Mạnh Nhập Khẩu Chính Hãng.</p>
        </div>
    </footer>

    <!-- FLOATING PROMO -->
    <div class="floating-promo" id="floatingPromo">
        <a href="lienhe.php" class="floating-item"><i class="fas fa-map-marker-alt"></i> Tìm cửa hàng</a>
        <a href="#" class="floating-item open-offer-modal"><i class="fas fa-gift"></i> Nhận ưu đãi</a>
        <a href="quatangdoanhnghiep.php" class="floating-item"><i class="fas fa-briefcase"></i> Quà doanh nghiệp</a>
    </div>

    <div id="offerModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-body">
                <div class="form-col" id="modalFormState">
                    <h3>Vui lòng để lại thông tin của bạn.</h3>
                    <p>Ruouchat.vn sẽ gửi ưu đãi cho quý khách</p>
                    <form id="offerForm">
                        <div class="form-group">
                            <label>Họ và Tên (bắt buộc)</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại (bắt buộc)</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <button type="submit" class="submit-btn">NHẬN ƯU ĐÃI</button>
                    </form>
                </div>
                <div id="modalSuccessState" style="display:none; width:100%;">
                    <i class="fas fa-check-circle"></i>
                    <h3>Đăng ký được gửi đi thành công</h3>
                    <p>Cảm ơn bạn đã quan tâm! Chúng tôi sẽ liên hệ sớm nhất.</p>
                    <button class="close-success-btn">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ==================== TOAST NOTIFICATION ====================
        let toastTimer = null;

        function showToast(title, message, duration = 3000) {
            const toast = document.getElementById('toastNotification');
            const titleEl = document.getElementById('toastTitle');
            const msgEl = document.getElementById('toastMessage');
            if (!toast || !titleEl || !msgEl) return;
            titleEl.textContent = title;
            msgEl.textContent = message;
            toast.classList.add('show');
            if (toastTimer) clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                toast.classList.remove('show');
            }, duration);
        }

        function closeToast() {
            const toast = document.getElementById('toastNotification');
            if (toast) toast.classList.remove('show');
            if (toastTimer) clearTimeout(toastTimer);
        }

        // ==================== FIX MENU STRUCTURE ====================
        (function fixMenuStructure() {
            function applyFix() {
                document.querySelectorAll('.nav-item').forEach(item => {
                    const dropdown = item.querySelector(':scope > .dropdown-menu');
                    if (dropdown) {
                        if (!item.classList.contains('has-dropdown')) item.classList.add('has-dropdown');
                        if (!item.querySelector(':scope > .mobile-arrow')) {
                            const arrowSpan = document.createElement('span');
                            arrowSpan.className = 'mobile-arrow';
                            arrowSpan.innerHTML = '<i class="fas fa-chevron-down"></i>';
                            const link = item.querySelector(':scope > .nav-link');
                            if (link) link.insertAdjacentElement('afterend', arrowSpan);
                            else item.appendChild(arrowSpan);
                        }
                    }
                });
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', applyFix);
            else applyFix();
        })();

        // ==================== MOBILE MENU HANDLER ====================
        (function mobileMenu() {
            const mobileToggle = document.getElementById('mobileMenuToggle');
            const navList = document.getElementById('navList');
            function isMobile() { return window.innerWidth <= 992; }
            function closeAllDropdowns() {
                document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.remove('show-mobile-dropdown'));
                document.querySelectorAll('.mobile-arrow').forEach(arrow => arrow.classList.remove('active'));
            }
            function toggleDropdown(parent) {
                if (!parent) return;
                const dropdown = parent.querySelector(':scope > .dropdown-menu');
                const arrow = parent.querySelector('.mobile-arrow');
                if (!dropdown) return;
                const isOpen = dropdown.classList.contains('show-mobile-dropdown');
                closeAllDropdowns();
                if (!isOpen) {
                    dropdown.classList.add('show-mobile-dropdown');
                    if (arrow) arrow.classList.add('active');
                }
            }
            document.addEventListener('click', function(e) {
                if (!isMobile()) return;
                const arrow = e.target.closest('.mobile-arrow');
                if (arrow) {
                    e.preventDefault();
                    e.stopPropagation();
                    const parent = arrow.closest('.has-dropdown');
                    toggleDropdown(parent);
                }
            });
            if (mobileToggle && navList) {
                mobileToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    navList.classList.toggle('show-mobile');
                    const icon = this.querySelector('i');
                    if (navList.classList.contains('show-mobile')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                        closeAllDropdowns();
                    }
                });
            }
            document.addEventListener('click', function(e) {
                if (!isMobile()) return;
                if (!e.target.closest('.header')) {
                    if (navList) navList.classList.remove('show-mobile');
                    closeAllDropdowns();
                    const icon = mobileToggle?.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                }
            });
            window.addEventListener('resize', function() {
                if (!isMobile()) {
                    if (navList) navList.classList.remove('show-mobile');
                    closeAllDropdowns();
                    const icon = mobileToggle?.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                }
            });
        })();

        // ========== HEADER HIDE/SHOW ==========
        let lastScroll = 0;
        const header = document.querySelector('.header');
        const scrollThreshold = 100;

        function handleHeaderScroll() {
            let currentScroll = window.pageYOffset;
            if (currentScroll > scrollThreshold && currentScroll > lastScroll) {
                header.classList.add('hide');
                header.classList.remove('show');
            } else if (currentScroll < lastScroll || currentScroll <= 20) {
                header.classList.add('show');
                header.classList.remove('hide');
            }
            lastScroll = currentScroll;
            adjustMainContainerOffset();
        }
        window.addEventListener('scroll', handleHeaderScroll);
        setTimeout(() => {
            if (window.scrollY <= 20) {
                header.classList.add('show');
                header.classList.remove('hide');
            } else if (window.scrollY > scrollThreshold) {
                header.classList.add('hide');
                header.classList.remove('show');
            }
            adjustMainContainerOffset();
        }, 100);
        function adjustMainContainerOffset() {
            const headerElem = document.querySelector('.header');
            const mainContainer = document.querySelector('.main-container');
            if (headerElem && mainContainer) {
                let headerHeight = headerElem.offsetHeight;
                mainContainer.style.marginTop = (headerHeight + 30) + 'px';
            }
        }
        window.addEventListener('load', adjustMainContainerOffset);
        window.addEventListener('resize', adjustMainContainerOffset);
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== "#" && href !== "#!") {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });

        // ========== SEARCH SUGGESTIONS ==========
        const suggestionsList = ["wine", "1855", "rượu vang", "cửa hàng rượu vang", "nhà cung cấp rượu vang","rượu vang ý", "rượu vang pháp", "rượu vang mỹ", "San Marzano","vang Ý cao cấp", "vang Ý nhập khẩu", "Rượu vang Pháp", "Bordeaux","Grand Cru Classé", "vang Pháp chính hãng", "Rượu vang nhập khẩu","Rượu vang Pháp cao cấp", "Rượu vang Ý chính hãng", "Wine", "1855"];
        const uniqueSuggestions = [...new Map(suggestionsList.map(s => [s.toLowerCase(), s])).values()];
        const searchInput = document.getElementById('searchInput');
        const suggestionsBox = document.getElementById('searchSuggestions');
        const searchForm = document.getElementById('searchForm');
        function showSuggestions() {
            if (!searchInput.value.trim()) { renderSuggestions(uniqueSuggestions); suggestionsBox.classList.add('active'); } 
            else { const keyword = searchInput.value.trim().toLowerCase(); const filtered = uniqueSuggestions.filter(s => s.toLowerCase().includes(keyword)); if (filtered.length > 0) { renderSuggestions(filtered); suggestionsBox.classList.add('active'); } else { suggestionsBox.classList.remove('active'); } }
        }
        function renderSuggestions(items) {
            if (!items || items.length === 0) { suggestionsBox.innerHTML = ''; suggestionsBox.classList.remove('active'); return; }
            let html = `<div class="suggestions-header">ĐỀ XUẤT PHỔ BIẾN</div><div class="suggestions-grid">`;
            items.forEach(item => { html += `<div class="suggestion-item" data-value="${escapeHtml(item)}">${escapeHtml(item)}</div>`; });
            html += `</div><div class="suggestion-search-example" data-value="Rượu vang Ý chính hãng"><i class="fas fa-search"></i> Tìm kiếm Rượu vang Ý chính hãng</div>`;
            suggestionsBox.innerHTML = html;
            document.querySelectorAll('.suggestion-item, .suggestion-search-example').forEach(el => { el.addEventListener('click', function(e) { const value = this.getAttribute('data-value'); searchInput.value = value; suggestionsBox.classList.remove('active'); searchForm.submit(); }); });
        }
        function escapeHtml(str) { if(!str) return ''; return str.replace(/[&<>]/g, m => m==='&'?'&amp;':m==='<'?'&lt;':m==='>'?'&gt;':m); }

        // ========== HÀM LẤY TÊN GIỐNG NHO / QUỐC GIA / VÙNG TỪ ID (đồng bộ với trang chi tiết sản phẩm) ==========
        function getGrapeName(grapeId) {
            const grapeMap = {
                1: 'Cabernet Sauvignon', 2: 'Merlot', 3: 'Pinot Noir', 4: 'Syrah / Shiraz',
                5: 'Chardonnay', 6: 'Sauvignon Blanc', 7: 'Riesling', 8: 'Sangiovese',
                9: 'Nebbiolo', 10: 'Tempranillo', 11: 'Garnacha', 12: 'Malbec',
                13: 'Carménère', 14: 'Primitivo', 15: 'Barbera', 16: 'Montepulciano',
                17: 'Corvina', 18: 'Zinfandel', 19: 'Petit Verdot', 20: 'Viognier',
                21: 'Chenin Blanc', 22: 'Semillon', 23: 'Muscat', 24: 'Gewürztraminer',
                25: 'Pinot Grigio'
            };
            if (typeof grapeId === 'string' && /^\d+$/.test(grapeId)) return grapeMap[parseInt(grapeId)] || 'Giống nho #' + grapeId;
            if (typeof grapeId === 'number') return grapeMap[grapeId] || 'Giống nho #' + grapeId;
            return grapeId || 'Chưa cập nhật';
        }
        function getCountryName(countryId) {
            if (!countryId) return 'Chưa cập nhật';
            if (typeof countryId === 'string' && /^\d+$/.test(countryId)) return 'Quốc gia #' + countryId;
            if (typeof countryId === 'number') return 'Quốc gia #' + countryId;
            return countryId;
        }
        function getRegionName(regionId) {
            if (!regionId) return 'Chưa cập nhật';
            if (typeof regionId === 'string' && /^\d+$/.test(regionId)) return 'Vùng #' + regionId;
            if (typeof regionId === 'number') return 'Vùng #' + regionId;
            return regionId;
        }

        if (searchInput) {
            searchInput.addEventListener('focus', showSuggestions);
            searchInput.addEventListener('input', showSuggestions);
            document.addEventListener('click', function(e) { if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) { suggestionsBox.classList.remove('active'); } });
        }

        // ========== CART FUNCTIONS ==========
        async function updateCartCount() {
            try {
                const res = await fetch('api/cart.php', { credentials: 'include' });
                const cart = await res.json();
                const totalQty = Object.values(cart).reduce((sum, item) => sum + (item.quantity || 0), 0);
                const badge = document.querySelector('.cart-count');
                if (badge) badge.innerText = totalQty;
            } catch(e) { console.warn('Cart count error:', e); }
        }
        updateCartCount();

        // ========== WISHLIST COUNT ==========
        async function getWishlistFromDB() {
            try {
                const res = await fetch('api/wishlist.php', { 
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' }
                });
                if (res.status === 401) return [];
                const data = await res.json();
                if (data.success && Array.isArray(data.data)) {
                    return data.data.map(item => item.product_id);
                }
                return [];
            } catch(e) {
                console.warn('Wishlist fetch error:', e);
                return [];
            }
        }
        async function updateWishlistCount() {
            try {
                const wishlist = await getWishlistFromDB();
                const count = wishlist.length;
                const wishlistSpan = document.querySelector('.wishlist-count');
                if (wishlistSpan) {
                    wishlistSpan.innerText = count;
                    wishlistSpan.style.display = count > 0 ? 'flex' : 'none';
                }
            } catch(e) {
                console.warn('Update wishlist count error:', e);
                const wishlistSpan = document.querySelector('.wishlist-count');
                if (wishlistSpan) wishlistSpan.style.display = 'none';
            }
        }
        updateWishlistCount();

        // ========== USER DROPDOWN ==========
        async function checkLoginStatus() {
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'check' }),
                    credentials: 'include'
                });
                const data = await response.json();
                return data.logged_in ? data.user : null;
            } catch(e) { return null; }
        }
        async function performLogout() {
            await fetch('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' }),
                credentials: 'include'
            });
            updateUserMenu(null);
        }
        function updateUserMenu(user) {
            const userMenu = document.getElementById('userMenu');
            if (!userMenu) return;
            if (user) {
                userMenu.innerHTML = `<li><a href="account.php"><i class="fas fa-user-circle"></i> Tài khoản</a></li><li><a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>`;
                const logoutBtn = document.getElementById('logoutBtn');
                if (logoutBtn) logoutBtn.addEventListener('click', (e) => { e.preventDefault(); performLogout(); });
            } else {
                userMenu.innerHTML = `<li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a></li><li><a href="register.php"><i class="fas fa-user-plus"></i> Đăng ký</a></li>`;
            }
        }
        (async function() {
            const currentUser = await checkLoginStatus();
            updateUserMenu(currentUser);
        })();

        // ========== MODAL ƯU ĐÃI ==========
        const modal = document.getElementById('offerModal');
        const openBtns = document.querySelectorAll('.open-offer-modal');
        const closeBtn = document.querySelector('.close-modal');
        const formState = document.getElementById('modalFormState');
        const successState = document.getElementById('modalSuccessState');
        const offerForm = document.getElementById('offerForm');
        const closeSuccessBtn = document.querySelector('.close-success-btn');
        function closeModal() {
            if (modal) modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            if (offerForm) offerForm.reset();
        }
        if (openBtns.length) {
            openBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (formState) formState.style.display = 'block';
                    if (successState) successState.style.display = 'none';
                    if (modal) modal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                });
            });
        }
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (modal) {
            window.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });
        }
        if (offerForm) {
            offerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const name = document.getElementById('name').value.trim();
                const phone = document.getElementById('phone').value.trim();
                if (name === '' || phone === '') {
                    alert('Vui lòng điền đầy đủ họ tên và số điện thoại.');
                    return;
                }
                if (formState) formState.style.display = 'none';
                if (successState) successState.style.display = 'block';
            });
        }
        if (closeSuccessBtn) closeSuccessBtn.addEventListener('click', closeModal);

        // ========== FLOATING PROMO ==========
        const floatingPromo = document.getElementById('floatingPromo');
        if (floatingPromo && window.innerWidth >= 769) {
            let lastScrollTop = 0;
            window.addEventListener('scroll', function() {
                let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    floatingPromo.classList.remove('show');
                } else {
                    floatingPromo.classList.add('show');
                }
                lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            });
            setTimeout(() => {
                if (window.scrollY > 100) floatingPromo.classList.add('show');
            }, 500);
        }

        // ========== TOGGLE DESCRIPTION ==========
        function toggleDescription() {
            const descContent = document.getElementById('catDescContent');
            const btn = descContent.querySelector('.read-more-btn');
            const btnText = btn.querySelector('.btn-text');
            const icon = btn.querySelector('i');
            descContent.classList.toggle('expanded');
            if (descContent.classList.contains('expanded')) {
                btnText.textContent = 'Thu gọn';
                icon.style.transform = 'rotate(180deg)';
            } else {
                btnText.textContent = 'Xem thêm';
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // ========== HIỆU ỨNG PULSE CHO HOTLINE ==========
        document.addEventListener('DOMContentLoaded', function() {
            const hotlineItems = document.querySelectorAll('.hotline-item');
            hotlineItems.forEach((item, index) => {
                item.classList.remove('pulse');
                setTimeout(() => {
                    item.classList.add('pulse');
                }, index * 500);
            });
        });

        // ========== CÁC HÀM XỬ LÝ SẢN PHẨM ==========
        function getUrlParam(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }

        // ===== TRUYỀN DỮ LIỆU TỪ PHP SANG JS =====
        const currentCategoryId = <?php echo isset($categoryId) ? (int)$categoryId : 0; ?>;
        const searchKeyword = '<?php echo addslashes($searchKeyword); ?>';
        const brandFilter = '<?php echo addslashes($brandFilter); ?>'; // THÊM DÒNG NÀY
        const categoryMap = <?php echo json_encode($categoryMap); ?>;
        const wineCategories = <?php echo json_encode($wineCategories); ?>;
        const initialCountries = <?php echo json_encode($countries); ?>;
        const initialRegions = <?php echo json_encode($regions); ?>;
        const initialGrapeTypes = <?php echo json_encode($grapeTypes); ?>;
        const initialSpiritTypes = <?php echo json_encode($spiritTypes); ?>;
        const initialBrands = <?php echo json_encode($brands); ?>;

        let allProducts = [];
        let filteredProducts = [];
        let currentPage = 1;
        const itemsPerPage = 20;

        function slugify(text) {
            return String(text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
        }

        function getCategoryIdsForWineType(wineTypeName) {
            const targetSlug = slugify(wineTypeName);
            let matched = wineCategories.filter(cat => slugify(cat.name) === targetSlug || slugify(cat.slug) === targetSlug);
            if (matched.length === 0) {
                const map = { 
                    "Rượu vang đỏ": ["do","red"], 
                    "Rượu vang trắng": ["trang","white"], 
                    "Rượu vang hồng": ["hong","rose"], 
                    "Rượu vang ngọt": ["ngot","sweet"], 
                    "Rượu vang sủi": ["sui","sparkling"], 
                    "Rượu vang Moscato": ["moscato"] 
                };
                const aliases = map[wineTypeName] || [];
                matched = wineCategories.filter(cat => aliases.some(a => slugify(a) === slugify(cat.slug)));
            }
            return matched.map(cat => Number(cat.id));
        }

        // ===== HÀM LẤY ĐƯỜNG DẪN ẢNH CHÍNH XÁC =====
        function getImageUrl(imgPath) {
            if (!imgPath || imgPath === '') return 'https://placehold.co/400x400?text=No+Image';
            if (imgPath.startsWith('http://') || imgPath.startsWith('https://')) return imgPath;
            if (imgPath.startsWith('/')) return imgPath;
            if (imgPath.startsWith('uploads/')) return '/' + imgPath;
            if (imgPath.startsWith('images/')) return '/' + imgPath;
            if (imgPath.includes('../uploads/')) return '/' + imgPath.replace('../', '');
            if (imgPath.includes('uploads/')) {
                const uploadsIndex = imgPath.indexOf('uploads/');
                return '/' + imgPath.substring(uploadsIndex);
            }
            return '/uploads/' + imgPath;
        }

        // Hàm thêm vào giỏ hàng với toast notification
        async function addToCart(product_id) {
            try {
                const res = await fetch('api/cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add', product_id: product_id, quantity: 1 }),
                    credentials: 'include'
                });
                const data = await res.json();
                if (data.success) {
                    await updateCartCount();
                    showToast('✅ Thành công!', 'Đã thêm sản phẩm vào giỏ hàng!');
                } else {
                    showToast('❌ Lỗi!', data.message || 'Thêm vào giỏ hàng thất bại');
                }
            } catch(e) {
                showToast('❌ Lỗi!', 'Có lỗi xảy ra, vui lòng thử lại');
            }
        }

        // Hàm format giá - hiển thị "Liên hệ" nếu không có giá
        function formatPriceDisplay(price) {
            if (price === null || price === undefined || price === 0 || price === '') {
                return '<span class="contact-price-badge"><i class="fas fa-phone me-1"></i>Liên hệ</span>';
            }
            return new Intl.NumberFormat('vi-VN').format(price) + ' ₫';
        }

        function buildWineTypeFilter(products) {
            const container = document.getElementById('wineTypeFilterList');
            if (!container) return;
            const wineTypeNames = wineCategories.map(c => c.name);
            const counts = {};
            wineTypeNames.forEach(type => {
                const ids = getCategoryIdsForWineType(type);
                let count = 0;
                products.forEach(p => {
                    const prodCatIds = String(p.category_ids || p.category_id || '').split(',').map(id => Number(id.trim())).filter(id => !isNaN(id) && id > 0);
                    if (prodCatIds.some(id => ids.includes(id))) count++;
                });
                counts[type] = count;
            });
            let html = `<div class="filter-list">`;
            wineTypeNames.forEach(type => {
                html += `<label class="filter-item"><input type="checkbox" value="${escapeHtml(type)}" data-filter="wine_type_fixed"><span class="filter-text">${escapeHtml(type)}</span><span class="count">(${counts[type] || 0})</span></label>`;
            });
            html += `</div>`;
            container.innerHTML = html;
        }

        function buildFilterList(items, containerId, filterKey, products) {
            const container = document.getElementById(containerId);
            if (!container) return;
            if (!items || items.length === 0) {
                container.innerHTML = '<div class="filter-list">Không có dữ liệu</div>';
                return;
            }
            const counts = {};
            items.forEach(val => { counts[val] = 0; });
            products.forEach(p => {
                const val = p[filterKey];
                if (val && counts[val] !== undefined) counts[val]++;
            });
            let html = `<div class="filter-list">`;
            items.forEach(val => {
                html += `<label class="filter-item"><input type="checkbox" value="${escapeHtml(val)}" data-filter="${filterKey}"><span class="filter-text">${escapeHtml(val)}</span><span class="count">(${counts[val] || 0})</span></label>`;
            });
            html += `</div>`;
            container.innerHTML = html;
        }

        function buildSpiritFilters(products) {
            const spiritContainer = document.getElementById('spiritTypeFilterList');
            const brandContainer = document.getElementById('brandFilterList');
            if (spiritContainer) {
                let items = [...new Set(products.map(p => p.spirit_type).filter(v => v))];
                if (items.length === 0 && initialSpiritTypes.length) items = initialSpiritTypes;
                buildFilterList(items, 'spiritTypeFilterList', 'spirit_type', products);
            }
            if (brandContainer) {
                let items = [...new Set(products.map(p => p.brand).filter(v => v))];
                if (items.length === 0 && initialBrands.length) items = initialBrands;
                buildFilterList(items, 'brandFilterList', 'brand', products);
            }
        }

        function updateFilterCounts(products) {
            updateFilterCount('countryFilterList', 'country', products);
            updateFilterCount('regionFilterList', 'region', products);
            updateFilterCount('grapeFilterList', 'grape_type', products);
            updateFilterCount('spiritTypeFilterList', 'spirit_type', products);
            updateFilterCount('brandFilterList', 'brand', products);
            updateWineTypeFilterCount(products);
        }

        function updateFilterCount(containerId, filterKey, products) {
            const container = document.getElementById(containerId);
            if (!container) return;
            const counts = {};
            products.forEach(p => {
                const val = p[filterKey];
                if (val && val.trim()) {
                    counts[val] = (counts[val] || 0) + 1;
                }
            });
            const checkboxes = container.querySelectorAll('.filter-item input[type="checkbox"]');
            checkboxes.forEach(cb => {
                const value = cb.value;
                const countSpan = cb.closest('.filter-item')?.querySelector('.count');
                if (countSpan) {
                    const count = counts[value] || 0;
                    countSpan.textContent = `(${count})`;
                }
            });
        }

        function updateWineTypeFilterCount(products) {
            const container = document.getElementById('wineTypeFilterList');
            if (!container) return;
            const wineTypeNames = wineCategories.map(c => c.name);
            const counts = {};
            wineTypeNames.forEach(type => {
                const ids = getCategoryIdsForWineType(type);
                let count = 0;
                products.forEach(p => {
                    const prodCatIds = String(p.category_ids || p.category_id || '').split(',').map(id => Number(id.trim())).filter(id => !isNaN(id) && id > 0);
                    if (prodCatIds.some(id => ids.includes(id))) count++;
                });
                counts[type] = count;
            });
            const checkboxes = container.querySelectorAll('.filter-item input[type="checkbox"]');
            checkboxes.forEach(cb => {
                const value = cb.value;
                const countSpan = cb.closest('.filter-item')?.querySelector('.count');
                if (countSpan) {
                    const count = counts[value] || 0;
                    countSpan.textContent = `(${count})`;
                }
            });
        }

        function updatePriceRangeCounts(products) {
            const ranges = [
                { id: 'under2', min: 0, max: 2000000 },
                { id: '2to6', min: 2000000, max: 6000000 },
                { id: '6to15', min: 6000000, max: 15000000 },
                { id: '15to20', min: 15000000, max: 20000000 },
                { id: 'over20', min: 20000000, max: Infinity }
            ];
            ranges.forEach(range => {
                const count = products.filter(p => p.price >= range.min && p.price < range.max).length;
                const cb = document.querySelector(`#priceRangeCheckboxes input[value="${range.id}"]`);
                if (cb) {
                    const label = cb.closest('.price-range-item');
                    if (label) {
                        let countSpan = label.querySelector('.range-count');
                        if (!countSpan) {
                            countSpan = document.createElement('span');
                            countSpan.className = 'range-count';
                            countSpan.style.cssText = 'margin-left: 8px; color: #6c757d; font-size: 12px;';
                            label.appendChild(countSpan);
                        }
                        countSpan.textContent = `(${count})`;
                    }
                }
            });
        }

        function getSelectedFilters() {
            const selected = { wine_type_fixed: [], country: [], region: [], grape_type: [], spirit_type: [], brand: [] };
            document.querySelectorAll('.filter-item input[type="checkbox"]:checked').forEach(cb => {
                const key = cb.getAttribute('data-filter');
                if (key && selected[key] !== undefined) selected[key].push(cb.value);
            });
            return selected;
        }

        function getSelectedPriceRanges() {
            const ranges = [];
            document.querySelectorAll('#priceRangeCheckboxes input:checked').forEach(cb => {
                const val = cb.value;
                if (val === 'under2') ranges.push(p => p < 2000000);
                else if (val === '2to6') ranges.push(p => p >= 2000000 && p <= 6000000);
                else if (val === '6to15') ranges.push(p => p >= 6000000 && p <= 15000000);
                else if (val === '15to20') ranges.push(p => p >= 15000000 && p <= 20000000);
                else if (val === 'over20') ranges.push(p => p > 20000000);
            });
            return ranges;
        }

        function applyFilters() {
            const selected = getSelectedFilters();
            const priceFilters = getSelectedPriceRanges();
            
            let selectedCatIds = [];
            selected.wine_type_fixed.forEach(wt => {
                selectedCatIds.push(...getCategoryIdsForWineType(wt));
            });
            selectedCatIds = [...new Set(selectedCatIds)];
            
            const urlCatId = <?php echo isset($categoryId) ? (int)$categoryId : 0; ?>;
            const brandFilterVal = '<?php echo addslashes($brandFilter); ?>';
            
            let filtered = allProducts.filter(p => {
                // Lọc theo category
                if (urlCatId > 0) {
                    const prodCatIds = String(p.category_ids || p.category_id || '').split(',').map(id => Number(id.trim())).filter(id => !isNaN(id) && id > 0);
                    if (!prodCatIds.includes(urlCatId)) return false;
                }
                // Lọc theo wine type
                if (selectedCatIds.length) {
                    const prodCatIds = String(p.category_ids || p.category_id || '').split(',').map(id => Number(id.trim())).filter(id => !isNaN(id) && id > 0);
                    if (!prodCatIds.some(id => selectedCatIds.includes(id))) return false;
                }
                // Lọc theo brand từ URL (ưu tiên hơn)
                if (brandFilterVal) {
                    const pBrand = (p.brand || '').toLowerCase();
                    const pSupplier = (p.supplier_name || '').toLowerCase();
                    const pProducer = (p.producer || '').toLowerCase();
                    const filterVal = brandFilterVal.toLowerCase();
                    if (pBrand !== filterVal && pSupplier !== filterVal && pProducer !== filterVal) return false;
                }
                // Lọc theo các filter checkbox
                if (selected.country.length && !selected.country.includes(p.country)) return false;
                if (selected.region.length && !selected.region.includes(p.region)) return false;
                if (selected.grape_type.length && !selected.grape_type.includes(p.grape_type)) return false;
                if (selected.spirit_type.length && !selected.spirit_type.includes(p.spirit_type)) return false;
                if (selected.brand.length && !selected.brand.includes(p.brand)) return false;
                
                // Lọc theo giá
                if (priceFilters.length && !priceFilters.some(fn => fn(p.price))) return false;
                return true;
            });
            
            const sortVal = document.getElementById('sort').value;
            if (sortVal === 'price_asc') filtered.sort((a,b) => a.price - b.price);
            else if (sortVal === 'price_desc') filtered.sort((a,b) => b.price - a.price);
            else if (sortVal === 'name_asc') filtered.sort((a,b) => a.name.localeCompare(b.name, 'vi'));
            else if (sortVal === 'name_desc') filtered.sort((a,b) => b.name.localeCompare(a.name, 'vi'));
            else if (sortVal === 'newest') filtered.sort((a,b) => (b.id || 0) - (a.id || 0));
            
            filteredProducts = filtered;
            const totalElem = document.getElementById('totalCount');
            if (totalElem) totalElem.innerText = filteredProducts.length;
            const totalPages = Math.ceil(filteredProducts.length / itemsPerPage);
            if (currentPage > totalPages) currentPage = totalPages || 1;
            renderProductsOnPage();
            renderPagination();
        }

        function renderProductsOnPage() {
            const start = (currentPage - 1) * itemsPerPage;
            const pageProducts = filteredProducts.slice(start, start + itemsPerPage);
            const productCountElem = document.getElementById('productCount');
            if (productCountElem) productCountElem.innerText = pageProducts.length;
            const container = document.getElementById('productsContainer');
            if (!container) return;
            container.innerHTML = '';
            if (pageProducts.length === 0) {
                container.innerHTML = '<div class="error-message"><i class="fas fa-info-circle"></i> Không tìm thấy sản phẩm.</div>';
                return;
            }
            pageProducts.forEach(p => {
                let rightBadge = '';
                const stock = p.stock_quantity || 0;
                const displayStatus = p.display_status || 'visible';
                if (displayStatus === 'coming_soon') rightBadge = '<div class="product-badge-stock soon">Sắp về</div>';
                else if (displayStatus === 'discontinued') rightBadge = '<div class="product-badge-stock discontinued">Ngừng bán</div>';
                else if (stock <= 0) rightBadge = '<div class="product-badge-stock outstock">Hết hàng</div>';
                const leftBadge = p.old_price ? 'Giảm giá' : 'Mới';
                const detailLink = `/${p.slug}`;

                // ====== GIỐNG NHO ======
                let grape = p.grape_name || p.grape_type_display || p.grape_type || 'Chưa cập nhật';
                if (typeof grape === 'string' && /^\d+$/.test(grape)) grape = getGrapeName(grape);

                // ====== PHÂN LOẠI ======
                let wineType = p.wine_type || '';
                if (!wineType && p.category_id) {
                    const catIds = String(p.category_ids || p.category_id).split(',').map(Number);
                    const matched = wineCategories.find(c => catIds.includes(c.id));
                    if (matched) wineType = matched.name;
                }
                if (!wineType) wineType = p.type || 'Rượu vang';

                // ====== NSX / THƯƠNG HIỆU ======
                const producer = p.producer_name || p.nsx || p.brand || p.supplier_name || p.producer || 'Chưa cập nhật';

                // ====== XUẤT XỨ ======
                let country = p.country_name || p.country_display || p.country || p.xuat_xu || '';
                if (typeof country === 'string' && /^\d+$/.test(country)) country = getCountryName(country);
                if (!country || country === 'Chưa cập nhật') {
                    let region = p.region_name || p.region_display || p.region || '';
                    if (typeof region === 'string' && /^\d+$/.test(region)) region = getRegionName(region);
                    country = region || 'Chưa cập nhật';
                }

                // ====== NỒNG ĐỘ ======
                const alcohol = p.abv || p.alcohol || p.nong_do || '13%';

                // ====== DUNG TÍCH ======
                const volume = p.volume || p.dung_tich || '750ml';
                
                let imageUrl = p.main_image;
                if (!imageUrl) {
                    imageUrl = 'https://placehold.co/400x400?text=No+Image';
                } else if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
                    // giữ
                } else if (imageUrl.startsWith('/')) {
                    // giữ
                } else if (imageUrl.startsWith('uploads/')) {
                    imageUrl = '/' + imageUrl;
                } else if (imageUrl.includes('uploads/')) {
                    const uploadsIndex = imageUrl.indexOf('uploads/');
                    imageUrl = '/' + imageUrl.substring(uploadsIndex);
                } else {
                    imageUrl = '/uploads/' + imageUrl;
                }
                
                let priceHtml = '';
                if (p.price && p.price > 0) {
                    const priceFormatted = new Intl.NumberFormat('vi-VN').format(p.price);
                    if (p.old_price && p.old_price > 0) {
                        const oldPriceFormatted = new Intl.NumberFormat('vi-VN').format(p.old_price);
                        priceHtml = `
                            <span class="original-price-bottom">${oldPriceFormatted} ₫</span>
                            <span class="current-price-bottom">${priceFormatted} ₫</span>
                        `;
                    } else {
                        priceHtml = `<span class="current-price-bottom">${priceFormatted} ₫</span>`;
                    }
                } else {
                    priceHtml = `<span class="contact-price-badge"><i class="fas fa-phone me-1"></i>Liên hệ</span>`;
                }
                
                const card = document.createElement('div');
                card.className = 'product-card';
                card.innerHTML = `
                    <a href="${detailLink}" class="product-image-link">
                        <div class="product-badge">${escapeHtml(leftBadge)}</div>
                        ${rightBadge}
                        <img src="${imageUrl}" alt="${escapeHtml(p.name)}" class="product-image" loading="lazy" onerror="this.src='https://placehold.co/400x400?text=No+Image'">
                    </a>
                    <div class="product-info">
                        <h3><a href="${detailLink}">${escapeHtml(p.name)}</a></h3>
                    </div>
                    <div class="product-details">
                        <div class="detail-item"><i class="fa-solid fa-tag"></i><span>${escapeHtml(grape)}</span></div>
                        <div class="detail-item"><i class="fas fa-wine-glass-alt"></i><span>${escapeHtml(wineType)}</span></div>
                        <div class="detail-item"><i class="fas fa-building"></i><span>${escapeHtml(producer)}</span></div>
                        <div class="detail-item"><i class="fas fa-flag"></i><span>${escapeHtml(country)}</span></div>
                        <div class="detail-item"><i class="fas fa-percent"></i><span>${escapeHtml(alcohol)}</span></div>
                        <div class="detail-item"><i class="fas fa-flask"></i><span>${escapeHtml(volume)}</span></div>
                    </div>
                    <div class="product-bottom-row">
                        <div class="product-bottom-price">${priceHtml}</div>
                        <button class="btn-buy-now" data-id="${p.id}"><i class="fas fa-shopping-cart"></i> Mua ngay</button>
                    </div>
                `;
                container.appendChild(card);
            });
            
            document.querySelectorAll('.btn-buy-now').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const id = btn.getAttribute('data-id');
                    if (id) {
                        try {
                            const res = await fetch('api/cart.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'add', product_id: id, quantity: 1 }),
                                credentials: 'include'
                            });
                            const data = await res.json();
                            if (data.success) {
                                await updateCartCount();
                                showToast('✅ Thành công!', 'Đã thêm sản phẩm vào giỏ hàng!');
                            } else {
                                showToast('❌ Lỗi!', data.message || 'Thêm vào giỏ hàng thất bại');
                            }
                        } catch(e) {
                            showToast('❌ Lỗi!', 'Có lỗi xảy ra, vui lòng thử lại');
                        }
                    }
                });
            });
        }

        function renderPagination() {
            const total = Math.ceil(filteredProducts.length / itemsPerPage);
            const pagDiv = document.getElementById('pagination');
            if (!pagDiv) return;
            pagDiv.innerHTML = '';
            if (total <= 1) return;
            for (let i = 1; i <= total; i++) {
                const btn = document.createElement('div');
                btn.classList.add('page-number');
                if (i === currentPage) btn.classList.add('active');
                btn.innerText = i;
                btn.addEventListener('click', () => { 
                    currentPage = i; 
                    renderProductsOnPage(); 
                    renderPagination(); 
                    window.scrollTo({ top: 400, behavior: 'smooth' });
                });
                pagDiv.appendChild(btn);
            }
        }

        function resetFilters() {
            document.querySelectorAll('.filter-item input[type="checkbox"], #priceRangeCheckboxes input[type="checkbox"]').forEach(cb => cb.checked = false);
            const sortSelect = document.getElementById('sort');
            if (sortSelect) sortSelect.value = 'default';
            currentPage = 1;
            applyFilters();
        }

        function setView(view) {
            const container = document.getElementById('productsContainer');
            if (!container) return;
            const gridBtn = document.getElementById('gridViewBtn');
            const listBtn = document.getElementById('listViewBtn');
            if (view === 'grid') {
                container.classList.add('product-grid');
                container.classList.remove('product-list');
                if (gridBtn) gridBtn.classList.add('active');
                if (listBtn) listBtn.classList.remove('active');
            } else {
                container.classList.remove('product-grid');
                container.classList.add('product-list');
                if (listBtn) listBtn.classList.add('active');
                if (gridBtn) gridBtn.classList.remove('active');
            }
            renderProductsOnPage();
        }

        // ========== LOAD ALL PRODUCTS ==========
        async function loadAllProducts() {
            try {
                const categoryId = <?php echo isset($categoryId) ? (int)$categoryId : 0; ?>;
                let url = `api/products.php?all=1&_=${Date.now()}`;
                if (categoryId > 0) {
                    url += `&cat_id=${categoryId}`;
                }
                console.log('Fetching products from:', url);
                const res = await fetch(url);
                const data = await res.json();
                let products = [];
                if (data.products && Array.isArray(data.products)) products = data.products;
                else if (Array.isArray(data)) products = data;
                else throw new Error('Invalid API response');
                
                // Lọc theo category nếu có
                if (categoryId > 0) {
                    products = products.filter(p => {
                        const catIds = String(p.category_ids || p.category_id || '').split(',').map(id => Number(id.trim())).filter(id => !isNaN(id) && id > 0);
                        return catIds.includes(categoryId);
                    });
                }
                
                // ===== LỌC THEO BRAND TỪ URL (server-side đã lọc nhưng client-side vẫn lọc lại để đảm bảo) =====
                if (brandFilter && brandFilter.trim() !== '') {
                    const filterVal = brandFilter.toLowerCase();
                    products = products.filter(p => {
                        const pBrand = (p.brand || '').toLowerCase();
                        const pSupplier = (p.supplier_name || '').toLowerCase();
                        const pProducer = (p.producer || '').toLowerCase();
                        return pBrand === filterVal || pSupplier === filterVal || pProducer === filterVal;
                    });
                    console.log(`Lọc theo brand "${brandFilter}": ${products.length} sản phẩm`);
                }
                
                // ===== TÌM KIẾM THEO TỪ KHÓA (client-side) =====
                if (searchKeyword && searchKeyword.trim() !== '') {
                    const keyword = searchKeyword.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                    products = products.filter(p => {
                        const name = (p.name || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                        const desc = (p.description || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                        const catIds = String(p.category_ids || p.category_id || '').split(',').map(id => Number(id.trim())).filter(id => !isNaN(id) && id > 0);
                        let catNames = catIds.map(id => categoryMap[id] || '').join(' ');
                        catNames = catNames.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                        const brand = (p.brand || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                        const country = (p.country || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                        return name.includes(keyword) || desc.includes(keyword) || catNames.includes(keyword) || brand.includes(keyword) || country.includes(keyword);
                    });
                    console.log(`Tìm thấy ${products.length} sản phẩm cho từ khóa "${searchKeyword}"`);
                }
                
                allProducts = products.map(p => ({
                    ...p,
                    id: parseInt(p.id),
                    price: p.price !== null && p.price !== undefined ? Number(p.price) : null,
                    old_price: p.old_price ? Number(p.old_price) : null,
                    category_id: String(p.category_id || ''),
                    category_ids: String(p.category_ids || p.category_id || ''),
                    country: p.country || '',
                    region: p.region || '',
                    grape_type: p.grape_type || '',
                    alcohol: p.alcohol || p.abv || '13%',
                    supplier_name: p.supplier_name || '',
                    brand: p.brand || '',
                    producer: p.producer || '',
                    slug: p.slug || `sp-${p.id}`,
                    main_image: p.main_image && p.main_image.trim() ? p.main_image : null,
                    wine_type: p.wine_type || '',
                    spirit_type: p.spirit_type || '',
                    stock_quantity: p.stock_quantity || 0,
                    display_status: p.display_status || 'visible'
                }));
                
                // Build các bộ lọc
                buildWineTypeFilter(allProducts);
                
                const countries = [...new Set(allProducts.map(p => p.country).filter(v => v && v.trim()))];
                const regions = [...new Set(allProducts.map(p => p.region).filter(v => v && v.trim()))];
                const grapes = [...new Set(allProducts.map(p => p.grape_type).filter(v => v && v.trim()))];
                const spirits = [...new Set(allProducts.map(p => p.spirit_type).filter(v => v && v.trim()))];
                const brands = [...new Set(allProducts.map(p => p.brand).filter(v => v && v.trim()))];
                
                buildFilterList(countries.length ? countries : initialCountries, 'countryFilterList', 'country', allProducts);
                buildFilterList(regions.length ? regions : initialRegions, 'regionFilterList', 'region', allProducts);
                buildFilterList(grapes.length ? grapes : initialGrapeTypes, 'grapeFilterList', 'grape_type', allProducts);
                buildSpiritFilters(allProducts);
                
                updateFilterCounts(allProducts);
                updatePriceRangeCounts(allProducts);
                
                document.querySelectorAll('.filter-item input[type="checkbox"], #priceRangeCheckboxes input[type="checkbox"]').forEach(cb => {
                    cb.addEventListener('change', () => {
                        currentPage = 1;
                        applyFilters();
                    });
                });
                document.getElementById('sort')?.addEventListener('change', () => {
                    currentPage = 1;
                    applyFilters();
                });
                document.getElementById('resetAllFiltersBtn')?.addEventListener('click', resetFilters);
                
                applyFilters();
                await updateCartCount();
                await updateWishlistCount();
            } catch (err) {
                console.error('loadAllProducts error:', err);
                const container = document.getElementById('productsContainer');
                if (container) {
                    container.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i> Lỗi tải sản phẩm: ${err.message}</div>`;
                }
            }
        }

        // ========== DOM READY ==========
        document.addEventListener('DOMContentLoaded', async () => {
            await loadAllProducts();
            const gridBtn = document.getElementById('gridViewBtn');
            const listBtn = document.getElementById('listViewBtn');
            if (gridBtn) gridBtn.addEventListener('click', () => setView('grid'));
            if (listBtn) listBtn.addEventListener('click', () => setView('list'));
            const firstAccordion = document.querySelector('.accordion-item');
            if (firstAccordion) firstAccordion.classList.add('active');
            document.querySelectorAll('.accordion-item .accordion-header').forEach(header => {
                header.addEventListener('click', () => {
                    const item = header.closest('.accordion-item');
                    if (item) item.classList.toggle('active');
                });
            });
        });
    </script>
</body>
</html>