package com.vti.model;

import jakarta.persistence.*;
import lombok.*;
import java.time.LocalDateTime;

@Entity
@Table(name = "`notification`")
public class Notification {
	@Id
	@GeneratedValue(strategy = GenerationType.IDENTITY)
	private Integer id;

	@ManyToOne
	@JoinColumn(name = "user_id", nullable = false)
	private User user;

	@Column(columnDefinition = "TEXT", nullable = false)
	private String message;

	@Column(name = "is_read")
	private Boolean isRead = false;

	@Column(name = "created_at")
	private LocalDateTime createdAt = LocalDateTime.now();

	public Notification() {
		super();
	}

	public Notification(Integer id, User user, String message, Boolean isRead, LocalDateTime createdAt) {
		super();
		this.id = id;
		this.user = user;
		this.message = message;
		this.isRead = isRead;
		this.createdAt = createdAt;
	}

	public Integer getId() {
		return id;
	}

	public void setId(Integer id) {
		this.id = id;
	}

	public User getUser() {
		return user;
	}

	public void setUser(User user) {
		this.user = user;
	}

	public String getMessage() {
		return message;
	}

	public void setMessage(String message) {
		this.message = message;
	}

	public Boolean getIsRead() {
		return isRead;
	}

	public void setIsRead(Boolean isRead) {
		this.isRead = isRead;
	}

	public LocalDateTime getCreatedAt() {
		return createdAt;
	}

	public void setCreatedAt(LocalDateTime createdAt) {
		this.createdAt = createdAt;
	}

}
