package com.vti.repository;

import com.vti.model.Organization;
import com.vti.model.Project;
import com.vti.model.User;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.List;

public interface ProjectRepository extends JpaRepository<Project, Integer> {
    List<Project> findByProjectType(Project.ProjectType projectType);
    
    List<Project> findByCreatedByAndProjectType(User createdBy, Project.ProjectType projectType);
    
    @Query("SELECT p FROM Project p WHERE p.id IN :projectIds AND p.projectType = :projectType")
    List<Project> findByIdInAndProjectType(@Param("projectIds") List<Integer> projectIds, 
                                          @Param("projectType") Project.ProjectType projectType);
    
    @Query("SELECT COUNT(t) FROM Task t WHERE t.project.id = :projectId")
    Long countTasksByProjectId(@Param("projectId") Integer projectId);
    
    List<Project> findByProjectTypeAndOrganization(Project.ProjectType projectType, Organization organization);
    
    // ✅ THÊM METHOD NÀY - QUAN TRỌNG CHO CHỨC NĂNG MỜI THÀNH VIÊN
    List<Project> findByOrganization(Organization organization);
    
    // ✅ CÓ THỂ THÊM METHOD NÀY ĐỂ LẤY DỰ ÁN THEO ORGANIZATION ID
    List<Project> findByOrganizationId(Integer organizationId);
    
    // 🚨 THÊM METHOD NÀY - QUAN TRỌNG ĐỂ LẤY DỰ ÁN THEO DANH SÁCH ID, LOẠI VÀ TỔ CHỨC
    @Query("SELECT p FROM Project p WHERE p.id IN :projectIds AND p.projectType = :projectType AND p.organization = :organization")
    List<Project> findByIdInAndProjectTypeAndOrganization(
        @Param("projectIds") List<Integer> projectIds, 
        @Param("projectType") Project.ProjectType projectType,
        @Param("organization") Organization organization
    );
    
    // 🚨 THÊM METHOD NÀY - QUAN TRỌNG CHO GIỚI HẠN DỰ ÁN (7 dự án mỗi nhóm)
    Long countByOrganization(Organization organization);
}