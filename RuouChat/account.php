<?php
require_once 'includes/menu.php'; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Ruouchat | Rượu vang nhập khẩu chính hãng</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="https://winevn.com/images/favicon.png" type="image/x-icon">
    <style>
        /* ===== RESET & GLOBAL ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary-red: #990D23; --dark-red: #670011; --deep-red: #55000D; --gold: #FFBD27; --light-bg: #FBF8EE; --cream: #F2EFE2; --border-color: #E8E8E8; --text-dark: #212529; --text-light: #6C757D; }
        body { font-family: 'Inter', sans-serif; color: var(--text-dark); background: white; line-height: 1.6; overflow-x: hidden; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; width: 100%; }
        
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

        /* ===== ACCOUNT PAGE STYLES ===== */
        .account-main { padding: 30px 0 60px; }
        .account-grid { 
            display: grid; 
            grid-template-columns: 260px 1fr; 
            gap: 30px; 
            transition: margin-top 0.2s ease;
        }
        @media (max-width: 992px) { 
            .account-grid { grid-template-columns: 1fr; gap: 20px; } 
            .account-main { padding: 20px 0 40px; }
        }
        
        .account-sidebar { 
            background: white; 
            border: 1px solid var(--border-color); 
            border-radius: 16px; 
            padding: 12px 0; 
            height: fit-content; 
        }
        @media (max-width: 768px) {
            .account-sidebar { 
                overflow-x: auto; 
                white-space: nowrap; 
                padding: 8px 0; 
                border-radius: 40px; 
                background: var(--light-bg); 
                border: none; 
            }
            .sidebar-menu { 
                display: inline-flex; 
                gap: 8px; 
                padding: 0 12px; 
                list-style: none; 
            }
            .sidebar-menu li { display: inline-block; }
            .sidebar-menu li a { 
                border-left: none !important; 
                background: white; 
                border-radius: 60px; 
                padding: 8px 20px; 
                font-size: 14px; 
                white-space: nowrap; 
            }
            .sidebar-menu li.active a { 
                background: var(--primary-red); 
                color: white; 
            }
            .sidebar-menu hr { display: none; }
        }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li a { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 12px 24px; 
            color: var(--text-dark); 
            text-decoration: none; 
            font-size: 15px; 
            font-weight: 500; 
            border-left: 3px solid transparent; 
            cursor: pointer; 
            transition: 0.2s; 
        }
        .sidebar-menu li a:hover { 
            background: var(--light-bg); 
            color: var(--primary-red); 
            border-left-color: var(--primary-red); 
        }
        .sidebar-menu li.active a { 
            background: var(--light-bg); 
            color: var(--primary-red); 
            border-left-color: var(--primary-red); 
            font-weight: 600; 
        }
        .sidebar-menu hr { 
            margin: 8px 20px; 
            border: none; 
            border-top: 1px solid var(--border-color); 
        }
        
        .account-content { 
            background: white; 
            border: 1px solid var(--border-color); 
            border-radius: 20px; 
            padding: 28px 32px; 
            width: 100%; 
            overflow-x: auto; 
        }
        @media (max-width: 576px) { 
            .account-content { padding: 20px 16px; } 
        }
        .account-content h1 { 
            font-size: 26px; 
            font-family: 'Merriweather', serif; 
            font-weight: 700; 
            color: var(--primary-red); 
            margin-bottom: 22px; 
        }
        .account-content h2 { 
            font-size: 22px; 
            font-weight: 700; 
            color: var(--primary-red); 
            margin-bottom: 18px; 
            border-bottom: 2px solid var(--gold); 
            padding-bottom: 10px; 
        }
        .welcome-message { 
            background: var(--light-bg); 
            padding: 20px; 
            border-radius: 16px; 
            margin-bottom: 25px; 
            word-break: break-word; 
        }
        .welcome-message p { margin-bottom: 5px; }
        .welcome-message p:last-child { margin-bottom: 0; }
        .welcome-message strong { color: var(--primary-red); }
        
        .dashboard-info { 
            display: grid; 
            gap: 24px; 
            margin-top: 30px; 
            max-width: 960px; 
            margin-left: auto; 
            margin-right: auto; 
        }
        @media (min-width: 992px) { .dashboard-info { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 991px) and (min-width: 577px) { .dashboard-info { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .dashboard-info { grid-template-columns: 1fr; } }
        
        .info-card { 
            background: #f9f9f9; 
            padding: 24px 12px; 
            border-radius: 20px; 
            text-align: center; 
            cursor: pointer; 
            transition: 0.2s; 
        }
        .info-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 24px rgba(0,0,0,0.1); 
            background: white; 
        }
        .info-card i { 
            font-size: 40px; 
            color: var(--primary-red); 
            margin-bottom: 12px; 
        }
        .info-card h3 { 
            font-size: 18px; 
            margin-bottom: 4px; 
        }
        .info-card p { 
            font-size: 13px; 
            color: #6c757d; 
        }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* ===== ORDERS TABLE ===== */
        .orders-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 16px; 
            display: block; 
            overflow-x: auto; 
            white-space: nowrap; 
        }
        .orders-table th, .orders-table td { 
            border: 1px solid var(--border-color); 
            padding: 10px 14px; 
            text-align: left; 
        }
        .orders-table th { 
            background: var(--light-bg); 
            font-weight: 600; 
        }
        @media (max-width: 640px) { 
            .orders-table th, .orders-table td { padding: 8px 10px; font-size: 13px; } 
        }
        
        /* ===== ADDRESS ===== */
        .address-card { 
            background: #f9f9f9; 
            border: 1px solid var(--border-color); 
            border-radius: 16px; 
            padding: 18px 20px; 
            margin-bottom: 16px; 
        }
        .address-card .address-name-phone { 
            font-size: 16px; 
            font-weight: 600; 
            margin-bottom: 8px; 
        }
        .address-card .address-name-phone i { 
            color: var(--primary-red); 
            margin-right: 6px; 
        }
        .address-card .address-detail { 
            font-size: 15px; 
            color: #555; 
            margin-bottom: 12px; 
            padding-left: 6px; 
        }
        .address-card .address-detail i { 
            color: var(--gold); 
            margin-right: 8px; 
        }
        .address-card .address-actions { 
            display: flex; 
            gap: 10px; 
            flex-wrap: wrap; 
        }
        .address-empty { 
            text-align: center; 
            padding: 40px 20px; 
        }
        .address-empty i { 
            font-size: 48px; 
            color: var(--primary-red); 
            opacity: 0.5; 
        }
        .address-empty p { 
            margin: 15px 0; 
        }
        
        .address-form-container { 
            background: #fff; 
            border: 1px solid var(--border-color); 
            border-radius: 16px; 
            padding: 20px; 
            margin-bottom: 20px; 
            display: none; 
        }
        .address-form-container.show { display: block; }
        .address-form-container h3 { 
            font-size: 18px; 
            font-weight: 700; 
            color: var(--primary-red); 
            margin-bottom: 16px; 
        }
        .address-form-container .form-actions { 
            display: flex; 
            gap: 12px; 
            margin-top: 16px; 
        }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { 
            display: block; 
            margin-bottom: 6px; 
            font-weight: 600; 
            font-size: 14px; 
        }
        .form-group input, .form-group textarea { 
            width: 100%; 
            padding: 12px 14px; 
            border: 1px solid var(--border-color); 
            border-radius: 12px; 
            font-size: 14px; 
            font-family: 'Inter', sans-serif; 
            transition: border 0.2s; 
        }
        .form-group input:focus, .form-group textarea:focus { 
            outline: none; 
            border-color: var(--primary-red); 
            box-shadow: 0 0 0 3px rgba(153,13,35,0.1); 
        }
        .form-group textarea { resize: vertical; min-height: 80px; }
        
        .btn-save { 
            background: var(--primary-red); 
            color: white; 
            border: none; 
            padding: 10px 28px; 
            border-radius: 40px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: background 0.2s; 
        }
        .btn-save:hover { background: var(--dark-red); }
        .btn-cancel { 
            background: #6c757d; 
            color: white; 
            border: none; 
            padding: 10px 28px; 
            border-radius: 40px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: background 0.2s; 
        }
        .btn-cancel:hover { background: #5a6268; }
        .btn-add-address { 
            background: var(--primary-red); 
            color: white; 
            border: none; 
            padding: 10px 24px; 
            border-radius: 40px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: background 0.2s; 
        }
        .btn-add-address:hover { background: var(--dark-red); }
        .btn-edit { 
            background: white; 
            border: 1px solid var(--border-color); 
            padding: 6px 18px; 
            border-radius: 40px; 
            cursor: pointer; 
            font-size: 13px; 
            transition: 0.2s; 
        }
        .btn-edit:hover { 
            border-color: var(--primary-red); 
            color: var(--primary-red); 
        }
        .btn-delete { 
            background: white; 
            border: 1px solid #dc3545; 
            color: #dc3545; 
            padding: 6px 18px; 
            border-radius: 40px; 
            cursor: pointer; 
            font-size: 13px; 
            transition: 0.2s; 
        }
        .btn-delete:hover { 
            background: #dc3545; 
            color: white; 
        }
        
        /* ===== FAVORITES ===== */
        .favorites-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); 
            gap: 20px; 
            margin-top: 20px; 
        }
        @media (max-width: 550px) { .favorites-grid { grid-template-columns: 1fr; } }
        
        .favorite-card { 
            background: #fff; 
            border: 1px solid var(--border-color); 
            border-radius: 16px; 
            overflow: hidden; 
            transition: box-shadow 0.2s; 
        }
        .favorite-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .favorite-img { 
            width: 100%; 
            height: 200px; 
            object-fit: cover; 
            background: #f5f5f5; 
        }
        .favorite-info { padding: 14px 16px; }
        .favorite-name { 
            font-weight: 600; 
            font-size: 15px; 
            margin-bottom: 6px; 
            display: -webkit-box; 
            -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; 
            overflow: hidden; 
        }
        .favorite-price { 
            font-size: 17px; 
            font-weight: 700; 
            color: var(--primary-red); 
            margin: 6px 0 12px; 
        }
        .favorite-actions { 
            display: flex; 
            gap: 10px; 
            flex-wrap: wrap; 
        }
        .btn-view-product { 
            background: var(--primary-red); 
            color: white; 
            padding: 6px 16px; 
            border-radius: 40px; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            font-size: 13px; 
            transition: background 0.2s; 
        }
        .btn-view-product:hover { background: var(--dark-red); }
        .btn-favorite-remove { 
            background: white; 
            border: 1px solid #dc3545; 
            color: #dc3545; 
            padding: 6px 16px; 
            border-radius: 40px; 
            cursor: pointer; 
            font-size: 13px; 
            transition: 0.2s; 
        }
        .btn-favorite-remove:hover { 
            background: #dc3545; 
            color: white; 
        }
        
        /* ===== PROMOTIONS ===== */
        .promo-list { 
            display: grid; 
            gap: 16px; 
            margin-top: 16px; 
        }
        .promo-item { 
            background: #f9f9f9; 
            border: 1px solid var(--border-color); 
            border-radius: 12px; 
            padding: 16px 20px; 
            display: flex; 
            flex-wrap: wrap; 
            align-items: center; 
            justify-content: space-between; 
            gap: 12px; 
        }
        .promo-item .promo-code { 
            font-weight: 700; 
            color: var(--primary-red); 
            font-size: 18px; 
            letter-spacing: 1px; 
        }
        .promo-item .promo-desc { 
            flex: 1; 
            min-width: 120px; 
            font-size: 14px; 
            color: #555; 
        }
        .promo-item .promo-value { 
            font-weight: 600; 
            color: var(--dark-red); 
        }
        .promo-item .promo-expiry { 
            font-size: 13px; 
            color: #888; 
        }
        .promo-item .promo-status { 
            padding: 3px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600; 
        }
        .promo-item .promo-status.active { 
            background: #28a745; 
            color: white; 
        }
        .promo-item .promo-status.expired { 
            background: #6c757d; 
            color: white; 
        }
        @media (max-width: 576px) { 
            .promo-item { flex-direction: column; align-items: flex-start; } 
        }
        
        /* ===== PROFILE ===== */
        #tab-profile .form-group input[readonly] { 
            background: #f5f5f5; 
            cursor: not-allowed; 
        }
        #profileMessage { 
            padding: 10px 16px; 
            border-radius: 8px; 
            margin: 12px 0; 
            display: none; 
        }
        #profileMessage.success-msg { 
            display: block; 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        #profileMessage.error-msg { 
            display: block; 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        #tab-profile hr { 
            margin: 24px 0; 
            border: none; 
            border-top: 2px solid var(--border-color); 
        }
        #tab-profile h3 { 
            font-size: 18px; 
            font-weight: 700; 
            color: var(--primary-red); 
            margin-bottom: 16px; 
        }

        /* ===== PROMO ACTIONS - RESPONSIVE ===== */
        .promo-actions { 
            display: flex; 
            justify-content: center; 
            gap: 12px;
            margin: 40px 0 30px;
            flex-wrap: wrap; 
            padding: 0 10px;
        }
        .promo-link-item { 
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
        .promo-link-item i { font-size: 16px; }
        .promo-link-item:hover { 
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
                gap: 10px; 
                margin: 30px 0 20px; 
                padding: 0 8px;
            }
            .promo-link-item { 
                padding: 9px 18px; 
                font-size: 13px; 
                max-width: 150px;
                flex: 1 1 45%;
                min-width: 130px;
            }
            .promo-link-item i { font-size: 15px; }
        }
        @media (max-width: 480px) {
            .promo-actions { 
                gap: 8px; 
                margin: 25px 0 15px;
                flex-direction: column;
                align-items: center;
            }
            .promo-link-item { 
                width: 220px;
                max-width: 220px;
                padding: 10px 20px;
                font-size: 14px;
            }
            .promo-link-item i { font-size: 16px; }
            
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
            .promo-link-item { width: 200px; }
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
                <a href="account.php?tab=favorites" class="nav-icon-link" id="wishlistHeaderLink">
                    <i class="fa-solid fa-heart"></i>
                    <span class="wishlist-count" style="display: none;">0</span>
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

<main class="account-main">
    <div class="container">
        <div class="account-grid">
            <aside class="account-sidebar">
                <ul class="sidebar-menu">
                    <li data-tab="dashboard" class="active"><a><i class="fas fa-tachometer-alt"></i> Trang tài khoản</a></li>
                    <li data-tab="history"><a><i class="fas fa-shopping-bag"></i> Lịch sử</a></li>
                    <li data-tab="address"><a><i class="fas fa-map-marker-alt"></i> Địa chỉ</a></li>
                    <li data-tab="favorites"><a><i class="fas fa-heart"></i> Yêu thích</a></li>
                    <li data-tab="promotion"><a><i class="fas fa-ticket-alt"></i> Khuyến mãi</a></li>
                    <li data-tab="profile"><a><i class="fas fa-user-cog"></i> Tài khoản</a></li>
                    <hr>
                    <li><a href="#" id="logoutSidebar"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                </ul>
            </aside>
            <div class="account-content">
                <h1 id="pageTitle">TÀI KHOẢN</h1>
                <div id="tab-dashboard" class="tab-content active">
                    <div class="welcome-message" id="welcomeMessage"></div>
                    <div class="dashboard-info">
                        <div class="info-card" data-tab="history"><i class="fas fa-shopping-cart"></i><h3>Lịch sử</h3><p>Xem đơn hàng</p></div>
                        <div class="info-card" data-tab="address"><i class="fas fa-map-marker-alt"></i><h3>Địa chỉ</h3><p>Quản lý địa chỉ</p></div>
                        <div class="info-card" data-tab="favorites"><i class="fas fa-heart"></i><h3>Yêu thích</h3><p>Sản phẩm đã lưu</p></div>
                        <div class="info-card" data-tab="profile"><i class="fas fa-user-edit"></i><h3>Thông tin</h3><p>Cập nhật tài khoản</p></div>
                        <div class="info-card" data-tab="promotion"><i class="fas fa-ticket-alt"></i><h3>Khuyến mãi</h3><p>Ưu đãi</p></div>
                        <div class="info-card" id="dashboardLogoutCard"><i class="fas fa-sign-out-alt"></i><h3>Đăng xuất</h3><p>Thoát</p></div>
                    </div>
                </div>
                <div id="tab-history" class="tab-content"><h2>Lịch sử đơn hàng</h2><div id="ordersContainer"></div></div>
                <div id="tab-address" class="tab-content"><h2>Địa chỉ nhận hàng</h2><div class="address-form-container" id="addressFormContainer"><h3 id="formTitle">📍 Thêm địa chỉ mới</h3><div class="form-group"><label>Địa chỉ chi tiết</label><textarea id="addrDetail" rows="3" placeholder="Nhập địa chỉ cụ thể..."></textarea></div><div class="form-actions"><button class="btn-save" id="saveAddressBtn">Lưu</button><button class="btn-cancel" id="cancelAddressBtn">Hủy</button></div></div><div id="addressListContainer"></div></div>
                <div id="tab-favorites" class="tab-content"><h2>Sản phẩm yêu thích</h2><div id="favoritesContainer"></div></div>
                <div id="tab-promotion" class="tab-content"><h2>🎁 Mã khuyến mãi</h2><div id="promoListContainer"></div></div>
                <div id="tab-profile" class="tab-content"><h2>Thông tin tài khoản</h2><div class="form-group"><label>Họ tên</label><input type="text" id="profileFullname" placeholder="Nhập họ tên"></div><div class="form-group"><label>Email</label><input type="email" id="profileEmail" readonly></div><div class="form-group"><label>Số điện thoại</label><input type="tel" id="profilePhone" placeholder="Nhập số điện thoại"></div><hr><h3>🔒 Đổi mật khẩu</h3><div class="form-group"><label>Mật khẩu hiện tại</label><input type="password" id="currentPassword" placeholder="Nhập mật khẩu hiện tại"></div><div class="form-group"><label>Mật khẩu mới</label><input type="password" id="newPassword" placeholder="Nhập mật khẩu mới"></div><div class="form-group"><label>Xác nhận mật khẩu mới</label><input type="password" id="confirmNewPassword" placeholder="Xác nhận mật khẩu mới"></div><div id="profileMessage"></div><button class="btn-save" id="saveProfile">Cập nhật</button></div>
            </div>
        </div>
    </div>
</main>

<div class="promo-actions">
    <a href="lienhe.php" class="promo-link-item">
        <i class="fas fa-map-marker-alt"></i> TÌM CỬA HÀNG
    </a>
    <a href="#" class="promo-link-item open-offer-modal">
        <i class="fas fa-gift"></i> NHẬN ƯU ĐÃI
    </a>
    <a href="quatangdoanhnghiep.php" class="promo-link-item">
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
    // ==================== HEADER HIDE/SHOW (GIỐNG product_main) ====================
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

    function adjustAccountGridMarginTop() {
        const accountGrid = document.querySelector('.account-grid');
        if (accountGrid && headerElem) {
            let headerHeight = headerElem.offsetHeight;
            accountGrid.style.marginTop = (headerHeight + 30) + 'px';
        }
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
        adjustAccountGridMarginTop();
    }, 100);
    
    window.addEventListener('load', adjustAccountGridMarginTop);
    window.addEventListener('resize', adjustAccountGridMarginTop);

    // ==================== TÌM KIẾM ====================
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

            allLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (!href || href === '#') return;
                const linkFull = normalizePath(href);
                if (linkFull === currentFull) {
                    markActive(link);
                    matchedAny = true;
                }
            });

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
    
    // ==================== GIỎ HÀNG ====================
    async function updateCartCount() {
        try {
            const res = await fetch('api/cart.php', { credentials: 'include' });
            const cart = await res.json();
            const totalQty = Object.values(cart).reduce((sum, item) => sum + (item.quantity || 0), 0);
            const badge = document.querySelector('.cart-count');
            if (badge) badge.innerText = totalQty;
        } catch(e) { console.error(e); }
    }
    updateCartCount();
    
    // ==================== USER DROPDOWN ====================
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
        window.location.href = 'login.php';
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
    
    // ==================== XỬ LÝ TÀI KHOẢN ====================
    const API_BASE = 'api/';
    let currentUserInfo = null;
    let currentTab = 'dashboard';

    async function callApi(endpoint, method = 'GET', data = null) {
        let options = { method, headers: { 'Content-Type': 'application/json' }, credentials: 'include' };
        if (data) options.body = JSON.stringify(data);
        try {
            let res = await fetch(API_BASE + endpoint, options);
            let text = await res.text();
            if (!text.trim().startsWith('{') && !text.trim().startsWith('[')) {
                console.error('API trả về HTML/Text:', text.substring(0, 200));
                return { success: false, error: 'Lỗi server (response không phải JSON)' };
            }
            return JSON.parse(text);
        } catch (e) {
            console.error('Lỗi parse JSON:', e);
            return { success: false, error: 'Lỗi kết nối hoặc server' };
        }
    }

    function formatPrice(p) { return new Intl.NumberFormat('vi-VN').format(p); }
    function formatDate(d) { if (!d) return ''; return d.split('-').reverse().join('/'); }

    async function displayUserInfo() {
        let res = await callApi('get_user_info.php');
        if (!res.success) window.location.href = 'login.php';
        currentUserInfo = res.user;
        document.getElementById('welcomeMessage').innerHTML = 
            `<p><strong>Xin chào <span style="color:var(--primary-red);">${escapeHtml(res.user.fullname || res.user.email)}</span></strong></p>
            <p>Quản lý đơn hàng, địa chỉ, khuyến mãi và thông tin cá nhân.</p>`;
        document.getElementById('profileFullname').value = res.user.fullname || '';
        document.getElementById('profileEmail').value = res.user.email || '';
        document.getElementById('profilePhone').value = res.user.phone || '';
    }

    async function loadOrders() {
        let res = await callApi('get_orders.php');
        let container = document.getElementById('ordersContainer');
        if (!res.success || res.orders.length === 0) { 
            container.innerHTML = '<p style="padding:20px; text-align:center; color:#888;">Chưa có đơn hàng nào.</p>'; 
            return; 
        }
        let html = `<table class="orders-table"><thead><tr><th>Mã đơn</th><th>Ngày</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead><tbody>`;
        res.orders.forEach(o => { 
            html += `<tr><td>${escapeHtml(o.order_code)}</td><td>${o.created_at_formatted}</td><td>${o.total_formatted}</td><td>${o.status_text}</td></tr>`; 
        });
        html += `</tbody></table>`;
        container.innerHTML = html;
    }

    async function loadAddress() {
        if (!currentUserInfo) await displayUserInfo();
        let resAddr = await callApi('get_addresses.php');
        let address = (resAddr.success && resAddr.address) ? resAddr.address : '';
        let container = document.getElementById('addressListContainer');
        let fullname = currentUserInfo.fullname || 'Chưa cập nhật';
        let phone = currentUserInfo.phone || 'Chưa có SĐT';

        if (!address) {
            container.innerHTML = `<div class="address-empty">
                <i class="fas fa-map-marker-alt"></i>
                <p>Bạn chưa có địa chỉ giao hàng.</p>
                <button class="btn-add-address" id="showAddAddressBtn">+ Thêm địa chỉ mới</button>
            </div>`;
            let addBtn = document.getElementById('showAddAddressBtn');
            if (addBtn) addBtn.onclick = () => showAddressForm(false, '');
            return;
        }
        let addressHtml = `<div class="address-card">
            <div class="address-name-phone"><i class="fas fa-user-circle"></i> ${escapeHtml(fullname)} <span style="color:#999; margin:0 6px;">|</span> <i class="fas fa-phone-alt"></i> ${escapeHtml(phone)}</div>
            <div class="address-detail"><i class="fas fa-location-dot"></i> ${escapeHtml(address)}</div>
            <div class="address-actions">
                <button class="btn-edit" id="editAddressBtn"><i class="fas fa-pen"></i> Sửa</button>
                <button class="btn-delete" id="deleteAddressBtn"><i class="fas fa-trash-alt"></i> Xóa</button>
            </div>
        </div>`;
        container.innerHTML = addressHtml;
        document.getElementById('editAddressBtn').onclick = () => showAddressForm(true, address);
        document.getElementById('deleteAddressBtn').onclick = async () => {
            if (confirm('Bạn có chắc chắn muốn xóa địa chỉ này?')) {
                let res = await callApi('delete_address.php', 'POST', {});
                if (res.success) await loadAddress();
                else alert(res.message || 'Xóa thất bại');
            }
        };
    }

    function showAddressForm(editMode = false, existingAddress = '') {
        let formContainer = document.getElementById('addressFormContainer');
        document.getElementById('formTitle').innerText = editMode ? '✏️ Sửa địa chỉ' : '📍 Thêm địa chỉ mới';
        document.getElementById('addrDetail').value = existingAddress;
        formContainer.classList.add('show');
        
        let saveBtn = document.getElementById('saveAddressBtn');
        let newSaveBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);
        newSaveBtn.onclick = async () => {
            let address = document.getElementById('addrDetail').value.trim();
            if (!address) { alert('Vui lòng nhập địa chỉ chi tiết'); return; }
            let res = await callApi('update_address.php', 'POST', { address });
            if (res.success) { hideAddressForm(); await loadAddress(); }
            else alert(res.message || 'Cập nhật địa chỉ thất bại');
        };
        
        let cancelBtn = document.getElementById('cancelAddressBtn');
        let newCancelBtn = cancelBtn.cloneNode(true);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
        newCancelBtn.onclick = () => hideAddressForm();
    }

    function hideAddressForm() {
        document.getElementById('addressFormContainer').classList.remove('show');
        document.getElementById('addrDetail').value = '';
    }

    async function loadFavorites() {
        let container = document.getElementById('favoritesContainer');
        container.innerHTML = '<p style="padding:20px; text-align:center; color:#888;">Đang tải...</p>';
        try {
            let res = await callApi('wishlist.php');
            if (!res.success || !res.data || res.data.length === 0) {
                container.innerHTML = '<p style="padding:20px; text-align:center; color:#888;">💔 Chưa có sản phẩm yêu thích nào. Hãy khám phá và thêm sản phẩm bạn nhé!</p>';
                return;
            }

            let html = `<div class="favorites-grid">`;
            for (let item of res.data) {
                let productLink = '#';
                const ptype = item.product_type;
                if (ptype === 'product') {
                    let typeValue = item.type || item.product_subtype || '1';
                    productLink = item.slug ? `sp1.php?slug=${item.slug}&type=${typeValue}` : `sp1.php?id=${item.product_id}&type=${typeValue}`;
                } else if (ptype === 'crystal_glasses' || ptype === 'glass') {
                    productLink = item.slug ? `sp2.php?slug=${item.slug}` : `sp2.php?id=${item.product_id}`;
                } else if (ptype === 'gift') {
                    productLink = item.slug ? `sp1.php?slug=${item.slug}&type=gift` : `sp1.php?id=${item.product_id}&type=gift`;
                } else if (ptype === 'other') {
                    productLink = item.slug ? `sp_other.php?slug=${item.slug}` : `sp_other.php?id=${item.product_id}`;
                } else {
                    productLink = `product_detail.php?id=${item.product_id}`;
                }
                let imageUrl = (item.image && item.image !== '') ? item.image : 'https://placehold.co/300x200?text=No+Image';
                let priceDisplay = (item.price && item.price > 0) ? formatPrice(item.price) + ' ₫' : 'Liên hệ';
                html += `
                    <div class="favorite-card">
                        <img class="favorite-img" src="${imageUrl}" alt="${escapeHtml(item.name)}" onerror="this.src='https://placehold.co/300x200?text=No+Image'">
                        <div class="favorite-info">
                            <div class="favorite-name">${escapeHtml(item.name)}</div>
                            <div class="favorite-price">${priceDisplay}</div>
                            <div class="favorite-actions">
                                <a href="${productLink}" class="btn-view-product"><i class="fas fa-eye"></i> Xem chi tiết</a>
                                <button class="btn-favorite-remove" data-id="${item.product_id}" data-type="${item.product_type}"><i class="fas fa-trash-alt"></i> Xóa</button>
                            </div>
                        </div>
                    </div>
                `;
            }
            html += `</div>`;
            container.innerHTML = html;
            document.querySelectorAll('.btn-favorite-remove').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    let productId = btn.getAttribute('data-id');
                    let productType = btn.getAttribute('data-type');
                    if (!productId) return;
                    let resDel = await callApi('wishlist.php', 'DELETE', { product_id: productId, product_type: productType });
                    if (resDel.success) await loadFavorites();
                    else alert(resDel.error || 'Xóa thất bại');
                });
            });
        } catch (e) {
            console.error(e);
            container.innerHTML = '<p style="padding:20px; text-align:center; color:#d9534f;">Lỗi tải dữ liệu yêu thích. Vui lòng thử lại sau.</p>';
        }
    }

    async function loadPromotions() {
        let res = await callApi('get_promotions.php');
        let container = document.getElementById('promoListContainer');
        if (!res.success || res.promotions.length === 0) { 
            container.innerHTML = '<p style="padding:20px; text-align:center; color:#888;">Hiện chưa có khuyến mãi nào.</p>'; 
            return; 
        }
        let html = `<div class="promo-list">`;
        res.promotions.forEach(p => {
            let expired = p.expiry_date && new Date(p.expiry_date) < new Date();
            let statusClass = expired ? 'expired' : 'active';
            let statusText = expired ? 'Hết hạn' : 'Còn hiệu lực';
            html += `<div class="promo-item">
                <div class="promo-code">${escapeHtml(p.code)}</div>
                <div class="promo-desc">${escapeHtml(p.description)}</div>
                <div class="promo-value">💰 Giảm: ${escapeHtml(p.discount_value)}</div>
                <div class="promo-expiry"><i class="far fa-calendar-alt"></i> Hạn: ${p.expiry_date ? formatDate(p.expiry_date) : 'Không giới hạn'}</div>
                <span class="promo-status ${statusClass}">${statusText}</span>
            </div>`;
        });
        html += `</div>`;
        container.innerHTML = html;
    }

    async function updateProfile() {
        let fullname = document.getElementById('profileFullname').value.trim();
        let phone = document.getElementById('profilePhone').value.trim();
        let current_password = document.getElementById('currentPassword').value;
        let new_password = document.getElementById('newPassword').value;
        let confirm = document.getElementById('confirmNewPassword').value;
        
        let msgEl = document.getElementById('profileMessage');
        msgEl.className = '';
        msgEl.style.display = 'none';
        
        if (new_password && new_password !== confirm) {
            msgEl.innerText = 'Mật khẩu mới không khớp';
            msgEl.className = 'error-msg';
            msgEl.style.display = 'block';
            return;
        }
        let res = await callApi('update_profile.php', 'POST', { fullname, phone, current_password, new_password });
        if (res.success) {
            msgEl.innerText = res.message;
            msgEl.className = 'success-msg';
            msgEl.style.display = 'block';
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmNewPassword').value = '';
            let userRefresh = await callApi('get_user_info.php');
            if (userRefresh.success && userRefresh.user) {
                currentUserInfo = userRefresh.user;
                document.getElementById('welcomeMessage').innerHTML = 
                    `<p><strong>Xin chào <span style="color:var(--primary-red);">${escapeHtml(currentUserInfo.fullname || currentUserInfo.email)}</span></strong></p>
                    <p>Quản lý đơn hàng, địa chỉ, khuyến mãi và thông tin cá nhân.</p>`;
                if (currentTab === 'address') await loadAddress();
            }
            setTimeout(() => { msgEl.style.display = 'none'; }, 3000);
        } else {
            msgEl.innerText = res.message;
            msgEl.className = 'error-msg';
            msgEl.style.display = 'block';
        }
    }

    async function logoutAccount() {
        await callApi('logout.php', 'POST');
        window.location.href = 'login.php';
    }

    async function switchTab(tab) {
        currentTab = tab;
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        const activeTab = document.getElementById(`tab-${tab}`);
        if (activeTab) activeTab.classList.add('active');
        
        document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
        let activeSidebar = document.querySelector(`.sidebar-menu li[data-tab="${tab}"]`);
        if (activeSidebar) activeSidebar.classList.add('active');
        
        let titles = { 
            dashboard: 'TÀI KHOẢN', 
            history: 'LỊCH SỬ ĐƠN HÀNG', 
            address: 'ĐỊA CHỈ NHẬN HÀNG', 
            favorites: 'SẢN PHẨM YÊU THÍCH', 
            promotion: 'MÃ KHUYẾN MÃI', 
            profile: 'THÔNG TIN TÀI KHOẢN' 
        };
        document.getElementById('pageTitle').innerText = titles[tab] || 'TÀI KHOẢN';
        
        if (tab === 'history') await loadOrders();
        if (tab === 'address') await loadAddress();
        if (tab === 'favorites') await loadFavorites();
        if (tab === 'promotion') await loadPromotions();
    }

    window.onload = async () => {
        await displayUserInfo();
        document.querySelectorAll('.sidebar-menu li[data-tab]').forEach(item => item.addEventListener('click', () => switchTab(item.dataset.tab)));
        document.querySelectorAll('.info-card[data-tab]').forEach(card => card.addEventListener('click', () => switchTab(card.dataset.tab)));
        document.getElementById('dashboardLogoutCard')?.addEventListener('click', logoutAccount);
        document.getElementById('logoutSidebar')?.addEventListener('click', logoutAccount);
        document.getElementById('cancelAddressBtn')?.addEventListener('click', hideAddressForm);
        document.getElementById('saveProfile')?.addEventListener('click', updateProfile);
        await switchTab('dashboard');
    };
    
    // ==================== FIX CẤU TRÚC MENU ====================
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

    // ==================== MOBILE MENU ====================
    (function () {
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const navList = document.getElementById('navList');

        function isMobile() {
            return window.innerWidth <= 992;
        }

        function closeAllDropdowns() {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show-mobile-dropdown');
            });
            document.querySelectorAll('.mobile-arrow').forEach(arrow => {
                arrow.classList.remove('active');
            });
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

        document.addEventListener('click', function (e) {
            if (!isMobile()) return;
            const arrow = e.target.closest('.mobile-arrow');
            if (!arrow) return;
            e.preventDefault();
            e.stopPropagation();
            const parent = arrow.closest('.has-dropdown');
            toggleDropdown(parent);
        });

        if (mobileToggle && navList) {
            mobileToggle.addEventListener('click', function (e) {
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

        document.addEventListener('click', function (e) {
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

        window.addEventListener('resize', function () {
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

    // ==================== FLOATING PROMO ====================
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

    // ==================== MODAL ƯU ĐÃI ====================
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
</script>
</body>
</html>