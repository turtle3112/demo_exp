package com.vti.controller;

import com.vti.model.Organization;
import com.vti.model.User;
import com.vti.model.Project;
import com.vti.model.Invitation;
import com.vti.model.InvitationStatus;
import com.vti.repository.UserRepository;
import com.vti.repository.ProjectRepository;
import com.vti.repository.InvitationRepository;
import com.vti.service.ProjectMemberService;

import jakarta.transaction.Transactional;

import com.vti.service.AuditLogService;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.security.Principal;
import java.time.LocalDateTime;
import java.time.LocalDate;
import java.util.*;
import java.util.stream.Collectors;

@RestController
@RequestMapping("/groups")
public class GroupController {

    private final UserRepository userRepository;
    private final ProjectRepository projectRepository;
    private final InvitationRepository invitationRepository;
    private final ProjectMemberService projectMemberService;
    private final AuditLogService auditLogService;

    // 🚨 THÊM HẰNG SỐ GIỚI HẠN
    private static final int MAX_MEMBERS_PER_GROUP = 10;

    public GroupController(UserRepository userRepository, ProjectRepository projectRepository,
                           InvitationRepository invitationRepository, ProjectMemberService projectMemberService,
                           AuditLogService auditLogService) {
        this.userRepository = userRepository;
        this.projectRepository = projectRepository;
        this.invitationRepository = invitationRepository;
        this.projectMemberService = projectMemberService;
        this.auditLogService = auditLogService;
    }

    // ==================== LẤY DANH SÁCH THÀNH VIÊN ====================
    @GetMapping("/members")
    @PreAuthorize("hasAnyRole('ADMIN','MEMBER','EMPLOYEE')")
    public ResponseEntity<?> getGroupMembers(Principal principal) {
        try {
            User currentUser = userRepository.findByUsername(principal.getName())
                    .orElseThrow(() -> new RuntimeException("User không tồn tại"));

            Organization org = currentUser.getOrganization();
            if (org == null) return ResponseEntity.ok(List.of());

            List<User> members = userRepository.findByOrganization(org);
            
            // 🚨 THÊM THÔNG TIN GIỚI HẠN VÀO RESPONSE
            Map<String, Object> response = new HashMap<>();
            response.put("members", members);
            response.put("totalMembers", members.size());
            response.put("maxMembers", MAX_MEMBERS_PER_GROUP);
            response.put("remainingSlots", MAX_MEMBERS_PER_GROUP - members.size());
            
            return ResponseEntity.ok(response);

        } catch (Exception e) {
            e.printStackTrace();
            return ResponseEntity.status(500).body(Map.of("error", "Lỗi server"));
        }
    }

    // ==================== XOÁ THÀNH VIÊN ====================
    @DeleteMapping("/members/{id}")
    @PreAuthorize("hasRole('ADMIN')")
    public ResponseEntity<?> removeMemberFromGroup(@PathVariable Integer id, Principal principal) {
        try {
            User currentUser = userRepository.findByUsername(principal.getName())
                    .orElseThrow(() -> new RuntimeException("User không tồn tại"));

            User targetUser = userRepository.findById(id)
                    .orElseThrow(() -> new RuntimeException("User cần xoá không tồn tại"));

            if (currentUser.getId().equals(targetUser.getId())) {
                return ResponseEntity.badRequest().body(Map.of("error", "Bạn không thể tự xoá chính mình"));
            }

            if (targetUser.getOrganization() == null ||
                !Objects.equals(targetUser.getOrganization().getId(), currentUser.getOrganization().getId())) {
                return ResponseEntity.status(403).body(Map.of("error", "User không thuộc tổ chức của bạn"));
            }

            targetUser.setOrganization(null);
            userRepository.save(targetUser);

            return ResponseEntity.ok(Map.of("success", true, "message", "Đã xoá thành viên khỏi nhóm"));
        } catch (Exception e) {
            e.printStackTrace();
            return ResponseEntity.status(500).body(Map.of("error", "Lỗi server"));
        }
    }

