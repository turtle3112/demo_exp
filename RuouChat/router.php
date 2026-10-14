<?php
// router.php
require_once __DIR__ . '/api/config.php';

$url = $_GET['url'] ?? '';
$url = trim($url, '/');
$segments = explode('/', $url);
$first = $segments[0] ?? '';
$second = $segments[1] ?? '';

// Hàm hiển thị 404 an toàn (không cần file 404.php)
function show404() {
    http_response_code(404);
    echo '<!DOCTYPE html>
    <html lang="vi">
    <head><meta charset="UTF-8"><title>404 - Không tìm thấy trang</title>
    <style>
        body{font-family:Arial,sans-serif;text-align:center;padding:50px;background:#f8f9fa;}
        h1{font-size:80px;color:#990D23;margin-bottom:20px;}
        p{font-size:18px;color:#333;margin-bottom:30px;}
        a{color:#990D23;text-decoration:none;font-weight:bold;}
        a:hover{text-decoration:underline;}
    </style>
    </head>
    <body>
    <h1>404</h1>
    <p>Xin lỗi, trang bạn yêu cầu không tồn tại hoặc đã bị di chuyển.</p>
    <a href="/winecellar/">← Quay lại trang chủ</a>
    </body></html>';
    exit;
}

switch ($first) {
    case '':
        require __DIR__ . '/index.php';
        break;

    case 'ruou-vang':
    case 'ruou-manh':
        // Các route này đã được xử lý trực tiếp trong .htaccess,
        // nên nếu vẫn vào đây thì có nghĩa là URL không đúng định dạng.
        if ($second === '') {
            $_GET['product_type'] = ($first === 'ruou-vang') ? 1 : 2;
            require __DIR__ . '/product_main.php';
        } else {
            $newUrl = '/' . $first . '/' . $second;
            header("Location: $newUrl", true, 301);
            exit;
        }
        break;

    case 'ly-pha-le':
        if ($second === '') {
            if (file_exists(__DIR__ . '/lyphale.php')) {
                require __DIR__ . '/lyphale.php';
            } else {
                show404();
            }
        } else {
            $_GET['slug'] = $second;
            $_GET['type'] = 'glass';
            if (file_exists(__DIR__ . '/sp2.php')) {
                require __DIR__ . '/sp2.php';
            } else {
                show404();
            }
        }
        break;

    case 'san-pham-khac':
        if ($second === '') {
            if (file_exists(__DIR__ . '/sanphamkhac.php')) {
                require __DIR__ . '/sanphamkhac.php';
            } else {
                show404();
            }
        } else {
            $_GET['slug'] = $second;
            $_GET['type'] = 'other';
            if (file_exists(__DIR__ . '/sp2.php')) {
                require __DIR__ . '/sp2.php';
            } else {
                show404();
            }
        }
        break;

    case 'kien-thuc':
        if ($second === '') {
            if (file_exists(__DIR__ . '/kienthuc.php')) {
                require __DIR__ . '/kienthuc.php';
            } else {
                show404();
            }
        } else {
            $_GET['slug'] = $second;
            if (file_exists(__DIR__ . '/kienthuc1.php')) {
                require __DIR__ . '/kienthuc1.php';
            } else {
                show404();
            }
        }
        break;

    case 'nha-san-xuat':
        if ($second !== '') {
            $_GET['slug'] = $second;
            if (file_exists(__DIR__ . '/nhasanxuat.php')) {
                require __DIR__ . '/nhasanxuat.php';
            } else {
                show404();
            }
        } else {
            show404();
        }
        break;

    case 'lien-he':
        if (file_exists(__DIR__ . '/lienhe.php')) {
            require __DIR__ . '/lienhe.php';
        } else {
            show404();
        }
        break;

    case 'dang-nhap':
        if (file_exists(__DIR__ . '/login.php')) {
            require __DIR__ . '/login.php';
        } else {
            show404();
        }
        break;

    case 'dang-ky':
        if (file_exists(__DIR__ . '/register.php')) {
            require __DIR__ . '/register.php';
        } else {
            show404();
        }
        break;

    case 'gio-hang':
        if (file_exists(__DIR__ . '/cart.php')) {
            require __DIR__ . '/cart.php';
        } else {
            show404();
        }
        break;

    case 'thanh-toan':
        if (file_exists(__DIR__ . '/checkout.php')) {
            require __DIR__ . '/checkout.php';
        } else {
            show404();
        }
        break;

    case 'tai-khoan':
        if (file_exists(__DIR__ . '/account.php')) {
            require __DIR__ . '/account.php';
        } else {
            show404();
        }
        break;

    case 'quatangdoanhnghiep':
        if (file_exists(__DIR__ . '/quatangdoanhnghiep.php')) {
            require __DIR__ . '/quatangdoanhnghiep.php';
        } else {
            show404();
        }
        break;

    case 'chinh-sach':
        if ($second !== '') $_GET['slug'] = $second;
        if (file_exists(__DIR__ . '/chinh-sach.php')) {
            require __DIR__ . '/chinh-sach.php';
        } else {
            show404();
        }
        break;

    // ========== THÊM LẠI CASE ADMIN ==========
    case 'admin':
        // Chuyển hướng đến file index trong thư mục admin
        if (file_exists(__DIR__ . '/admin/index.php')) {
            require __DIR__ . '/admin/index.php';
        } elseif (file_exists(__DIR__ . '/admin/index.html')) {
            require __DIR__ . '/admin/index.html';
        } else {
            show404();
        }
        break;

    default:
        // Fallback cho các file PHP trực tiếp (ví dụ /about, /contact...)
        $file = __DIR__ . '/' . $first . '.php';
        if (file_exists($file)) {
            require $file;
        } else {
            show404();
        }
        break;
}