<?php
require_once 'config.php';

$result = $conn->query("SELECT DISTINCT post_type FROM posts WHERE status = 1 AND post_type IS NOT NULL AND post_type != ''");
if (!$result) {
    echo json_encode([]);
    exit;
}
$types = $result->fetch_all(MYSQLI_ASSOC);
header('Content-Type: application/json');
echo json_encode($types);
?>