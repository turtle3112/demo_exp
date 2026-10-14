<?php
require_once 'config.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);



$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

// GET list
if ($method === 'GET' && $action === 'list') {
    $page_id = isset($_GET['page_id']) ? (int)$_GET['page_id'] : 0;
    if (!$page_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing page_id']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM page_products WHERE page_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$page_id]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Gắn thêm thông tin sản phẩm từ bảng products
        foreach ($products as &$p) {
            // Lấy name, slug, price
            $prodStmt = $pdo->prepare("SELECT name, slug, price FROM products WHERE id = ?");
            $prodStmt->execute([$p['product_id']]);
            $prod = $prodStmt->fetch(PDO::FETCH_ASSOC);
            if ($prod) {
                $p['name'] = $prod['name'];
                $p['slug'] = $prod['slug'];
                $p['price'] = $prod['price'];
            } else {
                $p['name'] = 'Sản phẩm không tồn tại';
                $p['slug'] = '';
                $p['price'] = 0;
            }
            
            // Lấy ảnh chính từ bảng product_images (nếu có)
            $imgStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1");
            $imgStmt->execute([$p['product_id']]);
            $img = $imgStmt->fetch(PDO::FETCH_ASSOC);
            $p['main_image'] = $img ? $img['image_url'] : '';
        }
        echo json_encode(['success' => true, 'data' => $products]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
    }
    exit;
}

// POST actions
if ($method === 'POST') {
    if ($action === 'add') {
        $page_id = isset($input['page_id']) ? (int)$input['page_id'] : 0;
        $product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
        if (!$page_id || !$product_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing page_id or product_id']);
            exit;
        }
        try {
            // Kiểm tra tồn tại
            $check = $pdo->prepare("SELECT id FROM page_products WHERE page_id = ? AND product_id = ?");
            $check->execute([$page_id, $product_id]);
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Sản phẩm đã có trong trang']);
                exit;
            }
            // Lấy max sort_order
            $maxStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) FROM page_products WHERE page_id = ?");
            $maxStmt->execute([$page_id]);
            $new_order = $maxStmt->fetchColumn() + 1;
            $insert = $pdo->prepare("INSERT INTO page_products (page_id, product_id, sort_order) VALUES (?, ?, ?)");
            $insert->execute([$page_id, $product_id, $new_order]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
        }
        exit;
    } 
    elseif ($action === 'update_order') {
        $page_id = isset($input['page_id']) ? (int)$input['page_id'] : 0;
        $items = $input['items'] ?? [];
        if (!$page_id || !is_array($items)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }
        try {
            $pdo->beginTransaction();
            foreach ($items as $item) {
                $upd = $pdo->prepare("UPDATE page_products SET sort_order = ? WHERE id = ? AND page_id = ?");
                $upd->execute([$item['sort_order'], $item['id'], $page_id]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
        }
        exit;
    }
    else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action for POST']);
        exit;
    }
}

// DELETE
if ($method === 'DELETE') {
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing id']);
        exit;
    }
    try {
        $del = $pdo->prepare("DELETE FROM page_products WHERE id = ?");
        $del->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>