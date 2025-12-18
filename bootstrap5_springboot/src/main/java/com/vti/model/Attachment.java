package com.vti.model;

import jakarta.persistence.*;
import lombok.*;
import java.time.LocalDateTime;
import java.util.List;

@Entity
@Table(name = "`attachment`")
public class Attachment {
	@Id
	@GeneratedValue(strategy = GenerationType.IDENTITY)
	private Integer id;

	@ManyToOne
	@JoinColumn(name = "task_id", nullable = false)
	private Task task;

	@Column(name = "file_name", nullable = false)
	private String fileName;

	@Column(name = "file_path", nullable = false)
	private String filePath;

	@ManyToOne
	@JoinColumn(name = "uploaded_by", nullable = false)
	private User uploadedBy;

	@Column(name = "uploaded_at")
	private LocalDateTime uploadedAt = LocalDateTime.now();
	
	@Column(name = "file_size")
	private Long fileSize;

	public Attachment() {
		super();
	}

	public Attachment(Integer id, Task task, String fileName, String filePath, User uploadedBy,
			LocalDateTime uploadedAt) {
		super();
		this.id = id;
		this.task = task;
		this.fileName = fileName;
		this.filePath = filePath;
		this.uploadedBy = uploadedBy;
		this.uploadedAt = uploadedAt;
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

	public String getFileName() {
		return fileName;
	}

	public void setFileName(String fileName) {
		this.fileName = fileName;
	}

	public String getFilePath() {
		return filePath;
	}

	public void setFilePath(String filePath) {
		this.filePath = filePath;
	}

	public User getUploadedBy() {
		return uploadedBy;
	}

	public void setUploadedBy(User uploadedBy) {
		this.uploadedBy = uploadedBy;
	}

	public LocalDateTime getUploadedAt() {
		return uploadedAt;
	}

	public void setUploadedAt(LocalDateTime uploadedAt) {
		this.uploadedAt = uploadedAt;
	}

	
	public Long getFileSize() {
	    return fileSize;
	}

	public void setFileSize(Long fileSize) {
	    this.fileSize = fileSize;
	}

}
