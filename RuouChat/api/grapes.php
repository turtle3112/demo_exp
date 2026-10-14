<?php
// Bật debug để xem lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Đảm bảo không có output trước JSON
ob_clean();

require_once 'config.php';

// Ghi đè header để đảm bảo JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Kiểm tra secret key từ header
$headers = getallheaders();
$secret = isset($headers['X-Admin-Secret']) ? $headers['X-Admin-Secret'] : '';

// Cho phép nếu đúng secret hoặc đang test (không có secret)
if ($secret !== ADMIN_SECRET && !empty($secret)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // Kiểm tra bảng tồn tại, nếu chưa thì tạo
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'grapes'");
    if ($tableCheck->rowCount() == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `grapes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    if ($method === 'GET') {
        // Lấy danh sách loại nho
        $stmt = $pdo->query("SELECT id, name, created_at FROM grapes ORDER BY name ASC");
        $grapes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($grapes);
        
    } elseif ($method === 'POST') {
        // Thêm loại nho mới
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tên loại nho không được để trống']);
            exit;
        }
        
        $name = trim($data['name']);
        
        // Kiểm tra trùng lặp
        $checkStmt = $pdo->prepare("SELECT id FROM grapes WHERE name = ?");
        $checkStmt->execute([$name]);
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Loại nho này đã tồn tại']);
            exit;
        }
        
        // Thêm mới
        $stmt = $pdo->prepare("INSERT INTO grapes (name) VALUES (?)");
        $stmt->execute([$name]);
        $id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'id' => $id,
            'name' => $name,
            'message' => 'Thêm loại nho thành công'
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

// Đảm bảo không có output dư thừa
ob_end_flush();
?>