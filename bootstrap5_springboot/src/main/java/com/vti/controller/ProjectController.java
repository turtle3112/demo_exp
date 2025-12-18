package com.vti.controller;

import com.vti.model.Organization;
import com.vti.model.Project;
import com.vti.model.ProjectMember;
import com.vti.model.User;
import com.vti.repository.ProjectRepository;
import com.vti.repository.UserRepository;
import com.vti.service.AuditLogService;
import com.vti.service.ProjectService;

import dto.ProjectDTO;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.security.Principal;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

@RestController
@RequestMapping("/projects")
public class ProjectController {

	private final ProjectService projectService;
	private final UserRepository userRepository;
	private final ProjectRepository projectRepository;
	private final AuditLogService auditLogService;

	// 🚨 THÊM HẰNG SỐ GIỚI HẠN
	private static final int MAX_PROJECTS_PER_GROUP = 7;

	public ProjectController(ProjectService projectService, UserRepository userRepository,
			ProjectRepository projectRepository, AuditLogService auditLogService) {
		this.projectService = projectService;
		this.userRepository = userRepository;
		this.projectRepository = projectRepository;
		this.auditLogService = auditLogService;
	}

	@GetMapping("/all")
	public ResponseEntity<List<Project>> getAll() {
		return ResponseEntity.ok(projectService.getAllProjects());
	}

	@GetMapping("/{id}")
	public ResponseEntity<Project> getById(@PathVariable Integer id) {
		return ResponseEntity.ok(projectService.getProjectById(id));
	}

	@PreAuthorize("hasRole('ADMIN')")
	@PostMapping("/add")
	public ResponseEntity<Project> create(@RequestBody Project project, Principal principal) {
		User admin = userRepository.findByUsername(principal.getName()).orElseThrow();
		project.setCreatedBy(admin);
		Project created = projectService.createProject(project);

		String desc = "Tạo mới project ID " + created.getId() + ": " + created.getName();
		auditLogService.log(principal.getName(), "CREATE", "Project", created.getId(), desc);

		return ResponseEntity.ok(created);
	}

