<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $result = $conn->query("SELECT id, name FROM producers ORDER BY name");
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi truy vấn: ' . $conn->error]);
        exit;
    }
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
} 
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    if (empty($name)) {
        echo json_encode(['error' => 'Tên nhà sản xuất không được để trống']);
        exit;
    }

    // Kiểm tra trùng tên
    $check = $conn->prepare("SELECT id FROM producers WHERE name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo json_encode(['error' => 'Nhà sản xuất đã tồn tại']);
        exit;
    }
    $check->close();

    // Tạo slug từ tên (loại bỏ dấu, thay khoảng trắng bằng '-')
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $name)));
    $slug = str_replace(' ', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);

    // Insert chỉ với name và slug, các cột khác để NULL hoặc giá trị mặc định
    $stmt = $conn->prepare("INSERT INTO producers (name, slug, status) VALUES (?, ?, 1)");
    $stmt->bind_param("ss", $name, $slug);
    if ($stmt->execute()) {
        echo json_encode(['id' => $conn->insert_id, 'name' => $name]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi khi thêm: ' . $stmt->error]);
    }
    $stmt->close();
} 
else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>