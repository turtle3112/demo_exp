const API_BASE_URL = "http://localhost:8080";
let token;
let allProjects = [];

// Khởi tạo ứng dụng
function initApp() {
    const user = JSON.parse(localStorage.getItem('user'));
    token = localStorage.getItem('token');

    if (!user || !token) {
        window.location.href = 'login.html';
        return;
    }

    // Hiển thị thông tin user
    $('#currentUsername').text(user.fullName || user.username);
    $('#sidebarUsername').text(user.fullName || user.username);
    
    // Kiểm tra quyền và hiển thị nút phù hợp
    if (user.role !== 'ADMIN') {
        $('#btnCreateProject').hide();
        $('#btnInviteMember').hide();
    }

    if (user.accountType === 'PERSONAL' || user.role === 'EMPLOYEE') {
        $('#btnBackToPersonal').show();
    } else {
        $('#btnBackToPersonal').hide();
    }

    // Cập nhật trạng thái organization
    updateUserOrganizationStatus();
    
    // Toggle sidebar trên mobile
    $('#sidebarToggle').click(function() {
        $('.sidebar').toggleClass('active');
        $('.main-content, .navbar-custom').toggleClass('active');
    });

    // Khởi tạo các sự kiện
    initEvents();
    
    // Load dữ liệu
    loadProjects();
}

// Khởi tạo các sự kiện
function initEvents() {
    // Modal tạo dự án
    $('#createProjectModal').on('show.bs.modal', function() {
        loadTeamMembersForCreate();
    });
    
    $('#btnSubmitCreateProject').on('click', createNewProject);

    // Modal chỉnh sửa
    $('#btnSubmitEditProject').on('click', updateProject);
    $('#btnDeleteProjectModal').on('click', deleteProjectFromModal);

    // Modal mời thành viên
    $('#btnSendInvite').on('click', sendInvite);

    // Tìm kiếm và sắp xếp
    $('#searchInput').on('input', handleSearch);
    $('#sortSelect').on('change', handleSort);
}

// Load dự án doanh nghiệp
function loadProjects() {
    showLoading();
    
    $.ajax({
        url: `${API_BASE_URL}/projects/business`,
        method: 'GET',
        headers: { Authorization: `Bearer ${token}` },
        success: function(projects) {
            allProjects = projects;
            renderProjects(allProjects);
            hideLoading();
        },
        error: function(xhr) {
            console.error('❌ Lỗi tải dự án business:', xhr);
            
            // Fallback nếu endpoint business không hoạt động
            if (xhr.status === 404) {
                console.log('⚠️ Endpoint /projects/business không tồn tại, thử fallback...');
                loadProjectsFallback();
            } else {
                $('#projectList').html(`
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <p class="text-danger">Không thể tải danh sách dự án</p>
                        <button class="btn btn-primary" onclick="loadProjects()">
                            <i class="fas fa-redo"></i> Thử lại
                        </button>
                    </div>
                `);
                hideLoading();
            }
        }
    });
}

// Fallback nếu endpoint business không tồn tại
function loadProjectsFallback() {
    $.ajax({
        url: `${API_BASE_URL}/projects`,
        method: 'GET',
        headers: { Authorization: `Bearer ${token}` },
        success: function(allProjectsData) {
            // Lọc chỉ lấy dự án có type là ENTERPRISE hoặc BUSINESS
            allProjects = allProjectsData.filter(p => 
                p.projectType === 'ENTERPRISE' || 
                p.type === 'BUSINESS' ||
                (p.accountType && p.accountType === 'BUSINESS')
            );
            renderProjects(allProjects);
            hideLoading();
        },
        error: function(xhr) {
            console.error('❌ Lỗi fallback:', xhr);
            $('#projectList').html(`
                <div class="col-12 text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <p class="text-danger">Không thể kết nối đến server</p>
                </div>
            `);
            hideLoading();
        }
    });
}