    // ==================== THÀNH VIÊN TỰ RỜI NHÓM ====================
    @DeleteMapping("/members/self")
    @PreAuthorize("hasAnyRole('ADMIN','MEMBER')")
    public ResponseEntity<?> leaveGroup(Principal principal) {
        try {
            if (principal == null) return ResponseEntity.status(401).body(Map.of("error", "Unauthorized"));

            User currentUser = userRepository.findByUsername(principal.getName())
                    .orElseThrow(() -> new RuntimeException("User không tồn tại"));

            if (currentUser.getOrganization() == null) {
                return ResponseEntity.ok(Map.of("message", "Bạn không thuộc nhóm nào"));
            }

            String orgName = currentUser.getOrganization().getName();
            currentUser.setOrganization(null);
            userRepository.save(currentUser);

            return ResponseEntity.ok(Map.of("success", true, "message", "Bạn đã rời khỏi nhóm '" + orgName + "'"));
        } catch (Exception e) {
            e.printStackTrace();
            return ResponseEntity.status(500).body(Map.of("error", "Lỗi server"));
        }
    }

    // ==================== TẠO LỜI MỜI ====================
    @PostMapping("/invitations")
    @PreAuthorize("hasRole('ADMIN')")
    public ResponseEntity<?> createInvitation(@RequestBody Map<String, Object> request, Principal principal) {
        try {
            String email = (String) request.get("email");
            Object projectIdObj = request.get("projectId");
            Integer projectId = null;

            if (projectIdObj instanceof Integer) {
                projectId = (Integer) projectIdObj;
            } else if (projectIdObj instanceof String) {
                try {
                    projectId = Integer.parseInt((String) projectIdObj);
                } catch (Exception ignore) {}
            }

            if (email == null || email.isBlank()) {
                return ResponseEntity.badRequest().body(Map.of("error", "Email không được trống"));
            }

            User currentUser = userRepository.findByUsername(principal.getName())
                    .orElseThrow(() -> new RuntimeException("User không tồn tại"));

            Organization org = currentUser.getOrganization();
            if (org == null) return ResponseEntity.badRequest().body(Map.of("error", "Bạn chưa thuộc nhóm nào"));

            // 🚨 KIỂM TRA GIỚI HẠN THÀNH VIÊN (10 người)
            Long currentMemberCount = userRepository.countByOrganization(org);
            if (currentMemberCount >= MAX_MEMBERS_PER_GROUP) {
                return ResponseEntity.badRequest().body(Map.of(
                    "error", 
                    "Nhóm đã đạt tối đa " + MAX_MEMBERS_PER_GROUP + " thành viên, không thể mời thêm"
                ));
            }

            User invitedUser = userRepository.findByEmail(email)
                    .orElse(null);

            if (invitedUser == null) return ResponseEntity.badRequest().body(Map.of("error", "Không tìm thấy user"));

            if (invitedUser.getOrganization() != null &&
                invitedUser.getOrganization().getId().equals(org.getId())) {
                return ResponseEntity.badRequest().body(Map.of("error", "User đã trong nhóm"));
            }

            Optional<Invitation> existing = invitationRepository
                    .findByInvitedUserAndOrganizationAndStatus(invitedUser, org, InvitationStatus.PENDING);

            if (existing.isPresent()) {
                return ResponseEntity.badRequest().body(Map.of("error", "Đã có lời mời đang chờ"));
            }

            Project project = null;
            if (projectId != null) {
                project = projectRepository.findById(projectId)
                        .orElseThrow(() -> new RuntimeException("Project không tồn tại"));

                if (!Objects.equals(project.getOrganization().getId(), org.getId())) {
                    return ResponseEntity.badRequest().body(Map.of("error", "Project không thuộc nhóm"));
                }
            }

            Invitation invitation = new Invitation();
            invitation.setInvitedUser(invitedUser);
            invitation.setOrganization(org);
            invitation.setInvitedBy(currentUser);
            invitation.setProject(project);
            invitation.setStatus(InvitationStatus.PENDING);
            invitation.setInvitedAt(LocalDateTime.now());

            invitationRepository.save(invitation);

            auditLogService.log(currentUser.getUsername(), "INVITE",
                    "Invitation", invitation.getId(),
                    "Mời user " + invitedUser.getUsername());

            Map<String, Object> resp = new HashMap<>();
            resp.put("success", true);
            resp.put("message", "Đã gửi lời mời đến " + email);
            resp.put("invitationId", invitation.getId());

            return ResponseEntity.ok(resp);

        } catch (Exception e) {
            e.printStackTrace();
            return ResponseEntity.status(500).body(Map.of("error", "Lỗi server: " + e.getMessage()));
        }
    }