	@PreAuthorize("hasRole('ADMIN')")
	@PutMapping("/{id}")
	public ResponseEntity<?> update(@PathVariable Integer id, @RequestBody Project projectUpdates, Principal principal) {
		try {
			System.out.println("🔧 [DEBUG] Bắt đầu cập nhật project ID: " + id);
			System.out.println("🔧 [DEBUG] Dữ liệu nhận: " + projectUpdates);
			
			// 1. Lấy project hiện tại từ database
			Project existingProject = projectService.getProjectById(id);
			if (existingProject == null) {
				return ResponseEntity.status(404).body(Map.of("error", "Không tìm thấy dự án"));
			}
			
			// 2. Lấy user hiện tại
			User currentUser = userRepository.findByUsername(principal.getName()).orElseThrow();
			
			System.out.println("=== DEBUG UPDATE PROJECT ===");
			System.out.println("User: " + currentUser.getUsername());
			System.out.println("User Role: " + currentUser.getRole());
			System.out.println("User Org: " + (currentUser.getOrganization() != null ? currentUser.getOrganization().getId() : "null"));
			System.out.println("Project ID: " + id);
			System.out.println("Project Name: " + existingProject.getName());
			System.out.println("Project Org: " + (existingProject.getOrganization() != null ? existingProject.getOrganization().getId() : "null"));
			System.out.println("Project Type: " + existingProject.getProjectType());
			
			// 3. KIỂM TRA QUYỀN QUAN TRỌNG: Dự án phải thuộc tổ chức của user
			if (existingProject.getOrganization() == null) {
				System.out.println("❌ [DEBUG] Dự án không có organization");
				return ResponseEntity.status(403).body(Map.of("error", "Dự án không thuộc tổ chức nào"));
			}
			
			if (currentUser.getOrganization() == null) {
				System.out.println("❌ [DEBUG] User không có organization");
				return ResponseEntity.status(403).body(Map.of("error", "Bạn không thuộc tổ chức nào"));
			}
			
			if (!existingProject.getOrganization().getId().equals(currentUser.getOrganization().getId())) {
				System.out.println("❌ [DEBUG] Organization không khớp");
				System.out.println("Project Org ID: " + existingProject.getOrganization().getId());
				System.out.println("User Org ID: " + currentUser.getOrganization().getId());
				return ResponseEntity.status(403).body(Map.of("error", "Dự án không thuộc tổ chức của bạn"));
			}
			
			// 4. Kiểm tra project type (phải là ENTERPRISE cho business project)
			if (!existingProject.getProjectType().equals(Project.ProjectType.ENTERPRISE)) {
				System.out.println("❌ [DEBUG] Project type không phải ENTERPRISE: " + existingProject.getProjectType());
				return ResponseEntity.status(400).body(Map.of("error", "Chỉ được cập nhật dự án doanh nghiệp"));
			}
			
			// 5. Cập nhật chỉ các trường được phép (không đụng đến organization và createdBy)
			if (projectUpdates.getName() != null && !projectUpdates.getName().trim().isEmpty()) {
				existingProject.setName(projectUpdates.getName().trim());
				System.out.println("🔧 [DEBUG] Đã cập nhật tên: " + existingProject.getName());
			} else {
				return ResponseEntity.badRequest().body(Map.of("error", "Tên dự án không được để trống"));
			}
			
			if (projectUpdates.getDescription() != null) {
				existingProject.setDescription(projectUpdates.getDescription().trim());
			} else {
				existingProject.setDescription(null);
			}
			
			// 6. Xử lý deadlineDate - QUAN TRỌNG: dùng setDeadlineDate() thay vì setDeadline()
			System.out.println("🔧 [DEBUG] deadlineDate từ request: " + projectUpdates.getDeadlineDate());
			System.out.println("🔧 [DEBUG] deadline từ request: " + projectUpdates.getDeadline());
			
			if (projectUpdates.getDeadlineDate() != null) {
				// Dùng setDeadlineDate() để chuyển đổi LocalDate -> LocalDateTime
				existingProject.setDeadlineDate(projectUpdates.getDeadlineDate());
				System.out.println("🔧 [DEBUG] Đã set deadlineDate: " + existingProject.getDeadline());
			} else if (projectUpdates.getDeadline() != null) {
				// Nếu gửi thẳng LocalDateTime
				existingProject.setDeadline(projectUpdates.getDeadline());
			} else {
				// Nếu không gửi deadline, đặt thành null
				existingProject.setDeadline(null);
				System.out.println("🔧 [DEBUG] Đã đặt deadline thành null");
			}
			
			// 7. Giữ nguyên các trường quan trọng KHÔNG THAY ĐỔI
			// - Organization: giữ nguyên
			// - CreatedBy: giữ nguyên
			// - ProjectType: giữ nguyên
			// - CreatedAt: giữ nguyên
			
			// 8. Cập nhật project
			Project updated;
			try {
				updated = projectService.updateProject(id, existingProject);
				System.out.println("✅ [DEBUG] Cập nhật thành công: " + updated);
			} catch (Exception e) {
				System.out.println("❌ [DEBUG] Lỗi khi gọi service.updateProject: " + e.getMessage());
				e.printStackTrace();
				throw e;
			}

			// 9. Ghi audit log
			String desc = "Cập nhật project ID " + id + ": " + updated.getName();
			auditLogService.log(principal.getName(), "UPDATE", "Project", id, desc);

			return ResponseEntity.ok(updated);
		} catch (Exception e) {
			System.out.println("❌ [DEBUG] Lỗi khi cập nhật project: " + e.getMessage());
			e.printStackTrace();
			
			// Trả về thông báo lỗi chi tiết
			Map<String, Object> errorResponse = new HashMap<>();
			errorResponse.put("error", "Không thể cập nhật dự án");
			errorResponse.put("message", e.getMessage());
			errorResponse.put("timestamp", LocalDateTime.now());
			
			return ResponseEntity.status(500).body(errorResponse);
		}
	}

