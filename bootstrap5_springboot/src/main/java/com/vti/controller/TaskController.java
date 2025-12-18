package com.vti.controller;

import com.vti.model.Project;
import com.vti.model.Task;
import com.vti.model.Task.Status;
import com.vti.model.User;
import com.vti.repository.ProjectRepository;
import com.vti.repository.TaskRepository;
import com.vti.repository.UserRepository;
import com.vti.service.AuditLogService;
import com.vti.service.ProjectMemberService;
import com.vti.service.TaskService;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;
import java.time.LocalDateTime; // THÊM DÒNG NÀY
import java.time.format.DateTimeFormatter;


import java.security.Principal;
import java.time.LocalDate;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.stream.Collectors;

@RestController
@RequestMapping("/tasks")
public class TaskController {

	private final TaskService taskService;
	private final UserRepository userRepository;
	private final ProjectRepository projectRepository;
	private final ProjectMemberService projectMemberService;
	private final AuditLogService auditLogService;
	private final TaskRepository taskRepository;

	public TaskController(TaskService taskService, UserRepository userRepository, ProjectRepository projectRepository,
			ProjectMemberService projectMemberService, AuditLogService auditLogService, TaskRepository taskRepository) {
		this.taskService = taskService;
		this.userRepository = userRepository;
		this.projectRepository = projectRepository;
		this.projectMemberService = projectMemberService;
		this.auditLogService = auditLogService;
		this.taskRepository = taskRepository;
	}
	
	// THÊM CÁC ENDPOINT MỚI CHO PERSONAL TASKS
	@PostMapping("/personal")
	public ResponseEntity<Task> createPersonalTask(@RequestBody Map<String, Object> request, Principal principal) {
	    try {
	        Task task = parseTaskFromRequest(request);
	        
	        // Kiểm tra xem user có phải là chủ project personal không
	        User user = userRepository.findByUsername(principal.getName()).orElseThrow();
	        Project project = task.getProject();
	        
	        // Chỉ cho phép nếu project là PERSONAL và user là người tạo project
	        if (!project.getCreatedBy().getId().equals(user.getId())) {
	            return ResponseEntity.status(403).build();
	        }
	        
	        Task created = taskService.createTask(task);
	        
	        String desc = "Tạo task cá nhân ID " + created.getId() + ": " + created.getName();
	        auditLogService.log(principal.getName(), "CREATE", "Task", created.getId(), desc);
	        
	        return ResponseEntity.ok(created);
	    } catch (Exception e) {
	        return ResponseEntity.badRequest().build();
	    }
	}

	@GetMapping("/personal/project/{projectId}")
	public ResponseEntity<List<Task>> getPersonalTasksByProject(@PathVariable Integer projectId, Principal principal) {
	    try {
	        User user = userRepository.findByUsername(principal.getName()).orElseThrow();
	        Project project = projectRepository.findById(projectId).orElseThrow();
	        
	        // Kiểm tra quyền truy cập project personal
	        if (!project.getCreatedBy().getId().equals(user.getId())) {
	            return ResponseEntity.status(403).build();
	        }
	        
	        List<Task> tasks = taskService.getTasksByProject(projectId);
	        return ResponseEntity.ok(tasks);
	    } catch (Exception e) {
	        return ResponseEntity.badRequest().build();
	    }
	}

	@PutMapping("/personal/{id}")
	public ResponseEntity<Task> updatePersonalTask(@PathVariable Integer id, @RequestBody Map<String, Object> request, Principal principal) {
	    try {
	        Task task = parseTaskFromRequest(request);
	        Task existingTask = taskService.getTaskById(id);
	        User user = userRepository.findByUsername(principal.getName()).orElseThrow();

	        // Kiểm tra quyền
	        if (!existingTask.getProject().getCreatedBy().getId().equals(user.getId())) {
	            return ResponseEntity.status(403).build();
	        }

	        Task updated = taskService.updateTask(id, task);
	        System.out.println("Priority sau khi update: " + updated.getPriority()); 
	        
	        String desc = "Cập nhật task cá nhân ID " + id + ": " + updated.getName();
	        auditLogService.log(principal.getName(), "UPDATE", "Task", id, desc);
	        
	        return ResponseEntity.ok(updated);
	    } catch (Exception e) {
	        return ResponseEntity.badRequest().build();
	    }
	}