    // ==================== LẤY LỜI MỜI CỦA USER ====================
    @GetMapping("/invitations/my")
    @PreAuthorize("hasAnyRole('ADMIN','MEMBER','EMPLOYEE')")
    public ResponseEntity<?> getMyInvitations(Principal principal) {
        try {
            User user = userRepository.findByUsername(principal.getName())
                    .orElseThrow(() -> new RuntimeException("User không tồn tại"));

            List<Invitation> pending = invitationRepository
                    .findByInvitedUserAndStatus(user, InvitationStatus.PENDING);

            List<Map<String, Object>> list = pending.stream()
                    .map(i -> {
                        Map<String, Object> m = new HashMap<>();
                        m.put("id", i.getId());
                        m.put("organizationName", i.getOrganization().getName());
                        m.put("invitedBy", i.getInvitedBy().getUsername());
                        m.put("invitedAt", i.getInvitedAt());
                        if (i.getProject() != null) {
                            m.put("projectId", i.getProject().getId());
                            m.put("projectName", i.getProject().getName());
                        }
                        return m;
                    })
                    .collect(Collectors.toList());

            return ResponseEntity.ok(list);

        } catch (Exception e) {
            return ResponseEntity.status(500).body(Map.of("error", "Lỗi server"));
        }
    }

    // ==================== ACCEPT ====================
    @PostMapping("/invitations/{invitationId}/accept")
    @PreAuthorize("hasAnyRole('ADMIN','MEMBER','EMPLOYEE')")
    public ResponseEntity<?> acceptInvitation(@PathVariable Integer invitationId, Principal principal) {
        try {
            User user = userRepository.findByUsername(principal.getName())
                    .orElseThrow();

            Invitation inv = invitationRepository.findById(invitationId)
                    .orElseThrow(() -> new RuntimeException("Không tìm thấy lời mời"));

            if (!inv.getInvitedUser().getId().equals(user.getId())) {
                return ResponseEntity.status(403).body(Map.of("error", "Không có quyền"));
            }

            if (inv.getStatus() != InvitationStatus.PENDING) {
                return ResponseEntity.badRequest().body(Map.of("error", "Lời mời đã xử lý"));
            }

            // 🚨 KIỂM TRA GIỚI HẠN THÀNH VIÊN (10 người)
            Organization org = inv.getOrganization();
            Long currentMemberCount = userRepository.countByOrganization(org);
            if (currentMemberCount >= MAX_MEMBERS_PER_GROUP) {
                return ResponseEntity.badRequest().body(Map.of(
                    "error", 
                    "Nhóm đã đạt tối đa " + MAX_MEMBERS_PER_GROUP + " thành viên, không thể tham gia"
                ));
            }

            user.setOrganization(org);
            userRepository.save(user);

            if (inv.getProject() != null) {
                try {
                    projectMemberService.addMember(inv.getProject().getId(), user.getId());
                } catch (Exception ignore) {}
            }

            inv.accept();
            invitationRepository.save(inv);

            Map<String, Object> response = new HashMap<>();
            response.put("success", true);
            response.put("message", "Đã tham gia nhóm thành công");
            response.put("organizationName", inv.getOrganization().getName());
            
            return ResponseEntity.ok(response);

        } catch (Exception e) {
            return ResponseEntity.status(500).body(Map.of("error", "Lỗi server: " + e.getMessage()));
        }
    }

    // ==================== REJECT ====================
    @PostMapping("/invitations/{invitationId}/reject")
    @PreAuthorize("hasAnyRole('ADMIN','MEMBER','EMPLOYEE')")
    public ResponseEntity<?> rejectInvitation(@PathVariable Integer invitationId, Principal principal) {
        try {
            User user = userRepository.findByUsername(principal.getName())
                    .orElseThrow();

            Invitation inv = invitationRepository.findById(invitationId)
                    .orElseThrow(() -> new RuntimeException("Lời mời không tồn tại"));

            if (!inv.getInvitedUser().getId().equals(user.getId())) {
                return ResponseEntity.status(403).body(Map.of("error", "Không có quyền"));
            }

            if (inv.getStatus() != InvitationStatus.PENDING) {
                return ResponseEntity.badRequest().body(Map.of("error", "Lời mời đã xử lý"));
            }

            inv.decline();
            invitationRepository.save(inv);

            Map<String, Object> response = new HashMap<>();
            response.put("success", true);
            response.put("message", "Đã từ chối lời mời");
            
            return ResponseEntity.ok(response);

        } catch (Exception e) {
            return ResponseEntity.status(500).body(Map.of("error", "Lỗi server: " + e.getMessage()));
        }
    }

