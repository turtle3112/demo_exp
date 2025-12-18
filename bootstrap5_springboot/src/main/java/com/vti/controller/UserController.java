package com.vti.controller;

import com.vti.model.User;
import com.vti.repository.UserRepository;
import com.vti.service.AuditLogService;
import com.vti.service.PasswordResetService;
import com.vti.service.EmailService;
import dto.ForgotPasswordRequest;
import dto.VerifyOtpRequest;
import dto.ResetPasswordRequest;

import org.springframework.security.core.Authentication;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.web.bind.annotation.*;

import java.security.Principal;
import java.util.List;
import java.util.Map;
import java.util.Optional;

@RestController
@RequestMapping("/users")
public class UserController {

    private final UserRepository userRepository;
    private final AuditLogService auditLogService;
    private final PasswordEncoder passwordEncoder;
    private final PasswordResetService passwordResetService;
    private final EmailService emailService;

    public UserController(UserRepository userRepository, AuditLogService auditLogService, 
                         PasswordEncoder passwordEncoder, PasswordResetService passwordResetService,
                         EmailService emailService) {
        this.userRepository = userRepository;
        this.auditLogService = auditLogService;
        this.passwordEncoder = passwordEncoder;
        this.passwordResetService = passwordResetService;
        this.emailService = emailService;
    }

    @GetMapping
    @PreAuthorize("hasRole('ADMIN')")
    public ResponseEntity<List<User>> getAllUsers() {
        return ResponseEntity.ok(userRepository.findAll());
    }

    @GetMapping("/{id}")
    @PreAuthorize("hasRole('ADMIN')")
    public ResponseEntity<User> getUserById(@PathVariable Integer id) {
        User user = userRepository.findById(id).orElseThrow();
        return ResponseEntity.ok(user);
    }

    @PutMapping("/{id}")
    @PreAuthorize("hasRole('ADMIN')")
    public ResponseEntity<User> updateUser(@PathVariable Integer id, @RequestBody User updatedUser, Principal principal) {
        User user = userRepository.findById(id).orElseThrow();
        user.setFullName(updatedUser.getFullName());
        user.setRole(updatedUser.getRole());
        user.setEmployeeId(updatedUser.getEmployeeId());

        User saved = userRepository.save(user);
        auditLogService.log(principal.getName(), "UPDATE", "User", id, "Cập nhật thông tin user ID " + id);

        return ResponseEntity.ok(saved);
    }

    @PutMapping("/{id}/password")
    @PreAuthorize("hasRole('ADMIN')")
    public ResponseEntity<Void> updatePassword(@PathVariable Integer id, @RequestBody Map<String, String> body, Principal principal) {
        String newPassword = body.get("newPassword");
        if (newPassword == null || newPassword.isBlank()) {
            return ResponseEntity.badRequest().build();
        }

        User user = userRepository.findById(id).orElseThrow();
        user.setPassword(passwordEncoder.encode(newPassword));
        userRepository.save(user);

        auditLogService.log(principal.getName(), "PASSWORD_CHANGE", "User", id, "Thay đổi mật khẩu user ID " + id);
        return ResponseEntity.noContent().build();
    }

    @DeleteMapping("/{id}")
    @PreAuthorize("hasRole('ADMIN')")
    public ResponseEntity<Void> deleteUser(@PathVariable Integer id, Principal principal) {
        userRepository.deleteById(id);
        auditLogService.log(principal.getName(), "DELETE", "User", id, "Xoá user ID " + id);
        return ResponseEntity.noContent().build();
    }

    @GetMapping("/current")
    @PreAuthorize("isAuthenticated()")
    public ResponseEntity<?> getCurrentUser(Authentication authentication) {
        try {
            String username = authentication.getName();
            User user = userRepository.findByUsername(username)
                    .orElseThrow(() -> new RuntimeException("User not found"));
            return ResponseEntity.ok(user);
        } catch (Exception e) {
            return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                    .body(Map.of("error", "Cannot get user information"));
        }
    }

    @PutMapping("/profile/update")
    @PreAuthorize("hasAnyRole('ADMIN', 'EMPLOYEE', 'MEMBER')")
    public ResponseEntity<?> updateProfileAndPassword(@RequestBody Map<String, String> request, Principal principal) {
        try {
            String username = principal.getName();
            User currentUser = userRepository.findByUsername(username)
                    .orElseThrow(() -> new RuntimeException("User not found"));

            if (request.containsKey("email")) {
                String newEmail = request.get("email");
                Optional<User> existingUser = userRepository.findByEmail(newEmail);
                if (existingUser.isPresent() && !existingUser.get().getId().equals(currentUser.getId())) {
                    return ResponseEntity.badRequest().body(Map.of("error", "Email đã được sử dụng bởi người dùng khác"));
                }
                currentUser.setEmail(newEmail);
            }

            if (request.containsKey("password") && request.containsKey("confirmPassword")) {
                String password = request.get("password");
                String confirmPassword = request.get("confirmPassword");
                if (!password.equals(confirmPassword)) {
                    return ResponseEntity.badRequest().body(Map.of("error", "Mật khẩu và xác nhận mật khẩu không khớp"));
                }
                if (!password.isBlank()) {
                    currentUser.setPassword(passwordEncoder.encode(password));
                }
            }

            User savedUser = userRepository.save(currentUser);
            auditLogService.log(principal.getName(), "UPDATE", "User", savedUser.getId(), "Cập nhật thông tin cá nhân và mật khẩu");

            return ResponseEntity.ok(Map.of(
                "message", "Cập nhật thông tin thành công",
                "user", Map.of(
                    "username", savedUser.getUsername(),
                    "email", savedUser.getEmail(),
                    "fullName", savedUser.getFullName()
                )
            ));
        } catch (Exception e) {
            return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                    .body(Map.of("error", "Lỗi khi cập nhật thông tin: " + e.getMessage()));
        }
    }
    
