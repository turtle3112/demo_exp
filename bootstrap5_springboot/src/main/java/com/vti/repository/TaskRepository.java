package com.vti.repository;

import com.vti.model.Project;
import com.vti.model.Task;
import com.vti.model.User;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query; // THÊM IMPORT NÀY
import org.springframework.data.repository.query.Param;

import java.util.List;
import java.util.Optional;

public interface TaskRepository extends JpaRepository<Task, Integer> {
	List<Task> findByProject(Project project);

	List<Task> findByAssignedUsersContaining(User user);

	List<Task> findByProjectAndAssignedUsersContaining(Project project, User user);

	Optional<Task> findById(Integer id);

	List<Task> findByProjectId(Integer projectId);
	
    @Query("SELECT COUNT(t) FROM Task t WHERE t.project.id = :projectId")
    Long countByProjectId(@Param("projectId") Integer projectId);
    
 // Thêm vào TaskRepository.java
    Long countByProjectIdAndStatus(@Param("projectId") Integer projectId, @Param("status") Task.Status status);
}
