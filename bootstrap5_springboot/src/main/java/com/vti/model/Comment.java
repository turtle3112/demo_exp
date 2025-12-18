package com.vti.model;

import jakarta.persistence.*;
import lombok.*;
import java.time.LocalDateTime;

@Entity
@Table(name = "`comment`")
public class Comment {
	@Id
	@GeneratedValue(strategy = GenerationType.IDENTITY)
	private Integer id;

	@ManyToOne
	@JoinColumn(name = "task_id", nullable = false)
	private Task task;

	@Column(columnDefinition = "TEXT", nullable = false)
	private String content;

	@Column(name = "created_at")
	private LocalDateTime createdAt = LocalDateTime.now();

	@ManyToOne
	@JoinColumn(name = "created_by", nullable = false)
	private User createdBy;

	public Comment() {
		super();
	}

	public Comment(Integer id, Task task, String content, LocalDateTime createdAt, User createdBy) {
		super();
		this.id = id;
		this.task = task;
		this.content = content;
		this.createdAt = createdAt;
		this.createdBy = createdBy;
	}

	public Integer getId() {
		return id;
	}

	public void setId(Integer id) {
		this.id = id;
	}

	public Task getTask() {
		return task;
	}

	public void setTask(Task task) {
		this.task = task;
	}

	public String getContent() {
		return content;
	}

	public void setContent(String content) {
		this.content = content;
	}

	public LocalDateTime getCreatedAt() {
		return createdAt;
	}

	public void setCreatedAt(LocalDateTime createdAt) {
		this.createdAt = createdAt;
	}

	public User getCreatedBy() {
		return createdBy;
	}

	public void setCreatedBy(User createdBy) {
		this.createdBy = createdBy;
	}

}