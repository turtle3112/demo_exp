package com.vti.controller;

import com.vti.model.ProjectMember;
import com.vti.model.User;
import com.vti.repository.UserRepository;
import com.vti.service.AuditLogService;
import com.vti.service.ProjectMemberService;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.security.Principal;
import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/project-members")
public class ProjectMemberController {

    private final ProjectMemberService projectMemberService;
    private final AuditLogService auditLogService;
    private final UserRepository userRepository;

    public ProjectMemberController(
            ProjectMemberService projectMemberService,
            AuditLogService auditLogService,
            UserRepository userRepository
    ) {
        this.projectMemberService = projectMemberService;
        this.auditLogService = auditLogService;
        this.userRepository = userRepository;
    }

    // ==================== ADD MEMBERS ====================
    @PreAuthorize("hasRole('ADMIN')")
    @PostMapping("/add")
    public ResponseEntity<?> addMembers(@RequestBody Map<String, Object> body, Principal principal) {
        Integer projectId = (Integer) body.get("projectId");

        @SuppressWarnings("unchecked")
        List<Integer> userIds = (List<Integer>) body.get("userIds");

        if (projectId == null || userIds == null || userIds.isEmpty()) {
            return ResponseEntity.badRequest().body("projectId và userIds không được để trống");
        }

        projectMemberService.addMembers(projectId, userIds);

        String desc = "Thêm " + userIds.size() + " users vào project ID " + projectId;
        auditLogService.log(principal.getName(), "CREATE", "ProjectMember", null, desc);

        return ResponseEntity.ok().build();
    }

    // ==================== UPDATE MEMBERS ====================
    @PreAuthorize("hasRole('ADMIN')")
    @PutMapping("/{projectId}")
    public ResponseEntity<?> updateProjectMembers(
            @PathVariable Integer projectId,
            @RequestBody Map<String, List<Integer>> body,
            Principal principal
    ) {
        List<Integer> userIds = body.get("userIds");

        if (userIds == null) {
            return ResponseEntity.badRequest().body("Danh sách userIds không được để trống");
        }

        projectMemberService.updateMembers(projectId, userIds);

        String desc = "Cập nhật thành viên cho project ID " + projectId;
        auditLogService.log(principal.getName(), "UPDATE", "ProjectMember", null, desc);

        return ResponseEntity.ok().build();
    }

    // ==================== REMOVE ONE MEMBER ====================
    @PreAuthorize("hasRole('ADMIN')")
    @DeleteMapping("/remove")
    public ResponseEntity<?> removeMember(@RequestBody Map<String, Object> body, Principal principal) {
        Integer projectId = (Integer) body.get("projectId");
        Integer userId = (Integer) body.get("userId");

        if (projectId == null || userId == null) {
            return ResponseEntity.badRequest().body("projectId và userId không được để trống");
        }

        projectMemberService.removeMember(projectId, userId);

        String desc = "Xoá user ID " + userId + " khỏi project ID " + projectId;
        auditLogService.log(principal.getName(), "DELETE", "ProjectMember", null, desc);

        return ResponseEntity.noContent().build();
    }

    // ==================== GET MEMBERS ====================
    @PreAuthorize("hasAnyRole('ADMIN', 'EMPLOYEE')")
    @GetMapping("/project/{projectId}")
    public ResponseEntity<List<ProjectMember>> getMembersByProject(
            @PathVariable Integer projectId,
            Principal principal
    ) {
        User user = userRepository.findByUsername(principal.getName()).orElseThrow();

        if (!user.getRole().equals(User.Role.ADMIN)) {
            boolean isInProject = projectMemberService.isUserInProject(projectId, user.getId());
            if (!isInProject) {
                return ResponseEntity.status(403).build();
            }
        }

        return ResponseEntity.ok(projectMemberService.getMembersByProject(projectId));
    }

    // ==================== 🔥 DELETE ALL MEMBERS BY PROJECT (FOR DELETE PROJECT) ====================
    @PreAuthorize("hasRole('ADMIN')")
    @DeleteMapping("/project/{projectId}/all")
    public ResponseEntity<?> deleteAllMembersByProject(
            @PathVariable Integer projectId,
            Principal principal
    ) {
        projectMemberService.deleteByProjectId(projectId);

        String desc = "Xoá toàn bộ thành viên của project ID " + projectId;
        auditLogService.log(principal.getName(), "DELETE", "ProjectMember", null, desc);

        return ResponseEntity.noContent().build();
    }
}
