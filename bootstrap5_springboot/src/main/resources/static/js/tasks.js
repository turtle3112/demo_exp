const API_BASE_URL = "";

$(document).ready(() => {
	const user = JSON.parse(localStorage.getItem('user'));
	const token = localStorage.getItem('token');
	const projectId = new URLSearchParams(window.location.search).get('projectId');

	if (!user || !token || !projectId) {
		return window.location.href = 'login.html';
	}

	if (user.role === 'ADMIN') {
		$('#createTaskBtn').removeClass('d-none');
		$('#btnDashboard').removeClass('d-none');
		$("#editProjectBtn").removeClass("d-none");
		document.getElementById('deleteProjectBtn').classList.remove('d-none');
		document.getElementById('deleteProjectBtn').addEventListener('click', deleteProject);
	}

	let allTasks = [];
	let allUsers = [];

	function loadTasks() {
		$.ajax({
			url: `${API_BASE_URL}/tasks/project/${projectId}`,
			method: 'GET',
			headers: { Authorization: `Bearer ${token}` },
			success: function(tasks) {
				allTasks = tasks;
				renderTasks();
			},
			error: function() {
				alert('Không thể tải danh sách công việc hoặc Bạn không được phân công dự án này.');
			}
		});
	}

	function renderTasks() {
		const search = $('#searchInput').val().toLowerCase();
		const status = $('#statusFilter').val();
		const sortBy = $('#sortSelect').val();

		let filtered = allTasks.filter(task => {
			return task.name.toLowerCase().includes(search) &&
				(!status || task.status === status);
		});

		if (sortBy === 'name') {
			filtered.sort((a, b) => a.name.localeCompare(b.name));
		} else if (sortBy === 'deadline') {
			filtered.sort((a, b) => new Date(a.deadline) - new Date(b.deadline));
		}

		$('#taskList').empty();
		if (!filtered.length) {
			$('#taskList').html('<p>Không có công việc nào phù hợp.</p>');
			return;
		}

		filtered.forEach(task => {
			const assigned = task.assignedUsers.map(u => u.id).includes(user.id);
			const deadline = new Date(task.deadline);
			const now = new Date();
			const isOverdue = deadline < now && task.status !== 'DONE';
			const badge = task.status === 'DONE' ? 'success' : task.status === 'IN_PROGRESS' ? 'warning' : 'secondary';

			const card = `
        <div class="col-md-4 mb-4">
          <div class="card shadow-sm h-100" role="button" onclick="handleTaskClick(${task.id}, ${assigned})">
            <div class="card-body">
              <h5 class="card-title">${task.name}</h5>
              <p class="card-text">${task.description || 'Không có mô tả'}</p>
              <span class="badge bg-${badge}">${task.status.replace('_', ' ')}</span>
              <span class="ms-2 ${isOverdue ? 'text-danger fw-bold' : ''}">📅 ${deadline.toLocaleDateString()}</span>
              <div class="progress mt-2">
                <div class="progress-bar" role="progressbar" style="width: ${task.progress || 0}%">
                  ${task.progress || 0}%
                </div>
              </div>
            </div>
          </div>
        </div>`;
			$('#taskList').append(card);
		});
	}

	function deleteProject() {
		if (!confirm('Bạn chắc chắn muốn xoá dự án này?')) return;

		fetch(`${API_BASE_URL}/projects/${projectId}`, {
			method: 'DELETE',
			headers: { Authorization: `Bearer ${token}` }
		})
			.then(res => {
				if (res.ok) {
					alert('Xoá dự án thành công');
					window.location.href = 'projects.html';
				} else throw new Error();
			})
			.catch(() => alert('Không thể xoá dự án'));
	}

	function loadUsersForAssign() {
		// Bước 1: Lấy danh sách tất cả user
		$.ajax({
			url: `${API_BASE_URL}/users`,
			method: 'GET',
			headers: { Authorization: `Bearer ${token}` },
			success: (users) => {
				allUsers = users;

				// Bước 2: Lấy danh sách userId thuộc project này
				$.ajax({
					url: `${API_BASE_URL}/project-members/project/${projectId}`,
					method: 'GET',
					headers: { Authorization: `Bearer ${token}` },
					success: (projectMembers) => {
						$('#userCheckboxList').empty();

						projectMembers.forEach(pm => {
							const u = users.find(user => user.id === pm.userId);
							if (u) {
								$('#userCheckboxList').append(`
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="${u.id}" id="user-${u.id}">
                    <label class="form-check-label" for="user-${u.id}">${u.fullName} (${u.username})</label>
                  </div>
                `);
							}
						});
					},
					error: () => alert('Không thể tải danh sách user được phân công vào dự án')
				});
			},
			error: () => alert('Không thể tải danh sách user')
		});
	}

	$('#createTaskModal').on('show.bs.modal', function() {
		loadUsersForAssign();
	});

	$('#editProjectBtn').on('click', async function () {
		const projectId = new URLSearchParams(window.location.search).get('projectId');

		const project = await $.ajax({
			url: `${API_BASE_URL}/projects/${projectId}`,
			method: 'GET',
			headers: { Authorization: `Bearer ${token}` }
		});
		const allUsers = await $.ajax({
			url: `${API_BASE_URL}/users`,
			method: 'GET',
			headers: { Authorization: `Bearer ${token}` }
		});

		const assigned = await $.ajax({
			url: `${API_BASE_URL}/project-members/project/${projectId}`,
			method: 'GET',
			headers: { Authorization: `Bearer ${token}` }
		});

		$('#editProjectName').val(project.name);
		$('#editProjectDescription').val(project.description);

		const html = allUsers.map(u => {
		  const isChecked = assigned.some(pm => pm.userId === u.id);
		  return `
		    <div class="form-check">
		      <input class="form-check-input project-user-checkbox" type="checkbox" value="${u.id}" id="u-${u.id}" ${isChecked ? 'checked' : ''}>
		      <label class="form-check-label" for="u-${u.id}">${u.fullName} (${u.username})</label>
		    </div>
		  `;
		}).join('');
		$('#projectUserCheckboxes').html(html);
		$('#editProjectModal').modal('show');
	});
	
	window.saveProjectEdit = async function () {
	  const name = $('#editProjectName').val();
	  const description = $('#editProjectDescription').val();
	  const userIds = $('.project-user-checkbox:checked').map(function () {
	    return parseInt(this.value);
	  }).get();

	  await $.ajax({
	    url: `${API_BASE_URL}/projects/${projectId}`,
	    method: 'PUT',
	    headers: { Authorization: `Bearer ${token}` },
	    contentType: 'application/json',
	    data: JSON.stringify({ name, description })
	  });

	  await $.ajax({
	    url: `${API_BASE_URL}/project-members/${projectId}`,
	    method: 'PUT',
	    headers: { Authorization: `Bearer ${token}` },
	    contentType: 'application/json',
	    data: JSON.stringify({ userIds })
	  });

	  $('#editProjectModal').modal('hide');
	  alert('Cập nhật thành công!');
	}



	document.getElementById("createTaskForm").addEventListener("submit", function(e) {
	  e.preventDefault();

	  const name = $('#taskName').val().trim();
	  const description = $('#taskDesc').val().trim();
	  const deadline = $('#taskDeadline').val();
	  const assignedUsers = $('#userCheckboxList input[type="checkbox"]:checked')
	    .map(function () { return parseInt(this.value); })
	    .get();

	  // VALIDATE
	  if (!name) return alert("Tên công việc không được để trống");
	  if (name.length < 3) return alert("Tên công việc phải có ít nhất 3 ký tự");
	  if (!deadline) return alert("Vui lòng chọn deadline");
	  if (assignedUsers.length === 0) return alert("Cần phân công ít nhất 1 người thực hiện");

	  const taskData = {
	    name,
	    description,
	    deadline,
	    status: 'TODO',
	    assignedUsers,
	    projectId: parseInt(projectId)
	  };

	  fetch(`${API_BASE_URL}/tasks`, {
	    method: "POST",
	    headers: {
	      Authorization: `Bearer ${token}`,
	      "Content-Type": "application/json"
	    },
	    body: JSON.stringify(taskData)
	  })
	    .then((res) => {
	      if (res.ok) {
	        alert("Tạo task thành công");
	        bootstrap.Modal.getInstance(document.getElementById("createTaskModal")).hide();
	        loadTasks();
	        this.reset();
	        $('#userCheckboxList input[type="checkbox"]').prop('checked', false);
	      } else {
	        res.text().then(msg => alert(msg));
	      }
	    })
	    .catch(() => alert("Tạo task thất bại"));
	});

	window.handleTaskClick = function(taskId, assigned) {
		if (user.role === 'ADMIN' || assigned) {
			window.location.href = `task_detail.html?taskId=${taskId}`;
		} else {
			const modal = new bootstrap.Modal(document.getElementById('unauthorizedModal'));
			modal.show();
		}
	}

	$('#searchInput, #statusFilter, #sortSelect').on('input change', renderTasks);

	loadTasks();

});

