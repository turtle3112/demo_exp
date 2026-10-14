<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($conn) || !$conn instanceof mysqli) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi cấu hình database']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Vui lòng đăng nhập']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Lấy thông tin chi tiết sản phẩm
 */
function getProductDetails($conn, $product_id, $product_type) {
    // Chuyển 'glass' -> 'crystal_glasses'
    if ($product_type === 'glass') $product_type = 'crystal_glasses';

    $name = '';
    $price = 0;
    $image = '';
    $slug = '';

    switch ($product_type) {
        case 'product':
            $stmt = $conn->prepare("SELECT name, price, slug FROM products WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $name = $row['name'];
                    $price = (float)$row['price'];
                    $slug = $row['slug'];
                }
                $stmt->close();
            }
            $imgStmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND (is_main = 1 OR is_main IS NULL) LIMIT 1");
            if ($imgStmt) {
                $imgStmt->bind_param("i", $product_id);
                $imgStmt->execute();
                $imgRes = $imgStmt->get_result();
                if ($img = $imgRes->fetch_assoc()) $image = $img['image_url'];
                $imgStmt->close();
            }
            break;

        case 'other':
            $imageColumn = 'main_image';
            $colCheck = $conn->query("SHOW COLUMNS FROM other_products");
            if ($colCheck && $colCheck->num_rows) {
                $existing = [];
                while ($col = $colCheck->fetch_assoc()) $existing[] = $col['Field'];
                foreach (['image', 'image_url', 'thumbnail', 'main_image'] as $cand) {
                    if (in_array($cand, $existing)) { $imageColumn = $cand; break; }
                }
            }
            $sql = "SELECT name, price, `$imageColumn` AS img_url FROM other_products WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $name = $row['name'];
                    $price = (float)$row['price'];
                    $image = $row['img_url'] ?? '';
                }
                $stmt->close();
            }
            $slug = '';
            break;

        case 'crystal_glasses':
            $stmt = $conn->prepare("SELECT name, price, slug, main_image FROM crystal_glasses WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $name = $row['name'];
                    $price = (float)$row['price'];
                    $slug = $row['slug'];
                    $image = $row['main_image'] ?? '';
                }
                $stmt->close();
            }
            break;

        case 'gift':
            $stmt = $conn->prepare("SELECT name, price, slug, main_image FROM gift_products WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $name = $row['name'];
                    $price = (float)$row['price'];
                    $slug = $row['slug'];
                    $image = $row['main_image'] ?? '';
                }
                $stmt->close();
            }
            break;

        default: return null;
    }

    if (empty($name)) return null;
    if (empty($image)) $image = 'https://placehold.co/300x200?text=No+Image';
    return ['name' => $name, 'price' => $price, 'image' => $image, 'slug' => $slug];
}

/**
 * Tự động xác định loại sản phẩm
 */
function detectProductType($conn, $product_id) {
    $check = $conn->query("SELECT id FROM products WHERE id = $product_id");
    if ($check && $check->num_rows > 0) return 'product';
    $check = $conn->query("SELECT id FROM other_products WHERE id = $product_id");
    if ($check && $check->num_rows > 0) return 'other';
    $check = $conn->query("SELECT id FROM crystal_glasses WHERE id = $product_id");
    if ($check && $check->num_rows > 0) return 'crystal_glasses';
    $check = $conn->query("SELECT id FROM gift_products WHERE id = $product_id");
    if ($check && $check->num_rows > 0) return 'gift';
    return false;
}

// ------------------- GET -------------------
if ($method === 'GET') {
    $stmt = $conn->prepare("SELECT id, product_id, product_type FROM wishlist WHERE user_id = ? ORDER BY created_at DESC");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi truy vấn wishlist: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $details = getProductDetails($conn, $row['product_id'], $row['product_type']);
        if ($details) $items[] = array_merge(['id' => $row['id'], 'product_id' => $row['product_id'], 'product_type' => $row['product_type']], $details);
    }
    $stmt->close();
    echo json_encode(['success' => true, 'data' => $items]);
    exit;
}

// ------------------- POST -------------------
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu product_id']);
        exit;
    }

    $product_type = isset($input['product_type']) ? trim($input['product_type']) : null;
    if ($product_type === 'glass') $product_type = 'crystal_glasses';

    if (!$product_type) {
        $product_type = detectProductType($conn, $product_id);
        if (!$product_type) {
            http_response_code(404);
            echo json_encode(['error' => 'Sản phẩm không tồn tại']);
            exit;
        }
    } else {
        $allowed = ['product', 'other', 'crystal_glasses', 'gift'];
        if (!in_array($product_type, $allowed)) {
            http_response_code(400);
            echo json_encode(['error' => 'product_type không hợp lệ']);
            exit;
        }
        // Kiểm tra tồn tại
        $exists = false;
        switch ($product_type) {
            case 'product': $q = "SELECT id FROM products WHERE id = $product_id"; break;
            case 'other': $q = "SELECT id FROM other_products WHERE id = $product_id"; break;
            case 'crystal_glasses': $q = "SELECT id FROM crystal_glasses WHERE id = $product_id"; break;
            case 'gift': $q = "SELECT id FROM gift_products WHERE id = $product_id"; break;
        }
        $check = $conn->query($q);
        $exists = $check && $check->num_rows > 0;
        if (!$exists) {
            http_response_code(404);
            echo json_encode(['error' => 'Sản phẩm không tồn tại']);
            exit;
        }
    }

    // Dùng INSERT IGNORE để tránh duplicate error
    $insert = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, product_id, product_type, created_at) VALUES (?, ?, ?, NOW())");
    if (!$insert) {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi prepare: ' . $conn->error]);
        exit;
    }
    $insert->bind_param("iis", $user_id, $product_id, $product_type);
    if ($insert->execute()) {
        if ($insert->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            // Bị ignore do duplicate
            echo json_encode(['success' => false, 'error' => 'Sản phẩm đã có trong danh sách yêu thích']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Thêm thất bại: ' . $insert->error]);
    }
    $insert->close();
    exit;
}

// ------------------- DELETE -------------------
elseif ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu product_id']);
        exit;
    }

    $product_type = isset($input['product_type']) ? trim($input['product_type']) : null;
    if ($product_type === 'glass') $product_type = 'crystal_glasses';

    if (!$product_type) {
        $typeQuery = $conn->prepare("SELECT product_type FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1");
        $typeQuery->bind_param("ii", $user_id, $product_id);
        $typeQuery->execute();
        $typeRes = $typeQuery->get_result();
        if ($row = $typeRes->fetch_assoc()) $product_type = $row['product_type'];
        $typeQuery->close();
    }
    if (!$product_type) {
        http_response_code(400);
        echo json_encode(['error' => 'Không xác định được loại sản phẩm để xóa']);
        exit;
    }

    $delete = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ? AND product_type = ?");
    $delete->bind_param("iis", $user_id, $product_id, $product_type);
    if ($delete->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Xóa thất bại: ' . $delete->error]);
    }
    $delete->close();
    exit;
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
?>