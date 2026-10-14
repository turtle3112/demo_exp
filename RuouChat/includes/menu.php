<?php
/**
 * includes/menu.php
 * Quản lý menu động, xây dựng menu HTML cho header.
 * Hỗ trợ:
 * - Lấy menu từ bảng `menu_items`
 * - Fallback menu mặc định nếu không có DB
 * - Chuyển đổi link cũ thành link đẹp (SEO-friendly)
 * - Hỗ trợ gắn trực tiếp category_id để tạo link động đến product_main.php?cat_id=X
 * - Phân nhóm dropdown theo column_group, đánh dấu heading, highlight
 */

// Chỉ require config nếu chưa được gọi và file tồn tại
if (!isset($conn) && file_exists(__DIR__ . '/../api/config.php')) {
    require_once __DIR__ . '/../api/config.php';
}

// Nếu vẫn chưa có kết nối, tạo fallback (tránh lỗi fatal)
if (!isset($conn) || !$conn) {
    $menuItems = [];
} else {
    // Lấy tất cả menu items từ database, sắp xếp theo parent, sort_order
    $result = $conn->query("SELECT * FROM menu_items ORDER BY parent_id, sort_order, id");
    if ($result && $result->num_rows > 0) {
        $menuItems = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $menuItems = [];
    }
}

// Hàm chuyển đổi link cũ thành link đẹp (sử dụng base path /winecellar)
function convertOldLink($link) {
    global $base;
    
    // Xử lý các trường hợp cụ thể
    if (strpos($link, 'product_main.php?product_type=1') !== false) {
        return $base . '/ruou-vang';
    }
    if (strpos($link, 'product_main.php?product_type=2') !== false) {
        return $base . '/ruou-manh';
    }
    if ($link === 'product_main.php') {
        return $base . '/ruou-vang';
    }
    if ($link === 'lyphale.php') {
        return $base . '/ly-pha-le';
    }
    if (preg_match('/sp2\.php\?slug=([^&]+)/', $link, $matches)) {
        return $base . '/ly-pha-le/' . $matches[1];
    }
    if (preg_match('/sp1\.php\?slug=([^&]+)/', $link, $matches)) {
        return $base . '/ruou-vang/' . $matches[1];
    }
    if ($link === 'kienthuc.php') {
        return $base . '/kien-thuc';
    }
    if (preg_match('/kienthuc\.php\?slug=([^&]+)/', $link, $matches)) {
        return $base . '/kien-thuc/' . $matches[1];
    }
    if ($link === 'lienhe.php') {
        return $base . '/lien-he';
    }
    if ($link === 'login.php') {
        return $base . '/dang-nhap';
    }
    if ($link === 'register.php') {
        return $base . '/dang-ky';
    }
    if ($link === 'cart.php') {
        return $base . '/gio-hang';
    }
    if ($link === 'account.php') {
        return $base . '/tai-khoan';
    }
    if ($link === 'checkout.php') {
        return $base . '/thanh-toan';
    }
    if ($link === 'sanphamkhac.php') {
        return $base . '/san-pham-khac';
    }
    if ($link === 'quatangdoanhnghiep.php') {
        return $base . '/quatangdoanhnghiep';
    }
    if ($link === 'chinh-sach.php') {
        return $base . '/chinh-sach';
    }
    if ($link === 'nhasanxuat.php') {
        return $base . '/nha-san-xuat';
    }
    // Mặc định: chuyển nếu là file .php không có tham số -> loại bỏ .php và thêm base
    if (preg_match('/^([a-zA-Z0-9_-]+)\.php$/', $link, $simple)) {
        return $base . '/' . $simple[1];
    }
    // Giữ nguyên nếu không khớp (có thể là link tuyệt đối ngoài)
    return $link;
}

