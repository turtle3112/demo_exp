package com.vti.controller;

import com.vti.model.Comment;
import com.vti.model.CommentHistory;
import com.vti.service.AuditLogService;
import com.vti.service.CommentService;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.security.Principal;
import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/comments")
public class CommentController {

	private final CommentService commentService;
	private final AuditLogService auditLogService;

	public CommentController(CommentService commentService, AuditLogService auditLogService) {
		this.commentService = commentService;
		this.auditLogService = auditLogService;
	}

	@PostMapping("/task/{taskId}")
	public ResponseEntity<Comment> createComment(@PathVariable Integer taskId, @RequestBody Map<String, String> request,
			Principal principal) {
		String content = request.get("content");
		Comment comment = commentService.createComment(taskId, content, principal.getName());

		String desc = "Thêm comment vào task ID " + taskId + ": \"" + content + "\"";
		auditLogService.log(principal.getName(), "CREATE", "Comment", comment.getId(), desc);

		return ResponseEntity.ok(comment);
	}

	@GetMapping("/task/{taskId}")
	public ResponseEntity<List<Comment>> getCommentsByTask(@PathVariable Integer taskId) {
		return ResponseEntity.ok(commentService.getCommentsByTask(taskId));
	}

	@PreAuthorize("hasRole('ADMIN')")
	@DeleteMapping("/{commentId}")
	public ResponseEntity<Void> deleteComment(@PathVariable Integer commentId, Principal principal) {
		commentService.deleteComment(commentId, principal.getName());

		String desc = "Xoá comment ID " + commentId;
		auditLogService.log(principal.getName(), "DELETE", "Comment", commentId, desc);

		return ResponseEntity.noContent().build();
	}

	// ✅ ADMIN hoặc NGƯỜI TẠO được sửa comment
	@PatchMapping("/{commentId}")
	public ResponseEntity<Comment> updateComment(@PathVariable Integer commentId,
			@RequestBody Map<String, String> request, Principal principal) {
		String newContent = request.get("content");
		Comment updated = commentService.updateComment(commentId, newContent, principal.getName());

		String desc = "Sửa comment ID " + commentId + ": \"" + newContent + "\"";
		auditLogService.log(principal.getName(), "UPDATE", "Comment", commentId, desc);

		return ResponseEntity.ok(updated);
	}

	@GetMapping("/{commentId}/history")
	public ResponseEntity<List<CommentHistory>> getCommentHistory(@PathVariable Integer commentId) {
		return ResponseEntity.ok(commentService.getCommentHistory(commentId));
	}
	// Thêm endpoint mới để phù hợp với frontend
	@PostMapping
	public ResponseEntity<Comment> createCommentDirect(@RequestBody Map<String, Object> request, Principal principal) {
	    try {
	        String content = (String) request.get("content");
	        Integer taskId = (Integer) request.get("taskId");
	        
	        if (content == null || taskId == null) {
	            return ResponseEntity.badRequest().build();
	        }
	        
	        Comment comment = commentService.createComment(taskId, content, principal.getName());

	        String desc = "Thêm comment vào task ID " + taskId + ": \"" + content + "\"";
	        auditLogService.log(principal.getName(), "CREATE", "Comment", comment.getId(), desc);

	        return ResponseEntity.ok(comment);
	    } catch (Exception e) {
	        e.printStackTrace();
	        return ResponseEntity.badRequest().build();
	    }
	}
}