    // ==================== GET PROJECT LIST ====================
    @GetMapping("/projects/available")
    @PreAuthorize("hasRole('ADMIN')")
    public ResponseEntity<?> getAvailableProjects(Principal principal) {
        try {
            User user = userRepository.findByUsername(principal.getName())
                    .orElseThrow();

            if (user.getOrganization() == null)
                return ResponseEntity.badRequest().body(Map.of("error", "Bạn chưa thuộc nhóm nào"));

            List<Project> projects = projectRepository.findByOrganization(user.getOrganization());

            return ResponseEntity.ok(
                    projects.stream()
                            .map(p -> Map.of(
                                    "id", p.getId(),
                                    "name", p.getName(),
                                    "description", p.getDescription()
                            ))
                            .collect(Collectors.toList())
            );

        } catch (Exception e) {
            return ResponseEntity.status(500).body(Map.of("error", "Lỗi server"));
        }
    }

    // ==================== GET PROJECT DETAILS ====================
    @GetMapping("/projects/{projectId}")
    @PreAuthorize("hasAnyRole('ADMIN','MEMBER','EMPLOYEE')")
    public ResponseEntity<?> getProjectDetails(@PathVariable Integer projectId, Principal principal) {
        try {
            User user = userRepository.findByUsername(principal.getName())
                    .orElseThrow(() -> new RuntimeException("User không tồn tại"));

            if (user.getOrganization() == null)
                return ResponseEntity.status(403).body(Map.of("error", "Bạn chưa thuộc nhóm nào"));

            Project project = projectRepository.findById(projectId)
                    .orElseThrow(() -> new RuntimeException("Không tìm thấy dự án"));

            // 🚨 KIỂM TRA QUYỀN: dự án phải thuộc tổ chức của user
            if (!project.getOrganization().getId().equals(user.getOrganization().getId()))
                return ResponseEntity.status(403).body(Map.of("error", "Bạn không có quyền truy cập dự án này"));

            // 🚨 QUAN TRỌNG: Nếu user không phải ADMIN, kiểm tra xem có phải thành viên dự án không
            if (!user.getRole().equals(User.Role.ADMIN)) {
                boolean isMember = projectMemberService.isUserInProject(projectId, user.getId());
                if (!isMember) {
                    return ResponseEntity.status(403).body(Map.of("error", "Bạn không có quyền truy cập dự án này (không phải thành viên)"));
                }
            }

            Map<String, Object> res = new HashMap<>();
            res.put("id", project.getId());
            res.put("name", project.getName());
            res.put("description", project.getDescription());
            res.put("createdAt", project.getCreatedAt());
            res.put("deadline", project.getDeadline());
            res.put("projectType", project.getProjectType());
            res.put("status", project.getStatus());

            return ResponseEntity.ok(res);

        } catch (RuntimeException e) {
            return ResponseEntity.status(404).body(Map.of("error", e.getMessage()));
        } catch (Exception e) {
            return ResponseEntity.status(500).body(Map.of("error", "Lỗi server: " + e.getMessage()));
        }
    }

