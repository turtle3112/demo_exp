<?php
/**
 * api/admin_reports.php
 * API lấy dữ liệu thống kê cho báo cáo admin
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'config.php';

// Xác thực admin
$headers = getallheaders();
$secret = isset($headers['X-Admin-Secret']) ? $headers['X-Admin-Secret'] : '';
if ($secret !== 'RuouchatAdmin2024!') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Helper: lấy danh sách tháng trong năm
function getMonthsInYear($year) {
    $months = [];
    for ($i = 1; $i <= 12; $i++) {
        $months[] = ['month' => $i, 'name' => "Tháng $i"];
    }
    return $months;
}

// 1. Dashboard: tổng quan các chỉ số
if ($action === 'dashboard') {
    // Doanh thu (từ đơn completed)
    $revenueQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'completed'";
    $revenueResult = $conn->query($revenueQuery);
    $totalRevenue = $revenueResult->fetch_assoc()['total'];
    
    // Khách hàng mới (tháng này)
    $newCustomersQuery = "SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND role = 'user'";
    $newCustomersResult = $conn->query($newCustomersQuery);
    $newCustomers = $newCustomersResult->fetch_assoc()['total'];
    
    // Số người truy cập (giả sử có bảng analytics, nếu không thì dùng orders và users)
    $visitors = 0;
    $analyticsTable = $conn->query("SHOW TABLES LIKE 'analytics'")->num_rows > 0;
    if ($analyticsTable) {
        $visitorsQuery = "SELECT COUNT(DISTINCT session_id) as total FROM analytics WHERE DATE(visited_at) = CURDATE()";
        $visitorsResult = $conn->query($visitorsQuery);
        $visitors = $visitorsResult->fetch_assoc()['total'];
    } else {
        // Fallback: số người đặt hàng trong tháng
        $visitorsQuery = "SELECT COUNT(DISTINCT user_id) as total FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE())";
        $visitorsResult = $conn->query($visitorsQuery);
        $visitors = $visitorsResult->fetch_assoc()['total'];
    }
    
    // Tỉ lệ đặt hàng (số đơn hoàn thành / tổng số session)
    $totalSessions = 0;
    $completedOrders = 0;
    if ($analyticsTable) {
        $sessionQuery = "SELECT COUNT(DISTINCT session_id) as total FROM analytics WHERE DATE(visited_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $sessionRes = $conn->query($sessionQuery);
        $totalSessions = $sessionRes->fetch_assoc()['total'];
        $orderQuery = "SELECT COUNT(*) as total FROM orders WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $orderRes = $conn->query($orderQuery);
        $completedOrders = $orderRes->fetch_assoc()['total'];
    } else {
        $totalSessions = $visitors; // fallback
        $completedOrders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'];
    }
    $conversionRate = $totalSessions > 0 ? round(($completedOrders / $totalSessions) * 100, 2) : 0;
    
    // Tỉ lệ out (bỏ giỏ) – giả sử có bảng abandoned_carts, nếu không thì ước lượng
    $abandonRate = 0;
    
    // Sản phẩm được quan tâm (top 5 sản phẩm có nhiều view nhất – nếu có bảng product_views)
    // Fallback: top sản phẩm có nhiều đơn hàng
    $topProducts = [];
    $productQuery = "SELECT p.id, p.name, COUNT(o.id) as order_count 
                     FROM products p 
                     LEFT JOIN order_items oi ON p.id = oi.product_id 
                     LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
                     GROUP BY p.id ORDER BY order_count DESC LIMIT 5";
    $productRes = $conn->query($productQuery);
    while ($row = $productRes->fetch_assoc()) {
        $topProducts[] = $row;
    }
    
    // Bài viết được quan tâm (top 5 bài có nhiều view – nếu có bảng post_views)
    $topPosts = [];
    $postQuery = "SELECT id, title, view_count FROM posts ORDER BY view_count DESC LIMIT 5";
    $postRes = $conn->query($postQuery);
    while ($row = $postRes->fetch_assoc()) {
        $topPosts[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'total_revenue' => (float)$totalRevenue,
        'new_customers' => (int)$newCustomers,
        'visitors' => (int)$visitors,
        'conversion_rate' => (float)$conversionRate,
        'abandon_rate' => (float)$abandonRate,
        'top_products' => $topProducts,
        'top_posts' => $topPosts
    ]);
    exit;
}

// 2. Chi tiết doanh thu theo tháng / ngày
if ($action === 'revenue_detail') {
    $start = !empty($startDate) ? $startDate : date('Y-m-01');
    $end = !empty($endDate) ? $endDate : date('Y-m-t');
    
    // Biểu đồ theo tháng
    $monthlyData = [];
    for ($m = 1; $m <= 12; $m++) {
        $monthStart = date('Y-m-01', strtotime("$year-$m-01"));
        $monthEnd = date('Y-m-t', strtotime("$year-$m-01"));
        $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'completed' AND created_at BETWEEN ? AND ?");
        $stmt->bind_param("ss", $monthStart, $monthEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        $monthlyData[] = ['month' => $m, 'revenue' => (float)$result->fetch_assoc()['total']];
        $stmt->close();
    }
    
    // Lịch sử theo ngày trong khoảng
    $dailyHistory = [];
    $stmt = $conn->prepare("SELECT DATE(created_at) as date, COALESCE(SUM(total_amount), 0) as revenue, COUNT(*) as orders FROM orders WHERE status = 'completed' AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date DESC");
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $dailyHistory[] = $row;
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'monthly' => $monthlyData,
        'daily' => $dailyHistory
    ]);
    exit;
}

// 3. Chi tiết khách hàng mới theo tháng / ngày
if ($action === 'customers_detail') {
    $start = !empty($startDate) ? $startDate : date('Y-m-01');
    $end = !empty($endDate) ? $endDate : date('Y-m-t');
    
    $monthlyData = [];
    for ($m = 1; $m <= 12; $m++) {
        $monthStart = date('Y-m-01', strtotime("$year-$m-01"));
        $monthEnd = date('Y-m-t', strtotime("$year-$m-01"));
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND created_at BETWEEN ? AND ?");
        $stmt->bind_param("ss", $monthStart, $monthEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        $monthlyData[] = ['month' => $m, 'customers' => (int)$result->fetch_assoc()['total']];
        $stmt->close();
    }
    
    $dailyHistory = [];
    $stmt = $conn->prepare("SELECT DATE(created_at) as date, COUNT(*) as new_customers FROM users WHERE role = 'user' AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date DESC");
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $dailyHistory[] = $row;
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'monthly' => $monthlyData,
        'daily' => $dailyHistory
    ]);
    exit;
}

// 4. Chi tiết sản phẩm quan tâm
if ($action === 'products_detail') {
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'views_desc';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;
    
    $orderBy = "order_count DESC";
    switch ($sort) {
        case 'name_asc': $orderBy = "p.name ASC"; break;
        case 'name_desc': $orderBy = "p.name DESC"; break;
        case 'views_asc': $orderBy = "order_count ASC"; break;
        case 'views_desc': $orderBy = "order_count DESC"; break;
    }
    
    $where = "";
    if (!empty($search)) {
        $escaped = $conn->real_escape_string($search);
        $where = "WHERE p.name LIKE '%$escaped%'";
    }
    
    $sql = "SELECT p.id, p.name, p.price, COUNT(oi.id) as order_count 
            FROM products p 
            LEFT JOIN order_items oi ON p.id = oi.product_id 
            LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
            $where
            GROUP BY p.id 
            ORDER BY $orderBy 
            LIMIT $limit OFFSET $offset";
    $result = $conn->query($sql);
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    $countSql = "SELECT COUNT(DISTINCT p.id) as total FROM products p $where";
    $countResult = $conn->query($countSql);
    $total = $countResult->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit
    ]);
    exit;
}

// 5. Chi tiết bài viết quan tâm
if ($action === 'posts_detail') {
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'views_desc';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;
    
    $orderBy = "view_count DESC";
    switch ($sort) {
        case 'title_asc': $orderBy = "title ASC"; break;
        case 'title_desc': $orderBy = "title DESC"; break;
        case 'views_asc': $orderBy = "view_count ASC"; break;
        case 'views_desc': $orderBy = "view_count DESC"; break;
        case 'date_asc': $orderBy = "created_at ASC"; break;
        case 'date_desc': $orderBy = "created_at DESC"; break;
    }
    
    $where = "";
    if (!empty($search)) {
        $escaped = $conn->real_escape_string($search);
        $where = "WHERE title LIKE '%$escaped%'";
    }
    
    $sql = "SELECT id, title, view_count, created_at FROM posts $where ORDER BY $orderBy LIMIT $limit OFFSET $offset";
    $result = $conn->query($sql);
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    
    $countSql = "SELECT COUNT(*) as total FROM posts $where";
    $countResult = $conn->query($countSql);
    $total = $countResult->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'posts' => $posts,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
?>