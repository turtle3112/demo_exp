package com.vti.model;

import com.fasterxml.jackson.annotation.JsonIgnore;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import jakarta.persistence.*;

import java.time.LocalDateTime;
import java.time.LocalDate;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

@Entity
@Table(name = "`task`")
@JsonIgnoreProperties({"hibernateLazyInitializer", "handler"})
public class Task {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;

    // Ngăn vòng lặp JSON và lỗi Hibernate Proxy
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "project_id", nullable = false)
    @JsonIgnore
    private Project project;

    @Column(nullable = false, length = 100)
    private String name;

    @Column(columnDefinition = "TEXT")
    private String description;

    @Enumerated(EnumType.STRING)
    @Column(columnDefinition = "ENUM('TODO', 'IN_PROGRESS', 'DONE') DEFAULT 'TODO'")
    private Status status;

    public enum Status {
        TODO, IN_PROGRESS, DONE
    }

    private LocalDate deadline;

    @OneToMany(mappedBy = "task", cascade = CascadeType.ALL, orphanRemoval = true)
    @JsonIgnore
    private List<Attachment> attachments;

    // CHẶN JSON ĐỆ QUY & Proxy
    @ManyToMany(fetch = FetchType.LAZY)
    @JoinTable(
            name = "task_assignment",
            joinColumns = @JoinColumn(name = "task_id"),
            inverseJoinColumns = @JoinColumn(name = "user_id")
    )
    @JsonIgnore
    private Set<User> assignedUsers = new HashSet<>();

    @Enumerated(EnumType.STRING)
    @Column(columnDefinition = "ENUM('LOW', 'MEDIUM', 'HIGH') DEFAULT 'LOW'")
    private Priority priority;

    public enum Priority {
        LOW, MEDIUM, HIGH
    }

    @Transient
    private int tempProgress;

    @Column(name = "start_date")
    private LocalDateTime startDate;

    @Column(name = "due_date")
    private LocalDateTime dueDate;

    // Constructors
    public Task() {}

    public Task(Integer id, Project project, String name, String description,
                Status status, LocalDate deadline, Set<User> assignedUsers) {
        this.id = id;
        this.project = project;
        this.name = name;
        this.description = description;
        this.status = status;
        this.deadline = deadline;
        this.assignedUsers = assignedUsers;
    }

    // Getter + Setter
    public Integer getId() {
        return id;
    }

    public void setId(Integer id) {
        this.id = id;
    }

    public Project getProject() {
        return project;
    }

    public void setProject(Project project) {
        this.project = project;
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

    public Status getStatus() {
        return status;
    }

    public void setStatus(Status status) {
        this.status = status;
    }

    public Priority getPriority() {
        return priority;
    }

    public void setPriority(Priority priority) {
        this.priority = priority;
    }

    public LocalDate getDeadline() {
        return deadline;
    }

    public void setDeadline(LocalDate deadline) {
        this.deadline = deadline;
    }

    public Set<User> getAssignedUsers() {
        return assignedUsers;
    }

    public void setAssignedUsers(Set<User> assignedUsers) {
        this.assignedUsers = assignedUsers;
    }

    public int getTempProgress() {
        return tempProgress;
    }

    public void setTempProgress(int tempProgress) {
        this.tempProgress = tempProgress;
    }

    public LocalDateTime getStartDate() {
        return startDate;
    }

    public void setStartDate(LocalDateTime startDate) {
        this.startDate = startDate;
    }

    public LocalDateTime getDueDate() {
        return dueDate;
    }

    public void setDueDate(LocalDateTime dueDate) {
        this.dueDate = dueDate;
    }
}
