package com.vti.service;

import com.vti.model.Comment;
import com.vti.model.CommentHistory;
import com.vti.model.Task;
import com.vti.model.User;
import com.vti.repository.CommentHistoryRepository;
import com.vti.repository.CommentRepository;
import com.vti.repository.TaskRepository;
import com.vti.repository.UserRepository;
import jakarta.transaction.Transactional;
import org.springframework.security.access.AccessDeniedException;
import org.springframework.stereotype.Service;

import java.time.LocalDateTime;
import java.util.List;

@Service
public class CommentService {

	private final CommentRepository commentRepository;
	private final CommentHistoryRepository commentHistoryRepository;
	private final TaskRepository taskRepository;
	private final UserRepository userRepository;
	private final NotificationService notificationService;

	public CommentService(CommentRepository commentRepository, CommentHistoryRepository commentHistoryRepository,
			TaskRepository taskRepository, UserRepository userRepository, NotificationService notificationService) {
		this.commentRepository = commentRepository;
		this.commentHistoryRepository = commentHistoryRepository;
		this.taskRepository = taskRepository;
		this.userRepository = userRepository;
		this.notificationService = notificationService;
	}

	public Comment createComment(Integer taskId, String content, String username) {
	    Task task = taskRepository.findById(taskId).orElseThrow(() -> 
	        new RuntimeException("Không tìm thấy task với id: " + taskId));
	    User user = userRepository.findByUsername(username).orElseThrow(() -> 
	        new RuntimeException("Không tìm thấy user: " + username));

	    Comment comment = new Comment();
	    comment.setContent(content);
	    comment.setTask(task);
	    comment.setCreatedBy(user);
	    comment.setCreatedAt(LocalDateTime.now());

	    Comment saved = commentRepository.save(comment);

	    // Trigger: Gửi noti khi có comment mới
	    notificationService.notifyCommentAdded(task, user);

	    return saved;
	}

	public List<Comment> getCommentsByTask(Integer taskId) {
		Task task = taskRepository.findById(taskId).orElseThrow();
		return commentRepository.findByTaskOrderByCreatedAtDesc(task);
	}

	public void deleteComment(Integer commentId, String username) {
		Comment comment = commentRepository.findById(commentId).orElseThrow();
		User user = userRepository.findByUsername(username).orElseThrow();

		if (user.getRole() != User.Role.ADMIN) {
			throw new AccessDeniedException("Chỉ admin mới có quyền xoá comment.");
		}

		commentRepository.delete(comment);
	}

	@Transactional
	public Comment updateComment(Integer commentId, String newContent, String username) {
		Comment comment = commentRepository.findById(commentId).orElseThrow();
		User user = userRepository.findByUsername(username).orElseThrow();

		boolean isOwner = comment.getCreatedBy().getUsername().equals(username);
		boolean isAdmin = user.getRole() == User.Role.ADMIN;

		if (!isOwner && !isAdmin) {
			throw new AccessDeniedException("Bạn không có quyền sửa comment này.");
		}

		// Lưu lịch sử trước khi sửa
		CommentHistory history = new CommentHistory();
		history.setComment(comment);
		history.setPreviousContent(comment.getContent());
		history.setEditedAt(LocalDateTime.now());
		commentHistoryRepository.save(history);

		comment.setContent(newContent);
		return commentRepository.save(comment);
	}

	public List<CommentHistory> getCommentHistory(Integer commentId) {
		Comment comment = commentRepository.findById(commentId).orElseThrow();
		return commentHistoryRepository.findByCommentOrderByEditedAtDesc(comment);
	}
}
