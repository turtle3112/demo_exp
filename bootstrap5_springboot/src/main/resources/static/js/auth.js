const API_BASE_URL = ""; // Cập nhật lại nếu backend dùng port khác

$(document).ready(() => {
  $('#loginForm').on('submit', function (e) {
    e.preventDefault();

    const username = $('#username').val().trim();
    const password = $('#password').val();

    // Validate phía client
    if (!username || !password) {
      return showLoginError("Vui lòng điền đầy đủ tài khoản và mật khẩu.");
    }
    if (password.length < 6) {
      return showLoginError("Mật khẩu phải có ít nhất 6 ký tự.");
    }

    $.ajax({
      url: `${API_BASE_URL}/auth/login`,
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ username, password }),
	  success: function (res) {
	      console.log("Phản hồi đăng nhập: ", res);
	      localStorage.setItem('token', res.token);
	      localStorage.setItem('user', JSON.stringify(res.user));
	      
	      const accountType = (res.user.accountType || '').toUpperCase();
	      const role = (res.user.role || '').toUpperCase();
	      
	      console.log("Quyết định điều hướng - Loại tài khoản:", accountType, "Role: ", role);
	      
	      // ✅ ĐẢM BẢO AJAX HEADER ĐƯỢC SETUP
	      $.ajaxSetup({
	          headers: {
	              'Authorization': 'Bearer ' + res.token
	          }
	      });
	      
		  if (username == 'admin' && password == '12345678') {
		  	          window.location.href = 'dashboard.html';
		  	      }
		  else if (accountType === 'PERSONAL') {
	          window.location.href = 'projects_personal.html';
	      } 
	      else if (accountType === 'TEAM' && role === 'ADMIN') {
	          window.location.href = 'projects_groups.html';
	      }
	      else if (accountType === 'ENTERPRISE' && role === 'ADMIN') {
	          window.location.href = 'dashboard_business.html';
	      }
	      else {
	          if (role === 'EMPLOYEE') {
	              window.location.href = 'project_business.html';
	          } else {
	              window.location.href = 'projects_personal.html';
	          }
	      }
	  },
      error: function (err) {
		console.error('Lỗi đăng nhập:', err);
        if (err.status === 401 || err.status === 403) {
          showLoginError("Tài khoản hoặc mật khẩu không đúng.");
        } else {
          showLoginError("Đã có lỗi xảy ra, vui lòng thử lại sau.");
        }
      }
    });
  });

  function showLoginError(msg) {
    $('#errorMsg').text(msg).removeClass('d-none');
  }
});

// Hàm xử lý đăng ký (thêm vào cuối file auth.js)
function registerUser() {
    const formData = {
        fullName: $('#fullName').val(),
        email: $('#email').val(),
        username: $('#username').val(),
        password: $('#password').val(),
        accountType: $('#accountType').val(),
        organizationName: $('#teamName').val() || $('#companyName').val() || ''
    };
    
    // Basic validation
    if (formData.password !== $('#confirmPassword').val()) {
        showRegisterError('Mật khẩu xác nhận không khớp!');
        return;
    }
    
    // Gọi API đăng ký công khai
    $.ajax({
        url: '/auth/public/register',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            showRegisterSuccess('Đăng ký thành công! Chuyển hướng đến trang đăng nhập...');
			// Log thông tin trial để debug
			console.log('Registration successful:', response);
			if (response.trialEndDate) {
			    console.log('Trial ends at:', response.trialEndDate);
			}
			setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        },
        error: function(xhr) {
            const errorMessage = xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText;
            showRegisterError('Đăng ký thất bại: ' + errorMessage);
        }
    });
}

function showRegisterError(message) {
    $('#errorMsg').text(message).removeClass('d-none');
    $('#successMsg').addClass('d-none');
}

function showRegisterSuccess(message) {
    $('#successMsg').text(message).removeClass('d-none');
    $('#errorMsg').addClass('d-none');
}

function checkTrialStatus() {
    const user = JSON.parse(localStorage.getItem('user'));
    if (user && user.accountType === 'PERSONAL') {
        // Hiển thị thông báo trial trên UI
        const trialInfo = document.getElementById('trial-info');
        if (trialInfo) {
            trialInfo.innerHTML = `
                <div class="alert alert-info">
                    <strong>Trial Account:</strong> Bạn đang dùng thử. 
                    <a href="/upgrade.html">Nâng cấp ngay</a>
                </div>
            `;
        }
    }
}

// Gọi function này khi trang load
$(document).ready(() => {
    checkTrialStatus();
});