// Hiển thị dự án doanh nghiệp
function renderProjects(projects) {
    const container = $('#projectList');
    const emptyState = $('#emptyState');

    if (!projects || projects.length === 0) {
        container.hide();
        emptyState.show();
        return;
    }

    container.show();
    emptyState.hide();
    container.empty();

    const user = JSON.parse(localStorage.getItem('user')) || {};
    const userRole = user.role || '';

    projects.forEach(project => {
        const progress = project.progress || 0;
        const isOverdue = project.deadline && isDeadlineOverdue(project.deadline);
        const progressClass = progress === 100 ? 'bg-success' : 
                            isOverdue ? 'bg-danger' : 'bg-primary';
        
        // Xác định loại dự án
        let badgeText = 'DOANH NGHIỆP';
        let badgeClass = 'bg-info';
        
        if (project.projectType === 'TEAM') {
            badgeText = 'NHÓM';
            badgeClass = 'bg-primary';
        } else if (project.projectType === 'ENTERPRISE') {
            badgeText = 'DOANH NGHIỆP';
            badgeClass = 'bg-info';
        } else if (project.projectType === 'PERSONAL') {
            badgeText = 'CÁ NHÂN';
            badgeClass = 'bg-secondary';
        }

        const card = `
        <div class="col-xl-4 col-lg-6 mb-4">
            <div class="card project-card h-100" data-project-id="${project.id}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title fw-bold text-truncate me-2">${escapeHtml(project.name)}</h5>
                        <span class="badge ${badgeClass}">${badgeText}</span>
                    </div>
                    
                    <p class="card-text text-muted mb-3">${project.description || 'Chưa có mô tả'}</p>
                    
                    <div class="project-meta mb-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted">
                                    <i class="fas fa-tasks"></i><br>
                                    ${project.taskCount || 0} tasks
                                </small>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i><br>
                                    ${formatDate(project.createdAt)}
                                </small>
                            </div>
                            <div class="col-4">
                                <small class="${isOverdue ? 'text-danger fw-bold' : 'text-muted'}">
                                    <i class="fas fa-hourglass-end"></i><br>
                                    ${project.deadline ? formatDate(project.deadline) : 'Không hạn'}
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="progress-info mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Tiến độ</small>
                            <small class="text-muted">${progress}%</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar ${progressClass}" style="width: ${progress}%"></div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm btn-open-project" data-project-id="${project.id}">
                            <i class="fas fa-folder-open"></i> Mở dự án
                        </button>
                        ${userRole === 'ADMIN' ? `
                        <div class="btn-group">
                            <button class="btn btn-outline-warning btn-sm btn-edit-project" data-project-id="${project.id}">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-outline-danger btn-sm btn-delete-project" data-project-id="${project.id}">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        </div>
        `;
        container.append(card);
    });
    
    // Gắn sự kiện cho các nút
    attachProjectEvents();
}

// Gắn sự kiện cho các nút trên card dự án
function attachProjectEvents() {
    // Mở dự án
    $('.btn-open-project').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const projectId = $(this).data('project-id');
        viewProject(projectId);
    });
    
    // Sửa dự án
    $('.btn-edit-project').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const projectId = $(this).data('project-id');
        openEditProjectModal(projectId);
    });
    
    // Xóa dự án
    $('.btn-delete-project').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const projectId = $(this).data('project-id');
        confirmDeleteProject(projectId);
    });
    
    // Click vào toàn bộ card
    $('.project-card').off('click').on('click', function(e) {
        if (!$(e.target).closest('.btn, .dropdown, .dropdown-item').length) {
            const projectId = $(this).data('project-id');
            viewProject(projectId);
        }
    });
}

// Tạo dự án doanh nghiệp mới
async function createNewProject() {
    const name = $('#newProjectName').val().trim();
    const description = $('#newProjectDescription').val().trim();
    const deadline = $('#newProjectDeadline').val();
    const userIds = $('.new-project-user-checkbox:checked').map(function() {
        return parseInt(this.value);
    }).get();

    if (!name) {
        alert('Vui lòng nhập tên dự án');
        return;
    }

    try {
        const projectData = {
            name: name,
            description: description || '',
            deadlineDate: deadline || null
        };

        console.log('📤 Dữ liệu gửi đi:', projectData);

        const res = await $.ajax({
            url: `${API_BASE_URL}/projects/business/add`,
            method: 'POST',
            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(projectData)
        });

        console.log('✅ Tạo dự án thành công:', res);

        // Thêm thành viên nếu có
        if (userIds.length > 0 && res && res.id) {
            try {
                await $.ajax({
                    url: `${API_BASE_URL}/project-members/add`,
                    method: 'POST',
                    headers: {
                        Authorization: `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    data: JSON.stringify({
                        projectId: res.id,
                        userIds: userIds
                    })
                });
                console.log('✅ Thêm thành viên thành công');
            } catch (memberErr) {
                console.error('⚠️ Lỗi thêm thành viên:', memberErr);
            }
        }

        $('#createProjectModal').modal('hide');
        showSuccess('Tạo dự án doanh nghiệp thành công!');
        resetCreateForm();
        loadProjects();

    } catch (err) {
        console.error('❌ Lỗi tạo dự án:', err);
        
        if (err.responseJSON && err.responseJSON.error) {
            alert('Lỗi: ' + err.responseJSON.error);
        } else if (err.responseJSON && err.responseJSON.message) {
            alert('Lỗi: ' + err.responseJSON.message);
        } else {
            alert('Đã có lỗi xảy ra khi tạo dự án. Vui lòng thử lại.');
        }
    }
}