    @PostMapping("/profile/check-password")
    public ResponseEntity<?> checkPassword(@RequestBody Map<String, String> body, Principal principal) {
        String inputPassword = body.get("password");

        // ✔ Sửa Optional -> User
        User user = userRepository.findByUsername(principal.getName())
                .orElseThrow(() -> new RuntimeException("User not found"));

        boolean match = passwordEncoder.matches(inputPassword, user.getPassword());

        return ResponseEntity.ok(Map.of("valid", match));
    }



    @GetMapping("/profile")
    @PreAuthorize("hasAnyRole('ADMIN', 'EMPLOYEE', 'MEMBER')")
    public ResponseEntity<?> getCurrentUserProfile(Principal principal) {
        try {
            String username = principal.getName();
            User user = userRepository.findByUsername(username)
                    .orElseThrow(() -> new RuntimeException("User not found"));
            return ResponseEntity.ok(Map.of(
                "username", user.getUsername(),
                "email", user.getEmail(),
                "fullName", user.getFullName() != null ? user.getFullName() : "",
                "employeeId", user.getEmployeeId() != null ? user.getEmployeeId() : ""
            ));
        } catch (Exception e) {
            return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                    .body(Map.of("error", "Không thể lấy thông tin người dùng"));
        }
    }

    // ==================== QUÊN MẬT KHẨU - GỬI OTP (SỬ DỤNG DTO) ====================
    @PostMapping("/forgot-password")
    public ResponseEntity<?> forgotPassword(@RequestBody ForgotPasswordRequest request) {
        try {
            String email = request.getEmail();
            
            if (email == null || email.trim().isEmpty()) {
                return ResponseEntity.badRequest().body(Map.of("message", "Email không được để trống"));
            }

            // Sử dụng phương thức validation từ DTO
            if (!request.isValid()) {
                return ResponseEntity.badRequest().body(Map.of("message", "Email không hợp lệ"));
            }

            Optional<User> userOpt = userRepository.findByEmail(email);
            
            // 🎯 IN THÔNG TIN DEBUG RA CONSOLE
            System.out.println("\n" + "🔍".repeat(40));
            System.out.println("🚀 FORGOT PASSWORD REQUEST RECEIVED");
            System.out.println("📧 Email received: " + email);
            System.out.println("👤 User exists: " + userOpt.isPresent());
            
            if (userOpt.isPresent()) {
                User user = userOpt.get();
                System.out.println("📋 User details:");
                System.out.println("   - Username: " + user.getUsername());
                System.out.println("   - Full Name: " + user.getFullName());
                System.out.println("   - Account Status: " + user.getAccountStatus());
                System.out.println("   - Can reset password: " + user.canResetPassword());
            } else {
                System.out.println("⚠️  No user found with email: " + email);
            }
            System.out.println("🔍".repeat(40) + "\n");
            
            if (userOpt.isEmpty()) {
                // Trả về thành công ngay cả khi email không tồn tại (bảo mật)
                return ResponseEntity.ok(Map.of("message", "Nếu email tồn tại, mã OTP sẽ được gửi đến email của bạn"));
            }

            // Kiểm tra user có thể reset password không
            User user = userOpt.get();
            if (!user.canResetPassword()) {
                System.out.println("❌ User cannot reset password - Account status: " + user.getAccountStatus());
                return ResponseEntity.badRequest().body(Map.of("message", "Tài khoản của bạn không thể đặt lại mật khẩu. Vui lòng liên hệ quản trị viên."));
            }

            String otp = passwordResetService.generateOTP(email);
            emailService.sendOTPEmail(email, otp);

            return ResponseEntity.ok(Map.of("message", "Nếu email tồn tại, mã OTP sẽ được gửi đến email của bạn"));

        } catch (Exception e) {
            System.err.println("❌ ERROR in forgotPassword: " + e.getMessage());
            e.printStackTrace();
            return ResponseEntity.internalServerError()
                    .body(Map.of("message", "Lỗi hệ thống: " + e.getMessage()));
        }
    }

