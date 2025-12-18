// ========== CẬP NHẬT QUAN TRỌNG: TÍCH HỢP VỚI BACKEND ==========

// Base URL cho API - khớp với UserController
const API_BASE_URL = 'http://localhost:8080/users';

// Global variables
let userEmail = '';
let otpTimer;
let timeLeft = 300; // 5 minutes in seconds

// Hàm test API connection
async function testAPI() {
    console.log('🔍 Testing API connection...');
    try {
        const response = await fetch(`${API_BASE_URL}/forgot-password`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: 'test@example.com' })
        });
        console.log('✅ API Response Status:', response.status);
        const text = await response.text();
        console.log('✅ API Response:', text);
        return { success: response.ok, status: response.status, data: text };
    } catch (error) {
        console.error('❌ API Test Failed:', error);
        return { success: false, error: error.message };
    }
}

// Hàm gọi API - ĐÃ SỬA: Xử lý response tốt hơn
async function callAPI(endpoint, data) {
    console.log(`📤 Calling API: ${API_BASE_URL}${endpoint}`, data);
    
    try {
        const response = await fetch(`${API_BASE_URL}${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        console.log(`📥 API Response Status: ${response.status} for ${endpoint}`);
        
        let result;
        try {
            result = await response.json();
        } catch {
            const text = await response.text();
            result = { message: text };
        }
        
        console.log(`📥 API Response Data:`, result);
        
        return { 
            success: response.ok, 
            data: result,
            status: response.status 
        };
    } catch (error) {
        console.error(`❌ API call failed for ${endpoint}:`, error);
        return { 
            success: false, 
            error: 'Lỗi kết nối đến server',
            data: { message: 'Lỗi kết nối đến server. Vui lòng kiểm tra kết nối mạng.' }
        };
    }
}

// Show message function - ĐÃ SỬA: Hiển thị tốt hơn
function showMessage(message, type = 'info') {
    const messageContainer = document.getElementById('messageContainer');
    const messageClass = type === 'error' ? 'error' : 
                        type === 'success' ? 'success' : 'info';
    
    messageContainer.innerHTML = `
        <div class="message ${messageClass}">
            ${message}
        </div>
    `;
    
    // Auto remove success/info messages after 5 seconds
    if (type === 'success' || type === 'info') {
        setTimeout(() => {
            if (messageContainer.innerHTML.includes(message)) {
                messageContainer.innerHTML = '';
            }
        }, 5000);
    }
}

// Move between steps - ĐÃ SỬA: Thêm animation
function goToStep(stepNumber) {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(step => {
        step.classList.remove('active');
    });
    
    // Remove active class from all steps
    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active', 'completed');
    });
    
    // Show current step and mark previous steps as completed
    for (let i = 1; i <= stepNumber; i++) {
        if (i === stepNumber) {
            document.getElementById(`step${i}`).classList.add('active');
            setTimeout(() => {
                document.getElementById(`step${i}Form`).classList.add('active');
            }, 100);
        } else {
            document.getElementById(`step${i}`).classList.add('completed');
        }
    }
}

// Step 1: Send OTP - GỌI API THẬT - ĐÃ SỬA: Thêm loading state
async function sendOTP() {
    const email = document.getElementById('email').value.trim();
    
    if (!email) {
        showMessage('Vui lòng nhập email của bạn', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showMessage('Email không hợp lệ', 'error');
        return;
    }
    
    userEmail = email;
    
    // Disable button và hiển thị loading
    const sendButton = document.querySelector('#step1Form button');
    const originalText = sendButton.textContent;
    sendButton.textContent = '🔄 Đang gửi...';
    sendButton.disabled = true;
    
    showMessage('🔄 Đang gửi mã OTP...', 'info');
    
    try {
        const result = await callAPI('/forgot-password', { email: email });
        
        if (result.success) {
            showMessage('✅ Nếu email tồn tại, mã OTP sẽ được gửi đến email của bạn! Vui lòng kiểm tra hộp thư.', 'success');
            document.getElementById('emailDisplay').textContent = email;
            startOTPTimer();
            goToStep(2);
            
            // Hiển thị OTP demo trong console
            console.log('=== DEMO: Kiểm tra console backend để xem mã OTP ===');
            
        } else {
            showMessage(`❌ ${result.data?.message || 'Lỗi khi gửi OTP. Vui lòng thử lại.'}`, 'error');
        }
    } catch (error) {
        showMessage('❌ Lỗi kết nối. Vui lòng kiểm tra mạng và thử lại.', 'error');
    } finally {
        // Restore button
        sendButton.textContent = originalText;
        sendButton.disabled = false;
    }
}

// Step 2: Verify OTP - GỌI API THẬT - ĐÃ SỬA: Thêm loading state
async function verifyOTP() {
    const otpCode = getOTPCode();
    
    if (otpCode.length !== 6) {
        showMessage('Vui lòng nhập đầy đủ 6 chữ số OTP', 'error');
        return;
    }
    
    if (!/^\d{6}$/.test(otpCode)) {
        showMessage('Mã OTP phải là 6 chữ số', 'error');
        return;
    }
    
    // Disable button và hiển thị loading
    const verifyButton = document.querySelector('#step2Form button');
    const originalText = verifyButton.textContent;
    verifyButton.textContent = '🔍 Đang xác thực...';
    verifyButton.disabled = true;
    
    showMessage('🔍 Đang xác thực OTP...', 'info');
    
    try {
        const result = await callAPI('/verify-otp', {
            email: userEmail,
            otp: otpCode
        });
        
        if (result.success) {
            showMessage('✅ OTP xác thực thành công!', 'success');
            clearInterval(otpTimer);
            setTimeout(() => goToStep(3), 1000);
        } else {
            showMessage(`❌ ${result.data?.message || 'OTP không chính xác hoặc đã hết hạn'}`, 'error');
            clearOTPInputs();
        }
    } catch (error) {
        showMessage('❌ Lỗi kết nối. Vui lòng thử lại.', 'error');
    } finally {
        // Restore button
        verifyButton.textContent = originalText;
        verifyButton.disabled = false;
    }
}

// Step 3: Reset Password - GỌI API THẬT - ĐÃ SỬA: Thêm loading state
async function resetPassword() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const otpCode = getOTPCode();
    
    // Validation
    if (!newPassword || !confirmPassword) {
        showMessage('Vui lòng nhập đầy đủ mật khẩu', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showMessage('Mật khẩu xác nhận không khớp', 'error');
        return;
    }
    
    if (newPassword.length < 8) {
        showMessage('Mật khẩu phải có ít nhất 8 ký tự', 'error');
        return;
    }
    
    // Disable button và hiển thị loading
    const resetButton = document.querySelector('#step3Form button');
    const originalText = resetButton.textContent;
    resetButton.textContent = '🔄 Đang xử lý...';
    resetButton.disabled = true;
    
    showMessage('🔄 Đang đặt lại mật khẩu...', 'info');
    
    try {
        const result = await callAPI('/reset-password', {
            email: userEmail,
            otp: otpCode,
            newPassword: newPassword
        });
        
        if (result.success) {
            showMessage('✅ Đặt lại mật khẩu thành công! Đang chuyển hướng...', 'success');
            
            // Redirect to login page after 2 seconds
            setTimeout(() => {
                window.location.href = 'login.html?message=password_reset_success';
            }, 2000);
        } else {
            showMessage(`❌ ${result.data?.message || 'Lỗi khi đặt lại mật khẩu. Vui lòng thử lại.'}`, 'error');
        }
    } catch (error) {
        showMessage('❌ Lỗi kết nối. Vui lòng thử lại.', 'error');
    } finally {
        // Restore button
        resetButton.textContent = originalText;
        resetButton.disabled = false;
    }
}

// OTP Input Handling Functions - GIỮ NGUYÊN
function handleOtpInput(current) {
    const index = parseInt(current.getAttribute('data-index'));
    const value = current.value;
    
    // Only allow numbers and ensure it's a single digit
    if (value && !/^\d$/.test(value)) {
        current.value = '';
        return;
    }
    
    // Update visual state
    if (value) {
        current.classList.add('filled');
        
        // Auto-focus next input if available
        if (index < 5) {
            const nextInput = document.querySelector(`.otp-input[data-index="${index + 1}"]`);
            if (nextInput) {
                nextInput.focus();
            }
        }
    } else {
        current.classList.remove('filled');
    }
}

function handleOtpKeydown(current, event) {
    const index = parseInt(current.getAttribute('data-index'));
    const key = event.key;
    
    // Allow only numbers
    if (!/^\d$/.test(key) && 
        !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(key)) {
        event.preventDefault();
        return;
    }
    
    // Handle Backspace
    if (key === 'Backspace') {
        if (current.value === '' && index > 0) {
            // Move to previous input and clear it
            const prevInput = document.querySelector(`.otp-input[data-index="${index - 1}"]`);
            if (prevInput) {
                prevInput.focus();
                prevInput.value = '';
                prevInput.classList.remove('filled');
            }
        } else {
            // Clear current input but stay there
            current.value = '';
            current.classList.remove('filled');
        }
        event.preventDefault();
    }
    
    // Handle Arrow keys for navigation
    else if (key === 'ArrowLeft' && index > 0) {
        const prevInput = document.querySelector(`.otp-input[data-index="${index - 1}"]`);
        if (prevInput) prevInput.focus();
        event.preventDefault();
    }
    else if (key === 'ArrowRight' && index < 5) {
        const nextInput = document.querySelector(`.otp-input[data-index="${index + 1}"]`);
        if (nextInput) nextInput.focus();
        event.preventDefault();
    }
    
    // Handle number input
    else if (/^\d$/.test(key)) {
        current.value = key;
        current.classList.add('filled');
        
        // Auto-move to next input
        if (index < 5) {
            const nextInput = document.querySelector(`.otp-input[data-index="${index + 1}"]`);
            if (nextInput) {
                setTimeout(() => nextInput.focus(), 10);
            }
        }
        event.preventDefault();
    }
}

function getOTPCode() {
    const otpInputs = document.querySelectorAll('.otp-input');
    let otpCode = '';
    otpInputs.forEach(input => {
        otpCode += input.value;
    });
    return otpCode;
}

function clearOTPInputs() {
    const otpInputs = document.querySelectorAll('.otp-input');
    otpInputs.forEach(input => {
        input.value = '';
        input.classList.remove('filled');
    });
    // Focus on first input
    if (otpInputs[0]) {
        otpInputs[0].focus();
    }
}

function autoFillOTP() {
    // Hàm này giờ chỉ để test - trong thực tế sẽ nhập OTP thật từ email
    showMessage('💡 Trong môi trường thật, hãy kiểm tra email để lấy mã OTP', 'info');
}

// Timer Functions - GIỮ NGUYÊN
function startOTPTimer() {
    timeLeft = 300; // Reset to 5 minutes
    updateTimerDisplay();
    
    clearInterval(otpTimer);
    otpTimer = setInterval(() => {
        timeLeft--;
        updateTimerDisplay();
        
        if (timeLeft <= 0) {
            clearInterval(otpTimer);
            const timerElement = document.getElementById('otpTimer');
            if (timerElement) {
                timerElement.classList.add('expired');
            }
            const resendLink = document.getElementById('resendLink');
            if (resendLink) {
                resendLink.classList.remove('disabled');
            }
        }
    }, 1000);
    
    const resendLink = document.getElementById('resendLink');
    if (resendLink) {
        resendLink.classList.add('disabled');
    }
}

function updateTimerDisplay() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    const countdownElement = document.getElementById('countdown');
    if (countdownElement) {
        countdownElement.textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
}

// Resend OTP - ĐÃ SỬA: Thêm loading state
async function resendOTP() {
    const resendLink = document.getElementById('resendLink');
    if (resendLink.classList.contains('disabled')) {
        return;
    }
    
    // Disable resend và hiển thị loading
    resendLink.classList.add('disabled', 'loading');
    const originalText = resendLink.textContent;
    resendLink.textContent = '🔄 Đang gửi...';
    
    showMessage('🔄 Đang gửi lại mã OTP...', 'info');
    
    try {
        const result = await callAPI('/forgot-password', { email: userEmail });
        
        if (result.success) {
            showMessage('✅ Đã gửi lại mã OTP! Vui lòng kiểm tra email.', 'success');
            startOTPTimer();
            clearOTPInputs();
        } else {
            showMessage(`❌ ${result.data?.message || 'Lỗi khi gửi lại OTP'}`, 'error');
            resendLink.classList.remove('disabled');
        }
    } catch (error) {
        showMessage('❌ Lỗi kết nối. Vui lòng thử lại.', 'error');
        resendLink.classList.remove('disabled');
    } finally {
        // Restore resend link
        resendLink.classList.remove('loading');
        resendLink.textContent = originalText;
    }
}

// Password Strength Check - GIỮ NGUYÊN
function checkPasswordStrength() {
    const password = document.getElementById('newPassword').value;
    const strengthBar = document.getElementById('passwordStrength');
    const requirements = document.getElementById('passwordRequirements');
    
    if (!strengthBar || !requirements) return;
    
    let strength = 0;
    let feedback = [];
    
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    // Update strength bar
    strengthBar.className = 'password-strength';
    if (strength <= 2) {
        strengthBar.classList.add('weak');
        feedback.push('Mật khẩu yếu');
    } else if (strength <= 4) {
        strengthBar.classList.add('medium');
        feedback.push('Mật khẩu trung bình');
    } else {
        strengthBar.classList.add('strong');
        feedback.push('Mật khẩu mạnh');
    }
    
    // Update requirements text
    if (password.length > 0) {
        requirements.textContent = feedback.join(' • ');
    } else {
        requirements.textContent = 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số';
    }
}

function checkPasswordMatch() {
    const password = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const messageElement = document.getElementById('passwordMatchMessage');
    
    if (!messageElement) return;
    
    if (confirmPassword.length === 0) {
        messageElement.textContent = '';
        return;
    }
    
    if (password === confirmPassword) {
        messageElement.textContent = '✓ Mật khẩu khớp';
        messageElement.style.color = '#28a745';
    } else {
        messageElement.textContent = '✗ Mật khẩu không khớp';
        messageElement.style.color = '#dc3545';
    }
}

// Navigation Functions - GIỮ NGUYÊN
function backToStep1() {
    goToStep(1);
    clearInterval(otpTimer);
}

function backToStep2() {
    goToStep(2);
    startOTPTimer();
}

// Utility Functions - GIỮ NGUYÊN
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Initialize when page loads - ĐÃ SỬA: Xử lý lỗi tốt hơn
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Forgot Password Page Loaded');
    
    // Test API connection on load
    testAPI().then(result => {
        if (result.success) {
            console.log('✅ API Connection Test: PASSED');
            showMessage('✅ Kết nối server thành công!', 'success');
        } else {
            console.log('❌ API Connection Test: FAILED');
            showMessage('⚠️ Không thể kết nối đến server. Vui lòng đảm bảo server đang chạy.', 'error');
        }
    });
    
    // Focus email input on page load
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.focus();
    }
    
    // Add Enter key support
    if (emailInput) {
        emailInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendOTP();
            }
        });
    }
    
    const newPasswordInput = document.getElementById('newPassword');
    if (newPasswordInput) {
        newPasswordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                resetPassword();
            }
        });
    }

    // Add paste support for OTP
    const otpInputs = document.querySelectorAll('.otp-input');
    if (otpInputs.length > 0) {
        otpInputs[0].addEventListener('paste', function(e) {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
            const digits = pasteData.split('');
            
            digits.forEach((digit, index) => {
                if (otpInputs[index]) {
                    otpInputs[index].value = digit;
                    otpInputs[index].classList.add('filled');
                }
            });
            
            // Focus the next empty input or the last one
            const nextEmptyIndex = digits.length < 6 ? digits.length : 5;
            if (otpInputs[nextEmptyIndex]) {
                otpInputs[nextEmptyIndex].focus();
            }
        });
    }
    
    // Thêm CSS cho loading states nếu chưa có
    if (!document.querySelector('#forgotPasswordStyles')) {
        const styles = `
            <style id="forgotPasswordStyles">
                button:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }
                .loading {
                    opacity: 0.6;
                    cursor: wait !important;
                }
                .message.success {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }
                .message.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
                .message.info {
                    background: #d1ecf1;
                    color: #0c5460;
                    border: 1px solid #bee5eb;
                }
            </style>
        `;
        document.head.insertAdjacentHTML('beforeend', styles);
    }
});