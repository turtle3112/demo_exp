<?php
require_once 'includes/menu.php'; 

// Lấy slug từ URL (ưu tiên query string, nếu không thì lấy từ pathname)
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$preview_mode = isset($_GET['preview']) && $_GET['preview'] == 1;

if (!$slug) {
    // Lấy từ PATH_INFO hoặc từ URI (hỗ trợ URL đẹp)
    $requestUri = $_SERVER['REQUEST_URI'];
    $path = parse_url($requestUri, PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    // Loại bỏ thư mục gốc nếu có
    $filtered = array_values(array_filter($segments, function($seg) {
        return $seg !== 'winecellar' && $seg !== 'ly-pha-le' && $seg !== 'sp2.php';
    }));
    if (!empty($filtered)) {
        $slug = end($filtered);
    }
}

$seo_title = '';
$seo_description = '';
$seo_image = '';
$seo_canonical = '';
$product = null;
$is_admin_preview = false;

// Kiểm tra xem có phải admin đang xem preview không
if ($preview_mode) {
    // Kiểm tra session admin
    session_start();
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        $is_admin_preview = true;
    }
    // Fallback: kiểm tra cookie admin
    if (!$is_admin_preview && isset($_COOKIE['admin_preview']) && $_COOKIE['admin_preview'] === '1') {
        $is_admin_preview = true;
    }
}

if ($slug) {
    // Gọi API nội bộ để lấy thông tin sản phẩm
    // Nếu là admin preview, gọi API admin để lấy cả sản phẩm ẩn
    $api_url = $is_admin_preview ? "api/admin_glasses.php?slug=" . urlencode($slug) : "api/glasses.php?slug=" . urlencode($slug);
    
    // Dùng file_get_contents nếu cho phép, fallback curl
    $response = null;
    if (ini_get('allow_url_fopen')) {
        $response = @file_get_contents($api_url);
    }
    if (!$response && function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
    }
    
    // Nếu không lấy được từ API, thử gọi trực tiếp database
    if (!$response) {
        // Fallback: kết nối database trực tiếp
        require_once 'api/config.php';
        if ($conn && !$conn->connect_error) {
            if ($is_admin_preview) {
                $stmt = $conn->prepare("SELECT * FROM crystal_glasses WHERE slug = ?");
            } else {
                $stmt = $conn->prepare("SELECT * FROM crystal_glasses WHERE slug = ? AND status = 1");
            }
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
            
            if ($product) {
                // Xử lý categories
                if (!empty($product['categories'])) {
                    $categories = json_decode($product['categories'], true);
                    $product['categories'] = is_array($categories) ? $categories : [];
                } else {
                    $product['categories'] = [];
                }
                $product['price'] = floatval($product['price']);
                $product['old_price'] = $product['old_price'] ? floatval($product['old_price']) : null;
                
                $seo_title = !empty($product['seo_title']) ? $product['seo_title'] : $product['name'] . ' - Ly pha lê Ruouchat';
                $seo_description = !empty($product['seo_description']) ? $product['seo_description'] : 'Ly pha lê cao cấp Riedel, thiết kế tinh tế, giao hàng toàn quốc.';
                $seo_image = !empty($product['main_image']) ? $product['main_image'] : 'https://ruouchat.vn/assets/images/logo.jpg';
                $seo_canonical = "https://ruouchat.vn/ly-pha-le/" . urlencode($slug);
                
                if ($is_admin_preview && isset($product['status']) && $product['status'] == 0) {
                    $seo_title = '[PREVIEW] ' . $seo_title;
                }
            }
        }
        $conn->close();
    } else {
        $product = json_decode($response, true);
        if ($product && !isset($product['error'])) {
            $seo_title = !empty($product['seo_title']) ? $product['seo_title'] : $product['name'] . ' - Ly pha lê Ruouchat';
            $seo_description = !empty($product['seo_description']) ? $product['seo_description'] : 'Ly pha lê cao cấp Riedel, thiết kế tinh tế, giao hàng toàn quốc.';
            $seo_image = !empty($product['main_image']) ? $product['main_image'] : 'https://ruouchat.vn/assets/images/logo.jpg';
            $seo_canonical = "https://ruouchat.vn/ly-pha-le/" . urlencode($slug);
            
            if ($is_admin_preview && isset($product['status']) && $product['status'] == 0) {
                $seo_title = '[PREVIEW] ' . $seo_title;
            }
        }
    }
}

// Fallback canonical
if (!$seo_canonical) {
    $seo_canonical = "https://ruouchat.vn/sp2.php?slug=" . urlencode($slug);
}

// Lấy danh sách categories để hiển thị tên đẹp
$CATEGORIES = [
    'ly-vang-do' => 'LY VANG ĐỎ',
    'ly-vang-trang' => 'LY VANG TRẮNG',
    'ly-champagne' => 'LY CHAMPAGNE',
    'ly-ruou-manh' => 'LY RƯỢU MẠNH',
    'ly-chan-mau' => 'LY CHÂN MÀU',
    'ly-cocktail' => 'LY COCKTAIL',
    'decanter' => 'DECANTER',
    'ly-thu-cong' => 'LY THỦ CÔNG'
];

// Hàm lấy tên category đẹp
function getCategoryName($slug, $CATEGORIES) {
    return isset($CATEGORIES[$slug]) ? $CATEGORIES[$slug] : $slug;
}

