<?php
/**
 * Trang chủ Wine VN - Phiên bản động hoàn toàn
 * Tất cả các mục sản phẩm (Rượu vang, Hộp quà sang trọng, Quà tặng rượu vang)
 * đều được lấy từ API products.php
 * Sidebar banner dọc có chiều cao bằng khung sản phẩm
 * Cho phép mua hàng không cần đăng nhập
 */

// Đường dẫn chuẩn đến menu.php dùng __DIR__ để tránh lỗi undefined function
require_once __DIR__ . '/includes/menu.php';

// ===== LẤY DANH SÁCH THƯƠNG HIỆU NỔI BẬT (is_producer=1) - GIỚI HẠN 6 =====
require_once __DIR__ . '/api/config.php'; // Kết nối database (biến $conn)
$brands = [];
if (isset($conn) && $conn) {
    // CHỈ LẤY 6 THƯƠNG HIỆU ĐẦU TIÊN
    $sql = "SELECT id, name, slug, image, description, status, is_producer, level 
            FROM categories 
            WHERE is_producer = 1 AND status = 1 
            ORDER BY name ASC 
            LIMIT 6";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Lấy is_featured từ description (tag [FEATURED:0/1])
            $is_featured = 0;
            if (preg_match('/\[FEATURED:(.*?)\]/', $row['description'] ?? '', $matches)) {
                $is_featured = (int)$matches[1];
            }
            // Lấy country từ description (tag [COUNTRY:...])
            $country = '';
            if (preg_match('/\[COUNTRY:(.*?)\]/', $row['description'] ?? '', $matches)) {
                $country = $matches[1];
            }
            $brands[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'image' => $row['image'],
                'country' => $country,
                'is_featured' => $is_featured,
                'status' => $row['status'],
                'level' => $row['level']
            ];
        }
    }
}