	@DeleteMapping("/personal/{id}")
	public ResponseEntity<Void> deletePersonalTask(@PathVariable Integer id, Principal principal) {
	    try {
	        Task task = taskService.getTaskById(id);
	        User user = userRepository.findByUsername(principal.getName()).orElseThrow();

	        // Kiểm tra quyền
	        if (!task.getProject().getCreatedBy().getId().equals(user.getId())) {
	            return ResponseEntity.status(403).build();
	        }

	        taskService.deleteTask(id);
	        
	        String desc = "Xoá task cá nhân ID " + id;
	        auditLogService.log(principal.getName(), "DELETE", "Task", id, desc);
	        
	        return ResponseEntity.noContent().build();
	    } catch (Exception e) {
	        return ResponseEntity.badRequest().build();
	    }
	}
	@PatchMapping("/personal/{id}/status")
	public ResponseEntity<Task> updatePersonalTaskStatus(
	    @PathVariable Integer id, 
	    @RequestBody Map<String, String> statusUpdate, 
	    Principal principal) {
	    
	    try {
	        Task existingTask = taskService.getTaskById(id);
	        User user = userRepository.findByUsername(principal.getName()).orElseThrow();

	        // Kiểm tra quyền
	        if (!existingTask.getProject().getCreatedBy().getId().equals(user.getId())) {
	            return ResponseEntity.status(403).build();
	        }

	        String newStatus = statusUpdate.get("status");
	        if (newStatus == null) {
	            return ResponseEntity.badRequest().build();
	        }

	        // Cập nhật chỉ status
	        existingTask.setStatus(Status.valueOf(newStatus));
	        Task updated = taskService.updateTask(id, existingTask);
	        
	        String desc = "Cập nhật trạng thái task cá nhân ID " + id + " thành " + newStatus;
	        auditLogService.log(principal.getName(), "STATUS_CHANGE", "Task", id, desc);
	        
	        return ResponseEntity.ok(updated);
	    } catch (Exception e) {
	        e.printStackTrace(); // Thêm dòng này để debug
	        return ResponseEntity.badRequest().build();
	    }
	}
	@PreAuthorize("hasRole('ADMIN')")
	@PostMapping
	public ResponseEntity<Task> create(@RequestBody Map<String, Object> request, Principal principal) {
		Task task = parseTaskFromRequest(request);
		Task created = taskService.createTask(task);

		String desc = "Tạo task ID " + created.getId() + ": " + created.getName() + " trong project ID "
				+ created.getProject().getId();
		auditLogService.log(principal.getName(), "CREATE", "Task", created.getId(), desc);

		return ResponseEntity.ok(created);
	}

	@PreAuthorize("hasRole('ADMIN')")
	@PutMapping("/{id}")
	public ResponseEntity<Task> update(@PathVariable Integer id, @RequestBody Map<String, Object> request,
			Principal principal) {
		Task task = parseTaskFromRequest(request);
		Task updated = taskService.updateTask(id, task);

		String desc = "Cập nhật task ID " + id + ": " + updated.getName();
		auditLogService.log(principal.getName(), "UPDATE", "Task", id, desc);

		return ResponseEntity.ok(updated);
	}

	@PreAuthorize("hasRole('ADMIN')")
	@DeleteMapping("/{id}")
	public ResponseEntity<Void> delete(@PathVariable Integer id, Principal principal) {
		taskService.deleteTask(id);

		String desc = "Xoá task ID " + id;
		auditLogService.log(principal.getName(), "DELETE", "Task", id, desc);

		return ResponseEntity.noContent().build();
	}

	@GetMapping("/project/{projectId}")
	public ResponseEntity<List<Task>> getByProject(@PathVariable Integer projectId, Principal principal) {
		User user = userRepository.findByUsername(principal.getName()).orElseThrow();

		if (!user.getRole().equals(User.Role.ADMIN)) {
			boolean isInProject = projectMemberService.isUserInProject(projectId, user.getId());
			if (!isInProject) {
				return ResponseEntity.status(403).build();
			}
		}

		return ResponseEntity.ok(taskService.getTasksByProject(projectId));
	}

	@PatchMapping("/{id}/status")
	public ResponseEntity<Task> updateStatus(@PathVariable Integer id, @RequestBody Task request, Principal principal) {
		Task updated = taskService.updateStatus(id, request.getStatus(), principal.getName());

		String desc = "Cập nhật trạng thái task ID " + id + " thành " + updated.getStatus();
		auditLogService.log(principal.getName(), "STATUS_CHANGE", "Task", id, desc);

		return ResponseEntity.ok(updated);
	}

