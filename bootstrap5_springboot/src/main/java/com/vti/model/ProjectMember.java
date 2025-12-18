package com.vti.model;

import jakarta.persistence.*;
import lombok.*;

@Entity
@IdClass(ProjectMemberId.class)
@Table(name = "`projectmember`")
public class ProjectMember {
	@Id
	private Integer projectId;

	@Id
	private Integer userId;

	public ProjectMember() {
		super();
	}

	public ProjectMember(Integer projectId, Integer userId) {
		super();
		this.projectId = projectId;
		this.userId = userId;
	}

	public Integer getProjectId() {
		return projectId;
	}

	public void setProjectId(Integer projectId) {
		this.projectId = projectId;
	}

	public Integer getUserId() {
		return userId;
	}

	public void setUserId(Integer userId) {
		this.userId = userId;
	}

}