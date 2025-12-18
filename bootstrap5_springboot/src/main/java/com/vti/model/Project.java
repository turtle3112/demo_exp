package com.vti.model;

import com.fasterxml.jackson.annotation.JsonFormat;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

import jakarta.persistence.*;

import java.time.LocalDate;
import java.time.LocalDateTime;

@Entity
@Table(name = "`project`")
@JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})

public class Project {
	@Id
	@GeneratedValue(strategy = GenerationType.IDENTITY)
	private Integer id;

	@Column(nullable = false, length = 100)
	private String name;

	@Column(columnDefinition = "TEXT")
	private String description;

	@ManyToOne(fetch = FetchType.LAZY)
	@JoinColumn(name = "created_by", nullable = false)
	private User createdBy;

	@Column(name = "created_at")
    @JsonFormat(pattern = "yyyy-MM-dd'T'HH:mm:ss")
	private LocalDateTime createdAt = LocalDateTime.now();
	
	@Enumerated(EnumType.STRING)
	@Column(name = "project_type")
	private ProjectType projectType = ProjectType.TEAM;
	
	// ✅ ĐÃ CÓ ORGANIZATION - RẤT TỐT!
	@ManyToOne(fetch = FetchType.LAZY)
	@JoinColumn(name = "organization_id")
	private Organization organization;

	public enum ProjectType {
	    PERSONAL, TEAM, ENTERPRISE 
	}

	public Project() {
		super();
	}
	
    @Column(name = "deadline")
    private LocalDateTime deadline;
    
    @JsonFormat(pattern = "yyyy-MM-dd")
    public LocalDate getDeadlineDate() {
        return deadline != null ? deadline.toLocalDate() : null;
    }

    @JsonFormat(pattern = "yyyy-MM-dd")
    public void setDeadlineDate(LocalDate date) {
        if (date != null) {
            this.deadline = date.atTime(23, 59, 59);
        } else {
            this.deadline = null;
        }
    }
    
    @Enumerated(EnumType.STRING)
    @Column(name = "status")
    private ProjectStatus status = ProjectStatus.IN_PROGRESS;
    
    public enum ProjectStatus {
        IN_PROGRESS,    // Chưa hoàn thành
        COMPLETED,      // Đã hoàn thành
        EXPIRED         // Hết hạn
    }
    
    @Override
    public String toString() {
        return "Project{" +
                "id=" + id +
                ", name='" + name + '\'' +
                ", description='" + description + '\'' +
                ", projectType=" + projectType +
                ", deadline=" + deadline +
                '}';
    }

	public Project(Integer id, String name, String description, User createdBy, LocalDateTime createdAt) {
		super();
		this.id = id;
		this.name = name;
		this.description = description;
		this.createdBy = createdBy;
		this.createdAt = createdAt;
	}

	// Getters and Setters
	public Integer getId() {
		return id;
	}

	public void setId(Integer id) {
		this.id = id;
	}

	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	public String getDescription() {
		return description;
	}

	public void setDescription(String description) {
		this.description = description;
	}

	public User getCreatedBy() {
		return createdBy;
	}

	public void setCreatedBy(User createdBy) {
		this.createdBy = createdBy;
	}

	public LocalDateTime getCreatedAt() {
		return createdAt;
	}

	public void setCreatedAt(LocalDateTime createdAt) {
		this.createdAt = createdAt;
	}
	
	// ✅ QUAN TRỌNG: Getter và Setter cho organization
	public Organization getOrganization() {
	    return organization;
	}

	public void setOrganization(Organization organization) {
	    this.organization = organization;
	}
	
	public ProjectType getProjectType() {
	    return projectType;
	}

	public void setProjectType(ProjectType projectType) {
	    this.projectType = projectType;
	}
	
	public LocalDateTime getDeadline() {
		return deadline;
	}

	public void setDeadline(LocalDateTime deadline) {
		this.deadline = deadline;
	}
	
    public ProjectStatus getStatus() {
        return status;
    }
    
    public void setStatus(ProjectStatus status) {
        this.status = status;
    }
}