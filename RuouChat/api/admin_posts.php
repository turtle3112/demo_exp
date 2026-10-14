<?php
// api/admin_posts.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

function createSlug($str) {
    $str = trim(mb_strtolower($str));
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

function normalizeImageUrl($image) {
    if (empty($image)) return '';
    if (preg_match('#^https?://#', $image)) return $image;
    if (strpos($image, '/') === 0) return $image;
    return '/' . $image;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // GET: lấy danh sách hoặc chi tiết bài viết kèm categories, gallery
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id) {
            // Lấy bài viết
            $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $post = $stmt->get_result()->fetch_assoc();
            if (!$post) {
                echo json_encode([]);
                exit;
            }
            // Lấy danh mục
            $stmtCat = $conn->prepare("SELECT category_id FROM post_category_relations WHERE post_id = ?");
            $stmtCat->bind_param("i", $id);
            $stmtCat->execute();
            $cats = $stmtCat->get_result()->fetch_all(MYSQLI_ASSOC);
            $post['category_ids'] = array_column($cats, 'category_id');
            // Lấy gallery
            $stmtGal = $conn->prepare("SELECT id, image_url, sort_order, is_cover FROM post_images WHERE post_id = ? ORDER BY sort_order ASC");
            $stmtGal->bind_param("i", $id);
            $stmtGal->execute();
            $post['gallery'] = $stmtGal->get_result()->fetch_all(MYSQLI_ASSOC);
            $post['gallery'] = array_map(function($img) {
                $img['image_url'] = normalizeImageUrl($img['image_url']);
                return $img;
            }, $post['gallery']);
            echo json_encode($post);
        } else {
            // Lấy danh sách bài viết kèm tên danh mục (lấy một danh mục đầu tiên để hiển thị)
            $sql = "SELECT p.*, GROUP_CONCAT(pc.name SEPARATOR ', ') as category_names
                    FROM posts p
                    LEFT JOIN post_category_relations pcr ON p.id = pcr.post_id
                    LEFT JOIN post_categories pc ON pcr.category_id = pc.id
                    GROUP BY p.id ORDER BY p.id DESC";
            $result = $conn->query($sql);
            $posts = $result->fetch_all(MYSQLI_ASSOC);
            foreach ($posts as &$p) {
                $p['image'] = normalizeImageUrl($p['image']);
            }
            echo json_encode($posts);
        }
        exit;
    }

    // POST: thêm mới bài viết
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $title = trim($input['title'] ?? '');
        if (empty($title)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title required']);
            exit;
        }
        $slug = trim($input['slug'] ?? '') ?: createSlug($title);
        $content = $input['content'] ?? '';
        $short_description = $input['short_description'] ?? '';
        $status = (int)($input['status'] ?? 1);
        $category_ids = $input['category_ids'] ?? [];
        $gallery = $input['gallery'] ?? []; // mảng {url, sort_order, is_cover}
        $image_cover = $input['image_cover'] ?? ''; // URL ảnh đại diện

        $conn->begin_transaction();
        try {
            // Insert post
            $stmt = $conn->prepare("INSERT INTO posts (title, slug, content, short_description, status, image, author, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Admin', NOW())");
            $stmt->bind_param("ssssis", $title, $slug, $content, $short_description, $status, $image_cover);
            $stmt->execute();
            $post_id = $conn->insert_id;

            // Categories
            if (!empty($category_ids)) {
                $stmtCat = $conn->prepare("INSERT INTO post_category_relations (post_id, category_id) VALUES (?, ?)");
                foreach ($category_ids as $cat_id) {
                    $stmtCat->bind_param("ii", $post_id, $cat_id);
                    $stmtCat->execute();
                }
            }
            // Gallery images
            if (!empty($gallery)) {
                $stmtImg = $conn->prepare("INSERT INTO post_images (post_id, image_url, sort_order, is_cover) VALUES (?, ?, ?, ?)");
                foreach ($gallery as $idx => $img) {
                    $url = $img['url'];
                    $sort = $img['sort_order'] ?? $idx;
                    $is_cover = ($image_cover === $url) ? 1 : 0;
                    $stmtImg->bind_param("isii", $post_id, $url, $sort, $is_cover);
                    $stmtImg->execute();
                }
            }
            $conn->commit();
            echo json_encode(['success' => true, 'id' => $post_id]);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // PUT: cập nhật bài viết
    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        $title = trim($input['title'] ?? '');
        if ($id <= 0 || empty($title)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }
        $slug = trim($input['slug'] ?? '') ?: createSlug($title);
        $content = $input['content'] ?? '';
        $short_description = $input['short_description'] ?? '';
        $status = (int)($input['status'] ?? 1);
        $category_ids = $input['category_ids'] ?? [];
        $gallery = $input['gallery'] ?? [];
        $image_cover = $input['image_cover'] ?? '';

        $conn->begin_transaction();
        try {
            // Update post
            $stmt = $conn->prepare("UPDATE posts SET title=?, slug=?, content=?, short_description=?, status=?, image=? WHERE id=?");
            $stmt->bind_param("ssssisi", $title, $slug, $content, $short_description, $status, $image_cover, $id);
            $stmt->execute();

            // Update categories: xóa cũ, thêm mới
            $conn->query("DELETE FROM post_category_relations WHERE post_id = $id");
            if (!empty($category_ids)) {
                $stmtCat = $conn->prepare("INSERT INTO post_category_relations (post_id, category_id) VALUES (?, ?)");
                foreach ($category_ids as $cat_id) {
                    $stmtCat->bind_param("ii", $id, $cat_id);
                    $stmtCat->execute();
                }
            }
            // Update gallery: xóa cũ, thêm mới
            $conn->query("DELETE FROM post_images WHERE post_id = $id");
            if (!empty($gallery)) {
                $stmtImg = $conn->prepare("INSERT INTO post_images (post_id, image_url, sort_order, is_cover) VALUES (?, ?, ?, ?)");
                foreach ($gallery as $idx => $img) {
                    $url = $img['url'];
                    $sort = $img['sort_order'] ?? $idx;
                    $is_cover = ($image_cover === $url) ? 1 : 0;
                    $stmtImg->bind_param("isii", $id, $url, $sort, $is_cover);
                    $stmtImg->execute();
                }
            }
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // DELETE: xóa bài viết
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            exit;
        }
        $conn->query("DELETE FROM post_category_relations WHERE post_id = $id");
        $conn->query("DELETE FROM post_images WHERE post_id = $id");
        $conn->query("DELETE FROM posts WHERE id = $id");
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>