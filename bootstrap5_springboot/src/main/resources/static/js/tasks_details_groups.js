const API_BASE_URL = "http://localhost:8080";

class GroupTaskManager {
    constructor() {
        this.user = JSON.parse(localStorage.getItem('user'));
        this.token = localStorage.getItem('token');
        this.projectId = this.getProjectIdFromURL();
        this.project = null;
        this.tasks = [];
        this.taskToDelete = null;
        this.projectCreatedAt = null;
        this.projectDeadline = null;
        
        // THÊM: Debug logging
        console.log('=== GroupTaskManager INIT ===');
        console.log('Project ID from URL:', this.projectId);
        console.log('User:', this.user);
        
        this.init();
    }
	
	hasEditPermission() {
		console.log('=== DEBUG hasEditPermission ===');
		console.log('User:', this.user);
		console.log('User role:', this.user?.role);
		console.log('Project:', this.project);
		console.log('Project createdBy:', this.project?.createdBy);
		console.log('User ID:', this.user?.id);
		console.log('Project creator ID:', this.project?.createdBy?.id);
	    
	    // Kiểm tra nếu user tồn tại
	    if (!this.user) return false;
	    
	    // ADMIN có toàn quyền
	    if (this.user.role === 'ADMIN') {
			console.log('Result: ADMIN -> true');
	        return true;
	    }
	    
	    // Người tạo project có toàn quyền
	    if (this.project && this.project.createdBy && this.project.createdBy.id === this.user.id) {
			console.log('Result: Project Owner -> true');
			return true;
	    }
	    
	    // Thành viên thông thường không có quyền sửa/xóa
		console.log('Result: Regular Member -> false');
	    return false;
	}

	init() {
	    if (!this.user || !this.token) {
	        console.error('No user or token found, redirecting to login');
	        window.location.href = 'login.html';
	        return;
	    }

	    if (!this.projectId) {
	        alert('Không tìm thấy thông tin dự án');
	        window.location.href = 'projects_groups.html';
	        return;
	    }

	    this.setupEventListeners();
	    
	    // Load project details TRƯỚC, sau đó mới load tasks
	    this.loadProjectDetails().then(() => {
	        console.log('Project loaded, now loading tasks...');
	        this.loadTasks(); // Chỉ load tasks sau khi project đã loaded
	    }).catch(error => {
	        console.error('Failed to load project:', error);
	    });
	    
	    // Update UI với user info
	    $('#currentUsername').text(this.user.fullName || this.user.username);
	    $('#sidebarUsername').text(this.user.fullName || this.user.username);
	    
	    // Ẩn settings menu cho tài khoản cá nhân
	    if (this.user.accountType === 'PERSONAL') {
	        $('.settings-menu-item').hide();
	    }
	    
	    // Toggle sidebar trên mobile
	    $('#sidebarToggle').click(function() {
	        $('.sidebar').toggleClass('active');
	        $('.main-content, .navbar-custom').toggleClass('active');
	    });
	}
	
	// THÊM method này để đảm bảo kiểm tra quyền chính xác
	getUserEditPermission() {
	    // Kiểm tra trực tiếp từ localStorage và project data
	    const user = JSON.parse(localStorage.getItem('user'));
	    if (!user) return false;
	    
	    console.log('=== Direct Permission Check ===');
	    console.log('User ID:', user.id);
	    console.log('User Role:', user.role);
	    console.log('Project Creator ID:', this.project?.createdBy?.id);
	    
	    // ADMIN có toàn quyền
	    if (user.role === 'ADMIN') {
	        console.log('Permission: ADMIN -> true');
	        return true;
	    }
	    
	    // Người tạo project có toàn quyền
	    if (this.project && this.project.createdBy && this.project.createdBy.id === user.id) {
	        console.log('Permission: Project Owner -> true');
	        return true;
	    }
	    
	    // Thành viên thông thường không có quyền sửa/xóa
	    console.log('Permission: Regular Member -> false');
	    return false;
	}

	getProjectIdFromURL() {
	    const urlParams = new URLSearchParams(window.location.search);
	    let id = urlParams.get('projectId');

	    if (id) {
	        localStorage.setItem("selectedProjectId", id);
	        return id;
	    }

	    // Nếu URL không có thì lấy từ localStorage
	    id = localStorage.getItem("selectedProjectId");
	    return id;
	}

