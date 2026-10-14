<?php
require_once 'config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Hàm lấy thông tin sản phẩm từ bảng tương ứng
function getProductInfo($conn, $product_id, $product_type = null) {
    // Nếu có product_type thì tìm đúng bảng
    if ($product_type) {
        switch ($product_type) {
            case 'product':
                $table = 'products';
                $id_col = 'id';
                break;
            case 'other':
                $table = 'other_products';
                $id_col = 'id';
                break;
            case 'crystal_glasses':
            case 'glass':
                $table = 'crystal_glasses';
                $id_col = 'id';
                break;
            case 'gift':
                $table = 'gift_products';
                $id_col = 'id';
                break;
            default:
                return null;
        }
        $stmt = $conn->prepare("SELECT id, name, price FROM `$table` WHERE id = ?");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if ($product) {
            // Lấy ảnh (có thể có cột main_image hoặc image_url)
            $img = '';
            $colCheck = $conn->query("SHOW COLUMNS FROM `$table`");
            $columns = [];
            while ($col = $colCheck->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
            if (in_array('main_image', $columns)) {
                $imgStmt = $conn->prepare("SELECT main_image FROM `$table` WHERE id = ?");
                $imgStmt->bind_param('i', $product_id);
                $imgStmt->execute();
                $imgRow = $imgStmt->get_result()->fetch_assoc();
                $img = $imgRow['main_image'] ?? '';
            } elseif (in_array('image_url', $columns)) {
                $imgStmt = $conn->prepare("SELECT image_url FROM `$table` WHERE id = ?");
                $imgStmt->bind_param('i', $product_id);
                $imgStmt->execute();
                $imgRow = $imgStmt->get_result()->fetch_assoc();
                $img = $imgRow['image_url'] ?? '';
            } else {
                // fallback: lấy từ bảng product_images chung
                $imgStmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND (is_main = 1 OR is_main IS NULL) LIMIT 1");
                $imgStmt->bind_param('i', $product_id);
                $imgStmt->execute();
                $imgRow = $imgStmt->get_result()->fetch_assoc();
                $img = $imgRow['image_url'] ?? '';
            }
            $product['image'] = $img;
            return $product;
        }
        return null;
    }

    // Nếu không có product_type, tự động dò các bảng
    $tables = ['products', 'other_products', 'crystal_glasses', 'gift_products'];
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SELECT id, name, price FROM `$table` WHERE id = ?");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if ($product) {
            // Lấy ảnh tương tự như trên
            $img = '';
            $colCheck = $conn->query("SHOW COLUMNS FROM `$table`");
            $columns = [];
            while ($col = $colCheck->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
            if (in_array('main_image', $columns)) {
                $imgStmt = $conn->prepare("SELECT main_image FROM `$table` WHERE id = ?");
                $imgStmt->bind_param('i', $product_id);
                $imgStmt->execute();
                $imgRow = $imgStmt->get_result()->fetch_assoc();
                $img = $imgRow['main_image'] ?? '';
            } elseif (in_array('image_url', $columns)) {
                $imgStmt = $conn->prepare("SELECT image_url FROM `$table` WHERE id = ?");
                $imgStmt->bind_param('i', $product_id);
                $imgStmt->execute();
                $imgRow = $imgStmt->get_result()->fetch_assoc();
                $img = $imgRow['image_url'] ?? '';
            } else {
                $imgStmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND (is_main = 1 OR is_main IS NULL) LIMIT 1");
                $imgStmt->bind_param('i', $product_id);
                $imgStmt->execute();
                $imgRow = $imgStmt->get_result()->fetch_assoc();
                $img = $imgRow['image_url'] ?? '';
            }
            $product['image'] = $img;
            return $product;
        }
    }
    return null;
}

// GET: lấy giỏ hàng
if ($method === 'GET') {
    $cart = $_SESSION['cart'] ?? [];
    // Đảm bảo mỗi item có đủ thông tin (ảnh)
    foreach ($cart as &$item) {
        if (empty($item['image'])) {
            $info = getProductInfo($conn, $item['id'], $item['product_type'] ?? null);
            if ($info) {
                $item['image'] = $info['image'] ?? '';
                $item['name'] = $info['name'];
                $item['price'] = (float)$info['price'];
            }
        }
    }
    echo json_encode($cart);
    exit;
}

// POST: thêm, cập nhật, xóa
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $product_id = (int)($input['product_id'] ?? 0);
    $quantity = max(1, (int)($input['quantity'] ?? 1));
    $product_type = $input['product_type'] ?? null;

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    switch ($action) {
        case 'add':
            $product = getProductInfo($conn, $product_id, $product_type);
            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => 'Sản phẩm không tồn tại']);
                exit;
            }
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id'       => $product_id,
                    'name'     => $product['name'],
                    'price'    => (float)$product['price'],
                    'quantity' => $quantity,
                    'image'    => $product['image'] ?? '',
                    'product_type' => $product_type // lưu lại để sau này dùng
                ];
            }
            echo json_encode(['success' => true]);
            break;

        case 'update':
            if (isset($_SESSION['cart'][$product_id])) {
                if ($quantity <= 0) {
                    unset($_SESSION['cart'][$product_id]);
                } else {
                    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Sản phẩm không có trong giỏ']);
            }
            break;

        case 'remove':
            unset($_SESSION['cart'][$product_id]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Hành động không hợp lệ']);
    }
    exit;
}

// DELETE: xóa toàn bộ giỏ
if ($method === 'DELETE') {
    unset($_SESSION['cart']);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>