	@PreAuthorize("hasRole('ADMIN')")
	@DeleteMapping("/{id}")
	public ResponseEntity<Void> delete(@PathVariable Integer id, Principal principal) {
		projectService.deleteProject(id);

		String desc = "Xoá project ID " + id;
		auditLogService.log(principal.getName(), "DELETE", "Project", id, desc);

		return ResponseEntity.noContent().build();
	}

	@PostMapping("/personal")
	// ✅ SỬA: THÊM 'MEMBER' VÀO DANH SÁCH CHO PHÉP
	@PreAuthorize("hasAnyRole('ADMIN', 'EMPLOYEE', 'USER', 'GUEST', 'MEMBER')")
	public ResponseEntity<Project> createPersonalProject(@RequestBody Project project, Principal principal) {
	    try {
	        // Lấy user từ principal
	        User user = userRepository.findByUsername(principal.getName()).orElseThrow();
	        
	        // 🚨 SỬA: CHO PHÉP CẢ TEAM MEMBERS TẠO DỰ ÁN CÁ NHÂN
	        // Chỉ từ chối nếu là BUSINESS account (không phải EMPLOYEE)
	        if ("BUSINESS".equals(user.getAccountType()) && !user.getRole().equals(User.Role.EMPLOYEE)) {
	            System.out.println("DEBUG: Từ chối - User là BUSINESS và không phải EMPLOYEE");
	            return ResponseEntity.status(403).build();
	        }

	        // Set thông tin cho dự án cá nhân
	        project.setCreatedBy(user);
	        project.setProjectType(Project.ProjectType.PERSONAL);
	        
	        System.out.println("DEBUG: Tạo dự án cá nhân bởi: " + user.getUsername());
	        System.out.println("DEBUG - Role: " + user.getRole() + ", AccountType: " + user.getAccountType());
	        
	        Project created = projectService.createProject(project);

	        // Ghi audit log
	        String desc = "Tạo dự án cá nhân ID " + created.getId() + ": " + created.getName();
	        auditLogService.log(principal.getName(), "CREATE", "Project", created.getId(), desc);
	        System.out.println("DEBUG: Dự án cá nhân tạo thành công!");
	        
	        return ResponseEntity.ok(created);
	    } catch (Exception e) {
	        System.out.println("DEBUG: Ngoại lệ: "+e.getMessage());
	        e.printStackTrace();
	        return ResponseEntity.badRequest().build();
	    }
	}
    
    @GetMapping("/personal/{id}")
    @PreAuthorize("hasAnyRole('ADMIN', 'EMPLOYEE', 'USER', 'GUEST', 'MEMBER')") 
    public ResponseEntity<Project> getPersonalProjectById(@PathVariable Integer id, Principal principal) {
        try {
            User user = userRepository.findByUsername(principal.getName()).orElseThrow();
            Project project = projectService.getProjectById(id);

            // Kiểm tra quyền truy cập: chỉ cho phép xem dự án cá nhân của chính user
            if (!project.getProjectType().equals(Project.ProjectType.PERSONAL) || 
                !project.getCreatedBy().getId().equals(user.getId())) {
                return ResponseEntity.status(403).build();
            }

            return ResponseEntity.ok(project);
        } catch (Exception e) {
            return ResponseEntity.notFound().build();
        }
    }
    