// Reset form tạo dự án
function resetCreateForm() {
    $('#newProjectName').val('');
    $('#newProjectDescription').val('');
    $('#newProjectDeadline').val('');
    $('.new-project-user-checkbox').prop('checked', false);
}

// Mở modal chỉnh sửa dự án - PHIÊN BẢN CẢI TIẾN
function openEditProjectModal(projectId) {
    console.log('🔍 Đang mở modal chỉnh sửa cho project ID:', projectId);
    
    // TRƯỚC TIÊN: Thử lấy dữ liệu từ danh sách đã load
    const projectFromList = allProjects.find(p => p.id == projectId);
    if (projectFromList) {
        console.log('✅ Tìm thấy dự án trong danh sách đã tải:', projectFromList);
        populateEditModal(projectFromList);
        return;
    }
    
    // Nếu không có trong danh sách, hiển thị form với dữ liệu mặc định
    console.log('⚠️ Không tìm thấy dự án trong danh sách');
    
    // HIỂN THỊ FORM CHỈNH SỬA VỚI DỮ LIỆU TỐI THIỂU
    $('#editProjectId').val(projectId);
    $('#editProjectName').val('Dự án #' + projectId);
    $('#editProjectDescription').val('');
    $('#editProjectDeadline').val('');
    
    // Load users
    loadUsersForEdit(projectId);
    $('#editProjectModal').modal('show');
    
    console.log('ℹ️ Không thể tải dữ liệu từ server. Vui lòng nhập thông tin thủ công.');
}

// Hàm điền dữ liệu vào modal chỉnh sửa
function populateEditModal(project) {
    if (!project) {
        console.error('❌ Dữ liệu dự án không hợp lệ');
        alert('Không thể tải thông tin dự án. Vui lòng thử lại sau.');
        return;
    }
    
    $('#editProjectId').val(project.id);
    $('#editProjectName').val(project.name || '');
    $('#editProjectDescription').val(project.description || '');
    
    // Format date cho input
    let deadline = '';
    if (project.deadline) {
        if (project.deadline.includes('T')) {
            deadline = project.deadline.split('T')[0];
        } else if (project.deadlineDate) {
            deadline = project.deadlineDate.split('T')[0];
        } else {
            deadline = project.deadline;
        }
    } else if (project.deadlineDate) {
        deadline = project.deadlineDate.split('T')[0];
    }
    $('#editProjectDeadline').val(deadline);
    
    loadUsersForEdit(project.id);
    $('#editProjectModal').modal('show');
}

