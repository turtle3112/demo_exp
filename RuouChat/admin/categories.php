<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Ruouchat Admin | Quản lý danh mục</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; overflow-x: hidden; }

        /* ===== SIDEBAR HOÀN TOÀN GIỐNG products.html ===== */
        .sidebar {
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            box-shadow: 12px 0 28px -12px rgba(0,0,0,0.25);
            position: sticky;
            top: 0;
            z-index: 100;
            border-right: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar .p-3.text-white.text-center {
            font-size: 1.45rem;
            font-weight: 500;
            letter-spacing: 1px;
            background: linear-gradient(135deg, #FFFFFF 0%, #e2e8f0 100%);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
            padding: 1.3rem 0.75rem !important;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .sidebar .d-flex.flex-column { padding: 0 8px; }
        .sidebar a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 12px 18px;
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 14px;
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 20px;
            transition: all 0.25s;
            background: transparent;
        }
        .sidebar a i { width: 28px; font-size: 1.25rem; text-align: center; color: #94a3b8; transition: all 0.2s; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(6px); }
        .sidebar a:hover i { color: #facc15; transform: scale(1.08); }
        .sidebar a.active {
            background: linear-gradient(95deg, rgba(234,179,8,0.18), rgba(234,179,8,0.05));
            color: white;
            border-left: 3px solid #eab308;
        }
        .sidebar a.active i { color: #facc15; }
        
        #logoutBtn {
            margin-top: 28px;
            border-top: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            color: #facc15;
            font-weight: 600;
        }
        #logoutBtn i { color: #facc15; }
        #logoutBtn:hover { background: rgba(250,204,21,0.2); color: #fde047; transform: translateX(6px); }
        
        /* Responsive */
        @media (max-width: 992px) {
            .content { padding: 22px 24px; }
            .sidebar a { padding: 10px 14px; font-size: 0.88rem; gap: 10px; }
            .sidebar a i { width: 24px; font-size: 1.1rem; }
        }
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                position: relative;
                width: 100%;
                border-right: none;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                padding-bottom: 12px;
            }
            .sidebar .d-flex.flex-column {
                flex-direction: row !important;
                flex-wrap: wrap;
                justify-content: center;
                gap: 6px;
                padding: 0 12px;
            }
            .sidebar a {
                display: inline-flex;
                width: auto;
                margin: 4px 3px;
                padding: 10px 16px;
                border-radius: 40px;
                background: rgba(255,255,255,0.04);
                white-space: nowrap;
            }
            .sidebar a:hover { transform: translateY(-2px) translateX(0); }
            .sidebar .p-3.text-white.text-center {
                width: 100%;
                padding: 0.9rem 0.5rem !important;
                margin-bottom: 8px;
                font-size: 1.3rem;
            }
            #logoutBtn {
                margin-top: 0;
                border-top: none;
                background: rgba(250,204,21,0.12);
            }
            .content h2 { font-size: 1.7rem; margin-top: 8px; }
        }
        @media (max-width: 576px) {
            .sidebar {
                overflow-x: auto;
                white-space: nowrap;
                scrollbar-width: thin;
            }
            .sidebar .d-flex.flex-column {
                flex-wrap: nowrap;
                justify-content: flex-start;
                overflow-x: auto;
                padding-bottom: 8px;
            }
            .content { padding: 16px; }
            .table-custom { font-size: 0.8rem; }
            .btn-edit, .btn-delete { padding: 3px 10px; font-size: 0.7rem; }
        }

        .content { padding: 28px 32px; background: #f8fafc; min-height: 100vh; }
        .card { border-radius: 20px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .table th { background: #f1f5f9; font-weight: 600; }
        .btn-sm { border-radius: 30px; }

        /* Loading overlay */
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e293b;
            backdrop-filter: blur(4px);
        }
        .spinner-border { width: 3rem; height: 3rem; }
    </style>
</head>
<body>
<div id="loading-overlay">
    <div class="text-center">
        <div class="spinner-border text-warning mb-3" role="status"></div>
        <div>Kiểm tra quyền truy cập...</div>
    </div>
</div>
<div class="container-fluid" id="mainContent" style="display: none;">
    <div class="row">
        <div class="col-md-2 p-0 sidebar">
            <div class="p-3 text-white text-center">Ruouchat Admin</div>
            <div class="d-flex flex-column">
                <a href="index.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="products.html"><i class="fas fa-wine-bottle"></i> Sản phẩm</a>
                <a href="categories.html" class="active"><i class="fas fa-wine-bottle"></i> Danh mục</a>
                <a href="orders.html"><i class="fas fa-shopping-cart"></i> Đơn hàng</a>
                <a href="customers.html"><i class="fas fa-users"></i> Khách hàng</a>
                <a href="contacts.html"><i class="fas fa-envelope"></i> Liên hệ</a>
                <a href="posts.html"><i class="fas fa-newspaper"></i> Bài viết</a>
                <a href="gift_products.html"><i class="fas fa-gift"></i> Quà tặng</a>
                <a href="glasses.html"><i class="fas fa-glass-martini"></i> Ly pha lê</a>
                <a href="other_products.html"><i class="fas fa-user-shield"></i> Sản phẩm khác</a>
                <a href="promotions.html"><i class="fas fa-bullhorn"></i> Khuyến mãi</a>
                <a href="producers.php"><i class="fas fa-bullhorn"></i> Nhà sản xuất</a>
                <a href="policies.html"><i class="fas fa-file-contract"></i> Chính sách</a>
                <a href="index.php"><i class="fas fa-list"></i> Menu</a>
                <a href="pages.php"><i class="fas fa-list"></i> Trang mới</a>
                <a href="banners.html"><i class="fas fa-list"></i> Banner</a>
                <a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
        <div class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Quản lý danh mục</h2>
                <a href="category_form.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Thêm danh mục</a>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr><th>ID</th><th>Hình ảnh</th><th>Tên danh mục</th><th>Slug</th><th>Trạng thái</th><th>Thao tác</th></tr>
                            </thead>
                            <tbody id="categoryTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const ADMIN_SECRET = "RuouchatAdmin2024!";
    const API_BASE = '../api/';

    // ==================== KIỂM TRA ĐĂNG NHẬP ADMIN ====================
    async function checkAdminAndLoad() {
        try {
            const response = await fetch('/api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'check' })
            });
            const data = await response.json();
            
            if (!data.logged_in || data.user?.role !== 'admin') {
                window.location.href = 'login.html';
                return false;
            }
            document.getElementById('loading-overlay').style.display = 'none';
            document.getElementById('mainContent').style.display = 'block';
            loadCategories(); // gọi hàm load dữ liệu
            return true;
        } catch (error) {
            console.error('Lỗi kiểm tra đăng nhập:', error);
            window.location.href = 'login.html';
            return false;
        }
    }

    // Hàm đăng xuất
    async function logout() {
        try {
            await fetch('/api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' })
            });
        } catch(e) { console.warn(e); }
        window.location.href = 'login.html';
    }

    // Gắn sự kiện logout
    document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        logout();
    });

    // ==================== CÁC HÀM QUẢN LÝ DANH MỤC ====================
    async function fetchCategories() {
        const res = await fetch(API_BASE + 'categories.php', { headers: { 'X-Admin-Secret': ADMIN_SECRET } });
        const data = await res.json();
        return Array.isArray(data) ? data : [];
    }

    async function deleteCategory(id) {
        if (!confirm('Bạn có chắc muốn xóa danh mục này?')) return;
        const res = await fetch(API_BASE + `categories.php?id=${id}`, { method: 'DELETE', headers: { 'X-Admin-Secret': ADMIN_SECRET } });
        const result = await res.json();
        if (result.success) {
            alert('Xóa thành công');
            loadCategories();
        } else {
            alert('Xóa thất bại');
        }
    }

    function renderTable(categories) {
        const tbody = document.getElementById('categoryTable');
        if (!categories.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">Chưa có danh mục nào</td></tr>';
            return;
        }
        let html = '';
        categories.forEach(cat => {
            const imgHtml = cat.image ? `<img src="${cat.image}" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">` : '<i class="fas fa-folder-open fa-2x text-muted"></i>';
            const statusBadge = cat.status == 1 ? '<span class="badge bg-success">Hiển thị</span>' : '<span class="badge bg-secondary">Ẩn</span>';
            html += `<tr>
                        <td>${cat.id}</td>
                        <td>${imgHtml}</td>
                        <td><strong>${escapeHtml(cat.name)}</strong></td>
                        <td>${escapeHtml(cat.slug)}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <a href="category_form.php?id=${cat.id}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Sửa</a>
                            <button class="btn btn-sm btn-danger" onclick="deleteCategory(${cat.id})"><i class="fas fa-trash"></i> Xóa</button>
                        </td>
                     </tr>`;
        });
        tbody.innerHTML = html;
    }

    function escapeHtml(str) { if(!str) return ''; return str.replace(/[&<>]/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;' }[m])); }

    async function loadCategories() {
        const cats = await fetchCategories();
        renderTable(cats);
    }

    // Bắt đầu kiểm tra admin
    checkAdminAndLoad();
</script>
</body>
</html>