package dto;

public class ForgotPasswordRequest {
    private String email;

    public ForgotPasswordRequest() {
    }

    public ForgotPasswordRequest(String email) {
        this.email = email;
    }

    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    @Override
    public String toString() {
        return "ForgotPasswordRequest{" +
                "email='" + email + '\'' +
                '}';
    }

    public boolean isValid() {
        return email != null && !email.trim().isEmpty() && 
               email.matches("^[A-Za-z0-9+_.-]+@(.+)$");
    }
}