    // ==================== CẬP NHẬT DỰ ÁN ====================
    @PutMapping("/projects/{projectId}")
    @PreAuthorize("hasRole('ADMIN')")
    public ResponseEntity<?> updateProject(
            @PathVariable Integer projectId,
            @RequestBody Map<String, Object> updates,
            Principal principal) {
        try {
            User currentUser = userRepository.findByUsername(principal.getName())
                    .orElseThrow(() -> new RuntimeException("User không tồn tại"));

            // Kiểm tra user có thuộc tổ chức không
            if (currentUser.getOrganization() == null) {
                return ResponseEntity.status(403).body(Map.of("error", "Bạn không thuộc tổ chức nào"));
            }

            // Tìm dự án
            Project project = projectRepository.findById(projectId)
                    .orElseThrow(() -> new RuntimeException("Không tìm thấy dự án"));

            // Kiểm tra quyền truy cập: dự án phải thuộc tổ chức của user
            if (!project.getOrganization().getId().equals(currentUser.getOrganization().getId())) {
                return ResponseEntity.status(403).body(Map.of("error", "Bạn không có quyền chỉnh sửa dự án này"));
            }

            // Cập nhật các trường được phép
            if (updates.containsKey("name")) {
                String name = (String) updates.get("name");
                if (name != null && !name.trim().isEmpty()) {
                    project.setName(name.trim());
                } else {
                    return ResponseEntity.badRequest().body(Map.of("error", "Tên dự án không được để trống"));
                }
            }

            if (updates.containsKey("description")) {
                String description = (String) updates.get("description");
                project.setDescription(description != null ? description.trim() : null);
            }

            // Xử lý deadline - nhận cả hai trường (deadline hoặc deadlineDate)
            Object deadlineObj = updates.get("deadline");
            if (deadlineObj == null) {
                deadlineObj = updates.get("deadlineDate");
            }
            
            if (deadlineObj != null) {
                if (deadlineObj instanceof String) {
                    String deadlineStr = (String) deadlineObj;
                    if (!deadlineStr.trim().isEmpty()) {
                        try {
                            // Thử parse với định dạng ISO LocalDateTime
                            LocalDateTime deadline = LocalDateTime.parse(deadlineStr);
                            project.setDeadline(deadline);
                        } catch (Exception e) {
                            // Nếu không parse được LocalDateTime, thử LocalDate
                            try {
                                LocalDate localDate = LocalDate.parse(deadlineStr);
                                LocalDateTime deadline = localDate.atStartOfDay();
                                project.setDeadline(deadline);
                            } catch (Exception e2) {
                                return ResponseEntity.badRequest().body(Map.of("error", "Định dạng ngày không hợp lệ. Sử dụng định dạng YYYY-MM-DD hoặc YYYY-MM-DDTHH:mm:ss"));
                            }
                        }
                    } else {
                        // Chuỗi rỗng - đặt deadline thành null
                        project.setDeadline(null);
                    }
                } else if (deadlineObj instanceof String && ((String) deadlineObj).equalsIgnoreCase("null")) {
                    project.setDeadline(null);
                }
            }

            // Lưu thay đổi
            projectRepository.save(project);

            auditLogService.log(currentUser.getUsername(), "UPDATE",
                    "Project", project.getId(),
                    "Cập nhật dự án: " + project.getName());

            Map<String, Object> response = new HashMap<>();
            response.put("success", true);
            response.put("message", "Cập nhật dự án thành công");
            response.put("projectId", project.getId());
            response.put("name", project.getName());
            response.put("description", project.getDescription());
            response.put("deadline", project.getDeadline());

            return ResponseEntity.ok(response);

        } catch (RuntimeException e) {
            return ResponseEntity.status(404).body(Map.of("error", e.getMessage()));
        } catch (Exception e) {
            e.printStackTrace();
            return ResponseEntity.status(500).body(Map.of("error", "Lỗi server: " + e.getMessage()));
        }
    }

 // ==================== XÓA DỰ ÁN ====================
    @DeleteMapping("/projects/{projectId}")
    @PreAuthorize("hasRole('ADMIN')")
    @Transactional
    public ResponseEntity<?> deleteProject(@PathVariable Integer projectId, Principal principal) {
        try {
            User currentUser = userRepository.findByUsername(principal.getName())
                    .orElseThrow(() -> new RuntimeException("User không tồn tại"));

            if (currentUser.getOrganization() == null) {
                return ResponseEntity.status(403).body(Map.of("error", "Bạn không thuộc tổ chức nào"));
            }

            Project project = projectRepository.findById(projectId)
                    .orElseThrow(() -> new RuntimeException("Không tìm thấy dự án"));

            if (!project.getOrganization().getId().equals(currentUser.getOrganization().getId())) {
                return ResponseEntity.status(403).body(Map.of("error", "Bạn không có quyền xóa dự án này"));
            }

            // ✅ 1. XOÁ PROJECT MEMBER (QUAN TRỌNG NHẤT)
            projectMemberService.deleteByProjectId(projectId);

            // ✅ 2. XOÁ INVITATIONS
            invitationRepository.deleteByProjectId(projectId);

            // (Nếu có task, comment, attachment... thì xoá tiếp tại đây)

            // ✅ 3. GHI AUDIT LOG
            auditLogService.log(
                    currentUser.getUsername(),
                    "DELETE",
                    "Project",
                    project.getId(),
                    "Xóa dự án: " + project.getName()
            );

            // ✅ 4. XOÁ PROJECT
            projectRepository.delete(project);

            return ResponseEntity.ok(
                    Map.of("success", true, "message", "Xóa dự án thành công")
            );

        } catch (RuntimeException e) {
            return ResponseEntity.status(404).body(Map.of("error", e.getMessage()));
        } catch (Exception e) {
            e.printStackTrace();
            return ResponseEntity.status(500).body(Map.of("error", "Lỗi server: " + e.getMessage()));
        }
    }


}