package com.vti.service;

import com.vti.model.Organization;
import com.vti.model.Project;
import com.vti.model.ProjectMember;
import com.vti.model.Task;
import com.vti.model.User;
import com.vti.repository.ProjectMemberRepository;
import com.vti.repository.ProjectRepository;
import com.vti.repository.TaskRepository;

import dto.ProjectDTO;

import org.springframework.stereotype.Service;

import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.stream.Collectors;

@Service
public class ProjectService {
	private final ProjectRepository projectRepository;
	private final ProjectMemberRepository projectMemberRepository;
	private final TaskService taskService;
	private final TaskRepository taskRepository;

	public ProjectService(ProjectRepository projectRepository, 
			ProjectMemberRepository projectMemberRepository, 
			TaskService taskService, TaskRepository taskRepository) {
		super();
		this.projectRepository = projectRepository;
		this.projectMemberRepository = projectMemberRepository;
		this.taskService = taskService;
		this.taskRepository = taskRepository;
	}

	public Project createProject(Project project) {
	    System.out.println("DEBUG ProjectService: Đang tạo project:");
	    System.out.println("DEBUG - Tên: " + project.getName());
	    System.out.println("DEBUG - Mô tả: " + project.getDescription());
	    System.out.println("DEBUG - Loại: " + project.getProjectType());
	    System.out.println("DEBUG - Deadline: " + project.getDeadline());
	    System.out.println("DEBUG - CreatedBy: " + (project.getCreatedBy() != null ? project.getCreatedBy().getUsername() : "null"));
	    System.out.println("DEBUG - Organization: " + (project.getOrganization() != null ? project.getOrganization().getName() : "null"));
	    
	    Project saved = projectRepository.save(project);
	    System.out.println("DEBUG ProjectService: Đã tạo project ID: " + saved.getId());
	    return saved;
	}

	public Project updateProject(Integer id, Project updated) {
		Project project = projectRepository.findById(id).orElseThrow();
		project.setName(updated.getName());
		project.setDescription(updated.getDescription());
	    project.setDeadline(updated.getDeadline());
		return projectRepository.save(project);
	}

	public void deleteProject(Integer projectId) {
		List<Task> tasks = taskRepository.findByProjectId(projectId);
		for (Task task : tasks) {
			Integer taskId = task.getId();
			taskService.deleteTask(taskId);
		}
		projectMemberRepository.deleteByProjectId(projectId);
		projectRepository.deleteById(projectId);
	}

	public List<Project> getAllProjects() {
		return projectRepository.findAll();
	}

	public Project getProjectById(Integer id) {
		return projectRepository.findById(id).orElseThrow();
	}
	
    public void updateProjectStatus(Integer projectId) {
        Project project = projectRepository.findById(projectId).orElseThrow();
        
        // Nếu đã hoàn thành thì giữ nguyên
        if (project.getStatus() == Project.ProjectStatus.COMPLETED) {
            return;
        }
        
        LocalDateTime now = LocalDateTime.now();
        
        // Kiểm tra nếu có deadline và đã qua hạn
        if (project.getDeadline() != null && project.getDeadline().isBefore(now)) {
            project.setStatus(Project.ProjectStatus.EXPIRED);
        } else {
            project.setStatus(Project.ProjectStatus.IN_PROGRESS);
        }
        
        projectRepository.save(project);
    }
    
    // THÊM PHƯƠNG THỨC NÀY
    public List<ProjectDTO> getPersonalProjectsByUser(User user) {
        List<Project> projects = projectRepository.findByCreatedByAndProjectType(user, Project.ProjectType.PERSONAL);
        List<ProjectDTO> projectDTOs = new ArrayList<>();
        
        for (Project project : projects) {
            Long taskCount = taskRepository.countByProjectId(project.getId());
            
            // 🎯 TỰ ĐỘNG TÍNH TOÁN TRẠNG THÁI DỰA TRÊN CÁC TASK
            Project.ProjectStatus status = this.calculateProjectStatus(project.getId());
            project.setStatus(status); // Cập nhật status cho project
            
            ProjectDTO dto = new ProjectDTO(project, taskCount);
            projectDTOs.add(dto);
        }
        
        return projectDTOs;
    }
    