// ===== DANH SÁCH THƯƠNG HIỆU CHO THANH LỌC (tùy chỉnh) =====
$brandFilterList = [
    ['name' => 'Bordeaux', 'slug' => 'Sandrone'],
    ['name' => 'Burgundy', 'slug' => 'burgundy'],
    ['name' => 'Champagne', 'slug' => 'champagne'],
    ['name' => 'San Marzano', 'slug' => 'san-marzano'],
    ['name' => 'Tuscany', 'slug' => 'tuscany'],
    ['name' => 'Rioja', 'slug' => 'rioja'],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Ruouchat - Đại lý rượu vang & rượu mạnh nhập khẩu chính hãng</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="https://winevn.com/images/favicon.png" type="image/x-icon">
    <style>
        /* ===== RESET & GLOBAL ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary-red: #990D23; --dark-red: #670011; --deep-red: #55000D; --gold: #FFBD27; --light-bg: #FBF8EE; --cream: #F2EFE2; --border-color: #E8E8E8; --text-dark: #212529; --text-light: #6C757D; --plum-red: #8B1E1E; }
        body { font-family: 'Inter', sans-serif; color: var(--text-dark); background: white; line-height: 1.6; overflow-x: hidden; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; width: 100%; }
        
        /* ===== RESPONSIVE CONTAINER ===== */
        @media (max-width: 1200px) { .container { padding: 0 16px; } }
        @media (max-width: 768px) { .container { padding: 0 12px; } }
        @media (max-width: 480px) { .container { padding: 0 10px; } }
        
        /* ===== HEADER 2 TẦNG - MÀU TRẮNG, SÁT ĐẦU TRANG ===== */
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
            transition: all 0.1s ease;
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
            .has-dropdown.dropdown-active .dropdown-menu { display: grid; }
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
            /* Ẩn icon home trên mobile */
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

        /* ===== PRODUCT CARD ===== */
        .product-card {
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
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border-color: #5a1212;
        }

        .product-image-link {
            display: block;
            overflow: hidden;
            position: relative;
            background-color: #f9f9f9;
            width: 100%;
            padding-bottom: 116.67%;
            height: 0;
        }

        .product-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: #f9f9f9;
            padding: 10px;
            transition: transform 0.5s ease;
            display: block;
        }

        .product-card:hover .product-image { 
            transform: scale(1.03);
        }

        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--primary-red);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            z-index: 2;
        }
        .product-badge-stock {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            z-index: 2;
            background: #ffc107;
            color: #333;
        }
        .product-badge-stock.discontinued { background: #6c757d; color: white; }
        .product-badge-stock.outstock { background: #dc3545; color: white; }
        .product-badge-stock.soon { background: #17a2b8; color: white; }

        .flash-badge-product {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #eab308;
            color: #1e293b;
            font-size: 10px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 4px;
            z-index: 2;
        }
        .flash-discount {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            z-index: 2;
        }

        .product-info {
            padding: 8px 10px 4px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .product-info h3 {
            font-size: 14px;
            margin-bottom: 4px;
            font-weight: 600;
            line-height: 1.4;
        }
        .product-info h3 a {
            text-decoration: none;
            color: inherit;
            transition: color 0.2s;
        }
        .product-info h3 a:hover { color: var(--primary-red); }
        
        .product-details {
            background: #fafafa;
            padding: 6px 8px;
            margin: 6px 8px 8px 8px;
            border-radius: 8px;
            border: 1px solid #eee;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5px 8px;
        }
        .detail-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 3px 0;
            border-bottom: 1px solid #eaeaea;
            overflow: hidden;
        }
        .detail-item i {
            width: 18px;
            color: var(--primary-red);
            font-size: 11px;
            flex-shrink: 0;
        }
        .detail-item span {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #4a4a4a;
            font-size: 11px;
        }
        
        .product-bottom-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            border-top: 1px solid #eee;
            margin-top: auto;
        }
        .btn-buy-now {
            background: var(--primary-red);
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-buy-now:hover {
            background: var(--dark-red);
            transform: translateY(-2px);
        }
        .product-bottom-price {
            text-align: left;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .current-price-bottom {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary-red);
        }
        .original-price-bottom {
            font-size: 11px;
            color: #6c757d;
            text-decoration: line-through;
            margin-bottom: 2px;
        }

        /* LAYOUT CHO PHẦN RƯỢU VANG - BANNER DỌC */
        .wine-layout {
            display: flex;
            gap: 30px;
            align-items: stretch;
        }
        .wine-sidebar {
            width: 280px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }
        .sidebar-banner-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            display: block;
            flex: 1;
            width: 100%;
            background-color: #f0f0f0;
            transition: transform 0.3s ease;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .sidebar-banner-item:hover {
            transform: translateY(-5px);
        }
        .sidebar-banner-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            transition: transform 0.5s ease;
        }
        .sidebar-banner-item:hover .sidebar-banner-bg {
            transform: scale(1.05);
        }
        .sidebar-banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(0deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0) 100%);
            z-index: 1;
        }
        .sidebar-banner-item:hover .sidebar-banner-overlay {
            background: linear-gradient(0deg, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.2) 100%);
        }
        .sidebar-banner-content {
            position: relative;
            z-index: 2;
            padding: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            color: white;
        }
        .sidebar-banner-brand {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .sidebar-banner-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            background: var(--gold);
            color: var(--text-dark);
            padding: 6px 14px;
            border-radius: 40px;
            width: fit-content;
            margin-top: 10px;
        }
        .wine-products {
            flex: 1;
            min-width: 0;
            min-height: 520px;
        }
        .products-grid {
            max-width: 1200px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 40px;
            justify-content: center; /* Căn giữa các sản phẩm */
        }
        
        /* ===== RESPONSIVE CHO SIDEBAR BANNER ===== */
        @media (max-width: 992px) {
            .wine-layout {
                flex-direction: column;
                align-items: stretch;
            }
            .wine-products {
                order: 1;
            }
            .wine-sidebar {
                width: 100%;
                order: 2;
                margin-top: 20px;
                margin-bottom: 0;
            }
            .sidebar-banner-item {
                min-height: 150px;
                flex: 0 0 auto;
                background-size: cover;
                background-position: center;
            }
            .sidebar-banner-content {
                padding: 15px;
            }
            .sidebar-banner-brand {
                font-size: 16px;
            }
            .sidebar-banner-link {
                padding: 5px 12px;
                font-size: 12px;
            }
            .wine-products {
                min-height: auto;
            }
        }
        
        /* ===== HERO SLIDER ===== */
        .hero { 
            position: relative; 
            width: 100%; 
            overflow: hidden; 
            background: #1a0a0a;
            margin-top: 0; 
        }

        .hero-slider-container { 
            position: relative; 
            width: 100%; 
            height: 480px;
            overflow: hidden; 
            background: #1a0a0a;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1); 
        }

        .hero-slides-wrapper { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            display: flex; 
            transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94); 
        }

        .hero-slide { 
            position: relative; 
            min-width: 100%; 
            height: 100%; 
            background-size: cover; 
            background-position: center; 
            background-repeat: no-repeat; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }

        .hero-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.5) 100%);
            z-index: 1;
        }

        .hero-content { 
            text-align: center; 
            color: white; 
            max-width: 800px; 
            padding: 0 20px; 
            z-index: 3; 
            position: relative; 
        }

        .hero-content h1 { 
            font-size: clamp(26px, 4.5vw, 42px);
            font-family: 'Merriweather', serif; 
            font-weight: 700; 
            margin-bottom: 16px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.5); 
        }

        .hero-content p { 
            font-size: clamp(14px, 2vw, 18px);
            margin-bottom: 28px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.4); 
        }

        .hero-btn { 
            display: inline-flex; 
            align-items: center; 
            gap: 10px; 
            background: linear-gradient(135deg, var(--gold), #FF9D00); 
            color: var(--text-dark); 
            text-decoration: none; 
            padding: 14px 34px;
            border-radius: 50px; 
            font-weight: 700; 
            font-size: 15px;
            transition: all 0.3s ease; 
            box-shadow: 0 4px 15px rgba(255, 189, 39, 0.3);
        }

        .hero-btn:hover { 
            transform: translateY(-3px) scale(1.02); 
            gap: 15px; 
            box-shadow: 0 8px 30px rgba(255, 189, 39, 0.5);
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.95);
            border: none;
            border-radius: 50%;
            font-size: 20px;
            color: var(--primary-red);
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .slider-btn:hover {
            background: var(--primary-red);
            color: white;
            transform: translateY(-50%) scale(1.05);
        }

        .slider-btn i {
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .btn-prev { left: 30px; }
        .btn-next { right: 30px; }

        .slider-dots { 
            position: absolute; 
            bottom: 30px; 
            left: 50%; 
            transform: translateX(-50%); 
            display: flex; 
            gap: 12px; 
            z-index: 10; 
        }

        .slider-dots .dot { 
            width: 14px; 
            height: 14px; 
            border-radius: 50%; 
            background: rgba(255,255,255,0.5); 
            border: none; 
            cursor: pointer; 
            transition: all 0.3s ease;
        }

        .slider-dots .dot.active { 
            width: 40px; 
            border-radius: 20px; 
            background: white; 
        }

        /* ===== RESPONSIVE HERO ===== */
        @media (max-width: 992px) {
            .hero-slider-container { height: 380px; }
            .hero-content h1 { font-size: 30px; }
            .hero-content p { font-size: 16px; }
            .slider-btn {
                width: 45px;
                height: 45px;
                font-size: 16px;
            }
            .btn-prev { left: 15px; }
            .btn-next { right: 15px; }
        }

        @media (max-width: 768px) {
            .hero-slider-container { height: 320px; }
            .hero-content h1 { font-size: 24px; margin-bottom: 12px; }
            .hero-content p { font-size: 14px; margin-bottom: 20px; }
            .hero-btn { 
                padding: 12px 28px; 
                font-size: 14px; 
            }
            .slider-btn {
                width: 40px;
                height: 40px;
                font-size: 14px;
            }
            .btn-prev { left: 12px; }
            .btn-next { right: 12px; }
            .slider-dots { bottom: 20px; gap: 10px; }
            .slider-dots .dot { width: 12px; height: 12px; }
            .slider-dots .dot.active { width: 32px; }
        }

        @media (max-width: 480px) {
            .hero-slider-container { height: 260px; }
            .hero-content h1 { 
                font-size: 20px; 
                margin-bottom: 8px;
                text-shadow: 0 2px 12px rgba(0,0,0,0.6);
            }
            .hero-content p { 
                font-size: 13px; 
                margin-bottom: 14px;
                text-shadow: 0 2px 8px rgba(0,0,0,0.5);
            }
            .hero-btn { 
                padding: 10px 20px; 
                font-size: 12px; 
                gap: 6px;
            }
            .hero-btn i { font-size: 11px; }
            .slider-btn {
                width: 34px;
                height: 34px;
                font-size: 12px;
            }
            .btn-prev { left: 8px; }
            .btn-next { right: 8px; }
            .slider-dots { bottom: 14px; gap: 8px; }
            .slider-dots .dot { width: 10px; height: 10px; }
            .slider-dots .dot.active { width: 26px; }
        }

        @media (max-width: 360px) {
            .hero-slider-container { height: 220px; }
            .hero-content h1 { 
                font-size: 17px; 
                margin-bottom: 6px;
            }
            .hero-content p { 
                font-size: 11px; 
                margin-bottom: 10px;
            }
            .hero-btn { 
                padding: 8px 16px; 
                font-size: 11px; 
            }
            .slider-btn {
                width: 28px;
                height: 28px;
                font-size: 10px;
            }
            .btn-prev { left: 5px; }
            .btn-next { right: 5px; }
            .slider-dots { bottom: 10px; gap: 6px; }
            .slider-dots .dot { width: 8px; height: 8px; }
            .slider-dots .dot.active { width: 20px; }
        }

        /* ===== FEATURES ===== */
        .features { 
            background: var(--deep-red); 
            padding: 25px 0;
        }

        .features-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 20px;
        }

        .feature-item { 
            text-align: center; 
            color: white; 
            border-right: 1px solid rgba(255,255,255,0.2); 
            padding-right: 20px;
        }

        .feature-item:last-child {
            border-right: none;
        }

        .feature-icon { 
            margin-bottom: 12px;
            height: 55px;
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }

        .feature-icon img { 
            max-width: 100%; 
            max-height: 55px;
        }

        .feature-item h3 { 
            font-size: 16px;
            font-weight: 700; 
            margin-bottom: 6px;
        }

        .feature-item p { 
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 0;
        }

        @media (max-width: 992px) {
            .features-grid { 
                grid-template-columns: repeat(2, 1fr); 
                gap: 15px;
            }
            .feature-item { 
                border-right: none; 
                border-bottom: 1px solid rgba(255,255,255,0.2); 
                padding-bottom: 15px;
                padding-right: 0;
            }
            .feature-item:last-child {
                border-bottom: none;
            }
            .feature-icon { 
                height: 50px; 
            }
            .feature-icon img { 
                max-height: 50px; 
            }
        }

        @media (max-width: 480px) {
            .features-grid { 
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .feature-item { 
                border-bottom: 1px solid rgba(255,255,255,0.2); 
                padding-bottom: 12px;
                padding-right: 0;
            }
            .feature-item:last-child {
                border-bottom: none;
            }
            .feature-icon { 
                height: 45px; 
            }
            .feature-icon img { 
                max-height: 45px; 
            }
            .feature-item h3 { 
                font-size: 15px; 
            }
            .feature-item p { 
                font-size: 12px; 
            }
        }

        /* ===== CATEGORIES ===== */
        .categories { background: var(--light-bg); padding: 60px 0; }
        .section-title { text-align: center; color: var(--text-dark); font-size: 32px; font-family: 'Merriweather', serif; font-weight: 700; margin-bottom: 40px; position: relative; padding-bottom: 15px; }
        .section-title::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 300px; height: 3px; background: var(--primary-red); }
        .categories-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 20px; }
        .category-item { background: white; border-radius: 12px; padding: 25px 15px; text-align: center; text-decoration: none; border: 2px solid transparent; transition: all 0.3s ease; display: block; color: black; }
        .category-item:hover { border-color: var(--primary-red); transform: translateY(-5px); }

        /* ===== RESPONSIVE CHO CATEGORIES ===== */
        @media (max-width: 480px) {
            .categories .section-title {
                font-size: 22px;
            }
            .categories-grid {
                gap: 10px;
            }
            .category-item {
                padding: 12px 6px;
                border-radius: 8px;
            }
            .category-item span {
                font-size: 12px;
                display: block;
                margin-top: 4px;
            }
            .category-icon img {
                max-width: 32px;
                height: auto;
            }
        }

        .view-all { text-align: center; margin-top: 20px; }
        .view-all-btn { display: inline-block; background: var(--primary-red); color: white; padding: 12px 30px; border-radius: 40px; text-decoration: none; font-weight: 600; transition: all 0.3s; }
        .view-all-btn:hover { background: var(--dark-red); transform: translateY(-2px); }

        .wine { margin-bottom: 50px; margin-top: 50px; }
        .luxury-gifts { margin-bottom: 50px; }
        .tet-gifts { margin-bottom: 50px; margin-top: 50px; }

        .featured-showcase { padding: 60px 0; background: linear-gradient(135deg, #fff8f0 0%, #fef5e9 100%); margin-top: 50px; }
        .showcase-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }
        .showcase-item { position: relative; border-radius: 0px; overflow: hidden; text-decoration: none; display: block; width: 100%; height: 250px; transition: transform 0.3s ease; box-shadow: 0 12px 28px rgba(0,0,0,0.1); }
        .showcase-item:hover { transform: translateY(-8px); }
        .showcase-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; transition: transform 0.5s ease; }
        .showcase-item:hover .showcase-bg { transform: scale(1.05); }
        .showcase-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(0deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0) 100%); z-index: 1; }
        .showcase-item:hover .showcase-overlay { background: linear-gradient(0deg, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.2) 100%); }
        .showcase-content { position: relative; z-index: 2; padding: 30px 25px; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; color: white; }
        .showcase-link { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 14px; text-transform: uppercase; background: var(--gold); color: var(--text-dark); padding: 8px 18px; border-radius: 40px; width: fit-content; }

        /* ===== SLIDER THƯƠNG HIỆU (thay thế wine-types) ===== */
        .featured-brands {
            padding: 60px 0;
            background: #ffffff;
        }
        .featured-brands .section-title {
            text-align: center;
            font-size: clamp(24px, 5vw, 34px);
            color: var(--primary-red);
            margin-bottom: 40px;
            font-weight: 700;
            letter-spacing: 0.5px;
            position: relative;
            line-height: 1.2;
        }
        .featured-brands .section-title::after {
            content: '';
            display: block;
            width: min(90%, 580px);
            height: 3px;
            background: var(--gold);
            margin: 18px auto 0;
        }
        .slider-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .slider-container {
            overflow: hidden;
            width: 100%;
            padding: 0 55px;
        }
        .slider-track {
            display: flex;
            gap: clamp(15px, 3vw, 25px);
            scroll-behavior: smooth;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding: 8px 0;
        }
        .slider-track::-webkit-scrollbar {
            display: none;
        }
        .brand-item {
            flex: 0 0 auto;
            width: clamp(200px, 32vw, 220px);
            height: clamp(180px, 30vw, 170px);
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            padding: clamp(12px, 2.5vw, 18px);
        }
        .brand-item:hover {
            border-color: var(--primary-red);
            background: white;
            box-shadow: 0 12px 24px rgba(139,0,0,0.12);
            transform: translateY(-5px);
        }
        .brand-item img {
            width: auto;
            height: auto;
            max-width: 90%;
            max-height: 150px;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .brand-name {
            font-weight: 700;
            font-size: clamp(14px, 3vw, 18px);
            color: var(--primary-red);
            margin-bottom: 4px;
            line-height: 1.2;
            padding: 0 4px;
        }
        .brand-origin {
            font-size: clamp(11px, 2.2vw, 13px);
            color: var(--text-light);
            font-style: italic;
            padding: 0 4px;
            line-height: 1.3;
        }
        .slider-prev,
        .slider-next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: clamp(40px, 8vw, 44px);
            height: clamp(40px, 8vw, 44px);
            border-radius: 50%;
            background: white;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            color: var(--primary-red);
            font-size: clamp(16px, 3.5vw, 20px);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            z-index: 10;
        }
        .slider-prev:hover,
        .slider-next:hover {
            background: var(--primary-red);
            color: white;
            border-color: var(--primary-red);
        }
        .slider-prev {
            left: clamp(5px, 2vw, 12px);
        }
        .slider-next {
            right: clamp(5px, 2vw, 12px);
        }

        /* Responsive cho slider thương hiệu */
        @media (max-width: 768px) {
            .slider-container {
                padding: 0 40px;
            }
            .brand-item {
                width: clamp(170px, 30vw, 220px);
                height: auto;
                min-height: 170px;
            }
            .brand-item img {
                max-height: 120px;
            }
        }
        @media (max-width: 480px) {
            .slider-container {
                padding: 0 32px;
            }
            .brand-item {
                width: 160px;
                min-height: 150px;
                padding: 10px;
            }
            .brand-item img {
                max-height: 100px;
            }
            .brand-name {
                font-size: 13px;
            }
            .brand-origin {
                font-size: 11px;
            }
            .slider-prev,
            .slider-next {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }
            .slider-prev {
                left: 2px;
            }
            .slider-next {
                right: 2px;
            }
        }

        .news-section { padding: 70px 0; background: #fffaf5; }
        .news-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px; }
        .news-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: all 0.3s; text-decoration: none; color: var(--text-dark); display: flex; flex-direction: column; height: 100%; }
        .news-card:hover { transform: translateY(-5px); }
        .news-img { width: 100%; height: 200px; object-fit: cover; }
        .news-content { padding: 20px 20px 25px; flex: 1; }
        .news-title { font-size: 18px; font-weight: 700; font-family: 'Merriweather', serif; line-height: 1.4; margin-bottom: 12px; }
        .news-desc {
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-wrap: break-word;
            font-size: 14px;
            color: #5a5a5a;
            line-height: 1.5;
            margin-bottom: 16px;
        }
        .news-more { color: var(--primary-red); font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 5px; }

        /* ===== FLASH SALE ===== */
        .flash-sale-section {
            background: linear-gradient(145deg, #fff5eb 0%, #ffe8da 100%);
            padding: 50px 0;
            border-bottom: 1px solid rgba(153,13,35,0.1);
        }
        .flash-sale-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .flash-sale-title {
            font-size: 30px;
            font-weight: 800;
            color: var(--primary-red);
            text-transform: uppercase;
            letter-spacing: 3px;
            font-family: 'Merriweather', serif;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .flash-sale-title i {
            color: #fbbf24;
            font-size: 24px;
        }

        .flash-countdown {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            margin-top: 0;
        }
        .flash-countdown .label {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark);
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .flash-countdown .time-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .countdown-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 50px;
        }
        .countdown-item .number {
            font-size: 2.2rem;
            font-weight: 800;
            color: #fbbf24;
            font-variant-numeric: tabular-nums;
            letter-spacing: 1px;
            line-height: 1.2;
            text-shadow: 0 0 15px rgba(251, 191, 36, 0.25);
        }
        .countdown-item .unit {
            font-size: 10px;
            text-transform: uppercase;
            color: var(--text-dark);
            opacity: 0.5;
            letter-spacing: 1px;
            margin-top: 2px;
        }
        .countdown-separator {
            font-size: 2rem;
            font-weight: 300;
            color: var(--text-dark);
            opacity: 0.2;
            padding: 0 2px;
            margin-top: -6px;
        }
        .countdown-item.pulse .number {
            animation: countdownPulse 0.5s ease-in-out infinite alternate;
        }

        @keyframes countdownPulse {
            0% { opacity: 1; transform: scale(1); }
            100% { opacity: 0.5; transform: scale(0.95); }
        }

        /* ===== FOOTER ===== */
        .main-footer { background: var(--dark-red) !important; color: white; padding-top: 50px; margin-top: 0; width: 100%; }
        .footer-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 40px; padding-bottom: 40px; }
        .footer-section h4 { font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; color: white; }
        .footer-section p, .footer-section a { margin-bottom: 10px; font-size: 14px; opacity: 0.9; color: rgba(255,255,255,0.9); text-decoration: none; display: flex; align-items: center; gap: 6px; }
        .footer-section a:hover { color: white; }
        .social-links { display: flex; gap: 15px; margin-top: 20px; }
        .social-links a { width: 36px; height: 36px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; }
        .social-links a:hover { background: var(--primary-red); }
        .newsletter { display: flex; margin-top: 15px; margin-bottom: 25px; }
        .newsletter input { flex: 1; padding: 12px 15px; border: none; border-radius: 6px 0 0 6px; outline: none; }
        .newsletter button { background: var(--gold); color: var(--dark-red); border: none; padding: 0 20px; border-radius: 0 6px 6px 0; cursor: pointer; font-weight: 600; }
        .payment-methods { display: flex; gap: 15px; font-size: 24px; margin-top: 20px; }
        .footer-bottom { text-align: center; padding: 20px 0; border-top: 1px solid rgba(255,255,255,0.1); font-size: 13px; }

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

        /* ===== RESPONSIVE CHUNG ===== */
        @media (max-width: 1200px) {
            .products-grid { gap: 20px; }
        }
        @media (max-width: 992px) {
            .products-grid { grid-template-columns: repeat(3, 1fr); gap: 18px; }
            .categories-grid { grid-template-columns: repeat(3, 1fr); }
            .footer-container { grid-template-columns: repeat(2, 1fr); }
            .news-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .products-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
            .categories-grid { grid-template-columns: repeat(2, 1fr); }
            .showcase-grid { grid-template-columns: 1fr; }
            .news-grid { grid-template-columns: 1fr; }
            .detail-item span { white-space: normal; }
        }
        @media (max-width: 480px) {
            .products-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 12px; }
            .footer-container { grid-template-columns: 1fr; }
            .categories-grid { grid-template-columns: repeat(2,1fr); }
            .header { padding: 0 !important; }
            .logo img { height: 34px; }
            .search-bar { order: 3; width: 100%; max-width: 100%; margin-top: 6px; }
            .header-right { gap: 10px; }
            .cart-count, .wishlist-count { top: -8px; right: -10px; width: 17px; height: 17px; font-size: 10px; }
            .product-info { padding: 6px 8px; }
            .product-info h3 { font-size: 12px; }
            .product-details { 
                padding: 5px 6px; 
                margin: 4px 6px 6px 6px; 
                gap: 4px 6px;
                grid-template-columns: repeat(2, 1fr);
            }
            .detail-item { gap: 4px; }
            .detail-item i { width: 18px; font-size: 10px; }
            .detail-item span { font-size: 10px; white-space: normal; }
            .btn-buy-now { padding: 4px 8px; font-size: 10px; }
            .current-price-bottom { font-size: 12px; }
            .original-price-bottom { font-size: 10px; }
            .sidebar-banner-item { min-height: 120px; }
        }
        
        body.has-header-fixed .hero {
            margin-top: var(--header-height, 120px);
        }

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

        /* Brand filter bar */
        .brand-filter-bar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px 16px;
            margin-bottom: 24px;
            padding: 10px 0;
            border-bottom: 2px solid #eee;
        }
        .brand-filter-label {
            font-weight: 700;
            font-size: 15px;
            color: var(--text-dark);
            margin-right: 8px;
        }
        .brand-filter-buttons {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px 12px;
        }
        .brand-filter-btn {
            background: none;
            border: none;
            padding: 4px 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            transition: color 0.2s, transform 0.2s;
            position: relative;
        }
        .brand-filter-btn:not(:last-child)::after {
            content: '|';
            color: #ccc;
            margin-left: 12px;
            font-weight: 300;
        }
        .brand-filter-btn:hover {
            color: var(--primary-red);
            transform: translateY(-1px);
        }
        .brand-filter-btn.active {
            color: var(--primary-red);
            font-weight: 700;
        }
        .brand-filter-btn.active::before {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-red);
            border-radius: 3px;
        }
        @media (max-width: 480px) {
            .brand-filter-bar {
                gap: 6px 12px;
                padding: 8px 0;
            }
            .brand-filter-btn {
                font-size: 13px;
                padding: 2px 0;
            }
            .brand-filter-btn:not(:last-child)::after {
                margin-left: 8px;
            }
        }

        /* Căn giữa thanh lọc sản phẩm khác và bia ngoại */
        #otherBrandFilterBar,
        #beerFilterBar {
            justify-content: center;
        }
        #otherBrandFilterBar .brand-filter-label,
        #beerFilterBar .brand-filter-label {
            display: none;
        }
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

