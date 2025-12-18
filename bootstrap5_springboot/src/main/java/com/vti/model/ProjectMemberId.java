package com.vti.model;

import lombok.*;
import java.io.Serializable;
import java.util.Objects;

public class ProjectMemberId implements Serializable {
	private Integer projectId;
	private Integer userId;

	public ProjectMemberId() {
		super();
	}

	public ProjectMemberId(Integer projectId, Integer userId) {
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

	@Override
	public boolean equals(Object o) {
		if (this == o)
			return true;
		if (!(o instanceof ProjectMemberId that))
			return false;
		return Objects.equals(projectId, that.projectId) && Objects.equals(userId, that.userId);
	}

	@Override
	public int hashCode() {
		return Objects.hash(projectId, userId);
	}

}