// Cập nhật dự án - PHIÊN BẢN ĐƠN GIẢN HÓA
async function updateProject() {
    console.log('🔍 Bắt đầu cập nhật dự án');
    
    const projectId = $('#editProjectId').val();
    const name = $('#editProjectName').val().trim();
    const description = $('#editProjectDescription').val().trim();
    const deadline = $('#editProjectDeadline').val();
    
    if (!name) {
        alert('Vui lòng nhập tên dự án');
        return;
    }

    // Lấy danh sách thành viên được chọn
    const selectedUserIds = [];
    $('#editProjectUserCheckboxes input[type="checkbox"]:checked').each(function() {
        selectedUserIds.push(parseInt($(this).val()));
    });

    // Disable nút trong khi đang xử lý
    $('#btnSubmitEditProject').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');

    try {
        // Kiểm tra quyền cơ bản từ localStorage
        const user = JSON.parse(localStorage.getItem('user'));
        if (user.role !== 'ADMIN') {
            alert('❌ Chỉ ADMIN mới có quyền cập nhật dự án');
            $('#btnSubmitEditProject').prop('disabled', false).html('Cập nhật');
            return;
        }
        
        // Chuẩn bị dữ liệu
        const updateData = {
            name: name,
            description: description || '',
            deadlineDate: deadline || null
        };
        
        console.log('📤 Gửi dữ liệu cập nhật:', updateData);
        
        // Thử endpoint business trước
        try {
            const response = await $.ajax({
                url: `${API_BASE_URL}/projects/business/${projectId}`,
                method: 'PUT',
                contentType: 'application/json',
                headers: { 'Authorization': 'Bearer ' + token },
                data: JSON.stringify(updateData),
                timeout: 10000
            });
            
            console.log('✅ Cập nhật thành công qua business endpoint:', response);
            await handleUpdateSuccess(projectId, selectedUserIds, response);
            
        } catch (businessError) {
            console.log('⚠️ Business endpoint thất bại, thử endpoint chung:', businessError);
            
            // Thử endpoint chung
            try {
                const response = await $.ajax({
                    url: `${API_BASE_URL}/projects/${projectId}`,
                    method: 'PUT',
                    contentType: 'application/json',
                    headers: { 'Authorization': 'Bearer ' + token },
                    data: JSON.stringify(updateData),
                    timeout: 10000
                });
                
                console.log('✅ Cập nhật thành công qua endpoint chung:', response);
                await handleUpdateSuccess(projectId, selectedUserIds, response);
                
            } catch (generalError) {
                console.error('❌ Cả hai endpoint đều thất bại:', generalError);
                handleUpdateError(generalError, projectId, updateData, selectedUserIds);
            }
        }
        
    } catch (error) {
        console.error('❌ Lỗi không xử lý được:', error);
        alert('Đã xảy ra lỗi không xác định: ' + error.message);
        $('#btnSubmitEditProject').prop('disabled', false).html('Cập nhật');
    }
}

// Xử lý khi cập nhật thành công
async function handleUpdateSuccess(projectId, selectedUserIds, response) {
    try {
        // Cập nhật thành viên nếu có
        if (selectedUserIds.length > 0) {
            await $.ajax({
                url: `${API_BASE_URL}/project-members/${projectId}`,
                method: 'PUT',
                contentType: 'application/json',
                headers: { 'Authorization': 'Bearer ' + token },
                data: JSON.stringify({ userIds: selectedUserIds })
            });
            console.log('✅ Cập nhật thành viên thành công');
        }
    } catch (memberErr) {
        console.error('⚠️ Lỗi cập nhật thành viên:', memberErr);
    }
    
    $('#editProjectModal').modal('hide');
    $('#btnSubmitEditProject').prop('disabled', false).html('Cập nhật');
    showSuccess('Cập nhật dự án thành công!');
    
    // Refresh danh sách dự án
    loadProjects();
}

