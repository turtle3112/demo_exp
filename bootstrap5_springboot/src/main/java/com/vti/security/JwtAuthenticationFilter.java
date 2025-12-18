package com.vti.security;

import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.lang.NonNull;
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.security.core.authority.SimpleGrantedAuthority;
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.security.core.userdetails.UserDetailsService;
import org.springframework.security.web.authentication.WebAuthenticationDetailsSource;
import org.springframework.stereotype.Component;
import org.springframework.web.filter.OncePerRequestFilter;

// ✅ THÊM IMPORT CHO JWT
import io.jsonwebtoken.Claims;
import io.jsonwebtoken.Jwts;

import java.io.IOException;
import java.util.List;

@Component
public class JwtAuthenticationFilter extends OncePerRequestFilter {
    private final JwtTokenProvider jwtTokenProvider;
    private final UserDetailsService userDetailsService;

    public JwtAuthenticationFilter(JwtTokenProvider jwtTokenProvider, UserDetailsService userDetailsService) {
        this.jwtTokenProvider = jwtTokenProvider;
        this.userDetailsService = userDetailsService;
    }

    @Override
    protected void doFilterInternal(
            @NonNull HttpServletRequest request,
            @NonNull HttpServletResponse response, 
            @NonNull FilterChain filterChain)
            throws ServletException, IOException {

        System.out.println("=== JWT FILTER DEBUG ===");
        System.out.println("JWT Filter - Request to: " + request.getRequestURI());
        
        String path = request.getRequestURI();
        
        // Cho phép các endpoint công khai đi qua mà không cần xác thực
        if (path.equals("/auth/login") || 
            path.startsWith("/auth/public") ||
            path.startsWith("/css/") || 
            path.startsWith("/js/") ||
            path.startsWith("/assets/") || 
            path.equals("/favicon.ico")) {
            
            System.out.println("JWT Filter - Public endpoint: " + path + ", skipping JWT authentication");
            filterChain.doFilter(request, response);
            return;
        }

        String header = request.getHeader("Authorization");
        String token = null;
        
        if (header != null && header.startsWith("Bearer ")) {
            token = header.substring(7);
            System.out.println("JWT Filter - Token found: " + token.substring(0, 20) + "...");
            
            // ✅ SỬA: Sử dụng Claims class đúng cách
            try {
                // Parse token để xem claims - SỬA LỖI Ở ĐÂY
                Claims claims = Jwts.parserBuilder()
                    .setSigningKey(jwtTokenProvider.getKey())
                    .build()
                    .parseClaimsJws(token)
                    .getBody();
                
                System.out.println("Token claims: " + claims);
                System.out.println("Organization ID in token: " + claims.get("organizationId", Integer.class));
                System.out.println("Account Type in token: " + claims.get("accountType", String.class));
                System.out.println("User ID in token: " + claims.get("id", Integer.class));
                
                System.out.println("=== TOKEN CLAIMS DEBUG ===");
                System.out.println("Username: " + claims.getSubject());
                System.out.println("Roles: " + claims.get("roles"));
                System.out.println("AccountType: " + claims.get("accountType"));
                System.out.println("User ID: " + claims.get("id"));
            } catch (Exception e) {
                System.out.println("Error parsing token for debug: " + e.getMessage());
                e.printStackTrace();
            }
        } else {
            System.out.println("JWT Filter - No Bearer token found in Authorization header");
        }

     // Trong phần xử lý token, thêm debug:
        if (token != null && jwtTokenProvider.validateToken(token)) {
            try {
                String username = jwtTokenProvider.getUsernameFromToken(token);
                List<String> roles = jwtTokenProvider.getRolesFromToken(token);

                // ✅ THÊM DEBUG: Kiểm tra roles từ token
                System.out.println("=== TOKEN ROLES DEBUG ===");
                System.out.println("Roles from token: " + roles);
                
                UserDetails userDetails = userDetailsService.loadUserByUsername(username);
              
                System.out.println("UserDetails loaded: " + userDetails.getUsername());
                System.out.println("Authorities from UserDetails: " + userDetails.getAuthorities());
                
                // ✅ KIỂM TRA TỪNG AUTHORITY
                userDetails.getAuthorities().forEach(authority -> {
                    System.out.println("Authority: " + authority.getAuthority());
                });
                

                var authentication = new UsernamePasswordAuthenticationToken(
                    userDetails,
                    null, 
                    userDetails.getAuthorities()
                );
                
                authentication.setDetails(new WebAuthenticationDetailsSource().buildDetails(request));
                SecurityContextHolder.getContext().setAuthentication(authentication);
                
                System.out.println("✅ Authentication SUCCESS for: " + username);
                System.out.println("SecurityContext: " + SecurityContextHolder.getContext().getAuthentication());
                
            } catch (Exception e) {
                System.out.println("❌ JWT Filter - Error during authentication: " + e.getMessage());
                e.printStackTrace();
            }
        } else if (token != null) {
            System.out.println("❌ JWT Filter - Invalid token");
        } else {
            System.out.println("ℹ️ JWT Filter - No token provided");
        }


        filterChain.doFilter(request, response);
    }
}