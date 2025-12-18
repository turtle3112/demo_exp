package com.vti.service;

import com.vti.model.User;
import com.vti.repository.UserRepository;
import org.springframework.stereotype.Service;
import java.time.LocalDateTime;

@Service
public class TrialService {
    private final UserRepository userRepository;
    
    public TrialService(UserRepository userRepository) {
        this.userRepository = userRepository;
    }
    
    public boolean isTrialActive(User user) {
        if (user.getAccountStatus() == User.AccountStatus.ACTIVE) {
            return true;
        }
        
        if (user.getTrialEndDate() == null) {
            return false;
        }
        
        boolean isActive = LocalDateTime.now().isBefore(user.getTrialEndDate());
        
        // Auto-update status if trial expired
        if (!isActive && user.getAccountStatus() == User.AccountStatus.TRIAL_ACTIVE) {
            user.setAccountStatus(User.AccountStatus.TRIAL_EXPIRED);
            userRepository.save(user);
        }
        
        return isActive;
    }
}