    @DeleteMapping("/personal/{id}")
    @PreAuthorize("hasAnyRole('ADMIN', 'EMPLOYEE', 'USER', 'GUEST', 'MEMBER')")
    public ResponseEntity<Void> deletePersonalProject(@PathVariable Integer id, Principal principal) {
        try {
            User user = userRepository.findByUsername(principal.getName()).orElseThrow();
            Project project = projectService.getProjectById(id);
            
            // Kiểm tra quyền: chỉ cho phép xóa dự án cá nhân của chính user
            if (!project.getProjectType().equals(Project.ProjectType.PERSONAL) || 
                !project.getCreatedBy().getId().equals(user.getId())) {
                return ResponseEntity.status(403).build();
            }
            
            projectService.deleteProject(id);
            
            // Ghi audit log
            String desc = "Xóa dự án cá nhân ID " + id + ": " + project.getName();
            auditLogService.log(principal.getName(), "DELETE", "Project", id, desc);
            
            return ResponseEntity.noContent().build();
        } catch (Exception e) {
            return ResponseEntity.notFound().build();
        }
    }
    
    @GetMapping("/personal")
    @PreAuthorize("isAuthenticated()") // ✅ SỬA: Cho phép mọi user đã xác thực
    public ResponseEntity<List<ProjectDTO>> getPersonalProjects(Principal principal) {
        try {
            System.out.println("DEBUG: Đã nhận được yêu cầu lấy dự án cá nhân");
            User user = userRepository.findByUsername(principal.getName()).orElseThrow();
            System.out.println("DEBUG: Người dùng accountType: " + user.getAccountType());            
            
            // 🚀 ĐÃ SỬA: Dùng ProjectDTO thay vì Project
            List<ProjectDTO> personalProjects = projectService.getPersonalProjectsByUser(user);
            
            System.out.println("DEBUG: Số lượng dự án: " + personalProjects.size());
            for (ProjectDTO project : personalProjects) {
                System.out.println("DEBUG: Dự án - ID: " + project.getId() + 
                                 ", Tên: " + project.getName() + 
                                 ", Số task: " + project.getTaskCount());
            }
            return ResponseEntity.ok(personalProjects);
        } catch (Exception e) {
            return ResponseEntity.status(500).build();
        }
    }
    
    @PostMapping("/groups/add")
    @PreAuthorize("hasRole('ADMIN')")
    public ResponseEntity<?> createGroupProject(@RequestBody Project project, Principal principal) {
        try {
            System.out.println("DEBUG: Nhận yêu cầu tạo dự án nhóm");
            System.out.println("DEBUG: Dữ liệu nhận: " + project.toString());
            
            User admin = userRepository.findByUsername(principal.getName()).orElseThrow();
            
            // 🚨 KIỂM TRA GIỚI HẠN DỰ ÁN (7 dự án)
            if (admin.getOrganization() != null) {
                Long currentProjectCount = projectRepository.countByOrganization(admin.getOrganization());
                if (currentProjectCount >= MAX_PROJECTS_PER_GROUP) {
                    return ResponseEntity.badRequest().body(Map.of(
                        "error", "Nhóm đã đạt tối đa " + MAX_PROJECTS_PER_GROUP + " dự án, không thể tạo thêm"
                    ));
                }
            }
            
            project.setCreatedBy(admin);
            project.setProjectType(Project.ProjectType.TEAM); // Quan trọng!
            
            // Set organization từ user admin
            if (admin.getOrganization() != null) {
                project.setOrganization(admin.getOrganization());
                System.out.println("DEBUG: Set organization: " + admin.getOrganization().getName());
            }
            
            Project created = projectService.createProject(project);
            System.out.println("DEBUG: Tạo dự án thành công, ID: " + created.getId());

            String desc = "Tạo mới project nhóm ID " + created.getId() + ": " + created.getName();
            auditLogService.log(principal.getName(), "CREATE", "Project", created.getId(), desc);

            return ResponseEntity.ok(created);
        } catch (Exception e) {
            System.out.println("DEBUG: Lỗi khi tạo dự án nhóm: " + e.getMessage());
            e.printStackTrace();
            return ResponseEntity.badRequest().body(Map.of("error", e.getMessage()));
        }
    }
    
