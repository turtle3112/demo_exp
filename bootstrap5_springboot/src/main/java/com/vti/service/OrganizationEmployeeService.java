package com.vti.service;

import dto.CreateEmployeeRequest;
import dto.EmployeeDTO;
import dto.UpdateEmployeeRequest;
import com.vti.model.User;
import com.vti.model.Organization;
import com.vti.repository.UserRepository;
import com.vti.repository.OrganizationRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;

import java.time.LocalDateTime;
import java.util.List;
import java.util.stream.Collectors;

@Service
public class OrganizationEmployeeService {
    
    @Autowired
    private UserRepository userRepository;
    
    @Autowired
    private OrganizationRepository organizationRepository;
    
    @Autowired
    private PasswordEncoder passwordEncoder;
    
    public List<EmployeeDTO> getOrganizationEmployees(Integer organizationId, UserDetails userDetails) {
        System.out.println("=== DEBUG: getOrganizationEmployees ===");
        System.out.println("Organization ID: " + organizationId);
        System.out.println("UserDetails: " + (userDetails != null ? userDetails.getUsername() : "NULL"));
        
        // Kiểm tra quyền truy cập
        if (userDetails == null) {
            throw new RuntimeException("User not authenticated");
        }
        
        User currentUser = userRepository.findByUsername(userDetails.getUsername())
                .orElseThrow(() -> new RuntimeException("User not found: " + userDetails.getUsername()));
        
        System.out.println("Current User Org ID: " + currentUser.getOrganization().getId());
        System.out.println("Current User Role: " + currentUser.getRole());
        
        if (!currentUser.getOrganization().getId().equals(organizationId)) {
            throw new RuntimeException("Access denied: User not in organization");
        }
        
        if (!currentUser.getRole().equals(User.Role.ADMIN)) {
            throw new RuntimeException("Access denied: User not admin");
        }
        
        // Lấy tất cả user thuộc organization này
        List<User> users = userRepository.findByOrganizationId(organizationId);
        System.out.println("Found " + users.size() + " users in organization");
        
        return users.stream().map(user -> 
            new EmployeeDTO(
                user.getId().longValue(),
                user.getUsername(),
                user.getFullName(),
                user.getEmail(),
                user.getEmployeeId(),
                user.getCreatedAt(),
                user.getRole().equals(User.Role.ADMIN)
            )
        ).collect(Collectors.toList());
    }
    
    public void createEmployee(Integer organizationId, CreateEmployeeRequest request, UserDetails userDetails) {
        System.out.println("=== DEBUG: createEmployee ===");
        System.out.println("Organization ID: " + organizationId);
        System.out.println("UserDetails: " + (userDetails != null ? userDetails.getUsername() : "NULL"));
        
        // Kiểm tra quyền
        if (userDetails == null) {
            throw new RuntimeException("User not authenticated");
        }
        
        User currentUser = userRepository.findByUsername(userDetails.getUsername())
                .orElseThrow(() -> new RuntimeException("User not found: " + userDetails.getUsername()));
        
        if (!currentUser.getOrganization().getId().equals(organizationId) || 
            !currentUser.getRole().equals(User.Role.ADMIN)) {
            throw new RuntimeException("Access denied");
        }
        
        // Kiểm tra username đã tồn tại
        if (userRepository.existsByUsername(request.getUsername())) {
            throw new RuntimeException("Username already exists");
        }
        
        // Kiểm tra email đã tồn tại
        if (userRepository.existsByEmail(request.getEmail())) {
            throw new RuntimeException("Email already exists");
        }
        
        Organization organization = organizationRepository.findById(organizationId)
                .orElseThrow(() -> new RuntimeException("Organization not found"));
        
        // Tạo user mới
        User newEmployee = new User();
        newEmployee.setUsername(request.getUsername());
        newEmployee.setPassword(passwordEncoder.encode(request.getPassword()));
        newEmployee.setFullName(request.getFullName());
        newEmployee.setEmail(request.getEmail());
        newEmployee.setEmployeeId(request.getEmployeeId());
        newEmployee.setRole(User.Role.EMPLOYEE);
        newEmployee.setAccountType("ENTERPRISE");
        newEmployee.setOrganization(organization);
        newEmployee.setCreatedAt(LocalDateTime.now());
        newEmployee.setAccountStatus(User.AccountStatus.ACTIVE);
        
        userRepository.save(newEmployee);
        System.out.println("Created new employee: " + request.getUsername());
    }
    