<header class="header">
    <div class="container">
        <div class="header-top">
            <div class="logo">
                <a href="index.php">
                    <img src="assets/images/logo.jpg" alt="Ruouchat - Rượu Vang Nhập Khẩu">
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
                    <?php echo buildMenuHTML($menuItems, 0); ?>
                </ul>
            </nav>
        </div>
    </div>
</header>

<!-- HERO SECTION -->
<section class="hero">
    <div class="hero-slider-container">
        <div class="hero-slides-wrapper" id="heroSlidesWrapper"></div>
        <button class="slider-btn btn-prev" id="prevSlide" aria-label="Slide trước">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="slider-btn btn-next" id="nextSlide" aria-label="Slide sau">
            <i class="fas fa-chevron-right"></i>
        </button>
        <div class="slider-dots" id="sliderDots"></div>
    </div>
</section>

<!-- FEATURES -->
<section class="features">
    <div class="container">
        <div class="features-grid">
            <div class="feature-item"><div class="feature-icon"><img src="assets/images/champagne-glasses-solid.png" alt="6000 sản phẩm"></div><h3>6000 SẢN PHẨM</h3><p>Nhập khẩu và phân phối chính hãng</p></div>
            <div class="feature-item"><div class="feature-icon"><img src="assets/images/dollar-sign-solid.png" alt="Báo giá cạnh tranh"></div><h3>BÁO GIÁ CẠNH TRANH</h3><p>Giá tốt nhất thị trường, ưu đãi số lượng lớn</p></div>
            <div class="feature-item"><div class="feature-icon"><img src="assets/images/truck-fast-solid.png" alt="Miễn phí giao hàng"></div><h3>MIỄN PHÍ GIAO HÀNG</h3><p>Miễn phí ship nội thành từ 6 chai</p></div>
            <div class="feature-item"><div class="feature-icon"><img src="assets/images/award-solid.png" alt="Cam kết chất lượng"></div><h3>CAM KẾT CHẤT LƯỢNG</h3><p>Chính sách đổi trả minh bạch, miễn phí</p></div>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<section class="categories">
    <div class="container">
        <h2 class="section-title">DANH MỤC SẢN PHẨM</h2>
        <div class="categories-grid">
            <a href="product_main.php?product_type=1" class="category-item"><div class="category-icon"><img src="assets/images/Red-wine-1.svg" alt="Rượu vang"></div><span>Rượu vang</span></a>
            <a href="product_main.php?cat_id=10" class="category-item"><div class="category-icon"><img src="assets/images/Red-wine.svg" alt="Rượu vang bịch"></div><span>Rượu vang bịch</span></a>
            <a href="product_main.php?spirit_type=Rượu whisky" class="category-item"><div class="category-icon"><img src="assets/images/Red-wine-2.svg" alt="Rượu Whisky"></div><span>Rượu Whisky</span></a>
            <a href="product_main.php?spirit_type=Rượu Rum" class="category-item"><div class="category-icon"><img src="assets/images/Red-wine-3.svg" alt="Rượu Rum"></div><span>Rượu Rum</span></a>
            <a href="product_main.php?spirit_type=Rượu Gin" class="category-item"><div class="category-icon"><img src="assets/images/Red-wine-4.svg" alt="Rượu Gin"></div><span>Rượu Gin</span></a>
            <a href="product_main.php?spirit_type=Rượu Gin" class="category-item"><div class="category-icon"><img src="assets/images/Red-wine-5.svg" alt="Rượu Liqueur"></div><span>Rượu Liqueur</span></a>
            <a href="product_main.php?spirit_type=Rượu Gin" class="category-item"><div class="category-icon"><img src="assets/images/Red-wine-7.svg" alt="Rượu Cognac"></div><span>Rượu Cognac</span></a>
            <a href="product_main.php?spirit_type=Rượu Gin" class="category-item"><div class="category-icon"><img src="assets/images/Red-wine-8.svg" alt="Rượu Vodka"></div><span>Rượu Vodka</span></a>
            <a href="product_main.php?spirit_type=Rượu Gin" class="category-item"><div class="category-icon"><img src="assets/images/Red-wine-9.svg" alt="Rượu Armagnac"></div><span>Rượu Armagnac</span></a>
            <a href="product_main.php?spirit_type=Rượu Gin" class="category-item"><div class="category-icon"><img src="assets/images/Red-wine-6.svg" alt="Rượu Champagne"></div><span>Rượu Champagne</span></a>
            <a href="product_main.php?spirit_type=Rượu Gin" class="category-item"><div class="category-icon"><img src="assets/images/Red-wine-10.svg" alt="Rượu Tequila"></div><span>Rượu Tequila</span></a>
            <a href="sanphamkhac.php" class="category-item"><div class="category-icon"><img src="assets/images/Red-wine-11.svg" alt="Phụ kiện & Bia"></div><span>Phụ kiện & Bia</span></a>
        </div>
    </div>
