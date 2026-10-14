<?php
require_once '../api/config.php';

// Kiểm tra đăng nhập admin
if (!isAdminLoggedIn()) {
    header('Location: login.html');
    exit;
}

// Hàm tạo slug từ chuỗi
function createSlug($str) {
    $str = trim(mb_strtolower($str, 'UTF-8'));
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    $str = trim($str, '-');
    return $str;
}

// Xử lý thêm / sửa
$action = isset($_GET['action']) ? $_GET['action'] : '';
$editId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $status = isset($_POST['status']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Xử lý upload ảnh
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/category/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_' . uniqid() . '.' . $ext;
        $destination = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            $imagePath = 'uploads/category/' . $fileName;
        } else {
            $message = 'Lỗi upload ảnh.';
        }
    } elseif (!empty($_POST['old_image']) && $action === 'edit') {
        $imagePath = $_POST['old_image'];
    }
    
    if (empty($name)) {
        $message = 'Tên nhà sản xuất không được để trống.';
    } else {
        $slug = createSlug($name);
        
        // Xây dựng description với is_featured
        $finalDescription = $description . "\n[FEATURED:" . $is_featured . "]";
        
        if ($action === 'add') {
            // Kiểm tra slug đã tồn tại
            $check = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND is_producer = 1");
            $check->bind_param("s", $slug);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $slug .= '-' . time();
            }
            $check->close();
            
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, image, status, is_producer, level) VALUES (?, ?, ?, ?, ?, 1, 0)");
            $stmt->bind_param("ssssi", $name, $slug, $finalDescription, $imagePath, $status);
            if ($stmt->execute()) {
                $message = 'Thêm nhà sản xuất thành công.';
                // Reset form
                $name = $description = '';
                $imagePath = '';
                $is_featured = 0;
                // Redirect để reset form
                header('Location: producers.php?msg=' . urlencode($message));
                exit;
            } else {
                $message = 'Lỗi: ' . $stmt->error;
            }
            $stmt->close();
        } elseif ($action === 'edit' && $editId > 0) {
            // Kiểm tra slug đã tồn tại (trừ chính nó)
            $check = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND id != ? AND is_producer = 1");
            $check->bind_param("si", $slug, $editId);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $slug .= '-' . time();
            }
            $check->close();
            
            $stmt = $conn->prepare("UPDATE categories SET name=?, slug=?, description=?, image=?, status=? WHERE id=? AND is_producer=1");
            $stmt->bind_param("ssssii", $name, $slug, $finalDescription, $imagePath, $status, $editId);
            if ($stmt->execute()) {
                $message = 'Cập nhật thành công.';
                header('Location: producers.php?msg=' . urlencode($message));
                exit;
            } else {
                $message = 'Lỗi cập nhật: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Xử lý xoá
if ($action === 'delete' && $editId > 0) {
    $imgQuery = $conn->prepare("SELECT image FROM categories WHERE id = ? AND is_producer = 1");
    $imgQuery->bind_param("i", $editId);
    $imgQuery->execute();
    $imgResult = $imgQuery->get_result();
    if ($row = $imgResult->fetch_assoc()) {
        if (!empty($row['image']) && file_exists('../' . $row['image'])) {
            unlink('../' . $row['image']);
        }
    }
    $imgQuery->close();
    
    $del = $conn->prepare("DELETE FROM categories WHERE id = ? AND is_producer = 1");
    $del->bind_param("i", $editId);
    if ($del->execute()) {
        $message = 'Đã xoá nhà sản xuất.';
    } else {
        $message = 'Xoá thất bại.';
    }
    $del->close();
    header('Location: producers.php?msg=' . urlencode($message));
    exit;
}

// Lấy dữ liệu để sửa (nếu có)
$editData = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? AND is_producer = 1");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($editData) {
        $name = $editData['name'];
        $description = $editData['description'];
        $status = $editData['status'];
        $imagePath = $editData['image'];
        
        // Lấy is_featured từ description
        $is_featured = 0;
        if (preg_match('/\[FEATURED:(.*?)\]/', $description, $matches)) {
            $is_featured = (int)$matches[1];
            $description = trim(preg_replace('/\[FEATURED:.*?\]/', '', $description));
        }
    }
}

// Phân trang danh sách
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$totalRows = $conn->query("SELECT COUNT(*) as cnt FROM categories WHERE is_producer = 1")->fetch_assoc()['cnt'];
$totalPages = ceil($totalRows / $limit);

$sql = "SELECT * FROM categories WHERE is_producer = 1 ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
$producers = $result->fetch_all(MYSQLI_ASSOC);

// Hàm lấy is_featured từ description
function getFeaturedFromDesc($desc) {
    if (preg_match('/\[FEATURED:(.*?)\]/', $desc, $matches)) {
        return (int)$matches[1];
    }
    return 0;
}

