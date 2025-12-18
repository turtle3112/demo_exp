const API_BASE_URL = "";

$(document).ready(() => {
  const token = localStorage.getItem("token");
  const user = JSON.parse(localStorage.getItem("user"));
  if (!token || !user) return (window.location.href = "login.html");

  $('#currentUsername').text(`👤 ${user.fullName}`);
  loadUsers();
  loadAuditLogs();

  $('.nav-link').on('click', function () {
    $('.nav-link').removeClass('active');
    $(this).addClass('active');
    const sectionId = $(this).data('section');
    $('.dashboard-section').addClass('d-none');
    $(`#section-${sectionId}`).removeClass('d-none');
  });

  $('#btnAddUser').on('click', function () {
    $('#userFormTitle').text("Thêm người dùng");
    $('#userForm').trigger('reset');
    $('#userId').val("");
    $('#inputPassword').parent().show();
    $('#userModal').modal('show');
  });

  $('#btnSaveUser').on('click', saveUser);

  $('#userList')
    .on('click', '.edit-user-btn', function () {
      openEditUserModal($(this).data('id'));
    })
    .on('click', '.delete-user-btn', function () {
      deleteUser($(this).data('id'));
    });
});

function loadUsers() {
  $.ajax({
    url: `${API_BASE_URL}/users`,
    method: "GET",
    headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
    success: (users) => {
      const container = $('#userList').empty();
      users.forEach(user => {
        container.append(`
          <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center">
            <div>
              <strong>${user.fullName}</strong> (${user.username}) - ${user.role}
              <br><small>Mã NV: ${user.employeeId || 'N/A'}</small>
            </div>
            <div>
              <button class="btn btn-warning edit-user-btn" data-id="${user.id}">Sửa</button>
              <button class="btn btn-danger delete-user-btn" data-id="${user.id}">Xóa</button>
            </div>
          </div>
        `);
      });
    },
    error: () => alert("Lỗi khi tải danh sách người dùng")
  });
}

function openEditUserModal(id) {
  $.ajax({
    url: `${API_BASE_URL}/users/${id}`,
    method: "GET",
    headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
    success: (user) => {
      $('#userFormTitle').text("Chỉnh sửa người dùng");
      $('#userId').val(user.id);
      $('#inputFullName').val(user.fullName);
      $('#inputUsername').val(user.username);
      $('#inputEmployeeId').val(user.employeeId);
      $('#inputRole').val(user.role);
      $('#inputPassword').parent().hide(); // Ẩn trường mật khẩu
      $('#userModal').modal('show');
    },
    error: () => alert("Lỗi khi tải thông tin người dùng")
  });
}

function saveUser() {
  const id = $('#userId').val();
  const isCreate = !id;

  const fullName = $('#inputFullName').val().trim();
  const username = $('#inputUsername').val().trim();
  const password = $('#inputPassword').val().trim();
  const employeeId = $('#inputEmployeeId').val().trim();
  const role = $('#inputRole').val();

  // Validate chung
  if (!fullName || !username || !employeeId || !role) {
    alert("Vui lòng điền đầy đủ thông tin.");
    return;
  }

  if (isCreate && (!password || password.length < 6)) {
    alert("Mật khẩu phải có ít nhất 6 ký tự.");
    return;
  }

  $.ajax({
    url: `${API_BASE_URL}/users`,
    method: "GET",
    headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
    success: (users) => {
      const usernameExists = users.some(u => u.username === username && (!id || u.id != id));
      const employeeIdExists = users.some(u => u.employeeId === employeeId && (!id || u.id != id));

      if (usernameExists) return alert("Tên đăng nhập đã tồn tại.");
      if (employeeIdExists) return alert("Mã nhân viên đã tồn tại.");

      // Tạo mới
      if (isCreate) {
        const data = { fullName, username, password, employeeId, role };
        $.ajax({
          url: `${API_BASE_URL}/auth/register`,
          method: 'POST',
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
            'Content-Type': 'application/json'
          },
          data: JSON.stringify(data),
          success: () => {
            $('#userModal').modal('hide');
            loadUsers();
          },
          error: (xhr) => {
            alert("Lỗi khi tạo người dùng: " + xhr.responseText);
          }
        });

      // Chỉnh sửa
      } else {
        const data = { fullName, username, employeeId, role };
        $.ajax({
          url: `${API_BASE_URL}/users/${id}`,
          method: 'PUT',
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
            'Content-Type': 'application/json'
          },
          data: JSON.stringify(data),
          success: () => {
            $('#userModal').modal('hide');
            loadUsers();
          },
          error: () => alert("Lỗi khi cập nhật người dùng")
        });
      }
    },
    error: () => alert("Không thể kiểm tra dữ liệu hiện có.")
  });
}


function deleteUser(id) {
  if (!confirm("Bạn chắc chắn muốn xoá người dùng này?")) return;
  $.ajax({
    url: `${API_BASE_URL}/users/${id}`,
    method: 'DELETE',
    headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
    success: () => loadUsers(),
    error: () => alert("Không thể xoá người dùng")
  });
}


function loadAuditLogs() {
  $.ajax({
    url: `${API_BASE_URL}/audit-logs/all`,
    method: "GET",
    headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
    success: (logs) => {
      const container = $('#auditLog').empty();
      if (!logs.length) return container.html('<p>Không có lịch sử hoạt động.</p>');

      logs.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

      logs.forEach(log => {
        const time = new Date(log.timestamp).toLocaleString();
        container.append(`
          <div class="border-bottom py-2">
            <div>
              <strong>${log.username}</strong> thực hiện 
              <strong>${log.action}</strong> tại 
              <em>${log.entity}</em> 
              (ID: ${log.entityId})
            </div>
            <div class="text-muted small">${time}</div>
          </div>
        `);
      });
    },
    error: () => $('#auditLog').html('<p class="text-danger">Lỗi khi tải log</p>')
  });
}
