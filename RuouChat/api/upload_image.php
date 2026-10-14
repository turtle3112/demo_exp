<?php
/**
 * api/upload_image.php
 * API upload ảnh cho admin - Hỗ trợ đầy đủ các loại ảnh
 * 
 * Hỗ trợ:
 * - product_gallery: Ảnh gallery sản phẩm (có ALT text)
 * - product_desc: Ảnh mô tả sản phẩm (có ALT text)
 * - post_gallery: Ảnh gallery bài viết
 * - post_thumbnail: Ảnh đại diện bài viết
 * - category: Ảnh danh mục
 * - banner: Ảnh banner
 * - general: Ảnh chung
 * 
 * Input POST:
 * - upload: file ảnh (required)
 * - type: loại ảnh (default: general)
 * - product_id: ID sản phẩm (nếu type là product_gallery)
 * - post_id: ID bài viết (nếu type là post_gallery)
 * - alt_text: ALT text cho ảnh (optional)
 * - is_main: có là ảnh chính không (0/1, default: 0)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

// Định nghĩa ADMIN_SECRET nếu chưa có
if (!defined('ADMIN_SECRET')) {
    define('ADMIN_SECRET', 'RuouchatAdmin2024!');
}

// ========== HELPER FUNCTIONS ==========

/**
 * Lấy base URL của dự án
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $dir = dirname($script);
    $base = dirname($dir);
    $base = ($base == '/' || $base == '\\') ? '' : $base;
    return $protocol . $host . $base;
}

/**
 * Tạo URL từ đường dẫn file
 */
function getFileUrl($filePath) {
    $baseUrl = getBaseUrl();
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    $relativePath = str_replace($docRoot, '', $filePath);
    $relativePath = str_replace('\\', '/', $relativePath);
    return $baseUrl . $relativePath;
}

/**
 * Tạo thumbnail cho ảnh
 */
function createThumbnail($sourcePath, $thumbPath, $width = 200, $height = 200) {
    $info = getimagesize($sourcePath);
    if (!$info) return false;
    
    $srcWidth = $info[0];
    $srcHeight = $info[1];
    $type = $info[2];
    
    // Tạo ảnh nguồn
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $src = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    // Tính toán tỷ lệ crop
    $srcAspect = $srcWidth / $srcHeight;
    $thumbAspect = $width / $height;
    
    if ($srcAspect > $thumbAspect) {
        // Ảnh ngang hơn - crop chiều ngang
        $newWidth = $srcHeight * $thumbAspect;
        $newHeight = $srcHeight;
        $srcX = ($srcWidth - $newWidth) / 2;
        $srcY = 0;
    } else {
        // Ảnh dọc hơn - crop chiều dọc
        $newWidth = $srcWidth;
        $newHeight = $srcWidth / $thumbAspect;
        $srcX = 0;
        $srcY = ($srcHeight - $newHeight) / 2;
    }
    
    // Tạo ảnh thumbnail
    $thumb = imagecreatetruecolor($width, $height);
    
    // Giữ nền trong suốt cho PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $width, $height, $transparent);
    }
    
    imagecopyresampled($thumb, $src, 0, 0, $srcX, $srcY, $width, $height, $newWidth, $newHeight);
    
    // Lưu ảnh
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $thumbPath, 80);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $thumbPath, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $thumbPath);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($thumb, $thumbPath, 80);
            break;
    }
    
    imagedestroy($src);
    imagedestroy($thumb);
    return true;
}

/**
 * Xóa file cũ (khi cập nhật ảnh đại diện)
 */
function deleteOldFile($filePath) {
    if ($filePath && file_exists($filePath)) {
        @unlink($filePath);
        // Xóa thumbnail nếu có
        $thumbPath = preg_replace('/\.([^.]+)$/', '_thumb.$1', $filePath);
        if (file_exists($thumbPath)) @unlink($thumbPath);
        return true;
    }
    return false;
}

/**
 * Tạo tên file an toàn
 */
function generateSafeFilename($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $timestamp = date('Ymd_His');
    $random = bin2hex(random_bytes(8));
    return $timestamp . '_' . $random . '.' . $ext;
}

/**
 * Tạo thư mục nếu chưa tồn tại
 */
function ensureDirectory($path) {
    if (!is_dir($path)) {
        return mkdir($path, 0755, true);
    }
    return true;
}

// ========== AUTHENTICATION ==========

$isAuth = false;

// Kiểm tra session admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $isAuth = true;
}