    setupEventListeners() {
        // Task management
        $('#btnAddTask').on('click', () => this.showAddTaskModal());
        $('#btnAddFirstTask').on('click', () => this.showAddTaskModal());
        $('#btnSaveTask').on('click', () => this.saveTask());
        $('#confirmDeleteTask').on('click', () => this.confirmDeleteTask());

        // Search and filters
        $('#searchTaskInput').on('input', (e) => this.filterTasks(e.target.value));
        $('#filterStatus').on('change', () => this.applyFilters());
        $('#filterPriority').on('change', () => this.applyFilters());
        
        // Export
        $('#btnExportTasks').on('click', () => this.exportTasks());
        
        // Date validation
        $('#taskStartDate').on('change', () => this.validateDates());
        $('#taskDueDate').on('change', () => this.validateDates());
    }

	async loadProjectDetails() {
	    try {
	        console.log(`🔄 loadProjectDetails: Gọi API /groups/projects/${this.projectId}`);
	        
	        const response = await fetch(`${API_BASE_URL}/groups/projects/${this.projectId}`, {
	            headers: {
	                'Authorization': `Bearer ${this.token}`
	            }
	        });

	        console.log(`Response status: ${response.status}`);
	        console.log(`Response ok: ${response.ok}`);
	        
	        // Xử lý response dựa trên status code
	        if (response.ok) {
	            this.project = await response.json();
	            console.log('✅ Project loaded successfully:', this.project);
	            
	            // Kiểm tra và parse dates
	            if (this.project.createdAt) {
	                this.projectCreatedAt = new Date(this.project.createdAt);
	            }
	            if (this.project.deadline) {
	                this.projectDeadline = new Date(this.project.deadline);
	            }
	            
	            this.displayProjectInfo();
	            
	            // Kiểm tra và ẩn nút sau khi project loaded
	            const hasEditPermission = this.getUserEditPermission();
	            console.log('Final permission check:', hasEditPermission);
	            
	            if (!hasEditPermission) {
	                $('#btnAddTask').hide();
	                $('#btnAddFirstTask').hide();
	                $('#btnExportTasks').hide();
	                $('#permissionNotice').removeClass('d-none');
	            } else {
	                $('#btnAddTask').show();
	                $('#btnAddFirstTask').show();
	                $('#btnExportTasks').show();
	                $('#permissionNotice').addClass('d-none');
	            }
	            
	            return this.project;
	        } 
	        else {
	            // Xử lý lỗi - đọc response JSON một lần duy nhất
	            const errorData = await response.json();
	            console.error('Server error response:', errorData);
	            
	            if (response.status === 403) {
	                alert(errorData.message || 'Bạn không có quyền truy cập dự án này');
	                window.location.href = 'projects_groups.html';
	            } else if (response.status === 404) {
	                alert(errorData.message || 'Không tìm thấy dự án');
	                window.location.href = 'projects_groups.html';
	            } else if (response.status === 400) {
	                alert(errorData.message || 'Yêu cầu không hợp lệ');
	                window.location.href = 'projects_groups.html';
	            } else {
	                alert(errorData.message || `Lỗi server: ${response.status}`);
	                window.location.href = 'projects_groups.html';
	            }
	            throw new Error(errorData.message || `HTTP ${response.status}`);
	        }
	    } catch (error) {
	        console.error('Lỗi tải thông tin dự án:', error);
	        
	        // Kiểm tra nếu là lỗi network
	        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
	            alert('Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.');
	        } else {
	            alert('Lỗi khi tải thông tin dự án: ' + error.message);
	        }
	        
	        window.location.href = 'projects_groups.html';
	        throw error; // Re-throw để promise chain có thể catch
	    }
	}

    displayProjectInfo() {
        if (!this.project) {
            console.error('Không có project để hiển thị');
            return;
        }
        
        $('#projectTitle').text(this.project.name || 'Không có tên');
        $('#projectName').text(this.project.name || 'Không có tên');
        $('#projectDescription').text(this.project.description || 'Không có mô tả');
        
        if (this.project.createdAt) {
            $('#createdDate').text(new Date(this.project.createdAt).toLocaleString('vi-VN'));
        } else {
            $('#createdDate').text('Không có ngày tạo');
        }
        
        if (this.projectDeadline) {
            const today = new Date();
            const deadline = new Date(this.projectDeadline);
            
            if (deadline < today) {
                $('#projectDeadlineDisplay').html(`<span class="text-danger fw-bold">${deadline.toLocaleString('vi-VN')} ⏰ QUÁ HẠN</span>`);
            } else {
                $('#projectDeadlineDisplay').text(deadline.toLocaleString('vi-VN'));
            }
        } else {
            $('#projectDeadlineDisplay').text('Không có hạn');
        }
    }

    async loadTasks() {
        try {
            console.log(`🔄 loadTasks: Gọi API /tasks/groups/project/${this.projectId}`);
            
            const response = await fetch(`${API_BASE_URL}/tasks/groups/project/${this.projectId}`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                this.tasks = await response.json();
                console.log(`✅ Loaded ${this.tasks.length} tasks`);
                this.updateProgress();
                this.renderTasks(this.tasks);
            } else {
                const errorText = await response.text();
                console.error('Lỗi tải công việc:', errorText);
            }
        } catch (error) {
            console.error('Lỗi tải công việc:', error);
        }
    }

    updateProgress() {
        const totalTasks = this.tasks.length;
        const completedTasks = this.tasks.filter(task => task.status === 'DONE').length;
        const progress = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

        $('#totalTasks').text(totalTasks);
        $('#completedTasks').text(completedTasks);
        $('#progressText').text(`${progress}% hoàn thành`);

        const progressBar = $('#progressBar');
        
        // Animate progress
        progressBar.css({
            'transition': 'width 0.8s ease-in-out, background 0.8s ease',
            'width': `${progress}%`
        });
        
        // Modern color scheme with animation
        if (progress < 30) {
            progressBar.css({
                'background': 'linear-gradient(90deg, #ff4757, #ff3742)',
                'box-shadow': '0 2px 10px rgba(255, 71, 87, 0.3)'
            });
        } else if (progress < 70) {
            progressBar.css({
                'background': 'linear-gradient(90deg, #ffa502, #ff7e00)',
                'box-shadow': '0 2px 10px rgba(255, 165, 2, 0.3)'
            });
        } else {
            progressBar.css({
                'background': 'linear-gradient(90deg, #2ed573, #1e90ff)',
                'box-shadow': '0 2px 10px rgba(46, 213, 115, 0.3)'
            });
        }
    }

	renderTasks(tasks) {
	    console.log('=== DEBUG renderTasks ===');
	    const hasEditPermission = this.getUserEditPermission();
	    
	    const container = $('#tasksContainer');
	    const emptyState = $('#emptyState');
	    
	    if (tasks.length === 0) {
	        container.hide();
	        emptyState.show();
	        return;
	    }

	    emptyState.hide();
	    container.show();
	    container.empty();

	    tasks.forEach(task => {
	        const priority = task.priority || 'LOW';
	        const priorityClass = priority.toLowerCase();
	        const taskTitle = task.name || 'Không có tiêu đề';
	        const taskDescription = task.description || '';
	        const isOverdue = this.isOverdue(task);

	        console.log(`Rendering task ${task.id}, hasEditPermission: ${hasEditPermission}`);

	        // Tạo HTML cho các nút dựa trên quyền
	        let actionButtons = '';
	        
	        // Nút sửa và xóa chỉ hiển thị cho người có quyền chỉnh sửa
	        if (hasEditPermission) {
	            actionButtons += `
	                <button class="btn btn-outline-primary btn-sm flex-fill" 
	                        onclick="groupTaskManager.editTask(${task.id})">
	                    <i class="fas fa-edit"></i> Sửa
	                </button>
	                <button class="btn btn-outline-danger btn-sm" 
	                        onclick="groupTaskManager.deleteTask(${task.id}, '${this.escapeHtml(taskTitle)}')">
	                    <i class="fas fa-trash"></i>
	                </button>
	            `;
	        }
	        
	        // Nút hoàn thành/mở lại luôn hiển thị cho tất cả thành viên
	        actionButtons += `
	            <button class="btn btn-outline-success btn-sm flex-fill" 
	                    onclick="groupTaskManager.toggleTaskStatus(${task.id})">
	                <i class="fas fa-check"></i> 
	                ${task.status === 'DONE' ? 'Mở lại' : 'Hoàn thành'}
	            </button>
	            <button class="btn btn-outline-info btn-sm" 
	                    onclick="groupTaskManager.viewTaskDetails(${task.id})">
	                <i class="fas fa-eye"></i> Chi tiết
	            </button>
	        `;

	        const card = `
	        <div class="col-lg-6 col-xl-4 mb-3">
	            <div class="card task-card ${task.status === 'DONE' ? 'task-completed' : ''} task-priority-${priorityClass}">
	                <div class="card-body">
	                    <div class="d-flex justify-content-between align-items-start mb-2">
	                        <h6 class="card-title mb-0 flex-grow-1">${this.escapeHtml(taskTitle)}</h6>
	                        <span class="badge ${this.getStatusBadgeClass(task.status)} status-badge">
	                            ${this.getStatusText(task.status)}
	                        </span>
	                    </div>
	                    
	                    ${taskDescription ? `<p class="card-text small text-muted">${this.escapeHtml(taskDescription)}</p>` : ''}
	                    
	                    <div class="task-meta mb-2">
	                        <div class="d-flex justify-content-between align-items-center mb-2">
	                            <small class="text-muted">
	                                <i class="fas fa-flag ${this.getPriorityColor(priority)}"></i>
	                                ${this.getPriorityText(priority)}
	                            </small>
	                            
	                        </div>
	                        
	                        <div class="d-flex justify-content-between align-items-center">
	                            <div class="task-date-item">
	                                ${task.startDate ? `
	                                    <small class="text-muted">
	                                        <i class="fas fa-play-circle"></i>
	                                        ${new Date(task.startDate).toLocaleDateString('vi-VN')}
	                                    </small>
	                                ` : ''}
	                            </div>
	                            
	                            <div class="task-date-item">
	                                ${task.dueDate ? `
	                                    <small class="text-muted ${isOverdue ? 'text-danger fw-bold' : ''}">
	                                        <i class="fas fa-hourglass-end"></i>
	                                        ${new Date(task.dueDate).toLocaleDateString('vi-VN')}
	                                    </small>
	                                ` : ''}
	                            </div>
	                        </div>
	                    </div>
	                    
	                    <div class="d-flex gap-1">
	                        ${actionButtons}
	                    </div>
	                </div>
	            </div>
	        </div>
	        `;
	        container.append(card);
	    });
	}

    showAddTaskModal() {
        $('#taskModalTitle').text('Thêm công việc mới');
        $('#taskForm')[0].reset();
        $('#taskId').val('');
        
        // Set default dates
        const now = new Date();
        const nowString = now.toISOString().slice(0,16);
        $('#taskStartDate').val(nowString);
        
        // Set date constraints
        if (this.projectCreatedAt) {
            const minDateTime = this.projectCreatedAt.toISOString().slice(0,16);
            $('#taskStartDate').attr('min', minDateTime);
            $('#taskDueDate').attr('min', minDateTime);
        }

        if (this.projectDeadline) {
            const maxDateTime = this.projectDeadline.toISOString().slice(0,16);
            $('#taskStartDate').attr('max', maxDateTime);
            $('#taskDueDate').attr('max', maxDateTime);
        }

        $('#taskModal').modal('show');
    }

    editTask(taskId) {
        const task = this.tasks.find(t => t.id === taskId);
        if (!task) return;

        $('#taskModalTitle').text('Chỉnh sửa công việc');
        $('#taskId').val(task.id);
        $('#taskTitle').val(task.name || '');
        $('#taskDescription').val(task.description || '');
        $('#taskPriority').val(task.priority);
        
        // Format dates for datetime-local input
        $('#taskStartDate').val(task.startDate ? 
            new Date(task.startDate).toISOString().slice(0,16) : '');
        $('#taskDueDate').val(task.dueDate ? 
            new Date(task.dueDate).toISOString().slice(0,16) : '');

        // Set date constraints
        if (this.projectCreatedAt) {
            const minDateTime = this.projectCreatedAt.toISOString().slice(0,16);
            $('#taskStartDate').attr('min', minDateTime);
            $('#taskDueDate').attr('min', minDateTime);
        }

        if (this.projectDeadline) {
            const maxDateTime = this.projectDeadline.toISOString().slice(0,16);
            $('#taskStartDate').attr('max', maxDateTime);
            $('#taskDueDate').attr('max', maxDateTime);
        }

        $('#taskModal').modal('show');
    }

    async saveTask() {
        const taskId = $('#taskId').val();
        const taskData = {
            name: $('#taskTitle').val().trim(),
            description: $('#taskDescription').val().trim(),
            priority: $('#taskPriority').val(),
            projectId: parseInt(this.projectId),
        };
        
        // Handle status
        if (!taskId) {
            // New task: default to TODO
            taskData.status = 'TODO';
        } else {
            // Existing task: get status from current task
            const existingTask = this.tasks.find(t => t.id === parseInt(taskId));
            taskData.status = existingTask ? existingTask.status : 'TODO';
        }
        
        const startDateValue = $('#taskStartDate').val();
        const dueDateValue = $('#taskDueDate').val();

        // Validation
        if (!this.validateDates()) {
            return;
        }

        if (!taskData.name) {
            alert('Vui lòng nhập tên công việc');
            return;
        }

        // Handle dates
        if (startDateValue) {
            taskData.startDate = startDateValue;
        }
        if (dueDateValue) {
            taskData.dueDate = dueDateValue;
        }

        try {
            const url = taskId ? `${API_BASE_URL}/tasks/groups/${taskId}` : `${API_BASE_URL}/tasks/groups`;
            const method = taskId ? 'PUT' : 'POST';
            
            console.log('Dữ liệu gửi đi:', taskData);

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify(taskData)
            });

            if (response.ok) {
                const updatedTask = await response.json(); 
                console.log('Dữ liệu trả về từ server:', updatedTask); 
                
                $('#taskModal').modal('hide');
                this.loadTasks();
            } else {
                const errorText = await response.text();
                console.error('Chi tiết lỗi từ server:', errorText);
                alert('Lỗi khi lưu công việc: ' + response.statusText);
            }
        } catch (error) {
            console.error('Lỗi lưu công việc:', error);
            alert('Lỗi kết nối đến server');
        }
    }

    validateDates() {
        const startDate = $('#taskStartDate').val();
        const dueDate = $('#taskDueDate').val();
        
        if (!startDate || !dueDate) return true;

        const start = new Date(startDate);
        const due = new Date(dueDate);
        
        // Check against project creation date
        if (this.projectCreatedAt && start < this.projectCreatedAt) {
            alert('Ngày bắt đầu không được trước ngày tạo dự án');
            $('#taskStartDate').val('');
            return false;
        }

        // Check against project deadline (if any)
        if (this.projectDeadline && due > this.projectDeadline) {
            alert('Hạn hoàn thành không được sau deadline dự án');
            $('#taskDueDate').val('');
            return false;
        }

        if (start > due) {
            alert('Ngày bắt đầu không được sau hạn hoàn thành');
            $('#taskDueDate').val('');
            return false;
        }

        return true;
    }

    deleteTask(taskId, taskTitle) {
        const hasEditPermission = this.getUserEditPermission();
        if (!hasEditPermission) {
            alert('Bạn không có quyền xóa công việc này');
            return;
        }
        
        this.taskToDelete = taskId;
        $('#taskToDeleteName').text(taskTitle);
        $('#deleteTaskModal').modal('show');
    }

    async confirmDeleteTask() {
        if (!this.taskToDelete) return;

        try {
            const response = await fetch(`${API_BASE_URL}/tasks/groups/${this.taskToDelete}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                $('#deleteTaskModal').modal('hide');
                this.loadTasks();
            } else {
                alert('Lỗi khi xóa công việc');
            }
        } catch (error) {
            console.error('Lỗi xóa công việc:', error);
            alert('Lỗi kết nối đến server');
        } finally {
            this.taskToDelete = null;
        }
    }

    async toggleTaskStatus(taskId) {
        const task = this.tasks.find(t => t.id === taskId);
        if (!task) return;

        const newStatus = task.status === 'DONE' ? 'TODO' : 'DONE';
        
        try {
            const response = await fetch(`${API_BASE_URL}/tasks/groups/${taskId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify({ 
                    status: newStatus 
                })
            });

            if (response.ok) {
                this.loadTasks();
            } else {
                const errorText = await response.text();
                console.error('Lỗi từ server:', errorText);
                alert('Lỗi khi cập nhật trạng thái');
            }
        } catch (error) {
            console.error('Lỗi cập nhật trạng thái:', error);
        }
    }

    viewTaskDetails(taskId) {
        window.location.href = `tasks_details_details_groups.html?taskId=${taskId}&projectId=${this.projectId}`;
    }

    filterTasks(searchTerm) {
        const filtered = this.tasks.filter(task => {
            const title = task.name || '';
            const description = task.description || '';
            return title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                   description.toLowerCase().includes(searchTerm.toLowerCase());
        });
        this.renderTasks(filtered);
    }

    applyFilters() {
        const statusFilter = $('#filterStatus').val();
        const priorityFilter = $('#filterPriority').val();

        let filtered = this.tasks;

        if (statusFilter) {
            filtered = filtered.filter(task => task.status === statusFilter);
        }

        if (priorityFilter) {
            filtered = filtered.filter(task => task.priority === priorityFilter);
        }

        this.renderTasks(filtered);
    }

    exportTasks() {
        if (!this.project || !this.project.name) {
            alert('Không thể xuất file khi không có thông tin dự án');
            return;
        }
        
        const csvContent = this.convertTasksToCSV();
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', `tasks_${this.project.name}_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    convertTasksToCSV() {
        const headers = ['Tiêu đề', 'Mô tả', 'Ưu tiên', 'Trạng thái', 'Ngày bắt đầu', 'Hạn hoàn thành', 'Ngày tạo'];
        const rows = this.tasks.map(task => [
            task.name || 'Không có tiêu đề',
            task.description || '',
            this.getPriorityText(task.priority || 'LOW'),
            this.getStatusText(task.status),
            task.startDate ? new Date(task.startDate).toLocaleDateString('vi-VN') : '',
            task.dueDate ? new Date(task.dueDate).toLocaleDateString('vi-VN') : '',
            task.createdDate ? new Date(task.createdDate).toLocaleDateString('vi-VN') : 'Chưa có'
        ]);

        return [headers, ...rows]
            .map(row => row.map(field => `"${field.replace(/"/g, '""')}"`).join(','))
            .join('\n');
    }

    // Helper methods
    getStatusBadgeClass(status) {
        switch (status) {
            case 'TODO': return 'bg-secondary';
            case 'IN_PROGRESS': return 'bg-primary';
            case 'DONE': return 'bg-success';
            default: return 'bg-secondary';
        }
    }

    getStatusText(status) {
        switch (status) {
            case 'TODO': return 'Chưa bắt đầu';
            case 'IN_PROGRESS': return 'Đang làm';
            case 'DONE': return 'Hoàn thành';
            default: return status;
        }
    }

    getPriorityColor(priority) {
        const prio = priority || 'LOW';
        switch (prio) {
            case 'HIGH': return 'text-danger';
            case 'MEDIUM': return 'text-warning';
            case 'LOW': return 'text-success';
            default: return 'text-muted';
        }
    }

    getPriorityText(priority) {
        const prio = priority || 'LOW';
        switch (prio) {
            case 'HIGH': return 'Cao';
            case 'MEDIUM': return 'Trung bình';
            case 'LOW': return 'Thấp';
            default: return prio;
        }
    }

    isOverdue(task) {
        if (!task.dueDate || task.status === 'DONE') return false;
        return new Date(task.dueDate) < new Date();
    }

    escapeHtml(unsafe) {
        if (unsafe === undefined || unsafe === null) {
            return '';
        }
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

// Khởi tạo ứng dụng
let groupTaskManager;
$(document).ready(function() {
    groupTaskManager = new GroupTaskManager();
});