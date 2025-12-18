package com.vti.service;

import com.vti.model.AuditLog;
import com.vti.repository.AuditLogRepository;
import org.springframework.stereotype.Service;

@Service
public class AuditLogService {

	private final AuditLogRepository auditLogRepository;

	public AuditLogService(AuditLogRepository auditLogRepository) {
		this.auditLogRepository = auditLogRepository;
	}

	public void log(String username, String action, String entity, Integer entityId, String details) {
		AuditLog log = new AuditLog();
		log.setUsername(username);
		log.setAction(action);
		log.setEntity(entity);
		log.setEntityId(entityId);
		log.setDetails(details);
		auditLogRepository.save(log);
	}
}
