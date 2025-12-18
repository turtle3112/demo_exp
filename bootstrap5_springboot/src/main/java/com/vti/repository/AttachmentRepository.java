package com.vti.repository;

import com.vti.model.Attachment;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.util.List;

public interface AttachmentRepository extends JpaRepository<Attachment, Integer> {
	List<Attachment> findByTaskId(Integer taskId);

	void deleteByTaskId(Integer taskId);
	
    List<Attachment> findByUploadedById(Integer userId);
    
    List<Attachment> findByUploadedByIdIn(List<Integer> userIds);

}