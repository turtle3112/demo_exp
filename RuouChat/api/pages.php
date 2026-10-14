<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                $stmt = $pdo->query("SELECT id, title, slug, page_type, status, created_at, updated_at FROM pages ORDER BY created_at DESC");
                $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $pages]);
            } elseif (isset($_GET['id']) && ctype_digit($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
                $stmt->execute([(int)$_GET['id']]);
                $page = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($page) {
                    echo json_encode(['success' => true, 'data' => $page]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Page not found']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Missing action or valid id']);
            }
            break;

        case 'POST':
            $title = trim($input['title'] ?? '');
            $slug = trim($input['slug'] ?? '');
            $content = $input['content'] ?? '';
            $status = isset($input['status']) && in_array($input['status'], ['published', 'draft']) ? $input['status'] : 'published';
            $page_type = isset($input['page_type']) && in_array($input['page_type'], ['content', 'product_list']) ? $input['page_type'] : 'content';

            if (empty($title) || empty($slug)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields: title, slug']);
                exit;
            }
            if ($page_type === 'content' && empty($content)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing content for content page']);
                exit;
            }

            $check = $pdo->prepare("SELECT id FROM pages WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Slug already exists']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, status, page_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $content, $status, $page_type]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'PUT':
            $id = isset($input['id']) && ctype_digit((string)$input['id']) ? (int)$input['id'] : 0;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing or invalid page id']);
                exit;
            }

            $updates = [];
            $params = [];

            if (isset($input['title'])) {
                $updates[] = "title = ?";
                $params[] = trim($input['title']);
            }
            if (isset($input['slug'])) {
                $newSlug = trim($input['slug']);
                $check = $pdo->prepare("SELECT id FROM pages WHERE slug = ? AND id != ?");
                $check->execute([$newSlug, $id]);
                if ($check->fetch()) {
                    http_response_code(409);
                    echo json_encode(['error' => 'Slug already exists']);
                    exit;
                }
                $updates[] = "slug = ?";
                $params[] = $newSlug;
            }
            if (isset($input['content'])) {
                $updates[] = "content = ?";
                $params[] = $input['content'];
            }
            if (isset($input['status']) && in_array($input['status'], ['published', 'draft'])) {
                $updates[] = "status = ?";
                $params[] = $input['status'];
            }
            if (isset($input['page_type']) && in_array($input['page_type'], ['content', 'product_list'])) {
                $updates[] = "page_type = ?";
                $params[] = $input['page_type'];
            }

            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                exit;
            }

            $params[] = $id;
            $sql = "UPDATE pages SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $id = isset($input['id']) && ctype_digit((string)$input['id']) ? (int)$input['id'] : 0;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing or invalid page id']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}