<?php
require_once 'includes/menu.php'; 
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Đăng ký tài khoản - Rượu Chất</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="https://winevn.com/images/favicon.png" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            --error-color: #dc3545;
            --success-color: #198754;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            background: white;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
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

        /* ===== HEADER 2 TẦNG VỚI HIỆU ỨNG ẨN PHẦN TRÊN (GIỐNG index.php) ===== */
        .header {
            background: var(--light-bg);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            padding: 8px 0 6px 0;
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
            margin-top: 8px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            flex-wrap: wrap;
            transition: all 0.2s ease;
        }

        .logo { flex-shrink: 0; }
        .logo img { height: 42px; width: auto; display: block; max-width: 100%; }

        .header-search {
            flex: 1;
            max-width: 700px;
            min-width: 200px;
            position: relative;
        }
        .search-bar { width: 100%; position: relative; }
        .search-form {
            display: flex;
            width: 100%;
            background: white;
            border-radius: 60px;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }
        .search-form:focus-within {
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(153, 13, 35, 0.1);
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
            margin-top: 8px;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding-top: 4px;
            position: relative;
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
            color: var(--text-dark);
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
            color: var(--primary-red);
            background: rgba(153, 13, 35, 0.05);
        }
        .nav-link.highlight-text {
            color: var(--primary-red);
            font-weight: 700;
        }
        .nav-link.active {
            color: var(--primary-red) !important;
            background: rgba(153, 13, 35, 0.08);
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
            .mobile-arrow i {
                transition: transform 0.25s ease;
                font-size: 14px;
            }
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
            .dropdown-menu.show-mobile-dropdown {
                display: grid !important;
                animation: dropdownFade 0.25s ease;
            }
            @keyframes dropdownFade {
                from { opacity: 0; transform: translateY(-8px); }
                to   { opacity: 1; transform: translateY(0); }
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
            .header-bottom { margin-top: 6px !important; padding-top: 3px !important; }
            /* Ẩn icon home trên mobile */
            .desktop-only {
                display: none !important;
            }
        }
        @media (max-width: 680px) {
            .header-top { gap: 8px !important; padding: 4px 0; }
            .logo img { height: 34px !important; }
            .header-search { order: 3; width: 100%; max-width: 100%; margin-top: 6px !important; }
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
            .header-search { margin-top: 4px !important; }
            .header-right { gap: 8px !important; }
            .container { padding: 0 12px; }
        }

        /* ========== REGISTER SECTION ========== */
        .account-section {
            margin-top: 100px;
            padding: 60px 0;
            background: linear-gradient(135deg, #fff 0%, #fff 100%);
            min-height: calc(100vh - 77px - 80px);
            display: flex;
            align-items: center;
        }

        .account-container {
            width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .register-form {
            padding: 50px 40px;
            background: white;
        }

        .section-title {
            font-size: 28px;
            font-family: 'Merriweather', serif;
            font-weight: 700;
            color: var(--primary-red);
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 12px;
            text-align: center;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            height: 3px;
            background: var(--gold);
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .form-group label .required {
            color: var(--primary-red);
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(153, 13, 35, 0.1);
        }

        .form-group input.error-input {
            border-color: var(--error-color);
            background-color: #fff8f8;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
            z-index: 2;
        }

        .error-message {
            color: var(--error-color);
            font-size: 12.5px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
        }

        .error-message i {
            font-size: 12px;
        }

        .success-message {
            color: var(--success-color);
            font-size: 13px;
            margin-top: 8px;
            background: #e9f7ef;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
            color: white;
            border: none;
            padding: 14px;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(153, 13, 35, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .login-link a {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .password-strength {
            font-size: 12px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 4px;
            flex: 1;
            overflow: hidden;
        }

        .strength-fill {
            width: 0%;
            height: 100%;
            transition: width 0.2s;
        }

        @media (max-width: 768px) {
            .account-section {
                margin-top: 80px;
                padding: 40px 20px;
            }
            .register-form {
                padding: 35px 25px;
            }
            .section-title {
                font-size: 24px;
            }
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

        .footer-section p {
            margin-bottom: 10px;
            font-size: 14px;
            opacity: 0.9;
        }

        .footer-section a {
            display: block;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            margin-bottom: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .footer-section a:hover {
            color: white;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

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
            margin-bottom: 0;
        }

        .social-links a:hover {
            background: var(--primary-red);
        }

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

        @media (max-width: 992px) {
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 576px) {
            .footer-container {
                grid-template-columns: 1fr;
            }
        }
        
        /* Điều chỉnh margin-top cho account-section dựa trên header */
        body.has-header-fixed .account-section {
            margin-top: var(--header-height, 120px);
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

    <!-- ===== HEADER 2 TẦNG ===== -->
    <header class="header">
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <a href="index.php">
                        <img src="assets/images/logo.jpg" alt="Wine VN - Rượu Vang Nhập Khẩu">
                    </a>
                </div>
                <div class="header-search">
                    <div class="search-bar">
                        <form class="search-form" action="product_main.php" method="GET" id="searchForm">
                            <input type="text" name="search" id="searchInput" placeholder="Tìm kiếm rượu vang, whisky, quà tặng..." aria-label="Tìm kiếm" autocomplete="off">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                        <div class="search-suggestions" id="searchSuggestions"></div>
                    </div>
                </div>
                <div class="header-right">
                    <a href="lienhe.php" class="nav-icon-link"><i class="fa-solid fa-location-dot"></i></a>
                    <a href="account.php" class="nav-icon-link"><i class="fa-solid fa-heart"></i></a>
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

    <section class="account-section">
        <div class="account-container">
            <div class="register-form">
                <h2 class="section-title">ĐĂNG KÝ TÀI KHOẢN</h2>
                <form id="registerForm" action="#" method="post">
                    <div class="form-group" id="fullname-group">
                        <label>Họ và tên <span class="required">*</span></label>
                        <input type="text" name="fullname" id="fullname" placeholder="Nguyễn Văn A" autocomplete="name">
                    </div>
                    <div class="form-group" id="email-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" id="email" placeholder="example@email.com" autocomplete="email">
                    </div>
                    <div class="form-group" id="phone-group">
                        <label>Số điện thoại</label>
                        <input type="tel" name="phone" id="phone" placeholder="090 123 4567" autocomplete="tel">
                    </div>
                    <div class="form-group" id="password-group">
                        <label>Mật khẩu <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" name="reg_password" id="regPassword" placeholder="••••••••" autocomplete="new-password">
                            <i class="far fa-eye toggle-password" data-target="regPassword"></i>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <span>Độ mạnh:</span>
                            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                            <span id="strengthText" style="min-width: 65px;">Yếu</span>
                        </div>
                    </div>
                    <div class="form-group" id="confirm-group">
                        <label>Xác nhận mật khẩu <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="confirmPassword" placeholder="••••••••" autocomplete="off">
                            <i class="far fa-eye toggle-password" data-target="confirmPassword"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit" id="submitBtn">ĐĂNG KÝ <i class="fas fa-arrow-right"></i></button>
                    <div class="login-link"><b>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></b></div>
                </form>
            </div>
        </div>
    </section>

    <footer class="main-footer">
        <div class="footer-container container">
            <div class="footer-section"><h4><i class="fas fa-store"></i> Ruouchat.vn</h4><p>Địa chỉ: 113B Ngõ 25 Vũ Ngọc Phan, Phường Láng, Hà Nội</p><p>Điện thoại: <strong>0969 111 855</strong></p><p>Email: vangchat.vn@gmail.com</p><div class="social-links"><a href="#"><i class="fab fa-facebook-f"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-youtube"></i></a><a href="#"><i class="fab fa-tiktok"></i></a></div></div>
            <div class="footer-section"><h4><i class="fas fa-list"></i> Danh Mục Nổi Bật</h4><a href="product_main.php"><i class="fas fa-caret-right"></i> Rượu Vang Đỏ</a><a href="product_main.php"><i class="fas fa-caret-right"></i> Rượu Vang Pháp</a><a href="product_main.php"><i class="fas fa-caret-right"></i> Rượu Vang Ý</a><a href="product_main.php"><i class="fas fa-caret-right"></i> Rượu Vang Chile</a><a href="product_main.php"><i class="fas fa-caret-right"></i> Rượu Champagne</a><a href="product_main.php"><i class="fas fa-caret-right"></i> Rượu Whisky</a><a href="product_main.php"><i class="fas fa-caret-right"></i> Quà tặng rượu</a></div>
            <div class="footer-section"><h4><i class="fas fa-user-shield"></i> Chính Sách</h4><a href="chinh-sach.php?slug=chinh-sach-bao-mat"><i class="fas fa-caret-right"></i> Chính sách bảo mật</a><a href="chinh-sach.php?slug=chinh-sach-van-chuyen"><i class="fas fa-caret-right"></i> Chính sách vận chuyển</a><a href="chinh-sach.php?slug=chinh-sach-doi-tra"><i class="fas fa-caret-right"></i> Chính sách đổi trả</a><a href="chinh-sach.php?slug=dieu-khoan-su-dung"><i class="fas fa-caret-right"></i> Điều khoản sử dụng</a><a href="chinh-sach.php?slug=huong-dan-mua-hang"><i class="fas fa-caret-right"></i> Hướng dẫn mua hàng</a><a href="chinh-sach.php?slug=cau-hoi-thuong-gap"><i class="fas fa-caret-right"></i> Câu hỏi thường gặp</a></div>
            <div class="footer-section"><h4><i class="fas fa-envelope"></i> Đăng Ký Nhận Tin</h4><p>Nhận thông tin khuyến mãi và sản phẩm mới từ Ruouchat.vn</p><div class="newsletter"><input type="email" placeholder="Email của bạn"><button><i class="fas fa-paper-plane"></i></button></div><div class="payment-methods"><a href="#"><i class="fab fa-cc-visa"></i></a><a href="#"><i class="fab fa-cc-mastercard"></i></a><a href="#"><i class="fab fa-cc-paypal"></i></a><a href="#"><i class="fas fa-credit-card"></i></a><a href="#"><i class="fas fa-qrcode"></i></a></div></div>
        </div>
        <div class="footer-bottom"><p>© 2026 Ruouchat.vn - Chuyên Rượu Vang Nhập Khẩu Chính Hãng.</p></div>
    </footer>

    <script>
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

        // ===== HEADER HIDE/SHOW (GIỐNG index.php) =====
        let lastScroll = 0;
        const headerElem = document.querySelector('.header');
        const scrollThreshold = 100;
        
        function adjustAccountSectionMarginTop() {
            const accountSection = document.querySelector('.account-section');
            if (headerElem && accountSection) {
                let headerHeight = headerElem.offsetHeight;
                accountSection.style.marginTop = (headerHeight + 30) + 'px';
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
            adjustAccountSectionMarginTop();
        }, 100);
        
        window.addEventListener('load', adjustAccountSectionMarginTop);
        window.addEventListener('resize', adjustAccountSectionMarginTop);

        // ===== FIX MENU STRUCTURE =====
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

        // ===== MOBILE MENU =====
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

        // ===== SEARCH SUGGESTIONS =====
        const suggestionsList = ["wine", "1855", "rượu vang", "cửa hàng rượu vang", "nhà cung cấp rượu vang","rượu vang ý", "rượu vang pháp", "rượu vang mỹ", "San Marzano","vang Ý cao cấp", "vang Ý nhập khẩu", "Rượu vang Pháp", "Bordeaux","Grand Cru Classé", "vang Pháp chính hãng", "Rượu vang nhập khẩu","Rượu vang Pháp cao cấp", "Rượu vang Ý chính hãng", "Wine", "1855"];
        const uniqueSuggestions = [...new Map(suggestionsList.map(s => [s.toLowerCase(), s])).values()];
        const searchInput = document.getElementById('searchInput');
        const suggestionsBox = document.getElementById('searchSuggestions');
        const searchForm = document.getElementById('searchForm');
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;' }[m]));
        }
        
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

        // ===== CART COUNT =====
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

        // ===== USER DROPDOWN =====
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
        (async function() {
            const currentUser = await checkLoginStatus();
            updateUserMenu(currentUser);
        })();

        // ===== REGISTER FORM =====
        (function() {
            const form = document.getElementById('registerForm');
            const fullnameInput = document.getElementById('fullname');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const passwordInput = document.getElementById('regPassword');
            const confirmInput = document.getElementById('confirmPassword');
            const submitBtn = document.getElementById('submitBtn');

            // Helper hiển thị lỗi
            function showError(inputElement, message, groupId) {
                const group = document.getElementById(groupId);
                if (!group) return;
                const oldError = group.querySelector('.error-message');
                if (oldError) oldError.remove();
                const inp = group.querySelector('input');
                if (inp) inp.classList.add('error-input');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                const target = group.querySelector('.password-wrapper') || inp;
                target.parentNode.insertBefore(errorDiv, target.nextSibling);
            }

            function clearError(groupId) {
                const group = document.getElementById(groupId);
                if (!group) return;
                const errorMsg = group.querySelector('.error-message');
                if (errorMsg) errorMsg.remove();
                const inp = group.querySelector('input');
                if (inp) inp.classList.remove('error-input');
            }

            function clearAllErrors() {
                ['fullname-group', 'email-group', 'phone-group', 'password-group', 'confirm-group'].forEach(clearError);
            }

            // Validation cơ bản (phía client)
            function validateFullName() {
                const name = fullnameInput.value.trim();
                if (!name) {
                    showError(fullnameInput, 'Họ và tên không được để trống.', 'fullname-group');
                    return false;
                }
                if (name.length < 2) {
                    showError(fullnameInput, 'Họ và tên phải có ít nhất 2 ký tự.', 'fullname-group');
                    return false;
                }
                clearError('fullname-group');
                return true;
            }

            function validateEmail() {
                const email = emailInput.value.trim();
                if (!email) {
                    showError(emailInput, 'Email không được để trống.', 'email-group');
                    return false;
                }
                const emailRegex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    showError(emailInput, 'Email không đúng định dạng (ví dụ: ten@domain.com).', 'email-group');
                    return false;
                }
                clearError('email-group');
                return true;
            }

            function validatePhone() {
                const phone = phoneInput.value.trim();
                if (phone === "") return true;
                const phoneRegex = /^(0[3|5|7|8|9])[0-9]{8}$/;
                if (!phoneRegex.test(phone)) {
                    showError(phoneInput, 'Số điện thoại không hợp lệ (VD: 0912345678).', 'phone-group');
                    return false;
                }
                clearError('phone-group');
                return true;
            }

            function validatePassword() {
                const password = passwordInput.value;
                if (!password) {
                    showError(passwordInput, 'Mật khẩu không được để trống.', 'password-group');
                    return false;
                }
                if (password.length < 6) {
                    showError(passwordInput, 'Mật khẩu phải có ít nhất 6 ký tự.', 'password-group');
                    return false;
                }
                clearError('password-group');
                return true;
            }

            function validateConfirmPassword() {
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                if (!confirm) {
                    showError(confirmInput, 'Vui lòng xác nhận mật khẩu.', 'confirm-group');
                    return false;
                }
                if (password !== confirm) {
                    showError(confirmInput, 'Mật khẩu xác nhận không khớp.', 'confirm-group');
                    return false;
                }
                clearError('confirm-group');
                return true;
            }

            // Gửi request đăng ký
            async function registerUser(fullname, email, phone, password) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'ĐANG XỬ LÝ... <i class="fas fa-spinner fa-pulse"></i>';

                try {
                    const response = await fetch('api/auth.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'register',
                            username: email,
                            email: email,
                            password: password,
                            fullname: fullname,
                            phone: phone
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        const successDiv = document.createElement('div');
                        successDiv.className = 'success-message';
                        successDiv.innerHTML = '<i class="fas fa-check-circle"></i> Đăng ký thành công! Chuyển hướng đến trang đăng nhập...';
                        const formContainer = document.querySelector('.register-form');
                        const oldMsg = formContainer.querySelector('.success-message');
                        if (oldMsg) oldMsg.remove();
                        formContainer.insertBefore(successDiv, form);
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 1500);
                    } else {
                        const errorMsg = data.error || 'Đăng ký thất bại, vui lòng thử lại.';
                        showError(emailInput, errorMsg, 'email-group');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'ĐĂNG KÝ <i class="fas fa-arrow-right"></i>';
                    }
                } catch (error) {
                    console.error(error);
                    showError(emailInput, 'Lỗi kết nối server. Vui lòng thử lại sau.', 'email-group');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'ĐĂNG KÝ <i class="fas fa-arrow-right"></i>';
                }
            }

            // Submit form
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                clearAllErrors();

                const isFullnameValid = validateFullName();
                const isEmailValid = validateEmail();
                const isPhoneValid = validatePhone();
                const isPassValid = validatePassword();
                const isConfirmValid = validateConfirmPassword();

                if (isFullnameValid && isEmailValid && isPhoneValid && isPassValid && isConfirmValid) {
                    registerUser(
                        fullnameInput.value.trim(),
                        emailInput.value.trim(),
                        phoneInput.value.trim(),
                        passwordInput.value
                    );
                } else {
                    const firstError = document.querySelector('.error-message');
                    if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            // Real-time validation events
            fullnameInput.addEventListener('blur', validateFullName);
            emailInput.addEventListener('blur', validateEmail);
            phoneInput.addEventListener('blur', validatePhone);
            passwordInput.addEventListener('input', function() {
                validatePassword();
                if (confirmInput.value.length > 0) validateConfirmPassword();
                
                // Password strength
                const strength = checkStrength(passwordInput.value);
                const fill = document.getElementById('strengthFill');
                const text = document.getElementById('strengthText');
                if (strength.score === 0) {
                    fill.style.width = '20%';
                    fill.style.background = '#dc3545';
                    text.textContent = 'Yếu';
                    text.style.color = '#dc3545';
                } else if (strength.score === 1) {
                    fill.style.width = '40%';
                    fill.style.background = '#ffc107';
                    text.textContent = 'Trung bình';
                    text.style.color = '#ffc107';
                } else if (strength.score === 2) {
                    fill.style.width = '70%';
                    fill.style.background = '#17a2b8';
                    text.textContent = 'Mạnh';
                    text.style.color = '#17a2b8';
                } else {
                    fill.style.width = '100%';
                    fill.style.background = '#198754';
                    text.textContent = 'Rất mạnh';
                    text.style.color = '#198754';
                }
            });
            confirmInput.addEventListener('input', validateConfirmPassword);

            // Toggle password visibility
            document.querySelectorAll('.toggle-password').forEach(icon => {
                icon.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    if (input.type === 'password') {
                        input.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    }
                });
            });

            // Password strength checker
            function checkStrength(password) {
                let score = 0;
                if (password.length >= 8) score++;
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) score++;
                if (password.match(/\d/)) score++;
                if (password.match(/[^a-zA-Z\d]/)) score++;
                return { score: score };
            }
        })();
    </script>
</body>
</html>