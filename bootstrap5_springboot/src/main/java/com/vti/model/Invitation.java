package com.vti.model;

import jakarta.persistence.*;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import java.time.LocalDateTime;

@Entity
@Table(name = "invitations")
public class Invitation {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;

    // Người được mời
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "invited_user_id", nullable = false)
    @JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
    private User invitedUser;

    // Organization liên quan
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "organization_id", nullable = false)
    @JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
    private Organization organization;

    // Optional: nếu lời mời liên quan đến project
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "project_id")
    @JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
    private Project project;

    // Người gửi
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "invited_by_id", nullable = false)
    @JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
    private User invitedBy;

    // Nội dung lời mời
    @Column(name = "message", length = 500)
    private String message;

    // Trạng thái lời mời
    @Enumerated(EnumType.STRING)
    @Column(nullable = false)
    private InvitationStatus status = InvitationStatus.PENDING;

    // Thời gian gửi lời mời
    @Column(nullable = false)
    private LocalDateTime invitedAt = LocalDateTime.now();

    // Thời gian phản hồi (Accept/Decline)
    private LocalDateTime respondedAt;

    // ======================================
    // Constructors
    // ======================================

    public Invitation() {}

    public Invitation(User invitedUser, Organization organization, User invitedBy, String message) {
        this.invitedUser = invitedUser;
        this.organization = organization;
        this.invitedBy = invitedBy;
        this.message = message;
        this.status = InvitationStatus.PENDING;
        this.invitedAt = LocalDateTime.now();
    }

    // ======================================
    // Getters & Setters
    // ======================================

    public Integer getId() {
        return id;
    }

    public void setId(Integer id) {
        this.id = id;
    }

    public User getInvitedUser() {
        return invitedUser;
    }

    public void setInvitedUser(User invitedUser) {
        this.invitedUser = invitedUser;
    }

    public Organization getOrganization() {
        return organization;
    }

    public void setOrganization(Organization organization) {
        this.organization = organization;
    }

    public Project getProject() {
        return project;
    }

    public void setProject(Project project) {
        this.project = project;
    }

    public User getInvitedBy() {
        return invitedBy;
    }

    public void setInvitedBy(User invitedBy) {
        this.invitedBy = invitedBy;
    }

    public String getMessage() {
        return message;
    }

    public void setMessage(String message) {
        this.message = message;
    }

    public InvitationStatus getStatus() {
        return status;
    }

    public void setStatus(InvitationStatus status) {
        this.status = status;
    }

    public LocalDateTime getInvitedAt() {
        return invitedAt;
    }

    public void setInvitedAt(LocalDateTime invitedAt) {
        this.invitedAt = invitedAt;
    }

    public LocalDateTime getRespondedAt() {
        return respondedAt;
    }

    public void setRespondedAt(LocalDateTime respondedAt) {
        this.respondedAt = respondedAt;
    }

    // ======================================
    // Business Logic
    // ======================================

    public void accept() {
        this.status = InvitationStatus.ACCEPTED;
        this.respondedAt = LocalDateTime.now();
    }

    public void decline() {
        this.status = InvitationStatus.DECLINED;
        this.respondedAt = LocalDateTime.now();
    }

    public boolean isPending() {
        return InvitationStatus.PENDING.equals(this.status);
    }

    @Override
    public String toString() {
        return "Invitation{" +
                "id=" + id +
                ", invitedUser=" + (invitedUser != null ? invitedUser.getId() : null) +
                ", organization=" + (organization != null ? organization.getId() : null) +
                ", project=" + (project != null ? project.getId() : null) +
                ", invitedBy=" + (invitedBy != null ? invitedBy.getId() : null) +
                ", message='" + message + '\'' +
                ", status=" + status +
                ", invitedAt=" + invitedAt +
                ", respondedAt=" + respondedAt +
                '}';
    }
}
