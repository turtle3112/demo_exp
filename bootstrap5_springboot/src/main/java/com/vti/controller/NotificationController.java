package com.vti.controller;

import com.vti.model.Notification;
import com.vti.service.NotificationService;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.security.Principal;
import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/notifications")
public class NotificationController {

	private final NotificationService notificationService;

	public NotificationController(NotificationService notificationService) {
		this.notificationService = notificationService;
	}

	@PreAuthorize("hasRole('ADMIN')")
	@PostMapping("/broadcast")
	public ResponseEntity<Void> broadcastNotification(@RequestBody Map<String, String> request) {
		String message = request.get("message");
		notificationService.notifyAllUsers(message);
		return ResponseEntity.ok().build();
	}

	@GetMapping
	public ResponseEntity<List<Notification>> getMyNotifications(Principal principal) {
		return ResponseEntity.ok(notificationService.getNotifications(principal.getName()));
	}

	@PatchMapping("/{id}/read")
	public ResponseEntity<Void> markAsRead(@PathVariable Integer id, Principal principal) {
		notificationService.markAsRead(id, principal.getName());
		return ResponseEntity.ok().build();
	}

	@GetMapping("/unread")
	public ResponseEntity<List<Notification>> getUnreadNotifications(Principal principal) {
		return ResponseEntity.ok(notificationService.getUnreadNotifications(principal.getName()));
	}

	@DeleteMapping("/{id}")
	public ResponseEntity<Void> deleteNotification(@PathVariable Integer id, Principal principal) {
		notificationService.deleteNotification(id, principal.getName());
		return ResponseEntity.noContent().build();
	}
}
