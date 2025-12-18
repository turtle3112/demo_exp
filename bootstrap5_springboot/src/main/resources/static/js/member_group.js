const API_BASE_URL = "http://localhost:8080"; 

$(document).ready(() => {
  const token = localStorage.getItem("token");
  const user = JSON.parse(localStorage.getItem("user") || '{}');
  if (!token || !user.id) return (window.location.href = "login.html");

  $('#currentUsername').text(`👤 ${user.fullName || user.username}`);
  loadGroupMembers(user);

  // Sự kiện cho nút xóa của ADMIN
  $('#userList').on('click', '.delete-user-btn', function () {
    const userId = $(this).data('id');
    deleteMember(userId);
  });

  // Sự kiện cho nút "Rời nhóm" của thành viên
  $('#userList').on('click', '.leave-group-btn', function () {
    leaveGroup();
  });
});

function loadGroupMembers(currentUser) {
  $('#userList').html('<p>Đang tải danh sách thành viên...</p>');

  $.ajax({
    url: `${API_BASE_URL}/groups/members`,
    method: "GET",
    headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
    success: function(response) {  // ✅ THAY ĐỔI: response thay vì members
      console.log("Dữ liệu từ API:", response);
      
      const container = $('#userList').empty();

      // ✅ FIX LỖI: Lấy danh sách thành viên từ response.members
      // API trả về object, không phải array trực tiếp
      let members = [];
      
      if (Array.isArray(response)) {
        // Trường hợp hiếm: API trả về array trực tiếp
        members = response;
      } else if (response && typeof response === 'object' && Array.isArray(response.members)) {
        // Trường hợp thường gặp: API trả về object có thuộc tính members
        members = response.members;
        
        // Hiển thị thông tin giới hạn nhóm nếu có
        if (response.totalMembers !== undefined && response.maxMembers !== undefined) {
          container.append(`
            <div class="alert alert-info mb-3">
              <i class="fas fa-users me-2"></i>
              <strong>Thống kê nhóm:</strong> 
              ${response.totalMembers}/${response.maxMembers} thành viên 
              (còn ${response.remainingSlots || 0} chỗ trống)
            </div>
          `);
        }
      } else {
        // Dữ liệu không hợp lệ
        console.error("Dữ liệu không hợp lệ:", response);
        container.html('<p class="text-danger">Lỗi: Dữ liệu từ server không đúng định dạng</p>');
        return;
      }

      if (!members || members.length === 0) {
        container.html('<p>Chưa có thành viên nào trong nhóm.</p>');
        return;
      }

      // ✅ BÂY GIỜ SỬ DỤNG forEach ĐƯỢC (vì members đã là array)
      members.forEach(member => {
        // Kiểm tra nếu là chính user hiện tại
        const isCurrentUser = member.id === currentUser.id;
        const canDelete = currentUser.role === 'ADMIN' && !isCurrentUser;
        // ✅ THAY ĐỔI: Chỉ hiện nút "Rời nhóm" cho MEMBER, không hiện cho ADMIN
        const canLeave = isCurrentUser && currentUser.role !== 'ADMIN';

        container.append(`
          <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center">
            <div>
              <strong>${member.fullName || member.username}</strong> (${member.username}) - ${member.role}
              <br><small>Mã NV: ${member.employeeId || 'N/A'}</small>
              ${isCurrentUser ? '<br><span class="badge bg-primary">Bạn</span>' : ''}
            </div>
            <div>
              ${canDelete
                ? `<button class="btn btn-danger delete-user-btn me-2" data-id="${member.id}">Xóa khỏi nhóm</button>`
                : ''
              }
              ${canLeave
                ? `<button class="btn btn-warning leave-group-btn" data-id="${member.id}">Rời nhóm</button>`
                : ''
              }
            </div>
          </div>
        `);
      });
    },
    error: (xhr) => {
      console.error("Lỗi:", xhr);
      alert("Lỗi khi tải danh sách thành viên nhóm");
    }
  });
}

function deleteMember(userId) {
  if (!confirm("Bạn chắc chắn muốn xoá thành viên này khỏi nhóm?")) return;

  $.ajax({
    url: `${API_BASE_URL}/groups/members/${userId}`,
    method: 'DELETE',
    headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
    success: (response) => {
      alert("Xóa thành viên thành công!");
      loadGroupMembers(JSON.parse(localStorage.getItem("user")));
    },
    error: (xhr) => {
      console.error("Lỗi:", xhr);
      const message = xhr.responseJSON || xhr.responseText || "Không thể xoá thành viên khỏi nhóm";
      alert("Lỗi: " + message);
    }
  });
}

function leaveGroup() {
  if (!confirm("Bạn chắc chắn muốn rời khỏi nhóm này? Bạn sẽ không thể truy cập vào nhóm cho đến khi được thêm lại.")) return;

  // Sử dụng URL đơn giản, tránh encoding issues
  const url = `${API_BASE_URL}/groups/members/self`;
  
  console.log("Gửi request đến:", url);

  $.ajax({
    url: url,
    method: 'DELETE',
    headers: { 
      Authorization: `Bearer ${localStorage.getItem("token")}`,
      'Content-Type': 'application/json'
    },
    success: (response) => {
      console.log("Response thành công:", response);
      
      // Cập nhật thông tin user trong localStorage
      const user = JSON.parse(localStorage.getItem("user"));
      user.organization = null;
      localStorage.setItem("user", JSON.stringify(user));
      
      alert("Bạn đã rời nhóm thành công!");
      
      // Chuyển hướng về trang projects_personal.html
      window.location.href = "projects_personal.html";
    },
    error: (xhr) => {
      console.error("Lỗi chi tiết:", xhr);
      console.log("Status:", xhr.status);
      console.log("Response Text:", xhr.responseText);
      
      // Parse JSON response để lấy thông báo lỗi cụ thể
      let errorMessage = "Không thể rời nhóm";
      try {
        if (xhr.responseText) {
          const errorObj = JSON.parse(xhr.responseText);
          errorMessage = errorObj.error || errorObj.message || xhr.responseText;
        }
      } catch (e) {
        errorMessage = xhr.responseText || "Lỗi không xác định";
      }
      
      alert(`Lỗi ${xhr.status}: ${errorMessage}`);
      
      // Nếu là lỗi 400, vẫn cho phép chuyển hướng (trường hợp user đã không thuộc nhóm)
      if (xhr.status === 400) {
        const user = JSON.parse(localStorage.getItem("user"));
        user.organization = null;
        localStorage.setItem("user", JSON.stringify(user));
        window.location.href = "projects_personal.html";
      }
    }
  });
}