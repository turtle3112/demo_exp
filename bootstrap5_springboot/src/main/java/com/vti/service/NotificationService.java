package com.vti.service;

import com.vti.model.*;
import com.vti.repository.NotificationRepository;
import com.vti.repository.ProjectRepository;
import com.vti.repository.TaskRepository;
import com.vti.repository.UserRepository;
import jakarta.transaction.Transactional;
import org.springframework.stereotype.Service;

import java.time.LocalDateTime;
import java.util.*;

@Service
public class NotificationService {

	private final NotificationRepository notificationRepository;
	private final UserRepository userRepository;
	private final ProjectRepository projectRepository;
	private final TaskRepository taskRepository;

	public NotificationService(NotificationRepository notificationRepository, UserRepository userRepository,
			ProjectRepository projectRepository, TaskRepository taskRepository) {
		this.notificationRepository = notificationRepository;
		this.userRepository = userRepository;
		this.projectRepository = projectRepository;
		this.taskRepository = taskRepository;
	}

	// Tạo noti cho 1 user
	public void createNotification(User user, String message) {
		Notification noti = new Notification();
		noti.setUser(user);
		noti.setMessage(message);
		noti.setIsRead(false);
		noti.setCreatedAt(LocalDateTime.now());
		notificationRepository.save(noti);
	}

	// Tạo noti cho nhiều user
	public void createNotifications(Collection<User> users, String message) {
		List<Notification> notifications = new ArrayList<>();
		for (User user : users) {
			Notification noti = new Notification();
			noti.setUser(user);
			noti.setMessage(message);
			noti.setIsRead(false);
			noti.setCreatedAt(LocalDateTime.now());
			notifications.add(noti);
		}
		notificationRepository.saveAll(notifications);
	}

	// Admin gửi noti cho toàn bộ user
	public void notifyAllUsers(String message) {
		List<User> allUsers = userRepository.findAll();
		createNotifications(allUsers, message);
	}

	// Trigger 1 Gán user vào project
	public void notifyProjectAssignment(String username, Integer projectId) {
		Optional<Project> projectOpt = projectRepository.findById(projectId);
		if (projectOpt.isEmpty())
			return;

		Project project = projectOpt.get();

		userRepository.findByUsername(username).ifPresent(user -> {
			createNotification(user, "Bạn đã được thêm vào project: " + project.getName());
		});
	}

	// Trigger Gán user vào task
	public void notifyTaskAssignment(Integer taskId, List<String> usernames) {
		Optional<Task> taskOpt = taskRepository.findById(taskId);
		if (taskOpt.isEmpty())
			return;
		Task task = taskOpt.get();

		for (String username : usernames) {
			userRepository.findByUsername(username).ifPresent(user -> {
				createNotification(user, "Bạn đã được giao task: " + task.getName());
			});
		}
	}

	// Trigger Task đổi trạng thái
	public void notifyTaskStatusChanged(Task task, Task.Status oldStatus) {
		String msg = "Task \"" + task.getName() + "\" đã đổi trạng thái từ " + oldStatus + " sang " + task.getStatus();
		createNotifications(task.getAssignedUsers(), msg);
	}

	// Trigger 4 - Có comment mới
	public void notifyCommentAdded(Task task, User author) {
		String msg = author.getUsername() + " đã thêm 1 bình luận vào task: " + task.getName();
		Set<User> users = new HashSet<>(task.getAssignedUsers());
		users.remove(author); // Không gửi noti cho chính người tạo comment
		createNotifications(users, msg);
	}

	// Trigger 5 - Có file đính kèm mới
	public void notifyAttachmentAdded(Task task, User uploader) {
		String msg = uploader.getUsername() + " đã thêm 1 file vào task: " + task.getName();
		Set<User> users = new HashSet<>(task.getAssignedUsers());
		users.remove(uploader); // Không gửi noti cho chính uploader
		createNotifications(users, msg);
	}

	// Lấy tất cả notification theo user
	public List<Notification> getNotifications(String username) {
		Optional<User> userOpt = userRepository.findByUsername(username);
		return userOpt.map(notificationRepository::findByUserOrderByCreatedAtDesc).orElse(Collections.emptyList());
	}

	// Đánh dấu là đã đọc
	@Transactional
	public void markAsRead(Integer notificationId, String username) {
		Notification noti = notificationRepository.findById(notificationId).orElseThrow();
		if (!noti.getUser().getUsername().equals(username)) {
			throw new SecurityException("Không có quyền");
		}
		noti.setIsRead(true);
		notificationRepository.save(noti);
	}

	public List<Notification> getUnreadNotifications(String username) {
		Optional<User> userOpt = userRepository.findByUsername(username);
		return userOpt.map(user -> notificationRepository.findByUserAndIsReadFalseOrderByCreatedAtDesc(user))
				.orElse(Collections.emptyList());
	}

	public void deleteNotification(Integer id, String username) {
		Notification noti = notificationRepository.findById(id).orElseThrow();
		if (!noti.getUser().getUsername().equals(username)) {
			throw new SecurityException("Không có quyền xoá thông báo này");
		}
		notificationRepository.delete(noti);
	}
}
