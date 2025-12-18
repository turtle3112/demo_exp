const API_BASE_URL = "http://localhost:8080";
let token;
let allProjects = [];
let groupMemberInfo = { totalMembers: 0, maxMembers: 10 };
let groupProjectInfo = { totalProjects: 0, maxProjects: 7 };

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

    // CHỈ ADMIN ĐƯỢC TẠO DỰ ÁN NHÓM
    if (user.role !== 'ADMIN') {
        $('#btnCreateProject').hide();
        $('#btnInviteMember').hide();
    } else {
        // Cập nhật trạng thái nút cho ADMIN
        updateInviteButtonState();
    }

    // Hiển thị nút quay lại trang cá nhân cho PERSONAL users
    if (user.accountType === 'PERSONAL' || user.role === 'EMPLOYEE') {
        $('#btnBackToPersonal').show();
    } else {
        $('#btnBackToPersonal').hide();
    }

    // CHỈ ẩn menu thiết lập cho PERSONAL users
    if (user.accountType === 'PERSONAL') {
        $('.settings-menu-item').hide();
    }

    // Cập nhật trạng thái organization
    updateUserOrganizationStatus();
    
    // Gọi hàm xử lý hiển thị nút đúng cách
    handleFirstProjectButtonVisibility();
    
    // Khởi tạo các sự kiện
    initEvents();
    
    // Load dự án và thông tin thành viên
    loadProjects();
    loadGroupMemberInfo();
}

async function handleFirstProjectButtonVisibility() {
    try {
        const user = JSON.parse(localStorage.getItem('user'));
        
        // 🚨 KIỂM TRA 1: Có dự án không?
        const hasProjects = allProjects && allProjects.length > 0;
        
        // 🚨 KIỂM TRA 2: User có quyền tạo dự án không?
        const canCreateProjects = user.role === 'ADMIN';
        
        // 🚨 KIỂM TRA 3: Đã đạt giới hạn 7 dự án chưa?
        const reachedProjectLimit = groupProjectInfo.totalProjects >= groupProjectInfo.maxProjects;
        
        console.log('🔍 Kiểm tra hiển thị nút:', {
            hasProjects: hasProjects,
            canCreateProjects: canCreateProjects,
            userRole: user.role,
            accountType: user.accountType,
            totalProjects: groupProjectInfo.totalProjects,
            maxProjects: groupProjectInfo.maxProjects,
            reachedProjectLimit: reachedProjectLimit
        });
        
        if (!hasProjects && canCreateProjects && !reachedProjectLimit) {
            // 🚨 CHỈ hiển thị nút khi: CHƯA có dự án VÀ CÓ quyền tạo dự án VÀ CHƯA đạt giới hạn
            $('.create-first-project-btn').show();
            $('#emptyState .btn-primary').show();
            console.log('✅ HIỂN THỊ nút "Tạo dự án đầu tiên"');
        } else {
            // Ẩn nút trong các trường hợp khác
            $('.create-first-project-btn').hide();
            $('#emptyState .btn-primary').hide();
            console.log('✅ Ẩn nút "Tạo dự án đầu tiên"');
        }
        
    } catch (error) {
        console.error('Lỗi khi xử lý hiển thị nút:', error);
        // Trong trường hợp lỗi, ẩn nút để tránh hiển thị sai
        $('.create-first-project-btn').hide();
    }
}

// Khởi tạo các sự kiện
function initEvents() {
    // Modal tạo dự án
    $('#createProjectModal').on('show.bs.modal', function() {
        loadTeamMembersForCreate();
        updateCreateProjectButtonState();
    }).on('hidden.bs.modal', function() {
        // Reset form khi đóng modal
        $('#newProjectName').val('');
        $('#newProjectDescription').val('');
        $('#newProjectDeadline').val('');
        $('#newProjectUserCheckboxes').empty();
        // Xóa cảnh báo nếu có
        $('#createProjectModal .alert-danger').remove();
        $('#createProjectModal .modal-title').html('Tạo dự án nhóm mới');
        $('#btnSubmitCreateProject').prop('disabled', false).html('Tạo dự án');
    });
    
    $('#btnSubmitCreateProject').on('click', createNewProject);

    // Modal chỉnh sửa
    $('#editProjectModal').on('hidden.bs.modal', function() {
        // Reset nút cập nhật khi đóng modal
        $('#btnSubmitEditProject').prop('disabled', false).html('Cập nhật');
    });
    
    $('#btnSubmitEditProject').on('click', updateProject);
    $('#btnDeleteProjectModal').on('click', deleteProjectFromModal);

    // Modal mời thành viên - THÊM: load dự án khi modal mở
    $('#inviteMemberModal').on('show.bs.modal', function() {
        loadProjectsForInvite();
        loadGroupMemberInfo(); // Load thông tin thành viên mới nhất
    });

    $('#btnSendInvite').on('click', sendInvite);

    // Tìm kiếm và sắp xếp
    $('#searchInput').on('input', handleSearch);
    $('#sortSelect').on('change', handleSort);
}

