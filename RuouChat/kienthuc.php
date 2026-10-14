<?php
require_once 'includes/menu.php';
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
    <style>
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

        /* ===== STYLE CHO TRANG KIẾN THỨC ===== */
        .knowledge-banner {
            background-color: var(--dark-red);
            min-height: 350px;
            display: flex;
            align-items: center;
            text-align: center;
            color: white;
            padding: 50px 0;
            transition: margin-top 0.2s ease;
        }
        .knowledge-banner h1 {
            font-size: 45px;
            font-weight: 500;
            margin-bottom: 0px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--gold);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .knowledge-banner .subtitle {
            font-size: 22px;
            font-weight: 500;
            margin-bottom: 20px;
            color: white;
        }
        .knowledge-banner .description {
            max-width: 900px;
            margin: 0 auto;
            font-size: 16px;
            line-height: 1.8;
            opacity: 0.95;
            color: rgba(255,255,255,0.9);
        }

        .filter-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 50px;
            margin: 40px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .filter-btn {
            padding: 8px 24px;
            border-radius: 40px;
            background: var(--light-bg);
            color: var(--text-dark);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid var(--border-color);
        }
        .filter-btn.active, .filter-btn:hover {
            background: var(--primary-red);
            color: white;
            border-color: var(--primary-red);
        }
        .featured-posts {
            padding: 60px 0;
            background-color: #f9f9f9;
        }

        /* ===== POSTS GRID 4 CỘT - ĐÃ SỬA ===== */
        .posts-grid-3col {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-top: 20px;
        }

        .post-card-item {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid var(--border-color);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            text-decoration: none !important;
            color: inherit !important;
            cursor: pointer;
        }

        .post-card-item:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(139,0,0,0.1);
            border-color: var(--primary-red);
        }

        .post-card-item .post-img-wrap {
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: #f5f5f5;
        }

        .post-card-item .post-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .post-card-item:hover .post-img-wrap img {
            transform: scale(1.03);
        }

        .post-card-item .post-body {
            padding: 16px 18px 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .post-card-item .post-category {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary-red);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: inline-block;
        }

        .post-card-item .post-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color 0.2s;
        }

        .post-card-item:hover .post-title {
            color: var(--primary-red);
        }

        .post-card-item .post-excerpt {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 14px;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .post-card-item .post-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
            font-size: 13px;
            color: var(--text-light);
        }

        .post-card-item .post-meta .author {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .post-card-item .post-meta .author i {
            color: var(--primary-red);
            font-size: 13px;
        }

        .post-card-item .post-meta .date {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .post-card-item .post-meta .date i {
            color: var(--text-light);
            font-size: 12px;
        }

        /* ===== RESPONSIVE CHO POSTS GRID - ĐÃ SỬA ===== */
        @media (max-width: 1200px) {
            .posts-grid-3col {
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 992px) {
            .posts-grid-3col {
                grid-template-columns: repeat(2, 1fr);
                gap: 18px;
            }
            .knowledge-banner {
                height: auto;
                min-height: 280px;
                padding: 50px 20px;
            }
            .knowledge-banner h1 {
                font-size: 32px;
            }
            .knowledge-banner .subtitle {
                font-size: 18px;
            }
            .filter-bar {
                border-radius: 30px;
                padding: 12px 16px;
                gap: 10px;
                justify-content: center;
                flex-wrap: wrap;
                margin: 30px 0;
            }
            .filter-btn {
                padding: 6px 18px;
                font-size: 13px;
            }
        }

        @media (max-width: 576px) {
            .posts-grid-3col {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .post-card-item .post-img-wrap {
                height: 180px;
            }
            
            .post-card-item .post-title {
                font-size: 16px;
            }
            
            .post-card-item .post-body {
                padding: 14px 16px 16px;
            }
            .knowledge-banner h1 {
                font-size: 26px;
            }
            .knowledge-banner .subtitle {
                font-size: 16px;
            }
            .knowledge-banner .description {
                font-size: 14px;
            }
            .filter-btn {
                padding: 5px 14px;
                font-size: 12px;
            }
        }

        .load-more-wrapper {
            text-align: center;
            margin-top: 40px;
        }
        .load-more-btn {
            background: white;
            border: 2px solid var(--primary-red);
            color: var(--primary-red);
            padding: 12px 40px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .load-more-btn:hover {
            background: var(--primary-red);
            color: white;
        }

        /* ===== PROMO ACTIONS - RESPONSIVE ===== */
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

        /* ===== FLOATING PROMO ===== */
        .floating-promo { 
            position: fixed; 
            bottom: 0; 
            left: 0; 
            right: 0; 
            background: white; 
            box-shadow: 0 -6px 25px rgba(0,0,0,0.18);
            padding: 12px 16px;
            display: flex; 
            justify-content: center; 
            gap: 12px;
            z-index: 1000; 
            transform: translateY(100%); 
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-top: 4px solid #8B1E1E;
            flex-wrap: wrap;
        }
        .floating-promo.show { transform: translateY(0); }
        
        /* Ẩn Floating Promo trên mobile (<= 768px) */
        @media (max-width: 768px) {
            .floating-promo {
                display: none !important;
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
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

        /* ===== RESPONSIVE CHUNG ===== */
        @media (max-width: 992px) {
            .main-nav { overflow-x: auto; white-space: nowrap; }
            .nav-list { flex-wrap: nowrap; justify-content: flex-start; overflow-x: auto; }
            .footer-container { grid-template-columns: repeat(2, 1fr); gap: 30px; }
            .header-top { gap: 10px; }
            .header-bottom { margin-top: 6px !important; padding-top: 3px !important; }
        }
        @media (max-width: 768px) {
            .promo-actions { 
                gap: 12px; 
                margin: 30px 0 20px; 
                padding: 0 8px;
            }
            .promo-item { 
                padding: 12px 20px; 
                font-size: 15px; 
                max-width: 200px;
                flex: 1 1 45%;
                min-width: 140px;
            }
            .promo-item i { font-size: 18px; }
        }
        @media (max-width: 480px) {
            .promo-actions { 
                gap: 10px; 
                margin: 25px 0 15px;
                flex-direction: column;
                align-items: center;
            }
            .promo-item { 
                width: 280px;
                max-width: 280px;
                padding: 14px 24px;
                font-size: 16px;
            }
            .promo-item i { font-size: 20px; }
            
            .floating-promo { 
                padding: 10px 12px; 
                gap: 8px;
                flex-direction: column;
            }
            .floating-item { 
                width: 100%; 
                max-width: none;
                padding: 12px 20px;
                font-size: 15px;
                border-radius: 30px;
            }
            .floating-item i { font-size: 18px; }
        }
        @media (max-width: 360px) {
            .promo-item { width: 260px; }
            .floating-item { padding: 12px 18px; font-size: 14px; }
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

<!-- HEADER với class show ban đầu -->
<header class="header show">
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

<!-- NỘI DUNG CHÍNH -->
<section class="knowledge-banner" id="knowledgeBanner">
    <div class="container">
        <h1>KIẾN THỨC RƯỢU VANG</h1>
        <p class="subtitle">Kinh nghiệm rượu vang</p>
        <p class="description">Chào mừng bạn đến với Ruouchat.vn – Nơi chia sẻ những thông tin hữu ích về rượu ngoại, rượu vang nhập khẩu...</p>
    </div>
</section>

<section class="featured-posts">
    <div class="container">
        <div class="filter-bar" id="filterBar"></div>
        <div class="posts-grid-3col" id="postsGrid"></div>
        <div class="load-more-wrapper" id="loadMoreWrapper" style="display: none;">
            <button class="load-more-btn" id="loadMoreBtn">Xem thêm bài viết <i class="fas fa-chevron-down"></i></button>
        </div>
    </div>
</section>

<div class="promo-actions">
    <a href="lienhe.php" class="promo-item">
        <i class="fas fa-map-marker-alt"></i> TÌM CỬA HÀNG
    </a>
    <a href="#" class="promo-item open-offer-modal">
        <i class="fas fa-gift"></i> NHẬN ƯU ĐÃI
    </a>
    <a href="quatangdoanhnghiep.php" class="promo-item">
        <i class="fas fa-briefcase"></i> QUÀ DOANH NGHIỆP
    </a>
</div>

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

<div class="floating-promo" id="floatingPromo">
    <a href="lienhe.php" class="floating-item">
        <i class="fas fa-map-marker-alt"></i> Tìm cửa hàng
    </a>
    <a href="#" class="floating-item open-offer-modal">
        <i class="fas fa-gift"></i> Nhận ưu đãi
    </a>
    <a href="quatangdoanhnghiep.php" class="floating-item">
        <i class="fas fa-briefcase"></i> Quà doanh nghiệp
    </a>
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
            <div id="modalSuccessState" style="display: none; width: 100%;">
                <i class="fas fa-check-circle"></i>
                <h3>Đăng ký được gửi đi thành công</h3>
                <p>Cảm ơn bạn đã quan tâm! Chúng tôi sẽ liên hệ sớm nhất.</p>
                <button class="close-success-btn">Đóng</button>
            </div>
        </div>
    </div>
</div>

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
    
    // ==================== ACTIVE MENU THEO URL HIỆN TẠI ====================
    (function setActiveNavLink() {
        function normalizePath(url) {
            try {
                const u = new URL(url, window.location.origin);
                let path = u.pathname.replace(/\/+$/, '') || '/';
                return path + u.search;
            } catch (e) {
                return url;
            }
        }

        function applyActive() {
            const currentFull = normalizePath(window.location.href);
            const currentPathOnly = currentFull.split('?')[0];

            document.querySelectorAll('.nav-link.active').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.dropdown-column a.active').forEach(el => el.classList.remove('active'));

            function markActive(link) {
                link.classList.add('active');
                const parentDropdown = link.closest('.dropdown-menu');
                if (parentDropdown) {
                    const parentItem = parentDropdown.closest('.nav-item');
                    const parentLink = parentItem ? parentItem.querySelector(':scope > .nav-link') : null;
                    if (parentLink) parentLink.classList.add('active');
                }
            }

            const allLinks = document.querySelectorAll('.dropdown-column a, .nav-link');
            let matchedAny = false;

            // 1. Ưu tiên so khớp chính xác (đầy đủ path + query string)
            allLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (!href || href === '#') return;
                const linkFull = normalizePath(href);
                if (linkFull === currentFull) {
                    markActive(link);
                    matchedAny = true;
                }
            });

            // 2. Nếu chưa có, so khớp chỉ theo path (bỏ qua query string)
            if (!matchedAny) {
                allLinks.forEach(link => {
                    const href = link.getAttribute('href');
                    if (!href || href === '#') return;
                    const linkPathOnly = normalizePath(href).split('?')[0];
                    if (linkPathOnly !== '/' && linkPathOnly === currentPathOnly) {
                        markActive(link);
                        matchedAny = true;
                    }
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', applyActive);
        } else {
            applyActive();
        }
        window.addEventListener('popstate', applyActive);
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

    // ========== HEADER HIDE/SHOW ==========
    let lastScroll = 0;
    const header = document.querySelector('.header');
    const scrollThreshold = 100;
    const knowledgeBanner = document.getElementById('knowledgeBanner');

    function handleHeaderScroll() {
        let currentScroll = window.pageYOffset;
        
        if (currentScroll > scrollThreshold && currentScroll > lastScroll) {
            header.classList.add('hide');
            header.classList.remove('show');
        } 
        else if (currentScroll < lastScroll || currentScroll <= 20) {
            header.classList.add('show');
            header.classList.remove('hide');
        }
        
        lastScroll = currentScroll;
    }

    function adjustBannerMargin() {
        if (header && knowledgeBanner) {
            let headerHeight = header.offsetHeight;
            knowledgeBanner.style.marginTop = headerHeight + 'px';
        }
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
        adjustBannerMargin();
    }, 100);
    
    window.addEventListener('load', adjustBannerMargin);
    window.addEventListener('resize', adjustBannerMargin);

    // ========== CUỘN MƯỢT ==========
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
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
    if (searchInput) {
        searchInput.addEventListener('focus', showSuggestions);
        searchInput.addEventListener('input', showSuggestions);
        document.addEventListener('click', function(e) { if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) { suggestionsBox.classList.remove('active'); } });
    }

    // ========== API CART ==========
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
        return await callAPI('api/cart.php');
    }

    async function updateCartCount() {
        const cart = await getCart();
        if (cart && typeof cart === 'object') {
            const count = Object.values(cart).reduce((sum, item) => sum + (item.quantity || 0), 0);
            const badge = document.querySelector('.cart-count');
            if (badge) badge.innerText = count;
        }
    }
    
    // ========== WISHLIST COUNT ==========
    async function getWishlistFromDB() {
        try {
            const res = await fetch('api/wishlist.php', { credentials: 'include' });
            const data = await res.json();
            if (data.success && Array.isArray(data.data)) {
                return data.data.map(item => item.product_id);
            }
            return [];
        } catch(e) { return []; }
    }
    async function updateWishlistCount() {
        try {
            const wishlist = await getWishlistFromDB();
            const count = wishlist.length;
            const wishlistSpan = document.querySelector('.wishlist-count');
            if (wishlistSpan) wishlistSpan.innerText = count;
        } catch(e) { console.error(e); }
    }

    // ========== USER MENU ==========
    async function checkLoginStatus() { 
        try { 
            const response = await fetch('api/auth.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'check' }), credentials: 'include' }); 
            const data = await response.json(); 
            return data.logged_in ? data.user : null; 
        } catch(e) { return null; } 
    }
    async function performLogout() { 
        await fetch('api/auth.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'logout' }), credentials: 'include' }); 
        window.location.reload(); 
    }
    function updateUserMenu(user) {
        const userMenu = document.getElementById('userMenu');
        if (!userMenu) return;
        if (user) { 
            userMenu.innerHTML = `<li><a href="account.php"><i class="fas fa-user-circle"></i> Tài khoản</a></li><li><a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>`; 
            const logoutBtn = document.getElementById('logoutBtn'); 
            if (logoutBtn) logoutBtn.addEventListener('click', (e) => { e.preventDefault(); performLogout(); }); 
        }
        else { 
            userMenu.innerHTML = `<li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a></li><li><a href="register.php"><i class="fas fa-user-plus"></i> Đăng ký</a></li>`; 
        }
    }

    // ========== LOGIC BÀI VIẾT - ĐÃ SỬA LIMIT TỪ 6 LÊN 8 ==========
    let currentPage = 1;
    let totalPages = 1;
    let currentType = '';

    async function loadPosts(page = 1, postType = '')
    {
        // Sửa limit từ 6 thành 8 để hiển thị đủ 2 hàng (4 cột x 2 hàng)
        const url = `api/posts.php?page=${page}&limit=8` + (postType ? `&post_type=${postType}` : '');
        try
        {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            if (data.posts)
            {
                if (page === 1)
                {
                    renderPosts(data.posts);
                    totalPages = data.totalPages;
                    // Ẩn/hiện nút "Xem thêm" dựa trên tổng số trang
                    const loadMoreWrapper = document.getElementById('loadMoreWrapper');
                    if (totalPages > 1) {
                        loadMoreWrapper.style.display = 'block';
                    } else {
                        loadMoreWrapper.style.display = 'none';
                    }
                }
                else
                {
                    appendPosts(data.posts);
                    // Kiểm tra nếu đã tải hết thì ẩn nút
                    if (page >= totalPages) {
                        document.getElementById('loadMoreWrapper').style.display = 'none';
                    }
                }
                currentPage = page;
            }
        }
        catch (error)
        {
            console.error('Lỗi tải bài viết:', error);
            document.getElementById('postsGrid').innerHTML = '<div style="text-align:center; padding:50px;">Lỗi tải dữ liệu: ' + error.message + '</div>';
        }
    }

    function renderPosts(posts)
    {
        const grid = document.getElementById('postsGrid');
        if (posts.length === 0)
        {
            grid.innerHTML = '<div style="text-align:center; padding:50px;">Chưa có bài viết nào.</div>';
            return;
        }
        
        let html = '';
        posts.forEach(post =>
        {
            // Định dạng ngày tháng
            let formattedDate = post.created_at || '22/06/2026';
            if (post.created_at) {
                const date = new Date(post.created_at);
                if (!isNaN(date.getTime())) {
                    const d = String(date.getDate()).padStart(2, '0');
                    const m = String(date.getMonth() + 1).padStart(2, '0');
                    const y = date.getFullYear();
                    formattedDate = `${d}/${m}/${y}`;
                }
            }
            
            const imageSrc = post.image || 'assets/images/default-post.jpg';
            const category = post.post_type || 'Kiến thức';
            const title = escapeHtml(post.title);
            const excerpt = escapeHtml(post.short_description || post.content || '');
            const author = post.author || 'Admin';
            const slug = post.slug;
            const linkUrl = `kienthuc1.php?slug=${slug}`;
            
            html += `
                <a href="${linkUrl}" class="post-card-item" style="text-decoration: none; color: inherit;">
                    <div class="post-img-wrap">
                        <img src="${imageSrc}" alt="${title}" loading="lazy" onerror="this.src='assets/images/default-post.jpg'">
                    </div>
                    <div class="post-body">
                        <span class="post-category">${escapeHtml(category)}</span>
                        <h3 class="post-title">${title}</h3>
                        <p class="post-excerpt">${excerpt}</p>
                        <div class="post-meta">
                            <span class="author"><i class="fas fa-user-edit"></i> Đăng bởi: ${escapeHtml(author)}</span>
                            <span class="date"><i class="far fa-calendar-alt"></i> ${formattedDate}</span>
                        </div>
                    </div>
                </a>
            `;
        });
        
        grid.innerHTML = html;
    }

    function appendPosts(posts)
    {
        const grid = document.getElementById('postsGrid');
        let html = '';
        posts.forEach(post =>
        {
            let formattedDate = post.created_at || '22/06/2026';
            if (post.created_at) {
                const date = new Date(post.created_at);
                if (!isNaN(date.getTime())) {
                    const d = String(date.getDate()).padStart(2, '0');
                    const m = String(date.getMonth() + 1).padStart(2, '0');
                    const y = date.getFullYear();
                    formattedDate = `${d}/${m}/${y}`;
                }
            }
            
            const imageSrc = post.image || 'assets/images/default-post.jpg';
            const category = post.post_type || 'Kiến thức';
            const title = escapeHtml(post.title);
            const excerpt = escapeHtml(post.short_description || post.content || '');
            const author = post.author || 'Admin';
            const slug = post.slug;
            const linkUrl = `kienthuc1.php?slug=${slug}`;
            
            html += `
                <a href="${linkUrl}" class="post-card-item" style="text-decoration: none; color: inherit;">
                    <div class="post-img-wrap">
                        <img src="${imageSrc}" alt="${title}" loading="lazy" onerror="this.src='assets/images/default-post.jpg'">
                    </div>
                    <div class="post-body">
                        <span class="post-category">${escapeHtml(category)}</span>
                        <h3 class="post-title">${title}</h3>
                        <p class="post-excerpt">${excerpt}</p>
                        <div class="post-meta">
                            <span class="author"><i class="fas fa-user-edit"></i> Đăng bởi: ${escapeHtml(author)}</span>
                            <span class="date"><i class="far fa-calendar-alt"></i> ${formattedDate}</span>
                        </div>
                    </div>
                </a>
            `;
        });
        
        grid.insertAdjacentHTML('beforeend', html);
    }

    async function loadPostTypes()
    {
        try
        {
            const res = await fetch('api/post_types.php');
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const types = await res.json();
            const filterBar = document.getElementById('filterBar');
            filterBar.innerHTML = '';
            const allBtn = document.createElement('button');
            allBtn.className = 'filter-btn active';
            allBtn.setAttribute('data-type', '');
            allBtn.innerText = 'Tất cả';
            allBtn.onclick = () => filterByType('');
            filterBar.appendChild(allBtn);
            types.forEach(t =>
            {
                if (t.post_type)
                {
                    const btn = document.createElement('button');
                    btn.className = 'filter-btn';
                    btn.setAttribute('data-type', t.post_type);
                    btn.innerText = getTypeLabel(t.post_type);
                    btn.onclick = () => filterByType(t.post_type);
                    filterBar.appendChild(btn);
                }
            });
            const urlParams = new URLSearchParams(window.location.search);
            const typeParam = urlParams.get('type');
            if (typeParam) filterByType(typeParam);
            else loadPosts(1, '');
        }
        catch (error)
        {
            console.error('Lỗi tải danh mục:', error);
        }
    }

    function getTypeLabel(type)
    {
        const labels = {
            'wine': 'Kiến thức rượu vang',
            'spirit': 'Kiến thức rượu mạnh',
            'hot': 'Top sản phẩm HOT',
            'recipe': 'Công thức pha chế rượu',
            'gift': 'Blog Quà tặng'
        };
        return labels[type] || type;
    }

    function filterByType(type)
    {
        currentType = type;
        currentPage = 1;
        loadPosts(1, type);
        document.querySelectorAll('.filter-btn').forEach(btn =>
        {
            btn.classList.remove('active');
            if (btn.getAttribute('data-type') === type) btn.classList.add('active');
        });
    }

    document.getElementById('loadMoreBtn')?.addEventListener('click', () =>
    {
        if (currentPage < totalPages) loadPosts(currentPage + 1, currentType);
    });

    // ========== FLOATING PROMO - CHỈ CHẠY TRÊN PC ==========
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

    // ========== KHỞI CHẠY ==========
    document.addEventListener('DOMContentLoaded', async function() {
        // User menu
        const currentUser = await checkLoginStatus();
        updateUserMenu(currentUser);
        
        // Cập nhật giỏ hàng
        await updateCartCount();
        await updateWishlistCount();
        
        // Tải bài viết
        loadPostTypes();
        
        // Modal ưu đãi
        const modal = document.getElementById('offerModal');
        const openBtns = document.querySelectorAll('.open-offer-modal');
        const closeBtn = document.querySelector('.close-modal');
        const formState = document.getElementById('modalFormState');
        const successState = document.getElementById('modalSuccessState');
        const form = document.getElementById('offerForm');
        const closeSuccessBtn = document.querySelector('.close-success-btn');
        if (modal && openBtns.length) {
            openBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (formState) formState.style.display = 'block';
                    if (successState) successState.style.display = 'none';
                    modal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                });
            });
            function closeModal() {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                if (form) form.reset();
            }
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            window.addEventListener('click', function(e) { if (e.target == modal) closeModal(); });
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const name = document.getElementById('name').value.trim();
                    const phone = document.getElementById('phone').value.trim();
                    if (name === '' || phone === '') { alert('Vui lòng điền đầy đủ họ tên và số điện thoại.'); return; }
                    if (formState) formState.style.display = 'none';
                    if (successState) successState.style.display = 'block';
                });
            }
            if (closeSuccessBtn) closeSuccessBtn.addEventListener('click', closeModal);
        }
    });
</script>
</body>
</html>