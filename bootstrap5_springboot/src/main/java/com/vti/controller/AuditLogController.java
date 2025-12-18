package com.vti.controller;

import com.vti.model.AuditLog;
import com.vti.repository.AuditLogRepository;
import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import jakarta.persistence.Query;
import org.springframework.data.domain.PageRequest;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.time.LocalDate;
import java.util.List;

@PreAuthorize("hasRole('ADMIN')")
@RestController
@RequestMapping("/audit-logs")
public class AuditLogController {

	private final AuditLogRepository auditLogRepository;

	@PersistenceContext
	private EntityManager entityManager;

	public AuditLogController(AuditLogRepository auditLogRepository) {
		this.auditLogRepository = auditLogRepository;
	}

	public static class AuditLogFilter {
		public String username;
		public String entity;
		public String action;
	}

	@GetMapping("/recent")
	public ResponseEntity<List<AuditLog>> getRecentLogs() {
		List<AuditLog> logs = auditLogRepository.findAll(PageRequest.of(0, 10)).getContent();
		return ResponseEntity.ok(logs);
	}

	@GetMapping("/all")
	public ResponseEntity<List<AuditLog>> getAllLogs() {
		return ResponseEntity.ok(auditLogRepository.findAll());
	}

	@GetMapping("/stats/top-users")
	public ResponseEntity<List<Object[]>> getTopUsers() {
		String sql = "SELECT a.username, COUNT(*) FROM AuditLog a GROUP BY a.username ORDER BY COUNT(*) DESC";
		Query query = entityManager.createQuery(sql);
		query.setMaxResults(5);
		return ResponseEntity.ok(query.getResultList());
	}

	@GetMapping("/stats/actions")
	public ResponseEntity<List<Object[]>> getActionCounts() {
		String sql = "SELECT a.action, COUNT(*) FROM AuditLog a GROUP BY a.action";
		Query query = entityManager.createQuery(sql);
		return ResponseEntity.ok(query.getResultList());
	}

	@GetMapping("/stats/daily")
	public ResponseEntity<List<Object[]>> getDailyCounts() {
		String sql = "SELECT DATE(a.timestamp), COUNT(*) FROM AuditLog a WHERE a.timestamp >= :start GROUP BY DATE(a.timestamp) ORDER BY DATE(a.timestamp)";
		Query query = entityManager.createQuery(sql);
		query.setParameter("start", LocalDate.now().minusDays(7).atStartOfDay());
		return ResponseEntity.ok(query.getResultList());
	}

	@GetMapping
	public ResponseEntity<List<AuditLog>> searchLogs(@RequestParam(required = false) String username,
			@RequestParam(required = false) String entity, @RequestParam(required = false) String action) {
		String jpql = "SELECT a FROM AuditLog a WHERE 1=1";
		if (username != null)
			jpql += " AND a.username = :username";
		if (entity != null)
			jpql += " AND a.entity = :entity";
		if (action != null)
			jpql += " AND a.action = :action";

		Query query = entityManager.createQuery(jpql);
		if (username != null)
			query.setParameter("username", username);
		if (entity != null)
			query.setParameter("entity", entity);
		if (action != null)
			query.setParameter("action", action);

		return ResponseEntity.ok(query.getResultList());
	}

	@PostMapping("/search")
	public ResponseEntity<List<AuditLog>> searchLogsWithBody(@RequestBody AuditLogFilter filter) {
		String jpql = "SELECT a FROM AuditLog a WHERE 1=1";
		if (filter.username != null)
			jpql += " AND a.username = :username";
		if (filter.entity != null)
			jpql += " AND a.entity = :entity";
		if (filter.action != null)
			jpql += " AND a.action = :action";

		Query query = entityManager.createQuery(jpql);
		if (filter.username != null)
			query.setParameter("username", filter.username);
		if (filter.entity != null)
			query.setParameter("entity", filter.entity);
		if (filter.action != null)
			query.setParameter("action", filter.action);

		return ResponseEntity.ok(query.getResultList());
	}
}