    @PatchMapping("/personal/{id}/complete")
    @PreAuthorize("hasAnyRole('ADMIN', 'EMPLOYEE')")
    public ResponseEntity<Project> markProjectAsCompleted(@PathVariable Integer id, Principal principal) {
        try {
            User user = userRepository.findByUsername(principal.getName()).orElseThrow();
            Project project = projectService.getProjectById(id);
            
            // Kiểm tra quyền
            if (!project.getProjectType().equals(Project.ProjectType.PERSONAL) || 
                !project.getCreatedBy().getId().equals(user.getId())) {
                return ResponseEntity.status(403).build();
            }
            
            Project updated = projectService.markProjectAsCompleted(id);
            return ResponseEntity.ok(updated);
        } catch (Exception e) {
            return ResponseEntity.notFound().build();
        }
    }
    
    @PatchMapping("/personal/{id}/inprogress")
    @PreAuthorize("hasAnyRole('ADMIN', 'EMPLOYEE')")
    public ResponseEntity<Project> markProjectAsInProgress(@PathVariable Integer id, Principal principal) {
        try {
            User user = userRepository.findByUsername(principal.getName()).orElseThrow();
            Project project = projectService.getProjectById(id);
            
            // Kiểm tra quyền
            if (!project.getProjectType().equals(Project.ProjectType.PERSONAL) || 
                !project.getCreatedBy().getId().equals(user.getId())) {
                return ResponseEntity.status(403).build();
            }
            
            Project updated = projectService.markProjectAsInProgress(id);
            return ResponseEntity.ok(updated);
        } catch (Exception e) {
            return ResponseEntity.notFound().build();
        }
    }
    
    @PutMapping("/personal/{id}")
    @PreAuthorize("hasAnyRole('ADMIN', 'EMPLOYEE', 'MEMBER')")
    public ResponseEntity<Project> updatePersonalProject(@PathVariable Integer id, @RequestBody Project projectData, Principal principal) {
        try {
            User user = userRepository.findByUsername(principal.getName()).orElseThrow();
            Project existingProject = projectService.getProjectById(id);

            // Kiểm tra quyền: chỉ cho phép sửa dự án cá nhân của chính user
            if (!existingProject.getProjectType().equals(Project.ProjectType.PERSONAL) || 
                !existingProject.getCreatedBy().getId().equals(user.getId())) {
                return ResponseEntity.status(403).build();
            }

            // Cập nhật thông tin
            existingProject.setName(projectData.getName());
            existingProject.setDescription(projectData.getDescription());
            
            // Cập nhật deadline nếu có
            if (projectData.getDeadlineDate() != null) {
                existingProject.setDeadlineDate(projectData.getDeadlineDate());
            } else {
                existingProject.setDeadline(null);
            }

            Project updated = projectService.updateProject(id, existingProject);

            // Ghi audit log
            String desc = "Cập nhật dự án cá nhân ID " + id + ": " + updated.getName();
            auditLogService.log(principal.getName(), "UPDATE", "Project", id, desc);

            return ResponseEntity.ok(updated);
        } catch (Exception e) {
            return ResponseEntity.notFound().build();
        }
    }
    
