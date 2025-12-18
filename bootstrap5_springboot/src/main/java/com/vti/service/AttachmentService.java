package com.vti.service;

import com.vti.model.Attachment;
import com.vti.model.Task;
import com.vti.model.User;
import com.vti.repository.AttachmentRepository;
import com.vti.repository.TaskRepository;
import com.vti.repository.UserRepository;
import org.springframework.core.io.FileSystemResource;
import org.springframework.core.io.Resource;
import org.springframework.stereotype.Service;
import org.springframework.web.multipart.MultipartFile;

import java.io.File;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.time.ZoneId;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

@Service
public class AttachmentService {

	private final AttachmentRepository attachmentRepository;
	private final TaskRepository taskRepository;
	private final UserRepository userRepository;
	private final NotificationService notificationService;
	private final String uploadDir = "uploads";

	public AttachmentService(AttachmentRepository attachmentRepository, TaskRepository taskRepository,
			UserRepository userRepository, NotificationService notificationService) {
		this.attachmentRepository = attachmentRepository;
		this.taskRepository = taskRepository;
		this.userRepository = userRepository;
		this.notificationService = notificationService;
	}

	public Attachment upload(Integer taskId, MultipartFile file, String username) {
	    System.out.println("=== ATTACHMENT UPLOAD SERVICE ===");
	    System.out.println("File: " + file.getOriginalFilename() + " (" + file.getSize() + " bytes)");
	    
	    // VALIDATION
	    if (file.isEmpty()) {
	        throw new IllegalArgumentException("File trống");
	    }
	    
	    Task task = taskRepository.findById(taskId)
	            .orElseThrow(() -> new RuntimeException("Không tìm thấy task: " + taskId));
	    User user = userRepository.findByUsername(username)
	            .orElseThrow(() -> new RuntimeException("Không tìm thấy user: " + username));

	    try {
	        // Tạo thư mục upload
	        Path uploadPath = Paths.get(uploadDir);
	        if (!Files.exists(uploadPath)) {
	            Files.createDirectories(uploadPath);
	            System.out.println("Đã tạo thư mục: " + uploadPath.toAbsolutePath());
	        }

	        String fileName = System.currentTimeMillis() + "_" + file.getOriginalFilename();
	        String filePath = uploadDir + File.separator + fileName;
	        
	        System.out.println("Lưu file tại: " + filePath);
	        Files.write(Paths.get(filePath), file.getBytes());

	        // Tạo và lưu attachment
	        Attachment attachment = new Attachment();
	        attachment.setTask(task);
	        attachment.setUploadedBy(user);
	        attachment.setFileName(file.getOriginalFilename());
	        attachment.setFilePath(filePath);
	        
	        attachment.setFileSize(file.getSize());  

	        Attachment savedAttachment = attachmentRepository.save(attachment);
	        System.out.println("✅ Upload thành công! ID: " + savedAttachment.getId());
	        
	        return savedAttachment;

	    } catch (IOException e) {
	        System.err.println("❌ Lỗi IO: " + e.getMessage());
	        throw new RuntimeException("Upload thất bại: " + e.getMessage(), e);
	    }
	}

	public List<Attachment> getByTask(Integer taskId) {
		return attachmentRepository.findByTaskId(taskId);
	}

	public Resource download(Integer id) {
		Attachment attachment = attachmentRepository.findById(id)
				.orElseThrow(() -> new RuntimeException("Attachment not found"));
		return new FileSystemResource(attachment.getFilePath());
	}

	public Attachment getMetadata(Integer id) {
		return attachmentRepository.findById(id).orElseThrow(() -> new RuntimeException("Attachment not found"));
	}

