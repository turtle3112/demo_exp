<?php
header('Content-Type: application/json');



require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id) {
        // Lấy chi tiết một liên hệ
        $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $contact = $result->fetch_assoc();
        echo json_encode($contact);
    } else {
        // Lấy danh sách tất cả
        $result = $conn->query("SELECT * FROM contacts ORDER BY id DESC");
        $contacts = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($contacts);
    }
    exit;
}

if ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Missing contact id']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>