    @GetMapping("/groups")
    @PreAuthorize("hasAnyRole('ADMIN', 'EMPLOYEE', 'MEMBER')")
    public ResponseEntity<List<ProjectDTO>> getGroupProjectsForCurrentUser(Principal principal) {
        try {
            User user = userRepository.findByUsername(principal.getName()).orElseThrow();
            
            // 🚨 QUAN TRỌNG: Kiểm tra user có organization không
            if (user.getOrganization() == null) {
                System.out.println("DEBUG: User không có organization, trả về danh sách rỗng");
                return ResponseEntity.ok(new ArrayList<>());
            }
            
            List<ProjectDTO> groupProjects;
            
            if (user.getRole().equals(User.Role.ADMIN)) {
                // ✅ SỬA: ADMIN chỉ xem dự án TRONG TỔ CHỨC CỦA MÌNH
                groupProjects = projectService.getGroupProjectsByOrganization(user.getOrganization());
                System.out.println("DEBUG: ADMIN lấy dự án của organization: " + user.getOrganization().getName());
            } else {
                // EMPLOYEE/MEMBER: lấy dự án TEAM mà họ là thành viên
                groupProjects = projectService.getGroupProjectsByUser(user);
                System.out.println("DEBUG: MEMBER lấy dự án theo membership");
            }
            
            System.out.println("DEBUG: Số lượng dự án trả về: " + groupProjects.size());
            return ResponseEntity.ok(groupProjects);
        } catch (Exception e) {
            e.printStackTrace();
            return ResponseEntity.status(HttpStatus.BAD_REQUEST).build();
        }
    }
    
    @GetMapping("/business")
    @PreAuthorize("hasAnyRole('ADMIN', 'EMPLOYEE')")
    public ResponseEntity<List<ProjectDTO>> getBusinessProjectsForCurrentUser(Principal principal) {
        try {
            User user = userRepository.findByUsername(principal.getName()).orElseThrow();
            
            if (user.getOrganization() == null) {
                return ResponseEntity.ok(new ArrayList<>());
            }
            
            List<ProjectDTO> businessProjects;
            
            if (user.getRole().equals(User.Role.ADMIN)) {
                // 🎯 DÙNG METHOD MỚI
                businessProjects = projectService.getBusinessProjectsByOrganization(user.getOrganization());
            } else {
                // 🎯 DÙNG METHOD MỚI
                businessProjects = projectService.getBusinessProjectsByUser(user);
            }
            
            return ResponseEntity.ok(businessProjects);
        } catch (Exception e) {
            return ResponseEntity.status(HttpStatus.BAD_REQUEST).build();
        }
    }
    
    @PostMapping("/business/add")
    @PreAuthorize("hasRole('ADMIN')")
    public ResponseEntity<?> createBusinessProject(@RequestBody Project project, Principal principal) {
        try {
            System.out.println("DEBUG: Nhận yêu cầu tạo dự án doanh nghiệp");
            
            User admin = userRepository.findByUsername(principal.getName()).orElseThrow();
            
            // 🚨 KIỂM TRA GIỚI HẠN DỰ ÁN (7 dự án)
            if (admin.getOrganization() != null) {
                Long currentProjectCount = projectRepository.countByOrganization(admin.getOrganization());
                if (currentProjectCount >= MAX_PROJECTS_PER_GROUP) {
                    return ResponseEntity.badRequest().body(Map.of(
                        "error", "Nhóm đã đạt tối đa " + MAX_PROJECTS_PER_GROUP + " dự án, không thể tạo thêm"
                    ));
                }
            }
            
            project.setCreatedBy(admin);
            project.setProjectType(Project.ProjectType.ENTERPRISE); // Quan trọng!
            
            // Set organization từ user admin
            if (admin.getOrganization() != null) {
                project.setOrganization(admin.getOrganization());
                System.out.println("DEBUG: Set organization: " + admin.getOrganization().getName());
            }
            
            Project created = projectService.createProject(project);
            System.out.println("DEBUG: Tạo dự án doanh nghiệp thành công, ID: " + created.getId());

            String desc = "Tạo mới project doanh nghiệp ID " + created.getId() + ": " + created.getName();
            auditLogService.log(principal.getName(), "CREATE", "Project", created.getId(), desc);

            return ResponseEntity.ok(created);
        } catch (Exception e) {
            System.out.println("DEBUG: Lỗi khi tạo dự án doanh nghiệp: " + e.getMessage());
            e.printStackTrace();
            return ResponseEntity.badRequest().body(Map.of("error", e.getMessage()));
        }
    }
    
