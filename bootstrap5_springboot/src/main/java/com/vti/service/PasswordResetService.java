package com.vti.service;

import org.springframework.stereotype.Service;

import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
import java.util.Random;

@Service
public class PasswordResetService {
    
    private final Map<String, OTPData> otpStorage = new ConcurrentHashMap<>();
    
    /**
     * Tạo mã OTP cho email - ĐÃ SỬA: In OTP rõ ràng ra console
     */
    public String generateOTP(String email) {
        String otp = String.format("%06d", new Random().nextInt(999999));
        OTPData otpData = new OTPData(otp, System.currentTimeMillis() + (5 * 60 * 1000)); // 5 phút
        otpStorage.put(email, otpData);
        
        // In OTP ra console với định dạng dễ nhìn
        System.out.println("\n" + "=".repeat(50));
        System.out.println("📧 EMAIL: " + email);
        System.out.println("🔐 MÃ OTP: " + otp);
        System.out.println("⏰ THỜI GIAN: " + new java.util.Date());
        System.out.println("⏳ HẾT HẠN: " + new java.util.Date(otpData.getExpiryTime()));
        System.out.println("=".repeat(50) + "\n");
        
        return otp;
    }
    
    /**
     * Xác thực mã OTP
     */
    public boolean verifyOTP(String email, String otp) {
        OTPData otpData = otpStorage.get(email);
        if (otpData == null) {
            System.out.println("❌ OTP not found for email: " + email);
            return false;
        }
        
        if (System.currentTimeMillis() > otpData.getExpiryTime()) {
            otpStorage.remove(email);
            System.out.println("❌ OTP expired for email: " + email);
            return false;
        }
        
        boolean isValid = otpData.getOtp().equals(otp);
        if (isValid) {
            System.out.println("✅ OTP verified successfully for email: " + email);
        } else {
            System.out.println("❌ OTP mismatch for email: " + email);
        }
        
        return isValid;
    }
    
    /**
     * Xóa OTP sau khi sử dụng
     */
    public void clearOTP(String email) {
        otpStorage.remove(email);
        System.out.println("🗑️ OTP cleared for email: " + email);
    }
    
    /**
     * Kiểm tra xem OTP có tồn tại và còn hiệu lực không
     */
    public boolean hasValidOTP(String email) {
        OTPData otpData = otpStorage.get(email);
        if (otpData == null) return false;
        
        if (System.currentTimeMillis() > otpData.getExpiryTime()) {
            otpStorage.remove(email);
            return false;
        }
        
        return true;
    }
    
    /**
     * Lấy thời gian còn lại của OTP (tính bằng giây)
     */
    public long getOTPTimeLeft(String email) {
        OTPData otpData = otpStorage.get(email);
        if (otpData == null) return 0;
        
        long timeLeft = (otpData.getExpiryTime() - System.currentTimeMillis()) / 1000;
        return Math.max(0, timeLeft);
    }
    
    /**
     * Inner class để lưu trữ thông tin OTP
     */
    private static class OTPData {
        private String otp;
        private long expiryTime;
        
        public OTPData(String otp, long expiryTime) {
            this.otp = otp;
            this.expiryTime = expiryTime;
        }
        
        public String getOtp() { return otp; }
        public long getExpiryTime() { return expiryTime; }
    }
}