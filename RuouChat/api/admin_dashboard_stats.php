<?php
/**
 * Admin Dashboard Stats API
 * Dữ liệu cho dashboard tổng quan
 * 
 * Yêu cầu: Header X-Admin-Secret: RuouchatAdmin2024!
 */

// Chỉ gửi header JSON ngay từ đầu
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

// Include config (không được phép có bất kỳ output nào trước dòng này)
require_once __DIR__ . '/config.php';  // $conn, $pdo, ADMIN_SECRET đã được định nghĩa

// ==================== KIỂM TRA XÁC THỰC ====================


// ==================== HÀM KIỂM TRA BẢNG ====================
function tableExists($conn, $table) {
    if (!$conn) return false;
    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    return $result && $result->num_rows > 0;
}

// ==================== XỬ LÝ ACTION CHI TIẾT ====================
$action = $_GET['detail'] ?? '';

if ($action) {
    switch ($action) {
        case 'revenue':
            $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
            $to = $_GET['to'] ?? date('Y-m-d');
            $sort = $_GET['sort'] ?? 'date_desc';
            $order = ($sort === 'date_asc') ? 'ASC' : 'DESC';
            if (!tableExists($conn, 'orders')) {
                echo json_encode([]);
                exit;
            }
            $sql = "SELECT DATE(created_at) as date, SUM(total_amount) as value 
                    FROM orders 
                    WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ? 
                    GROUP BY DATE(created_at) 
                    ORDER BY date $order";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode([]);
                exit;
            }
            $stmt->bind_param('ss', $from, $to);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            foreach ($data as &$row) {
                $row['value'] = (float)($row['value'] ?? 0);
            }
            echo json_encode($data);
            exit;
            
        case 'newcustomers':
            $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
            $to = $_GET['to'] ?? date('Y-m-d');
            $sort = $_GET['sort'] ?? 'date_desc';
            $order = ($sort === 'date_asc') ? 'ASC' : 'DESC';
            if (!tableExists($conn, 'users')) {
                echo json_encode([]);
                exit;
            }
            $sql = "SELECT DATE(created_at) as date, COUNT(id) as value 
                    FROM users 
                    WHERE role = 'user' AND DATE(created_at) BETWEEN ? AND ? 
                    GROUP BY DATE(created_at) 
                    ORDER BY date $order";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode([]);
                exit;
            }
            $stmt->bind_param('ss', $from, $to);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            foreach ($data as &$row) {
                $row['value'] = (int)($row['value'] ?? 0);
            }
            echo json_encode($data);
            exit;
            
        case 'products':
            $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 10;
            if (!tableExists($conn, 'order_items') || !tableExists($conn, 'products') || !tableExists($conn, 'orders')) {
                echo json_encode([]);
                exit;
            }
            $sql = "SELECT p.name, SUM(oi.quantity) as sold, SUM(oi.price * oi.quantity) as revenue 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE o.status = 'completed' 
                    GROUP BY oi.product_id 
                    ORDER BY sold DESC 
                    LIMIT ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode([]);
                exit;
            }
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            foreach ($data as &$row) {
                $row['sold'] = (int)($row['sold'] ?? 0);
                $row['revenue'] = (float)($row['revenue'] ?? 0);
            }
            echo json_encode($data);
            exit;
            
        case 'posts':
            $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 10;
            if (!tableExists($conn, 'posts')) {
                echo json_encode([]);
                exit;
            }
            // Kiểm tra cột views
            $hasViews = false;
            $colCheck = $conn->query("SHOW COLUMNS FROM posts LIKE 'views'");
            if ($colCheck && $colCheck->num_rows) $hasViews = true;
            if ($hasViews) {
                $sql = "SELECT id, title, views, created_at as date FROM posts ORDER BY views DESC LIMIT ?";
            } else {
                $sql = "SELECT id, title, 0 as views, created_at as date FROM posts ORDER BY created_at DESC LIMIT ?";
            }
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode([]);
                exit;
            }
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            foreach ($data as &$row) {
                $row['views'] = (int)($row['views'] ?? 0);
                $row['date'] = $row['date'] ?? '';
            }
            echo json_encode($data);
            exit;
            
        default:
            echo json_encode([]);
            exit;
    }
}

// ==================== DASHBOARD TỔNG HỢP ====================
$totalRevenue = 0;
if (tableExists($conn, 'orders')) {
    $res = $conn->query("SELECT COALESCE(SUM(total_amount),0) as total FROM orders WHERE status='completed'");
    if ($res) $totalRevenue = (float)($res->fetch_assoc()['total'] ?? 0);
}