    public void updateEmployee(Integer organizationId, Integer employeeId, UpdateEmployeeRequest request, UserDetails userDetails) {
        System.out.println("=== DEBUG: updateEmployee ===");
        System.out.println("Organization ID: " + organizationId);
        System.out.println("Employee ID: " + employeeId);
        System.out.println("UserDetails: " + (userDetails != null ? userDetails.getUsername() : "NULL"));
        
        // Kiểm tra quyền
        if (userDetails == null) {
            throw new RuntimeException("User not authenticated");
        }
        
        User currentUser = userRepository.findByUsername(userDetails.getUsername())
                .orElseThrow(() -> new RuntimeException("User not found: " + userDetails.getUsername()));
        
        if (!currentUser.getOrganization().getId().equals(organizationId) || 
            !currentUser.getRole().equals(User.Role.ADMIN)) {
            throw new RuntimeException("Access denied");
        }
        
        // Tìm employee cần update
        User employee = userRepository.findById(employeeId)
                .orElseThrow(() -> new RuntimeException("Employee not found"));
        
        // Kiểm tra employee có thuộc organization không
        if (!employee.getOrganization().getId().equals(organizationId)) {
            throw new RuntimeException("Employee not in this organization");
        }
        
        // Không cho phép update admin
        if (employee.getRole().equals(User.Role.ADMIN)) {
            throw new RuntimeException("Cannot update admin account");
        }
        
        // Update thông tin
        employee.setFullName(request.getFullName());
        employee.setEmail(request.getEmail());
        employee.setEmployeeId(request.getEmployeeId());
        
        userRepository.save(employee);
        System.out.println("Updated employee: " + employee.getUsername());
    }
    
    public void deleteEmployee(Integer organizationId, Integer employeeId, UserDetails userDetails) {
        System.out.println("=== DEBUG: deleteEmployee ===");
        System.out.println("Organization ID: " + organizationId);
        System.out.println("Employee ID: " + employeeId);
        System.out.println("UserDetails: " + (userDetails != null ? userDetails.getUsername() : "NULL"));
        
        // Kiểm tra quyền
        if (userDetails == null) {
            throw new RuntimeException("User not authenticated");
        }
        
        User currentUser = userRepository.findByUsername(userDetails.getUsername())
                .orElseThrow(() -> new RuntimeException("User not found: " + userDetails.getUsername()));
        
        if (!currentUser.getOrganization().getId().equals(organizationId) || 
            !currentUser.getRole().equals(User.Role.ADMIN)) {
            throw new RuntimeException("Access denied");
        }
        
        // Tìm employee cần xóa
        User employee = userRepository.findById(employeeId)
                .orElseThrow(() -> new RuntimeException("Employee not found"));
        
        // Kiểm tra employee có thuộc organization không
        if (!employee.getOrganization().getId().equals(organizationId)) {
            throw new RuntimeException("Employee not in this organization");
        }
        
        // Không cho phép xóa admin
        if (employee.getRole().equals(User.Role.ADMIN)) {
            throw new RuntimeException("Cannot delete admin account");
        }
        
        // Không cho phép xóa chính mình
        if (employee.getId().equals(currentUser.getId())) {
            throw new RuntimeException("Cannot delete your own account");
        }
        
        userRepository.delete(employee);
        System.out.println("Deleted employee: " + employee.getUsername());
    }
}