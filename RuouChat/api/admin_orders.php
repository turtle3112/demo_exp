<?php
require_once 'config.php'; // file này PHẢI có session_start()

if (!isset($_SESSION)) {
    session_start();
}

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized - Bạn không có quyền truy cập']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id) {
        // Lấy thông tin đơn hàng + chi tiết sản phẩm (giữ nguyên)
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }
        $stmt_items = $conn->prepare("SELECT oi.*, p.name, p.slug FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt_items->bind_param("i", $id);
        $stmt_items->execute();
        $items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
        $order['items'] = $items;
        echo json_encode($order);
        exit;
    }

    // ---------- FILTER & SORT (danh sách đơn hàng) ----------
    $sql = "SELECT * FROM orders WHERE 1=1";
    $params = [];
    $types = "";

    // Lọc theo mảng trạng thái (status[])
    if (isset($_GET['status']) && is_array($_GET['status'])) {
        $placeholders = implode(',', array_fill(0, count($_GET['status']), '?'));
        $sql .= " AND status IN ($placeholders)";
        foreach ($_GET['status'] as $st) {
            $params[] = $st;
            $types .= "s";
        }
    } elseif (isset($_GET['status']) && !is_array($_GET['status'])) {
        $sql .= " AND status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }

    // Lọc theo phương thức thanh toán
    if (!empty($_GET['payment_method'])) {
        $sql .= " AND payment_method = ?";
        $params[] = $_GET['payment_method'];
        $types .= "s";
    }

    // Tìm kiếm (order_code, fullname, phone, email)
    if (!empty($_GET['search'])) {
        $search = "%" . $_GET['search'] . "%";
        $sql .= " AND (order_code LIKE ? OR fullname LIKE ? OR phone LIKE ? OR email LIKE ?)";
        $params[] = $search; $params[] = $search; $params[] = $search; $params[] = $search;
        $types .= "ssss";
    }

    // Sắp xếp
    $sort = $_GET['sort'] ?? 'newest';
    switch ($sort) {
        case 'oldest': $orderBy = "created_at ASC"; break;
        case 'total_high': $orderBy = "total_amount DESC"; break;
        case 'total_low': $orderBy = "total_amount ASC"; break;
        default: $orderBy = "created_at DESC";
    }
    $sql .= " ORDER BY $orderBy";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($orders);
    exit;
}

// Các method PUT, DELETE giữ nguyên như cũ
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    $status = $input['status'] ?? '';
    $allowed = ['pending', 'processing', 'completed', 'cancelled'];
    if ($id && in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
    }
    exit;
}

if ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if ($id) {
        $conn->query("DELETE FROM order_items WHERE order_id = $id");
        $conn->query("DELETE FROM orders WHERE id = $id");
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Missing order id']);
    }
    exit;
}
?>