package com.vti.security;

import com.vti.model.Organization;
import com.vti.repository.OrganizationRepository;
import com.vti.model.User;
import com.vti.repository.UserRepository;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.security.authentication.AuthenticationManager;
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.web.bind.annotation.*;

import java.time.LocalDateTime;
import java.util.Map;
import java.util.HashMap;

@RestController
@RequestMapping("/auth")
public class AuthController {
	private final AuthenticationManager authenticationManager;
	private final JwtTokenProvider jwtTokenProvider;
	private final UserRepository userRepository;
	private final PasswordEncoder passwordEncoder;
	   private final OrganizationRepository organizationRepository; //
	

	    public AuthController(AuthenticationManager authenticationManager, 
                JwtTokenProvider jwtTokenProvider,
                UserRepository userRepository, 
                PasswordEncoder passwordEncoder,
                OrganizationRepository organizationRepository) { 
			this.authenticationManager = authenticationManager;
			this.jwtTokenProvider = jwtTokenProvider;
			this.userRepository = userRepository;
			this.passwordEncoder = passwordEncoder;
			this.organizationRepository = organizationRepository; // 
		    System.out.println("=== AUTH CONTROLLER DEBUG ===");
		    System.out.println("UserRepository: " + (userRepository != null ? "OK" : "NULL"));
		    System.out.println("OrganizationRepository: " + (organizationRepository != null ? "OK" : "NULL"));
		    System.out.println("PasswordEncoder: " + (passwordEncoder != null ? "OK" : "NULL"));
}