	public void deleteAttachment(Integer id, String username) {
		Attachment attachment = attachmentRepository.findById(id)
				.orElseThrow(() -> new RuntimeException("Attachment not found"));

		User user = userRepository.findByUsername(username).orElseThrow(() -> new RuntimeException("User not found"));

		boolean isAdmin = user.getRole() == User.Role.ADMIN;
		boolean isUploader = attachment.getUploadedBy().getId().equals(user.getId());

		if (!isAdmin && !isUploader) {
			throw new RuntimeException("You are not authorized to delete this attachment.");
		}

		File file = new File(attachment.getFilePath());
		if (file.exists()) {
			file.delete();
		}

		attachmentRepository.delete(attachment);
	}
	// THÊM METHOD ĐỂ HỖ TRỢ UPLOAD NHIỀU FILE
	public List<Attachment> uploadMultipleFiles(Integer taskId, MultipartFile[] files, String username) {
	    List<Attachment> attachments = new ArrayList<>();
	    for (MultipartFile file : files) {
	        Attachment attachment = this.upload(taskId, file, username);
	        attachments.add(attachment);
	    }
	    return attachments;
	}
	
	public List<Attachment> getAttachmentsByUserId(Integer userId) {
	    try {
	        System.out.println("🔄 Getting attachments for user ID: " + userId);
	        
	        // CÁCH 1: Sử dụng query theo uploadedBy (nếu bạn muốn lấy file user đã upload)
	        List<Attachment> attachments = attachmentRepository.findByUploadedById(userId);
	        
	        
	        System.out.println("✅ Found " + attachments.size() + " attachments for user " + userId);
	        return attachments;
	        
	    } catch (Exception e) {
	        System.err.println("❌ Error getting attachments for user " + userId + ": " + e.getMessage());
	        e.printStackTrace();
	        return new ArrayList<>(); // Trả về list rỗng thay vì throw exception
	    }
	}
	
	public List<Attachment> getGroupAttachments(User user) {
	    try {
	        System.out.println("🔄 Getting group attachments for user: " + user.getUsername());
	        
	        // Logic để lấy tất cả file trong nhóm của user
	        // Giả sử mỗi user có organization/group
	        if (user.getOrganization() != null) {
	            // Lấy tất cả users trong cùng organization
	            List<User> groupUsers = userRepository.findByOrganization(user.getOrganization());
	            List<Integer> userIds = groupUsers.stream().map(User::getId).collect(Collectors.toList());
	            
	            // Lấy tất cả attachments của các user trong nhóm
	            return attachmentRepository.findByUploadedByIdIn(userIds);
	        } else {
	            // Fallback: nếu không có organization, trả về file của chính user
	            return attachmentRepository.findByUploadedById(user.getId());
	        }
	    } catch (Exception e) {
	        System.err.println("❌ Error getting group attachments: " + e.getMessage());
	        return new ArrayList<>();
	    }
	}
	
	public Map<String, Object> getGroupStorageStats(User user) {
	    Map<String, Object> stats = new HashMap<>();
	    
	    try {
	        List<Attachment> groupAttachments = getGroupAttachments(user);
	        
	        long totalSize = groupAttachments.stream()
	            .mapToLong(attachment -> attachment.getFileSize() != null ? attachment.getFileSize() : 0)
	            .sum();
	        
	        // Giả sử mỗi nhóm có 1GB dung lượng
	        long maxStorage = 1024 * 1024 * 1024; // 1GB
	        long remaining = Math.max(0, maxStorage - totalSize);
	        
	        // Đếm số file trong 7 ngày gần đây
	        long recentCount = groupAttachments.stream()
	            .filter(attachment -> {
	                if (attachment.getUploadedAt() == null) return false;
	                long diffDays = (System.currentTimeMillis() - attachment.getUploadedAt().atZone(ZoneId.systemDefault()).toInstant().toEpochMilli()) 
	                              / (1000 * 60 * 60 * 24);
	                return diffDays < 7;
	            })
	            .count();
	        
	        stats.put("totalDocuments", groupAttachments.size());
	        stats.put("recentDocuments", recentCount);
	        stats.put("usedStorage", totalSize);
	        stats.put("remainingStorage", remaining);
	        stats.put("maxStorage", maxStorage);
	        
	    } catch (Exception e) {
	        System.err.println("❌ Error calculating storage stats: " + e.getMessage());
	        // Trả về giá trị mặc định nếu có lỗi
	        stats.put("totalDocuments", 0);
	        stats.put("recentDocuments", 0);
	        stats.put("usedStorage", 0);
	        stats.put("remainingStorage", 1024 * 1024 * 1024); // 1GB
	        stats.put("maxStorage", 1024 * 1024 * 1024);
	    }
	    
	    return stats;
	}
}