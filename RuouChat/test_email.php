<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    // Cấu hình SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'turtlenink@gmail.com';      // 👈 Thay bằng email của bạn
    $mail->Password   = 'jtlj ejwn vvhm qsig';         // 👈 Mật khẩu ứng dụng 16 ký tự
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // hoặc 'ssl' với port 465
    $mail->Port       = 587;

    // Người gửi và người nhận
    $mail->setFrom('no-reply@gmail.com', 'Ruouchat');
    $mail->addAddress('viet081204@gmail.com', 'Test'); // 👈 Thay email nhận

    // Nội dung
    $mail->isHTML(true);
    $mail->Subject = 'Test email from Ruouchat';
    $mail->Body    = '<h1>Chào bạn</h1><p>Đây là email test gửi từ PHPMailer.</p>';
    $mail->AltBody = 'Nội dung thuần text';

    $mail->send();
    echo '✅ Gửi email thành công!';
} catch (Exception $e) {
    echo "❌ Gửi thất bại. Lỗi: {$mail->ErrorInfo}";
}
?>