// Kiểm tra header X-Admin-Secret
$headers = function_exists('getallheaders') ? getallheaders() : [];
$secret = $headers['X-Admin-Secret'] ?? $_SERVER['HTTP_X_ADMIN_SECRET'] ?? '';
if ($secret === ADMIN_SECRET) {
    $isAuth = true;
}

if (!$isAuth) {
    http_response_code(403);
    echo json_encode([
        'uploaded' => false,
        'error' => ['message' => 'Unauthorized. Invalid admin credentials.']
    ]);
    exit;
}

// ========== VALIDATE REQUEST ==========

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'uploaded' => false,
        'error' => ['message' => 'Method not allowed. Use POST.']
    ]);
    exit;
}

// Lấy file upload
$file = $_FILES['upload'] ?? $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize limit.',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE limit.',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
    ];
    $errorMsg = $errorMessages[$file['error']] ?? 'Unknown upload error.';
    http_response_code(400);
    echo json_encode([
        'uploaded' => false,
        'error' => ['message' => $errorMsg]
    ]);
    exit;
}

// Kiểm tra loại file
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode([
        'uploaded' => false,
        'error' => ['message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP are allowed.']
    ]);
    exit;
}

// Kiểm tra kích thước file (max 10MB)
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode([
        'uploaded' => false,
        'error' => ['message' => 'File too large. Maximum size is 10MB.']
    ]);
    exit;
}

// ========== PROCESS UPLOAD ==========

$type = $_POST['type'] ?? 'general';
$altText = isset($_POST['alt_text']) ? trim($_POST['alt_text']) : null;
$isMain = isset($_POST['is_main']) ? intval($_POST['is_main']) : 0;

// Xác định thư mục upload
$baseUploadDir = __DIR__ . '/../uploads/';
$subDir = '';

switch ($type) {
    case 'product_gallery':
    case 'product':
        $subDir = 'products/';
        break;
    case 'product_desc':
        $subDir = 'product_desc/';
        break;
    case 'post_gallery':
    case 'post':
        $subDir = 'posts/';
        break;
    case 'post_thumbnail':
        $subDir = 'posts/thumbnails/';
        break;
    case 'category':
        $subDir = 'categories/';
        break;
    case 'banner':
        $subDir = 'banners/';
        break;
    default:
        $subDir = 'general/';
}

$uploadDir = $baseUploadDir . $subDir;

// Tạo thư mục con theo năm/tháng
$yearMonth = date('Y/m');
$uploadDir .= $yearMonth . '/';

if (!ensureDirectory($uploadDir)) {
    http_response_code(500);
    echo json_encode([
        'uploaded' => false,
        'error' => ['message' => 'Cannot create upload directory: ' . $uploadDir]
    ]);
    exit;
}

// Tạo tên file an toàn
$filename = generateSafeFilename($file['name']);
$filePath = $uploadDir . $filename;

// Di chuyển file
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    http_response_code(500);
    echo json_encode([
        'uploaded' => false,
        'error' => ['message' => 'Cannot save uploaded file.']
    ]);
    exit;
}

// Tạo thumbnail
$thumbPath = preg_replace('/\.([^.]+)$/', '_thumb.$1', $filePath);
$hasThumb = createThumbnail($filePath, $thumbPath);

// Tạo URL
$baseUrl = getBaseUrl();
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $filePath);
$relativePath = str_replace('\\', '/', $relativePath);
$url = $baseUrl . $relativePath;

$thumbUrl = null;
if ($hasThumb) {
    $thumbRelativePath = str_replace($docRoot, '', $thumbPath);
    $thumbRelativePath = str_replace('\\', '/', $thumbRelativePath);
    $thumbUrl = $baseUrl . $thumbRelativePath;
}

// Nếu không có alt text, lấy từ tên file
if (empty($altText)) {
    $altText = pathinfo($file['name'], PATHINFO_FILENAME);
    $altText = str_replace(['_', '-'], ' ', $altText);
    $altText = preg_replace('/[^a-zA-Z0-9\p{L}\s]/u', '', $altText);
}

$response = [
    'uploaded' => true,
    'url' => $url,
    'thumb_url' => $thumbUrl,
    'alt' => $altText,
    'type' => $type,
    'filename' => $filename,
    'size' => $file['size']
];

// ========== SAVE TO DATABASE ==========

