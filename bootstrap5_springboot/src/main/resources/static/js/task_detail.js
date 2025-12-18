const API_BASE_URL = "";
const token = localStorage.getItem("token");
const user = JSON.parse(localStorage.getItem("user"));
const taskId = new URLSearchParams(window.location.search).get("taskId");

if (!taskId || !token || !user) {
	window.location.href = "login.html";
}

$(document).ready(() => {
	loadTaskDetail();
	loadComments();
	loadFiles();

	$("#btnBack").click(() => window.history.back());
	$("#btnUpdateStatus").click(updateTaskStatus);
	$("#btnSendComment").click(sendComment);
	$("#uploadForm").on("submit", uploadFile);
	$("#btnEdit").click(openEditModal);
	$("#btnDelete").click(deleteTask);
	$("#btnAssign").click(openAssignModal);
	$("#saveTaskEdit").click(saveTaskEdit);
	$("#saveAssignment").click((e) => saveAssignment(e));
});

// --- TASK DETAIL ---
function loadTaskDetail() {
	$.ajax({
		url: `${API_BASE_URL}/tasks/${taskId}`,
		method: "GET",
		headers: { Authorization: `Bearer ${token}` },
		success: (task) => {
			window.projectIdOfTask = task.project.id;
			$("#taskName").text(task.name);
			$("#taskDesc").text(task.description || "Không có mô tả");
			$("#taskStatus").text(task.status).removeClass().addClass(`badge bg-${getStatusColor(task.status)}`);
			$("#taskDeadline").text(task.deadline);
			const isAssigned = task.assignedUsers.some(u => u.username === user.username);
			const isAdmin = user.role === "ADMIN";
			const progress = task.progress || 0;
			$("#taskProgress").css("width", `${progress}%`).text(`${progress}%`);
			$("#taskUsers").text(task.assignedUsers.map(u => u.fullName).join(", "));
			window.assignedUsers = task.assignedUsers;
			assignedUsers = task.assignedUsers;

			if (isAdmin) {
				$("#btnEdit, #btnDelete, #btnAssign").removeClass("d-none");
			}

			if (isAssigned) {
				$("#statusUpdateSection").removeClass("d-none");
				$("#statusSelect").val(task.status);
			}
		},
		error: () => alert("Không thể tải chi tiết công việc"),
	});
}

function getStatusColor(status) {
	return {
		TODO: "secondary",
		IN_PROGRESS: "warning",
		DONE: "success",
	}[status] || "dark";
}

function updateTaskStatus() {
	const status = $("#statusSelect").val();
	$.ajax({
		url: `${API_BASE_URL}/tasks/${taskId}/status`,
		method: "PATCH",
		headers: {
			Authorization: `Bearer ${token}`,
			"Content-Type": "application/json",
		},
		data: JSON.stringify({ status }),
		success: () => {
			alert("Cập nhật trạng thái thành công");
			loadTaskDetail();
		},
		error: () => alert("Lỗi khi cập nhật trạng thái"),
	});
}

// --- COMMENT ---
function loadComments() {
	$.ajax({
		url: `${API_BASE_URL}/comments/task/${taskId}`,
		method: "GET",
		headers: { Authorization: `Bearer ${token}` },
		success: (comments) => {
			$("#commentList").empty();
			comments.forEach((c) => {
				const isOwner = c.createdBy.username === user.username;
				const isAdmin = user.role === "ADMIN";
				let actions = "";
				if (isOwner) {
					actions += `<a href="#" class="text-primary me-2 edit-comment" data-id="${c.id}" data-content="${c.content}">Sửa</a>`;
				}
				if (isOwner || isAdmin) {
					actions += `<a href="#" class="text-danger delete-comment" data-id="${c.id}">Xoá</a>`;
				}

				$("#commentList").append(`
					<div class="mb-2">
						<strong>${c.createdBy.fullName}:</strong>
						<span>${c.content}</span>
						<small class="text-muted">(${new Date(c.createdAt).toLocaleString()})</small>
						<div>${actions}</div>
					</div>
				`);
			});

			$(".delete-comment").click(function (e) {
				e.preventDefault();
				const id = $(this).data("id");
				if (confirm("Bạn có chắc muốn xoá bình luận?")) {
					$.ajax({
						url: `${API_BASE_URL}/comments/${id}`,
						method: "DELETE",
						headers: { Authorization: `Bearer ${token}` },
						success: loadComments,
					});
				}
			});

			$(".edit-comment").click(function (e) {
				e.preventDefault();
				const id = $(this).data("id");
				const content = prompt("Sửa bình luận:", $(this).data("content"));
				if (content) {
					$.ajax({
						url: `${API_BASE_URL}/comments/${id}`,
						method: "PATCH",
						headers: {
							Authorization: `Bearer ${token}`,
							"Content-Type": "application/json",
						},
						data: JSON.stringify({ content }),
						success: loadComments,
					});
				}
			});
		},
	});
}

function sendComment() {
	const content = $("#newComment").val().trim();
	if (!content) return;
	$.ajax({
		url: `${API_BASE_URL}/comments/task/${taskId}`,
		method: "POST",
		headers: {
			Authorization: `Bearer ${token}`,
			"Content-Type": "application/json",
		},
		data: JSON.stringify({ id: taskId, content }),
		success: () => {
			$("#newComment").val("");
			loadComments();
		},
	});
}

