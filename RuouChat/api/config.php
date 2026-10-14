<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Chỉ gửi header JSON khi file được gọi TRỰC TIẾP (không phải include)
if (basename($_SERVER['SCRIPT_FILENAME']) == 'config.php') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit(0);
    }
}

$host = "sql100.infinityfree.com";
$user = "if0_41775416";
$pass = "RuouChat2026";
$dbname = "if0_41775416_ruouchat";
$port = 3306;


// Kết nối MySQLi (cho các API cũ nếu cần)
$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Kết nối DB thất bại: ' . $conn->connect_error]));
}
$conn->set_charset('utf8');

// Kết nối PDO (dùng cho hầu hết các module: admin, frontend, ...)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['error' => 'Kết nối PDO thất bại: ' . $e->getMessage()]));
}

// --------------------- HẰNG SỐ DÙNG CHUNG ---------------------
define('ADMIN_SECRET', 'RuouchatAdmin2024!');  // Quan trọng: phải khớp với header gửi từ admin

// --------------------- HÀM KIỂM TRA ADMIN ---------------------
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function redirectIfNotLoggedIn() {
    if (!isAdminLoggedIn()) {
        header('Location: login.html');
        exit;
    }
}

// --------------------- HÀM KIỂM TRA USER CHO API ---------------------
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        exit;
    }
    return $_SESSION['user_id'];
}
?>