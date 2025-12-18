package com.vti.security;

import com.vti.model.User;
import com.vti.repository.UserRepository;
import io.jsonwebtoken.*;
import io.jsonwebtoken.security.Keys;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

import java.security.Key;
import java.util.Date;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

@Component
public class JwtTokenProvider {

    private final Key key = Keys.secretKeyFor(SignatureAlgorithm.HS512);
    private final long expiration = 86400000; // 1 ngày

    @Autowired
    private UserRepository userRepository;

    // ✅ SỬA: Sinh token với đầy đủ thông tin user
    public String generateToken(String username) {
        User user = userRepository.findByUsername(username)
                .orElseThrow(() -> new RuntimeException("User không tồn tại"));

        // ✅ SỬA: Xử lý khi role = null
        Map<String, Object> claims = new HashMap<>();
        
        // ✅ QUAN TRỌNG: Xử lý role null
        if (user.getRole() != null) {
            claims.put("roles", List.of("ROLE_" + user.getRole().name()));
        } else {
            claims.put("roles", List.of("ROLE_GUEST")); // Gán role mặc định
        }
        
        claims.put("id", user.getId());
        claims.put("fullName", user.getFullName());
        claims.put("email", user.getEmail());
        claims.put("accountType", user.getAccountType());
        claims.put("organizationId", user.getOrganization() != null ? user.getOrganization().getId() : null);
        claims.put("organizationName", user.getOrganization() != null ? user.getOrganization().getName() : null);
        claims.put("accountStatus", user.getAccountStatus().name());

        Date now = new Date();
        Date expiry = new Date(now.getTime() + expiration);

        return Jwts.builder()
                .setClaims(claims)
                .setSubject(username)
                .setIssuedAt(now)
                .setExpiration(expiry)
                .signWith(key)
                .compact();
    }
    
    public Key getKey() {
        return key;
    }

    // Lấy username từ token
    public String getUsernameFromToken(String token) {
        return Jwts.parserBuilder().setSigningKey(key).build().parseClaimsJws(token).getBody().getSubject();
    }

    // Lấy danh sách quyền (roles) từ token
    public List<String> getRolesFromToken(String token) {
        try {
            Claims claims = Jwts.parserBuilder().setSigningKey(key).build().parseClaimsJws(token).getBody();
            @SuppressWarnings("unchecked")
            List<String> roles = (List<String>) claims.get("roles");
            return roles != null ? roles : List.of("ROLE_GUEST"); // ✅ Fallback
        } catch (Exception e) {
            return List.of("ROLE_GUEST"); // ✅ Fallback khi có lỗi
        }
    }

    // ✅ THÊM: Lấy organizationId từ token
    public Integer getOrganizationIdFromToken(String token) {
        Claims claims = Jwts.parserBuilder().setSigningKey(key).build().parseClaimsJws(token).getBody();
        return claims.get("organizationId", Integer.class);
    }

    // ✅ THÊM: Lấy accountType từ token
    public String getAccountTypeFromToken(String token) {
        Claims claims = Jwts.parserBuilder().setSigningKey(key).build().parseClaimsJws(token).getBody();
        return claims.get("accountType", String.class);
    }

    // ✅ THÊM: Lấy userId từ token
    public Integer getUserIdFromToken(String token) {
        Claims claims = Jwts.parserBuilder().setSigningKey(key).build().parseClaimsJws(token).getBody();
        return claims.get("id", Integer.class);
    }

    // Kiểm tra token hợp lệ hay không
    public boolean validateToken(String token) {
        try {
            Jwts.parserBuilder().setSigningKey(key).build().parseClaimsJws(token);
            return true;
        } catch (JwtException | IllegalArgumentException e) {
            return false;
        }
    }
}