    // 🚨 QUAN TRỌNG: SỬA LẠI PHƯƠNG THỨC NÀY
    public List<ProjectDTO> getGroupProjectsByUser(User user) {
        try {
            System.out.println("=== DEBUG: Bắt đầu getGroupProjectsByUser ===");
            System.out.println("User ID: " + user.getId() + ", Username: " + user.getUsername());
            System.out.println("User Organization: " + (user.getOrganization() != null ? user.getOrganization().getName() : "null"));
            
            // 🚨 KIỂM TRA: User phải có organization
            if (user.getOrganization() == null) {
                System.out.println("DEBUG: User không có organization, trả về danh sách rỗng");
                return new ArrayList<>();
            }
            
            // 🚨 LẤY DANH SÁCH PROJECT MEMBER CỦA USER
            List<ProjectMember> projectMembers = projectMemberRepository.findByUserId(user.getId());
            System.out.println("DEBUG: Số lượng projectMembers: " + projectMembers.size());
            
            if (projectMembers.isEmpty()) {
                System.out.println("DEBUG: User không là thành viên của dự án nào");
                return new ArrayList<>();
            }
            
            // Lấy danh sách project IDs mà user là thành viên
            List<Integer> projectIds = projectMembers.stream()
                    .map(ProjectMember::getProjectId)
                    .distinct()
                    .collect(Collectors.toList());
            System.out.println("DEBUG: Project IDs từ projectMembers: " + projectIds);
            
            // 🚨 LẤY DỰ ÁN VỚI 3 ĐIỀU KIỆN:
            // 1. ID trong danh sách projectIds (user là thành viên)
            // 2. Loại TEAM
            // 3. Thuộc tổ chức của user
            List<Project> projects = projectRepository.findByIdInAndProjectTypeAndOrganization(
                    projectIds, 
                    Project.ProjectType.TEAM, 
                    user.getOrganization()
            );
            
            System.out.println("DEBUG: Số dự án TEAM tìm thấy: " + projects.size());
            projects.forEach(p -> System.out.println(" - " + p.getName() + " (ID: " + p.getId() + ")"));
            
            List<ProjectDTO> projectDTOs = new ArrayList<>();
            
            for (Project project : projects) {
                Long taskCount = taskRepository.countByProjectId(project.getId());
                Project.ProjectStatus status = this.calculateProjectStatus(project.getId());
                project.setStatus(status);
                
                ProjectDTO dto = new ProjectDTO(project, taskCount);
                projectDTOs.add(dto);
            }
            
            System.out.println("=== DEBUG: Kết thúc getGroupProjectsByUser ===");
            return projectDTOs;
        } catch (Exception e) {
            System.out.println("Lỗi trong getGroupProjectsByUser: " + e.getMessage());
            e.printStackTrace();
            return new ArrayList<>();
        }
    }
    
    public Project.ProjectStatus calculateProjectStatus(Integer projectId) {
        Project project = projectRepository.findById(projectId).orElseThrow();
        
        // Đếm tổng số task và số task đã hoàn thành
        Long totalTasks = taskRepository.countByProjectId(projectId);
        Long completedTasks = taskRepository.countByProjectIdAndStatus(projectId, Task.Status.DONE);
        
        LocalDateTime now = LocalDateTime.now();
        
        System.out.println("🎯 DEBUG calculateProjectStatus:");
        System.out.println("  - Project: " + project.getName());
        System.out.println("  - Total Tasks: " + totalTasks);
        System.out.println("  - Completed Tasks: " + completedTasks);
        System.out.println("  - Deadline: " + project.getDeadline());
        System.out.println("  - Now: " + now);
        
        // Nếu không có task nào, coi như chưa hoàn thành
        if (totalTasks == 0) {
            System.out.println("  - No tasks, checking deadline...");
            if (project.getDeadline() != null && project.getDeadline().isBefore(now)) {
                System.out.println("  - Result: EXPIRED (no tasks, deadline passed)");
                return Project.ProjectStatus.EXPIRED;
            }
            System.out.println("  - Result: IN_PROGRESS (no tasks)");
            return Project.ProjectStatus.IN_PROGRESS;
        }
        
        // Nếu tất cả task đã hoàn thành
        if (completedTasks.equals(totalTasks)) {
            System.out.println("  - Result: COMPLETED (all tasks done)");
            return Project.ProjectStatus.COMPLETED;
        }
        
        // Nếu có task chưa hoàn thành và đã qua hạn
        if (project.getDeadline() != null && project.getDeadline().isBefore(now)) {
            System.out.println("  - Result: EXPIRED (deadline passed with incomplete tasks)");
            return Project.ProjectStatus.EXPIRED;
        }
        
        System.out.println("  - Result: IN_PROGRESS (default)");
        return Project.ProjectStatus.IN_PROGRESS;
    }
    
