package com.vti.repository;

import com.vti.model.Organization;
import com.vti.model.User;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.List;
import java.util.Optional;

public interface UserRepository extends JpaRepository<User, Integer> {
    Optional<User> findByUsername(String username);
    Optional<User> findByEmployeeId(String employeeId);
    Optional<User> findByEmail(String email);
    
    List<User> findByOrganization(Organization organization);

    // ✅ THÊM CÁC METHODS NÀY
    @Query("SELECT u FROM User u WHERE u.organization.id = :organizationId")
    List<User> findByOrganizationId(@Param("organizationId") Integer organizationId);
    
    boolean existsByUsername(String username);
    boolean existsByEmail(String email);
    
    // ✅ THÊM CÁC METHODS MỚI CHO CHỨC NĂNG QUÊN MẬT KHẨU VÀ QUẢN LÝ USER
    
    /**
     * Tìm user bằng username hoặc email (dùng cho login)
     */
    @Query("SELECT u FROM User u WHERE u.username = :usernameOrEmail OR u.email = :usernameOrEmail")
    Optional<User> findByUsernameOrEmail(@Param("usernameOrEmail") String usernameOrEmail);
    
    /**
     * Kiểm tra tồn tại employeeId
     */
    boolean existsByEmployeeId(String employeeId);
    
    /**
     * Tìm user theo role
     */
    List<User> findByRole(User.Role role);
    
    /**
     * Tìm user theo account status
     */
    List<User> findByAccountStatus(User.AccountStatus accountStatus);
    
    /**
     * Tìm user active (cho chức năng quên mật khẩu)
     */
    @Query("SELECT u FROM User u WHERE u.email = :email AND u.accountStatus IN (com.vti.model.User.AccountStatus.ACTIVE, com.vti.model.User.AccountStatus.TRIAL_ACTIVE)")
    Optional<User> findActiveUserByEmail(@Param("email") String email);
    
    /**
     * Đếm số user theo organization
     */
    Long countByOrganization(Organization organization);
    
    /**
     * Đếm số user theo role và organization
     */
    Long countByRoleAndOrganization(User.Role role, Organization organization);
    
    /**
     * Tìm user theo username và organization (cho multi-tenant)
     */
    Optional<User> findByUsernameAndOrganization(String username, Organization organization);
    
    /**
     * Tìm user theo email và organization (cho multi-tenant)
     */
    Optional<User> findByEmailAndOrganization(String email, Organization organization);
    
    /**
     * Tìm tất cả user với pagination và filter theo organization
     */
    @Query("SELECT u FROM User u WHERE " +
           "(:organizationId IS NULL OR u.organization.id = :organizationId) AND " +
           "(:role IS NULL OR u.role = :role) AND " +
           "(:accountStatus IS NULL OR u.accountStatus = :accountStatus)")
    List<User> findUsersWithFilters(
        @Param("organizationId") Integer organizationId,
        @Param("role") User.Role role,
        @Param("accountStatus") User.AccountStatus accountStatus
    );
    
    /**
     * Tìm user bằng email và kiểm tra trạng thái active (cho quên mật khẩu)
     */
    @Query("SELECT u FROM User u WHERE u.email = :email AND u.accountStatus IN ('ACTIVE', 'TRIAL_ACTIVE')")
    Optional<User> findActiveByEmail(@Param("email") String email);
    
    /**
     * Kiểm tra email đã tồn tại cho user khác (dùng khi update profile)
     */
    @Query("SELECT CASE WHEN COUNT(u) > 0 THEN true ELSE false END FROM User u WHERE u.email = :email AND u.id <> :userId")
    boolean existsByEmailAndIdNot(@Param("email") String email, @Param("userId") Integer userId);
    
    /**
     * Kiểm tra username đã tồn tại cho user khác (dùng khi update profile)
     */
    @Query("SELECT CASE WHEN COUNT(u) > 0 THEN true ELSE false END FROM User u WHERE u.username = :username AND u.id <> :userId")
    boolean existsByUsernameAndIdNot(@Param("username") String username, @Param("userId") Integer userId);
    
    // ✅ SỬA LỖI: THAY THẾ 2 METHOD GÂY LỖI
    
    /**
     * Tìm user đã hết hạn trial - SỬA LẠI
     */
    @Query("SELECT u FROM User u WHERE u.accountStatus = 'TRIAL_EXPIRED' AND u.trialEndDate < CURRENT_TIMESTAMP")
    List<User> findExpiredTrialUsers();
    
    /**
     * Tìm user sắp hết hạn trial (trong vòng 7 ngày) - SỬA LẠI
     * Dùng native query để tránh lỗi Hibernate 6
     */
    @Query(value = "SELECT * FROM user u WHERE u.account_status = 'TRIAL_ACTIVE' AND u.trial_end_date BETWEEN CURRENT_TIMESTAMP AND DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 7 DAY)", nativeQuery = true)
    List<User> findExpiringTrialUsers();
    
    /**
     * Tìm user theo tên (tìm kiếm gần đúng)
     */
    @Query("SELECT u FROM User u WHERE LOWER(u.fullName) LIKE LOWER(CONCAT('%', :name, '%'))")
    List<User> findByFullNameContainingIgnoreCase(@Param("name") String name);
    
    /**
     * Tìm user theo email (tìm kiếm gần đúng)
     */
    @Query("SELECT u FROM User u WHERE LOWER(u.email) LIKE LOWER(CONCAT('%', :email, '%'))")
    List<User> findByEmailContainingIgnoreCase(@Param("email") String email);
    
    /**
     * Đếm số user theo trạng thái tài khoản
     */
    @Query("SELECT COUNT(u) FROM User u WHERE u.accountStatus = :status")
    Long countByAccountStatus(@Param("status") User.AccountStatus status);
    
    /**
     * Đếm số user theo role
     */
    @Query("SELECT COUNT(u) FROM User u WHERE u.role = :role")
    Long countByRole(@Param("role") User.Role role);
}