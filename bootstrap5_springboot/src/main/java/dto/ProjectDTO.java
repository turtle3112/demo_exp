package dto;

import com.vti.model.Project;
import java.time.LocalDateTime;

public class ProjectDTO {
    private Integer id;
    private String name;
    private String description;
    private LocalDateTime createdAt;
    private LocalDateTime deadline;
    private Long taskCount;
    private Project.ProjectStatus status;

    // Constructor từ Project + taskCount
    public ProjectDTO(Project project, Long taskCount) {
        this.id = project.getId();
        this.name = project.getName();
        this.description = project.getDescription();
        this.createdAt = project.getCreatedAt();
        this.deadline = project.getDeadline();
        this.taskCount = taskCount;
        this.status = project.getStatus(); 
    }

    // 🚨 QUAN TRỌNG: THÊM GETTER METHODS
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

    public LocalDateTime getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(LocalDateTime createdAt) {
        this.createdAt = createdAt;
    }

    public LocalDateTime getDeadline() {
        return deadline;
    }

    public void setDeadline(LocalDateTime deadline) {
        this.deadline = deadline;
    }

    public Long getTaskCount() {
        return taskCount;
    }

    public void setTaskCount(Long taskCount) {
        this.taskCount = taskCount;
    }
    public Project.ProjectStatus getStatus() {
        return status;
    }
    
    public void setStatus(Project.ProjectStatus status) {
        this.status = status;
    }
}