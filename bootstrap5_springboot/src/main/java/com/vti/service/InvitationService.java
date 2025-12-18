package com.vti.service;

import com.vti.model.Invitation;
import com.vti.model.InvitationStatus;
import com.vti.model.Organization;
import com.vti.model.User;
import com.vti.repository.InvitationRepository;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Optional;

@Service
public class InvitationService {

    @Autowired
    private InvitationRepository invitationRepository;

    // ==========================
    // GỬI LỜI MỜI (CÓ KIỂM TRA TRÙNG)
    // ==========================
    @Transactional
    public Invitation sendInvitation(User invitedUser, Organization organization, String message, User invitedBy) {

        boolean existingInvitation = existsInvitation(invitedUser, organization, InvitationStatus.PENDING);

        if (existingInvitation) {
            throw new RuntimeException("Đã có lời mời đang chờ xử lý cho user này");
        }

        Invitation invitation = new Invitation();
        invitation.setInvitedUser(invitedUser);
        invitation.setOrganization(organization);
        invitation.setInvitedBy(invitedBy);
        invitation.setInvitedAt(LocalDateTime.now());
        invitation.setStatus(InvitationStatus.PENDING);
        invitation.setMessage(message); // CHỈ set nếu entity có field message

        return saveInvitation(invitation);
    }

    // ==========================
    // GỬI LỜI MỜI BẰNG OBJECT CÓ SẴN
    // ==========================
    @Transactional
    public Invitation sendInvitation(Invitation invitation) {

        invitation.setStatus(InvitationStatus.PENDING);

        if (invitation.getInvitedAt() == null) {
            invitation.setInvitedAt(LocalDateTime.now());
        }

        if (invitation.getInvitedBy() == null) {
            throw new RuntimeException("invitedBy không được để trống");
        }

        boolean existingInvitation = existsInvitation(
                invitation.getInvitedUser(),
                invitation.getOrganization(),
                InvitationStatus.PENDING
        );

        if (existingInvitation) {
            throw new RuntimeException("Đã có lời mời đang chờ xử lý cho user này");
        }

        return saveInvitation(invitation);
    }

    // ==========================
    // KIỂM TRA CÓ THỂ GỬI LỜI MỜI KHÔNG
    // ==========================
    public boolean canSendInvitation(User invitedUser, Organization organization) {
        return !existsInvitation(invitedUser, organization, InvitationStatus.PENDING);
    }

    // ==========================
    // GETTER METHODS CŨ (GIỮ NGUYÊN)
    // ==========================
    public List<Invitation> getInvitationsByUserAndStatus(User user, InvitationStatus status) {
        return invitationRepository.findByInvitedUserAndStatus(user, status);
    }

    public Optional<Invitation> getInvitationByUserAndOrganizationAndStatus(
            User user, Organization organization, InvitationStatus status) {

        return invitationRepository.findByInvitedUserAndOrganizationAndStatus(user, organization, status);
    }

    public List<Invitation> getInvitationsByOrganizationAndStatus(Organization organization, InvitationStatus status) {
        return invitationRepository.findByOrganizationAndStatus(organization, status);
    }

    public List<Invitation> getInvitationsByUser(User user) {
        return invitationRepository.findByInvitedUser(user);
    }

    public boolean existsInvitation(User user, Organization organization, InvitationStatus status) {
        return invitationRepository.existsByInvitedUserAndOrganizationAndStatus(user, organization, status);
    }

    // ==========================
    // SAVE INVITATION (AUTO SET TIMESTAMP)
    // ==========================
    public Invitation saveInvitation(Invitation invitation) {

        if (invitation.getInvitedAt() == null) {
            invitation.setInvitedAt(LocalDateTime.now());
        }

        return invitationRepository.save(invitation);
    }

    // ==========================
    // DELETE
    // ==========================
    public void deleteInvitation(Integer id) {
        invitationRepository.deleteById(id);
    }

    // ==========================
    // UPDATE STATUS (DÙNG BUSINESS METHOD accept/decline)
    // ==========================
    @Transactional
    public Invitation updateInvitationStatus(Integer invitationId, InvitationStatus newStatus) {

        Invitation invitation = invitationRepository.findById(invitationId)
                .orElseThrow(() -> new RuntimeException("Không tìm thấy lời mời với ID: " + invitationId));

        switch (newStatus) {
            case ACCEPTED -> invitation.accept();
            case DECLINED -> invitation.decline();
            default -> invitation.setStatus(newStatus);
        }

        return saveInvitation(invitation);
    }
}
