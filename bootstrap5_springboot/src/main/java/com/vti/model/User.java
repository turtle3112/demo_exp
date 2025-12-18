package com.vti.model;

import jakarta.persistence.*;
import lombok.*;
import java.time.LocalDateTime;
import java.util.HashSet;
import java.util.Set;

import com.fasterxml.jackson.annotation.JsonIgnore;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

@Entity
@Table(name = "user")
@JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})

public class User {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;

    @Column(nullable = false, unique = true, length = 50)
    private String username;

    @Column(nullable = false, length = 255)
    private String password;

    @Column(name = "full_name", length = 100)
    private String fullName;

    @Enumerated(EnumType.STRING)
    @Column(nullable = true)
    private Role role;

    @Column(name = "created_at")
    private LocalDateTime createdAt = LocalDateTime.now();

    public enum Role {
        ADMIN, MEMBER, EMPLOYEE
    }

    @Column(name = "employee_id", unique = true, length = 50)
    private String employeeId;
    
    @ManyToMany(mappedBy = "assignedUsers")
    @JsonIgnore
    private Set<Task> tasks = new HashSet<>();

    public User() {
        super();
    }
    
    // CẬP NHẬT: THÊM RÀNG BUỘC CHO EMAIL
    @Column(nullable = false, unique = true, length = 100)
    private String email;
    
    @Column(name = "account_type")
    private String accountType;
    
    @Enumerated(EnumType.STRING)
    private AccountStatus accountStatus = AccountStatus.TRIAL_ACTIVE;
    
    @Column(name = "trial_start_date")
    private LocalDateTime trialStartDate;

    @Column(name = "trial_end_date")
    private LocalDateTime trialEndDate;
    
    // === THÊM ENUM AccountStatus ===
    public enum AccountStatus {
        TRIAL_ACTIVE, TRIAL_EXPIRED, ACTIVE, LOCKED
    }
    
    @ManyToOne
    @JoinColumn(name = "organization_id")
    private Organization organization;

    // CẬP NHẬT CONSTRUCTOR ĐẦY ĐỦ
    @Builder
    public User(Integer id, String username, String password, String fullName, Role role, LocalDateTime createdAt,
            String employeeId, String email, String accountType, AccountStatus accountStatus, 
            LocalDateTime trialStartDate, LocalDateTime trialEndDate, Organization organization) {
        super();
        this.id = id;
        this.username = username;
        this.password = password;
        this.fullName = fullName;
        this.role = role;
        this.createdAt = createdAt;
        this.employeeId = employeeId;
        this.email = email;
        this.accountType = accountType;
        this.accountStatus = accountStatus;
        this.trialStartDate = trialStartDate;
        this.trialEndDate = trialEndDate;
        this.organization = organization;
    }

    public Integer getId() {
        return id;
    }

    public void setId(Integer id) {
        this.id = id;
    }

    public String getUsername() {
        return username;
    }

    public void setUsername(String username) {
        this.username = username;
    }

    public String getPassword() {
        return password;
    }

    public void setPassword(String password) {
        this.password = password;
    }

    public String getFullName() {
        return fullName;
    }

    public void setFullName(String fullName) {
        this.fullName = fullName;
    }

    public Role getRole() {
        return role;
    }

    public void setRole(Role role) {
        this.role = role;
    }

    public LocalDateTime getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(LocalDateTime createdAt) {
        this.createdAt = createdAt;
    }

    public String getEmployeeId() {
        return employeeId;
    }

    public void setEmployeeId(String employeeId) {
        this.employeeId = employeeId;
    }
    
    // THÊM GETTER/SETTER CHO TASKS
    public Set<Task> getTasks() {
        return tasks;
    }

    public void setTasks(Set<Task> tasks) {
        this.tasks = tasks;
    }

    public String getEmail() {
        return email;
    }
    
    public void setEmail(String email) {
        this.email = email;
    }

    public String getAccountType() {
        return accountType;
    }

    public void setAccountType(String accountType) {
        this.accountType = accountType;
    }
    
    public AccountStatus getAccountStatus() {
        return accountStatus;
    }

    public void setAccountStatus(AccountStatus accountStatus) {
        this.accountStatus = accountStatus;
    }

    public LocalDateTime getTrialStartDate() {
        return trialStartDate;
    }

    public void setTrialStartDate(LocalDateTime trialStartDate) {
        this.trialStartDate = trialStartDate;
    }

    public LocalDateTime getTrialEndDate() {
        return trialEndDate;
    }

    public void setTrialEndDate(LocalDateTime trialEndDate) {
        this.trialEndDate = trialEndDate;
    }
    
    public Organization getOrganization() {
        return organization;
    }

    public void setOrganization(Organization organization) {
        this.organization = organization;
    }
    
    public String getOrganizationName() {
        return organization != null ? organization.getName() : null;
    }

    // ========== THÊM CÁC PHƯƠNG THỨC TIỆN ÍCH CHO CHỨC NĂNG QUÊN MẬT KHẨU ==========
    
    /**
     * Kiểm tra user có đang active không
     */
    public boolean isActive() {
        return accountStatus == AccountStatus.ACTIVE || accountStatus == AccountStatus.TRIAL_ACTIVE;
    }

    /**
     * Kiểm tra trial đã hết hạn chưa
     */
    public boolean isTrialExpired() {
        return accountStatus == AccountStatus.TRIAL_EXPIRED;
    }

    /**
     * Kiểm tra user có thể reset password không
     * - Tài khoản phải active
     * - Email không được null hoặc rỗng
     */
    public boolean canResetPassword() {
        return isActive() && email != null && !email.trim().isEmpty();
    }

    /**
     * Kiểm tra user có role ADMIN không
     */
    public boolean isAdmin() {
        return role == Role.ADMIN;
    }

    /**
     * Kiểm tra user có role EMPLOYEE không
     */
    public boolean isEmployee() {
        return role == Role.EMPLOYEE;
    }

    /**
     * Kiểm tra user có role MEMBER không
     */
    public boolean isMember() {
        return role == Role.MEMBER;
    }

    /**
     * Phương thức toString để debug
     */
    @Override
    public String toString() {
        return "User{" +
                "id=" + id +
                ", username='" + username + '\'' +
                ", email='" + email + '\'' +
                ", fullName='" + fullName + '\'' +
                ", role=" + role +
                ", accountStatus=" + accountStatus +
                ", organization=" + (organization != null ? organization.getName() : "null") +
                '}';
    }

    /**
     * Phương thức tạo user demo cho testing
     */
    public static User createDemoUser() {
        User user = new User();
        user.setUsername("demo_user");
        user.setPassword("encoded_password");
        user.setFullName("Demo User");
        user.setEmail("demo@example.com");
        user.setRole(Role.MEMBER);
        user.setAccountStatus(AccountStatus.ACTIVE);
        return user;
    }

    /**
     * Kiểm tra email có hợp lệ không
     */
    public boolean hasValidEmail() {
        return email != null && email.matches("^[A-Za-z0-9+_.-]+@(.+)$");
    }
}