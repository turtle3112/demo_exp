package dto;

import java.time.LocalDateTime;

public class EmployeeDTO {
    private Long id;
    private String username;
    private String fullName;
    private String email;
    private String employeeId;
    private LocalDateTime createdAt;
    private boolean isAdmin;
    
    // Constructors
    public EmployeeDTO() {}
    
    public EmployeeDTO(Long id, String username, String fullName, String email, 
                      String employeeId, LocalDateTime createdAt, boolean isAdmin) {
        this.setId(id);
        this.setUsername(username);
        this.setFullName(fullName);
        this.setEmail(email);
        this.setEmployeeId(employeeId);
        this.setCreatedAt(createdAt);
        this.setAdmin(isAdmin);
    }

	public Long getId() {
		return id;
	}

	public void setId(Long id) {
		this.id = id;
	}

	public String getUsername() {
		return username;
	}

	public void setUsername(String username) {
		this.username = username;
	}

	public String getFullName() {
		return fullName;
	}

	public void setFullName(String fullName) {
		this.fullName = fullName;
	}

	public String getEmployeeId() {
		return employeeId;
	}

	public void setEmployeeId(String employeeId) {
		this.employeeId = employeeId;
	}

	public String getEmail() {
		return email;
	}

	public void setEmail(String email) {
		this.email = email;
	}

	public LocalDateTime getCreatedAt() {
		return createdAt;
	}

	public void setCreatedAt(LocalDateTime createdAt) {
		this.createdAt = createdAt;
	}

	public boolean isAdmin() {
		return isAdmin;
	}

	public void setAdmin(boolean isAdmin) {
		this.isAdmin = isAdmin;
	}

}