// Hàm format giá
function formatPrice($price) {
    if ($price === null || $price === '' || $price === 0) return '0 ₫';
    return number_format(floatval($price), 0, ',', '.') . ' ₫';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <?php if ($seo_title): ?>
    <title><?php echo htmlspecialchars($seo_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($seo_image); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($seo_canonical); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($seo_canonical); ?>">
    <?php else: ?>
    <title>Chi tiết ly pha lê | Ruouchat.vn</title>
    <meta name="description" content="Chi tiết sản phẩm ly pha lê cao cấp, chính hãng.">
    <?php endif; ?>
    
    <?php if ($is_admin_preview): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="https://winevn.com/images/favicon.png" type="image/x-icon">
    <style>
        /* ===== RESET & GLOBAL ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary-red: #990D23; --dark-red: #670011; --deep-red: #55000D; --gold: #FFBD27; --light-bg: #FBF8EE; --cream: #F2EFE2; --border-color: #E8E8E8; --text-dark: #212529; --text-light: #6C757D; --plum-red: #8B1E1E; }
        body { font-family: 'Inter', sans-serif; color: var(--text-dark); background: white; line-height: 1.6; overflow-x: hidden; padding-top: 0; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; width: 100%; }

        /* ===== TOAST NOTIFICATION ===== */
        .toast-notification {
            position: fixed;
            bottom: 100px;
            right: 20px;
            background: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 99999;
            display: none;
            max-width: 400px;
            border-left: 5px solid var(--primary-red);
            animation: slideUp 0.3s ease;
        }
        .toast-notification.show {
            display: block;
        }
        .toast-notification .toast-title {
            font-weight: 700;
            color: var(--primary-red);
            font-size: 16px;
            margin-bottom: 4px;
        }
        .toast-notification .toast-message {
            font-size: 14px;
            color: #555;
        }
        .toast-notification .toast-close {
            position: absolute;
            top: 8px;
            right: 12px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #999;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ===== RESPONSIVE CONTAINER ===== */
        @media (max-width: 1200px) { .container { padding: 0 16px; } }
        @media (max-width: 768px) { .container { padding: 0 12px; } }
        @media (max-width: 480px) { .container { padding: 0 10px; } }
        
        /* ===== HEADER 2 TẦNG VỚI HIỆU ỨNG ẨN PHẦN TRÊN (GIỐNG product_main) ===== */
        .header {
            background: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 0;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .header.hide .header-top {
            display: none;
        }
        
        .header.hide .header-bottom {
            margin-top: 0;
            border-top: none;
        }
        
        .header.show .header-top {
            display: flex;
        }
        
        .header.show .header-bottom {
            margin-top: 0;
            border-top: none;
        }
        
        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            flex-wrap: wrap;
            transition: all 0.2s ease;
            padding: 10px 0;
        }
        .logo { flex-shrink: 0; }
        .logo img { height: 42px; width: auto; display: block; max-width: 100%; }
        .search-bar { flex: 1; max-width: 700px; min-width: 200px; position: relative; }
        .search-form {
            display: flex;
            width: 100%;
            background: white;
            border-radius: 60px;
            border: 2px solid #000000;
            transition: all 0.2s;
        }
        .search-form:focus-within {
            border-color: #000000;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }
        .search-form input {
            flex: 1;
            padding: 12px 18px;
            border: none;
            background: transparent;
            border-radius: 60px 0 0 60px;
            font-size: 14px;
            outline: none;
        }
        .search-form button {
            background: transparent;
            border: none;
            padding: 0 18px;
            border-radius: 0 60px 60px 0;
            cursor: pointer;
            color: var(--text-dark);
            font-size: 18px;
        }
        .search-form button:hover { color: var(--primary-red); }

        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            border: 1px solid var(--border-color);
            z-index: 1050;
            max-height: 500px;
            overflow-y: auto;
            display: none;
            margin-top: 8px;
            padding: 16px 20px;
        }
        .search-suggestions.active { display: block; }
        .suggestions-header {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-red);
        }
        .suggestions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .suggestion-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 30px;
            padding: 8px 12px;
            font-size: 14px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .suggestion-item:hover {
            background: var(--primary-red);
            border-color: var(--primary-red);
            color: white;
        }
        .suggestion-search-example {
            background: #fff3e0;
            border: 1px solid var(--gold);
            border-radius: 30px;
            padding: 10px 16px;
            font-size: 14px;
            display: inline-block;
            cursor: pointer;
            transition: 0.2s;
        }
        .suggestion-search-example i { margin-right: 8px; color: var(--gold); }
        .suggestion-search-example:hover {
            background: var(--gold);
            color: var(--text-dark);
        }
        @media (max-width: 992px) {
            .suggestions-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 576px) {
            .suggestions-grid { grid-template-columns: 1fr; }
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-shrink: 0;
        }
        .nav-icon-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-dark);
            text-decoration: none;
            font-size: 22px;
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav-icon-link span { font-size: 14px; font-weight: 500; }
        .nav-icon-link:hover { color: var(--primary-red); }
        .cart-icon {
            position: relative;
            font-size: 24px;
            cursor: pointer;
        }
        .cart-icon a { color: var(--text-dark); text-decoration: none; }
        .cart-count {
            position: absolute;
            top: -10px;
            right: -12px;
            background: var(--primary-red);
            color: white;
            font-size: 11px;
            width: 19px;
            height: 19px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .wishlist-icon {
            position: relative;
            font-size: 24px;
            cursor: pointer;
        }
        .wishlist-icon a { color: var(--text-dark); text-decoration: none; }
        .wishlist-count {
            position: absolute;
            top: -10px;
            right: -12px;
            background: var(--primary-red);
            color: white;
            font-size: 11px;
            width: 19px;
            height: 19px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .user-dropdown {
            position: relative;
            cursor: pointer;
        }
        .user-icon { font-size: 26px; color: var(--text-dark); transition: 0.2s; }
        .user-icon:hover { color: var(--primary-red); }
        .dropdown-menu-user {
            position: absolute;
            top: 130%;
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            min-width: 190px;
            list-style: none;
            padding: 10px 0;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            border: 1px solid var(--border-color);
            z-index: 1050;
        }
        .user-dropdown:hover .dropdown-menu-user {
            opacity: 1;
            visibility: visible;
            top: 100%;
        }
        .dropdown-menu-user li { padding: 10px 22px; }
        .dropdown-menu-user li a {
            text-decoration: none;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            transition: 0.2s;
        }
        .dropdown-menu-user li a:hover {
            color: var(--primary-red);
            transform: translateX(4px);
        }

        .mobile-menu-toggle {
            display: none;
            background: transparent;
            border: none;
            font-size: 28px;
            color: var(--text-dark);
            cursor: pointer;
            padding: 0 5px;
            transition: color 0.2s;
        }
        .mobile-menu-toggle:hover { color: var(--primary-red); }

        .header-bottom {
            background: var(--dark-red);
            margin-top: 0;
            border-top: none;
            padding-top: 4px;
            padding-bottom: 4px;
            position: relative;
            width: 100vw;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            transition: margin-top 0.2s ease, border-top 0.2s ease;
        }
        .main-nav { width: 100%; }
        .nav-list {
            display: flex;
            list-style: none;
            gap: 4px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 0;
            padding: 0 8px;
        }
        .nav-item { 
            position: static;
            display: flex; 
            align-items: center; 
            flex-wrap: wrap; 
        }
        .nav-link {
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 14px;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
            border-radius: 40px;
            white-space: nowrap;
        }
        .nav-link:hover {
            color: var(--gold);
            background: rgba(255, 255, 255, 0.1);
        }
        .nav-link.highlight-text {
            color: var(--gold);
            font-weight: 700;
        }
        .nav-link.active {
            color: var(--gold) !important;
            background: rgba(255, 255, 255, 0.12);
        }
        
        .mobile-arrow { display: none; }
        
        /* ===== DROPDOWN MENU - 4 CỘT ===== */
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            width: 90vw;
            max-width: 1200px;
            background: white;
            padding: 25px 30px;
            display: none;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-top: 0px;
            box-shadow: 0 20px 35px rgba(0,0,0,0.15);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
            border-radius: 0;
        }
        @media (min-width: 993px) {
            .has-dropdown:hover .dropdown-menu { display: grid; }
        }
        .dropdown-menu::before {
            content: "";
            position: absolute;
            inset: 0;
            background: white;
            z-index: -1;
        }
        .dropdown-column { 
            min-width: 0; 
            padding: 0 10px;
        }
        .dropdown-column h3 {
            color: var(--primary-red);
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--primary-red);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .dropdown-column a {
            color: var(--text-dark);
            text-decoration: none;
            font-size: 13px;
            font-weight: 400;
            display: block;
            padding: 5px 0;
            transition: color 0.2s, padding-left 0.2s;
            word-break: break-word;
        }
        .dropdown-column a:hover {
            color: var(--primary-red);
            padding-left: 6px;
        }
        
        .dropdown-column a.active {
            color: var(--primary-red);
            font-weight: 700;
            padding-left: 6px;
        }

        @media (max-width: 1200px) {
            .dropdown-menu { grid-template-columns: repeat(2, 1fr); gap: 20px; width: 95vw; }
        }
        @media (max-width: 768px) {
            .dropdown-menu { grid-template-columns: 1fr; gap: 15px; }
        }

        /* MOBILE MENU STYLES */
        @media (max-width: 992px) {
            .header-bottom { position: relative; overflow: visible; }
            .mobile-menu-toggle { display: inline-flex; align-items: center; }
            .main-nav .nav-list {
                display: none;
                position: absolute;
                top: calc(100% + 10px);
                left: 0;
                right: 0;
                width: 100%;
                background: white;
                flex-direction: column;
                padding: 12px 0;
                gap: 6px;
                box-shadow: 0 15px 30px rgba(0,0,0,0.12);
                border-radius: 0 0 18px 18px;
                z-index: 2000;
                max-height: 85vh;
                overflow-y: auto;
                overflow-x: hidden;
                border-top: 1px solid var(--border-color);
            }
            .main-nav .nav-list.show-mobile { display: flex; }
            .main-nav .nav-list .nav-item {
                width: 100%;
                margin: 0;
                background: #f8f9fa;
                border-radius: 12px;
                overflow: visible;
            }
            .has-dropdown {
                position: relative;
                display: flex !important;
                flex-wrap: wrap;
                align-items: stretch;
                background: #f8f9fa;
                border-radius: 12px;
                overflow: visible;
            }
            .main-nav .nav-list .nav-link {
                flex: 1;
                min-height: 52px;
                padding: 12px 16px;
                display: flex;
                align-items: center;
                font-size: 15px;
                font-weight: 600;
                border-radius: 12px;
                background: #f8f9fa;
                color: var(--text-dark);
                text-decoration: none;
            }
            .mobile-arrow {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 52px;
                height: 52px;
                flex-shrink: 0;
                cursor: pointer;
                border-left: 1px solid #e0e0e0;
                transition: 0.2s;
                color: var(--text-dark);
            }
            .mobile-arrow i { transition: transform 0.25s ease; font-size: 14px; }
            .mobile-arrow.active i { transform: rotate(180deg); }
            .dropdown-menu {
                position: relative;
                width: 100%;
                min-width: 100%;
                margin-top: 0;
                padding: 10px 12px 12px 12px;
                background: #ffffff;
                border: none;
                box-shadow: none;
                border-radius: 0 0 12px 12px;
                display: none;
                grid-template-columns: 1fr;
                gap: 10px;
                order: 2;
                left: 0;
                transform: none;
                overflow-x: hidden;
                max-height: 60vh;
                overflow-y: auto;
            }
            .dropdown-menu.show-mobile-dropdown { display: grid !important; animation: dropdownFade 0.25s ease; }
            @keyframes dropdownFade {
                from { opacity: 0; transform: translateY(-8px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .dropdown-column {
                background: #fefefe;
                border-radius: 12px;
                padding: 12px;
                border: 1px solid #ececec;
                margin: 0;
                word-break: break-word;
                overflow-wrap: break-word;
            }
            .dropdown-column h3 {
                font-size: 14px;
                font-weight: 800;
                margin-bottom: 10px;
                padding-bottom: 6px;
                border-bottom: 1px solid var(--primary-red);
                color: var(--primary-red);
            }
            .dropdown-column a {
                display: block;
                padding: 8px 0;
                font-size: 14px;
                color: var(--text-dark);
                text-decoration: none;
                word-break: break-word;
            }
            .dropdown-column a:hover {
                color: var(--primary-red);
                padding-left: 6px;
            }
        }

        @media (max-width: 992px) {
            .header-top { gap: 10px; }
            .header-bottom { margin-top: 0 !important; padding-top: 3px !important; }
            
            .desktop-only {
                display: none !important;
            }
        }
        @media (max-width: 680px) {
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
        @media (max-width: 480px) {
            .header { padding: 0 !important; }
            .header-top { gap: 6px !important; padding: 4px 0 !important; }
            .logo img { height: 32px !important; }
            .search-bar { margin-top: 4px !important; }
            .header-right { gap: 8px !important; }
            .container { padding: 0 12px; }
        }

        /* ===== HOTLINE FLOATING BUTTON ===== */
        .hotline-float {
            position: fixed;
            right: 15px;
            bottom: 80px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .hotline-item {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary-red);
            color: white;
            text-decoration: none;
            font-size: 20px;
            box-shadow: 0 3px 12px rgba(153, 13, 35, 0.35);
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }

        .hotline-item:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 20px rgba(153, 13, 35, 0.5);
        }

        .hotline-item .tooltip {
            position: absolute;
            right: 58px;
            background: rgba(0, 0, 0, 0.85);
            color: white;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .hotline-item .tooltip::after {
            content: '';
            position: absolute;
            right: -6px;
            top: 50%;
            transform: translateY(-50%);
            border-left: 6px solid rgba(0, 0, 0, 0.85);
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
        }

        .hotline-item:hover .tooltip {
            opacity: 1;
            visibility: visible;
            right: 62px;
        }

        .hotline-item.zalo {
            background: #0068FF;
            box-shadow: 0 3px 12px rgba(0, 104, 255, 0.35);
        }
        .hotline-item.zalo:hover {
            box-shadow: 0 5px 20px rgba(0, 104, 255, 0.5);
        }
        .hotline-item.zalo .zalo-icon {
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hotline-item.zalo .zalo-icon svg {
            width: 100%;
            height: 100%;
            fill: white;
        }
        .hotline-item.messenger {
            background: #0084FF;
            box-shadow: 0 3px 12px rgba(0, 132, 255, 0.35);
        }
        .hotline-item.messenger:hover {
            box-shadow: 0 5px 20px rgba(0, 132, 255, 0.5);
        }
        .hotline-item.messenger i {
            font-size: 22px;
        }
        .hotline-item.phone {
            background: #25D366;
            box-shadow: 0 3px 12px rgba(37, 211, 102, 0.35);
        }
        .hotline-item.phone:hover {
            box-shadow: 0 5px 20px rgba(37, 211, 102, 0.5);
        }
        .hotline-item.phone i {
            font-size: 18px;
        }

        .hotline-item.pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(153, 13, 35, 0.3); }
            70% { box-shadow: 0 0 0 12px rgba(153, 13, 35, 0); }
            100% { box-shadow: 0 0 0 0 rgba(153, 13, 35, 0); }
        }
        .hotline-item.zalo.pulse {
            animation: pulseZalo 2s infinite;
        }
        @keyframes pulseZalo {
            0% { box-shadow: 0 0 0 0 rgba(0, 104, 255, 0.3); }
            70% { box-shadow: 0 0 0 12px rgba(0, 104, 255, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 104, 255, 0); }
        }
        .hotline-item.messenger.pulse {
            animation: pulseMessenger 2s infinite;
        }
        @keyframes pulseMessenger {
            0% { box-shadow: 0 0 0 0 rgba(0, 132, 255, 0.3); }
            70% { box-shadow: 0 0 0 12px rgba(0, 132, 255, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 132, 255, 0); }
        }

        @media (max-width: 768px) {
            .hotline-float { right: 10px; bottom: 70px; gap: 6px; }
            .hotline-item { width: 40px; height: 40px; font-size: 16px; }
            .hotline-item.zalo .zalo-icon { width: 22px; height: 22px; }
            .hotline-item.messenger i { font-size: 18px; }
            .hotline-item.phone i { font-size: 15px; }
            .hotline-item .tooltip { font-size: 11px; padding: 4px 10px; right: 48px; }
            .hotline-item:hover .tooltip { right: 50px; }
        }
        @media (max-width: 480px) {
            .hotline-float { right: 6px; bottom: 60px; gap: 5px; }
            .hotline-item { width: 36px; height: 36px; font-size: 14px; }
            .hotline-item.zalo .zalo-icon { width: 20px; height: 20px; }
            .hotline-item.messenger i { font-size: 16px; }
            .hotline-item.phone i { font-size: 13px; }
            .hotline-item .tooltip { font-size: 10px; padding: 3px 8px; right: 42px; }
            .hotline-item:hover .tooltip { right: 44px; }
            .hotline-item .tooltip::after { right: -5px; border-left: 5px solid rgba(0, 0, 0, 0.85); border-top: 5px solid transparent; border-bottom: 5px solid transparent; }
        }

        /* ===== ADMIN PREVIEW BANNER ===== */
        .admin-preview-banner {
            display: <?php echo ($is_admin_preview && $product && isset($product['status']) && $product['status'] == 0) ? 'block' : 'none'; ?>;
            background: #fef3c7;
            border-bottom: 3px solid #f59e0b;
            color: #92400e;
            padding: 8px 15px;
            text-align: center;
            font-size: 14px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            font-weight: 500;
        }
        .admin-preview-banner i { margin-right: 8px; }
        .admin-preview-banner .close-preview {
            background: #f59e0b;
            border: none;
            color: white;
            padding: 2px 12px;
            border-radius: 20px;
            margin-left: 15px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        .admin-preview-banner .close-preview:hover { background: #d97706; }

        /* ===== BREADCRUMB - GIỐNG sp1.php ===== */
        .category-breadcrumb {
            font-size: 16px;
            margin-bottom: 16px;
            padding: 8px 0;
            background: transparent;
            font-family: 'Inter', sans-serif;
        }
        .category-breadcrumb a {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .category-breadcrumb a:hover {
            text-decoration: underline;
        }
        .category-breadcrumb span {
            color: var(--text-dark);
            font-weight: 500;
        }
        
        /* ===== MAIN CONTENT WRAPPER - ĐẨY XUỐNG DƯỚI HEADER ===== */
        .main-content-wrapper {
            padding-top: 120px;
            min-height: 100vh;
        }
        
        @media (max-width: 992px) {
            .main-content-wrapper {
                padding-top: 100px;
            }
        }
        
        @media (max-width: 680px) {
            .main-content-wrapper {
                padding-top: 85px;
            }
        }
        
        @media (max-width: 480px) {
            .main-content-wrapper {
                padding-top: 75px;
            }
        }

        /* ===== STYLE RIÊNG CHO TRANG CHI TIẾT LY PHA LÊ ===== */
        .product-detail-wrapper { 
            max-width: 1280px; 
            margin: 0 auto 40px auto;
            padding: 0 20px; 
        }
        .product-detail-container { display: flex; gap: 50px; margin-bottom: 50px; flex-wrap: wrap; }
        .product-image-section { flex: 0 0 45%; max-width: 45%; }
        .product-image-main { width: 100%; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .product-image-main img { width: 100%; height: auto; display: block; }
        .product-info-section { flex: 1; min-width: 250px; }
        .product-title { font-size: 28px; font-weight: 700; margin-bottom: 10px; color: var(--primary-red); line-height: 1.3; }
        .product-status-badge { 
            display: inline-block; 
            margin-left: 12px; 
            font-size: 14px; 
            font-weight: 600; 
            padding: 4px 12px; 
            border-radius: 30px; 
            vertical-align: middle;
            background: #fee2e2; 
            color: #b91c1c;
        }
        .product-sku-rating { display: flex; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e0e0e0; }
        .product-sku { font-size: 14px; color: var(--text-light); }
        .rating-favorite-wrapper { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .rating-stars-display { display: inline-flex; align-items: center; gap: 4px; font-size: 16px; color: #f5b81b; }
        .votes-count { font-size: 14px; color: var(--text-light); }
        .favorite-btn { background: none; border: none; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 30px; transition: all 0.2s; color: #555; background: #f5f5f5; }
        .favorite-btn.active { color: var(--primary-red); background: #ffeeee; }
        .favorite-btn.active i { font-weight: 900; }
        .product-categories { margin-bottom: 15px; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .product-categories strong { font-weight: 600; color: var(--text-dark); }
        .category-badge { background: #f0f0f0; padding: 4px 12px; border-radius: 20px; font-size: 13px; color: var(--primary-red); font-weight: 500; }
        .product-description { font-size: 16px; line-height: 1.6; margin-bottom: 25px; color: var(--text-dark); }
        .product-description img { max-width: 100%; height: auto; border-radius: 8px; margin: 10px 0; }
        .product-specs-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px 20px; margin-bottom: 20px; background: #f9f9f9; padding: 15px; border-radius: 12px; }
        .spec-item { display: flex; justify-content: space-between; border-bottom: 1px dashed #ddd; padding: 8px 0; }
        .spec-label { font-weight: 600; color: var(--primary-red); }
        .spec-value { color: var(--text-dark); text-align: right; }
        .product-old-price { font-size: 18px; color: #888; text-decoration: line-through; margin-bottom: 5px; }
        .product-price { font-size: 32px; font-weight: 700; color: var(--primary-red); margin-bottom: 20px; }
        .product-actions { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; margin-bottom: 20px; }
        .quantity-selector { display: flex; align-items: center; gap: 10px; }
        .quantity-btn { width: 36px; height: 36px; background: white; border: 2px solid #ddd; border-radius: 8px; font-size: 20px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .quantity-btn:hover { border-color: var(--primary-red); color: var(--primary-red); }
        .quantity-input { width: 60px; height: 36px; text-align: center; font-size: 16px; font-weight: 600; border: 2px solid #ddd; border-radius: 8px; }
        .add-to-cart-btn { padding: 12px 30px; background: var(--primary-red); color: white; border: none; border-radius: 40px; font-weight: 700; font-size: 16px; cursor: pointer; transition: background 0.3s; }
        .add-to-cart-btn:hover { background: var(--dark-red); }
        .social-share { display: flex; align-items: center; gap: 18px; margin-top: 28px; padding-top: 10px; border-top: 1px solid var(--border-color); flex-wrap: wrap; }
        .social-share > span { font-size: 15px; font-weight: 600; background: var(--light-bg); padding: 6px 14px; border-radius: 40px; }
        .share-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 50%; background: white; color: #4a4a4a; font-size: 20px; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #eee; cursor: pointer; text-decoration: none; }
        .share-icon-btn:hover { background: var(--primary-red); color: white; transform: translateY(-3px); }
        .copy-tooltip { position: relative; }
        .tooltip-text { visibility: hidden; background-color: #333; color: #fff; text-align: center; border-radius: 6px; padding: 5px 10px; position: absolute; bottom: 130%; left: 50%; transform: translateX(-50%); font-size: 12px; white-space: nowrap; opacity: 0; transition: opacity 0.3s; }
        .copy-tooltip:hover .tooltip-text { visibility: visible; opacity: 1; }
        .rating-container { margin-top: 50px; padding-top: 20px; border-top: 2px solid var(--border-color); }
        .rating-title { font-size: 24px; font-weight: 700; margin-bottom: 25px; color: var(--primary-red); border-left: 4px solid var(--gold); padding-left: 15px; }
        .rating-summary { display: flex; align-items: center; gap: 30px; margin-bottom: 30px; background: #f9f9f9; padding: 20px 25px; border-radius: 16px; flex-wrap: wrap; }
        .average-box { text-align: center; background: white; padding: 15px 25px; border-radius: 20px; }
        .average-number { font-size: 48px; font-weight: 800; color: var(--primary-red); line-height: 1; }
        .average-stars { font-size: 22px; color: #f5b81b; margin: 8px 0; }
        .total-votes { font-size: 14px; color: var(--text-light); }
        .clickable-stars { display: flex; gap: 8px; font-size: 32px; cursor: pointer; }
        .clickable-stars i { color: #ddd; transition: 0.2s; }
        .clickable-stars i:hover, .clickable-stars i.active-star { color: #f5b81b; transform: scale(1.05); }
        .rating-message { margin-top: 12px; font-size: 14px; color: green; opacity: 0; transition: 0.3s; }
        .rating-message.show { opacity: 1; }
        
        /* ===== SẢN PHẨM CÙNG GIÁ - ẢNH HIỂN THỊ FULL ===== */
        .similar-products-section { margin-top: 60px; padding-top: 30px; border-top: 2px solid var(--border-color); }
        .similar-title { font-size: 24px; font-weight: 700; margin-bottom: 30px; color: var(--primary-red); border-left: 4px solid var(--gold); padding-left: 15px; }
        .similar-products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 30px;
        }
        @media (max-width: 1200px) {
            .similar-products-grid { grid-template-columns: repeat(4, 1fr) !important; gap: 20px; }
        }
        @media (max-width: 992px) {
            .similar-products-grid { grid-template-columns: repeat(3, 1fr) !important; gap: 18px; }
        }
        @media (max-width: 768px) {
            .similar-products-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 14px; }
        }
        @media (max-width: 480px) {
            .similar-products-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 12px; }
        }
        .similar-product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 2px solid #8B1E1E;
        }
        .similar-product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border-color: #5a1212;
        }
        .similar-product-card .product-image-link { 
            display: block; 
            overflow: hidden; 
            position: relative; 
            background-color: #f9f9f9;
            width: 100%;
            padding-bottom: 116.67%;
            height: 0;
        }
        .similar-product-card .product-image { 
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: #f9f9f9;
            padding: 10px;
            transition: transform 0.5s ease;
        }
        .similar-product-card:hover .product-image { transform: scale(1.02); }
        .similar-product-card .product-badge { position: absolute; top: 10px; left: 10px; background: #990D23; color: white; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; z-index: 2; }
        .similar-product-card .product-badge-stock { position: absolute; top: 10px; right: 10px; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; z-index: 2; background: #ffc107; color: #333; }
        .similar-product-card .product-badge-stock.discontinued { background: #6c757d; color: white; }
        .similar-product-card .product-badge-stock.outstock { background: #dc3545; color: white; }
        .similar-product-card .product-badge-stock.soon { background: #17a2b8; color: white; }
        .similar-product-card .product-info { padding: 8px 10px 4px; flex: 1; display: flex; flex-direction: column; }
        .similar-product-card .product-info h3 { font-size: 14px; margin-bottom: 4px; font-weight: 600; line-height: 1.4; }
        .similar-product-card .product-info h3 a { text-decoration: none; color: inherit; transition: color 0.2s; }
        .similar-product-card .product-info h3 a:hover { color: #990D23; }
        .similar-product-card .product-details { background: #fafafa; padding: 6px 8px; margin: 6px 8px 8px 8px; border-radius: 8px; border: 1px solid #eee; display: grid; grid-template-columns: 1fr 1fr; gap: 5px 8px; }
        .similar-product-card .detail-item { display: flex; align-items: center; gap: 5px; padding: 3px 0; border-bottom: 1px solid #eaeaea; overflow: hidden; }
        .similar-product-card .detail-item i { width: 18px; color: #990D23; font-size: 11px; flex-shrink: 0; }
        .similar-product-card .detail-item span { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #4a4a4a; font-size: 11px; }
        .similar-product-card .product-bottom-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; border-top: 1px solid #eee; margin-top: auto; }
        .similar-product-card .btn-buy-now { background: #990D23; color: white; border: none; padding: 5px 12px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .similar-product-card .btn-buy-now:hover { background: #670011; transform: translateY(-2px); }
        .similar-product-card .product-bottom-price { text-align: left; display: flex; flex-direction: column; align-items: flex-start; }
        .similar-product-card .current-price-bottom { font-size: 14px; font-weight: 700; color: #990D23; }
        .similar-product-card .original-price-bottom { font-size: 11px; color: #6c757d; text-decoration: line-through; margin-bottom: 2px; }
        .similar-view-more { text-align: center; margin-top: 20px; }
        .similar-view-more a { display: inline-block; padding: 10px 30px; background: var(--light-bg); border: 1px solid var(--primary-red); border-radius: 40px; color: var(--primary-red); font-weight: 600; text-decoration: none; transition: all 0.3s; }
        .similar-view-more a:hover { background: var(--primary-red); color: white; }

        .faq-section { margin: 30px 0; }
        .hotline-banner { background: #f9f9f9; border: 1px dashed var(--primary-red); border-radius: 8px; padding: 15px; text-align: center; margin-bottom: 20px; }
        .hotline-banner a { color: var(--primary-red); font-weight: 700; text-decoration: none; font-size: 18px; }
        .accordion-item { margin-bottom: 10px; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; }
        .accordion-btn { width: 100%; padding: 15px 20px; background: #f5f5f5; border: none; text-align: left; font-weight: 600; display: flex; justify-content: space-between; cursor: pointer; transition: background 0.2s; font-size: 16px; }
        .accordion-btn:hover { background: #eee; }
        .accordion-btn i { transition: transform 0.3s; }
        .accordion-btn.active i { transform: rotate(180deg); }
        .accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
        .accordion-content-inner { padding: 15px 20px; background: white; }

        .promo-actions { 
            display: flex; 
            justify-content: center; 
            gap: 20px;
            margin: 40px 0 30px;
            flex-wrap: wrap; 
            padding: 0 10px;
        }
        .promo-item { 
            display: inline-flex; 
            align-items: center; 
            gap: 10px;
            padding: 14px 24px;
            background-color: #f8f8f8; 
            border: 2px solid #8B1E1E; 
            border-radius: 50px;
            color: #8B1E1E; 
            font-weight: 700; 
            font-size: 16px;
            text-decoration: none; 
            transition: all 0.3s; 
            box-shadow: 0 4px 12px rgba(139,30,30,0.12);
            white-space: nowrap;
            flex: 1;
            max-width: 220px;
            justify-content: center;
        }
        .promo-item i { font-size: 20px; }
        .promo-item:hover { 
            background-color: #8B1E1E; 
            color: white; 
            transform: translateY(-3px); 
            box-shadow: 0 8px 20px rgba(139,30,30,0.3); 
        }

        /* ===== FLOATING PROMO - CHỈ HIỂN THỊ TRÊN PC ===== */
        .floating-promo { 
            position: fixed; 
            bottom: 0; 
            left: 0; 
            right: 0; 
            background: white; 
            box-shadow: 0 -6px 25px rgba(0,0,0,0.18);
            padding: 12px 16px;
            display: none !important;
            justify-content: center; 
            gap: 12px;
            z-index: 1000; 
            transform: translateY(100%); 
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-top: 4px solid #8B1E1E;
            flex-wrap: wrap;
        }
        .floating-promo.show { transform: translateY(0); }
        
        /* Hiển thị trên PC (>=769px) */
        @media (min-width: 769px) {
            .floating-promo {
                display: flex !important;
            }
        }
        
        .floating-item { 
            display: inline-flex; 
            align-items: center; 
            gap: 6px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #8B1E1E 0%, #A61C2A 100%);
            color: white; 
            border-radius: 25px;
            text-decoration: none; 
            font-weight: 600; 
            font-size: 14px;
            transition: all 0.3s; 
            border: 2px solid rgba(255,255,255,0.2);
            box-shadow: 0 4px 12px rgba(139,30,30,0.3);
            white-space: nowrap;
            flex: 1;
            max-width: 160px;
            justify-content: center;
        }
        .floating-item i { font-size: 16px; }
        .floating-item:hover { 
            background: linear-gradient(135deg, #6b1717 0%, #8B1E1E 100%);
            transform: translateY(-2px) scale(1.05); 
            box-shadow: 0 8px 20px rgba(139,30,30,0.4); 
        }

        .main-footer { 
            background: var(--dark-red) !important; 
            color: white; 
            padding-top: 50px; 
            margin-top: 0; 
            width: 100%; 
        }
        .footer-container { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 40px; 
            padding-bottom: 40px; 
        }
        .footer-section h4 { 
            font-size: 18px; 
            margin-bottom: 20px; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            color: white; 
        }
        .footer-section p, .footer-section a { 
            margin-bottom: 10px; 
            font-size: 14px; 
            opacity: 0.9; 
            color: rgba(255,255,255,0.9); 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            gap: 6px; 
            transition: color 0.2s; 
        }
        .footer-section a:hover { color: white; }
        .social-links { display: flex; gap: 15px; margin-top: 20px; }
        .social-links a { 
            width: 36px; 
            height: 36px; 
            background: rgba(255,255,255,0.1); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            transition: background 0.3s; 
        }
        .social-links a:hover { background: var(--primary-red); }
        .newsletter { 
            display: flex; 
            margin-top: 15px; 
            margin-bottom: 25px; 
        }
        .newsletter input { 
            flex: 1; 
            padding: 12px 15px; 
            border: none; 
            border-radius: 6px 0 0 6px; 
            font-size: 14px; 
            outline: none; 
        }
        .newsletter button { 
            background: var(--gold); 
            color: var(--dark-red); 
            border: none; 
            padding: 0 20px; 
            border-radius: 0 6px 6px 0; 
            cursor: pointer; 
            font-weight: 600; 
        }
        .payment-methods { 
            display: flex; 
            gap: 15px; 
            font-size: 24px; 
            margin-top: 20px; 
            color: rgba(255,255,255,0.8); 
        }
        .payment-methods a { color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.2s; }
        .payment-methods a:hover { color: white; }
        .footer-bottom { 
            text-align: center; 
            padding: 20px 0; 
            border-top: 1px solid rgba(255,255,255,0.1); 
            font-size: 13px; 
            opacity: 0.8; 
        }

        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background-color: #fff; margin: 5% auto; padding: 30px; border-radius: 24px; width: 90%; max-width: 900px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); position: relative; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close-modal { position: absolute; right: 25px; top: 15px; font-size: 35px; font-weight: 300; color: #888; cursor: pointer; transition: color 0.2s; line-height: 1; }
        .close-modal:hover { color: var(--primary-red); }
        .modal-body { display: flex; gap: 30px; flex-wrap: wrap; }
        .form-col { flex: 1 1 300px; }
        .form-col h3 { font-size: 24px; color: var(--primary-red); margin-bottom: 5px; }
        .form-col p { color: var(--text-light); margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 5px; color: var(--text-dark); }
        .form-group input { width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 12px; font-size: 16px; transition: border-color 0.2s; }
        .form-group input:focus { outline: none; border-color: var(--primary-red); }
        .submit-btn { background: var(--primary-red); color: white; border: none; padding: 14px 30px; border-radius: 50px; font-size: 18px; font-weight: 700; cursor: pointer; width: 100%; transition: background 0.3s, transform 0.2s; box-shadow: 0 5px 15px rgba(139,0,0,0.3); border: 2px solid var(--primary-red); }
        .submit-btn:hover { background: var(--dark-red); transform: translateY(-2px); }
        #modalSuccessState { text-align: center; padding: 20px 0; }
        #modalSuccessState i { font-size: 70px; color: #2ecc71; margin-bottom: 15px; }
        #modalSuccessState h3 { font-size: 26px; color: var(--text-dark); margin-bottom: 10px; }
        #modalSuccessState p { color: var(--text-light); margin-bottom: 25px; }
        .close-success-btn { background: var(--primary-red); color: white; border: none; padding: 12px 40px; border-radius: 50px; font-size: 18px; font-weight: 600; cursor: pointer; transition: background 0.3s; border: 2px solid var(--primary-red); }
        .close-success-btn:hover { background: var(--dark-red); }

        .error-message { 
            text-align: center; 
            padding: 60px 20px; 
            font-size: 18px; 
            color: var(--text-light);
            background: #f9f9f9;
            border-radius: 16px;
            margin: 40px 0;
        }
        .error-message i { font-size: 48px; color: #ef4444; display: block; margin-bottom: 15px; }

        @media (max-width: 992px) {
            .main-nav { overflow-x: auto; white-space: nowrap; }
            .nav-list { flex-wrap: nowrap; justify-content: flex-start; overflow-x: auto; }
            .dropdown-menu { display: none !important; }
            .footer-container { grid-template-columns: repeat(2, 1fr); gap: 30px; }
            .header-top { gap: 10px; }
            .header-bottom { margin-top: 6px !important; padding-top: 3px !important; }
            .product-image-section { flex: 0 0 100%; max-width: 100%; }
        }
        @media (max-width: 768px) {
            .product-detail-container { gap: 25px; }
            .product-title { font-size: 22px; }
            .product-price { font-size: 26px; }
            .product-specs-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 680px) {
            .header-top { gap: 8px !important; padding: 4px 0; }
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
        @media (max-width: 480px) {
            .header { padding: 6px 0 4px 0 !important; }
            .header-top { gap: 6px !important; }
            .logo img { height: 32px !important; }
            .search-bar { margin-top: 4px !important; }
            .header-right { gap: 8px !important; }
            .container { padding: 0 12px; }
            .product-detail-wrapper { padding: 0 12px; }
            .product-title { font-size: 20px; }
            .product-price { font-size: 24px; }
            .promo-actions { 
                gap: 12px; 
                margin: 30px 0 20px; 
                padding: 0 8px;
                flex-direction: column;
                align-items: center;
            }
            .promo-item { 
                width: 280px;
                max-width: 280px;
                padding: 14px 24px;
                font-size: 16px;
            }
            .footer-container { 
                grid-template-columns: 1fr; 
                gap: 30px;
                text-align: left !important;
            }
            .footer-section { text-align: left !important; }
            .footer-section p, .footer-section a { justify-content: flex-start !important; }
            .social-links, .payment-methods { justify-content: flex-start !important; }
        }
        @media (max-width: 360px) {
            .promo-item { width: 260px; }
        }
        
        .nav-list .nav-item .nav-link {
            transition: all 0.2s ease;
        }
        .nav-list .nav-item .nav-link:hover {
            color: var(--gold) !important;
            background: rgba(255, 255, 255, 0.1) !important;
        }
        .nav-list .nav-item .nav-link .fa-home {
           font-size: 16px;
            margin-right: 4px;
        }
        @media (max-width: 992px) {
            .nav-list .nav-item .nav-link .fa-home {
            font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <!-- ===== TOAST NOTIFICATION ===== -->
    <div class="toast-notification" id="toastNotification">
        <button class="toast-close" onclick="closeToast()">&times;</button>
        <div class="toast-title" id="toastTitle">Thành công!</div>
        <div class="toast-message" id="toastMessage">Đã thêm vào giỏ hàng</div>
    </div>

    <!-- ===== HOTLINE FLOATING ===== -->
<div class="hotline-float">
    <a href="https://zalo.me/0977869066" target="_blank" class="hotline-item zalo pulse" title="Chat Zalo">
        <span class="zalo-icon">
            <!-- Đã thay đổi SVG tại đây để giống logo Zalo mẫu -->
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

    <!-- ADMIN PREVIEW BANNER -->
    <div class="admin-preview-banner" id="adminPreviewBanner">
        <i class="fas fa-eye"></i>
        <span>🔍 Bạn đang xem trước sản phẩm (chế độ admin) - Sản phẩm đang <strong>ẨN</strong></span>
        <button class="close-preview" onclick="closePreview()"><i class="fas fa-times"></i> Đóng preview</button>
    </div>

    <!-- HEADER -->
    <header class="header">
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <a href="https://ruouchat.vn/index.php">
                        <img src="../assets/images/logo.jpg" alt="Wine VN - Rượu Vang Nhập Khẩu">
                    </a>
                </div>
                <div class="search-bar">
                    <form class="search-form" action="product_main.php" method="GET" id="searchForm">
                        <input type="text" name="search" id="searchInput" placeholder="Tìm kiếm rượu vang, whisky, quà tặng..." aria-label="Tìm kiếm" autocomplete="off">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </div>
                <div class="header-right">
                    <a href="lienhe.php" class="nav-icon-link">
                        <i class="fa-solid fa-location-dot"></i>
                    </a>
                    <a href="account.php" class="nav-icon-link">
                        <i class="fa-solid fa-heart"></i>
                    </a>
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
                        <?php echo buildMenuHTML($menuItems, 0); ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- ===== BREADCRUMB - GIỐNG sp1.php ===== -->
    <div class="main-content-wrapper">
        <div class="container">
            <div class="category-breadcrumb">
                <a href="index.php">Trang chủ</a> / 
                <a href="lyphale.php">Ly pha lê</a> / 
                <span id="productBreadcrumb"><?php echo $product ? htmlspecialchars($product['name']) : 'Chi tiết sản phẩm'; ?></span>
                <?php if ($product && isset($product['status']) && $product['status'] == 0): ?>
                <span class="product-status-badge"><i class="fas fa-eye-slash"></i> ĐANG ẨN</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="product-detail-wrapper">
            <?php if ($product && !isset($product['error'])): ?>
            <div class="product-detail-container" id="productDetail">
                <div class="product-image-section">
                    <div class="product-image-main">
                        <img src="<?php echo htmlspecialchars($product['main_image'] ?? 'assets/images/11.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://placehold.co/600x600?text=No+Image'">
                    </div>
                </div>
                <div class="product-info-section">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="product-sku-rating">
                        <div class="product-sku">Mã: #<?php echo $product['id']; ?></div>
                        <div class="rating-favorite-wrapper">
                            <div class="rating-stars-display" id="ratingStarsDisplay">★★★★★</div>
                            <span class="votes-count" id="votesCountDisplay">(0 đánh giá)</span>
                            <button class="favorite-btn" id="favoriteBtn" data-id="<?php echo $product['id']; ?>" data-type="glass">
                                <i class="far fa-heart"></i> Yêu thích
                            </button>
                        </div>
                    </div>
                    
                    <?php if (!empty($product['categories']) && is_array($product['categories'])): ?>
                    <div class="product-categories">
                        <strong>Danh mục:</strong>
                        <?php foreach ($product['categories'] as $cat): ?>
                        <span class="category-badge"><?php echo htmlspecialchars(getCategoryName($cat, $CATEGORIES)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="product-description">
                        <?php echo $product['description'] ?? 'Sản phẩm ly pha lê cao cấp, thiết kế tinh tế.'; ?>
                    </div>
                    
                    <div class="product-specs-grid">
                        <div class="spec-item"><span class="spec-label">Chất liệu</span><span class="spec-value">Pha lê cao cấp</span></div>
                        <div class="spec-item"><span class="spec-label">Dung tích</span><span class="spec-value"><?php echo htmlspecialchars($product['capacity'] ?? $product['volume'] ?? '350ml'); ?></span></div>
                        <div class="spec-item"><span class="spec-label">Thương hiệu</span><span class="spec-value"><?php echo htmlspecialchars($product['brand'] ?? 'Riedel'); ?></span></div>
                        <div class="spec-item"><span class="spec-label">Loại ly</span><span class="spec-value"><?php echo htmlspecialchars($product['type'] ?? $product['category'] ?? 'Ly rượu vang'); ?></span></div>
                    </div>
                    
                    <?php if (!empty($product['old_price']) && $product['old_price'] > 0 && $product['old_price'] != $product['price']): ?>
                    <div class="product-old-price"><?php echo formatPrice($product['old_price']); ?></div>
                    <?php endif; ?>
                    
                    <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                    
                    <div class="product-actions">
                        <div class="quantity-selector">
                            <button class="quantity-btn minus">-</button>
                            <input type="text" class="quantity-input" value="1" readonly>
                            <button class="quantity-btn plus">+</button>
                        </div>
                        <button class="add-to-cart-btn" data-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                        </button>
                    </div>
                    
                    <div class="social-share">
                        <span>Chia sẻ</span>
                        <a href="#" id="shareFacebook" class="share-icon-btn"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" id="shareTwitter" class="share-icon-btn"><i class="fab fa-twitter"></i></a>
                        <a href="#" id="shareZalo" class="share-icon-btn"><i class="fab fa-zalo"></i></a>
                        <a href="#" id="shareLinkedin" class="share-icon-btn"><i class="fab fa-linkedin-in"></i></a>
                        <div class="share-icon-btn copy-tooltip" id="shareCopy"><i class="fas fa-link"></i><span class="tooltip-text">Sao chép</span></div>
                    </div>
                </div>
            </div>
            
            <!-- Rating Section -->
            <div id="ratingSection"></div>
            
            <!-- FAQ Section -->
            <div class="faq-section" id="faqSection">
                <div class="hotline-banner">
                    <i class="fas fa-phone-alt"></i> 
                          Liên hệ hotline <a href="tel:0969 111 855">0969 111 855</a> để được tư vấn
                </div>
                <div class="accordion-item">
                    <button class="accordion-btn">Bảo quản ly pha lê như thế nào? <i class="fas fa-chevron-down"></i></button>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">Rửa bằng nước ấm, không dùng máy rửa chén, lau khô bằng vải mềm để tránh trầy xước.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <button class="accordion-btn">Thời gian giao hàng? <i class="fas fa-chevron-down"></i></button>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">Miễn phí giao hàng nội thành TP.HCM trong vòng 24h. Giao hàng toàn quốc từ 2-4 ngày.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <button class="accordion-btn">Chính sách đổi trả? <i class="fas fa-chevron-down"></i></button>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">Hỗ trợ đổi trả trong vòng 7 ngày nếu sản phẩm có lỗi từ nhà sản xuất.</div>
                    </div>
                </div>
            </div>
            
            <!-- ===== SẢN PHẨM CÙNG GIÁ - TỈ LỆ 6:7 ===== -->
            <div class="similar-products-section" id="similarProductsSection" style="display: none;">
                <h3 class="similar-title"><i class="fas fa-wine-bottle"></i> SẢN PHẨM CÙNG MỨC GIÁ</h3>
                <div id="similarProductsGrid" class="similar-products-grid"></div>
                <div class="similar-view-more"><a href="lyphale.php">Xem thêm sản phẩm →</a></div>
            </div>
            
            <?php else: ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <p>Không tìm thấy sản phẩm.</p>
                <p class="mt-2" style="font-size: 14px; color: #94a3b8;">
                    <?php if ($slug): ?>
                    Slug: <?php echo htmlspecialchars($slug); ?>
                    <?php else: ?>
                    Vui lòng kiểm tra lại đường dẫn.
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PROMO ACTIONS -->
    <div class="promo-actions">
        <a href="lienhe.php" class="promo-item"><i class="fas fa-map-marker-alt"></i> TÌM CỬA HÀNG</a>
        <a href="#" class="promo-item open-offer-modal"><i class="fas fa-gift"></i> NHẬN ƯU ĐÃI</a>
        <a href="quatangdoanhnghiep.php" class="promo-item"><i class="fas fa-briefcase"></i> QUÀ DOANH NGHIỆP</a>
    </div>

    <!-- FOOTER -->
    <footer class="main-footer">
        <div class="footer-container container">
            <div class="footer-section">
                <h4><i class="fas fa-store"></i> Ruouchat.vn</h4>
                <p>Địa chỉ: 113B Ngõ 25 Vũ Ngọc Phan, Phường Láng, Hà Nội</p>
                <p>Điện thoại: <strong>0969 111 855</strong></p>
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
                <a href="product_main.php"><i class="fas fa-caret-right"></i> Rượu Vang Đỏ</a>
                <a href="product_main.php"><i class="fas fa-caret-right"></i> Rượu Vang Pháp</a>
                <a href="product_main.php"><i class="fas fa-caret-right"></i> Rượu Vang Ý</a>
                <a href="product_main.php"><i class="fas fa-caret-right"></i> Rượu Vang Chile</a>
                <a href="product_main.php"><i class="fas fa-caret-right"></i> Rượu Champagne</a>
                <a href="product_main.php"><i class="fas fa-caret-right"></i> Rượu Whisky</a>
                <a href="product_main.php"><i class="fas fa-caret-right"></i> Quà tặng rượu</a>
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
            <p>© 2026 Ruouchat.vn - Chuyên Rượu Vang Nhập Khẩu Chính Hãng.</p>
        </div>
    </footer>

    <!-- Floating promo bar - CHỈ HIỂN THỊ TRÊN PC -->
    <div class="floating-promo" id="floatingPromo">
        <a href="lienhe.php" class="floating-item"><i class="fas fa-map-marker-alt"></i> Tìm cửa hàng</a>
        <a href="#" class="floating-item open-offer-modal"><i class="fas fa-gift"></i> Nhận ưu đãi</a>
        <a href="quatangdoanhnghiep.php" class="floating-item"><i class="fas fa-briefcase"></i> Quà doanh nghiệp</a>
    </div>

    <!-- Modal ưu đãi -->
    <div id="offerModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-body">
                <div class="form-col" id="modalFormState">
                    <h3>Vui lòng để lại thông tin của bạn.</h3>
                    <p>Ruouchat.vn sẽ gửi ưu đãi cho quý khách</p>
                    <form id="offerForm">
                        <div class="form-group"><label>Họ và Tên (bắt buộc)</label><input type="text" id="name" required></div>
                        <div class="form-group"><label>Số điện thoại (bắt buộc)</label><input type="tel" id="phone" required></div>
                        <button type="submit" class="submit-btn">NHẬN ƯU ĐÃI</button>
                    </form>
                </div>
                <div id="modalSuccessState" style="display: none;">
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

        // ===== HIỆU ỨNG PULSE CHO HOTLINE =====
        document.addEventListener('DOMContentLoaded', function() {
            const hotlineItems = document.querySelectorAll('.hotline-item');
            hotlineItems.forEach((item, index) => {
                item.classList.remove('pulse');
                setTimeout(() => {
                    item.classList.add('pulse');
                }, index * 500);
            });
        });

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
        const headerElem = document.querySelector('.header');
        const scrollThreshold = 100;
        
        function handleHeaderScroll() {
            let currentScroll = window.pageYOffset;
            if (currentScroll > scrollThreshold && currentScroll > lastScroll) {
                headerElem.classList.add('hide');
                headerElem.classList.remove('show');
            } else if (currentScroll < lastScroll || currentScroll <= 20) {
                headerElem.classList.add('show');
                headerElem.classList.remove('hide');
            }
            lastScroll = currentScroll;
        }
        
        window.addEventListener('scroll', handleHeaderScroll);
        
        setTimeout(() => {
            if (window.scrollY <= 20) {
                headerElem.classList.add('show');
                headerElem.classList.remove('hide');
            } else if (window.scrollY > scrollThreshold) {
                headerElem.classList.add('hide');
                headerElem.classList.remove('show');
            }
        }, 100);

        // ==================== HÀM TIỆN ÍCH ====================
        function formatPrice(price) {
            if (price === undefined || price === null || price === '') return '0 ₫';
            let num = parseFloat(price);
            if (isNaN(num)) return '0 ₫';
            return num.toLocaleString('vi-VN') + ' ₫';
        }
        
        function escapeHtml(str) { 
            if(!str) return ''; 
            return str.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m])); 
        }

        async function callAPI(url, options = {}) {
            try {
                const res = await fetch(url, { 
                    ...options, 
                    headers: { 'Content-Type': 'application/json' }, 
                    credentials: 'include' 
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
                return data;
            } catch(e) {
                console.error(`API call failed (${url}):`, e);
                return null;
            }
        }

        // ==================== HEADER: SEARCH ====================
        const suggestionsList = ["wine", "1855", "rượu vang", "cửa hàng rượu vang", "nhà cung cấp rượu vang","rượu vang ý", "rượu vang pháp", "rượu vang mỹ", "San Marzano","vang Ý cao cấp", "vang Ý nhập khẩu", "Rượu vang Pháp", "Bordeaux","Grand Cru Classé", "vang Pháp chính hãng", "Rượu vang nhập khẩu","Rượu vang Pháp cao cấp", "Rượu vang Ý chính hãng", "Wine", "1855"];
        const uniqueSuggestions = [...new Map(suggestionsList.map(s => [s.toLowerCase(), s])).values()];
        const searchInput = document.getElementById('searchInput');
        const suggestionsBox = document.getElementById('searchSuggestions');
        const searchForm = document.getElementById('searchForm');
        
        function showSuggestions() {
            if (!searchInput.value.trim()) { 
                renderSuggestions(uniqueSuggestions); 
                suggestionsBox.classList.add('active'); 
            } else { 
                const keyword = searchInput.value.trim().toLowerCase(); 
                const filtered = uniqueSuggestions.filter(s => s.toLowerCase().includes(keyword)); 
                if (filtered.length > 0) { 
                    renderSuggestions(filtered); 
                    suggestionsBox.classList.add('active'); 
                } else { 
                    suggestionsBox.classList.remove('active'); 
                } 
            }
        }
        
        function renderSuggestions(items) {
            if (!items || items.length === 0) { 
                suggestionsBox.innerHTML = ''; 
                suggestionsBox.classList.remove('active'); 
                return; 
            }
            let html = `<div class="suggestions-header">ĐỀ XUẤT PHỔ BIẾN</div><div class="suggestions-grid">`;
            items.forEach(item => { 
                html += `<div class="suggestion-item" data-value="${escapeHtml(item)}">${escapeHtml(item)}</div>`; 
            });
            html += `</div><div class="suggestion-search-example" data-value="Rượu vang Ý chính hãng"><i class="fas fa-search"></i> Tìm kiếm Rượu vang Ý chính hãng</div>`;
            suggestionsBox.innerHTML = html;
            document.querySelectorAll('.suggestion-item, .suggestion-search-example').forEach(el => { 
                el.addEventListener('click', function(e) { 
                    const value = this.getAttribute('data-value'); 
                    searchInput.value = value; 
                    suggestionsBox.classList.remove('active'); 
                    searchForm.submit(); 
                }); 
            });
        }
        
        if (searchInput) {
            searchInput.addEventListener('focus', showSuggestions);
            searchInput.addEventListener('input', showSuggestions);
            document.addEventListener('click', function(e) { 
                if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) { 
                    suggestionsBox.classList.remove('active'); 
                } 
            });
        }

        // ==================== CART FUNCTIONS WITH TOAST ====================
        async function addToCart(product_id, quantity = 1) {
            try {
                const res = await fetch('api/cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add', product_id, quantity, product_type: 'crystal_glasses' }),
                    credentials: 'include'
                });
                const data = await res.json();
                if (data.success) {
                    await updateCartCount();
                    showToast('✅ Thành công!', 'Đã thêm sản phẩm vào giỏ hàng!');
                    return true;
                } else {
                    showToast('❌ Lỗi!', data.message || 'Thêm vào giỏ hàng thất bại');
                    return false;
                }
            } catch(e) {
                showToast('❌ Lỗi!', 'Có lỗi xảy ra, vui lòng thử lại');
                return false;
            }
        }
        
        async function getCart() { return await callAPI('api/cart.php'); }
        
        async function updateCartCount() {
            const cart = await getCart();
            const total = Object.values(cart || {}).reduce((s, i) => s + (i.quantity || 0), 0);
            const countSpan = document.querySelector('.cart-count');
            if (countSpan) countSpan.innerText = total;
        }
        
        async function checkLoginStatus() {
            try {
                const res = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'check' }),
                    credentials: 'include'
                });
                const data = await res.json();
                return data.logged_in ? data.user : null;
            } catch (e) { return null; }
        }
        
        async function updateUserMenu() {
            const userMenu = document.getElementById('userMenu');
            if (!userMenu) return;
            const user = await checkLoginStatus();
            if (user) {
                userMenu.innerHTML = `<li><a href="account.php"><i class="fas fa-user-circle"></i> Tài khoản</a></li><li><a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>`;
                const logoutBtn = document.getElementById('logoutBtn');
                if (logoutBtn) logoutBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    await fetch('api/auth.php', { method: 'POST', body: JSON.stringify({ action: 'logout' }), headers: { 'Content-Type': 'application/json' }, credentials: 'include' });
                    window.location.reload();
                });
            } else {
                userMenu.innerHTML = `<li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a></li><li><a href="register.php"><i class="fas fa-user-plus"></i> Đăng ký</a></li>`;
            }
        }

        // ==================== WISHLIST ====================
        async function safeFetchJson(url, options) {
            try {
                const res = await fetch(url, options);
                const text = await res.text();
                if (!text.trim()) return { success: false };
                if (!text.trim().startsWith('{') && !text.trim().startsWith('[')) return { success: false };
                const data = JSON.parse(text);
                if (!res.ok) throw new Error(data.error);
                return data;
            } catch(e) { return { success: false }; }
        }
        
        async function getWishlistFromDB() {
            const data = await safeFetchJson('api/wishlist.php', { credentials: 'include' });
            if (data.success && Array.isArray(data.data)) return data.data.map(item => item.product_id);
            return [];
        }
        
        async function addToWishlistDB(productId, productType = 'glass') {
            const data = await safeFetchJson('api/wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, product_type: productType }),
                credentials: 'include'
            });
            if (data.success) return true;
            if (data.error === 'Vui lòng đăng nhập' || (data.error && data.error.includes('đăng nhập'))) {
                alert('Vui lòng đăng nhập để sử dụng tính năng yêu thích');
                window.location.href = 'login.php';
                return false;
            }
            alert(data.error || 'Không thể thêm yêu thích');
            return false;
        }
        
        async function removeFromWishlistDB(productId, productType = 'glass') {
            const data = await safeFetchJson('api/wishlist.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, product_type: productType }),
                credentials: 'include'
            });
            return data.success === true;
        }
        
        async function toggleFavorite(productId, productType, btnElement) {
            let wishlist = await getWishlistFromDB();
            const isFav = wishlist.includes(productId);
            let success;
            if (isFav) success = await removeFromWishlistDB(productId, productType);
            else success = await addToWishlistDB(productId, productType);
            if (success) {
                if (isFav) {
                    btnElement.classList.remove('active');
                    btnElement.innerHTML = '<i class="far fa-heart"></i> Yêu thích';
                } else {
                    btnElement.classList.add('active');
                    btnElement.innerHTML = '<i class="fas fa-heart"></i> Yêu thích';
                }
            }
        }

        // ==================== RATING (localStorage) ====================
        function getProductRatings(productId) {
            const all = JSON.parse(localStorage.getItem('product_ratings_glass') || '{}');
            return all[productId] || [];
        }
        
        function saveProductRatings(productId, ratings) {
            const all = JSON.parse(localStorage.getItem('product_ratings_glass') || '{}');
            all[productId] = ratings;
            localStorage.setItem('product_ratings_glass', JSON.stringify(all));
        }
        
        function addRating(productId, starValue) {
            const ratings = getProductRatings(productId);
            ratings.push(parseInt(starValue));
            saveProductRatings(productId, ratings);
            return ratings;
        }
        
        function computeRatingStats(ratings) {
            if (!ratings.length) return { avg: 0, total: 0 };
            const sum = ratings.reduce((a, b) => a + b, 0);
            return { avg: parseFloat((sum / ratings.length).toFixed(1)), total: ratings.length };
        }
        
        function renderStarsHtml(rating) {
            let html = '';
            const full = Math.floor(rating);
            const half = rating % 1 >= 0.5;
            for (let i = 1; i <= 5; i++) {
                if (i <= full) html += '<i class="fas fa-star"></i>';
                else if (i === full + 1 && half) html += '<i class="fas fa-star-half-alt"></i>';
                else html += '<i class="far fa-star"></i>';
            }
            return html;
        }
        
        function updateTopRatingDisplay(productId) {
            const { avg, total } = computeRatingStats(getProductRatings(productId));
            const starsSpan = document.getElementById('ratingStarsDisplay');
            const votesSpan = document.getElementById('votesCountDisplay');
            if (starsSpan) starsSpan.innerHTML = renderStarsHtml(avg);
            if (votesSpan) votesSpan.innerHTML = `(${total} đánh giá)`;
        }
        
        function seedDemoRatingsIfEmpty(productId) {
            if (getProductRatings(productId).length === 0) saveProductRatings(productId, [5, 4, 5, 4, 5]);
        }
        
        function renderRatingSection(productId) {
            const container = document.getElementById('ratingSection');
            if (!container) return;
            const { avg, total } = computeRatingStats(getProductRatings(productId));
            const ratingHtml = `
                <div class="rating-container">
                    <h3 class="rating-title"><i class="fas fa-star"></i> Đánh giá sản phẩm</h3>
                    <div class="rating-summary">
                        <div class="average-box">
                            <div class="average-number">${avg.toFixed(1)}</div>
                            <div class="average-stars">${renderStarsHtml(avg)}</div>
                            <div class="total-votes">${total} lượt</div>
                        </div>
                        <div class="rate-action">
                            <p>Bạn đánh giá thế nào?</p>
                            <div class="clickable-stars" id="clickableStars">
                                <i class="far fa-star" data-value="1"></i>
                                <i class="far fa-star" data-value="2"></i>
                                <i class="far fa-star" data-value="3"></i>
                                <i class="far fa-star" data-value="4"></i>
                                <i class="far fa-star" data-value="5"></i>
                            </div>
                            <div class="rating-message" id="ratingMessage">Cảm ơn bạn!</div>
                        </div>
                    </div>
                </div>
            `;
            container.innerHTML = ratingHtml;
            document.querySelectorAll('#clickableStars i').forEach(star => {
                star.addEventListener('click', (e) => {
                    const value = parseInt(star.getAttribute('data-value'));
                    addRating(productId, value);
                    renderRatingSection(productId);
                    updateTopRatingDisplay(productId);
                    const msg = document.getElementById('ratingMessage');
                    msg.classList.add('show');
                    setTimeout(() => msg.classList.remove('show'), 2000);
                });
            });
        }

        // ==================== SOCIAL SHARE ====================
        function setupSocialShare(productName, productUrl) {
            const url = encodeURIComponent(productUrl);
            const title = encodeURIComponent(productName);
            const fb = document.getElementById('shareFacebook');
            const tw = document.getElementById('shareTwitter');
            const zl = document.getElementById('shareZalo');
            const li = document.getElementById('shareLinkedin');
            const cp = document.getElementById('shareCopy');
            if (fb) fb.onclick = (e) => { e.preventDefault(); window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank'); };
            if (tw) tw.onclick = (e) => { e.preventDefault(); window.open(`https://twitter.com/intent/tweet?text=${title}&url=${url}`, '_blank'); };
            if (zl) zl.onclick = (e) => { e.preventDefault(); window.open(`https://zalo.me/share?url=${url}&text=${title}`, '_blank'); };
            if (li) li.onclick = (e) => { e.preventDefault(); window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank'); };
            if (cp) cp.onclick = async (e) => {
                e.preventDefault();
                try { 
                    await navigator.clipboard.writeText(productUrl); 
                    showToast('✅ Thành công!', 'Đã sao chép liên kết sản phẩm!');
                } catch (err) { 
                    prompt('Sao chép thủ công:', productUrl); 
                }
            };
        }

        // ==================== CLOSE PREVIEW ====================
        function closePreview() {
            const url = new URL(window.location.href);
            url.searchParams.delete('preview');
            window.location.href = url.toString();
        }

        // ==================== SIMILAR PRODUCTS - CÓ TOAST ====================
        async function loadSimilarProducts(currentProduct) {
            try {
                const currentPrice = currentProduct.price || 0;
                let minPrice = 0, maxPrice = 0;
                
                if (currentPrice < 2000000) {
                    minPrice = 0;
                    maxPrice = 2000000;
                } else if (currentPrice >= 2000000 && currentPrice <= 6000000) {
                    minPrice = 2000000;
                    maxPrice = 6000000;
                } else if (currentPrice > 6000000 && currentPrice <= 15000000) {
                    minPrice = 6000000;
                    maxPrice = 15000000;
                } else if (currentPrice > 15000000 && currentPrice <= 20000000) {
                    minPrice = 15000000;
                    maxPrice = 20000000;
                } else if (currentPrice > 20000000) {
                    minPrice = 20000000;
                    maxPrice = 999999999;
                }
                
                let url = `api/glasses.php?limit=20`;
                
                const resp = await fetch(url);
                const data = await resp.json();
                let products = (data.products || []);
                
                let filteredProducts = products.filter(p => 
                    p.id != currentProduct.id && 
                    p.price >= minPrice && 
                    p.price <= maxPrice
                ).slice(0, 4);
                
                if (filteredProducts.length < 4) {
                    const moreProducts = products.filter(p => 
                        p.id != currentProduct.id && 
                        !filteredProducts.some(fp => fp.id === p.id)
                    ).slice(0, 4 - filteredProducts.length);
                    filteredProducts = [...filteredProducts, ...moreProducts];
                }
                
                if (filteredProducts.length === 0) {
                    document.getElementById('similarProductsSection').style.display = 'none';
                    return;
                }
                
                const container = document.getElementById('similarProductsGrid');
                container.innerHTML = '';
                
                filteredProducts.forEach(p => {
                    let leftBadge = '';
                    if (p.old_price && p.old_price > p.price) {
                        leftBadge = '<div class="product-badge">Giảm giá</div>';
                    } else {
                        leftBadge = '<div class="product-badge">Mới</div>';
                    }
                    
                    let rightBadge = '';
                    if (p.display_status === 'coming_soon') {
                        rightBadge = '<div class="product-badge-stock soon">Sắp về</div>';
                    } else if (p.display_status === 'discontinued') {
                        rightBadge = '<div class="product-badge-stock discontinued">Ngừng bán</div>';
                    } else if ((p.stock_quantity || 0) <= 0) {
                        rightBadge = '<div class="product-badge-stock outstock">Hết hàng</div>';
                    }
                    
                    const detailLink = `/ly-pha-le/${p.slug || p.id}`;
                    const imageUrl = p.main_image || 'https://placehold.co/600x700?text=No+Image';
                    const priceFormatted = new Intl.NumberFormat('vi-VN').format(p.price);
                    let priceHtml = `<span class="current-price-bottom">${priceFormatted} ₫</span>`;
                    if (p.old_price && p.old_price > p.price) {
                        priceHtml = `<span class="original-price-bottom">${new Intl.NumberFormat('vi-VN').format(p.old_price)} ₫</span>${priceHtml}`;
                    }
                    
                    const card = document.createElement('div');
                    card.className = 'similar-product-card';
                    card.innerHTML = `
                        <a href="${detailLink}" class="product-image-link">
                            ${leftBadge}
                            ${rightBadge}
                            <img src="${imageUrl}" alt="${escapeHtml(p.name)}" class="product-image" loading="lazy" onerror="this.src='https://placehold.co/600x700?text=No+Image'">
                        </a>
                        <div class="product-info">
                            <h3><a href="${detailLink}">${escapeHtml(p.name)}</a></h3>
                        </div>
                        <div class="product-details">
                            <div class="detail-item"><i class="fas fa-glass-whiskey"></i><span>${escapeHtml(p.type || 'Ly pha lê')}</span></div>
                            <div class="detail-item"><i class="fas fa-tag"></i><span>${escapeHtml(p.brand || 'Riedel')}</span></div>
                            <div class="detail-item"><i class="fas fa-ruler-vertical"></i><span>${escapeHtml(p.capacity || p.volume || '350ml')}</span></div>
                            <div class="detail-item"><i class="fas fa-box"></i><span>${escapeHtml(p.category || 'Ly rượu vang')}</span></div>
                        </div>
                        <div class="product-bottom-row">
                            <div class="product-bottom-price">${priceHtml}</div>
                            <button class="btn-buy-now" data-id="${p.id}"><i class="fas fa-shopping-cart"></i> Mua</button>
                        </div>
                    `;
                    container.appendChild(card);
                });
                
                // ===== SỰ KIỆN MUA HÀNG VỚI TOAST =====
                document.querySelectorAll('.similar-product-card .btn-buy-now').forEach(btn => {
                    btn.addEventListener('click', async (e) => {
                        e.stopPropagation();
                        const productId = btn.getAttribute('data-id');
                        await addToCart(productId, 1);
                    });
                });
                
                document.getElementById('similarProductsSection').style.display = 'block';
            } catch (e) {
                console.error(e);
                document.getElementById('similarProductsSection').style.display = 'none';
            }
        }

        // ==================== KHỞI CHẠY ====================
        (async () => {
            await updateUserMenu();
            await updateCartCount();
            
            const productId = <?php echo $product ? (int)$product['id'] : 0; ?>;
            if (productId > 0) {
                seedDemoRatingsIfEmpty(productId);
                updateTopRatingDisplay(productId);
                renderRatingSection(productId);
                
                let wishlist = [];
                try { wishlist = await getWishlistFromDB(); } catch(e) { console.warn(e); }
                const isFav = wishlist.includes(productId);
                const favBtn = document.getElementById('favoriteBtn');
                if (favBtn) {
                    if (isFav) {
                        favBtn.classList.add('active');
                        favBtn.innerHTML = '<i class="fas fa-heart"></i> Yêu thích';
                    }
                    favBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        toggleFavorite(productId, 'glass', favBtn);
                    });
                }
                
                setupSocialShare('<?php echo addslashes($product['name'] ?? ''); ?>', window.location.href);
                
                const minus = document.querySelector('.minus');
                const plus = document.querySelector('.plus');
                const qtyInp = document.querySelector('.quantity-input');
                if (minus && plus && qtyInp) {
                    minus.onclick = () => { let v = parseInt(qtyInp.value); if (v > 1) qtyInp.value = v - 1; };
                    plus.onclick = () => { let v = parseInt(qtyInp.value); qtyInp.value = v + 1; };
                }
                
                const addBtn = document.querySelector('.add-to-cart-btn');
                if (addBtn) {
                    addBtn.onclick = async () => {
                        const qty = parseInt(qtyInp.value);
                        await addToCart(productId, qty);
                    };
                }
                
                document.querySelectorAll('.accordion-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        let content = this.nextElementSibling;
                        let icon = this.querySelector('i');
                        if (content.style.maxHeight) {
                            content.style.maxHeight = null;
                            icon.style.transform = 'rotate(0deg)';
                        } else {
                            content.style.maxHeight = content.scrollHeight + 'px';
                            icon.style.transform = 'rotate(180deg)';
                        }
                    });
                });
                
                // Load similar products
                const currentProduct = <?php echo json_encode($product); ?>;
                await loadSimilarProducts(currentProduct);
            }
        })();
        
        // ==================== MODAL & FLOATING PROMO ====================
        document.addEventListener('DOMContentLoaded', () => {
            let lastScrollTop = 0;
            const floating = document.getElementById('floatingPromo');
            
            // Chỉ chạy scroll event trên PC (>=769px)
            if (floating && window.innerWidth >= 769) {
                window.addEventListener('scroll', () => {
                    let st = window.pageYOffset;
                    if (st > lastScrollTop) floating.classList.remove('show');
                    else floating.classList.add('show');
                    lastScrollTop = st <= 0 ? 0 : st;
                });
                setTimeout(() => { if (window.scrollY > 100) floating.classList.add('show'); }, 500);
            }
            
            const modal = document.getElementById('offerModal');
            const openBtns = document.querySelectorAll('.open-offer-modal');
            const closeBtn = document.querySelector('.close-modal');
            const formState = document.getElementById('modalFormState');
            const successState = document.getElementById('modalSuccessState');
            const offerForm = document.getElementById('offerForm');
            const closeSuccess = document.querySelector('.close-success-btn');
            
            if (modal && openBtns.length) {
                openBtns.forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (formState) formState.style.display = 'block';
                        if (successState) successState.style.display = 'none';
                        modal.style.display = 'block';
                        document.body.style.overflow = 'hidden';
                    });
                });
                
                const closeModal = () => {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    if (offerForm) offerForm.reset();
                };
                
                if (closeBtn) closeBtn.addEventListener('click', closeModal);
                window.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
                
                if (offerForm) {
                    offerForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        const name = document.getElementById('name').value.trim();
                        const phone = document.getElementById('phone').value.trim();
                        if (!name || !phone) { alert('Vui lòng điền đầy đủ họ tên và số điện thoại.'); return; }
                        if (formState) formState.style.display = 'none';
                        if (successState) successState.style.display = 'block';
                    });
                }
                
                if (closeSuccess) closeSuccess.addEventListener('click', closeModal);
            }
        });
    </script>
</body>
</html>