	@GetMapping("/{id}")
	public ResponseEntity<Task> getTaskById(@PathVariable Integer id, Principal principal) {
		Task task = taskService.getTaskById(id);
		User user = userRepository.findByUsername(principal.getName()).orElseThrow();

		boolean isAdmin = user.getRole().equals(User.Role.ADMIN);
		boolean isAssigned = task.getAssignedUsers().contains(user);
		if (!isAdmin && !isAssigned) {
			return ResponseEntity.status(403).body(null);
		}

		return ResponseEntity.ok(task);
	}

	@PreAuthorize("hasRole('ADMIN')")
	@PostMapping("/{id}/assign")
	public ResponseEntity<Task> assignUsers(@PathVariable Integer id, @RequestBody Map<String, List<Integer>> request,
			Principal principal) {
		List<Integer> userIds = request.get("userIds");
		Task updated = taskService.assignUsers(id, userIds);

		String desc = "Phân công user cho task ID " + id;
		auditLogService.log(principal.getName(), "ASSIGN", "Task", id, desc);

		return ResponseEntity.ok(updated);
	}

	private Task parseTaskFromRequest(Map<String, Object> request) {
	    Object idObj = request.get("id");
	    Task task;

	    if (idObj != null) {
	        Integer id;
	        if (idObj instanceof Number) {
	            id = ((Number) idObj).intValue();
	        } else if (idObj instanceof String) {
	            id = Integer.parseInt((String) idObj);
	        } else {
	            throw new IllegalArgumentException("Id không hợp lệ");
	        }

	        task = taskRepository.findById(id)
	                .orElseThrow(() -> new RuntimeException("Không tìm thấy task với id = " + id));
	    } else {
	        task = new Task();
	    }

	    // Gán project - CHỈ khi có projectId trong request
	    Object projectIdObj = request.get("projectId");
	    if (projectIdObj != null) {
	        final Integer projectIdValue;
	        if (projectIdObj instanceof Number) {
	            projectIdValue = ((Number) projectIdObj).intValue();
	        } else if (projectIdObj instanceof String) {
	            projectIdValue = Integer.parseInt((String) projectIdObj);
	        } else {
	            throw new IllegalArgumentException("projectId không hợp lệ");
	        }
	        Project project = projectRepository.findById(projectIdValue)
	                .orElseThrow(() -> new IllegalArgumentException("Không tìm thấy project với id = " + projectIdValue));
	        task.setProject(project);
	    }

	    // Gán các field cơ bản - CHỈ khi có trong request
	    if (request.containsKey("name")) {
	        task.setName((String) request.get("name"));
	    }
	    
	    if (request.containsKey("description")) {
	        task.setDescription((String) request.get("description"));
	    }
	    
	    // XỬ LÝ deadline - CHỈ khi có trong request
	    if (request.containsKey("deadline")) {
	        String deadlineStr = (String) request.get("deadline");
	        if (deadlineStr != null && !deadlineStr.isBlank()) {
	            task.setDeadline(LocalDate.parse(deadlineStr));
	        } else {
	            task.setDeadline(null);
	        }
	    }
	    
	    // XỬ LÝ startDate và dueDate - CHỈ khi có trong request
	    if (request.containsKey("startDate")) {
	        String startDateStr = (String) request.get("startDate");
	        if (startDateStr != null && !startDateStr.isBlank()) {
	            task.setStartDate(parseDateTime(startDateStr));
	        } else {
	            task.setStartDate(null);
	        }
	    }

	    if (request.containsKey("dueDate")) {
	        String dueDateStr = (String) request.get("dueDate");
	        if (dueDateStr != null && !dueDateStr.isBlank()) {
	            task.setDueDate(parseDateTime(dueDateStr));
	        } else {
	            task.setDueDate(null);
	        }
	    }

	    
	    // Gán status nếu có
	    if (request.containsKey("status")) {
	        Object statusObj = request.get("status");
	        if (statusObj != null) {
	            try {
	                task.setStatus(Status.valueOf(statusObj.toString()));
	            } catch (IllegalArgumentException e) {
	                throw new IllegalArgumentException("Giá trị status không hợp lệ: " + statusObj);
	            }
	        }
	    }
	    
	    // Gán priority nếu có
	    if (request.containsKey("priority")) {
	        Object priorityObj = request.get("priority");
	        if (priorityObj != null) {
	            try {
	                task.setPriority(Task.Priority.valueOf(priorityObj.toString()));
	            } catch (IllegalArgumentException e) {
	                throw new IllegalArgumentException("Giá trị priority không hợp lệ: " + priorityObj);
	            }
	        }
	    }
	    
	    // Gán danh sách assignedUsers nếu có
	    if (request.containsKey("assignedUsers")) {
	        List<Integer> userIds = (List<Integer>) request.get("assignedUsers");
	        Set<User> users = userIds.stream()
	                .map(uid -> userRepository.findById(uid)
	                        .orElseThrow(() -> new IllegalArgumentException("Không tìm thấy user với id = " + uid)))
	                .collect(Collectors.toSet());
	        task.setAssignedUsers(users);
	    }

	    return task;
	}
	// ====== THÊM CÁC ENDPOINT CHO GROUPS ======

