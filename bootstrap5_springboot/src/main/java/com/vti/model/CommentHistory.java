package com.vti.model;

import jakarta.persistence.*;
import java.time.LocalDateTime;

@Entity
@Table(name = "comment_history")
public class CommentHistory {

	@Id
	@GeneratedValue(strategy = GenerationType.IDENTITY)
	private Integer id;

	@ManyToOne
	@JoinColumn(name = "comment_id", nullable = false)
	private Comment comment;

	@Column(columnDefinition = "TEXT", nullable = false)
	private String previousContent;

	@Column(nullable = false)
	private LocalDateTime editedAt;

	public CommentHistory() {
		super();
	}

	public CommentHistory(Integer id, Comment comment, String previousContent, LocalDateTime editedAt) {
		super();
		this.id = id;
		this.comment = comment;
		this.previousContent = previousContent;
		this.editedAt = editedAt;
	}

	public Integer getId() {
		return id;
	}

	public void setId(Integer id) {
		this.id = id;
	}

	public Comment getComment() {
		return comment;
	}

	public void setComment(Comment comment) {
		this.comment = comment;
	}

	public String getPreviousContent() {
		return previousContent;
	}

	public void setPreviousContent(String previousContent) {
		this.previousContent = previousContent;
	}

	public LocalDateTime getEditedAt() {
		return editedAt;
	}

	public void setEditedAt(LocalDateTime editedAt) {
		this.editedAt = editedAt;
	}
}