// Nếu $menuItems không phải mảng hoặc rỗng, tạo menu mặc định (fallback)
if (!is_array($menuItems) || empty($menuItems)) {
    $menuItems = [
        ['id' => 1, 'parent_id' => 0, 'label' => 'Trang chủ', 'link' => 'index.php', 'sort_order' => 1, 'is_highlight' => 0, 'is_heading' => 0, 'column_group' => 1, 'category_id' => null],
        ['id' => 2, 'parent_id' => 0, 'label' => 'Rượu vang', 'link' => 'product_main.php?product_type=1', 'sort_order' => 2, 'is_highlight' => 1, 'is_heading' => 0, 'column_group' => 1, 'category_id' => null],
        ['id' => 3, 'parent_id' => 2, 'label' => 'Rượu vang Pháp', 'link' => 'ruou-vang?country=Pháp', 'sort_order' => 1, 'is_highlight' => 0, 'is_heading' => 0, 'column_group' => 1, 'category_id' => null],
        ['id' => 4, 'parent_id' => 2, 'label' => 'Rượu vang Ý', 'link' => 'ruou-vang?country=Ý', 'sort_order' => 2, 'is_highlight' => 0, 'is_heading' => 0, 'column_group' => 1, 'category_id' => null],
        ['id' => 5, 'parent_id' => 0, 'label' => 'Rượu mạnh', 'link' => 'product_main.php?product_type=2', 'sort_order' => 3, 'is_highlight' => 0, 'is_heading' => 0, 'column_group' => 1, 'category_id' => null],
        ['id' => 6, 'parent_id' => 5, 'label' => 'Whisky', 'link' => 'ruou-manh?spirit_type=Whisky', 'sort_order' => 1, 'is_highlight' => 0, 'is_heading' => 0, 'column_group' => 1, 'category_id' => null],
        ['id' => 7, 'parent_id' => 5, 'label' => 'Cognac', 'link' => 'ruou-manh?spirit_type=Cognac', 'sort_order' => 2, 'is_highlight' => 0, 'is_heading' => 0, 'column_group' => 1, 'category_id' => null],
        ['id' => 8, 'parent_id' => 0, 'label' => 'Ly pha lê', 'link' => 'lyphale.php', 'sort_order' => 4, 'is_highlight' => 0, 'is_heading' => 0, 'column_group' => 1, 'category_id' => null],
        ['id' => 9, 'parent_id' => 0, 'label' => 'Kiến thức', 'link' => 'kienthuc.php', 'sort_order' => 5, 'is_highlight' => 0, 'is_heading' => 0, 'column_group' => 1, 'category_id' => null],
        ['id' => 10, 'parent_id' => 0, 'label' => 'Nhà sản xuất', 'link' => 'nhasanxuat.php', 'sort_order' => 6, 'is_highlight' => 0, 'is_heading' => 0, 'column_group' => 1, 'category_id' => null],
        ['id' => 11, 'parent_id' => 0, 'label' => 'Liên hệ', 'link' => 'lienhe.php', 'sort_order' => 7, 'is_highlight' => 0, 'is_heading' => 0, 'column_group' => 1, 'category_id' => null],
    ];
}

// Chuyển đổi link cho từng item, đồng thời xử lý category_id (nếu có)
foreach ($menuItems as $key => &$item) {
    // Ưu tiên dùng category_id nếu có (>=1) để tạo link động
    if (!empty($item['category_id']) && $item['category_id'] > 0) {
        $item['link'] = 'product_main.php?cat_id=' . (int)$item['category_id'];
    } else {
        // Xử lý link cũ bình thường
        if (isset($item['link'])) {
            $item['link'] = convertOldLink($item['link']);
        }
    }
    
    // Đảm bảo các trường cần thiết tồn tại
    if (!isset($item['is_highlight'])) $item['is_highlight'] = 0;
    if (!isset($item['is_heading'])) $item['is_heading'] = 0;
    if (!isset($item['column_group'])) $item['column_group'] = 1;
    if (!isset($item['label'])) $item['label'] = 'Menu';
    if (!isset($item['category_id'])) $item['category_id'] = null;
}
unset($item);

/**
 * Hàm xây dựng menu HTML đệ quy
 * @param array $items Danh sách tất cả menu items (từ database hoặc fallback)
 * @param int $parent Id của parent hiện tại
 * @return string HTML của menu
 */
function buildMenuHTML($items, $parent = 0) {
    $html = '';
    foreach ($items as $item) {
        if ($item['parent_id'] == $parent) {
            // Kiểm tra có con không
            $hasChildren = false;
            foreach ($items as $sub) {
                if ($sub['parent_id'] == $item['id']) {
                    $hasChildren = true;
                    break;
                }
            }
            $liClass = $hasChildren ? 'class="nav-item has-dropdown"' : 'class="nav-item"';
            $linkClass = (!empty($item['is_highlight']) && $parent == 0) ? 'nav-link highlight-text' : 'nav-link';
            
            $html .= '<li ' . $liClass . '>';
            $html .= '<a href="' . htmlspecialchars($item['link']) . '" class="' . $linkClass . '">' . htmlspecialchars($item['label']);
            $html .= '</a>';
            
            if ($hasChildren) {
                // Lấy các con trực tiếp
                $children = array_filter($items, fn($c) => $c['parent_id'] == $item['id']);
                // Nhóm theo column_group
                $grouped = [];
                foreach ($children as $child) {
                    $group = !empty($child['column_group']) ? (int)$child['column_group'] : 1;
                    $grouped[$group][] = $child;
                }
                ksort($grouped);
                $html .= '<div class="dropdown-menu">';
                foreach ($grouped as $groupItems) {
                    $html .= '<div class="dropdown-column">';
                    foreach ($groupItems as $child) {
                        if (!empty($child['is_heading'])) {
                            $html .= '<h3>' . htmlspecialchars($child['label']) . '</h3>';
                        } else {
                            $highlightClass = !empty($child['is_highlight']) ? 'highlight' : '';
                            $html .= '<a href="' . htmlspecialchars($child['link']) . '" class="' . $highlightClass . '">' . htmlspecialchars($child['label']) . '</a>';
                        }
                    }
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
            $html .= '</li>';
        }
    }
    return $html;
}
?>