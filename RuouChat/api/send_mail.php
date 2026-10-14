<?php
// api/send_mail.php - Gửi email reset mật khẩu bằng PHPMailer
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Đường dẫn đến autoload của PHPMailer (sau khi cài qua Composer)
require_once __DIR__ . '/../vendor/autoload.php';

function sendResetEmail($to, $token) {
    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'], 2) . "/reset_password.php?token=$token";
    $subject = "Đặt lại mật khẩu - Ruouchat";
    
    // Nội dung email (HTML)
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Đặt lại mật khẩu</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .btn { display: inline-block; padding: 12px 24px; background: #990D23; color: white; text-decoration: none; border-radius: 5px; }
            .btn:hover { background: #670011; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Xin chào,</h2>
            <p>Bạn vừa yêu cầu đặt lại mật khẩu cho tài khoản tại <strong>Ruouchat.vn</strong>.</p>
            <p>Vui lòng nhấp vào nút bên dưới để tạo mật khẩu mới:</p>
            <p style='text-align: center;'>
                <a href='$resetLink' class='btn'>ĐẶT LẠI MẬT KHẨU</a>
            </p>
            <p>Nếu nút không hoạt động, bạn có thể sao chép và dán link sau vào trình duyệt:<br>
            <a href='$resetLink'>$resetLink</a></p>
            <p>Liên kết này có hiệu lực trong <strong>1 giờ</strong>.</p>
            <p>Nếu bạn không yêu cầu, vui lòng bỏ qua email này.</p>
            <br>
            <p>Trân trọng,<br>Đội ngũ Ruouchat</p>
        </div>
    </body>
    </html>
    ";
    
    $mail = new PHPMailer(true);
    
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';          // SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'YOUR_EMAIL@gmail.com';    // THAY bằng email Gmail của bạn
        $mail->Password   = 'YOUR_APP_PASSWORD';       // THAY bằng mật khẩu ứng dụng 16 ký tự
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // hoặc PHPMailer::ENCRYPTION_SMTPS (cho SSL port 465)
        $mail->Port       = 587;                       // Port TLS (hoặc 465 nếu dùng SSL)
        
        // Người gửi và người nhận
        $mail->setFrom('YOUR_EMAIL@gmail.com', 'Ruouchat.vn');
        $mail->addAddress($to);                        // Email người nhận
        
        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);         // Nội dung thuần cho client không hỗ trợ HTML
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Ghi log lỗi để dễ dàng debug
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>