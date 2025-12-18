package com.vti.security;

import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.http.HttpMethod;
import org.springframework.security.authentication.AuthenticationManager;
import org.springframework.security.config.annotation.authentication.configuration.AuthenticationConfiguration;
import org.springframework.security.config.annotation.method.configuration.EnableMethodSecurity;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.http.SessionCreationPolicy;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.web.SecurityFilterChain;
import org.springframework.security.web.authentication.UsernamePasswordAuthenticationFilter;
import org.springframework.web.cors.CorsConfiguration;
import org.springframework.web.cors.CorsConfigurationSource;
import org.springframework.web.cors.UrlBasedCorsConfigurationSource;

import com.vti.service.CustomUserDetailsService;

import java.util.Arrays;

@Configuration
@EnableMethodSecurity
public class SecurityConfig {
	private final JwtAuthenticationFilter jwtAuthenticationFilter;
	private final CustomUserDetailsService userDetailsService;

	public SecurityConfig(JwtAuthenticationFilter jwtAuthenticationFilter,
			CustomUserDetailsService userDetailsService) {
		this.jwtAuthenticationFilter = jwtAuthenticationFilter;
		this.userDetailsService = userDetailsService;
	}

	@SuppressWarnings("removal")
	@Bean
	public SecurityFilterChain filterChain(HttpSecurity http) throws Exception {
	    http.cors().and().csrf().disable()
	        .sessionManagement().sessionCreationPolicy(SessionCreationPolicy.STATELESS).and()
	        .authorizeHttpRequests()
	        // ✅ CHO PHÉP TẤT CẢ TRANG HTML & TÀI NGUYÊN TĨNH
	        .requestMatchers(
	                "/auth/**",           
	                "/login.html", 
	                "/register.html",
	                "/accept_invitation.html",
	                "/*.html",  
	                "/js/**", 
	                "/css/**", 
	                "/assets/**",
	                "/favicon.ico", 
	                "/attachments/download/**",
	                "/error",
	                "/invitations/accept",
	                "/invitations/my-invitations",
	                "/dashboard_personal.html",        
	                "/projects_personal.html", 
	                "/tasks_details_details_personal.html",
	                "/tasks_details_personal.html"             
	            ).permitAll()

	        // ✅ SỬA: API cá nhân chỉ cho GUEST và MEMBER, KHÔNG cho ADMIN
	        .requestMatchers(HttpMethod.POST, "/projects/personal")
            .hasAnyRole("GUEST", "MEMBER", "ADMIN", "EMPLOYEE", "USER")
        .requestMatchers(HttpMethod.GET, "/projects/personal")
            .hasAnyRole("GUEST", "MEMBER", "ADMIN", "EMPLOYEE", "USER")	            
	        // ✅ API nhóm cho cả ADMIN và MEMBER
	        .requestMatchers("/api/groups/**", "/api/projects/group/**")
	            .hasAnyRole("ADMIN", "MEMBER")
	        .anyRequest().permitAll();

	    http.addFilterBefore(jwtAuthenticationFilter, UsernamePasswordAuthenticationFilter.class);
	    return http.build();
	}
	// THÊM BEAN CORS CONFIGURATION
	@Bean
	public CorsConfigurationSource corsConfigurationSource() {
	    CorsConfiguration configuration = new CorsConfiguration();
	    configuration.setAllowedOrigins(Arrays.asList("http://localhost:8080", "http://127.0.0.1:8080"));
	    configuration.setAllowedMethods(Arrays.asList("GET", "POST", "PUT", "DELETE", "OPTIONS"));
	    configuration.setAllowedHeaders(Arrays.asList("*"));
	    configuration.setAllowCredentials(true);
	    UrlBasedCorsConfigurationSource source = new UrlBasedCorsConfigurationSource();
	    source.registerCorsConfiguration("/**", configuration);
	    return source;
	}
	
	@Bean
	public AuthenticationManager authenticationManager(AuthenticationConfiguration config) throws Exception {
		return config.getAuthenticationManager();
	}

	@Bean
	public PasswordEncoder passwordEncoder() {
		return new BCryptPasswordEncoder();
	}
}