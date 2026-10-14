<?php
// api/post_categories.php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$result = $conn->query("SELECT id, name, slug FROM post_categories ORDER BY id");
$categories = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($categories);
?>