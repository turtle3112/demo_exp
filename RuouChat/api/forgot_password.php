<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Đảm bảo PHP dùng UTF-8
mb_internal_encoding('UTF-8');

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email.']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Email không tồn tại trong hệ thống.']);
    exit;
}

// Tạo token và thời gian hết hạn
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

$update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
$update->execute([$token, $expires, $user['id']]);

// Tạo link reset (đường dẫn đầy đủ, đúng định dạng)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/');
$resetLink = "$protocol://$host$path/reset_password.php?token=" . urlencode($token);

// ========== GỬI EMAIL ==========
$mail = new PHPMailer(true);
try {
    // Cấu hình SMTP (thay bằng thông tin thật của bạn)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'turtlenink@gmail.com';   // 👈 Thay email của bạn
    $mail->Password   = 'jtlj ejwn vvhm qsig';      // 👈 Mật khẩu ứng dụng
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // ===== QUAN TRỌNG: set charset UTF-8 =====
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    $mail->setFrom('turtlenink@gmail.com', 'Ruouchat.vn');
    $mail->addAddress($email, $user['fullname']);

    $mail->isHTML(true);
    $mail->Subject = '=?UTF-8?B?' . base64_encode('Đặt lại mật khẩu - Ruouchat') . '?=';

    // Nội dung HTML (đã đảm bảo tiếng Việt)
    $mail->Body = "
    <!DOCTYPE html>
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Xin chào {$user['fullname']},</h2>
        <p>Nhấp vào link bên dưới để đặt lại mật khẩu (có hiệu lực 1 giờ):</p>
        <p><a href='$resetLink'>$resetLink</a></p>
        <p>Nếu không phải bạn, vui lòng bỏ qua email này.</p>
        <p>Trân trọng,<br>Đội ngũ Ruouchat.vn</p>
    </body>
    </html>
    ";
    $mail->AltBody = "Đặt lại mật khẩu: $resetLink";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Đã gửi thành công, vui lòng kiểm tra email.']);
} catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'message' => 'Không thể gửi email. Lỗi: ' . $mail->ErrorInfo]);
}
?>