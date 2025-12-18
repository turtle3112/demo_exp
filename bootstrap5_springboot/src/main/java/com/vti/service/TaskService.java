package com.vti.service;

import com.vti.model.Comment;
import com.vti.model.Project;
import com.vti.model.Task;
import com.vti.model.User;
import com.vti.repository.AttachmentRepository;
import com.vti.repository.CommentHistoryRepository;
import com.vti.repository.CommentRepository;
import com.vti.repository.ProjectRepository;
import com.vti.repository.TaskRepository;
import com.vti.repository.UserRepository;
import com.vti.service.ProjectMemberService;
import java.time.LocalDateTime; // THÊM DÒNG NÀY

import jakarta.transaction.Transactional;

import com.vti.service.NotificationService;
import org.springframework.stereotype.Service;

import java.util.HashSet;
import java.util.List;
import java.util.Set;
import java.util.stream.Collectors;

@Service
public class TaskService {
	private final TaskRepository taskRepository;
	private final ProjectRepository projectRepository;
	private final UserRepository userRepository;
	private final ProjectMemberService projectMemberService;
	private final NotificationService notificationService;
	private final CommentRepository commentRepository;
	private final AttachmentRepository attachmentRepository;
	private final CommentHistoryRepository commentHistoryRepository;

	public TaskService(TaskRepository taskRepository, ProjectRepository projectRepository,
			UserRepository userRepository, ProjectMemberService projectMemberService,
			NotificationService notificationService, CommentRepository commentRepository,
			AttachmentRepository attachmentRepository, CommentHistoryRepository commentHistoryRepository) {
		this.taskRepository = taskRepository;
		this.projectRepository = projectRepository;
		this.userRepository = userRepository;
		this.projectMemberService = projectMemberService;
		this.notificationService = notificationService;
		this.commentRepository = commentRepository;
		this.attachmentRepository = attachmentRepository;
		this.commentHistoryRepository = commentHistoryRepository;
	}

	public Task createTask(Task task) {
		Task savedTask = taskRepository.save(task);
		List<String> usernames = task.getAssignedUsers().stream().map(User::getUsername).toList();
		notificationService.notifyTaskAssignment(savedTask.getId(), usernames);
		return savedTask;
	}

	public Task updateTask(Integer taskId, Task updatedTask) {
	    Task task = taskRepository.findById(taskId).orElseThrow();

	    Task.Status oldStatus = task.getStatus();
	    Set<User> oldUsers = new HashSet<>(task.getAssignedUsers());

	    task.setName(updatedTask.getName());
	    task.setDescription(updatedTask.getDescription());
	    task.setDeadline(updatedTask.getDeadline());
	    task.setStatus(updatedTask.getStatus());
	    task.setProject(updatedTask.getProject());
	    task.setAssignedUsers(updatedTask.getAssignedUsers());
	    task.setPriority(updatedTask.getPriority());
	    
	    // THÊM: Cập nhật startDate và dueDate
	    task.setStartDate(updatedTask.getStartDate());
	    task.setDueDate(updatedTask.getDueDate());

	    Task savedTask = taskRepository.save(task);

	    if (!oldStatus.equals(updatedTask.getStatus())) {
	        notificationService.notifyTaskStatusChanged(task, oldStatus);
	    }

	    Set<User> newUsers = new HashSet<>(updatedTask.getAssignedUsers());
	    newUsers.removeAll(oldUsers);
	    List<String> newUsernames = newUsers.stream().map(User::getUsername).toList();
	    if (!newUsernames.isEmpty()) {
	        notificationService.notifyTaskAssignment(taskId, newUsernames);
	    }

	    return savedTask;
	}

	@Transactional
	public void deleteTask(Integer taskId) {

		List<Comment> comments = commentRepository.findByTaskId(taskId);
		for (Comment comment : comments) {
			commentHistoryRepository.deleteByCommentId(comment.getId());
		}
		// Xoá comment trước
		commentRepository.deleteByTaskId(taskId);
		attachmentRepository.deleteByTaskId(taskId);

		// Sau đó xoá task
		taskRepository.deleteById(taskId);
	}

	public List<Task> getTasksByProject(Integer projectId) {
		Project project = projectRepository.findById(projectId).orElseThrow();
		return taskRepository.findByProject(project);
	}

	public List<Task> getTasksAssignedToUser(User user) {
		return taskRepository.findByAssignedUsersContaining(user);
	}

	public Task updateStatus(Integer taskId, Task.Status status, String username) {
	    Task task = taskRepository.findById(taskId).orElseThrow();
	    User user = userRepository.findByUsername(username).orElseThrow();

	    Project project = task.getProject();

	    boolean isAdmin = user.getRole().equals(User.Role.ADMIN);
	    boolean isProjectOwner = project.getCreatedBy().getId().equals(user.getId());
	    boolean isAssignedUser = task.getAssignedUsers().contains(user);

	    if (!isAdmin && !isProjectOwner && !isAssignedUser) {
	        throw new SecurityException("Không có quyền cập nhật task này");
	    }

	    Task.Status oldStatus = task.getStatus();
	    task.setStatus(status);
	    Task savedTask = taskRepository.save(task);

	    if (!oldStatus.equals(status)) {
	        notificationService.notifyTaskStatusChanged(savedTask, oldStatus);
	    }

	    return savedTask;
	}


	public Task getTaskById(Integer id) {
		Task task = taskRepository.findById(id).orElseThrow(() -> new RuntimeException("Task không tồn tại"));
		return task;
	}

	@Transactional
	public Task assignUsers(Integer taskId, List<Integer> userIds) {
		Task task = taskRepository.findById(taskId).orElseThrow();

		Set<User> users = userIds.stream().map(id -> userRepository.findById(id).orElseThrow())
				.collect(Collectors.toSet());

		task.setAssignedUsers(users);

		// Gửi thông báo
		List<String> usernames = users.stream().map(User::getUsername).toList();
		notificationService.notifyTaskAssignment(task.getId(), usernames);

		return taskRepository.save(task);
	}
}
