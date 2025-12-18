package com.vti.repository;

import com.vti.model.Invitation;
import com.vti.model.InvitationStatus;
import com.vti.model.Organization;
import com.vti.model.User;

import jakarta.transaction.Transactional;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Modifying;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.Optional;

@Repository
public interface InvitationRepository extends JpaRepository<Invitation, Integer> {

    // ==================== QUERY HIỆN CÓ ====================

    List<Invitation> findByInvitedUserAndStatus(
            User invitedUser,
            InvitationStatus status
    );

    Optional<Invitation> findByInvitedUserAndOrganizationAndStatus(
            User invitedUser,
            Organization organization,
            InvitationStatus status
    );

    List<Invitation> findByOrganizationAndStatus(
            Organization organization,
            InvitationStatus status
    );

    List<Invitation> findByInvitedUser(User invitedUser);

    boolean existsByInvitedUserAndOrganizationAndStatus(
            User invitedUser,
            Organization organization,
            InvitationStatus status
    );

    // ==================== FIX FK DELETE PROJECT ====================

    @Modifying
    @Transactional
    @Query("DELETE FROM Invitation i WHERE i.project.id = :projectId")
    void deleteByProjectId(@Param("projectId") Integer projectId);
}