function getCleanDescription($desc) {
    return trim(preg_replace('/\[FEATURED:.*?\]/', '', $desc));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Ruouchat Admin | Quản lý nhà sản xuất</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
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

        /* ===== PRODUCERS STYLES (co nhỏ) ===== */
        .content-area { padding: 0; }
        .content-area h2 {
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

        /* Tất cả nút border-radius: 10px */
        .btn, .btn-primary, .btn-success, .btn-warning,
        .btn-danger, .btn-secondary, .btn-sm, .btn-action {
            border-radius: 10px !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, #eab308, #ca8a04);
            border: none;
            padding: 6px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #ca8a04, #b45309);
        }
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            padding: 6px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            transition: all 0.2s;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #059669, #047857);
        }
        .btn-warning {
            background: #f59e0b;
            border: none;
            padding: 4px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            transition: all 0.2s;
        }
        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-2px);
            color: white;
        }
        .btn-danger {
            background: #ef4444;
            border: none;
            padding: 4px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            transition: all 0.2s;
        }
        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            color: white;
        }
        .btn-secondary {
            background: #64748b;
            border: none;
            padding: 6px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            transition: all 0.2s;
        }
        .btn-secondary:hover {
            background: #475569;
            transform: translateY(-2px);
            color: white;
        }

        .card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 6px 16px rgba(0,0,0,0.04);
            overflow: hidden;
            margin-bottom: 16px;
        }
        .card-header {
            background: #f8fafc;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 10px 16px;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.8rem;
        }
        .card-body {
            padding: 16px 20px;
        }
        .card-footer {
            background: #f8fafc;
            padding: 12px 16px;
            border-top: 1px solid #e2e8f0;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #eab308;
            box-shadow: 0 0 0 3px rgba(234,179,8,0.15);
        }
        .form-label {
            font-weight: 600;
            font-size: 0.75rem;
            color: #334155;
            margin-bottom: 2px;
        }
        .form-check-input:checked {
            background-color: #eab308;
            border-color: #eab308;
        }
        .form-check-label {
            font-size: 0.85rem;
        }

        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background: #f8fafc;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 10px 10px;
            color: #334155;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        .table td {
            vertical-align: middle;
            padding: 10px 10px;
            font-size: 0.78rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .table img {
            max-height: 45px;
            width: auto;
            border-radius: 10px;
        }

        .status-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-active {
            background: #dcfce7;
            color: #15803d;
        }
        .status-inactive {
            background: #fee2e2;
            color: #b91c1c;
        }
        .featured-badge {
            background: #fef3c7;
            color: #d97706;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.6rem;
            font-weight: 700;
            display: inline-block;
            border: 1px solid #f59e0b;
        }
        .producer-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.6rem;
            font-weight: 600;
            display: inline-block;
        }

        .alert {
            border-radius: 12px;
            border: none;
            font-size: 0.9rem;
            padding: 12px 16px;
        }

        .pagination .page-item .page-link {
            border-radius: 10px !important;
            margin: 0 2px;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            font-size: 0.8rem;
            padding: 4px 12px;
        }
        .pagination .page-item.active .page-link {
            background: #eab308;
            border-color: #eab308;
            color: #0f172a;
        }
        .pagination .page-item .page-link:hover {
            background: #f1f5f9;
            border-color: #eab308;
        }

        .badge {
            font-size: 0.7rem;
            padding: 3px 10px;
            border-radius: 20px;
        }

        @media (max-width: 768px) {
            .table td, .table th { padding: 6px 6px; font-size: 0.7rem; }
            .content-area h2 { font-size: 1.2rem; }
            .card-body { padding: 12px 14px; }
            .table img { max-height: 35px; }
            .btn { font-size: 0.75rem; padding: 4px 10px; }
            .btn-primary, .btn-success, .btn-secondary { padding: 4px 12px; font-size: 0.75rem; }
            .form-control, .form-select { font-size: 0.8rem; padding: 5px 10px; }
        }

        @media (max-width: 480px) {
            .table td, .table th { padding: 4px 4px; font-size: 0.6rem; }
            .table img { max-height: 28px; }
            .btn { font-size: 0.65rem; padding: 3px 8px; }
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
            <a href="categories.html"><i class="fas fa-folder"></i> Danh mục</a>
            <a href="orders.html"><i class="fas fa-shopping-cart"></i> Đơn hàng</a>
            <a href="customers.html"><i class="fas fa-users"></i> Khách hàng</a>
            <a href="contacts.html"><i class="fas fa-envelope"></i> Liên hệ</a>
            <a href="posts.html"><i class="fas fa-newspaper"></i> Bài viết</a>
            <a href="gift_products.html"><i class="fas fa-gift"></i> Quà tặng</a>
            <a href="glasses.html"><i class="fas fa-glass-martini"></i> Ly pha lê</a>
            <a href="other_products.html"><i class="fas fa-box"></i> Sản phẩm khác</a>
            <a href="promotions.html"><i class="fas fa-bullhorn"></i> Khuyến mãi</a>
            <a href="producers.php" class="active"><i class="fas fa-building"></i> Nhà sản xuất</a>
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
        <div class="content-area">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h2>Quản lý nhà sản xuất</h2>
                <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Thêm mới</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_GET['msg']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($action === 'add' || ($action === 'edit' && $editData)): ?>
                <div class="card mb-3">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <strong><i class="fas fa-<?= $action === 'add' ? 'plus-circle' : 'edit' ?> me-2"></i><?= $action === 'add' ? 'THÊM NHÀ SẢN XUẤT' : 'CẬP NHẬT NHÀ SẢN XUẤT' ?></strong>
                        <span class="producer-badge"><i class="fas fa-building me-1"></i>Nhà sản xuất</span>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <div class="mb-2">
                                        <label class="form-label">Tên nhà sản xuất <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($name ?? '') ?>">
                                        <small class="text-muted">Danh mục sẽ được tạo trong bảng categories với is_producer = 1</small>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Mô tả</label>
                                        <textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($description ?? '') ?></textarea>
                                    </div>
                                    <div class="mb-2">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" name="status" class="form-check-input" id="status" <?= (isset($status) && $status == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="status">Hiển thị (kích hoạt)</label>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" name="is_featured" class="form-check-input" id="is_featured" <?= (isset($is_featured) && $is_featured == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="is_featured">
                                                <span class="text-warning fw-bold"><i class="fas fa-star"></i> Nổi bật</span>
                                                <small class="text-muted d-block">(Hiển thị trong phần "CÁC THƯƠNG HIỆU NỔI BẬT TỪ PHÁP" trên trang chủ)</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Lưu ý:</strong> Nhà sản xuất được lưu trong bảng <code>categories</code> với cờ <code>is_producer = 1</code>. 
                                            Bạn có thể quản lý tất cả danh mục tại <a href="categories.html" class="alert-link">Categories</a>.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <label class="form-label">Ảnh đại diện</label>
                                        <input type="file" name="image" class="form-control" accept="image/*">
                                        <?php if (!empty($imagePath)): ?>
                                            <div class="mt-2">
                                                <img src="../<?= htmlspecialchars($imagePath) ?>" style="max-width: 100%; max-height: 120px; border-radius: 10px;" class="border p-1">
                                                <input type="hidden" name="old_image" value="<?= htmlspecialchars($imagePath) ?>">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i> Lưu lại</button>
                                <a href="producers.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i> Hủy bỏ</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-list me-2"></i> Danh sách nhà sản xuất</strong>
                    <span class="badge bg-warning text-dark">Từ bảng categories (is_producer=1)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th width="40">ID</th>
                                    <th width="60">Ảnh</th>
                                    <th>Tên</th>
                                    <th>Slug</th>
                                    <th width="90">Trạng thái</th>
                                    <th width="90">Nổi bật</th>
                                    <th width="140">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($producers) > 0): ?>
                                    <?php foreach ($producers as $item): ?>
                                    <?php $featured = getFeaturedFromDesc($item['description']); ?>
                                    <tr>
                                        <td><?= $item['id'] ?></td>
                                        <td>
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="../<?= htmlspecialchars($item['image']) ?>" style="height: 40px; width: auto; border-radius: 10px;">
                                            <?php else: ?>
                                                <i class="fas fa-building fa-2x text-muted"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-semibold"><?= htmlspecialchars($item['name']) ?></td>
                                        <td><code style="font-size:0.7rem;"><?= htmlspecialchars($item['slug']) ?></code></td>
                                        <td>
                                            <span class="status-badge <?= $item['status'] ? 'status-active' : 'status-inactive' ?>">
                                                <?= $item['status'] ? 'Hiển thị' : 'Ẩn' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($featured == 1): ?>
                                                <span class="featured-badge"><i class="fas fa-star"></i> Nổi bật</span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?action=edit&id=<?= $item['id'] ?>" class="btn btn-sm btn-warning me-1"><i class="fas fa-edit"></i></a>
                                            <a href="javascript:void(0)" onclick="confirmDelete(<?= $item['id'] ?>)" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-building fa-3x mb-3 opacity-50 d-block"></i>
                                            Chưa có nhà sản xuất nào. 
                                            <a href="?action=add" class="btn btn-primary btn-sm mt-2">Thêm nhà sản xuất</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sidebar.classList.contains('open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });
    }

    overlay.addEventListener('click', closeMobileMenu);

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
    });

    function confirmDelete(id) {
        if (confirm('Bạn có chắc chắn muốn xóa nhà sản xuất này? Hành động không thể khôi phục.')) {
            window.location.href = '?action=delete&id=' + id;
        }
    }

    document.getElementById('logoutBtn')?.addEventListener('click', async function(e) {
        e.preventDefault();
        if (confirm('Đăng xuất khỏi hệ thống?')) {
            try {
                await fetch('/api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' }),
                    credentials: 'include'
                });
            } catch(e) { console.warn(e); }
            window.location.href = 'login.html';
        }
    });
</script>
</body>
</html>