$newCustomers30d = 0;
if (tableExists($conn, 'users')) {
    $res = $conn->query("SELECT COUNT(id) as cnt FROM users WHERE role='user' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    if ($res) $newCustomers30d = (int)($res->fetch_assoc()['cnt'] ?? 0);
}

$totalVisits = 0;
if (tableExists($conn, 'user_sessions')) {
    $res = $conn->query("SELECT COUNT(DISTINCT session_id) as visits FROM user_sessions WHERE date >= CURDATE() - INTERVAL 30 DAY");
    if ($res) $totalVisits = (int)($res->fetch_assoc()['visits'] ?? 0);
}

$totalUsers = 0;
if (tableExists($conn, 'users')) {
    $res = $conn->query("SELECT COUNT(id) as cnt FROM users WHERE role='user'");
    if ($res) $totalUsers = (int)($res->fetch_assoc()['cnt'] ?? 0);
}

$completedOrders = 0;
if (tableExists($conn, 'orders')) {
    $res = $conn->query("SELECT COUNT(id) as cnt FROM orders WHERE status='completed'");
    if ($res) $completedOrders = (int)($res->fetch_assoc()['cnt'] ?? 0);
}

$conversionRate = ($totalUsers > 0) ? $completedOrders / $totalUsers : 0;
$bounceRate = ($totalUsers > 0) ? (($totalUsers - $completedOrders) / $totalUsers) : 0;

// Biểu đồ doanh thu 6 tháng
$revenueChart = [];
if (tableExists($conn, 'orders')) {
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $year = (int)date('Y', strtotime($month));
        $monthNum = (int)date('m', strtotime($month));
        $sql = "SELECT COALESCE(SUM(total_amount),0) as total FROM orders WHERE status='completed' AND YEAR(created_at)=$year AND MONTH(created_at)=$monthNum";
        $res = $conn->query($sql);
        $total = $res ? (float)($res->fetch_assoc()['total'] ?? 0) : 0;
        $revenueChart[] = ['label' => "Tháng $monthNum", 'value' => $total];
    }
} else {
    for ($i = 5; $i >= 0; $i--) $revenueChart[] = ['label' => "Tháng " . date('m', strtotime("-$i months")), 'value' => 0];
}

// Biểu đồ khách hàng mới 6 tháng
$customersChart = [];
if (tableExists($conn, 'users')) {
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $year = (int)date('Y', strtotime($month));
        $monthNum = (int)date('m', strtotime($month));
        $sql = "SELECT COUNT(id) as cnt FROM users WHERE role='user' AND YEAR(created_at)=$year AND MONTH(created_at)=$monthNum";
        $res = $conn->query($sql);
        $cnt = $res ? (int)($res->fetch_assoc()['cnt'] ?? 0) : 0;
        $customersChart[] = ['label' => "Tháng $monthNum", 'value' => $cnt];
    }
} else {
    for ($i = 5; $i >= 0; $i--) $customersChart[] = ['label' => "Tháng " . date('m', strtotime("-$i months")), 'value' => 0];
}

// Top sản phẩm
$topProducts = [];
if (tableExists($conn, 'order_items') && tableExists($conn, 'products') && tableExists($conn, 'orders')) {
    $sql = "SELECT p.name, SUM(oi.quantity) as sold 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            JOIN orders o ON oi.order_id = o.id 
            WHERE o.status='completed' 
            GROUP BY oi.product_id 
            ORDER BY sold DESC LIMIT 5";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $topProducts[] = ['name' => $row['name'], 'sold' => (int)($row['sold'] ?? 0)];
        }
    }
}

// Top bài viết
$topPosts = [];
if (tableExists($conn, 'posts')) {
    $hasViews = false;
    $colCheck = $conn->query("SHOW COLUMNS FROM posts LIKE 'views'");
    if ($colCheck && $colCheck->num_rows) $hasViews = true;
    $sql = $hasViews 
        ? "SELECT title, views FROM posts ORDER BY views DESC LIMIT 5"
        : "SELECT title, 0 as views FROM posts ORDER BY created_at DESC LIMIT 5";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $topPosts[] = ['title' => $row['title'], 'views' => (int)($row['views'] ?? 0)];
        }
    }
}

// Trả về JSON
echo json_encode([
    'total_revenue' => $totalRevenue,
    'new_customers_30d' => $newCustomers30d,
    'total_visits' => $totalVisits,
    'conversion_rate' => round($conversionRate, 4),
    'bounce_rate' => round($bounceRate, 4),
    'revenue_chart' => $revenueChart,
    'new_customers_chart' => $customersChart,
    'top_products' => $topProducts,
    'top_posts' => $topPosts,
]);