// Load dự án
function loadProjects() {
    showLoading();
    
    console.log('🔧 DEBUG - Đang tải danh sách dự án...');
    
    $.ajax({
        url: `${API_BASE_URL}/projects/groups`,
        method: 'GET',
        headers: { Authorization: `Bearer ${token}` },
        success: function(projects) {
            console.log('✅ DEBUG - Số lượng dự án nhận được:', projects.length);
            
            allProjects = projects;
            
            // Cập nhật thông tin số lượng dự án
            groupProjectInfo.totalProjects = projects.length;
            groupProjectInfo.maxProjects = 7;
            
            renderProjects(allProjects);
            
            // Cập nhật hiển thị nút sau khi load dự án
            handleFirstProjectButtonVisibility();
            hideLoading();
        },
        error: function(xhr) {
            console.error('❌ DEBUG - Lỗi tải dự án:', {
                status: xhr.status,
                responseText: xhr.responseText
            });
            
            hideLoading();
            showToastError('Không thể tải danh sách dự án: ' + (xhr.responseJSON?.message || xhr.statusText));
        }
    });
}

// Tải thông tin thành viên nhóm (SỬA: Tải chính xác thông tin)
function loadGroupMemberInfo() {
    const user = JSON.parse(localStorage.getItem('user'));
    if (user.role !== 'ADMIN') return;
    
    console.log('👥 Đang tải thông tin thành viên nhóm...');
    
    $.ajax({
        url: `${API_BASE_URL}/groups/members`,
        method: 'GET',
        headers: { Authorization: `Bearer ${token}` },
        success: function(response) {
            console.log('✅ Thông tin thành viên:', response);
            
            // 🚨 SỬA: Xử lý response theo cả 2 định dạng
            if (response.totalMembers !== undefined) {
                // Định dạng mới: response là object với các thuộc tính
                groupMemberInfo.totalMembers = response.totalMembers;
                groupMemberInfo.maxMembers = response.maxMembers || 10;
            } else if (Array.isArray(response)) {
                // Định dạng cũ: response là mảng các thành viên
                groupMemberInfo.totalMembers = response.length;
                groupMemberInfo.maxMembers = 10;
            }
            
            console.log(`📊 Thống kê thành viên: ${groupMemberInfo.totalMembers}/${groupMemberInfo.maxMembers}`);
            
            // Cập nhật UI nếu cần
            updateInviteButtonState();
        },
        error: function(xhr) {
            console.error('❌ Lỗi tải thông tin thành viên:', xhr);
        }
    });
}

// Cập nhật trạng thái nút mời thành viên
function updateInviteButtonState() {
    const user = JSON.parse(localStorage.getItem('user'));
    
    if (user.role === 'ADMIN') {
        if (groupMemberInfo.totalMembers >= groupMemberInfo.maxMembers) {
            // Đã đạt giới hạn thành viên
            $('#btnInviteMember').prop('disabled', true);
            $('#btnInviteMember').attr('title', `Đã đạt tối đa ${groupMemberInfo.maxMembers} thành viên`);
            $('#btnInviteMember').html('<i class="fas fa-user-slash"></i> Mời thành viên (Đã đầy)');
        } else {
            $('#btnInviteMember').prop('disabled', false);
            $('#btnInviteMember').removeAttr('title');
            $('#btnInviteMember').html('<i class="fas fa-user-plus"></i> Mời thành viên');
        }
    }
}

