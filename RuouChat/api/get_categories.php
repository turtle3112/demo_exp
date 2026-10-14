<?php
/**
 * api/get_categories.php
 * API lấy danh sách danh mục cho dropdown menu
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

$result = $conn->query("SELECT id, name, slug FROM categories ORDER BY name ASC");
$categories = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($categories);
$conn->close();
?>