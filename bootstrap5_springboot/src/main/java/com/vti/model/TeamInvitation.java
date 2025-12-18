package com.vti.model;

import jakarta.persistence.*;
import java.time.LocalDateTime;

import com.fasterxml.jackson.annotation.JsonIgnore;

@Entity
@Table(name = "team_invitations")
public class TeamInvitation {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;

    private String email;

    @ManyToOne
    @JoinColumn(name = "organization_id")
    private Organization organization;

    private String token;

    @Enumerated(EnumType.STRING)
    private InvitationStatus status = InvitationStatus.PENDING;

    @Column(name = "created_at")
    private LocalDateTime createdAt = LocalDateTime.now();

    @Column(name = "expires_at")
    private LocalDateTime expiresAt;

    @ManyToOne
    @JoinColumn(name = "invited_by")
    @JsonIgnore 
    private User invitedBy;

    public enum InvitationStatus {
        PENDING, ACCEPTED, EXPIRED
    }

    // Constructors
    public TeamInvitation() {}

    public TeamInvitation(String email, Organization organization, String token, User invitedBy) {
        this.email = email;
        this.organization = organization;
        this.token = token;
        this.invitedBy = invitedBy;
        this.expiresAt = LocalDateTime.now().plusDays(7);
    }

    // === THÊM GETTER/SETTER ===
    public Integer getId() { return id; }
    public void setId(Integer id) { this.id = id; }

    public String getEmail() { return email; }
    public void setEmail(String email) { this.email = email; }

    public Organization getOrganization() { return organization; }
    public void setOrganization(Organization organization) { this.organization = organization; }

    public String getToken() { return token; }
    public void setToken(String token) { this.token = token; }

    public InvitationStatus getStatus() { return status; }
    public void setStatus(InvitationStatus status) { this.status = status; }

    public LocalDateTime getCreatedAt() { return createdAt; }
    public void setCreatedAt(LocalDateTime createdAt) { this.createdAt = createdAt; }

    public LocalDateTime getExpiresAt() { return expiresAt; }
    public void setExpiresAt(LocalDateTime expiresAt) { this.expiresAt = expiresAt; }

    public User getInvitedBy() { return invitedBy; }
    public void setInvitedBy(User invitedBy) { this.invitedBy = invitedBy; }
}