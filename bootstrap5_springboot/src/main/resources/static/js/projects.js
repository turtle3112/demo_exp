const API_BASE_URL = "";

let allProjects = [];

$(document).ready(() => {
  const user = JSON.parse(localStorage.getItem('user'));
  const token = localStorage.getItem('token');

  if (!user || !token) {
    return window.location.href = 'login.html';
  }

  // Gán quyền hiển thị theo role
  if (user.role === 'ADMIN') {
    $('#btnCreateProject').removeClass('d-none');
    $('#btnDashboard').removeClass('d-none');
  }

  // Load danh sách project
  $.ajax({
    url: `${API_BASE_URL}/projects/all`,
    method: 'GET',
    headers: {
      Authorization: `Bearer ${token}`
    },
    success: function (projects) {
      allProjects = projects;
      renderProjects(allProjects);
    },
    error: function () {
      alert('Lỗi khi load danh sách dự án.');
    }
  });

  // Tìm kiếm
  $('#searchInput').on('input', () => {
    const keyword = $('#searchInput').val().toLowerCase().trim();
    const filtered = allProjects.filter(p => p.name.toLowerCase().includes(keyword));
    renderProjects(filtered);
  });

  // Sắp xếp
  $('#sortSelect').on('change', () => {
    const selected = $('#sortSelect').val();
    let sorted = [...allProjects];

    switch (selected) {
      case 'name-asc':
        sorted.sort((a, b) => a.name.localeCompare(b.name));
        break;
      case 'name-desc':
        sorted.sort((a, b) => b.name.localeCompare(a.name));
        break;
    }

    renderProjects(sorted);
  });
  
  function loadUsersForProjectCreation() {
    $.ajax({
      url: `${API_BASE_URL}/users`,
      method: 'GET',
      headers: { Authorization: `Bearer ${token}` },
      success: (users) => {
        const html = users.map(u => `
          <div class="form-check">
            <input class="form-check-input new-project-user-checkbox" type="checkbox" value="${u.id}" id="new-user-${u.id}">
            <label class="form-check-label" for="new-user-${u.id}">${u.fullName} (${u.username})</label>
          </div>
        `).join('');
        $('#newProjectUserCheckboxes').html(html);
      },
      error: () => alert("Không thể tải danh sách user")
    });
  }

  $('#createProjectModal').on('show.bs.modal', loadUsersForProjectCreation);

  async function createNewProject() {
    const name = $('#newProjectName').val().trim();
    const description = $('#newProjectDescription').val().trim();
    const userIds = $('.new-project-user-checkbox:checked').map(function () {
      return parseInt(this.value);
    }).get();

    if (!name) return alert("Vui lòng nhập tên dự án");

    try {
      // B1: Tạo project
      const res = await fetch(`${API_BASE_URL}/projects/add`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ name, description })
      });

      if (!res.ok) throw new Error("Tạo dự án thất bại");

      const createdProject = await res.json();

      // B2: Phân công user vào project
      for (const userId of userIds) {
        await fetch(`${API_BASE_URL}/project-members/add`, {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            projectId: createdProject.id,
            userId
          })
        });
      }

      $('#createProjectModal').modal('hide');
      alert("Tạo dự án thành công!");
      location.reload();

    } catch (err) {
      console.error(err);
      alert("Đã có lỗi xảy ra khi tạo dự án.");
    }
  }
  
  $('#btnSubmitCreateProject').on('click', createNewProject);
  
  function loadNotifications() {
    $.ajax({
      url: `${API_BASE_URL}/notifications`,
      method: 'GET',
      headers: { Authorization: `Bearer ${token}` },
      success: (notifications) => {
        renderNotificationList(notifications);
        updateNotiBadge(notifications);
      },
      error: () => console.error("Lỗi khi tải notification")
    });
  }

  function renderNotificationList(notifications) {
    const list = $('#notificationList');
    list.empty();

    if (notifications.length === 0) {
      list.append('<li class="dropdown-item text-muted small">Không có thông báo nào.</li>');
      return;
    }

    notifications.forEach(noti => {
      const item = `
        <li class="border-bottom py-2 small d-flex justify-content-between align-items-start ${noti.read ? '' : 'fw-bold'}">
          <div>
            ${noti.message}
            <div class="text-muted small">${new Date(noti.createdAt).toLocaleString()}</div>
          </div>
          <button class="btn btn-sm btn-link text-danger p-0 ms-2 btn-delete-noti" data-id="${noti.id}">🗑️</button>
        </li>`;
      list.append(item);

      // Auto mark as read nếu chưa đọc
      if (!noti.read) markAsRead(noti.id);
    });
  }

  function markAsRead(id) {
    $.ajax({
      url: `${API_BASE_URL}/notifications/${id}/read`,
      method: 'PATCH',
      headers: { Authorization: `Bearer ${token}` }
    });
  }

  
  function deleteNotification(id) {
    const token = localStorage.getItem('token');
    fetch(`${API_BASE_URL}/notifications/${id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${token}` }
    }).then(() => fetchNotifications());
  }
  
  

  function updateNotiBadge(notifications) {
    const unreadCount = notifications.filter(n => !n.read).length;
    const badge = $('#notiBadge');

    if (unreadCount > 0) {
      badge.removeClass('d-none').text(unreadCount);
    } else {
      badge.addClass('d-none');
    }
  }

  
  $('#notificationIcon').on('click', function () {
    $('#notificationDropdown').toggle(); // Mở / đóng dropdown

    // Gọi API lấy noti và mark đã đọc
    fetchNotifications();
  });
	
  
  async function fetchNotifications() {
    const token = localStorage.getItem('token');
    const res = await fetch(`${API_BASE_URL}/notifications`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    const notifications = await res.json();

    const list = notifications.map(noti => `
      <li class="mb-2 border-bottom pb-2 ${noti.read ? '' : 'fw-bold'}">
        ${noti.message}
		<button class="btn btn-sm btn-link text-danger p-0 ms-2 btn-delete-noti" data-id="${noti.id}">🗑️</button>
      </li>
    `).join('');

    $('#notificationList').html(list);
	
	$('.btn-delete-noti').on('click', function () {
	    const id = $(this).data('id');
	    deleteNotification(id);
	  });
    // Đánh dấu đã đọc
    notifications.forEach(noti => {
      if (!noti.read) {
        fetch(`${API_BASE_URL}/notifications/${noti.id}/read`, {
          method: 'PATCH',
          headers: { Authorization: `Bearer ${token}` }
        });
      }
    });

    // Ẩn badge vì đã đọc hết
    $('#notiBadge').addClass('d-none');
  }
  
  async function checkUnreadNotifications() {
    const token = localStorage.getItem('token');
    const res = await fetch(`${API_BASE_URL}/notifications/unread`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    const unread = await res.json();
    if (unread.length > 0) {
      $('#notiBadge').removeClass('d-none');
    }
  }
  checkUnreadNotifications();
});

function renderProjects(projects) {
  const container = $('#projectList');
  container.empty();

  if (!projects.length) {
    container.html("<p>Chưa có dự án nào bạn tham gia.</p>");
    return;
  }

  projects.forEach(p => {
    const card = `
      <div class="col-md-4 mb-4">
        <div class="card shadow-sm h-100" role="button" onclick="viewTasks(${p.id})">
          <div class="card-body">
            <h5 class="card-title">${p.name}</h5>
            <p class="card-text">${p.description || "Không có mô tả"}</p>
            <div class="progress mt-2">
              <div class="progress-bar bg-success" role="progressbar" style="width: ${p.progress || 0}%">
                ${p.progress || 0}%
              </div>
            </div>
          </div>
        </div>
      </div>`;
    container.append(card);
  });
}

function viewTasks(projectId) {
  window.location.href = `tasks.html?projectId=${projectId}`;
}