	@GetMapping("/groups/project/{projectId}")
	public ResponseEntity<List<Task>> getGroupTasksByProject(@PathVariable Integer projectId, Principal principal) {
	    try {
	        User user = userRepository.findByUsername(principal.getName()).orElseThrow();
	        
	        // Kiểm tra user có trong project không
	        boolean isInProject = projectMemberService.isUserInProject(projectId, user.getId());
	        if (!isInProject && !user.getRole().equals(User.Role.ADMIN)) {
	            return ResponseEntity.status(403).build();
	        }
	        
	        List<Task> tasks = taskService.getTasksByProject(projectId);
	        return ResponseEntity.ok(tasks);
	    } catch (Exception e) {
	        return ResponseEntity.badRequest().build();
	    }
	}

	@PostMapping("/groups")
	public ResponseEntity<Task> createGroupTask(@RequestBody Map<String, Object> request, Principal principal) {
	    try {
	        Task task = parseTaskFromRequest(request);
	        User user = userRepository.findByUsername(principal.getName()).orElseThrow();
	        
	        // Kiểm tra quyền tạo task trong project
	        boolean isInProject = projectMemberService.isUserInProject(task.getProject().getId(), user.getId());
	        if (!isInProject && !user.getRole().equals(User.Role.ADMIN)) {
	            return ResponseEntity.status(403).build();
	        }
	        
	        Task created = taskService.createTask(task);
	        
	        String desc = "Tạo task nhóm ID " + created.getId() + ": " + created.getName();
	        auditLogService.log(principal.getName(), "CREATE", "Task", created.getId(), desc);
	        
	        return ResponseEntity.ok(created);
	    } catch (Exception e) {
	        e.printStackTrace();
	        return ResponseEntity.badRequest().build();
	    }
	}

	@PutMapping("/groups/{id}")
	public ResponseEntity<Task> updateGroupTask(@PathVariable Integer id, @RequestBody Map<String, Object> request, Principal principal) {
	    try {
	        Task existingTask = taskService.getTaskById(id);
	        User user = userRepository.findByUsername(principal.getName()).orElseThrow();

	        // Kiểm tra quyền - chỉ admin hoặc chủ project được sửa
	        if (!hasEditPermission(existingTask, user)) {
	            return ResponseEntity.status(403).build();
	        }

	        Task task = parseTaskFromRequest(request);
	        Task updated = taskService.updateTask(id, task);
	        
	        String desc = "Cập nhật task nhóm ID " + id + ": " + updated.getName();
	        auditLogService.log(principal.getName(), "UPDATE", "Task", id, desc);
	        
	        return ResponseEntity.ok(updated);
	    } catch (Exception e) {
	        e.printStackTrace();
	        return ResponseEntity.badRequest().build();
	    }
	}

	@DeleteMapping("/groups/{id}")
	public ResponseEntity<Void> deleteGroupTask(@PathVariable Integer id, Principal principal) {
	    try {
	        Task task = taskService.getTaskById(id);
	        User user = userRepository.findByUsername(principal.getName()).orElseThrow();

	        // Kiểm tra quyền - chỉ admin hoặc chủ project được xóa
	        if (!hasEditPermission(task, user)) {
	            return ResponseEntity.status(403).build();
	        }

	        taskService.deleteTask(id);
	        
	        String desc = "Xoá task nhóm ID " + id;
	        auditLogService.log(principal.getName(), "DELETE", "Task", id, desc);
	        
	        return ResponseEntity.noContent().build();
	    } catch (Exception e) {
	        e.printStackTrace();
	        return ResponseEntity.badRequest().build();
	    }
	}