    // ==================== XÁC THỰC OTP (SỬ DỤNG DTO) ====================
    @PostMapping("/verify-otp")
    public ResponseEntity<?> verifyOTP(@RequestBody VerifyOtpRequest request) {
        try {
            String email = request.getEmail();
            String otp = request.getOtp();

            if (email == null || otp == null) {
                return ResponseEntity.badRequest().body(Map.of("message", "Email và OTP không được để trống"));
            }

            // Sử dụng phương thức validation từ DTO
            if (!request.isValid()) {
                return ResponseEntity.badRequest().body(Map.of("message", "OTP phải là 6 chữ số"));
            }

            // 🎯 IN THÔNG TIN DEBUG RA CONSOLE
            System.out.println("\n" + "🔐".repeat(30));
            System.out.println("📨 VERIFY OTP REQUEST");
            System.out.println("📧 Email: " + email);
            System.out.println("🔢 OTP: " + otp);
            System.out.println("🔐".repeat(30) + "\n");

            boolean isValid = passwordResetService.verifyOTP(email, otp);
            if (isValid) {
                System.out.println("✅ OTP VALID for email: " + email);
                return ResponseEntity.ok(Map.of("message", "OTP hợp lệ"));
            } else {
                System.out.println("❌ OTP INVALID for email: " + email);
                return ResponseEntity.badRequest().body(Map.of("message", "OTP không hợp lệ hoặc đã hết hạn"));
            }

        } catch (Exception e) {
            System.err.println("❌ ERROR in verifyOTP: " + e.getMessage());
            e.printStackTrace();
            return ResponseEntity.internalServerError()
                    .body(Map.of("message", "Lỗi hệ thống: " + e.getMessage()));
        }
    }

    // ==================== ĐẶT LẠI MẬT KHẨU (SỬ DỤNG DTO) ====================
    @PostMapping("/reset-password")
    public ResponseEntity<?> resetPassword(@RequestBody ResetPasswordRequest request) {
        try {
            String email = request.getEmail();
            String otp = request.getOtp();
            String newPassword = request.getNewPassword();

            if (email == null || otp == null || newPassword == null) {
                return ResponseEntity.badRequest().body(Map.of("message", "Thiếu thông tin bắt buộc"));
            }

            // Sử dụng phương thức validation từ DTO
            if (!request.isValid()) {
                return ResponseEntity.badRequest().body(Map.of("message", "Mật khẩu phải có ít nhất 8 ký tự và OTP phải là 6 chữ số"));
            }

            // 🎯 IN THÔNG TIN DEBUG RA CONSOLE
            System.out.println("\n" + "🔄".repeat(35));
            System.out.println("🔄 RESET PASSWORD REQUEST");
            System.out.println("📧 Email: " + email);
            System.out.println("🔢 OTP: " + otp);
            System.out.println("🔑 New Password: " + "•".repeat(newPassword.length()));
            System.out.println("🔄".repeat(35) + "\n");

            // Xác thực OTP trước
            if (!passwordResetService.verifyOTP(email, otp)) {
                System.out.println("❌ OTP verification FAILED for email: " + email);
                return ResponseEntity.badRequest().body(Map.of("message", "OTP không hợp lệ"));
            }

            // Tìm user bằng email
            User user = userRepository.findByEmail(email)
                    .orElseThrow(() -> new RuntimeException("Không tìm thấy user với email: " + email));

            // Cập nhật mật khẩu
            user.setPassword(passwordEncoder.encode(newPassword));
            userRepository.save(user);

            // Xóa OTP sau khi sử dụng
            passwordResetService.clearOTP(email);

            // Ghi log
            auditLogService.log(user.getUsername(), "UPDATE", "User", user.getId(),
                    "Đặt lại mật khẩu qua chức năng quên mật khẩu");

            System.out.println("✅ PASSWORD RESET SUCCESSFUL for user: " + user.getUsername());
            
            // Gửi email thông báo thành công
            emailService.sendPasswordResetSuccessEmail(email, user.getUsername());

            return ResponseEntity.ok(Map.of("message", "Mật khẩu đã được đặt lại thành công"));

        } catch (Exception e) {
            System.err.println("❌ ERROR in resetPassword: " + e.getMessage());
            e.printStackTrace();
            return ResponseEntity.internalServerError()
                    .body(Map.of("message", "Lỗi khi đặt lại mật khẩu: " + e.getMessage()));
        }
    }

    // ==================== API TEST/DEBUG ====================
    @GetMapping("/debug/otp-status/{email}")
    public ResponseEntity<?> debugOtpStatus(@PathVariable String email) {
        try {
            boolean hasValidOTP = passwordResetService.hasValidOTP(email);
            long timeLeft = passwordResetService.getOTPTimeLeft(email);
            
            Map<String, Object> response = Map.of(
                "email", email,
                "hasValidOTP", hasValidOTP,
                "timeLeftSeconds", timeLeft,
                "timeLeftFormatted", String.format("%02d:%02d", timeLeft / 60, timeLeft % 60)
            );
            
            System.out.println("\n🔍 DEBUG OTP STATUS: " + response + "\n");
            
            return ResponseEntity.ok(response);
        } catch (Exception e) {
            return ResponseEntity.internalServerError()
                    .body(Map.of("error", e.getMessage()));
        }
    }
}