	    @PostMapping("/login")
	    public ResponseEntity<?> login(@RequestBody Map<String, String> loginRequest) {
	        String username = loginRequest.get("username");
	        String password = loginRequest.get("password");

	        authenticationManager.authenticate(new UsernamePasswordAuthenticationToken(username, password));

	        String token = jwtTokenProvider.generateToken(username);
	        User user = userRepository.findByUsername(username).orElseThrow();

	        // Tạo response user với đầy đủ thông tin
	        Map<String, Object> userResponse = new HashMap<>();
	        userResponse.put("id", user.getId());
	        userResponse.put("username", user.getUsername());
	        userResponse.put("fullName", user.getFullName());
	        
	        // ✅ SỬA LỖI: Xử lý khi role = null
	        userResponse.put("role", user.getRole() != null ? user.getRole().name() : null);
	        
	        // THÊM CÁC TRƯỜNG MỚI VÀO ĐÂY
	        userResponse.put("email", user.getEmail());
	        userResponse.put("accountType", user.getAccountType() != null ? user.getAccountType() : "PERSONAL");
	        userResponse.put("accountStatus", user.getAccountStatus() != null ? user.getAccountStatus().name() : "TRIAL_ACTIVE");
	        userResponse.put("trialStartDate", user.getTrialStartDate());
	        userResponse.put("trialEndDate", user.getTrialEndDate());
	        // === SỬA LẠI - Lấy organization name từ organization entity ===
	        userResponse.put("organizationName", user.getOrganization() != null ? user.getOrganization().getName() : null);
	        userResponse.put("organizationId", user.getOrganization() != null ? user.getOrganization().getId() : null);
	        
	        return ResponseEntity.ok(Map.of(
	                "token", token, 
	                "user", userResponse
	                ));
	    }
	@PreAuthorize("permitAll()")
	// ĐĂNG KÝ CÔNG KHAI - không yêu cầu xác thực
	@PostMapping("/public/register")
	public ResponseEntity<?> publicRegister(@RequestBody Map<String, String> request) {
	    try {
	        System.out.println("=== DEBUG REGISTRATION START ===");
	        
	        String username = request.get("username");
	        String password = request.get("password");
	        String fullName = request.get("fullName");
	        String email = request.get("email");
	        String accountType = request.get("accountType");
	        String organizationName = request.get("organizationName");

	        System.out.println("DEBUG: Registration data - username: " + username);

	        // Validate dữ liệu (giữ nguyên)
	        if (username == null || username.isBlank()) {
	            return ResponseEntity.badRequest().body("Username không được để trống");
	        }
	        // ... các validate khác giữ nguyên

	        // Kiểm tra username và email đã tồn tại
	        if (userRepository.findByUsername(username).isPresent()) {
	            return ResponseEntity.badRequest().body("Username đã tồn tại");
	        }
	        if (userRepository.findByEmail(email).isPresent()) {
	            return ResponseEntity.badRequest().body("Email đã tồn tại");
	        }

	        // Tạo user mới
	        User user = new User();
	        user.setUsername(username);
	        user.setPassword(passwordEncoder.encode(password));
	        user.setFullName(fullName);
	        user.setEmail(email);
	        
	        String upperAccountType = accountType.toUpperCase();
	        user.setAccountType(upperAccountType);

	        user.setTrialStartDate(LocalDateTime.now());
	        user.setTrialEndDate(LocalDateTime.now().plusDays(3));
	        user.setAccountStatus(User.AccountStatus.TRIAL_ACTIVE);

	        // XỬ LÝ ORGANIZATION - GIẢI QUYẾT VÒNG TRÒN
	        if ("PERSONAL".equals(upperAccountType)) {
	        	user.setRole(User.Role.EMPLOYEE);
	            user.setOrganization(null);
	            
	            String employeeId = generateEmployeeId(upperAccountType);
	            user.setEmployeeId(employeeId);
	            
	            User savedUser = userRepository.save(user);
	            System.out.println("DEBUG: Personal user saved: " + savedUser.getId());
	            
	        } else {
	            // TEAM hoặc ENTERPRISE - GIẢI PHÁP MỚI
	            if (organizationName == null || organizationName.trim().isEmpty()) {
	                return ResponseEntity.badRequest().body("Tên tổ chức không được để trống");
	            }
	            
	            user.setRole(User.Role.ADMIN);
	            
	            // BƯỚC 1: Lưu user TẠM THỜI không có organization
	            String employeeId = generateEmployeeId(upperAccountType);
	            user.setEmployeeId(employeeId);
	            user.setOrganization(null); // QUAN TRỌNG: Tạm thời null
	            
	            User savedUser = userRepository.save(user);
	            System.out.println("DEBUG: User saved temporarily: " + savedUser.getId());
	            
	            // BƯỚC 2: Tạo organization với createdBy
	            Organization organization = new Organization();
	            organization.setName(organizationName);
	            organization.setType("TEAM".equals(upperAccountType) ? 
	                Organization.OrganizationType.TEAM : Organization.OrganizationType.ENTERPRISE);
	            organization.setDescription("Tổ chức được tạo tự động khi đăng ký");
	            organization.setCreatedBy(savedUser); // User đã được lưu
	            
	            Organization savedOrganization = organizationRepository.save(organization);
	            System.out.println("DEBUG: Organization saved: " + savedOrganization.getId());
	            
	            // BƯỚC 3: Cập nhật user với organization
	            savedUser.setOrganization(savedOrganization);
	            User finalUser = userRepository.save(savedUser);
	            System.out.println("DEBUG: User updated with organization: " + finalUser.getId());
	        }

	        System.out.println("=== DEBUG REGISTRATION SUCCESS ===");
	        return ResponseEntity.ok(Map.of(
	            "message", "Đăng ký thành công", 
	            "userId", user.getId(),
	            "accountType", user.getAccountType()
	        ));
	        
	    } catch (Exception e) {
	        System.err.println("=== REGISTRATION ERROR ===");
	        e.printStackTrace();
	        
	        // Trả về thông báo lỗi chi tiết hơn
	        String errorMsg = "Đăng ký thất bại: " + e.getMessage();
	        if (e.getCause() != null) {
	            errorMsg += " | Cause: " + e.getCause().getMessage();
	        }
	        return ResponseEntity.status(500).body(errorMsg);
	    }
	}
	
	private String generateEmployeeId(String accountType) {
	    String prefix = "PERS";
	    if ("TEAM".equalsIgnoreCase(accountType)) {
	        prefix = "TEAM";
	    } else if ("ENTERPRISE".equalsIgnoreCase(accountType)) {
	        prefix = "ENT";
	    }
	    return prefix + System.currentTimeMillis();
	}

}