	@PatchMapping("/groups/{id}/status")
	public ResponseEntity<Task> updateGroupTaskStatus(
		    @PathVariable Integer id, 
		    @RequestBody Map<String, String> statusUpdate, 
		    Principal principal) {
		    
		    try {
		        Task existingTask = taskService.getTaskById(id);
		        User user = userRepository.findByUsername(principal.getName()).orElseThrow();

		        // Kiểm tra quyền - chỉ admin hoặc chủ project được đổi trạng thái
				/*
				 * if (!hasEditPermission(existingTask, user)) { return
				 * ResponseEntity.status(403).build(); }
				 */

		        String newStatus = statusUpdate.get("status");
		        if (newStatus == null) {
		            return ResponseEntity.badRequest().build();
		        }

		        existingTask.setStatus(Status.valueOf(newStatus));
		        Task updated = taskService.updateTask(id, existingTask);
		        
		        String desc = "Cập nhật trạng thái task nhóm ID " + id + " thành " + newStatus;
		        auditLogService.log(principal.getName(), "STATUS_CHANGE", "Task", id, desc);
		        
		        return ResponseEntity.ok(updated);
		    } catch (Exception e) {
		        e.printStackTrace();
		        return ResponseEntity.badRequest().build();
		    }
		}
	@GetMapping("/groups/{id}")
	public ResponseEntity<Task> getGroupTaskById(@PathVariable Integer id, Principal principal) {
	    try {
	        Task task = taskService.getTaskById(id);
	        User user = userRepository.findByUsername(principal.getName()).orElseThrow();
	        
	        // Kiểm tra user có trong project không
	        boolean isInProject = projectMemberService.isUserInProject(task.getProject().getId(), user.getId());
	        if (!isInProject && !user.getRole().equals(User.Role.ADMIN)) {
	            return ResponseEntity.status(403).build();
	        }
	        
	        return ResponseEntity.ok(task);
	    } catch (Exception e) {
	        e.printStackTrace();
	        return ResponseEntity.badRequest().build();
	    }
	}
	@GetMapping("/personal/{id}")
	public ResponseEntity<Task> getPersonalTaskById(@PathVariable Integer id, Principal principal) {
	    try {
	        Task task = taskService.getTaskById(id);
	        User user = userRepository.findByUsername(principal.getName()).orElseThrow();

	        // Kiểm tra quyền: chỉ chủ sở hữu project của task mới được xem
	        if (!task.getProject().getCreatedBy().getId().equals(user.getId())) {
	            return ResponseEntity.status(403).build();
	        }

	        return ResponseEntity.ok(task);
	    } catch (Exception e) {
	        e.printStackTrace();
	        return ResponseEntity.badRequest().build();
	    }
	}
	
	// THÊM METHOD NÀY VÀO CUỐI CLASS TaskController (trước dấu "}")
	private boolean hasEditPermission(Task task, User user) {
	    // ADMIN có toàn quyền
	    if (user.getRole().equals(User.Role.ADMIN)) {
	        return true;
	    }
	    
	    // Người tạo project có toàn quyền
	    if (task.getProject().getCreatedBy().getId().equals(user.getId())) {
	        return true;
	    }
	    
	    // Thành viên thông thường chỉ được xem
	    return false;
	}
	
	private static final DateTimeFormatter DATE_TIME_FORMATTER =
	        DateTimeFormatter.ofPattern("yyyy-MM-dd'T'HH:mm");

	private static final DateTimeFormatter DATE_TIME_FORMATTER_WITH_SECONDS =
	        DateTimeFormatter.ofPattern("yyyy-MM-dd'T'HH:mm:ss");
	
	private LocalDateTime parseDateTime(String value) {
	    try {
	        // cố parse dạng đầy đủ
	        return LocalDateTime.parse(value, DATE_TIME_FORMATTER_WITH_SECONDS);
	    } catch (Exception e) {
	        // fallback khi FE không gửi giây
	        return LocalDateTime.parse(value, DATE_TIME_FORMATTER);
	    }
	}

}