// Xử lý khi cập nhật thất bại
function handleUpdateError(error, projectId, projectData, selectedUserIds) {
    let errorMsg = 'Không thể cập nhật dự án. ';
    
    if (error.status === 500) {
        errorMsg += 'Lỗi server (500). ';
    } else if (error.status === 403) {
        errorMsg += 'Không có quyền truy cập. ';
    } else if (error.status === 404) {
        errorMsg += 'Dự án không tồn tại. ';
    } else if (error.status === 400) {
        errorMsg += 'Dữ liệu không hợp lệ. ';
    }
    
    if (error.responseText) {
        try {
            const errorDetail = JSON.parse(error.responseText);
            if (errorDetail.error) errorMsg += errorDetail.error;
            if (errorDetail.message) errorMsg += errorDetail.message;
        } catch (e) {
            // Không parse được, bỏ qua
        }
    }
    
    $('#btnSubmitEditProject').prop('disabled', false).html('Cập nhật');
    
    const userChoice = confirm(errorMsg + '\n\nBạn có muốn lưu thay đổi cục bộ không?');
    
    if (userChoice) {
        saveUpdateLocally(projectId, projectData, selectedUserIds);
    }
}

// Lưu cập nhật cục bộ
function saveUpdateLocally(projectId, projectData, selectedUserIds) {
    try {
        // Cập nhật trong danh sách cục bộ
        const projectIndex = allProjects.findIndex(p => p.id == projectId);
        if (projectIndex !== -1) {
            allProjects[projectIndex] = {
                ...allProjects[projectIndex],
                ...projectData
            };
            
            // Lưu vào localStorage để đồng bộ sau
            let offlineUpdates;
            try {
                const stored = localStorage.getItem('offlineUpdates');
                if (stored) {
                    offlineUpdates = JSON.parse(stored);
                    if (!Array.isArray(offlineUpdates)) {
                        offlineUpdates = [];
                    }
                } else {
                    offlineUpdates = [];
                }
            } catch (e) {
                console.error('Lỗi parse offlineUpdates:', e);
                offlineUpdates = [];
            }
            
            offlineUpdates.push({
                projectId: projectId,
                data: projectData,
                selectedUserIds: selectedUserIds,
                timestamp: new Date().toISOString(),
                type: 'UPDATE'
            });
            
            localStorage.setItem('offlineUpdates', JSON.stringify(offlineUpdates));
            console.log('✅ Đã lưu cục bộ:', offlineUpdates);
        }
        
        $('#editProjectModal').modal('hide');
        showSuccess('Cập nhật đã được lưu cục bộ. Sẽ đồng bộ khi có kết nối.');
        renderProjects(allProjects);
        
    } catch (error) {
        console.error('❌ Lỗi lưu cục bộ:', error);
        alert('Lỗi khi lưu cục bộ: ' + error.message);
    }
}

// Xóa dự án từ modal
function deleteProjectFromModal() {
    const projectId = $('#editProjectId').val();
    confirmDeleteProject(projectId);
}