    // 🔴 THÊM ENDPOINT RIÊNG CHO UPDATE BUSINESS PROJECT
    @PutMapping("/business/{id}")
    @PreAuthorize("hasRole('ADMIN')")
    public ResponseEntity<?> updateBusinessProject(@PathVariable Integer id, @RequestBody Map<String, Object> updates, Principal principal) {
        try {
            System.out.println("🔧 [DEBUG] Cập nhật business project ID: " + id);
            System.out.println("🔧 [DEBUG] Dữ liệu nhận: " + updates);
            
            User admin = userRepository.findByUsername(principal.getName()).orElseThrow();
            Project existingProject = projectService.getProjectById(id);
            
            if (existingProject == null) {
                return ResponseEntity.status(404).body(Map.of("error", "Không tìm thấy dự án"));
            }
            
            // Kiểm tra quyền: project phải thuộc organization của admin
            if (admin.getOrganization() == null) {
                System.out.println("❌ [DEBUG] Admin không có organization");
                return ResponseEntity.status(403).body(Map.of("error", "Bạn không thuộc tổ chức nào"));
            }
            
            if (existingProject.getOrganization() == null) {
                System.out.println("❌ [DEBUG] Project không có organization");
                return ResponseEntity.status(403).body(Map.of("error", "Dự án không thuộc tổ chức nào"));
            }
            
            if (!existingProject.getOrganization().getId().equals(admin.getOrganization().getId())) {
                System.out.println("❌ [DEBUG] Organization không khớp");
                System.out.println("Project Org ID: " + existingProject.getOrganization().getId());
                System.out.println("Admin Org ID: " + admin.getOrganization().getId());
                return ResponseEntity.status(403).body(Map.of("error", "Không có quyền cập nhật dự án này"));
            }
            
            // Kiểm tra project type phải là ENTERPRISE
            if (!existingProject.getProjectType().equals(Project.ProjectType.ENTERPRISE)) {
                System.out.println("❌ [DEBUG] Project type không phải ENTERPRISE: " + existingProject.getProjectType());
                return ResponseEntity.badRequest().body(Map.of("error", "Chỉ được cập nhật dự án doanh nghiệp"));
            }
            
            // Cập nhật name
            if (updates.containsKey("name")) {
                String name = (String) updates.get("name");
                if (name != null && !name.trim().isEmpty()) {
                    existingProject.setName(name.trim());
                } else {
                    return ResponseEntity.badRequest().body(Map.of("error", "Tên dự án không được để trống"));
                }
            }
            
            // Cập nhật description
            if (updates.containsKey("description")) {
                String description = (String) updates.get("description");
                existingProject.setDescription(description != null ? description.trim() : null);
            }
            
            // Xử lý deadlineDate
            Object deadlineObj = updates.get("deadlineDate");
            if (deadlineObj != null) {
                if (deadlineObj instanceof String) {
                    String dateStr = (String) deadlineObj;
                    if (!dateStr.trim().isEmpty()) {
                        try {
                            LocalDate date = LocalDate.parse(dateStr);
                            existingProject.setDeadlineDate(date);
                        } catch (Exception e) {
                            return ResponseEntity.badRequest().body(Map.of("error", "Định dạng ngày không hợp lệ. Dùng YYYY-MM-DD"));
                        }
                    } else {
                        existingProject.setDeadline(null);
                    }
                } else if (deadlineObj == null || deadlineObj.equals("null")) {
                    existingProject.setDeadline(null);
                }
            }
            
            // Lưu thay đổi
            Project updated = projectRepository.save(existingProject);
            
            // Ghi log
            String desc = "Cập nhật business project ID " + id + ": " + updated.getName();
            auditLogService.log(principal.getName(), "UPDATE", "Project", id, desc);
            
            return ResponseEntity.ok(updated);
        } catch (Exception e) {
            System.out.println("❌ [DEBUG] Lỗi update business project: " + e.getMessage());
            e.printStackTrace();
            return ResponseEntity.status(500).body(Map.of("error", e.getMessage()));
        }
    }
}