    // THÊM PHƯƠNG THỨC ĐÁNH DẤU HOÀN THÀNH
    public Project markProjectAsCompleted(Integer projectId) {
        Project project = projectRepository.findById(projectId).orElseThrow();
        project.setStatus(Project.ProjectStatus.COMPLETED);
        return projectRepository.save(project);
    }
    
    // THÊM PHƯƠNG THỨC ĐÁNH DẤU CHƯA HOÀN THÀNH
    public Project markProjectAsInProgress(Integer projectId) {
        Project project = projectRepository.findById(projectId).orElseThrow();
        project.setStatus(Project.ProjectStatus.IN_PROGRESS);
        return projectRepository.save(project);
    }
    
    public Project getPersonalProjectDetails(Integer projectId, User user) {
        Project project = projectRepository.findById(projectId)
            .orElseThrow(() -> new RuntimeException("Project not found"));
        
        // Kiểm tra xem project có phải là PERSONAL và thuộc về user không
        if (!project.getProjectType().equals(Project.ProjectType.PERSONAL) || 
            !project.getCreatedBy().getId().equals(user.getId())) {
            throw new RuntimeException("Access denied");
        }
        
        return project;
    }

    public List<Project> getProjectsByType(Project.ProjectType projectType) {
        return projectRepository.findByProjectType(projectType);
    }
    
    public List<ProjectDTO> getAllGroupProjectsAsDTO() {
        List<Project> projects = projectRepository.findByProjectType(Project.ProjectType.TEAM);
        List<ProjectDTO> projectDTOs = new ArrayList<>();
        
        for (Project project : projects) {
            Long taskCount = taskRepository.countByProjectId(project.getId());
            
            // 🎯 TỰ ĐỘNG TÍNH TOÁN TRẠNG THÁI
            Project.ProjectStatus status = this.calculateProjectStatus(project.getId());
            project.setStatus(status); // Cập nhật status cho project
            
            ProjectDTO dto = new ProjectDTO(project, taskCount);
            projectDTOs.add(dto);
        }
        
        return projectDTOs;
    }
    
    public List<ProjectDTO> getGroupProjectsByOrganization(Organization organization) {
        List<Project> projects = projectRepository.findByProjectTypeAndOrganization(
            Project.ProjectType.TEAM, organization);
        
        List<ProjectDTO> projectDTOs = new ArrayList<>();
        
        for (Project project : projects) {
            Long taskCount = taskRepository.countByProjectId(project.getId());
            Project.ProjectStatus status = this.calculateProjectStatus(project.getId());
            project.setStatus(status);
            
            ProjectDTO dto = new ProjectDTO(project, taskCount);
            projectDTOs.add(dto);
        }
        
        return projectDTOs;
    }
    
    public List<ProjectDTO> getProjectsByTypeAndOrganization(Project.ProjectType projectType, Organization organization) {
        List<Project> projects = projectRepository.findByProjectTypeAndOrganization(projectType, organization);
        
        List<ProjectDTO> projectDTOs = new ArrayList<>();
        for (Project project : projects) {
            Long taskCount = taskRepository.countByProjectId(project.getId());
            Project.ProjectStatus status = this.calculateProjectStatus(project.getId());
            project.setStatus(status);
            
            ProjectDTO dto = new ProjectDTO(project, taskCount);
            projectDTOs.add(dto);
        }
        
        return projectDTOs;
    }
    
