package com.vti.security;

import com.vti.model.User;
import com.vti.service.TrialService;
import org.springframework.stereotype.Component;
import org.springframework.web.servlet.HandlerInterceptor;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.lang.NonNull;

@Component
public class TrialInterceptor implements HandlerInterceptor {
    private final TrialService trialService;
    
    public TrialInterceptor(TrialService trialService) {
        this.trialService = trialService;
    }
    
    @Override
    public boolean preHandle(@NonNull HttpServletRequest request, @NonNull HttpServletResponse response, Object handler) throws Exception {
        // Skip trial check for public endpoints
        String requestURI = request.getRequestURI();
        if (requestURI.startsWith("/auth/") || 
            requestURI.startsWith("/public/") ||
            requestURI.equals("/error") ||
            requestURI.endsWith(".html") ||
            requestURI.contains(".")) {
            return true;
        }
        
        // Get current user from security context
        Object principal = org.springframework.security.core.context.SecurityContextHolder
                            .getContext().getAuthentication().getPrincipal();
        
        if (principal instanceof User) {
            User user = (User) principal;
            if (!trialService.isTrialActive(user)) {
                response.sendError(403, "Trial period has expired. Please upgrade to continue.");
                return false;
            }
        }
        
        return true;
    }
}