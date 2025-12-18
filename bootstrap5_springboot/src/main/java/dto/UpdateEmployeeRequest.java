package dto;

public class UpdateEmployeeRequest {
    private String fullName;
    private String email;
    private String employeeId;
    
    // Constructors, Getters, Setters
    public UpdateEmployeeRequest() {}

	public String getFullName() {
		return fullName;
	}

	public void setFullName(String fullName) {
		this.fullName = fullName;
	}

	public String getEmail() {
		return email;
	}

	public void setEmail(String email) {
		this.email = email;
	}

	public String getEmployeeId() {
		return employeeId;
	}

	public void setEmployeeId(String employeeId) {
		this.employeeId = employeeId;
	}
    
    // [Generated code...]
}