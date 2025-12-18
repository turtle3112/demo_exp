package com.vti.repository;

import com.vti.model.TeamInvitation;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.List;
import java.util.Optional;

public interface TeamInvitationRepository extends JpaRepository<TeamInvitation, Integer> {
    Optional<TeamInvitation> findByToken(String token);
    
    // SỬA: Thay Optional bằng List để tránh lỗi "unique result"
    @Query("SELECT ti FROM TeamInvitation ti WHERE ti.email = :email AND ti.organization.id = :organizationId")
    List<TeamInvitation> findByEmailAndOrganizationId(@Param("email") String email, @Param("organizationId") Integer organizationId);
    
    List<TeamInvitation> findByOrganizationIdAndStatus(Integer organizationId, TeamInvitation.InvitationStatus status);
    List<TeamInvitation> findByEmailAndStatus(String email, TeamInvitation.InvitationStatus status);
}