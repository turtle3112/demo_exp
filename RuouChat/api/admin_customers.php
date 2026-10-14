<?php
/**
 * api/admin_customers.php
 * API quản lý khách hàng (hỗ trợ tìm kiếm, lọc, sắp xếp, phân trang, lọc khách thân thiết)
 * Chỉ hiển thị tài khoản có role = 'user'
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'config.php';

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Xác thực admin secret


// Ngưỡng khách hàng thân thiết (VNĐ)
define('LOYALTY_THRESHOLD', 5000000);

// Lấy tham số đầu vào
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'total_spent_desc';
$filterDateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filterDateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filterMinTotal = isset($_GET['min_total']) ? floatval($_GET['min_total']) : 0;
$filterMaxTotal = isset($_GET['max_total']) ? floatval($_GET['max_total']) : 0;
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$loyaltyOnly = isset($_GET['loyalty_only']) && $_GET['loyalty_only'] == 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Xác định cột sắp xếp
$orderBy = 'total_spent DESC';
switch ($sort) {
    case 'name_asc': $orderBy = "u.fullname ASC"; break;
    case 'name_desc': $orderBy = "u.fullname DESC"; break;
    case 'email_asc': $orderBy = "u.email ASC"; break;
    case 'email_desc': $orderBy = "u.email DESC"; break;
    case 'total_spent_asc': $orderBy = "total_spent ASC"; break;
    case 'total_spent_desc': $orderBy = "total_spent DESC"; break;
    case 'order_count_asc': $orderBy = "order_count ASC"; break;
    case 'order_count_desc': $orderBy = "order_count DESC"; break;
    case 'registered_asc': $orderBy = "u.created_at ASC"; break;
    case 'registered_desc': $orderBy = "u.created_at DESC"; break;
}

// Lấy danh sách cột của bảng users để kiểm tra sự tồn tại
$userColumns = [];
$colRes = $conn->query("SHOW COLUMNS FROM users");
while ($col = $colRes->fetch_assoc()) {
    $userColumns[] = $col['Field'];
}

// Điều kiện role: chỉ lấy khách hàng (role = 'user')
$roleCondition = "";
if (in_array('role', $userColumns)) {
    $roleCondition = "u.role = 'user'";
}

// Kiểm tra bảng orders
$hasOrders = $conn->query("SHOW TABLES LIKE 'orders'")->num_rows > 0;
$orderColumns = [];
if ($hasOrders) {
    $colOrder = $conn->query("SHOW COLUMNS FROM orders");
    while ($col = $colOrder->fetch_assoc()) {
        $orderColumns[] = $col['Field'];
    }
}

// Xây dựng truy vấn thống kê đơn hàng (chỉ đơn completed)
$statsJoin = "";
if ($hasOrders && in_array('user_id', $orderColumns)) {
    $statusCondition = "";
    if (in_array('status', $orderColumns)) {
        $statusCondition = "AND o.status = 'completed'";
    }
    $statsJoin = "
        LEFT JOIN (
            SELECT 
                o.user_id,
                COUNT(o.id) AS order_count,
                COALESCE(SUM(o.total_amount), 0) AS total_spent,
                (SELECT order_code FROM orders WHERE user_id = o.user_id $statusCondition ORDER BY created_at DESC LIMIT 1) AS last_order_code
            FROM orders o
            WHERE 1=1 $statusCondition
            GROUP BY o.user_id
        ) stats ON u.id = stats.user_id
    ";
} else {
    // Nếu không có bảng orders hoặc cột user_id, tạo bảng giả
    $statsJoin = "LEFT JOIN (SELECT NULL AS user_id, 0 AS order_count, 0 AS total_spent, NULL AS last_order_code) stats ON 1=0";
}

// Bắt đầu xây dựng câu lệnh WHERE động
$whereConditions = ["1=1"];

if ($roleCondition) {
    $whereConditions[] = $roleCondition;
}

// Tìm kiếm
if (!empty($search)) {
    $escaped = $conn->real_escape_string($search);
    $searchCondition = "(u.fullname LIKE '%$escaped%' OR u.email LIKE '%$escaped%' OR u.username LIKE '%$escaped%'";
    if (in_array('phone', $userColumns)) {
        $searchCondition .= " OR u.phone LIKE '%$escaped%'";
    }
    $searchCondition .= ")";
    $whereConditions[] = $searchCondition;
}

// Lọc theo ngày đăng ký
if (!empty($filterDateFrom)) {
    $whereConditions[] = "u.created_at >= '" . $conn->real_escape_string($filterDateFrom) . " 00:00:00'";
}
if (!empty($filterDateTo)) {
    $whereConditions[] = "u.created_at <= '" . $conn->real_escape_string($filterDateTo) . " 23:59:59'";
}

// Lọc theo tổng chi tiêu (sử dụng COALESCE để xử lý null)
if ($filterMinTotal > 0) {
    $whereConditions[] = "COALESCE(stats.total_spent, 0) >= $filterMinTotal";
}
if ($filterMaxTotal > 0) {
    $whereConditions[] = "COALESCE(stats.total_spent, 0) <= $filterMaxTotal";
}

// Lọc khách hàng thân thiết
if ($loyaltyOnly) {
    $whereConditions[] = "COALESCE(stats.total_spent, 0) >= " . LOYALTY_THRESHOLD;
}

// Lọc theo trạng thái (nếu có cột status trong users)
if ($filterStatus === 'active' && in_array('status', $userColumns)) {
    $whereConditions[] = "u.status = 1";
} elseif ($filterStatus === 'inactive' && in_array('status', $userColumns)) {
    $whereConditions[] = "u.status = 0";
}

$whereClause = " WHERE " . implode(" AND ", $whereConditions);

// Câu lệnh SELECT chính
$sql = "SELECT 
            u.id,
            u.fullname AS name,
            u.email,
            " . (in_array('phone', $userColumns) ? "u.phone," : "NULL AS phone,") . "
            u.created_at AS registered_date,
            COALESCE(stats.order_count, 0) AS order_count,
            COALESCE(stats.total_spent, 0) AS total_spent,
            stats.last_order_code
        FROM users u
        $statsJoin
        $whereClause
        ORDER BY $orderBy
        LIMIT $limit OFFSET $offset";

// Câu lệnh đếm tổng số bản ghi (áp dụng cùng bộ lọc)
$countSql = "SELECT COUNT(*) AS total FROM users u $statsJoin $whereClause";

// Thực thi truy vấn
$result = $conn->query($sql);
if (!$result) {
    error_log("SQL Error: " . $conn->error . " in query: " . $sql);
    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $conn->error]);
    exit;
}

$countResult = $conn->query($countSql);
$totalCustomers = $countResult ? $countResult->fetch_assoc()['total'] : 0;
$customers = [];

while ($row = $result->fetch_assoc()) {
    $row['total_spent_formatted'] = number_format($row['total_spent'], 0, ',', '.') . 'đ';
    $row['registered_date_formatted'] = date('d/m/Y', strtotime($row['registered_date']));
    $customers[] = $row;
}

// Trả về kết quả
echo json_encode([
    'success' => true,
    'customers' => $customers,
    'total' => (int)$totalCustomers,
    'page' => $page,
    'limit' => $limit
]);
?>