    // PHƯƠNG THỨC CŨ - GIỮ LẠI NHƯNG KHÔNG SỬ DỤNG
    public List<Project> getProjectsByUserAndProjectType(User user, Project.ProjectType projectType) {
        try {
            System.out.println("=== DEBUG: Bắt đầu getProjectsByUserAndProjectType ===");
            System.out.println("User ID: " + user.getId() + ", Username: " + user.getUsername());
            System.out.println("Project Type: " + projectType);
            
            // Lấy tất cả project member records của user
            List<ProjectMember> userProjects = projectMemberRepository.findByUserId(user.getId());
            System.out.println("Số ProjectMember records: " + userProjects.size());
            
            if (userProjects.isEmpty()) {
                System.out.println("User không có dự án nào, trả về danh sách rỗng");
                return new ArrayList<>();
            }
            
            // Lấy danh sách project IDs
            List<Integer> projectIds = userProjects.stream()
                    .map(ProjectMember::getProjectId)
                    .distinct()
                    .collect(Collectors.toList());
            System.out.println("Project IDs: " + projectIds);
            
            // Lấy projects theo IDs và type
            List<Project> projects = projectRepository.findByIdInAndProjectType(projectIds, projectType);
            System.out.println("Số dự án " + projectType + " tìm thấy: " + projects.size());
            projects.forEach(p -> System.out.println(" - " + p.getName() + " (ID: " + p.getId() + ")"));
            
            System.out.println("=== DEBUG: Kết thúc getProjectsByUserAndProjectType ===");
            return projects;
        } catch (Exception e) {
            System.out.println("Lỗi trong getProjectsByUserAndProjectType: " + e.getMessage());
            e.printStackTrace();
            return new ArrayList<>();
        }
    }

    // 🚨 QUAN TRỌNG: SỬA LẠI PHƯƠNG THỨC NÀY
    public List<ProjectDTO> getBusinessProjectsByUser(User user) {
        try {
            System.out.println("=== DEBUG: Bắt đầu getBusinessProjectsByUser ===");
            System.out.println("User ID: " + user.getId() + ", Username: " + user.getUsername());
            
            // 🚨 KIỂM TRA: User phải có organization
            if (user.getOrganization() == null) {
                System.out.println("DEBUG: User không có organization, trả về danh sách rỗng");
                return new ArrayList<>();
            }
            
            // 🚨 LẤY DANH SÁCH PROJECT MEMBER CỦA USER
            List<ProjectMember> projectMembers = projectMemberRepository.findByUserId(user.getId());
            System.out.println("DEBUG: Số lượng projectMembers: " + projectMembers.size());
            
            if (projectMembers.isEmpty()) {
                System.out.println("DEBUG: User không là thành viên của dự án nào");
                return new ArrayList<>();
            }
            
            List<Integer> projectIds = projectMembers.stream()
                    .map(ProjectMember::getProjectId)
                    .distinct()
                    .collect(Collectors.toList());
            
            // 🚨 LẤY DỰ ÁN VỚI 3 ĐIỀU KIỆN:
            // 1. ID trong danh sách projectIds (user là thành viên)
            // 2. Loại ENTERPRISE
            // 3. Thuộc tổ chức của user
            List<Project> projects = projectRepository.findByIdInAndProjectTypeAndOrganization(
                    projectIds, 
                    Project.ProjectType.ENTERPRISE, 
                    user.getOrganization()
            );
            
            System.out.println("DEBUG: Số dự án ENTERPRISE tìm thấy: " + projects.size());
            
            List<ProjectDTO> projectDTOs = new ArrayList<>();
            
            for (Project project : projects) {
                Long taskCount = taskRepository.countByProjectId(project.getId());
                Project.ProjectStatus status = this.calculateProjectStatus(project.getId());
                project.setStatus(status);
                
                ProjectDTO dto = new ProjectDTO(project, taskCount);
                projectDTOs.add(dto);
            }
            
            return projectDTOs;
        } catch (Exception e) {
            System.out.println("Lỗi trong getBusinessProjectsByUser: " + e.getMessage());
            e.printStackTrace();
            return new ArrayList<>();
        }
    }

    public List<ProjectDTO> getBusinessProjectsByOrganization(Organization organization) {
        List<Project> projects = projectRepository.findByProjectTypeAndOrganization(
            Project.ProjectType.ENTERPRISE, organization);
        
        List<ProjectDTO> projectDTOs = new ArrayList<>();
        
        for (Project project : projects) {
            Long taskCount = taskRepository.countByProjectId(project.getId());
            Project.ProjectStatus status = this.calculateProjectStatus(project.getId());
            project.setStatus(status);
            
            ProjectDTO dto = new ProjectDTO(project, taskCount);
            projectDTOs.add(dto);
        }
        
        return projectDTOs;
    }
}