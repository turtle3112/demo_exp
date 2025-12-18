package com.vti.controller;

import com.vti.model.Attachment;
import com.vti.model.User;
import com.vti.service.AttachmentService;
import com.vti.service.AuditLogService;
import org.springframework.core.io.Resource;
import org.springframework.http.HttpHeaders;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;
import com.vti.repository.UserRepository;
import java.security.Principal;
import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/attachments")
public class AttachmentController {

	private final AttachmentService attachmentService;
	private final AuditLogService auditLogService;
	private final UserRepository userRepository;
	

	public AttachmentController(AttachmentService attachmentService, AuditLogService auditLogService, UserRepository userRepository) {
		this.attachmentService = attachmentService;
		this.auditLogService = auditLogService;
		this.userRepository = userRepository;
	}

	@PostMapping("/task/{taskId}")
	public ResponseEntity<Attachment> upload(
	        @PathVariable Integer taskId, 
	        @RequestParam("file") MultipartFile file,
	        Principal principal) {
	    
	    System.out.println("=== UPLOAD ENDPOINT CALLED ===");
	    System.out.println("Task ID: " + taskId);
	    System.out.println("File name: " + file.getOriginalFilename());
	    System.out.println("File size: " + file.getSize());
	    System.out.println("Content type: " + file.getContentType());
	    System.out.println("User: " + principal.getName());
	    
	    
	    try {
	        Attachment attachment = attachmentService.upload(taskId, file, principal.getName());

	        String desc = "Upload file \"" + attachment.getFileName() + "\" vào task ID " + taskId;
	        auditLogService.log(principal.getName(), "CREATE", "Attachment", attachment.getId(), desc);

	        return ResponseEntity.ok(attachment);
	    } catch (Exception e) {
	        System.err.println("Upload error: " + e.getMessage());
	        e.printStackTrace();
	        return ResponseEntity.status(500).build();
	    }
	}
	
	@GetMapping("/personal")
	public ResponseEntity<List<Attachment>> getUserAttachments(Principal principal) {
		
		try {
			System.out.println("=== GET USER ATTACHMENTS ===");
			System.out.println("User: " + principal.getName());
			
			// Tìm user bằng username
			User user = userRepository.findByUsername(principal.getName())
				.orElseThrow(() -> new RuntimeException("User not found: " + principal.getName()));
			
			System.out.println("User ID: " + user.getId());
			
			// Gọi service method mới
			List<Attachment> attachments = attachmentService.getAttachmentsByUserId(user.getId());
			System.out.println("Found " + attachments.size() + " attachments");
			
			return ResponseEntity.ok(attachments);
		} catch (Exception e) {
			System.err.println("Error getting user attachments: " + e.getMessage());
			e.printStackTrace();
			return ResponseEntity.badRequest().build();
		}
	}

	// Danh sách file theo task
	@GetMapping("/task/{taskId}")
	public ResponseEntity<List<Attachment>> getByTask(@PathVariable Integer taskId) {
		return ResponseEntity.ok(attachmentService.getByTask(taskId));
	}

	// Tải file
	@GetMapping("/download/{id}")
	public ResponseEntity<Resource> download(@PathVariable Integer id) {
		Resource file = attachmentService.download(id);
		Attachment attachment = attachmentService.getMetadata(id);
		return ResponseEntity.ok()
				.header(HttpHeaders.CONTENT_DISPOSITION, "attachment; filename=\"" + attachment.getFileName() + "\"")
				.body(file);
	}

	// Xem file
	@GetMapping("/preview/{id}")
	public ResponseEntity<Resource> preview(@PathVariable Integer id) {
		Resource file = attachmentService.download(id);
		return ResponseEntity.ok().body(file);
	}

	// Xoá file
	@DeleteMapping("/{id}")
	public ResponseEntity<Void> delete(@PathVariable Integer id, Principal principal) {
		attachmentService.deleteAttachment(id, principal.getName());

		String desc = "Xoá file đính kèm ID " + id;
		auditLogService.log(principal.getName(), "DELETE", "Attachment", id, desc);

		return ResponseEntity.noContent().build();
	}
	
	// Thêm method này vào AttachmentController
	@GetMapping("/groups")
	public ResponseEntity<List<Attachment>> getGroupAttachments(Principal principal) {
	    try {
	        User user = userRepository.findByUsername(principal.getName())
	            .orElseThrow(() -> new RuntimeException("User not found"));
	        
	        // Gọi service method mới để lấy tất cả file trong nhóm
	        List<Attachment> attachments = attachmentService.getGroupAttachments(user);
	        return ResponseEntity.ok(attachments);
	    } catch (Exception e) {
	        System.err.println("Error getting group attachments: " + e.getMessage());
	        return ResponseEntity.badRequest().build();
	    }
	}

	// API để lấy thống kê dung lượng nhóm
	@GetMapping("/groups/stats")
	public ResponseEntity<Map<String, Object>> getGroupStorageStats(Principal principal) {
	    try {
	        User user = userRepository.findByUsername(principal.getName())
	            .orElseThrow(() -> new RuntimeException("User not found"));
	        
	        Map<String, Object> stats = attachmentService.getGroupStorageStats(user);
	        return ResponseEntity.ok(stats);
	    } catch (Exception e) {
	        return ResponseEntity.badRequest().build();
	    }
	}

}