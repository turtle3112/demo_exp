<?php
require_once 'includes/menu.php'; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Ruouchat - Đại lý rượu vang & rượu mạnh nhập khẩu chính hãng</title>
    <meta name="description" content="Quà tặng doanh nghiệp cao cấp: hộp quà rượu vang, whisky, trà bánh. Dịch vụ in logo theo yêu cầu, chiết khấu tốt.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== RESET & GLOBAL ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary-red: #990D23;
            --dark-red: #670011;
            --deep-red: #55000D;
            --gold: #FFBD27;
            --light-bg: #FBF8EE;
            --cream: #F2EFE2;
            --border-color: #E8E8E8;
            --text-dark: #212529;
            --text-light: #6C757D;
        }
        body { font-family: 'Inter', sans-serif; color: var(--text-dark); background: white; line-height: 1.6; overflow-x: hidden; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; width: 100%; }

        /* ===== RESPONSIVE CONTAINER ===== */
        @media (max-width: 1200px) { .container { padding: 0 16px; } }
        @media (max-width: 768px) { .container { padding: 0 12px; } }
        @media (max-width: 480px) { .container { padding: 0 10px; } }

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
        
        /* ===== HEADER 2 TẦNG VỚI HIỆU ỨNG ẨN PHẦN TRÊN ===== */
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

        /* ===== HERO SLIDER STYLES ===== */
        .banner-wrapper {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 0;
        }
        .hero-slider-container {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            overflow: hidden;
            background: #f0f0f0;
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
            background: rgba(0,0,0,0.3);
            z-index: 1;
        }
        .hero-content {
            text-align: center;
            color: white;
            max-width: 800px;
            padding: 0 20px;
            z-index: 2;
            position: relative;
        }
        .hero-content h1 {
            font-size: clamp(28px, 5vw, 48px);
            font-family: 'Merriweather', serif;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        .hero-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--gold), #FF9D00);
            color: var(--text-dark);
            text-decoration: none;
            padding: 12px 32px;
            border-radius: 50px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .hero-btn:hover {
            transform: translateY(-3px);
            gap: 15px;
            background: var(--gold);
        }
        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 50%;
            font-size: 18px;
            color: var(--primary-red);
            cursor: pointer;
            z-index: 10;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .slider-btn:hover {
            background: var(--primary-red);
            color: white;
        }
        .btn-prev { left: 20px; }
        .btn-next { right: 20px; }
        .slider-dots {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 10;
        }
        .slider-dots .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .slider-dots .dot.active {
            width: 30px;
            border-radius: 20px;
            background: white;
        }
        @media (max-width: 768px) {
            .slider-btn { width: 40px; height: 40px; font-size: 14px; }
        }
        
        /* Phần giới thiệu trên desktop (lớp phủ) */
        @media (min-width: 993px) {
            .gift-hero {
                position: relative;
            }
            .gift-intro {
                position: absolute;
                bottom: 30px;
                left: 30px;
                right: 30px;
                background: rgba(0,0,0,0.7);
                backdrop-filter: blur(4px);
                border-radius: 24px;
                padding: 30px 40px;
                text-align: center;
                margin: 0;
                overflow: hidden;
                z-index: 3;
            }
            .gift-intro h2 {
                font-size: 2rem;
                margin-bottom: 16px;
                color: white;
                font-weight: 700;
                white-space: normal;
                word-break: break-word;
                overflow-wrap: break-word;
                line-height: 1.3;
            }
            .gift-intro p {
                font-size: 1rem;
                color: #f5f5f5;
                line-height: 1.5;
                white-space: normal;
                word-break: break-word;
                overflow-wrap: break-word;
                margin-bottom: 8px;
            }
            .gift-intro .highlight {
                color: var(--gold);
                font-weight: 600;
            }
        }
        
        /* FIX MOBILE: intro nằm dưới banner */
        @media (max-width: 992px) {
            .gift-hero {
                margin-top: 0 !important;
                background: transparent;
            }
            .gift-intro {
                position: relative;
                width: calc(100% - 30px);
                margin: 16px auto 0 auto !important;
                background: rgba(0, 0, 0, 0.85);
                backdrop-filter: blur(5px);
                border-radius: 24px;
                padding: 20px 16px !important;
                text-align: center;
                box-sizing: border-box;
                z-index: 3;
            }
            .gift-intro h2 {
                font-size: 1.5rem !important;
                font-weight: 700;
                color: white;
                margin-bottom: 12px;
                white-space: normal !important;
                word-break: break-word;
                overflow-wrap: break-word;
                line-height: 1.3;
            }
            .gift-intro p {
                font-size: 0.85rem !important;
                line-height: 1.5;
                white-space: normal !important;
                word-break: break-word;
                color: #f5f5f5;
                margin-bottom: 8px;
            }
            .gift-intro .highlight {
                color: var(--gold);
                font-weight: 600;
            }
        }

        @media (max-width: 480px) {
            .gift-intro {
                width: calc(100% - 20px);
                padding: 16px 12px !important;
                border-radius: 20px;
                margin-top: 12px auto !important;
            }
            .gift-intro h2 {
                font-size: 1.25rem !important;
            }
            .gift-intro p {
                font-size: 0.75rem !important;
            }
        }
        
        /* ===== GIFT CARDS - CẢI TIẾN HIỂN THỊ ===== */
        .gift-content {
            max-width: 1280px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .gift-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 40px;
        }

        @media (max-width: 992px) {
            .gift-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 576px) {
            .gift-grid {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
        }

        .gift-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid #f0f0f0;
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
        }
        .gift-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 35px rgba(139,0,0,0.12);
            border-color: var(--primary-red);
        }
        .gift-image {
            height: 220px;
            background: #f8f5f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .gift-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60%;
            background: linear-gradient(to top, rgba(0,0,0,0.3), transparent);
            pointer-events: none;
        }
        .gift-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }
        .gift-card:hover .gift-image img {
            transform: scale(1.05);
        }
        .gift-info {
            padding: 18px 16px 16px;
            text-align: center;
            background: white;
            position: relative;
        }
        .gift-info .gift-icon {
            display: inline-block;
            background: var(--primary-red);
            color: white;
            width: 32px;
            height: 32px;
            line-height: 32px;
            border-radius: 50%;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .gift-info h3 {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 42px;
        }
        .gift-info .gift-tag {
            display: inline-block;
            background: var(--gold);
            color: var(--text-dark);
            font-size: 11px;
            font-weight: 600;
            padding: 3px 12px;
            border-radius: 20px;
            margin-top: 6px;
            letter-spacing: 0.5px;
        }
        @media (max-width: 576px) {
            .gift-image {
                height: 150px;
            }
            .gift-info {
                padding: 12px 10px 10px;
            }
            .gift-info h3 {
                font-size: 13px;
                min-height: 34px;
                -webkit-line-clamp: 2;
            }
            .gift-info .gift-icon {
                width: 26px;
                height: 26px;
                line-height: 26px;
                font-size: 12px;
                margin-bottom: 4px;
            }
            .gift-info .gift-tag {
                font-size: 10px;
                padding: 2px 10px;
            }
        }
        
        /* ===== SẢN PHẨM QUÀ TẶNG - GIỐNG PRODUCT_MAIN.PHP ===== */
        .tet-products {
            max-width: 1280px;
            margin: 60px auto;
            padding: 0 20px;
        }
        .section-title {
            font-size: clamp(1.5rem, 5vw, 2rem);
            color: var(--primary-red);
            text-align: center;
            margin-bottom: 40px;
            font-weight: 700;
            position: relative;
            padding-bottom: 15px;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            max-width: 80%;
            height: 3px;
            background: var(--gold);
        }
        
        /* ===== PRODUCT CARD - ĐƠN GIẢN CHỈ ẢNH, TÊN, GIÁ, NÚT MUA ===== */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }

        @media (max-width: 1200px) {
            .product-grid { grid-template-columns: repeat(3, 1fr) !important; gap: 20px; }
        }
        @media (max-width: 992px) {
            .product-grid { grid-template-columns: repeat(3, 1fr) !important; gap: 18px; }
        }
        @media (max-width: 768px) {
            .product-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 14px; }
        }
        @media (max-width: 480px) {
            .product-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 12px; }
        }

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
        .product-link {
            text-decoration: none;
            color: inherit;
            display: block;
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
        .product-image-link img {
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
        .product-card:hover .product-image-link img { transform: scale(1.02); }
        
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
        
        .product-info {
            padding: 10px 12px 6px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .product-info h3 {
            font-size: 15px;
            margin-bottom: 6px;
            font-weight: 600;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 40px;
        }
        .product-info h3 a {
            text-decoration: none;
            color: inherit;
            transition: color 0.2s;
        }
        .product-info h3 a:hover { color: var(--primary-red); }
        
        .product-bottom-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px 12px 12px;
            border-top: 1px solid #eee;
            margin-top: auto;
        }
        .btn-buy-now {
            background: var(--primary-red);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            white-space: nowrap;
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
            font-size: 15px;
            font-weight: 700;
            color: var(--primary-red);
        }
        .price-contact {
            font-size: 15px;
            font-weight: 700;
            color: #d97706;
            background: #fef3c7;
            padding: 2px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        .price-contact i {
            margin-right: 4px;
        }
        .original-price-bottom {
            font-size: 12px;
            color: #6c757d;
            text-decoration: line-through;
            margin-bottom: 2px;
        }

        @media (max-width: 768px) {
            .product-info h3 { font-size: 13px; min-height: 34px; }
            .btn-buy-now { padding: 6px 12px; font-size: 12px; }
            .current-price-bottom { font-size: 14px; }
            .price-contact { font-size: 13px; padding: 2px 8px; }
        }
        @media (max-width: 480px) {
            .product-info { padding: 6px 8px; }
            .product-info h3 { font-size: 12px; min-height: 30px; }
            .product-bottom-row { padding: 8px 10px 10px 10px; }
            .btn-buy-now { padding: 5px 10px; font-size: 11px; }
            .current-price-bottom { font-size: 13px; }
            .price-contact { font-size: 12px; padding: 2px 6px; }
            .original-price-bottom { font-size: 10px; }
        }

        .load-more-wrapper { text-align: center; margin-top: 20px; }
        .load-more-btn { 
            background: white; 
            border: 2px solid var(--primary-red); 
            color: var(--primary-red); 
            padding: 12px 40px; 
            border-radius: 50px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            display: inline-flex; 
            align-items: center; 
            gap: 10px; 
        }
        .load-more-btn:hover { background: var(--primary-red); color: white; }
        
        .consultant-section { background: var(--light-bg); padding: 60px 0; margin-top: 40px; }
        .consultant-content { display: grid; grid-template-columns: 1fr; justify-items: center; }
        .consultant-form { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid var(--border-color); max-width: 600px; width: 100%; }
        .form-group { margin-bottom: 20px; }
        .form-group input, .form-group textarea { width: 100%; padding: 15px; border: 1px solid var(--border-color); border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; transition: border-color 0.3s; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--primary-red); }
        .submit-btn { background: var(--primary-red); color: white; border: none; padding: 15px 30px; border-radius: 50px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; transition: background 0.3s; }
        .submit-btn:hover { background: var(--dark-red); }
        
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

        /* ===== FLOATING PROMO - CHỈ HIỂN THỊ TRÊN PC, ẨN TRÊN MOBILE ===== */
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
            .floating-promo { display: none !important; }
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
        
        @media (max-width: 992px) {
            .main-nav { overflow-x: auto; white-space: nowrap; }
            .nav-list { flex-wrap: nowrap; justify-content: flex-start; overflow-x: auto; }
            .footer-container { grid-template-columns: repeat(2, 1fr); gap: 30px; }
            .header-top { gap: 10px; }
            .header-bottom { margin-top: 6px !important; padding-top: 3px !important; }
        }
        @media (max-width: 768px) {
            .gift-grid { gap: 20px; }
            .product-grid { gap: 20px; }
            .promo-actions { gap: 12px; margin: 30px 0 20px; padding: 0 8px; }
            .promo-item { padding: 12px 20px; font-size: 15px; max-width: 200px; flex: 1 1 45%; min-width: 140px; }
            .promo-item i { font-size: 18px; }
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
            .section-title { font-size: 1.4rem; }
            .promo-actions { gap: 10px; margin: 25px 0 15px; flex-direction: column; align-items: center; }
            .promo-item { width: 280px; max-width: 280px; padding: 14px 24px; font-size: 16px; }
            .promo-item i { font-size: 20px; }
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

    <header class="header show">
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <a href="index.php">
                        <img src="assets/images/logo.jpg" alt="Ruouchat - Rượu Vang Nhập Khẩu">
                    </a>
                </div>
                <!-- ===== SEARCH BAR - GIỐNG HỆT INDEX.PHP ===== -->
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

    <!-- BANNER SECTION (DYNAMIC SLIDER - RIÊNG CHO QUÀ TẶNG DOANH NGHIỆP) -->
    <div class="banner-wrapper">
        <div class="hero-slider-container">
            <div class="hero-slides-wrapper" id="heroSlidesWrapper"></div>
            <button class="slider-btn btn-prev" id="prevSlide"><i class="fas fa-chevron-left"></i></button>
            <button class="slider-btn btn-next" id="nextSlide"><i class="fas fa-chevron-right"></i></button>
            <div class="slider-dots" id="sliderDots"></div>
        </div>
    </div>

    <!-- PHẦN GIỚI THIỆU QUÀ TẶNG -->
    <div class="gift-hero">
        <div class="gift-intro" id="giftIntro">
            <h2>QUÀ TẶNG DOANH NGHIỆP - Ruouchat.vn</h2>
            <p>Ruouchat.vn cung cấp giải pháp <strong class="highlight">Quà Tặng Doanh Nghiệp, Quà Tặng Trung Thu, Quà Tặng Tết</strong> ý nghĩa cho đối tác, khách hàng, nhân viên.</p>
            <p>Dịch vụ in logo Doanh Nghiệp lên hộp quà theo yêu cầu. Chính sách chiết khấu tốt, miễn phí giao hàng nội thành và linh hoạt giao hàng trên toàn quốc.</p>
        </div>
    </div>

    <!-- CÁC GIFT CARD -->
    <div class="gift-content">
        <div class="gift-grid">
            <a href="sp1.php?slug=hop-qua-doanh-nghiep-1-chai&type=gift" class="gift-card">
                <div class="gift-image">
                    <img src="assets/images/4.jpg" alt="Hộp quà 1 chai" loading="lazy">
                </div>
                <div class="gift-info">
                    <h3>Hộp quà tặng doanh nghiệp 1 chai Rượu</h3>
                    <span class="gift-tag">Quà tặng cao cấp</span>
                </div>
            </a>
            <a href="sp1.php?slug=hop-qua-doanh-nghiep-2-chai&type=gift" class="gift-card">
                <div class="gift-image">
                    <img src="assets/images/4.jpg" alt="Hộp quà 2 chai" loading="lazy">
                </div>
                <div class="gift-info">
                    <h3>Hộp quà tặng doanh nghiệp 2 chai Rượu</h3>
                    <span class="gift-tag">Quà tặng doanh nghiệp</span>
                </div>
            </a>
            <a href="sp1.php?slug=hop-qua-tet-ruou-vang-tra-banh-hat&type=gift" class="gift-card">
                <div class="gift-image">
                    <img src="assets/images/4.jpg" alt="Hộp quà Tết" loading="lazy">
                </div>
                <div class="gift-info">
                    <h3>Hộp quà Tết Rượu vang - Trà - Bánh - Hạt</h3>
                    <span class="gift-tag">Quà Tết 2026</span>
                </div>
            </a>
            <a href="sp1.php?slug=hop-qua-tang-gia-re&type=gift" class="gift-card">
                <div class="gift-image">
                    <img src="assets/images/4.jpg" alt="Hộp quà giá rẻ" loading="lazy">
                </div>
                <div class="gift-info">
                    <h3>Hộp quà tặng giá rẻ</h3>
                    <span class="gift-tag">Giá tốt</span>
                </div>
            </a>
            <a href="sp1.php?slug=hop-qua-tang-khong-con&type=gift" class="gift-card">
                <div class="gift-image">
                    <img src="assets/images/4.jpg" alt="Hộp quà không cồn" loading="lazy">
                </div>
                <div class="gift-info">
                    <h3>Hộp quà tặng không cồn</h3>
                    <span class="gift-tag">Không cồn</span>
                </div>
            </a>
            <a href="sp1.php?slug=hop-qua-tang-whisky&type=gift" class="gift-card">
                <div class="gift-image">
                    <img src="assets/images/4.jpg" alt="Hộp quà whisky" loading="lazy">
                </div>
                <div class="gift-info">
                    <h3>Hộp quà tặng whisky</h3>
                    <span class="gift-tag">Whisky cao cấp</span>
                </div>
            </a>
        </div>
    </div>

    <!-- SẢN PHẨM QUÀ TẶNG TẾT PHÁT TÀI -->
    <div class="tet-products">
        <h2 class="section-title">HỘP QUÀ TẶNG TẾT PHÁT TÀI (KẾT HỢP 4 MÓN)</h2>
        <div class="product-grid" id="productGridPhatTai"></div>
        <div class="load-more-wrapper">
            <button class="load-more-btn" id="loadMorePhatTai">XEM THÊM <i class="fas fa-chevron-down"></i></button>
        </div>
    </div>
    
    <!-- SẢN PHẨM QUÀ TẶNG TẾT PHÁT LỘC -->
    <div class="tet-products">
        <h2 class="section-title">HỘP QUÀ TẶNG TẾT PHÁT LỘC (KẾT HỢP 5 MÔN)</h2>
        <div class="product-grid" id="productGridPhatLoc"></div>
        <div class="load-more-wrapper">
            <button class="load-more-btn" id="loadMorePhatLoc">XEM THÊM <i class="fas fa-chevron-down"></i></button>
        </div>
    </div>

    <!-- TƯ VẤN -->
    <div class="consultant-section">
        <div class="container">
            <h2 class="section-title">NHẬN TƯ VẤN QUÀ TẶNG DOANH NGHIỆP</h2>
            <div class="consultant-content">
                <div class="consultant-form">
                    <form id="consultantForm">
                        <div class="form-group">
                            <input type="text" placeholder="Họ và tên" required>
                        </div>
                        <div class="form-group">
                            <input type="email" placeholder="Email" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" placeholder="Số điện thoại">
                        </div>
                        <button type="submit" class="submit-btn">GỬI YÊU CẦU</button>
                    </form>
                </div>
            </div>
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

    <!-- FLOATING PROMO - CHỈ HIỂN THỊ TRÊN PC -->
    <div class="floating-promo" id="floatingPromo">
        <a href="lienhe.php" class="floating-item"><i class="fas fa-map-marker-alt"></i> Tìm cửa hàng</a>
        <a href="#" class="floating-item open-offer-modal"><i class="fas fa-gift"></i> Nhận ưu đãi</a>
        <a href="quatangdoanhnghiep.php" class="floating-item"><i class="fas fa-briefcase"></i> Quà doanh nghiệp</a>
    </div>

    <!-- MODAL -->
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

        // ==================== CẤU HÌNH ====================
        const API_BASE = '/api/';
        
        // ==================== HÀM TIỆN ÍCH ====================
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;' }[m]));
        }
        
        function formatPrice(price) {
            // Kiểm tra nếu price là null, undefined hoặc 0
            if (price === null || price === undefined || price === 0) {
                return '<span class="price-contact"><i class="fas fa-phone"></i> Liên hệ</span>';
            }
            return new Intl.NumberFormat('vi-VN').format(price) + ' ₫';
        }
        
        function createSlugFromName(name) {
            if (!name) return '';
            return name.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
        }

        function getImageUrl(imgPath) {
            if (!imgPath) return 'https://placehold.co/600x700?text=No+Image';
            if (imgPath.startsWith('http://') || imgPath.startsWith('https://') || imgPath.startsWith('/')) {
                return imgPath;
            }
            return 'uploads/' + imgPath;
        }
        
        // ==================== HERO SLIDER BANNER ====================
        let currentSlideIndex = 0;
        let totalSlides = 0;
        let slideInterval = null;

        async function loadHeroSlides() {
            try {
                const response = await fetch(API_BASE + 'banners.php?position=gift_hero&active=1&limit=10');
                const data = await response.json();
                
                console.log('Gift banner data:', data);
                
                if (data.success && data.banners && data.banners.length > 0) {
                    return data.banners.sort((a,b) => (a.sort_order || 0) - (b.sort_order || 0));
                }
                
                return [{
                    image_url: 'assets/images/2.jpg',
                    title: 'Quà tặng doanh nghiệp cao cấp',
                    link: '#'
                }];
            } catch(e) {
                console.error('Lỗi tải hero slides:', e);
                return [{
                    image_url: 'assets/images/2.jpg',
                    title: 'Quà tặng doanh nghiệp cao cấp',
                    link: '#'
                }];
            }
        }

        function renderHeroSlides(slidesData) {
            const wrapper = document.getElementById('heroSlidesWrapper');
            const dotsContainer = document.getElementById('sliderDots');
            if (!wrapper) return;
            
            if (!slidesData || slidesData.length === 0) {
                wrapper.innerHTML = `<div class="hero-slide" style="background-image: url('assets/images/2.jpg');"><div class="hero-content"><h1>Quà tặng doanh nghiệp cao cấp</h1></div></div>`;
                if(dotsContainer) dotsContainer.innerHTML = '';
                totalSlides = 1;
                attachSliderEvents();
                return;
            }
            
            let slidesHtml = '';
            slidesData.forEach(slide => {
                let titleHtml = slide.title ? `<h1>${escapeHtml(slide.title)}</h1>` : '';
                let buttonHtml = '';
                if (slide.link && slide.link !== '') {
                    buttonHtml = `<a href="${escapeHtml(slide.link)}" class="hero-btn">Khám phá ngay <i class="fas fa-arrow-right"></i></a>`;
                }
                slidesHtml += `
                    <div class="hero-slide" style="background-image: url('${escapeHtml(slide.image_url)}');">
                        <div class="hero-content">
                            ${titleHtml}
                            ${buttonHtml}
                        </div>
                    </div>
                `;
            });
            wrapper.innerHTML = slidesHtml;
            
            if (dotsContainer) {
                dotsContainer.innerHTML = '';
                for (let i = 0; i < slidesData.length; i++) {
                    const dot = document.createElement('button');
                    dot.classList.add('dot');
                    if (i === 0) dot.classList.add('active');
                    dot.setAttribute('data-index', i);
                    dot.addEventListener('click', () => goToSlide(i));
                    dotsContainer.appendChild(dot);
                }
            }
            
            totalSlides = slidesData.length;
            attachSliderEvents();
        }
        
        function goToSlide(index) {
            const wrapper = document.querySelector('.hero-slides-wrapper');
            if (!wrapper) return;
            if (index < 0) index = 0;
            if (index >= totalSlides) index = totalSlides - 1;
            currentSlideIndex = index;
            wrapper.style.transform = `translateX(-${index * 100}%)`;
            const dots = document.querySelectorAll('.slider-dots .dot');
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
        }
        
        function nextSlide() { 
            if(totalSlides > 0) goToSlide((currentSlideIndex + 1) % totalSlides); 
        }
        function prevSlide() { 
            if(totalSlides > 0) goToSlide((currentSlideIndex - 1 + totalSlides) % totalSlides); 
        }
        
        function startAutoPlay() {
            if (slideInterval) clearInterval(slideInterval);
            if (totalSlides > 1) {
                slideInterval = setInterval(nextSlide, 5000);
            }
        }
        
        function resetAutoPlay() {
            if (slideInterval) {
                clearInterval(slideInterval);
                startAutoPlay();
            }
        }
        
        function attachSliderEvents() {
            const prevBtn = document.getElementById('prevSlide');
            const nextBtn = document.getElementById('nextSlide');
            
            if (prevBtn) {
                const newPrevBtn = prevBtn.cloneNode(true);
                prevBtn.parentNode.replaceChild(newPrevBtn, prevBtn);
                newPrevBtn.onclick = () => { prevSlide(); resetAutoPlay(); };
            }
            if (nextBtn) {
                const newNextBtn = nextBtn.cloneNode(true);
                nextBtn.parentNode.replaceChild(newNextBtn, nextBtn);
                newNextBtn.onclick = () => { nextSlide(); resetAutoPlay(); };
            }
            
            const container = document.querySelector('.hero-slider-container');
            if (container) {
                container.removeEventListener('mouseenter', () => {});
                container.removeEventListener('mouseleave', startAutoPlay);
                container.addEventListener('mouseenter', () => { if (slideInterval) clearInterval(slideInterval); });
                container.addEventListener('mouseleave', startAutoPlay);
            }
            
            startAutoPlay();
        }
        
        async function initHeroSlider() {
            const slides = await loadHeroSlides();
            renderHeroSlides(slides);
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
            adjustHeaderOffset();
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
            adjustHeaderOffset();
        }, 100);
        
        // ========== ĐIỀU CHỈNH OFFSET CHO BANNER ==========
        function adjustHeaderOffset() {
            const headerElem = document.querySelector('.header');
            const bannerWrapper = document.querySelector('.banner-wrapper');
            if (headerElem && bannerWrapper) {
                let headerHeight = headerElem.offsetHeight;
                bannerWrapper.style.marginTop = headerHeight + 'px';
            }
        }
        window.addEventListener('load', adjustHeaderOffset);
        window.addEventListener('resize', adjustHeaderOffset);

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
        if (searchInput) {
            searchInput.addEventListener('focus', showSuggestions);
            searchInput.addEventListener('input', showSuggestions);
            document.addEventListener('click', function(e) { if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) { suggestionsBox.classList.remove('active'); } });
        }

        // ========== API GIỎ HÀNG ==========
        async function addToCart(productId, quantity = 1) {
            try {
                const response = await fetch('api/cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ action: 'add', product_id: productId, quantity: quantity })
                });
                return await response.json();
            } catch (error) { 
                console.error(error); 
                return { success: false, message: 'Có lỗi xảy ra' }; 
            }
        }
        async function fetchCartCount() {
            try {
                const response = await fetch('api/cart.php', { credentials: 'include' });
                const cart = await response.json();
                let total = 0;
                if (cart && typeof cart === 'object') total = Object.values(cart).reduce((sum, item) => sum + (item.quantity || 0), 0);
                return total;
            } catch (e) { return 0; }
        }
        async function updateCartCount() {
            const count = await fetchCartCount();
            const countSpan = document.querySelector('.cart-count');
            if (countSpan) countSpan.innerText = count;
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

        // ========== SẢN PHẨM QUÀ TẶNG - ĐƠN GIẢN HÓA ==========
        const GIFT_API_BASE = 'api/gift_products.php';
        let phatTaiPage = 1, phatLocPage = 1, loadingPhatTai = false, loadingPhatLoc = false, hasMorePhatTai = true, hasMorePhatLoc = true;
        const LIMIT = 8;

        function renderGiftProducts(containerId, products) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            products.forEach(p => {
                let slug = p.slug ? p.slug : createSlugFromName(p.name);
                if (!slug) slug = p.id;
                const detailLink = `sp1.php?slug=${encodeURIComponent(slug)}&type=gift`;
                
                let leftBadge = 'Quà tặng';
                let rightBadge = '';
                const stock = p.stock_quantity || 0;
                const displayStatus = p.display_status || 'visible';
                if (displayStatus === 'coming_soon') {
                    rightBadge = '<div class="product-badge-stock soon">Sắp về</div>';
                } else if (displayStatus === 'discontinued') {
                    rightBadge = '<div class="product-badge-stock discontinued">Ngừng bán</div>';
                } else if (stock <= 0) {
                    rightBadge = '<div class="product-badge-stock outstock">Hết hàng</div>';
                }
                
                const imageUrl = getImageUrl(p.main_image);
                const priceHtml = formatPrice(p.price);
                
                const card = document.createElement('div');
                card.className = 'product-card';
                card.innerHTML = `
                    <a href="${detailLink}" class="product-image-link">
                        <div class="product-badge">${escapeHtml(leftBadge)}</div>
                        ${rightBadge}
                        <img src="${imageUrl}" alt="${escapeHtml(p.name)}" loading="lazy" 
                             onerror="this.src='https://placehold.co/600x700?text=No+Image'">
                    </a>
                    <div class="product-info">
                        <h3><a href="${detailLink}">${escapeHtml(p.name)}</a></h3>
                    </div>
                    <div class="product-bottom-row">
                        <div class="product-bottom-price">${priceHtml}</div>
                        <button class="btn-buy-now" data-id="${p.id}"><i class="fas fa-shopping-cart"></i> Mua ngay</button>
                    </div>
                `;
                
                const btn = card.querySelector('.btn-buy-now');
                if (btn) {
                    btn.addEventListener('click', async (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const res = await addToCart(p.id, 1);
                        if (res && res.success) {
                            showToast('✅ Thành công!', `Đã thêm "${p.name}" vào giỏ hàng!`);
                            updateCartCount();
                        } else {
                            showToast('❌ Lỗi!', res.message || 'Lỗi khi thêm vào giỏ hàng');
                        }
                    });
                }
                
                container.appendChild(card);
            });
        }
        
        async function loadMoreGiftProducts(category, containerId, pageRef, loadingFlag, hasMoreFlag, loadMoreBtnId) {
            if (loadingFlag || !hasMoreFlag) return { pageRef, loadingFlag, hasMoreFlag };
            loadingFlag = true;
            const url = `${GIFT_API_BASE}?category=${category}&page=${pageRef}&limit=${LIMIT}`;
            try {
                const res = await fetch(url);
                const data = await res.json();
                if (data.products && data.products.length) {
                    renderGiftProducts(containerId, data.products);
                    pageRef++;
                    if (data.currentPage >= data.totalPages) {
                        hasMoreFlag = false;
                        const btn = document.getElementById(loadMoreBtnId);
                        if (btn) btn.style.display = 'none';
                    }
                } else {
                    hasMoreFlag = false;
                    const btn = document.getElementById(loadMoreBtnId);
                    if (btn) btn.style.display = 'none';
                }
            } catch (err) { console.error(err); }
            finally { loadingFlag = false; }
            return { pageRef, loadingFlag, hasMoreFlag };
        }

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
                    showToast('⚠️ Lỗi!', 'Vui lòng điền đầy đủ họ tên và số điện thoại.');
                    return;
                }
                if (formState) formState.style.display = 'none';
                if (successState) successState.style.display = 'block';
            });
        }
        if (closeSuccessBtn) closeSuccessBtn.addEventListener('click', closeModal);

        // ========== KHỞI CHẠY ==========
        document.addEventListener('DOMContentLoaded', async function() {
            await initHeroSlider();
            
            const currentUser = await checkLoginStatus();
            updateUserMenu(currentUser);
            
            await updateCartCount();
            await updateWishlistCount();
            
            // Load sản phẩm Phát Tài
            let result = await loadMoreGiftProducts('phat-tai', 'productGridPhatTai', phatTaiPage, loadingPhatTai, hasMorePhatTai, 'loadMorePhatTai');
            phatTaiPage = result.pageRef; loadingPhatTai = result.loadingFlag; hasMorePhatTai = result.hasMoreFlag;
            
            // Load sản phẩm Phát Lộc
            result = await loadMoreGiftProducts('phat-loc', 'productGridPhatLoc', phatLocPage, loadingPhatLoc, hasMorePhatLoc, 'loadMorePhatLoc');
            phatLocPage = result.pageRef; loadingPhatLoc = result.loadingFlag; hasMorePhatLoc = result.hasMoreFlag;
            
            // Sự kiện load thêm Phát Tài
            document.getElementById('loadMorePhatTai').addEventListener('click', async () => {
                let r = await loadMoreGiftProducts('phat-tai', 'productGridPhatTai', phatTaiPage, loadingPhatTai, hasMorePhatTai, 'loadMorePhatTai');
                phatTaiPage = r.pageRef; loadingPhatTai = r.loadingFlag; hasMorePhatTai = r.hasMoreFlag;
            });
            
            // Sự kiện load thêm Phát Lộc
            document.getElementById('loadMorePhatLoc').addEventListener('click', async () => {
                let r = await loadMoreGiftProducts('phat-loc', 'productGridPhatLoc', phatLocPage, loadingPhatLoc, hasMorePhatLoc, 'loadMorePhatLoc');
                phatLocPage = r.pageRef; loadingPhatLoc = r.loadingFlag; hasMorePhatLoc = r.hasMoreFlag;
            });
            
            // Form tư vấn
            const consultantForm = document.getElementById('consultantForm');
            if (consultantForm) consultantForm.addEventListener('submit', function(e) { 
                e.preventDefault(); 
                showToast('✅ Thành công!', 'Đã gửi thông tin thành công! Chúng tôi sẽ liên hệ sớm nhất.');
                this.reset(); 
            });
        });
    </script>
</body>
</html>