</section>

<!-- FLASH SALE SECTION -->
<section class="flash-sale-section" id="flashSaleSection" style="display: none;">
    <div class="container">
        <div class="flash-sale-header">
            <h2 class="flash-sale-title">FLASH SALE</h2>
            <div class="flash-countdown" id="flashCountdown">
                <span class="label">Kết thúc sau</span>
                <div class="time-wrapper">
                    <div class="countdown-item hours">
                        <span class="number" id="countdown-hours">00</span>
                        <span class="unit">Giờ</span>
                    </div>
                    <span class="countdown-separator">:</span>
                    <div class="countdown-item minutes">
                        <span class="number" id="countdown-minutes">00</span>
                        <span class="unit">Phút</span>
                    </div>
                    <span class="countdown-separator">:</span>
                    <div class="countdown-item seconds">
                        <span class="number" id="countdown-seconds">00</span>
                        <span class="unit">Giây</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="products-grid" id="flashSaleProductsGrid"></div>
    </div>
</section>

<!-- RƯỢU VANG -->
<section class="wine">
    <div class="container">
        <h2 class="section-title">RƯỢU VANG</h2>
        <div class="wine-layout">
            <aside class="wine-sidebar" id="wineSidebar"></aside>
            <div class="wine-products">
                <div class="brand-filter-bar" id="brandFilterBar">
                    <span class="brand-filter-label">Thương hiệu:</span>
                    <div class="brand-filter-buttons">
                        <button class="brand-filter-btn active" data-brand="">Tất cả</button>
                        <?php foreach ($brandFilterList as $brand): ?>
                        <button class="brand-filter-btn" data-brand="<?php echo htmlspecialchars($brand['slug']); ?>"><?php echo htmlspecialchars($brand['name']); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="products-grid" id="featuredProducts"></div>
                <div class="view-all"><a href="product_main.php?product_type=1" class="view-all-btn">Xem tất cả <i class="fas fa-arrow-right"></i></a></div>
            </div>
        </div>
    </div>
</section>

<!-- SẢN PHẨM KHÁC -->
<section class="luxury-gifts">
    <div class="container">
        <h2 class="section-title">SẢN PHẨM KHÁC</h2>
        <div class="brand-filter-bar" id="otherBrandFilterBar">
            <div class="brand-filter-buttons">
                <button class="brand-filter-btn active" data-category="">Tất cả</button>
                <button class="brand-filter-btn" data-category="ly-ruou-vang">Ly Rượu Vang</button>
                <button class="brand-filter-btn" data-category="decanter">Decanter</button>
                <button class="brand-filter-btn" data-category="nuoc-khoang">Nước Khoáng</button>
                <button class="brand-filter-btn" data-category="phu-kien">Phụ Kiện</button>
            </div>
        </div>
        <div class="products-grid" id="otherProductsGrid"></div>
        <div class="view-all"><a href="product_main.php?cat_id=3" class="view-all-btn">Xem tất cả <i class="fas fa-arrow-right"></i></a></div>
    </div>
</section>

<!-- ===== BIA NGOẠI (thêm mới) ===== -->
<section class="luxury-gifts">
    <div class="container">
        <h2 class="section-title">BIA NGOẠI</h2>
        <!-- Thanh lọc căn giữa, các loại bia -->
        <div class="brand-filter-bar" id="beerFilterBar">
            <div class="brand-filter-buttons">
                <button class="brand-filter-btn active" data-category="">Tất cả</button>
                <button class="brand-filter-btn" data-category="Bỉ">Bia Bỉ</button>
                <button class="brand-filter-btn" data-category="Tiệp">Bia Tiệp</button>
                <button class="brand-filter-btn" data-category="Đức">Bia Đức</button>
                <button class="brand-filter-btn" data-category="Hà Lan">Bia Hà Lan</button>
                <button class="brand-filter-btn" data-category="Mỹ">Bia Mỹ</button>
            </div>
        </div>
        <div class="products-grid" id="beerProductsGrid"></div>
        <div class="view-all"><a href="product_main.php?product_type=3" class="view-all-btn">Xem tất cả <i class="fas fa-arrow-right"></i></a></div>
    </div>
</section>

<!-- HỘP QUÀ RƯỢU SANG TRỌNG -->
<section class="luxury-gifts">
    <div class="container">
        <h2 class="section-title">HỘP QUÀ RƯỢU SANG TRỌNG</h2>
        <div class="products-grid" id="luxuryGiftBoxesGrid"></div>
        <div class="view-all"><a href="product_main.php?cat_id=5" class="view-all-btn">Xem tất cả <i class="fas fa-arrow-right"></i></a></div>
    </div>
