package dto;

import java.time.LocalDateTime;

import com.vti.model.TeamInvitation;

public class TeamInvitationDTO {
    private Integer id;
    private String email;
    private String token;
    private TeamInvitation.InvitationStatus status;
    private LocalDateTime createdAt;
    private LocalDateTime expiresAt;
    private String organizationName;
    private String invitedByName;
    private String organizationDescription; // THÊM TRƯỜNG NÀY

    // Constructor từ Entity
    public TeamInvitationDTO(TeamInvitation invitation) {
        this.id = invitation.getId();
        this.email = invitation.getEmail();
        this.token = invitation.getToken();
        this.status = invitation.getStatus();
        this.createdAt = invitation.getCreatedAt();
        this.expiresAt = invitation.getExpiresAt();
        this.organizationName = invitation.getOrganization() != null ? 
            invitation.getOrganization().getName() : null;
        this.invitedByName = invitation.getInvitedBy() != null ? 
            invitation.getInvitedBy().getFullName() : null;
        this.organizationDescription = invitation.getOrganization() != null ? 
            invitation.getOrganization().getDescription() : null;
    }

    // Getters and Setters - THÊM ĐẦY ĐỦ
    public Integer getId() {
        return id;
    }

    public void setId(Integer id) {
        this.id = id;
    }

    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    public String getToken() {
        return token;
    }

    public void setToken(String token) {
        this.token = token;
    }

    public TeamInvitation.InvitationStatus getStatus() {
        return status;
    }

    public void setStatus(TeamInvitation.InvitationStatus status) {
        this.status = status;
    }

    public LocalDateTime getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(LocalDateTime createdAt) {
        this.createdAt = createdAt;
    }

    public LocalDateTime getExpiresAt() {
        return expiresAt;
    }

    public void setExpiresAt(LocalDateTime expiresAt) {
        this.expiresAt = expiresAt;
    }

    public String getOrganizationName() {
        return organizationName;
    }

    public void setOrganizationName(String organizationName) {
        this.organizationName = organizationName;
    }

    public String getInvitedByName() {
        return invitedByName;
    }

    public void setInvitedByName(String invitedByName) {
        this.invitedByName = invitedByName;
    }

    public String getOrganizationDescription() {
        return organizationDescription;
    }

    public void setOrganizationDescription(String organizationDescription) {
        this.organizationDescription = organizationDescription;
    }
}