// Cập nhật trạng thái nút tạo dự án
function updateCreateProjectButtonState() {
    const user = JSON.parse(localStorage.getItem('user'));
    
    if (user.role === 'ADMIN') {
        if (groupProjectInfo.totalProjects >= groupProjectInfo.maxProjects) {
            // Đã đạt giới hạn dự án
            $('#btnSubmitCreateProject').prop('disabled', true);
            $('#btnSubmitCreateProject').html(`<i class="fas fa-ban"></i> Đã đạt giới hạn (${groupProjectInfo.maxProjects} dự án)`);
            $('#createProjectModal .modal-title').html('<span class="text-danger">Đã đạt giới hạn dự án</span>');
            
            // Hiển thị cảnh báo trong modal
            $('#createProjectModal .modal-body').prepend(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Đã đạt giới hạn:</strong> Nhóm đã có ${groupProjectInfo.totalProjects}/${groupProjectInfo.maxProjects} dự án.
                    Vui lòng xóa bớt dự án để tạo mới.
                </div>
            `);
        } else {
            $('#btnSubmitCreateProject').prop('disabled', false);
            $('#btnSubmitCreateProject').html('Tạo dự án');
            $('#createProjectModal .modal-title').html('Tạo dự án nhóm mới');
        }
    }
}

// Hiển thị dự án
function renderProjects(projects) {
    const container = $('#projectList');
    const emptyState = $('#emptyState');

    if (!projects.length) {
        container.hide();
        emptyState.show();
        
        // Hiển thị thông tin giới hạn cho admin
        const user = JSON.parse(localStorage.getItem('user'));
        if (user.role === 'ADMIN') {
            $('#emptyState .limit-info').remove();
            $('#emptyState .card-body').append(`
                <div class="limit-info mt-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Giới hạn nhóm:</strong> Tối đa ${groupProjectInfo.maxProjects} dự án và ${groupMemberInfo.maxMembers} thành viên.
                        <div class="mt-1 small">Hiện tại: ${groupProjectInfo.totalProjects}/${groupProjectInfo.maxProjects} dự án, ${groupMemberInfo.totalMembers}/${groupMemberInfo.maxMembers} thành viên</div>
                    </div>
                </div>
            `);
        }
        return;
    }

    container.show();
    emptyState.hide();
    container.empty();

    const user = JSON.parse(localStorage.getItem('user'));
    const userRole = user.role || '';

    // Thêm thông tin giới hạn trên đầu danh sách dự án (chỉ cho admin)
    if (user.role === 'ADMIN') {
        container.append(`
            <div class="col-12 mb-3">
                <div class="alert ${groupProjectInfo.totalProjects >= groupProjectInfo.maxProjects ? 'alert-danger' : 'alert-info'}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-chart-bar me-2"></i>
                            <strong>Giới hạn nhóm:</strong> 
                            <span class="badge ${groupProjectInfo.totalProjects >= groupProjectInfo.maxProjects ? 'bg-danger' : 'bg-primary'} ms-2">
                                ${groupProjectInfo.totalProjects}/${groupProjectInfo.maxProjects} dự án
                            </span>
                            <span class="badge ${groupMemberInfo.totalMembers >= groupMemberInfo.maxMembers ? 'bg-danger' : 'bg-success'} ms-2">
                                ${groupMemberInfo.totalMembers}/${groupMemberInfo.maxMembers} thành viên
                            </span>
                        </div>
                        <div class="small">
                            ${groupProjectInfo.totalProjects >= groupProjectInfo.maxProjects ? 
                                '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Đã đạt giới hạn dự án</span>' : 
                                `<span class="text-success">Còn ${groupProjectInfo.maxProjects - groupProjectInfo.totalProjects} dự án có thể tạo</span>`}
                            ${groupMemberInfo.totalMembers >= groupMemberInfo.maxMembers ? 
                                '<span class="text-danger ms-3"><i class="fas fa-exclamation-triangle"></i> Đã đạt giới hạn thành viên</span>' : 
                                `<span class="text-success ms-3">Còn ${groupMemberInfo.maxMembers - groupMemberInfo.totalMembers} thành viên có thể mời</span>`}
                        </div>
                    </div>
                </div>
            </div>
        `);
    }

    projects.forEach(project => {
        const progress = project.progress || 0;
        const isOverdue = project.deadline && isDeadlineOverdue(project.deadline);

        // LẤY TRẠNG THÁI DỰ ÁN
        const status = getStatusBadge(project);

        const card = `
        <div class="col-xl-4 col-lg-6 mb-4">
            <div class="card project-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title fw-bold text-truncate me-2">${escapeHtml(project.name)}</h5>
                        <div class="d-flex flex-column align-items-end">
                            <span class="badge bg-primary mb-1">NHÓM</span>
                            <span class="badge ${status.class}">${status.text}</span>
                        </div>
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
                    
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="viewProject(${project.id})">
                            <i class="fas fa-folder-open"></i> Mở dự án
                        </button>
                        ${userRole === 'ADMIN' ? `
                        <div class="btn-group">
                            <button class="btn btn-outline-warning btn-sm" onclick="openEditProjectModal(${project.id})">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="confirmDeleteProject(${project.id})">
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
}

// Tạo dự án mới với kiểm tra giới hạn
async function createNewProject() {
    const name = $('#newProjectName').val().trim();
    const description = $('#newProjectDescription').val().trim();
    const deadline = $('#newProjectDeadline').val();
    const userIds = $('.new-project-user-checkbox:checked').map(function() {
        return parseInt(this.value);
    }).get();

    if (!name) {
        showToastError('Vui lòng nhập tên dự án');
        return;
    }

    // 🚨 KIỂM TRA GIỚI HẠN 7 DỰ ÁN
    if (groupProjectInfo.totalProjects >= groupProjectInfo.maxProjects) {
        showToastError(`Nhóm đã đạt tối đa ${groupProjectInfo.maxProjects} dự án. Không thể tạo thêm.`);
        return;
    }

    try {
        const projectData = {
            name: name,
            description: description,
            deadlineDate: deadline || null,
            projectType: 'TEAM'
        };

        const res = await $.ajax({
            url: `${API_BASE_URL}/projects/groups/add`,
            method: 'POST',
            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(projectData)
        });

        // Thêm thành viên nếu có
        if (userIds.length > 0) {
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
            } catch (memberErr) {
                console.error('Lỗi thêm thành viên:', memberErr);
            }
        }

        $('#createProjectModal').modal('hide');
        showSuccess('Tạo dự án thành công!');
        
        // Cập nhật số lượng dự án
        groupProjectInfo.totalProjects++;
        
        // Load lại dữ liệu
        loadProjects();
        loadGroupMemberInfo();

    } catch (err) {
        console.error('Lỗi tạo dự án:', err);
        
        // Kiểm tra nếu lỗi là do giới hạn dự án
        if (err.responseJSON && err.responseJSON.error && 
            err.responseJSON.error.includes('tối đa') && err.responseJSON.error.includes('dự án')) {
            showToastError(err.responseJSON.error);
            groupProjectInfo.totalProjects = groupProjectInfo.maxProjects; // Cập nhật số lượng
            handleFirstProjectButtonVisibility(); // Cập nhật UI
            updateCreateProjectButtonState();
        } else {
            showToastError('Đã có lỗi xảy ra khi tạo dự án: ' + (err.responseJSON?.message || err.statusText));
        }
    }
}

// Mở modal chỉnh sửa
function openEditProjectModal(projectId) {
    console.log('🔧 DEBUG - Mở modal chỉnh sửa project ID:', projectId);
    
    $.ajax({
        url: `${API_BASE_URL}/groups/projects/${projectId}`,
        method: 'GET',
        headers: { 'Authorization': 'Bearer ' + token },
        success: function(project) {
            console.log('✅ DEBUG - Dữ liệu project nhận được:', project);
            
            $('#editProjectId').val(project.id);
            $('#editProjectName').val(project.name);
            $('#editProjectDescription').val(project.description || '');
            
            // Xử lý ngày tháng: chuyển định dạng ISO sang YYYY-MM-DD cho input date
            let deadline = '';
            if (project.deadline) {
                const date = new Date(project.deadline);
                // Format thành YYYY-MM-DD cho input type="date"
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                deadline = `${year}-${month}-${day}`;
                console.log('📅 DEBUG - Deadline từ server:', project.deadline, '->', deadline);
            }
            $('#editProjectDeadline').val(deadline);
            
            loadUsersForEdit(project.id);
            $('#editProjectModal').modal('show');
        },
        error: function(xhr) {
            console.error('❌ DEBUG - Lỗi khi tải thông tin dự án:', {
                status: xhr.status,
                responseText: xhr.responseText
            });
            
            let errorMsg = 'Lỗi khi tải thông tin dự án';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            
            showToastError(errorMsg);
        }
    });
}

// Cập nhật dự án - ĐÃ SỬA LỖI ĐỊNH DẠNG NGÀY THÁNG
function updateProject() {
    const projectId = $('#editProjectId').val();
    
    console.log('🔧 DEBUG - Bắt đầu cập nhật dự án ID:', projectId);
    
    // Xử lý ngày tháng đúng cách - ĐÃ SỬA LỖI
    const deadlineInput = $('#editProjectDeadline').val();
    let deadlineDate = null;
    
    if (deadlineInput && deadlineInput.trim() !== '') {
        const date = new Date(deadlineInput);
        if (!isNaN(date.getTime())) {
            // Đặt giờ là 23:59:59
            date.setHours(23, 59, 59, 0);
            
            // Format thành YYYY-MM-DDTHH:mm:ss (đúng định dạng backend yêu cầu)
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            
            deadlineDate = `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
            console.log('📅 DEBUG - Deadline gửi đi (định dạng backend):', deadlineDate);
        } else {
            console.warn('⚠️ WARNING - Ngày không hợp lệ:', deadlineInput);
        }
    } else {
        console.log('📅 DEBUG - Không có deadline, sẽ xóa deadline');
        // Nếu muốn xóa deadline, gửi chuỗi rỗng hoặc "null"
        deadlineDate = "";
    }
    
    // Chỉ gửi một trường deadline
    const projectData = {
        name: $('#editProjectName').val().trim(),
        description: $('#editProjectDescription').val().trim(),
        deadline: deadlineDate  // Gửi trường deadline với định dạng đúng
    };

    console.log('📤 DEBUG - Dữ liệu gửi đi:', JSON.stringify(projectData, null, 2));

    if (!projectData.name) {
        showToastError('Vui lòng nhập tên dự án');
        return;
    }

    // Lấy danh sách thành viên được chọn
    const selectedUserIds = [];
    $('#editProjectUserCheckboxes input[type="checkbox"]:checked').each(function() {
        selectedUserIds.push(parseInt($(this).val()));
    });
    console.log('👥 DEBUG - Thành viên được chọn:', selectedUserIds);

    // Xác nhận trước khi cập nhật
    if (!confirm('Bạn có chắc chắn muốn cập nhật dự án này?')) {
        return;
    }

    // Hiển thị loading state cho nút
    $('#btnSubmitEditProject').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...');

    // Cập nhật thông tin dự án
    console.log('🌐 DEBUG - Gọi API PUT:', `${API_BASE_URL}/groups/projects/${projectId}`);
    
    $.ajax({
        url: `${API_BASE_URL}/groups/projects/${projectId}`,
        method: 'PUT',
        contentType: 'application/json',
        headers: { 
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json'
        },
        data: JSON.stringify(projectData),
        success: function(response) {
            console.log('✅ DEBUG - Response từ server:', response);
            
            if (!response) {
                console.error('❌ ERROR - Response rỗng từ server');
                showToastError('Lỗi: Không nhận được phản hồi từ server');
                $('#btnSubmitEditProject').prop('disabled', false).html('Cập nhật');
                return;
            }
            
            // Cập nhật dữ liệu cục bộ ngay lập tức
            const index = allProjects.findIndex(p => p.id == projectId);
            if (index !== -1) {
                allProjects[index].name = projectData.name;
                allProjects[index].description = projectData.description;
                
                // Cập nhật deadline nếu có
                if (deadlineDate) {
                    allProjects[index].deadline = deadlineDate;
                } else {
                    allProjects[index].deadline = null;
                }
                
                console.log('🔄 DEBUG - Đã cập nhật local project:', allProjects[index]);
                renderProjects(allProjects);
            }
            
            // Nếu có thành viên được chọn, cập nhật thành viên
            if (selectedUserIds.length > 0) {
                console.log('👥 DEBUG - Cập nhật thành viên cho project:', projectId);
                $.ajax({
                    url: `${API_BASE_URL}/project-members/${projectId}`,
                    method: 'PUT',
                    contentType: 'application/json',
                    headers: { 'Authorization': 'Bearer ' + token },
                    data: JSON.stringify({ userIds: selectedUserIds }),
                    success: function() {
                        console.log('✅ DEBUG - Cập nhật thành viên thành công');
                        completeUpdate();
                    },
                    error: function(xhr) {
                        console.warn('⚠️ WARNING - Lỗi cập nhật thành viên:', xhr);
                        completeUpdate('Cập nhật dự án thành công! (Có cảnh báo khi cập nhật thành viên)');
                    }
                });
            } else {
                // Không có thành viên nào được chọn
                console.log('ℹ️ INFO - Không có thành viên nào được chọn để cập nhật');
                completeUpdate();
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ ERROR - Chi tiết lỗi AJAX:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            $('#btnSubmitEditProject').prop('disabled', false).html('Cập nhật');
            
            let errorMsg = 'Lỗi khi cập nhật dự án';
            
            // Phân tích lỗi chi tiết hơn
            if (xhr.responseJSON) {
                errorMsg = xhr.responseJSON.message || xhr.responseJSON.error || errorMsg;
            } else if (xhr.responseText) {
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    errorMsg = errorData.message || errorMsg;
                } catch (e) {
                    errorMsg = xhr.responseText.substring(0, 200);
                }
            }
            
            showToastError(errorMsg);
            
            // Gợi ý sửa lỗi nếu là lỗi định dạng ngày
            if (xhr.status === 400 && xhr.responseText.includes('Định dạng ngày')) {
                console.error('🔍 DEBUG - Lỗi định dạng ngày tháng');
                console.log('🔍 DEBUG - Giá trị deadline gửi đi:', deadlineDate);
            }
        }
    });
    
    // Hàm hoàn thành cập nhật
    function completeUpdate(message = 'Cập nhật dự án thành công!') {
        console.log('✅ DEBUG - Hoàn thành cập nhật:', message);
        
        $('#editProjectModal').modal('hide');
        $('#btnSubmitEditProject').prop('disabled', false).html('Cập nhật');
        
        showSuccess(message);
        
        // Load lại từ server để có data mới nhất
        setTimeout(() => {
            loadProjects();
            loadGroupMemberInfo();
        }, 500);
    }
}

// Xóa dự án từ modal
function deleteProjectFromModal() {
    const projectId = $('#editProjectId').val();
    confirmDeleteProject(projectId);
}

// Xác nhận xóa dự án
function confirmDeleteProject(projectId) {
    if (!confirm('Bạn có chắc chắn muốn xóa dự án này? Toàn bộ công việc và dữ liệu liên quan sẽ bị xóa.')) {
        return;
    }

    // Thử endpoint delete trước
    $.ajax({
        url: `${API_BASE_URL}/groups/projects/${projectId}`,
        method: 'DELETE',
        headers: { Authorization: `Bearer ${token}` },
        success: function() {
            $('#editProjectModal').modal('hide');
            showSuccess('Xóa dự án thành công!');
            loadProjects();
        },
        error: function(xhr) {
            console.log('⚠️ delete thất bại, thử endpoint chung:', xhr);
            
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

// Gửi lời mời thành viên với kiểm tra giới hạn
function sendInvite() {
    const email = $('#inviteEmail').val().trim();
    const projectId = $('#inviteProjectSelect').val();
    
    if (!email) {
        showToastError('Vui lòng nhập email');
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showToastError('Email không đúng định dạng');
        return;
    }

    // 🚨 KIỂM TRA GIỚI HẠN 10 THÀNH VIÊN (SỬA: Không tự động tăng, chỉ kiểm tra)
    console.log(`📊 Kiểm tra giới hạn thành viên: ${groupMemberInfo.totalMembers}/${groupMemberInfo.maxMembers}`);
    
    // SỬA: Chuẩn bị dữ liệu gửi đi - LOẠI BỎ TRƯỜNG ROLE
    const inviteData = {
        email: email
    };

    // Thêm projectId nếu được chọn
    if (projectId && projectId !== "") {
        inviteData.projectId = parseInt(projectId);
    }

    console.log('📤 Gửi lời mời với dữ liệu:', inviteData);

    $.ajax({
        url: `${API_BASE_URL}/groups/invitations`,
        method: 'POST',
        headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify(inviteData),
        success: function(response) {
            $('#inviteMemberModal').modal('hide');
            $('#inviteEmail').val('');
            $('#inviteProjectSelect').val('');
            
            // SỬA: Hiển thị message từ response
            showSuccess(response.message || 'Đã gửi lời mời thành công!');
            console.log('✅ Invitation response:', response);
            
            // Load lại thông tin thành viên từ server để có số liệu chính xác
            setTimeout(() => {
                loadGroupMemberInfo();
            }, 500);
        },
        error: function(xhr) {
            console.error('❌ Invitation error details:', xhr);
            
            let errorMsg = 'Có lỗi xảy ra khi gửi lời mời';
            
            // SỬA: Xử lý lỗi tốt hơn
            if (xhr.responseJSON) {
                errorMsg = xhr.responseJSON.message || xhr.responseJSON.error || errorMsg;
                
                // Nếu lỗi là do giới hạn thành viên, cập nhật lại thông tin
                if (xhr.responseJSON.error && 
                    (xhr.responseJSON.error.includes('tối đa') || 
                     xhr.responseJSON.error.includes('thành viên') ||
                     xhr.responseJSON.error.includes('đầy'))) {
                    // Load lại thông tin thành viên để có số liệu chính xác
                    loadGroupMemberInfo();
                }
            } else if (xhr.responseText) {
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    errorMsg = errorData.message || errorMsg;
                } catch (e) {
                    errorMsg = xhr.responseText;
                }
            }
            
            showToastError('Gửi lời mời thất bại: ' + errorMsg);
        }
    });
}

// Load danh sách dự án cho dropdown mời thành viên
function loadProjectsForInvite() {
    $.ajax({
        url: `${API_BASE_URL}/groups/projects/available`,
        method: 'GET',
        headers: { Authorization: `Bearer ${token}` },
        success: function(projects) {
            const dropdown = $('#inviteProjectSelect');
            dropdown.empty();
            dropdown.append('<option value="">Chọn dự án (tùy chọn)</option>');
            
            if (projects && projects.length > 0) {
                projects.forEach(project => {
                    // ĐẢM BẢO project.id là số nguyên
                    const projectId = parseInt(project.id);
                    if (!isNaN(projectId)) {
                        dropdown.append(
                            $('<option></option>')
                                .val(projectId)
                                .text(project.name + (project.description ? ` - ${project.description}` : ''))
                        );
                    }
                });
            }
            
            console.log('✅ Loaded projects for invite:', projects);
        },
        error: function(xhr) {
            console.error('❌ Error loading projects for invite:', xhr);
            $('#inviteProjectSelect').html('<option value="">Không thể tải danh sách dự án</option>');
        }
    });
}

// Các hàm tiện ích
function showLoading() {
    $('#projectList').html(`
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Đang tải dự án...</p>
        </div>
    `);
}

function hideLoading() {
    // Loading sẽ được ẩn khi renderProjects được gọi
}

// Thêm hàm hiển thị toast thay vì alert
function showToastSuccess(message) {
    // Tạo toast element nếu chưa có
    if ($('#successToast').length === 0) {
        $('body').append(`
            <div id="successToast" class="toast position-fixed top-0 end-0 m-3" style="z-index: 1060;">
                <div class="toast-header bg-success text-white">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong class="me-auto">Thành công</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);
    } else {
        $('#successToast .toast-body').text(message);
    }
    
    // Hiển thị toast
    const toast = new bootstrap.Toast($('#successToast')[0]);
    toast.show();
}

function showToastError(message) {
    // Tạo toast element nếu chưa có
    if ($('#errorToast').length === 0) {
        $('body').append(`
            <div id="errorToast" class="toast position-fixed top-0 end-0 m-3" style="z-index: 1060;">
                <div class="toast-header bg-danger text-white">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong class="me-auto">Lỗi</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);
    } else {
        $('#errorToast .toast-body').text(message);
    }
    
    // Hiển thị toast
    const toast = new bootstrap.Toast($('#errorToast')[0]);
    toast.show();
}

// Thêm hàm hiển thị toast cảnh báo
function showToastWarning(message) {
    // Tạo toast element nếu chưa có
    if ($('#warningToast').length === 0) {
        $('body').append(`
            <div id="warningToast" class="toast position-fixed top-0 end-0 m-3" style="z-index: 1060;">
                <div class="toast-header bg-warning text-dark">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong class="me-auto">Cảnh báo</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);
    } else {
        $('#warningToast .toast-body').text(message);
    }
    
    // Hiển thị toast
    const toast = new bootstrap.Toast($('#warningToast')[0]);
    toast.show();
}

function showSuccess(message) {
    // Thay thế alert bằng toast
    showToastSuccess(message);
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('vi-VN');
}

function isDeadlineOverdue(deadline) {
    if (!deadline) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const deadlineDate = new Date(deadline);
    deadlineDate.setHours(0, 0, 0, 0);
    return deadlineDate < today;
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function handleSearch() {
    const keyword = $(this).val().toLowerCase().trim();
    const filtered = allProjects.filter(p => 
        p.name.toLowerCase().includes(keyword) ||
        (p.description && p.description.toLowerCase().includes(keyword))
    );
    renderProjects(filtered);
}

function handleSort() {
    const selected = $(this).val();
    let sorted = [...allProjects];

    switch (selected) {
        case 'name-asc':
            sorted.sort((a, b) => a.name.localeCompare(b.name));
            break;
        case 'name-desc':
            sorted.sort((a, b) => b.name.localeCompare(a.name));
            break;
        case 'recent':
            sorted.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
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

// Các hàm load thành viên
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
            
            // Cập nhật lại hiển thị nút sau khi có thông tin user mới
            setTimeout(() => {
                handleFirstProjectButtonVisibility();
            }, 500);
        }
    } catch (error) {
        console.error("Lỗi khi cập nhật thông tin user:", error);
    }
}

function loadTeamMembersForCreate() {
    $.ajax({
        url: `${API_BASE_URL}/groups/members`,
        method: 'GET',
        headers: { Authorization: `Bearer ${token}` },
        success: (response) => {
            // 🚨 SỬA: Xử lý response theo cả 2 định dạng
            let members;
            if (response.members) {
                // Định dạng mới: response là object với thuộc tính members
                members = response.members;
                // Cập nhật thông tin thành viên
                groupMemberInfo.totalMembers = response.totalMembers;
                groupMemberInfo.maxMembers = response.maxMembers || 10;
            } else if (Array.isArray(response)) {
                // Định dạng cũ: response là mảng các thành viên
                members = response;
                groupMemberInfo.totalMembers = response.length;
                groupMemberInfo.maxMembers = 10;
            } else {
                members = [];
            }
            
            console.log(`👥 Thống kê thành viên: ${groupMemberInfo.totalMembers}/${groupMemberInfo.maxMembers}`);
            
            const html = members.map(member => `
                <div class="form-check">
                    <input class="form-check-input new-project-user-checkbox" 
                           type="checkbox" value="${member.id}" id="new-user-${member.id}">
                    <label class="form-check-label" for="new-user-${member.id}">
                        ${member.fullName} (${member.email})
                    </label>
                </div>
            `).join('');
            $('#newProjectUserCheckboxes').html(html);
        },
        error: () => {
            $('#newProjectUserCheckboxes').html('<div class="text-danger">Không thể tải danh sách thành viên</div>');
        }
    });
}

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
    ]).then(function([allMembers, projectMembers]) {
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
        
        // Xử lý allMembers theo cả 2 định dạng
        let membersList;
        if (allMembers.members) {
            membersList = allMembers.members;
        } else if (Array.isArray(allMembers)) {
            membersList = allMembers;
        } else {
            membersList = [];
        }
        
        const html = membersList.map(user => {
            const isAssigned = assignedMemberIds.has(user.id);
            return `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="${user.id}" 
                           id="editUser-${user.id}" ${isAssigned ? 'checked' : ''}>
                    <label class="form-check-label" for="editUser-${user.id}">
                        ${user.fullName || user.username} (${user.email})
                        ${isAssigned ? ' <span class="badge bg-success">Đã phân công</span>' : ''}
                    </label>
                </div>
            `;
        }).join('');
        
        container.html(html);
    }).catch(function(error) {
        container.html('<div class="alert alert-danger">Không thể tải danh sách thành viên</div>');
    });
}

function getStatusBadge(project) {
    const now = new Date();
    const deadline = project.deadline ? new Date(project.deadline) : null;
    
    // Kiểm tra trạng thái từ backend trước
    if (project.status === 'COMPLETED') {
        return { text: 'Đã hoàn thành', class: 'bg-success' };
    } 
    else if (project.status === 'EXPIRED') {
        return { text: 'Hết hạn', class: 'bg-danger' };
    }
    // THÊM: Kiểm tra nếu có thông tin task count
    else if (project.totalTasks > 0 && project.completedTasks === project.totalTasks) {
        return { text: 'Đã hoàn thành', class: 'bg-success' };
    }
    // Fallback: tính toán dựa trên deadline
    else if (deadline && deadline < now) {
        return { text: 'Hết hạn', class: 'bg-danger' };
    }
    else {
        return { text: 'Đang thực hiện', class: 'bg-warning' };
    }
}

// Global functions
function viewProject(projectId) {
    window.location.href = `tasks_details_groups.html?projectId=${projectId}`;
}

// Khởi chạy ứng dụng khi DOM ready
$(document).ready(initApp);