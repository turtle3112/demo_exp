<?php
// api/auth.php
require_once 'config.php';  // config.php phải có session_start() và kết nối $conn

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// ==================== ĐĂNG KÝ ====================
if ($action === 'register') {
    $fullname = trim($input['fullname'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $password = $input['password'] ?? '';

    $errors = [];
    if (empty($fullname)) $errors[] = 'Họ tên không được để trống';
    if (empty($email)) $errors[] = 'Email không được để trống';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không đúng định dạng';
    if (!empty($phone) && !preg_match('/^(0[3|5|7|8|9])[0-9]{8}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ (10 số, bắt đầu bằng 03,05,07,08,09)';
    if (empty($password)) $errors[] = 'Mật khẩu không được để trống';
    if (strlen($password) < 6) $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => implode(', ', $errors)]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Email đã được đăng ký']);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, fullname, phone, role, created_at) VALUES (?, ?, ?, ?, ?, 'user', NOW())");
    $stmt->bind_param("sssss", $email, $email, $hashed, $fullname, $phone);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi database: ' . $conn->error]);
    }
    exit;
}

// ==================== ĐĂNG NHẬP ====================
elseif ($action === 'login') {
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Vui lòng nhập email và mật khẩu']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'] ?? 'user';
        $_SESSION['user_fullname'] = $user['fullname'] ?? '';
        $_SESSION['user_phone'] = $user['phone'] ?? '';
        $_SESSION['user_address'] = $user['address'] ?? '';

        // *** THÊM: Nếu role là admin thì set session admin_logged_in ***
        if (($user['role'] ?? '') === 'admin') {
            $_SESSION['admin_logged_in'] = true;
        }

        unset($user['password']);
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Email hoặc mật khẩu không đúng']);
    }
    exit;
}

// ==================== ĐĂNG XUẤT ====================
elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// ==================== KIỂM TRA ĐĂNG NHẬP (luôn đồng bộ) ====================
elseif ($action === 'check') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['logged_in' => false]);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, email, role, fullname, phone, address FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_fullname'] = $user['fullname'];
        $_SESSION['user_phone'] = $user['phone'] ?? '';
        $_SESSION['user_address'] = $user['address'] ?? '';

        // Cập nhật admin_logged_in dựa trên role hiện tại
        if ($user['role'] === 'admin') {
            $_SESSION['admin_logged_in'] = true;
        } else {
            unset($_SESSION['admin_logged_in']);
        }

        echo json_encode([
            'logged_in' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'fullname' => $user['fullname'],
                'phone' => $user['phone'] ?? '',
                'address' => $user['address'] ?? ''
            ]
        ]);
    } else {
        session_destroy();
        echo json_encode(['logged_in' => false]);
    }
    exit;
}

// ==================== CẬP NHẬT THÔNG TIN TÀI KHOẢN ====================
elseif ($action === 'update_profile') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $fullname = trim($input['fullname'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? '');

    if (empty($fullname)) {
        http_response_code(400);
        echo json_encode(['error' => 'Họ tên không được để trống']);
        exit;
    }

    if (!empty($phone) && !preg_match('/^(0[3|5|7|8|9])[0-9]{8}$/', $phone)) {
        http_response_code(400);
        echo json_encode(['error' => 'Số điện thoại không hợp lệ']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->bind_param("sssi", $fullname, $phone, $address, $user_id);
    if ($stmt->execute()) {
        $_SESSION['user_fullname'] = $fullname;
        $_SESSION['user_phone'] = $phone;
        $_SESSION['user_address'] = $address;
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi cập nhật: ' . $conn->error]);
    }
    exit;
}

// ==================== CẬP NHẬT MẬT KHẨU ====================
elseif ($action === 'change_password') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $old_password = $input['old_password'] ?? '';
    $new_password = $input['new_password'] ?? '';

    if (empty($old_password) || empty($new_password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Vui lòng nhập đầy đủ mật khẩu']);
        exit;
    }
    if (strlen($new_password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Mật khẩu mới phải có ít nhất 6 ký tự']);
        exit;
    }

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($old_password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Mật khẩu cũ không đúng']);
        exit;
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi cập nhật: ' . $conn->error]);
    }
    exit;
}

else {
    http_response_code(400);
    echo json_encode(['error' => 'Action không hợp lệ']);
}
?>