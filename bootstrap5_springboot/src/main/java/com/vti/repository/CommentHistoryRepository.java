package com.vti.repository;

import com.vti.model.CommentHistory;
import com.vti.model.Comment;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface CommentHistoryRepository extends JpaRepository<CommentHistory, Integer> {
	List<CommentHistory> findByComment(Comment comment);

	List<CommentHistory> findByCommentOrderByEditedAtDesc(Comment comment);

	void deleteByCommentId(Integer id);
}
