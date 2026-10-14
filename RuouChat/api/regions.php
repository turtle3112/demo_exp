<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_clean();

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$headers = getallheaders();
$secret = isset($headers['X-Admin-Secret']) ? $headers['X-Admin-Secret'] : '';

if ($secret !== ADMIN_SECRET && !empty($secret)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // Kiểm tra bảng
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'regions'");
    if ($tableCheck->rowCount() == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `regions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, name, created_at FROM regions ORDER BY name ASC");
        $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($regions);
        
    } elseif ($method === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tên vùng làm rượu không được để trống']);
            exit;
        }
        
        $name = trim($data['name']);
        
        $checkStmt = $pdo->prepare("SELECT id FROM regions WHERE name = ?");
        $checkStmt->execute([$name]);
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Vùng làm rượu này đã tồn tại']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO regions (name) VALUES (?)");
        $stmt->execute([$name]);
        $id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'id' => $id,
            'name' => $name,
            'message' => 'Thêm vùng làm rượu thành công'
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

ob_end_flush();
?>