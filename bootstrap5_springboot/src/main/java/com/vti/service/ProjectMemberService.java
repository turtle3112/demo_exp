package com.vti.service;

import com.vti.model.Project;
import com.vti.model.ProjectMember;
import com.vti.model.ProjectMemberId;
import com.vti.model.User;
import com.vti.repository.ProjectMemberRepository;
import com.vti.repository.ProjectRepository;
import com.vti.repository.UserRepository;

import jakarta.transaction.Transactional;
import org.springframework.stereotype.Service;

import java.util.List;

@Service
public class ProjectMemberService {

    private final ProjectMemberRepository projectMemberRepository;
    private final ProjectRepository projectRepository;
    private final UserRepository userRepository;
    private final NotificationService notificationService;

    public ProjectMemberService(
            ProjectMemberRepository projectMemberRepository,
            ProjectRepository projectRepository,
            UserRepository userRepository,
            NotificationService notificationService
    ) {
        this.projectMemberRepository = projectMemberRepository;
        this.projectRepository = projectRepository;
        this.userRepository = userRepository;
        this.notificationService = notificationService;
    }

    // ==================== ADD SINGLE MEMBER ====================
    public ProjectMember addMember(Integer projectId, Integer userId) {
        Project project = projectRepository.findById(projectId)
                .orElseThrow(() -> new RuntimeException("Project không tồn tại"));

        User user = userRepository.findById(userId)
                .orElseThrow(() -> new RuntimeException("User không tồn tại"));

        ProjectMember member = new ProjectMember(project.getId(), user.getId());

        projectMemberRepository.save(member);

        notificationService.notifyProjectAssignment(
                user.getUsername(),
                projectId
        );

        return member;
    }

    // ==================== ADD MULTIPLE MEMBERS ====================
    @Transactional
    public void addMembers(Integer projectId, List<Integer> userIds) {
        Project project = projectRepository.findById(projectId)
                .orElseThrow(() -> new RuntimeException("Project không tồn tại"));

        for (Integer userId : userIds) {
            User user = userRepository.findById(userId)
                    .orElseThrow(() -> new RuntimeException("User không tồn tại: " + userId));

            // Chưa là member thì mới thêm
            if (!projectMemberRepository.existsByProjectIdAndUserId(projectId, userId)) {
                ProjectMember member = new ProjectMember(projectId, userId);
                projectMemberRepository.save(member);

                notificationService.notifyProjectAssignment(
                        user.getUsername(),
                        projectId
                );
            }
        }
    }

    // ==================== UPDATE MEMBERS ====================
    @Transactional
    public void updateMembers(Integer projectId, List<Integer> userIds) {
        // Xoá toàn bộ member cũ
        projectMemberRepository.deleteByProjectId(projectId);

        // Thêm lại theo danh sách mới
        for (Integer userId : userIds) {
            ProjectMember member = new ProjectMember(projectId, userId);
            projectMemberRepository.save(member);
        }
    }

    // ==================== REMOVE ONE MEMBER ====================
    @Transactional
    public void removeMember(Integer projectId, Integer userId) {
        Project project = projectRepository.findById(projectId)
                .orElseThrow(() -> new RuntimeException("Project không tồn tại"));

        User user = userRepository.findById(userId)
                .orElseThrow(() -> new RuntimeException("User không tồn tại"));

        ProjectMemberId id = new ProjectMemberId(
                project.getId(),
                user.getId()
        );

        projectMemberRepository.deleteById(id);
    }

    // ==================== GET MEMBERS BY PROJECT ====================
    public List<ProjectMember> getMembersByProject(Integer projectId) {
        Project project = projectRepository.findById(projectId)
                .orElseThrow(() -> new RuntimeException("Project không tồn tại"));

        return projectMemberRepository.findByProjectId(project.getId());
    }

    // ==================== CHECK USER IN PROJECT ====================
    public boolean isUserInProject(Integer projectId, Integer userId) {
        Project project = projectRepository.findById(projectId)
                .orElseThrow(() -> new RuntimeException("Project không tồn tại"));

        User user = userRepository.findById(userId)
                .orElseThrow(() -> new RuntimeException("User không tồn tại"));

        return projectMemberRepository.existsByProjectIdAndUserId(
                project.getId(),
                user.getId()
        );
    }

    // ==================== GET PROJECTS BY USER ====================
    public List<ProjectMember> getProjectsByUser(Integer userId) {
        return projectMemberRepository.findByUserId(userId);
    }

    // ==================== 🔥 DELETE ALL MEMBERS BY PROJECT (FIX FK) ====================
    @Transactional
    public void deleteByProjectId(Integer projectId) {
        projectMemberRepository.deleteByProjectId(projectId);
    }
}
