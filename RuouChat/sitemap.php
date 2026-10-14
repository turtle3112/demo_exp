<?php
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

<?php
// ============================================================
// CẤU HÌNH DATABASE - THAY ĐỔI THEO THÔNG TIN CỦA BẠN
// ============================================================
$host = 'localhost';
$user = 'dcdtpglk_ruouchat';
$pass = 'Admin@0812';
$dbname = 'dcdtpglk_ruouchat';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Nếu lỗi, vẫn xuất sitemap cơ bản (không có dữ liệu từ DB)
    $pdo = null;
}

$baseUrl = 'https://ruouchat.vn';
$today = date('Y-m-d');

// ============================================================
// HÀM TẠO URL AN TOÀN
// ============================================================
function cleanUrl($url) {
    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}

// ============================================================
// HÀM XUẤT 1 URL TRONG SITEMAP
// ============================================================
function addUrl($loc, $lastmod, $changefreq, $priority, $images = []) {
    echo '<url>';
    echo '<loc>' . cleanUrl($loc) . '</loc>';
    echo '<lastmod>' . $lastmod . '</lastmod>';
    echo '<changefreq>' . $changefreq . '</changefreq>';
    echo '<priority>' . $priority . '</priority>';
    
    // Thêm hình ảnh cho Google Image Sitemap
    foreach ($images as $img) {
        echo '<image:image>';
        echo '<image:loc>' . cleanUrl($img) . '</image:loc>';
        echo '</image:image>';
    }
    
    echo '</url>';
}

// ============================================================
// 1. TRANG CHỦ
// ============================================================
addUrl($baseUrl . '/', $today, 'daily', '1.0');

// ============================================================
// 2. TRANG TĨNH (THEO .HTACCESS)
// ============================================================
$staticPages = [
    'gioi-thieu' => ['weekly', '0.8'],
    'lien-he' => ['monthly', '0.5'],
    'chinh-sach' => ['monthly', '0.5'],
    'quatangdoanhnghiep' => ['weekly', '0.7'],
    'gio-hang' => ['monthly', '0.3'],
    'thanh-toan' => ['monthly', '0.3'],
    'tai-khoan' => ['monthly', '0.3'],
    'dang-nhap' => ['monthly', '0.3'],
    'dang-ky' => ['monthly', '0.3'],
];

foreach ($staticPages as $slug => $params) {
    addUrl($baseUrl . '/' . $slug, $today, $params[0], $params[1]);
}

// ============================================================
// 3. DANH MỤC CẤP 1
// ============================================================
$mainCategories = [
    'ruou-vang' => ['weekly', '0.9'],
    'ruou-manh' => ['weekly', '0.9'],
    'bia-ngoai' => ['weekly', '0.8'],
    'ly-pha-le' => ['weekly', '0.8'],
    'san-pham-khac' => ['weekly', '0.7'],
    'kien-thuc' => ['weekly', '0.7'],
    'nha-san-xuat' => ['monthly', '0.6'],
];

foreach ($mainCategories as $slug => $params) {
    addUrl($baseUrl . '/' . $slug, $today, $params[0], $params[1]);
}

// ============================================================
// 4. DANH MỤC CON (LẤY TỪ BẢNG categories)
// ============================================================
if ($pdo) {
    $stmt = $pdo->query("
        SELECT slug 
        FROM categories 
        WHERE status = 1 
        AND slug NOT IN ('ruou-vang','ruou-manh','bia-ngoai','ly-pha-le','san-pham-khac','kien-thuc','nha-san-xuat')
        ORDER BY id
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        addUrl($baseUrl . '/' . $row['slug'], $today, 'weekly', '0.7');
    }
}

// ============================================================
// 5. SẢN PHẨM (LẤY TỪ BẢNG products)
// ============================================================
if ($pdo) {
    $stmt = $pdo->query("
        SELECT p.slug, pi.image_url
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        WHERE p.status = 1 AND p.display_status = 'visible'
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $images = [];
        if (!empty($row['image_url'])) {
            $images[] = $row['image_url'];
        }
        addUrl($baseUrl . '/' . $row['slug'], $today, 'monthly', '0.6', $images);
    }
}

// ============================================================
// 6. BÀI VIẾT (kien-thuc/ten-bai-viet)
// ============================================================
if ($pdo) {
    $stmt = $pdo->query("
        SELECT slug 
        FROM posts 
        WHERE status = 1
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        addUrl($baseUrl . '/kien-thuc/' . $row['slug'], $today, 'monthly', '0.5');
    }
}

// ============================================================
// 7. LY PHA LÊ (ly-pha-le/ten-san-pham)
// ============================================================
if ($pdo) {
    $stmt = $pdo->query("
        SELECT slug 
        FROM crystal_glasses 
        WHERE status = 1
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        addUrl($baseUrl . '/ly-pha-le/' . $row['slug'], $today, 'monthly', '0.5');
    }
}

// ============================================================
// 8. QUÀ TẶNG (qua-tang/ten-san-pham)
// ============================================================
if ($pdo) {
    $stmt = $pdo->query("
        SELECT slug 
        FROM gift_products 
        WHERE status = 1
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        addUrl($baseUrl . '/qua-tang/' . $row['slug'], $today, 'monthly', '0.5');
    }
}

// ============================================================
// 9. NHÀ SẢN XUẤT (nha-san-xuat/ten-nha-san-xuat)
// ============================================================
if ($pdo) {
    $stmt = $pdo->query("
        SELECT slug 
        FROM producers 
        WHERE status = 1
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        addUrl($baseUrl . '/nha-san-xuat/' . $row['slug'], $today, 'monthly', '0.5');
    }
}

// ============================================================
// 10. TRANG TĨNH TỪ BẢNG pages
// ============================================================
if ($pdo) {
    $stmt = $pdo->query("
        SELECT slug 
        FROM pages 
        WHERE status = 'published'
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        addUrl($baseUrl . '/' . $row['slug'], $today, 'monthly', '0.5');
    }
}
?>

</urlset>