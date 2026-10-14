<?php
require_once 'includes/menu.php'; 
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Ruouchat | Rượu vang cao cấp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <style>
        /* ===== RESET & GLOBAL (từ index.php) ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary-red: #990D23; --dark-red: #670011; --deep-red: #55000D; --gold: #FFBD27; --light-bg: #FBF8EE; --cream: #F2EFE2; --border-color: #E8E8E8; --text-dark: #212529; --text-light: #6C757D; }
        body { font-family: 'Inter', sans-serif; color: var(--text-dark); background: white; line-height: 1.6; overflow-x: hidden; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; width: 100%; }
        
        /* ===== HOTLINE FLOATING BUTTON (giống index.php) ===== */
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
        
        /* ===== HEADER 2 TẦNG VỚI HIỆU ỨNG ẨN PHẦN TRÊN (GIỐNG product_main) ===== */
        .header {
            background: #ffffff; /* ĐỔI: màu trắng thay vì light-bg */
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08); /* Shadow nhẹ hơn */
            padding: 0; /* Xóa padding để sát đầu trang */
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        /* Khi cuộn xuống: ẩn phần header-top, chỉ hiện menu */
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
            margin-top: 0; /* Xóa khoảng cách */
            border-top: none;
        }
        
        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            flex-wrap: wrap;
            transition: all 0.2s ease;
            padding: 10px 0; /* Padding cho header-top */
        }
        .logo { flex-shrink: 0; }
        .logo img { height: 42px; width: auto; display: block; max-width: 100%; }
        .search-bar { flex: 1; max-width: 700px; min-width: 200px; position: relative; }
        .search-form {
            display: flex;
            width: 100%;
            background: white;
            border-radius: 60px;
            border: 2px solid #000000; /* Viền đen */
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
            background: var(--dark-red); /* Màu đỏ giống footer (.main-footer) */
            margin-top: 0; /* Xóa khoảng cách */
            border-top: none;
            padding-top: 4px;
            padding-bottom: 4px;
            position: relative;
            width: 100vw; /* Kéo full màn hình */
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
        
        /* ===== CART PAGE STYLES (giữ nguyên) ===== */
        .cart-main { 
            padding: 48px 0 64px; 
            background: #fffaf5; 
        }
        .section-title { 
            font-family: 'Merriweather', serif; 
            font-size: 32px; 
            font-weight: 800; 
            color: var(--deep-red); 
            text-align: center; 
            margin-bottom: 40px; 
            position: relative; 
            padding-bottom: 12px; 
        }
        .section-title::after { 
            content: ''; 
            position: absolute; 
            bottom: 0; 
            left: 50%; 
            transform: translateX(-50%); 
            height: 3px; 
            background: var(--gold); 
            border-radius: 2px; 
        }
        .cart-layout { 
            display: grid; 
            grid-template-columns: 1fr 380px; 
            gap: 32px; 
            align-items: start; 
        }
        .cart-items-panel { 
            background: white; 
            border-radius: 24px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
            border: 1px solid var(--border-color); 
            overflow: hidden; 
        }
        .cart-header-row { 
            display: grid; 
            grid-template-columns: 3fr 1fr 1.2fr 1.2fr 0.5fr; 
            background: #F9F3EA; 
            padding: 18px 20px; 
            font-weight: 700; 
            font-size: 0.9rem; 
            text-transform: uppercase; 
            color: var(--dark-red); 
            border-bottom: 1px solid var(--border-color); 
        }
        .cart-item { 
            display: grid; 
            grid-template-columns: 3fr 1fr 1.2fr 1.2fr 0.5fr; 
            align-items: center; 
            padding: 24px 20px; 
            border-bottom: 1px solid #eee5dc; 
            transition: background 0.2s; 
        }
        .product-info { 
            display: flex; 
            align-items: center; 
            gap: 18px; 
        }
        .product-img { 
            width: 70px; 
            height: 70px; 
            object-fit: cover; 
            border-radius: 14px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.05); 
            border: 1px solid #f0e2d4; 
        }
        .product-name { 
            font-weight: 700; 
            font-size: 1rem; 
            color: var(--text-dark); 
            line-height: 1.4; 
        }
        .product-price { 
            font-weight: 600; 
            color: var(--primary-red); 
            font-size: 1rem; 
        }
        .quantity-control { 
            display: inline-flex; 
            align-items: center; 
            background: #f5efe8; 
            border-radius: 48px; 
            border: 1px solid #e2d5ca; 
            overflow: hidden; 
        }
        .qty-btn { 
            background: transparent; 
            border: none; 
            width: 36px; 
            height: 40px; 
            font-size: 1.3rem; 
            font-weight: 600; 
            cursor: pointer; 
            color: var(--dark-red); 
            transition: all 0.2s; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
        }
        .qty-btn:hover { 
            background: rgba(153, 13, 35, 0.1); 
            color: var(--primary-red); 
        }
        .qty-value { 
            width: 44px; 
            text-align: center; 
            font-weight: 600; 
            font-size: 1rem; 
            background: white; 
            border: none; 
            padding: 8px 0; 
            pointer-events: none; 
        }
        .item-subtotal { 
            font-weight: 700; 
            color: #1e1e2a; 
            font-size: 1.05rem; 
        }
        .remove-item-btn { 
            background: none; 
            border: none; 
            font-size: 1.25rem; 
            color: #b1a088; 
            cursor: pointer; 
            transition: all 0.2s; 
            width: 36px; 
            height: 36px; 
            border-radius: 50%; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
        }
        .remove-item-btn:hover { 
            background: #fce4e4; 
            color: var(--primary-red); 
            transform: scale(1.05); 
        }
        .cart-summary { 
            background: white; 
            border-radius: 24px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
            border: 1px solid var(--border-color); 
            padding: 28px 24px; 
            position: sticky; 
            top: 100px; 
        }
        .summary-title { 
            font-size: 1.4rem; 
            font-weight: 800; 
            font-family: 'Merriweather', serif; 
            color: var(--deep-red); 
            display: inline-block; 
            margin-bottom: 24px; 
            padding-bottom: 6px; 
        }
        .summary-row { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 20px; 
            font-size: 1rem; 
        }
        .summary-row.total-row { 
            margin-top: 20px; 
            padding-top: 20px; 
            border-top: 2px dashed #e2cfbc; 
            font-size: 1.3rem; 
            font-weight: 800; 
            color: var(--primary-red); 
        }
        .btn-checkout { 
            display: block; 
            width: 100%; 
            background: var(--primary-red); 
            color: white; 
            text-align: center; 
            padding: 16px 0; 
            border-radius: 60px; 
            font-weight: 700; 
            font-size: 1.1rem; 
            text-decoration: none; 
            transition: all 0.25s; 
            margin-top: 24px; 
            border: none; 
            cursor: pointer; 
            box-shadow: 0 4px 8px rgba(153,13,35,0.2); 
        }
        .btn-checkout:hover { 
            background: var(--dark-red); 
            transform: translateY(-2px); 
            box-shadow: 0 10px 20px rgba(153,13,35,0.25); 
        }
        .btn-continue { 
            display: inline-block; 
            background: transparent; 
            border: 1.5px solid var(--primary-red); 
            color: var(--primary-red); 
            padding: 12px 24px; 
            border-radius: 40px; 
            font-weight: 600; 
            text-decoration: none; 
            transition: all 0.2s; 
            margin-top: 16px; 
            text-align: center; 
        }
        .btn-continue:hover { 
            background: rgba(153,13,35,0.05); 
            border-color: var(--dark-red); 
        }
        .empty-cart-state { 
            text-align: center; 
            padding: 60px 20px; 
            background: white; 
            border-radius: 32px; 
            border: 1px solid var(--border-color); 
        }
        .empty-cart-state i { 
            font-size: 70px; 
            color: #e0cbbc; 
            margin-bottom: 20px; 
        }
        .empty-message { 
            font-size: 24px; 
            font-weight: 600; 
            margin: 20px 0 16px; 
        }
        
        /* ===== FOOTER ===== */
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
        .footer-bottom { 
            text-align: center; 
            padding: 20px 0; 
            border-top: 1px solid rgba(255,255,255,0.1); 
            font-size: 13px; 
            opacity: 0.8; 
        }

        /* ===== MODAL ===== */
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

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .main-nav { overflow-x: auto; white-space: nowrap; }
            .nav-list { flex-wrap: nowrap; justify-content: flex-start; overflow-x: auto; }
            .footer-container { grid-template-columns: repeat(2, 1fr); gap: 30px; }
            .header-top { gap: 10px; }
            .header-bottom { margin-top: 6px !important; padding-top: 3px !important; }
            .cart-layout { grid-template-columns: 1fr; gap: 24px; }
            .cart-summary { position: static; margin-top: 20px; }
            .cart-header-row { display: none; }
            .cart-item { grid-template-columns: 1fr; gap: 12px; padding: 20px; }
            .product-info { justify-content: space-between; }
            .quantity-wrapper { justify-self: start; }
            .remove-item-btn { justify-self: end; }
        }
        @media (max-width: 768px) {
            .cart-main { margin-top: 100px; }
        }
        @media (max-width: 580px) {
            .footer-container { 
                grid-template-columns: 1fr; 
                gap: 30px;
                text-align: left !important;
            }
            .footer-section {
                text-align: left !important;
                padding-left: 0;
            }
            .footer-section p,
            .footer-section a {
                justify-content: flex-start !important;
                text-align: left !important;
                margin-left: 0;
            }
            .social-links, .payment-methods { 
                justify-content: flex-start !important;
                margin-left: 0;
            }
            .newsletter { 
                justify-content: flex-start;
                max-width: 100%;
                margin-left: 0;
            }
            .cart-main { margin-top: 120px; }
            .section-title { font-size: 24px; }
        }
        @media (max-width: 480px) {
            .cart-main { margin-top: 140px; }
            .product-img { width: 50px; height: 50px; }
            .product-name { font-size: 14px; }
            .qty-btn { width: 32px; height: 36px; font-size: 1.1rem; }
            .qty-value { width: 36px; }
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
                    <span class="cart-count" id="cartCountBadge">0</span>
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

<main class="cart-main">
    <div class="container">
        <h2 class="section-title">GIỎ HÀNG CỦA BẠN</h2>
        <div id="dynamicCartContainer"></div>
    </div>
</main>

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

<script>
    // ===== HIỆU ỨNG PULSE CHO HOTLINE (giống index.php) =====
    document.addEventListener('DOMContentLoaded', function() {
        const hotlineItems = document.querySelectorAll('.hotline-item');
        hotlineItems.forEach((item, index) => {
            item.classList.remove('pulse');
            setTimeout(() => {
                item.classList.add('pulse');
            }, index * 500);
        });
    });

    // ==================== HEADER HIDE/SHOW (GIỐNG product_main) ====================
    let lastScroll = 0;
    let ticking = false;
    let scrollDirection = 'down';
    const headerElem = document.querySelector('.header');
    const scrollThreshold = 100;

    function handleHeaderScroll() {
        let currentScroll = window.pageYOffset;
        
        // Xác định hướng cuộn
        if (currentScroll > lastScroll + 2) {
            scrollDirection = 'down';
        } else if (currentScroll < lastScroll - 2) {
            scrollDirection = 'up';
        }
        
        // Cuộn xuống quá ngưỡng -> thêm class hide (ẩn header-top)
        if (currentScroll > scrollThreshold && scrollDirection === 'down') {
            if (!headerElem.classList.contains('hide')) {
                headerElem.classList.add('hide');
                headerElem.classList.remove('show');
                updateCartMarginTop();
            }
        } 
        // Cuộn lên hoặc ở đầu trang -> thêm class show (hiện header-top)
        else if (scrollDirection === 'up' || currentScroll <= 20) {
            if (!headerElem.classList.contains('show')) {
                headerElem.classList.add('show');
                headerElem.classList.remove('hide');
                setTimeout(updateCartMarginTop, 50);
            }
        }
        
        lastScroll = currentScroll;
        ticking = false;
    }

    function requestScrollUpdate() {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(handleHeaderScroll);
        }
    }
    
    // Điều chỉnh margin cho cart main
    function updateCartMarginTop() {
        const cartMain = document.querySelector('.cart-main');
        if (cartMain && headerElem) {
            let headerHeight = headerElem.offsetHeight;
            // Nếu header đang ẩn phần top, lấy chiều cao thực tế của bottom
            if (headerElem.classList.contains('hide')) {
                const headerBottom = headerElem.querySelector('.header-bottom');
                headerHeight = headerBottom ? headerBottom.offsetHeight + 10 : 55;
            }
            cartMain.style.marginTop = (headerHeight + 20) + 'px';
        }
    }
    
    // Debounced resize handler
    let resizeTimeout;
    function handleResize() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            updateCartMarginTop();
        }, 100);
    }
    
    // Attach events
    window.addEventListener('scroll', requestScrollUpdate, { passive: true });
    window.addEventListener('resize', handleResize);
    
    // Khởi tạo trạng thái ban đầu
    function initializeHeaderState() {
        if (window.scrollY <= 20) {
            headerElem.classList.add('show');
            headerElem.classList.remove('hide');
        } else if (window.scrollY > scrollThreshold) {
            headerElem.classList.add('hide');
            headerElem.classList.remove('show');
        } else {
            headerElem.classList.add('show');
            headerElem.classList.remove('hide');
        }
        updateCartMarginTop();
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeHeaderState);
    } else {
        initializeHeaderState();
    }
    
    window.addEventListener('load', updateCartMarginTop);
    
    // ResizeObserver để theo dõi thay đổi kích thước header
    const observer = new ResizeObserver(() => {
        updateCartMarginTop();
    });
    if (headerElem) observer.observe(headerElem);

    // ==================== MOBILE MENU SCRIPT ====================
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
                navList.classList.remove('show-mobile');
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
                navList.classList.remove('show-mobile');
                closeAllDropdowns();
                const icon = mobileToggle?.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    })();

    // ---- SEARCH SUGGESTIONS ----
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
    if (searchInput) {
        searchInput.addEventListener('focus', showSuggestions);
        searchInput.addEventListener('input', showSuggestions);
        document.addEventListener('click', function(e) { if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) { suggestionsBox.classList.remove('active'); } });
    }

    // ==================== CART FUNCTIONS ====================
    async function callAPI(url, options = {}) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: { 'Content-Type': 'application/json', ...options.headers },
                credentials: 'include'
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Lỗi');
            return data;
        } catch (error) {
            console.error(error);
            return null;
        }
    }

    async function getCart() {
        const cart = await callAPI('api/cart.php');
        return cart || {};
    }

    async function updateCartItem(productId, quantity) {
        return await callAPI('api/cart.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'update', product_id: productId, quantity: quantity })
        });
    }

    async function removeCartItem(productId) {
        return await callAPI('api/cart.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'remove', product_id: productId })
        });
    }

    async function updateCartCountBadge() {
        const cart = await getCart();
        const totalQty = Object.values(cart).reduce((sum, item) => sum + (item.quantity || 0), 0);
        const badge = document.getElementById('cartCountBadge');
        if (badge) badge.innerText = totalQty;
    }

    // Render giỏ hàng
    async function renderCartUI() {
        const cart = await getCart();
        const cartItemsArray = Object.values(cart);
        const container = document.getElementById('dynamicCartContainer');
        if (!container) return;

        if (cartItemsArray.length === 0) {
            container.innerHTML = `
                <div class="empty-cart-state">
                    <i class="fas fa-shopping-cart"></i>
                    <div class="empty-message">Giỏ hàng trống</div>
                    <p style="color: #7a6a5c; margin-bottom: 28px;">Hãy thêm những sản phẩm tuyệt hảo từ bộ sưu tập của chúng tôi</p>
                    <a href="product_main.php" class="btn-continue" style="background: var(--primary-red); color: white; border: none;">🛍️ Tiếp tục mua hàng</a>
                </div>
            `;
            updateCartCountBadge();
            return;
        }

        let itemsHtml = `
            <div class="cart-layout">
                <div class="cart-items-panel">
                    <div class="cart-header-row">
                        <span>SẢN PHẨM</span>
                        <span>GIÁ</span>
                        <span>SỐ LƯỢNG</span>
                        <span>TẠM TÍNH</span>
                        <span></span>
                    </div>
                    <div id="cartItemsList">
        `;
        let subtotalAmount = 0;

        for (const [id, item] of Object.entries(cart)) {
            const itemTotal = item.price * item.quantity;
            subtotalAmount += itemTotal;
            const formattedPrice = new Intl.NumberFormat('vi-VN').format(item.price);
            const formattedSubtotal = new Intl.NumberFormat('vi-VN').format(itemTotal);
            const safeName = escapeHtml(item.name);
            const imgUrl = item.image && item.image.trim() !== '' ? item.image : 'https://placehold.co/80x80?text=Wine';
            itemsHtml += `
                <div class="cart-item" data-id="${id}">
                    <div class="product-info">
                        <img class="product-img" src="${imgUrl}" alt="${safeName}" onerror="this.src='https://placehold.co/80x80?text=Vang'">
                        <div class="product-name">${safeName}</div>
                    </div>
                    <div class="product-price">${formattedPrice} ₫</div>
                    <div class="quantity-wrapper">
                        <div class="quantity-control">
                            <button class="qty-btn minus-btn" data-id="${id}" aria-label="Giảm số lượng">−</button>
                            <span class="qty-value" id="qty-val-${id}">${item.quantity}</span>
                            <button class="qty-btn plus-btn" data-id="${id}" aria-label="Tăng số lượng">+</button>
                        </div>
                    </div>
                    <div class="item-subtotal">${formattedSubtotal} ₫</div>
                    <div>
                        <button class="remove-item-btn" data-id="${id}" aria-label="Xóa sản phẩm"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </div>
            `;
        }

        itemsHtml += `
                    </div>
                </div>
                <div class="cart-summary">
                    <div class="summary-title">TỔNG CỘNG GIỎ HÀNG</div>
                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <span>${new Intl.NumberFormat('vi-VN').format(subtotalAmount)} ₫</span>
                    </div>
                    <div class="summary-row total-row">
                        <span>Tổng cộng:</span>
                        <span>${new Intl.NumberFormat('vi-VN').format(subtotalAmount)} ₫</span>
                    </div>
                    <a href="checkout.html" class="btn-checkout" id="proceedCheckoutBtn">TIẾN HÀNH THANH TOÁN <i class="fas fa-arrow-right"></i></a>
                    <a href="product_main.php" class="btn-continue" style="display: block; text-align: center;">← Tiếp tục mua hàng</a>
                </div>
            </div>
        `;

        container.innerHTML = itemsHtml;

        // Gắn sự kiện
        document.querySelectorAll('.minus-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const currentQtySpan = document.getElementById(`qty-val-${id}`);
                let currentQty = currentQtySpan ? parseInt(currentQtySpan.innerText) : 1;
                if (currentQty <= 1) {
                    await removeCartItem(id);
                } else {
                    await updateCartItem(id, currentQty - 1);
                }
                await renderCartUI();
            });
        });

        document.querySelectorAll('.plus-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const currentQtySpan = document.getElementById(`qty-val-${id}`);
                let currentQty = currentQtySpan ? parseInt(currentQtySpan.innerText) : 1;
                await updateCartItem(id, currentQty + 1);
                await renderCartUI();
            });
        });

        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                await removeCartItem(id);
                await renderCartUI();
            });
        });

        updateCartCountBadge();
    }

    // User menu
    (function() {
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
            window.location.reload();
        }
        async function updateUserMenu() {
            const userMenu = document.getElementById('userMenu');
            if (!userMenu) return;
            const user = await checkLoginStatus();
            if (user) {
                userMenu.innerHTML = `<li><a href="account.php"><i class="fas fa-user-circle"></i> Tài khoản</a></li><li><a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>`;
                const logoutBtn = document.getElementById('logoutBtn');
                if (logoutBtn) logoutBtn.addEventListener('click', (e) => { e.preventDefault(); performLogout(); });
            } else {
                userMenu.innerHTML = `<li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a></li><li><a href="register.php"><i class="fas fa-user-plus"></i> Đăng ký</a></li>`;
            }
        }
        updateUserMenu();
    })();

    document.addEventListener('DOMContentLoaded', () => {
        renderCartUI();
    });
</script>
</body>
</html>