</section>

<!-- BANNER QUẢNG CÁO -->
<section class="featured-showcase">
    <div class="container">
        <div class="showcase-grid" id="dynamicBannersGrid"></div>
    </div>
</section>

<!-- QUÀ TẶNG RƯỢU VANG -->
<section class="tet-gifts">
    <div class="container">
        <h2 class="section-title">QUÀ TẶNG RƯỢU VANG</h2>
        <div class="products-grid" id="giftProductsGrid"></div>
        <div class="view-all"><a href="product_main.php?cat_id=4" class="view-all-btn">Xem tất cả <i class="fas fa-arrow-right"></i></a></div>
    </div>
</section>

<!-- SLIDER THƯƠNG HIỆU NỔI BẬT -->
<section class="featured-brands">
    <div class="container">
        <h2 class="section-title">THƯƠNG HIỆU NỔI BẬT</h2>
        <div class="slider-wrapper">
            <button class="slider-prev" aria-label="Previous">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="slider-container">
                <div class="slider-track">
                    <?php if (!empty($brands)): ?>
                        <?php foreach ($brands as $brand): ?>
                        <div class="brand-item">
                            <a href="product_main.php?brand=<?php echo urlencode($brand['slug']); ?>" style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; text-decoration: none;">
                                <?php if (!empty($brand['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($brand['image']); ?>" alt="<?php echo htmlspecialchars($brand['name']); ?>">
                                <?php else: ?>
                                    <div style="width: 100%; max-width: 180px; height: 120px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-bottom: 8px;">
                                        <i class="fas fa-image fa-3x" style="color: #ccc;"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="brand-name"><?php echo htmlspecialchars($brand['name']); ?></div>
                                <div class="brand-origin"><?php echo htmlspecialchars($brand['country'] ?? ''); ?></div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="brand-item" style="width: 100%; text-align: center; padding: 20px;">Chưa có thương hiệu nào</div>
                    <?php endif; ?>
                </div>
            </div>
            <button class="slider-next" aria-label="Next">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<!-- TIN TỨC -->
<section class="news-section">
    <div class="container">
        <h2 class="section-title">TIN TỨC MỚI NHẤT</h2>
        <div class="news-grid" id="newsGrid"></div>
    </div>
</section>

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
            <p>Nhận thông tin khuyến mãi mới nhất</p>
            <div class="newsletter"><input type="email" placeholder="Email của bạn"><button><i class="fas fa-paper-plane"></i></button></div>
            <div class="payment-methods">
                <a href="#"><i class="fab fa-cc-visa"></i></a>
                <a href="#"><i class="fab fa-cc-mastercard"></i></a>
                <a href="#"><i class="fab fa-cc-paypal"></i></a>
                <a href="#"><i class="fas fa-credit-card"></i></a>
                <a href="#"><i class="fas fa-qrcode"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom"><p>© 2026 Ruouchat.vn - Chuyên Rượu Vang Nhập Khẩu Chính Hãng.</p></div>
</footer>

<script>
    // ==================== CẤU HÌNH ====================
    const API_BASE = '/api/';
    
    // ==================== HÀM TIỆN ÍCH ====================
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;' }[m]));
    }
    
    async function callAPI(url, options = {}) {
        try {
            const response = await fetch(API_BASE + url, {
                ...options,
                headers: { 'Content-Type': 'application/json', ...options.headers },
                credentials: 'include'
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Lỗi');
            return data;
        } catch(e) {
            console.error('API Error:', e);
            return null;
        }
    }
    
    function formatPrice(price) {
        if (!price) return '0₫';
        return price.toLocaleString('vi-VN') + '₫';
    }
    
    function getBrandName(product) {
        if (product.brand && product.brand.trim()) return product.brand;
        if (product.supplier_name && product.supplier_name.trim()) return product.supplier_name;
        if (product.producer && product.producer.trim()) return product.producer;
        if (product.producer_name && product.producer_name.trim()) return product.producer_name;
        return '';
    }

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
    
    // ==================== HEADER HIDE/SHOW ====================
    let lastScroll = 0;
    const headerElem = document.querySelector('.header');
    const scrollThreshold = 100;
    
    function adjustHeroMarginTop() {
        const heroElem = document.querySelector('.hero');
        if (headerElem && heroElem) {
            let headerHeight = headerElem.offsetHeight;
            heroElem.style.marginTop = headerHeight + 'px';
            document.body.classList.add('has-header-fixed');
            document.body.style.setProperty('--header-height', headerHeight + 'px');
        }
    }
    
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
        adjustHeroMarginTop();
    }, 100);
    window.addEventListener('load', adjustHeroMarginTop);
    window.addEventListener('resize', adjustHeroMarginTop);
    
    // ==================== CART & WISHLIST ====================
    async function updateCartCount() {
        try {
            const res = await fetch(API_BASE + 'cart.php', { credentials: 'include' });
            const cart = await res.json();
            const totalQty = Object.values(cart).reduce((sum, item) => sum + (item.quantity || 0), 0);
            const badge = document.querySelector('.cart-count');
            if (badge) badge.innerText = totalQty;
        } catch(e) { console.error(e); }
    }
    
    async function addToCart(productId) {
        try {
            const res = await fetch(API_BASE + 'cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add', product_id: productId, quantity: 1 }),
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
    
    async function getWishlistFromDB() {
        try {
            const res = await fetch(API_BASE + 'wishlist.php', { credentials: 'include' });
            const data = await res.json();
            return data.success && Array.isArray(data.data) ? data.data.map(item => item.product_id) : [];
        } catch(e) { return []; }
    }
    
    async function addToWishlistDB(productId, productType) {
        const res = await fetch(API_BASE + 'wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: Number(productId), product_type: Number(productType) }),
            credentials: 'include'
        });
        const data = await res.json();
        if (data.success) return true;
        if (data.error && data.error.includes('đăng nhập')) {
            showToast('💙 Yêu thích', 'Đăng nhập để lưu sản phẩm yêu thích!');
            return false;
        }
        return false;
    }
    
    async function removeFromWishlistDB(productId, productType) {
        const res = await fetch(API_BASE + 'wishlist.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: Number(productId), product_type: Number(productType) }),
            credentials: 'include'
        });
        const data = await res.json();
        return data.success === true;
    }
    
    async function toggleFavorite(productId, productType, btnElement) {
        let wishlist = await getWishlistFromDB();
        const isFav = wishlist.includes(productId);
        let success = isFav ? await removeFromWishlistDB(productId, productType) : await addToWishlistDB(productId, productType);
        if (success) {
            if (btnElement) {
                if (!isFav) {
                    btnElement.classList.add('active-wishlist');
                    btnElement.innerHTML = '<i class="fas fa-heart"></i>';
                } else {
                    btnElement.classList.remove('active-wishlist');
                    btnElement.innerHTML = '<i class="far fa-heart"></i>';
                }
            }
            await updateWishlistCount();
        }
    }
    
    async function updateWishlistCount() {
        const wishlist = await getWishlistFromDB();
        const countSpan = document.querySelector('.wishlist-count');
        if (countSpan) countSpan.innerText = wishlist.length;
    }
    
    // ==================== RENDER SẢN PHẨM CHUNG ====================
    function renderProducts(containerId, products, badgeText = null, isFlash = false, categoryField = null) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';
        if (!products || products.length === 0) {
            container.innerHTML = `<div style="grid-column: 1 / -1; text-align:center; padding:40px; display:flex; align-items:center; justify-content:center; min-height:300px; color:#888;">Chưa có sản phẩm nào</div>`;
            return;
        }
        
        // Giới hạn số sản phẩm hiển thị tối đa 4 cho các danh mục (trừ rượu vang)
        let displayProducts = products;
        if (containerId !== 'featuredProducts' && containerId !== 'flashSaleProductsGrid' && products.length > 4) {
            displayProducts = products.slice(0, 4);
        }
        
        displayProducts.forEach(p => {
            let detailLink = '#';
            if (p.product_type == 2) {
                detailLink = `/ruou-manh/${p.slug || 'san-pham'}`;
            } else {
                detailLink = `/ruou-vang/${p.slug || 'san-pham'}`;
            }
            
            let leftBadge = '';
            if (isFlash) {
                leftBadge = `<div class="flash-badge-product"><i class="fas fa-bolt"></i> FLASH</div>`;
            } else if (badgeText) {
                leftBadge = `<div class="product-badge">${escapeHtml(badgeText)}</div>`;
            } else if (p.old_price && p.old_price > p.price) {
                const percent = Math.round((1 - p.price / p.old_price) * 100);
                leftBadge = `<div class="product-badge">-${percent}%</div>`;
            } else if (p.discount_percent) {
                leftBadge = `<div class="product-badge">-${p.discount_percent}%</div>`;
            }
            
            let rightBadge = '';
            if (p.display_status === 'coming_soon') {
                rightBadge = '<div class="product-badge-stock soon">Sắp về</div>';
            } else if (p.display_status === 'discontinued') {
                rightBadge = '<div class="product-badge-stock discontinued">Ngừng bán</div>';
            } else if ((p.stock_quantity || 0) <= 0 && !isFlash) {
                rightBadge = '<div class="product-badge-stock outstock">Hết hàng</div>';
            }
            
            if (isFlash && p.flash_discount) {
                rightBadge = `<div class="flash-discount">-${p.flash_discount}%</div>`;
            } else if (isFlash && p.discount_percent) {
                rightBadge = `<div class="flash-discount">-${p.discount_percent}%</div>`;
            }
            
            const imageUrl = p.main_image || 'https://placehold.co/600x700?text=No+Image';
            const price = isFlash ? (p.flash_price || p.price) : p.price;
            const oldPrice = isFlash ? (p.original_price || p.old_price) : p.old_price;
            const priceFormatted = formatPrice(price);
            
            let priceHtml = `<span class="current-price-bottom">${priceFormatted}</span>`;
            if (oldPrice && oldPrice > price) {
                priceHtml = `<span class="original-price-bottom">${formatPrice(oldPrice)}</span>${priceHtml}`;
            }
            
            // ====== GIỐNG NHO ======
            let grape = p.grape_name || p.grape_type_display || p.grape_type || 'Chưa cập nhật';
            if (typeof grape === 'string' && /^\d+$/.test(grape)) grape = getGrapeName(grape);

            // ====== PHÂN LOẠI ======
            const wineType = p.wine_type || (p.product_type == 1 ? 'Rượu vang' : 'Rượu mạnh');

            // ====== NSX / THƯƠNG HIỆU ======
            const brand = getBrandName(p) || 'Chưa cập nhật';

            // ====== XUẤT XỨ ======
            let country = p.country_name || p.country_display || p.country || p.xuat_xu || '';
            if (typeof country === 'string' && /^\d+$/.test(country)) country = getCountryName(country);
            if (!country || country === 'Chưa cập nhật') {
                let region = p.region_name || p.region_display || p.region || '';
                if (typeof region === 'string' && /^\d+$/.test(region)) region = getRegionName(region);
                country = region || 'Chưa cập nhật';
            }

            // ====== NỒNG ĐỘ ======
            const abv = p.abv || p.alcohol || p.nong_do || '13%';

            // ====== DUNG TÍCH ======
            const volume = p.volume || p.dung_tich || '750ml';
            
            let categoryValue = '';
            if (categoryField && p[categoryField]) {
                categoryValue = p[categoryField];
            }
            
            const card = document.createElement('div');
            card.className = 'product-card';
            if (categoryValue) {
                card.setAttribute('data-category', categoryValue);
            }
            card.setAttribute('data-href', detailLink);
            card.innerHTML = `
                <a href="${detailLink}" class="product-image-link">
                    ${leftBadge}
                    ${rightBadge}
                    <img src="${imageUrl}" alt="${escapeHtml(p.name)}" class="product-image" loading="lazy" 
                         onerror="this.src='https://placehold.co/600x700?text=No+Image'">
                </a>
                <div class="product-info">
                    <h3><a href="${detailLink}">${escapeHtml(p.name)}</a></h3>
                </div>
                <div class="product-details">
                    <div class="detail-item"><i class="fa-solid fa-tag"></i><span>${escapeHtml(grape)}</span></div>
                    <div class="detail-item"><i class="fas fa-wine-glass-alt"></i><span>${escapeHtml(wineType)}</span></div>
                    <div class="detail-item"><i class="fas fa-building"></i><span>${escapeHtml(brand)}</span></div>
                    <div class="detail-item"><i class="fas fa-flag"></i><span>${escapeHtml(country)}</span></div>
                    <div class="detail-item"><i class="fas fa-percent"></i><span>${escapeHtml(abv)}</span></div>
                    <div class="detail-item"><i class="fas fa-flask"></i><span>${escapeHtml(volume)}</span></div>
                </div>
                <div class="product-bottom-row">
                    <div class="product-bottom-price">${priceHtml}</div>
                    <button class="btn-buy-now" data-id="${p.id}"><i class="fas fa-shopping-cart"></i> Mua</button>
                </div>
            `;
            container.appendChild(card);
        });
        
        container.querySelectorAll('.btn-buy-now').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                await addToCart(btn.dataset.id);
            });
        });
    }
    
    // ==================== HÀM LỌC VÀ GIỚI HẠN 4 SẢN PHẨM MỖI DANH MỤC ====================
    function groupAndLimitProducts(products, groupKey, limit = 4) {
        const groups = {};
        products.forEach(p => {
            const key = p[groupKey] || '';
            if (!groups[key]) groups[key] = [];
            groups[key].push(p);
        });
        let result = [];
        for (let key in groups) {
            const items = groups[key].slice(0, limit);
            result = result.concat(items);
        }
        return result;
    }
    
    // ==================== KHÓA CHIỀU CAO KHUNG RƯỢU VANG ====================
    let wineProductsMinHeight = 0;

    function lockWineProductsHeight() {
        const wineProductsEl = document.querySelector('.wine-products');
        if (!wineProductsEl) return;
        wineProductsEl.style.minHeight = '';
        const naturalHeight = wineProductsEl.offsetHeight;
        if (naturalHeight > wineProductsMinHeight) {
            wineProductsMinHeight = naturalHeight;
        }
        wineProductsEl.style.minHeight = wineProductsMinHeight + 'px';
    }

    function applyLockedWineProductsHeight() {
        const wineProductsEl = document.querySelector('.wine-products');
        if (wineProductsEl && wineProductsMinHeight > 0) {
            wineProductsEl.style.minHeight = wineProductsMinHeight + 'px';
        }
    }

    let wineHeightResizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(wineHeightResizeTimer);
        wineHeightResizeTimer = setTimeout(() => {
            wineProductsMinHeight = 0;
            const wineProductsEl = document.querySelector('.wine-products');
            if (wineProductsEl) wineProductsEl.style.minHeight = '';
            requestAnimationFrame(lockWineProductsHeight);
        }, 300);
    });
    
    // ==================== FLASH SALE ====================
    let flashCountdownInterval = null;
    
    function renderFlashSale(flashSaleData) {
        const section = document.getElementById('flashSaleSection');
        const container = document.getElementById('flashSaleProductsGrid');
        if (!flashSaleData || !flashSaleData.products || flashSaleData.products.length === 0) {
            if (section) section.style.display = 'none';
            return;
        }
        if (section) section.style.display = 'block';
        if (!container) return;
        if (flashSaleData.end_timestamp) {
            startFlashCountdown(flashSaleData.end_timestamp);
        }
        renderProducts('flashSaleProductsGrid', flashSaleData.products, null, true);
    }
    
    function startFlashCountdown(endTimestamp) {
        if (flashCountdownInterval) clearInterval(flashCountdownInterval);
        const updateCountdown = () => {
            const now = new Date().getTime();
            const distance = endTimestamp - now;
            if (distance < 0) {
                if (flashCountdownInterval) clearInterval(flashCountdownInterval);
                document.getElementById('countdown-hours').innerText = '00';
                document.getElementById('countdown-minutes').innerText = '00';
                document.getElementById('countdown-seconds').innerText = '00';
                return;
            }
            const hours = Math.floor(distance / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            const totalSeconds = Math.floor(distance / 1000);
            const secondsItem = document.querySelector('.countdown-item.seconds');
            if (totalSeconds < 60) {
                if (secondsItem) secondsItem.classList.add('pulse');
            } else {
                if (secondsItem) secondsItem.classList.remove('pulse');
            }
            document.getElementById('countdown-hours').innerText = String(hours).padStart(2, '0');
            document.getElementById('countdown-minutes').innerText = String(minutes).padStart(2, '0');
            document.getElementById('countdown-seconds').innerText = String(seconds).padStart(2, '0');
        };
        updateCountdown();
        flashCountdownInterval = setInterval(updateCountdown, 1000);
    }
    
    // ==================== LỌC DANH MỤC ====================
    function setupCategoryFilter(containerId, filterBarId) {
        const container = document.getElementById(containerId);
        const filterBar = document.getElementById(filterBarId);
        if (!container || !filterBar) return;
        const buttons = filterBar.querySelectorAll('.brand-filter-btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', function() {
                buttons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const category = this.dataset.category || '';
                const cards = container.querySelectorAll('.product-card');
                let anyVisible = false;
                cards.forEach(card => {
                    const cardCategory = card.dataset.category || '';
                    if (category === '' || cardCategory === category) {
                        card.style.display = '';
                        anyVisible = true;
                    } else {
                        card.style.display = 'none';
                    }
                });
                const noResult = container.querySelector('.no-result');
                if (!anyVisible) {
                    if (!noResult) {
                        const msg = document.createElement('div');
                        msg.className = 'no-result';
                        msg.style.cssText = 'grid-column: 1 / -1; text-align:center; padding:40px; color:#888;';
                        msg.innerText = 'Không có sản phẩm nào trong danh mục này.';
                        container.appendChild(msg);
                    }
                } else {
                    if (noResult) noResult.remove();
                }
            });
        });
    }
    
    // ==================== LOAD DỮ LIỆU TỪ API ====================
    async function loadFlashSale() {
        try {
            const data = await callAPI('get_flash_sale.php');
            if (data && data.success && data.data) {
                renderFlashSale(data.data);
            } else {
                document.getElementById('flashSaleSection').style.display = 'none';
            }
        } catch(e) {
            console.error('Lỗi load flash sale:', e);
            document.getElementById('flashSaleSection').style.display = 'none';
        }
    }
    
    async function loadWineProducts(brandSlug = '') {
        try {
            let url = 'products.php?product_type=1&limit=8';
            if (brandSlug) url += '&brand=' + encodeURIComponent(brandSlug);
            const data = await callAPI(url);
            const products = data?.products || [];
            renderProducts('featuredProducts', products, 'Rượu vang');
            document.querySelectorAll('#brandFilterBar .brand-filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.brand === brandSlug);
            });
            if (products.length > 0) {
                requestAnimationFrame(lockWineProductsHeight);
            } else {
                requestAnimationFrame(applyLockedWineProductsHeight);
            }
        } catch(e) {
            console.error('Lỗi load rượu vang:', e);
            renderProducts('featuredProducts', [], 'Rượu vang');
            requestAnimationFrame(applyLockedWineProductsHeight);
        }
    }
    
    async function loadOtherProducts() {
        try {
            const data = await callAPI('other_products.php');
            let products = data?.products || [];
            // Nhóm theo category_slug và lấy 4 sản phẩm mỗi danh mục
            products = groupAndLimitProducts(products, 'category_slug', 4);
            renderProducts('otherProductsGrid', products, 'Sản phẩm', false, 'category_slug');
            setupCategoryFilter('otherProductsGrid', 'otherBrandFilterBar');
        } catch(e) {
            console.error('Lỗi load sản phẩm khác:', e);
            renderProducts('otherProductsGrid', [], 'Sản phẩm');
        }
    }
    
    // ===== HÀM LOAD BIA NGOẠI =====
    async function loadBeerProducts() {
        try {
            const data = await callAPI('products.php?product_type=3&limit=30');
            let products = data?.products || [];
            // Nhóm theo country và lấy 4 sản phẩm mỗi danh mục
            products = groupAndLimitProducts(products, 'country', 4);
            renderProducts('beerProductsGrid', products, 'Bia', false, 'country');
            setupCategoryFilter('beerProductsGrid', 'beerFilterBar');
        } catch(e) {
            console.error('Lỗi load bia ngoại:', e);
            renderProducts('beerProductsGrid', [], 'Bia');
        }
    }
    
    async function loadLuxuryGiftBoxes() {
        try {
            const data = await callAPI('gift_products.php');
            const products = data?.products || [];
            renderProducts('luxuryGiftBoxesGrid', products, 'Hộp quà');
        } catch(e) {
            console.error('Lỗi load hộp quà sang trọng:', e);
            renderProducts('luxuryGiftBoxesGrid', [], 'Hộp quà');
        }
    }
    
    async function loadGiftProducts() {
        try {
            const data = await callAPI('gift_products.php');
            const products = data?.products || [];
            renderProducts('giftProductsGrid', products, 'Quà Tặng');
        } catch(e) {
            console.error('Lỗi load quà tặng rượu vang:', e);
            renderProducts('giftProductsGrid', [], 'Quà Tặng');
        }
    }
    
    // ==================== LOAD SIDEBAR BANNER ====================
    async function loadWineSidebarBanner() {
        const sidebarContainer = document.getElementById('wineSidebar');
        if (!sidebarContainer) return;
        try {
            const data = await callAPI('banners.php?position=wine_sidebar&active=1&limit=1');
            let banner = null;
            if (data && data.success && data.banners && data.banners.length > 0) {
                banner = data.banners[0];
            }
            if (!banner) {
                banner = {
                    image_url: 'assets/images/wine-sidebar-default.jpg',
                    title: 'Bộ sưu tập rượu vang Ý',
                    link: 'product_main.php?country=Rượu vang Ý'
                };
            }
            const html = `
                <a href="${escapeHtml(banner.link || '#')}" class="sidebar-banner-item" target="${banner.link ? '_blank' : '_self'}">
                    <div class="sidebar-banner-bg" style="background-image: url('${escapeHtml(banner.image_url)}');"></div>
                    <div class="sidebar-banner-overlay"></div>
                    <div class="sidebar-banner-content">
                        <div class="sidebar-banner-brand">${escapeHtml(banner.title || 'Khám phá ngay')}</div>
                        <div class="sidebar-banner-link">KHÁM PHÁ <i class="fas fa-arrow-right"></i></div>
                    </div>
                </a>
            `;
            sidebarContainer.innerHTML = html;
        } catch(e) {
            console.error('Lỗi tải sidebar banner:', e);
            sidebarContainer.innerHTML = `
                <a href="product_main.php?country=Rượu vang Ý" class="sidebar-banner-item">
                    <div class="sidebar-banner-bg" style="background-image: url('assets/images/wine-sidebar-default.jpg');"></div>
                    <div class="sidebar-banner-overlay"></div>
                    <div class="sidebar-banner-content">
                        <div class="sidebar-banner-brand">Bộ sưu tập rượu vang Ý</div>
                        <div class="sidebar-banner-link">KHÁM PHÁ <i class="fas fa-arrow-right"></i></div>
                    </div>
                </a>
            `;
        }
    }
    
    // ==================== LOAD BANNERS ====================
    async function loadHeroSlides() {
        try {
            const data = await callAPI('banners.php?position=hero_slider&active=1&limit=10');
            if (data && data.success && data.banners && data.banners.length) {
                return data.banners.sort((a,b) => (a.sort_order || 0) - (b.sort_order || 0));
            }
            return [];
        } catch(e) {
            console.error('Lỗi tải hero slides:', e);
            return [];
        }
    }
    
    function renderHeroSlides(slidesData) {
        const wrapper = document.getElementById('heroSlidesWrapper');
        const dotsContainer = document.getElementById('sliderDots');
        if (!wrapper) return;
        const fallbackSlides = [
            { image_url: 'assets/images/hero-fallback-1.jpg', title: 'Rượu Vang Nhập Khẩu Chính Hãng', link: 'product_main.php' },
            { image_url: 'assets/images/hero-fallback-2.jpg', title: 'Bộ Sưu Tập Rượu Vang Cao Cấp', link: 'product_main.php' }
        ];
        const slides = slidesData.length ? slidesData : fallbackSlides;
        let slidesHtml = '';
        slides.forEach((slide, index) => {
            const imageUrl = slide.image_url || `assets/images/hero-fallback-${(index % 2) + 1}.jpg`;
            let titleHtml = slide.title ? `<h1>${escapeHtml(slide.title)}</h1>` : '';
            let subtitleHtml = slide.subtitle ? `<p>${escapeHtml(slide.subtitle)}</p>` : '';
            let buttonHtml = '';
            if (slide.link && slide.link !== '') {
                buttonHtml = `<a href="${escapeHtml(slide.link)}" class="hero-btn">Khám phá ngay <i class="fas fa-arrow-right"></i></a>`;
            }
            slidesHtml += `
                <div class="hero-slide" style="background-image: url('${escapeHtml(imageUrl)}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
                    <div class="hero-content">
                        ${titleHtml}
                        ${subtitleHtml}
                        ${buttonHtml}
                    </div>
                </div>
            `;
        });
        wrapper.innerHTML = slidesHtml;
        dotsContainer.innerHTML = '';
        for (let i = 0; i < slides.length; i++) {
            const dot = document.createElement('button');
            dot.classList.add('dot');
            if (i === 0) dot.classList.add('active');
            dot.setAttribute('data-index', i);
            dot.addEventListener('click', () => goToSlide(i));
            dotsContainer.appendChild(dot);
        }
        attachSliderEvents(slides.length);
    }
    
    let currentSlideIndex = 0;
    let totalSlides = 0;
    let slideInterval = null;
    
    function goToSlide(index) {
        const wrapper = document.querySelector('.hero-slides-wrapper');
        if (!wrapper) return;
        if (index < 0) index = 0;
        if (index >= totalSlides) index = totalSlides - 1;
        currentSlideIndex = index;
        wrapper.style.transform = `translateX(-${index * 100}%)`;
        document.querySelectorAll('.dot').forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }
    
    function nextSlide() { goToSlide((currentSlideIndex + 1) % totalSlides); }
    function prevSlide() { goToSlide((currentSlideIndex - 1 + totalSlides) % totalSlides); }
    
    function startAutoPlay() {
        if (slideInterval) clearInterval(slideInterval);
        if (totalSlides > 1) {
            slideInterval = setInterval(nextSlide, 5000);
        }
    }
    
    function attachSliderEvents(slidesCount) {
        totalSlides = slidesCount;
        const prevBtn = document.getElementById('prevSlide');
        const nextBtn = document.getElementById('nextSlide');
        if (prevBtn) prevBtn.onclick = () => { prevSlide(); resetAutoPlay(); };
        if (nextBtn) nextBtn.onclick = () => { nextSlide(); resetAutoPlay(); };
        const heroContainer = document.querySelector('.hero-slider-container');
        if (heroContainer) {
            heroContainer.addEventListener('mouseenter', () => { if (slideInterval) clearInterval(slideInterval); });
            heroContainer.addEventListener('mouseleave', startAutoPlay);
        }
        startAutoPlay();
    }
    
    function resetAutoPlay() {
        if (slideInterval) {
            clearInterval(slideInterval);
            startAutoPlay();
        }
    }
    
    async function initHeroSlider() {
        const slides = await loadHeroSlides();
        renderHeroSlides(slides);
    }
    
    async function loadCategoryBanners() {
        const container = document.getElementById('dynamicBannersGrid');
        if (!container) return;
        try {
            const data = await callAPI('banners.php?position=home_sidebar&active=1&limit=2');
            if (!data || !data.success || !data.banners || data.banners.length === 0) {
                container.innerHTML = '<div class="col-12 text-center py-5 text-muted">Chưa có banner quảng cáo</div>';
                return;
            }
            const banners = data.banners.slice(0, 2);
            let html = '';
            banners.forEach(banner => {
                const linkUrl = banner.link && banner.link !== '' ? banner.link : '#';
                const imageUrl = banner.image_url || '';
                const title = banner.title || 'Quảng cáo';
                html += `
                    <a href="${escapeHtml(linkUrl)}" class="showcase-item" target="${banner.link ? '_blank' : '_self'}">
                        <div class="showcase-bg" style="background-image: url('${escapeHtml(imageUrl)}');"></div>
                        <div class="showcase-overlay"></div>
                        <div class="showcase-content">
                            <div class="showcase-brand">${escapeHtml(title)}</div>
                            <div class="showcase-link">KHÁM PHÁ <i class="fas fa-arrow-right"></i></div>
                        </div>
                    </a>
                `;
            });
            container.innerHTML = html;
        } catch(e) {
            console.error('Lỗi tải banner:', e);
            container.innerHTML = '<div class="col-12 text-center py-5 text-danger">Không thể tải quảng cáo</div>';
        }
    }
    
    async function loadNews() {
        const newsGrid = document.getElementById('newsGrid');
        if (!newsGrid) return;
        try {
            const data = await callAPI('posts.php?limit=4');
            if (!data || !data.posts || data.posts.length === 0) {
                newsGrid.innerHTML = '<div style="text-align:center; padding:40px;">Chưa có bài viết nào</div>';
                return;
            }
            let html = '';
            data.posts.forEach(post => {
                const imageUrl = post.image && post.image !== '' ? post.image : 'assets/images/default-post.jpg';
                const shortDesc = post.short_description || (post.content ? post.content.substring(0, 120) + '...' : '');
                html += `
                    <a href="kienthuc1.php?slug=${post.slug}" class="news-card">
                        <img src="${imageUrl}" alt="${escapeHtml(post.title)}" class="news-img" onerror="this.src='assets/images/default-post.jpg'">
                        <div class="news-content">
                            <h3 class="news-title">${escapeHtml(post.title)}</h3>
                            <p class="news-desc">${escapeHtml(shortDesc)}</p>
                            <div class="news-more">Đọc thêm <i class="fas fa-arrow-right"></i></div>
                        </div>
                    </a>
                `;
            });
            newsGrid.innerHTML = html;
        } catch(e) {
            console.error('Lỗi tải tin tức:', e);
            newsGrid.innerHTML = '<div style="text-align:center; padding:40px;">Không thể tải tin tức</div>';
        }
    }
    
    // ==================== XỬ LÝ CLICK VÀO CARD ====================
    function handleProductCardClick(e) {
        const card = e.target.closest('.product-card');
        if (!card) return;
        const isInteractive = e.target.closest('.btn-buy-now, .product-image-link, .product-info h3 a');
        if (isInteractive) return;
        let href = card.getAttribute('data-href');
        if (!href) {
            const link = card.querySelector('.product-image-link, .product-info h3 a');
            if (link) href = link.getAttribute('href');
        }
        if (href && href !== '#') {
            e.preventDefault();
            window.location.href = href;
        }
    }
    
    // ==================== MOBILE MENU ====================
    (function fixMenuStructure() {
        function applyFix() {
            document.querySelectorAll('.nav-item').forEach(item => {
                const dropdown = item.querySelector(':scope > .dropdown-menu');
                if (dropdown) {
                    if (!item.classList.contains('has-dropdown')) {
                        item.classList.add('has-dropdown');
                    }
                    if (!item.querySelector(':scope > .mobile-arrow')) {
                        const arrowSpan = document.createElement('span');
                        arrowSpan.className = 'mobile-arrow';
                        arrowSpan.innerHTML = '<i class="fas fa-chevron-down"></i>';
                        const link = item.querySelector(':scope > .nav-link');
                        if (link) {
                            link.insertAdjacentElement('afterend', arrowSpan);
                        } else {
                            item.appendChild(arrowSpan);
                        }
                    }
                }
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', applyFix);
        } else {
            applyFix();
        }
    })();

    // ==================== GIỮ SUBMENU KHI DI CHUỘT (fix flicker) ====================
    (function desktopDropdownHover() {
        function isDesktop() { return window.innerWidth > 992; }
        function init() {
            document.querySelectorAll('.has-dropdown').forEach(item => {
                if (item.dataset.hoverBound) return;
                const dropdown = item.querySelector(':scope > .dropdown-menu');
                if (!dropdown) return;
                item.dataset.hoverBound = '1';
                let hideTimer = null;

                function showDropdown() {
                    if (!isDesktop()) return;
                    if (hideTimer) clearTimeout(hideTimer);
                    item.classList.add('dropdown-active');
                }
                function scheduleHide() {
                    if (!isDesktop()) return;
                    if (hideTimer) clearTimeout(hideTimer);
                    hideTimer = setTimeout(() => {
                        item.classList.remove('dropdown-active');
                    }, 30);
                }

                item.addEventListener('mouseenter', showDropdown);
                item.addEventListener('mouseleave', scheduleHide);
                dropdown.addEventListener('mouseenter', showDropdown);
                dropdown.addEventListener('mouseleave', scheduleHide);
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();

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
    
    // ==================== KHỞI TẠO TRANG ====================
    document.addEventListener('DOMContentLoaded', async function() {
        await initHeroSlider();
        await loadFlashSale();
        await loadWineProducts();
        await loadWineSidebarBanner();
        await loadOtherProducts();
        await loadBeerProducts(); // Gọi load bia
        await loadLuxuryGiftBoxes();
        await loadGiftProducts();
        await loadCategoryBanners();
        await loadNews();
        await updateCartCount();
        await updateWishlistCount();
        document.body.addEventListener('click', handleProductCardClick);
        
        // Xử lý nút lọc thương hiệu cho rượu vang (chuyển hướng)
document.querySelectorAll('#brandFilterBar .brand-filter-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const brand = this.dataset.brand || '';
        let url = 'product_main.php';
        if (brand) {
            // Thêm tham số brand và product_type=1 để lọc rượu vang theo brand
            url += '?product_type=1&brand=' + encodeURIComponent(brand);
        } else {
            url += '?product_type=1';
        }
        window.location.href = url;
    });
});
    });
    
    // ==================== KIỂM TRA ĐĂNG NHẬP (HIỂN THỊ MENU) ====================
    (async function() {
        async function checkLoginStatus() {
            try {
                const response = await fetch(API_BASE + 'auth.php', {
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
            await fetch(API_BASE + 'auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' }),
                credentials: 'include'
            });
            window.location.reload();
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
        const currentUser = await checkLoginStatus();
        updateUserMenu(currentUser);
    })();

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

    // ===== SLIDER THƯƠNG HIỆU (auto chạy 3s) =====
    (function initBrandSlider() {
        const track = document.querySelector('.slider-track');
        const prevBtn = document.querySelector('.slider-prev');
        const nextBtn = document.querySelector('.slider-next');
        if (!track || !prevBtn || !nextBtn) return;

        let autoSlideInterval;
        const autoSlideDelay = 3000;
        let isHovering = false;
        let isTransitioning = false;

        function getStepWidth() {
            const item = track.querySelector('.brand-item');
            if (!item) return 245;
            const itemWidth = item.offsetWidth;
            const style = window.getComputedStyle(track);
            const gap = parseInt(style.gap) || 25;
            return itemWidth + gap;
        }

        function scrollToSlide(targetScroll) {
            if (isTransitioning) return;
            isTransitioning = true;
            track.scrollTo({ left: targetScroll, behavior: 'smooth' });
            setTimeout(() => { isTransitioning = false; }, 500);
        }

        function nextSlide() {
            if (isTransitioning) return;
            const step = getStepWidth();
            const maxScroll = track.scrollWidth - track.clientWidth;
            const currentScroll = track.scrollLeft;
            if (currentScroll >= maxScroll - 5) {
                track.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                scrollToSlide(Math.min(currentScroll + step, maxScroll));
            }
        }

        function prevSlide() {
            if (isTransitioning) return;
            const step = getStepWidth();
            const currentScroll = track.scrollLeft;
            if (currentScroll <= 5) {
                track.scrollTo({ left: track.scrollWidth - track.clientWidth, behavior: 'smooth' });
            } else {
                scrollToSlide(Math.max(currentScroll - step, 0));
            }
        }

        function startAutoSlide() {
            if (autoSlideInterval) clearInterval(autoSlideInterval);
            autoSlideInterval = setInterval(() => {
                if (!isHovering && !isTransitioning) nextSlide();
            }, autoSlideDelay);
        }

        function stopAutoSlide() {
            if (autoSlideInterval) {
                clearInterval(autoSlideInterval);
                autoSlideInterval = null;
            }
        }

        function resetAutoSlide() {
            stopAutoSlide();
            startAutoSlide();
        }

        track.addEventListener('mouseenter', () => { isHovering = true; });
        track.addEventListener('mouseleave', () => { isHovering = false; });

        let touchStartX = 0, touchEndX = 0, touchStartTime = 0;
        track.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            touchStartTime = Date.now();
            isHovering = true;
        }, { passive: true });
        track.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const diff = touchStartX - touchEndX;
            const timeDiff = Date.now() - touchStartTime;
            if (Math.abs(diff) > 50 && timeDiff < 300) {
                if (diff > 0) nextSlide(); else prevSlide();
                resetAutoSlide();
            }
            isHovering = false;
        }, { passive: true });

        prevBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            prevSlide();
            resetAutoSlide();
        });
        nextBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            nextSlide();
            resetAutoSlide();
        });

        prevBtn.style.pointerEvents = 'auto';
        nextBtn.style.pointerEvents = 'auto';
        prevBtn.style.zIndex = '30';
        nextBtn.style.zIndex = '30';

        setTimeout(startAutoSlide, 1500);

        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => { resetAutoSlide(); }, 300);
        });
    })();
</script>
</body>
</html>