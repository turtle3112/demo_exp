<?php
/**
 * api/admin_flash_sales.php
 * API quản lý Flash Sale cho admin
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log_flash_sale.txt');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Secret');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);

require_once __DIR__ . '/config.php';

// Kiểm tra kết nối database
if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . ($conn->connect_error ?? 'unknown')]);
    exit;
}

function sendError($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function sendSuccess($data = null) {
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ========== KIỂM TRA BẢNG TỒN TẠI ==========
$tableCheck = $conn->query("SHOW TABLES LIKE 'flash_sales'");
if ($tableCheck->num_rows == 0) {
    // Tạo bảng nếu chưa có
    $createFlashSales = "
    CREATE TABLE IF NOT EXISTS flash_sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        status ENUM('active', 'inactive', 'ended') DEFAULT 'inactive',
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status_time (status, start_time, end_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $createFlashProducts = "
    CREATE TABLE IF NOT EXISTS flash_sale_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        flash_sale_id INT NOT NULL,
        product_id INT NOT NULL,
        flash_price DECIMAL(15,0) NOT NULL,
        flash_stock INT DEFAULT 0,
        sold_count INT DEFAULT 0,
        max_per_user INT DEFAULT 0,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (flash_sale_id) REFERENCES flash_sales(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY uk_flash_product (flash_sale_id, product_id),
        INDEX idx_flash_sale (flash_sale_id),
        INDEX idx_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($createFlashSales);
    $conn->query($createFlashProducts);
}

// ========== GET danh sách flash sale ==========
if ($method === 'GET' && !isset($_GET['action'])) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id) {
        // Lấy chi tiết 1 flash sale
        $stmt = $conn->prepare("SELECT * FROM flash_sales WHERE id = ?");
        if (!$stmt) {
            sendError('Prepare failed: ' . $conn->error, 500);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $flashSale = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($flashSale) {
            // Lấy danh sách sản phẩm trong flash sale
            $stmt2 = $conn->prepare("
                SELECT 
                    fp.*, 
                    p.id as product_id, 
                    p.name, 
                    p.slug, 
                    p.price as original_price,
                    (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image
                FROM flash_sale_products fp
                JOIN products p ON fp.product_id = p.id
                WHERE fp.flash_sale_id = ?
                ORDER BY fp.display_order ASC, fp.id ASC
            ");
            if ($stmt2) {
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
                $products = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
                $flashSale['products'] = $products;
                $stmt2->close();
            } else {
                $flashSale['products'] = [];
            }
        }
        
        sendSuccess($flashSale);
        exit;
    }
    
    // Lấy danh sách tất cả flash sale
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $sql = "SELECT fs.*, 
                   (SELECT COUNT(*) FROM flash_sale_products WHERE flash_sale_id = fs.id) as product_count
            FROM flash_sales fs 
            ORDER BY fs.display_order ASC, fs.id DESC";
    
    if ($status && in_array($status, ['active', 'inactive', 'ended'])) {
        $stmt = $conn->prepare("SELECT fs.*, (SELECT COUNT(*) FROM flash_sale_products WHERE flash_sale_id = fs.id) as product_count FROM flash_sales fs WHERE fs.status = ? ORDER BY fs.display_order ASC, fs.id DESC");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $flashSales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result = $conn->query($sql);
        if ($result) {
            $flashSales = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $flashSales = [];
        }
    }
    
    sendSuccess($flashSales);
    exit;
}

// ========== GET flash sale đang hoạt động cho frontend ==========
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'active') {
    $now = date('Y-m-d H:i:s');
    
    $sql = "
        SELECT fs.* 
        FROM flash_sales fs
        WHERE fs.status = 'active' 
            AND fs.start_time <= ? 
            AND fs.end_time >= ?
        ORDER BY fs.display_order ASC, fs.id ASC
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error, 500);
    }
    $stmt->bind_param("ss", $now, $now);
    $stmt->execute();
    $flashSale = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($flashSale) {
        // Lấy sản phẩm flash sale
        $stmt2 = $conn->prepare("
            SELECT 
                fp.*,
                p.id, p.name, p.slug, p.price as original_price, p.description,
                (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image,
                (SELECT alt_text FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image_alt
            FROM flash_sale_products fp
            JOIN products p ON fp.product_id = p.id
            WHERE fp.flash_sale_id = ? 
                AND fp.flash_stock > fp.sold_count
                AND p.display_status IN ('visible', 'coming_soon')
            ORDER BY fp.display_order ASC, fp.id ASC
            LIMIT 20
        ");
        if ($stmt2) {
            $stmt2->bind_param("i", $flashSale['id']);
            $stmt2->execute();
            $products = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt2->close();
            
            foreach ($products as &$p) {
                $p['flash_price'] = (float)$p['flash_price'];
                $p['original_price'] = (float)$p['original_price'];
                $p['discount_percent'] = $p['original_price'] > 0 ? round((1 - $p['flash_price'] / $p['original_price']) * 100) : 0;
                $p['remaining_stock'] = $p['flash_stock'] - $p['sold_count'];
                $p['flash_price_formatted'] = number_format($p['flash_price'], 0, ',', '.') . 'đ';
                $p['original_price_formatted'] = number_format($p['original_price'], 0, ',', '.') . 'đ';
            }
            $flashSale['products'] = $products;
        } else {
            $flashSale['products'] = [];
        }
        $flashSale['end_timestamp'] = strtotime($flashSale['end_time']) * 1000;
    }
    
    sendSuccess($flashSale);
    exit;
}

// ========== CREATE flash sale ==========
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendError('Invalid JSON input: ' . json_last_error_msg(), 400);
    }
    
    $name = trim($input['name'] ?? '');
    $start_time = $input['start_time'] ?? '';
    $end_time = $input['end_time'] ?? '';
    $status = $input['status'] ?? 'inactive';
    $display_order = intval($input['display_order'] ?? 0);
    $description = $input['description'] ?? '';
    
    if (!$name || !$start_time || !$end_time) {
        sendError('Thiếu thông tin bắt buộc: tên, thời gian bắt đầu, thời gian kết thúc');
    }
    
    $stmt = $conn->prepare("
        INSERT INTO flash_sales (name, description, start_time, end_time, status, display_order)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error, 500);
    }
    $stmt->bind_param("sssssi", $name, $description, $start_time, $end_time, $status, $display_order);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        
        // Thêm sản phẩm nếu có
        if (isset($input['products']) && is_array($input['products']) && count($input['products']) > 0) {
            $prodStmt = $conn->prepare("
                INSERT INTO flash_sale_products (flash_sale_id, product_id, flash_price, flash_stock, max_per_user, display_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($prodStmt) {
                foreach ($input['products'] as $idx => $prod) {
                    $productId = intval($prod['product_id']);
                    $flashPrice = floatval($prod['flash_price']);
                    $flashStock = intval($prod['flash_stock']);
                    $maxPerUser = intval($prod['max_per_user'] ?? 0);
                    $prodStmt->bind_param("iidiii", $newId, $productId, $flashPrice, $flashStock, $maxPerUser, $idx);
                    $prodStmt->execute();
                }
                $prodStmt->close();
            }
        }
        
        echo json_encode(['success' => true, 'id' => $newId]);
    } else {
        sendError('Lỗi khi tạo: ' . $stmt->error, 500);
    }
    $stmt->close();
    exit;
}

// ========== UPDATE flash sale ==========
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendError('Invalid JSON input: ' . json_last_error_msg(), 400);
    }
    
    $id = intval($input['id'] ?? 0);
    if (!$id) sendError('Missing flash sale ID');
    
    $name = trim($input['name'] ?? '');
    $start_time = $input['start_time'] ?? '';
    $end_time = $input['end_time'] ?? '';
    $status = $input['status'] ?? 'inactive';
    $display_order = intval($input['display_order'] ?? 0);
    $description = $input['description'] ?? '';
    
    if (!$name || !$start_time || !$end_time) {
        sendError('Thiếu thông tin bắt buộc');
    }
    
    $stmt = $conn->prepare("
        UPDATE flash_sales SET 
            name = ?, description = ?, start_time = ?, end_time = ?, 
            status = ?, display_order = ?
        WHERE id = ?
    ");
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error, 500);
    }
    $stmt->bind_param("sssssii", $name, $description, $start_time, $end_time, $status, $display_order, $id);
    
    if ($stmt->execute()) {
        // Xóa sản phẩm cũ và thêm mới
        $conn->query("DELETE FROM flash_sale_products WHERE flash_sale_id = $id");
        
        if (isset($input['products']) && is_array($input['products']) && count($input['products']) > 0) {
            $prodStmt = $conn->prepare("
                INSERT INTO flash_sale_products (flash_sale_id, product_id, flash_price, flash_stock, max_per_user, display_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($prodStmt) {
                foreach ($input['products'] as $idx => $prod) {
                    $productId = intval($prod['product_id']);
                    $flashPrice = floatval($prod['flash_price']);
                    $flashStock = intval($prod['flash_stock']);
                    $maxPerUser = intval($prod['max_per_user'] ?? 0);
                    $prodStmt->bind_param("iidiii", $id, $productId, $flashPrice, $flashStock, $maxPerUser, $idx);
                    $prodStmt->execute();
                }
                $prodStmt->close();
            }
        }
        
        echo json_encode(['success' => true]);
    } else {
        sendError('Lỗi khi cập nhật: ' . $stmt->error, 500);
    }
    $stmt->close();
    exit;
}

// ========== DELETE flash sale ==========
if ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) sendError('Missing flash sale ID');
    
    $conn->query("DELETE FROM flash_sale_products WHERE flash_sale_id = $id");
    $conn->query("DELETE FROM flash_sales WHERE id = $id");
    
    echo json_encode(['success' => true]);
    exit;
}

// ========== Thêm sản phẩm vào flash sale ==========
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add_product') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) sendError('Invalid JSON input');
    
    $flash_sale_id = intval($input['flash_sale_id'] ?? 0);
    $product_id = intval($input['product_id'] ?? 0);
    $flash_price = floatval($input['flash_price'] ?? 0);
    $flash_stock = intval($input['flash_stock'] ?? 0);
    $max_per_user = intval($input['max_per_user'] ?? 0);
    
    if (!$flash_sale_id || !$product_id || $flash_price <= 0) {
        sendError('Thiếu thông tin bắt buộc');
    }
    
    $stmt = $conn->prepare("
        INSERT INTO flash_sale_products (flash_sale_id, product_id, flash_price, flash_stock, max_per_user)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        flash_price = VALUES(flash_price), flash_stock = VALUES(flash_stock), max_per_user = VALUES(max_per_user)
    ");
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error, 500);
    }
    $stmt->bind_param("iidii", $flash_sale_id, $product_id, $flash_price, $flash_stock, $max_per_user);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        sendError('Lỗi: ' . $stmt->error, 500);
    }
    $stmt->close();
    exit;
}

sendError('Method not allowed', 405);
?>