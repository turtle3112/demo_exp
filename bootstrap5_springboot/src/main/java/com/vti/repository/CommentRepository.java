package com.vti.repository;

import com.vti.model.Comment;
import com.vti.model.Task;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface CommentRepository extends JpaRepository<Comment, Integer> {
    List<Comment> findByTaskOrderByCreatedAtDesc(Task task);

	void deleteByTaskId(Integer taskId);

	List<Comment> findByTaskId(Integer taskId);
}
