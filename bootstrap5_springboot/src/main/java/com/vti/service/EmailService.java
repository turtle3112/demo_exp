package com.vti.service;

import org.springframework.stereotype.Service;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

@Service
public class EmailService {
    
    /**
     * Gửi email OTP cho chức năng quên mật khẩu - ĐÃ SỬA: In thông tin đẹp hơn
     */
    public void sendOTPEmail(String email, String otp) {
        String subject = "Mã OTP đặt lại mật khẩu - Task Management System";
        String content = buildOTPEmailContent(otp);
        
        System.out.println("\n" + "🎯".repeat(25));
        System.out.println("📤 DEMO - EMAIL OTP ĐÃ ĐƯỢC GỬI");
        System.out.println("📧 Đến: " + email);
        System.out.println("📝 Tiêu đề: " + subject);
        System.out.println("🔐 Mã OTP: " + otp);
        System.out.println("⏰ Thời gian: " + LocalDateTime.now().format(DateTimeFormatter.ofPattern("HH:mm:ss dd/MM/yyyy")));
        System.out.println("🎯".repeat(25));
        System.out.println("📄 Nội dung email:");
        System.out.println(content);
        System.out.println("🎯".repeat(25) + "\n");
    }
    
    /**
     * Xây dựng nội dung email OTP
     */
    private String buildOTPEmailContent(String otp) {
        return String.format("""
            Kính gửi Quý khách,
            
            Chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.
            
            🔐 MÃ OTP CỦA BẠN LÀ: %s
            
            ⚠️ Mã OTP có hiệu lực trong vòng 5 phút.
            ⚠️ Vui lòng không chia sẻ mã này với bất kỳ ai.
            
            Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.
            
            Trân trọng,
            Đội ngũ hỗ trợ Task Management System
            """, otp);
    }
    
    /**
     * Gửi email thông báo đặt lại mật khẩu thành công - ĐÃ THÊM
     */
    public void sendPasswordResetSuccessEmail(String email, String username) {
        String subject = "Mật khẩu đã được đặt lại thành công - Task Management System";
        String content = buildPasswordResetSuccessContent(username);
        
        System.out.println("\n" + "✅".repeat(25));
        System.out.println("📤 DEMO - PASSWORD RESET SUCCESS EMAIL");
        System.out.println("📧 Đến: " + email);
        System.out.println("👤 Username: " + username);
        System.out.println("📝 Tiêu đề: " + subject);
        System.out.println("⏰ Thời gian: " + LocalDateTime.now().format(DateTimeFormatter.ofPattern("HH:mm:ss dd/MM/yyyy")));
        System.out.println("✅".repeat(25));
        System.out.println("📄 Nội dung email:");
        System.out.println(content);
        System.out.println("✅".repeat(25) + "\n");
    }
    
    /**
     * Xây dựng nội dung email thông báo đặt lại mật khẩu thành công
     */
    private String buildPasswordResetSuccessContent(String username) {
        return String.format("""
            Kính gửi %s,
            
            Mật khẩu cho tài khoản của bạn đã được đặt lại thành công.
            
            Nếu bạn thực hiện thay đổi này, bạn có thể bỏ qua email này.
            
            Nếu bạn không thực hiện thay đổi này, vui lòng liên hệ ngay với đội ngũ hỗ trợ của chúng tôi.
            
            Trân trọng,
            Đội ngũ hỗ trợ Task Management System
            """, username);
    }
    
    // ... giữ nguyên các method khác nếu có
}