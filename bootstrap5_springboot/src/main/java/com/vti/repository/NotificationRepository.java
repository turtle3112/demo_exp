package com.vti.repository;

import com.vti.model.Notification;
import com.vti.model.User;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface NotificationRepository extends JpaRepository<Notification, Integer> {
	List<Notification> findByUserOrderByCreatedAtDesc(User user);

	List<Notification> findByUserAndIsReadFalseOrderByCreatedAtDesc(User user);
}