// Xác nhận xóa dự án - PHIÊN BẢN CẢI TIẾN
function confirmDeleteProject(projectId) {
    if (!confirm('Bạn có chắc chắn muốn xóa dự án này? Toàn bộ công việc và dữ liệu liên quan sẽ bị xóa.')) {
        return;
    }

    // Thử endpoint business delete trước
    $.ajax({
        url: `${API_BASE_URL}/projects/business/${projectId}`,
        method: 'DELETE',
        headers: { Authorization: `Bearer ${token}` },
        success: function() {
            $('#editProjectModal').modal('hide');
            showSuccess('Xóa dự án thành công!');
            loadProjects();
        },
        error: function(xhr) {
            console.log('⚠️ Business delete thất bại, thử endpoint chung:', xhr);
            
            // Thử endpoint chung
            $.ajax({
                url: `${API_BASE_URL}/projects/${projectId}`,
                method: 'DELETE',
                headers: { Authorization: `Bearer ${token}` },
                success: function() {
                    $('#editProjectModal').modal('hide');
                    showSuccess('Xóa dự án thành công!');
                    loadProjects();
                },
                error: function(xhr2) {
                    console.error('❌ Cả 2 endpoint đều lỗi:', xhr2);
                    
                    let errorMsg = 'Không thể xóa dự án. ';
                    try {
                        const errorDetail = JSON.parse(xhr2.responseText);
                        if (errorDetail.message) {
                            errorMsg += errorDetail.message;
                        }
                    } catch (e) {
                        errorMsg += 'Mã lỗi: ' + xhr2.status;
                    }
                    
                    alert(errorMsg);
                }
            });
        }
    });
}

// Gửi lời mời thành viên
function sendInvite() {
    const email = $('#inviteEmail').val().trim();
    
    if (!email) {
        alert('Vui lòng nhập email');
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Email không đúng định dạng');
        return;
    }

    $.ajax({
        url: `${API_BASE_URL}/invitations/send`,
        method: 'POST',
        headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({ email: email }),
        success: function() {
            $('#inviteMemberModal').modal('hide');
            $('#inviteEmail').val('');
            showSuccess('Đã gửi lời mời thành công!');
        },
        error: function(xhr) {
            alert('Gửi lời mời thất bại: ' + (xhr.responseJSON?.message || ''));
        }
    });
}