// --- FILES ---
function loadFiles() {
	$.ajax({
		url: `${API_BASE_URL}/attachments/task/${taskId}`,
		method: "GET",
		headers: { Authorization: `Bearer ${token}` },
		success: (files) => {
			$("#fileList").empty();
			files.forEach((f) => {
				const isUploader = f.uploadedBy.username === user.username;
				const isAdmin = user.role === "ADMIN";
				const canDelete = isUploader || isAdmin;

				$("#fileList").append(`
					<li class="list-group-item d-flex justify-content-between align-items-center">
						<a href="${API_BASE_URL}/attachments/download/${f.id}" target="_blank">${f.fileName}</a>
						<div>
							<span class="text-muted small">${new Date(f.uploadedAt).toLocaleString()}</span>
							${canDelete ? `<a href="#" class="text-danger ms-3 delete-file" data-id="${f.id}">Xoá</a>` : ""}
						</div>
					</li>
				`);
			});

			$(".delete-file").click(function (e) {
				e.preventDefault();
				const id = $(this).data("id");
				if (confirm("Xoá file này?")) {
					$.ajax({
						url: `${API_BASE_URL}/attachments/${id}`,
						method: "DELETE",
						headers: { Authorization: `Bearer ${token}` },
						success: loadFiles,
					});
				}
			});
		},
	});
}

function uploadFile(e) {
  e.preventDefault();
  const formData = new FormData(this);
  formData.append("id", taskId);

  $.ajax({
    url: `${API_BASE_URL}/attachments/task/${taskId}`,
    method: "POST",
    headers: { Authorization: `Bearer ${token}` },
    data: formData,
    processData: false,
    contentType: false,
    success: () => {
      this.reset();
      loadFiles();
    },
  });
}
// --- ADMIN FUNCTION ---
function openEditModal() {
	$("#editTaskName").val($("#taskName").text());
	$("#editTaskDesc").val($("#taskDesc").text());
	$("#editTaskDeadline").val($("#taskDeadline").text());
	const modal = new bootstrap.Modal("#editTaskModal");
	modal.show();
}

function saveTaskEdit() {
	const name = $("#editTaskName").val().trim();
	const description = $("#editTaskDesc").val().trim();
	const deadline = $("#editTaskDeadline").val();
	const projectId = window.projectIdOfTask;

	$.ajax({
		url: `${API_BASE_URL}/tasks/${taskId}`,
		method: "PUT",
		headers: {
			Authorization: `Bearer ${token}`,
			"Content-Type": "application/json",
		},
		data: JSON.stringify({
			id: taskId,
			name,
			description,
			deadline,
			projectId
		}),
		success: () => {
			loadTaskDetail();
			bootstrap.Modal.getInstance(document.getElementById("editTaskModal")).hide();
		},
	});
}

function deleteTask() {
	if (confirm("Bạn chắc chắn muốn xoá công việc này?")) {
		$.ajax({
			url: `${API_BASE_URL}/tasks/${taskId}`,
			method: "DELETE",
			headers: { Authorization: `Bearer ${token}` },
			success: () => window.location.href = "projects.html",
		});
	}
}

function openAssignModal() {
  const projectId = window.projectIdOfTask;

  // Gọi API lấy danh sách user thuộc dự án
  $.ajax({
    url: `${API_BASE_URL}/project-members/project/${projectId}`,
    method: 'GET',
    headers: { Authorization: `Bearer ${token}` },
    success: (members) => {
      const userIdsInProject = members.map(m => m.userId);

      // Gọi API lấy toàn bộ user để match userId
      $.ajax({
        url: `${API_BASE_URL}/users`,
        method: 'GET',
        headers: { Authorization: `Bearer ${token}` },
        success: (allUsers) => {
          $('#userCheckboxList').empty();

          const assignedUserIds = (window.assignedUsers || []).map(u => u.id);

          allUsers
            .filter(u => userIdsInProject.includes(u.id))
            .forEach(u => {
              const isChecked = assignedUserIds.includes(u.id);
              $('#userCheckboxList').append(`
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="${u.id}" id="user-${u.id}" ${isChecked ? 'checked' : ''}>
                  <label class="form-check-label" for="user-${u.id}">${u.fullName} (${u.username})</label>
                </div>
              `);
            });

          const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("assignUserModal"));
          modal.show();
        },
        error: () => alert("Không thể tải danh sách người dùng"),
      });
    },
    error: () => alert("Không thể tải danh sách thành viên dự án"),
  });
}


function saveAssignment(event) {
	event.preventDefault();

	const selectedUserIds = $('#userCheckboxList input[type="checkbox"]:checked')
		.map((_, checkbox) => parseInt(checkbox.value)).get();

	if (selectedUserIds.length === 0) {
		if (!confirm("Không phân công ai cả. Bạn chắc chắn muốn tiếp tục?")) return;
	}

	$.ajax({
		url: `${API_BASE_URL}/tasks/${taskId}/assign`,
		method: 'POST',
		headers: {
			Authorization: `Bearer ${token}`,
			'Content-Type': 'application/json'
		},
		data: JSON.stringify({ userIds: selectedUserIds }),
		success: () => {
			alert('Phân công thành công');
			loadTaskDetail();
			bootstrap.Modal.getInstance(document.getElementById('assignUserModal')).hide();
		},
		error: () => alert('Lỗi khi phân công')
	});
}