if (isset($conn) && $conn && !$conn->connect_error) {
    
    // Xử lý theo từng loại
    switch ($type) {
        case 'product_gallery':
            $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            if ($productId > 0) {
                // Lấy sort_order
                $sortResult = $conn->query("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_sort FROM product_images WHERE product_id = $productId");
                $sortOrder = 1;
                if ($sortResult && $row = $sortResult->fetch_assoc()) {
                    $sortOrder = $row['next_sort'];
                }
                
                // Nếu là ảnh chính hoặc chưa có ảnh chính nào
                if ($isMain == 1) {
                    // Đặt tất cả ảnh khác is_main = 0
                    $conn->query("UPDATE product_images SET is_main = 0 WHERE product_id = $productId");
                } else {
                    // Kiểm tra xem đã có ảnh chính chưa
                    $checkMain = $conn->query("SELECT id FROM product_images WHERE product_id = $productId AND is_main = 1 LIMIT 1");
                    if ($checkMain->num_rows == 0) {
                        $isMain = 1; // Ảnh đầu tiên sẽ là ảnh chính
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, thumb_url, alt_text, is_main, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssii", $productId, $url, $thumbUrl, $altText, $isMain, $sortOrder);
                if ($stmt->execute()) {
                    $response['image_id'] = $conn->insert_id;
                }
                $stmt->close();
            }
            break;
            
        case 'product_desc':
            // Lưu vào bảng ảnh mô tả sản phẩm
            $stmt = $conn->prepare("INSERT INTO product_desc_images (image_url, thumb_url, alt_text, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $url, $thumbUrl, $altText);
            if ($stmt->execute()) {
                $response['desc_image_id'] = $conn->insert_id;
            }
            $stmt->close();
            break;
            
        case 'post_gallery':
            $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
            if ($postId > 0) {
                $sortResult = $conn->query("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_sort FROM post_images WHERE post_id = $postId");
                $sortOrder = 1;
                if ($sortResult && $row = $sortResult->fetch_assoc()) {
                    $sortOrder = $row['next_sort'];
                }
                
                $stmt = $conn->prepare("INSERT INTO post_images (post_id, image_url, thumb_url, alt_text, sort_order, is_cover) VALUES (?, ?, ?, ?, ?, ?)");
                $isCover = $isMain;
                $stmt->bind_param("isssii", $postId, $url, $thumbUrl, $altText, $sortOrder, $isCover);
                if ($stmt->execute()) {
                    $response['image_id'] = $conn->insert_id;
                }
                $stmt->close();
            }
            break;
            
        case 'post_thumbnail':
            $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
            if ($postId > 0) {
                // Lấy thumbnail cũ để xóa
                $oldThumb = $conn->query("SELECT thumbnail FROM posts WHERE id = $postId")->fetch_assoc();
                if ($oldThumb && $oldThumb['thumbnail']) {
                    $oldPath = str_replace($baseUrl, $docRoot, $oldThumb['thumbnail']);
                    deleteOldFile($oldPath);
                }
                
                // Cập nhật thumbnail cho bài viết
                $stmt = $conn->prepare("UPDATE posts SET thumbnail = ?, alt_text = ? WHERE id = ?");
                $stmt->bind_param("ssi", $url, $altText, $postId);
                $stmt->execute();
                $stmt->close();
            }
            break;
            
        case 'category':
            $categoryId = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            if ($categoryId > 0) {
                // Lấy ảnh cũ để xóa
                $oldImg = $conn->query("SELECT image FROM categories WHERE id = $categoryId")->fetch_assoc();
                if ($oldImg && $oldImg['image']) {
                    $oldPath = str_replace($baseUrl, $docRoot, $oldImg['image']);
                    deleteOldFile($oldPath);
                }
                
                $stmt = $conn->prepare("UPDATE categories SET image = ? WHERE id = ?");
                $stmt->bind_param("si", $url, $categoryId);
                $stmt->execute();
                $stmt->close();
            }
            break;
            
        case 'banner':
            // Bảng banners cần có cột image_url và alt_text
            $bannerId = isset($_POST['banner_id']) ? intval($_POST['banner_id']) : 0;
            if ($bannerId > 0) {
                $stmt = $conn->prepare("UPDATE banners SET image_url = ?, alt_text = ? WHERE id = ?");
                $stmt->bind_param("ssi", $url, $altText, $bannerId);
                $stmt->execute();
                $stmt->close();
            } else {
                // Thêm mới banner
                $title = $_POST['title'] ?? '';
                $link = $_POST['link'] ?? '';
                $stmt = $conn->prepare("INSERT INTO banners (title, image_url, alt_text, link, status) VALUES (?, ?, ?, ?, 1)");
                $stmt->bind_param("ssss", $title, $url, $altText, $link);
                $stmt->execute();
                $stmt->close();
            }
            break;
    }
}

// ========== RETURN RESPONSE ==========

echo json_encode($response);
exit;
?>