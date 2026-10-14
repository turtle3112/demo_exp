<?php
/**
 * api/get_wine_attributes.php
 * API lấy danh sách các đặc trưng rượu vang (quốc gia, vùng, loại nho)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);

require_once __DIR__ . '/config.php';

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

$response = [];

switch ($type) {
    case 'countries':
        // Lấy danh sách quốc gia từ bảng products (cột country)
        $result = $conn->query("SELECT DISTINCT country FROM products WHERE country IS NOT NULL AND country != '' ORDER BY country ASC");
        $countries = [];
        while ($row = $result->fetch_assoc()) {
            $countries[] = $row['country'];
        }
        $response = $countries;
        break;
        
    case 'regions':
        // Lấy danh sách vùng làm vang
        $result = $conn->query("SELECT DISTINCT region FROM products WHERE region IS NOT NULL AND region != '' ORDER BY region ASC");
        $regions = [];
        while ($row = $result->fetch_assoc()) {
            $regions[] = $row['region'];
        }
        $response = $regions;
        break;
        
    case 'grapes':
        // Lấy danh sách loại nho
        $result = $conn->query("SELECT DISTINCT grape_type FROM products WHERE grape_type IS NOT NULL AND grape_type != '' ORDER BY grape_type ASC");
        $grapes = [];
        while ($row = $result->fetch_assoc()) {
            $grapes[] = $row['grape_type'];
        }
        $response = $grapes;
        break;
        
    default:
        $response = ['error' => 'Invalid type parameter'];
}

echo json_encode($response);
$conn->close();
?>