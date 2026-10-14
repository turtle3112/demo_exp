<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Đảm bảo API luôn trả JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Secret');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';

/**
 * Lấy HTTP header tương thích với cả XAMPP và InfinityFree
 */
function getRequestHeader($name)
{
    // Cách 1: getallheaders() nếu server hỗ trợ
    if (function_exists('getallheaders')) {
        $headers = getallheaders();

        foreach ($headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }
    }

    // Cách 2: lấy từ $_SERVER
    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

    if (isset($_SERVER[$serverKey])) {
        return $_SERVER[$serverKey];
    }

    // Một số server/proxy có thể dùng REDIRECT_HTTP_*
    $redirectKey = 'REDIRECT_' . $serverKey;

    if (isset($_SERVER[$redirectKey])) {
        return $_SERVER[$redirectKey];
    }

    return '';
}

// Lấy Admin Secret
$secret = getRequestHeader('X-Admin-Secret');

// Kiểm tra Admin Secret nếu được gửi lên
if (!empty($secret) && $secret !== ADMIN_SECRET) {
    http_response_code(401);

    echo json_encode([
        'error' => 'Unauthorized'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {

    // =========================================================
    // KIỂM TRA / TẠO BẢNG COUNTRIES
    // =========================================================

    $tableCheck = $pdo->query("SHOW TABLES LIKE 'countries'");

    if ($tableCheck->rowCount() == 0) {

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `countries` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_name` (`name`)
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci
        ");
    }

    // =========================================================
    // GET - LẤY DANH SÁCH QUỐC GIA
    // =========================================================

    if ($method === 'GET') {

        $stmt = $pdo->query("
            SELECT id, name, created_at
            FROM countries
            ORDER BY name ASC
        ");

        $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(
            $countries,
            JSON_UNESCAPED_UNICODE
        );

    // =========================================================
    // POST - THÊM QUỐC GIA
    // =========================================================

    } elseif ($method === 'POST') {

        $input = file_get_contents('php://input');

        $data = json_decode($input, true);

        if (!is_array($data)) {
            http_response_code(400);

            echo json_encode([
                'error' => 'Dữ liệu JSON không hợp lệ'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        if (empty($data['name'])) {

            http_response_code(400);

            echo json_encode([
                'error' => 'Tên quốc gia không được để trống'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $name = trim($data['name']);

        // Kiểm tra trùng tên
        $checkStmt = $pdo->prepare("
            SELECT id
            FROM countries
            WHERE name = ?
            LIMIT 1
        ");

        $checkStmt->execute([$name]);

        if ($checkStmt->fetch()) {

            http_response_code(400);

            echo json_encode([
                'error' => 'Quốc gia này đã tồn tại'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // Thêm quốc gia
        $stmt = $pdo->prepare("
            INSERT INTO countries (name)
            VALUES (?)
        ");

        $stmt->execute([$name]);

        $id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'id' => $id,
            'name' => $name,
            'message' => 'Thêm quốc gia thành công'
        ], JSON_UNESCAPED_UNICODE);

    // =========================================================
    // METHOD KHÔNG ĐƯỢC HỖ TRỢ
    // =========================================================

    } else {

        http_response_code(405);

        echo json_encode([
            'error' => 'Method not allowed'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>