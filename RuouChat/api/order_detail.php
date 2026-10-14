<?php
require_once 'config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order id']);
    exit;
}

$order = $conn->query("SELECT * FROM orders WHERE id = $id")->fetch_assoc();
if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

$items = $conn->query("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $id")->fetch_all(MYSQLI_ASSOC);
$order['items'] = $items;
echo json_encode($order);
?>