// Load thành viên cho modal tạo dự án
function loadTeamMembersForCreate() {
    $.ajax({
        url: `${API_BASE_URL}/groups/members`,
        method: 'GET',
        headers: { Authorization: `Bearer ${token}` },
        success: (response) => {
            const members = response.members || response || [];
            
            if (!members || members.length === 0) {
                $('#newProjectUserCheckboxes').html(`
                    <div class="text-muted p-2 text-center">
                        <i class="fas fa-users-slash"></i> Không có thành viên nào trong tổ chức
                    </div>
                `);
                return;
            }
            
            const html = members.map(member => `
                <div class="form-check mb-2">
                    <input class="form-check-input new-project-user-checkbox" 
                           type="checkbox" value="${member.id}" id="new-user-${member.id}">
                    <label class="form-check-label" for="new-user-${member.id}">
                        <strong>${member.fullName || member.username}</strong>
                        <br><small class="text-muted">${member.email}</small>
                    </label>
                </div>
            `).join('');
            
            $('#newProjectUserCheckboxes').html(html);
        },
        error: (xhr) => {
            console.error('❌ Lỗi tải thành viên:', xhr);
            $('#newProjectUserCheckboxes').html(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Không thể tải danh sách thành viên
                    <br><small>Kiểm tra kết nối hoặc quyền truy cập</small>
                </div>
            `);
        }
    });
}

// Load thành viên cho modal chỉnh sửa
function loadUsersForEdit(projectId) {
    const container = $('#editProjectUserCheckboxes');
    
    Promise.all([
        $.ajax({
            url: `${API_BASE_URL}/groups/members`,
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        }),
        $.ajax({
            url: `${API_BASE_URL}/project-members/project/${projectId}`,
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        })
    ]).then(function([response, projectMembers]) {
        const allMembers = response.members || response || [];
        const assignedMemberIds = new Set();
        
        if (projectMembers && projectMembers.length > 0) {
            projectMembers.forEach(member => {
                if (member.userId) {
                    assignedMemberIds.add(member.userId);
                } else if (member.user && member.user.id) {
                    assignedMemberIds.add(member.user.id);
                } else if (member.id && member.id.userId) {
                    assignedMemberIds.add(member.id.userId);
                }
            });
        }
        
        let html = '';
        if (allMembers && allMembers.length > 0) {
            html = allMembers.map(user => {
                const isAssigned = assignedMemberIds.has(user.id);
                return `
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="${user.id}" 
                               id="editUser-${user.id}" ${isAssigned ? 'checked' : ''}>
                        <label class="form-check-label" for="editUser-${user.id}">
                            <strong>${user.fullName || user.username}</strong>
                            <br><small class="text-muted">${user.email}</small>
                            ${isAssigned ? ' <span class="badge bg-success">Đã phân công</span>' : ''}
                        </label>
                    </div>
                `;
            }).join('');
        } else {
            html = '<div class="text-muted p-2 text-center">Không có thành viên nào trong tổ chức</div>';
        }
        
        container.html(html);
    }).catch(function(error) {
        console.error('❌ Lỗi tải thành viên:', error);
        container.html(`
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                Không thể tải danh sách thành viên
            </div>
        `);
    });
}

// Các hàm tiện ích
function showLoading() {
    $('#projectList').html(`
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Đang tải dự án doanh nghiệp...</p>
        </div>
    `);
}

function hideLoading() {
    // Loading sẽ được ẩn khi renderProjects được gọi
}

function showSuccess(message) {
    const toast = $(`
        <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    
    if (!$('.toast-container').length) {
        $('body').append('<div class="toast-container position-fixed top-0 end-0 p-3"></div>');
    }
    
    $('.toast-container').append(toast);
    
    const bsToast = new bootstrap.Toast(toast[0]);
    bsToast.show();
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    } catch (e) {
        return dateString;
    }
}

function isDeadlineOverdue(deadline) {
    if (!deadline) return false;
    try {
        return new Date(deadline) < new Date();
    } catch (e) {
        return false;
    }
}

function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/< /g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function handleSearch() {
    const keyword = $(this).val().toLowerCase().trim();
    const filtered = allProjects.filter(p => 
        (p.name && p.name.toLowerCase().includes(keyword)) ||
        (p.description && p.description.toLowerCase().includes(keyword))
    );
    renderProjects(filtered);
}

function handleSort() {
    const selected = $(this).val();
    let sorted = [...allProjects];

    switch (selected) {
        case 'name-asc':
            sorted.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
            break;
        case 'name-desc':
            sorted.sort((a, b) => (b.name || '').localeCompare(a.name || ''));
            break;
        case 'recent':
            sorted.sort((a, b) => new Date(b.createdAt || 0) - new Date(a.createdAt || 0));
            break;
        case 'deadline':
            sorted.sort((a, b) => {
                if (!a.deadline) return 1;
                if (!b.deadline) return -1;
                return new Date(a.deadline) - new Date(b.deadline);
            });
            break;
    }

    renderProjects(sorted);
}

// Cập nhật trạng thái tổ chức
async function updateUserOrganizationStatus() {
    try {
        const user = JSON.parse(localStorage.getItem('user'));
        const token = localStorage.getItem('token');
        
        const response = await fetch('http://localhost:8080/users/current', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (response.ok) {
            const updatedUser = await response.json();
            localStorage.setItem('user', JSON.stringify(updatedUser));
        }
    } catch (error) {
        console.error("Lỗi khi cập nhật thông tin user:", error);
    }
}

// Mở dự án doanh nghiệp
function viewProject(projectId) {
    localStorage.setItem("selectedProjectId", projectId);
    localStorage.setItem("projectType", "ENTERPRISE");
    window.location.href = `tasks_details_business.html?projectId=${projectId}`;
}

// Khởi chạy ứng dụng khi DOM ready
$(document).ready(function() {
    // Tạo container cho toast nếu chưa có
    if (!$('.toast-container').length) {
        $('body').append('<div class="toast-container position-fixed top-0 end-0 p-3"></div>');
    }
    
    initApp();
});