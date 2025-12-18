package com.vti.repository;

import com.vti.model.ProjectMember;
import com.vti.model.ProjectMemberId;

import jakarta.transaction.Transactional;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Modifying;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.List;

public interface ProjectMemberRepository extends JpaRepository<ProjectMember, ProjectMemberId> {

    // ==================== QUERY ====================

    List<ProjectMember> findByProjectId(Integer projectId);

    List<ProjectMember> findByUserId(Integer userId);

    boolean existsByProjectIdAndUserId(Integer projectId, Integer userId);

    // ==================== DELETE ====================

    /**
     * Xóa toàn bộ member của project
     * 👉 dùng khi delete project để tránh lỗi FK
     */
    @Modifying
    @Transactional
    @Query("DELETE FROM ProjectMember pm WHERE pm.id.projectId = :projectId")
    void deleteByProjectId(@Param("projectId") Integer projectId);

    /**
     * Xóa 1 user khỏi project
     */
    @Modifying
    @Transactional
    @Query("""
        DELETE FROM ProjectMember pm
        WHERE pm.id.projectId = :projectId
          AND pm.id.userId = :userId
    """)
    void deleteByProjectIdAndUserId(
            @Param("projectId") Integer projectId,
            @Param("userId") Integer userId
    );
}
