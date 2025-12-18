class PersonalTaskManager {
    constructor() {
        this.user = JSON.parse(localStorage.getItem('user'));
        this.token = localStorage.getItem('token');
        this.projectId = this.getProjectIdFromURL();
        this.project = null;
        this.tasks = [];
        this.taskToDelete = null;
        this.projectCreatedAt = null;
        this.projectDeadline = null;
        
        this.init();
    }

    init() {
        if (!this.user || !this.token) {
            window.location.href = 'login.html';
            return;
        }

        if (!this.projectId) {
            alert('Không tìm thấy thông tin dự án');
            window.location.href = 'projects_personal.html'; // Sửa đường dẫn
            return;
        }

        this.setupEventListeners();
        this.loadProjectDetails();
        this.loadTasks();
    }

    getProjectIdFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('projectId');
    }

    setupEventListeners() {
        // Thêm kiểm tra phần tử tồn tại trước khi thêm event listener
        const addTaskBtn = document.getElementById('btnAddTask');
        const addFirstTaskBtn = document.getElementById('btnAddFirstTask');
        const saveTaskBtn = document.getElementById('btnSaveTask');
        const confirmDeleteBtn = document.getElementById('confirmDeleteTask');
        const searchInput = document.getElementById('searchTaskInput');
        const filterStatus = document.getElementById('filterStatus');
        const filterPriority = document.getElementById('filterPriority');
        const exportBtn = document.getElementById('btnExportTasks');
        const startDateInput = document.getElementById('taskStartDate');
        const dueDateInput = document.getElementById('taskDueDate');

        if (addTaskBtn) addTaskBtn.addEventListener('click', () => this.showAddTaskModal());
        if (addFirstTaskBtn) addFirstTaskBtn.addEventListener('click', () => this.showAddTaskModal());
        if (saveTaskBtn) saveTaskBtn.addEventListener('click', () => this.saveTask());
        if (confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', () => this.confirmDeleteTask());
        
        if (searchInput) searchInput.addEventListener('input', (e) => this.filterTasks(e.target.value));
        if (filterStatus) filterStatus.addEventListener('change', (e) => this.applyFilters());
        if (filterPriority) filterPriority.addEventListener('change', (e) => this.applyFilters());
        
        if (exportBtn) exportBtn.addEventListener('click', () => this.exportTasks());
        
        if (startDateInput) startDateInput.addEventListener('change', (e) => this.validateDates());
        if (dueDateInput) dueDateInput.addEventListener('change', (e) => this.validateDates());
    }

    validateDates() {
        const startDate = document.getElementById('taskStartDate')?.value;
        const dueDate = document.getElementById('taskDueDate')?.value;
        
        if (!startDate || !dueDate) return true;

        const start = new Date(startDate);
        const due = new Date(dueDate);
        
        // Kiểm tra với ngày tạo dự án
        if (this.projectCreatedAt && start < this.projectCreatedAt) {
            alert('Ngày bắt đầu không được trước ngày tạo dự án');
            document.getElementById('taskStartDate').value = '';
            return false;
        }

        // Kiểm tra với deadline dự án (nếu có)
        if (this.projectDeadline && due > this.projectDeadline) {
            alert('Hạn hoàn thành không được sau deadline dự án');
            document.getElementById('taskDueDate').value = '';
            return false;
        }

        if (start > due) {
            alert('Ngày bắt đầu không được sau hạn hoàn thành');
            document.getElementById('taskDueDate').value = '';
            return false;
        }

        return true;
    }

    async loadProjectDetails() {
        try {
            const response = await fetch(`/projects/personal/${this.projectId}`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                this.project = await response.json();
                this.projectCreatedAt = new Date(this.project.createdAt);
                this.projectDeadline = this.project.deadline ? new Date(this.project.deadline) : null;
                this.displayProjectInfo();
            } 
            else if (response.status === 403) {
                alert('Bạn không có quyền truy cập dự án này');
                window.location.href = 'projects-personal.html'; // Sửa đường dẫn
            } else {
                throw new Error('Không thể tải thông tin dự án');
            }
        } catch (error) {
            console.error('Lỗi tải thông tin dự án:', error);
            alert('Lỗi khi tải thông tin dự án');
        }
    }

    displayProjectInfo() {
        const projectTitle = document.getElementById('projectTitle');
        const projectName = document.getElementById('projectName');
        const projectDescription = document.getElementById('projectDescription');
        const createdDate = document.getElementById('createdDate');
        const deadlineElement = document.getElementById('projectDeadlineDisplay');

        if (projectTitle) projectTitle.textContent = this.project.name;
        if (projectName) projectName.textContent = this.project.name;
        if (projectDescription) projectDescription.textContent = this.project.description || 'Không có mô tả';
        if (createdDate) createdDate.textContent = new Date(this.project.createdAt).toLocaleString('vi-VN');
        
        if (deadlineElement) {
            if (this.projectDeadline) {
                const today = new Date();
                const deadline = new Date(this.projectDeadline);
                
                if (deadline < today) {
                    deadlineElement.innerHTML = `<span class="text-danger fw-bold">${deadline.toLocaleString('vi-VN')} ⏰ QUÁ HẠN</span>`;
                } else {
                    deadlineElement.textContent = deadline.toLocaleString('vi-VN');
                }
            } else {
                deadlineElement.textContent = 'Không có hạn';
            }
        }
    }

    async loadTasks() {
        try {
            const response = await fetch(`/tasks/personal/project/${this.projectId}`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                this.tasks = await response.json();
                this.updateProgress();
                this.renderTasks(this.tasks);
            }
        } catch (error) {
            console.error('Lỗi tải công việc:', error);
        }
    }

	updateProgress() {
	    const totalTasks = this.tasks.length;
	    const completedTasks = this.tasks.filter(task => task.status === 'DONE').length;
	    const progress = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

	    document.getElementById('totalTasks').textContent = totalTasks;
	    document.getElementById('completedTasks').textContent = completedTasks;
	    document.getElementById('progressText').textContent = `${progress}% hoàn thành`;

	    const progressBar = document.getElementById('progressBar');
	    
	    // Animate progress
	    progressBar.style.transition = 'width 0.8s ease-in-out, background 0.8s ease';
	    progressBar.style.width = `${progress}%`;
	    
	    // Modern color scheme with animation
	    if (progress < 30) {
	        progressBar.style.background = 'linear-gradient(90deg, #ff4757, #ff3742)';
	        progressBar.style.boxShadow = '0 2px 10px rgba(255, 71, 87, 0.3)';
	    } else if (progress < 70) {
	        progressBar.style.background = 'linear-gradient(90deg, #ffa502, #ff7e00)';
	        progressBar.style.boxShadow = '0 2px 10px rgba(255, 165, 2, 0.3)';
	    } else {
	        progressBar.style.background = 'linear-gradient(90deg, #2ed573, #1e90ff)';
	        progressBar.style.boxShadow = '0 2px 10px rgba(46, 213, 115, 0.3)';
	    }
	}

    renderTasks(tasks) {
        const container = document.getElementById('tasksContainer');
        const emptyState = document.getElementById('emptyState');
        
        if (!container || !emptyState) return;

        if (tasks.length === 0) {
            container.classList.add('d-none');
            emptyState.classList.remove('d-none');
            return;
        }

        emptyState.classList.add('d-none');
        container.classList.remove('d-none');

        container.innerHTML = tasks.map(task => {
            const priority = task.priority || 'LOW';
            const priorityClass = priority.toLowerCase();
            const taskTitle = task.name || 'Không có tiêu đề';
            const taskDescription = task.description || '';
            const isOverdue = this.isOverdue(task);

            return `
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
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">
                                    <i class="fas fa-flag ${this.getPriorityColor(priority)}"></i>
                                    ${this.getPriorityText(priority)}
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i>
                                    ${task.createdDate ? new Date(task.createdDate).toLocaleDateString('vi-VN') : 'Chưa có'}
                                </small>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="task-date-item">
                                    ${task.startDate ? `
                                        <small class="text-muted">
                                            <i class="fas fa-play-circle"></i>
                                            ${new Date(task.startDate).toLocaleString('vi-VN')}
                                        </small>
                                    ` : ''}
                                </div>
                                
                                <div class="task-date-item">
                                    ${task.dueDate ? `
                                        <small class="text-muted ${isOverdue ? 'text-danger fw-bold' : ''}">
                                            <i class="fas fa-hourglass-end"></i>
                                            ${new Date(task.dueDate).toLocaleString('vi-VN')}
                                        </small>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-1">
                            <button class="btn btn-outline-primary btn-sm flex-fill" 
                                    onclick="personalTaskManager.editTask(${task.id})">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-outline-success btn-sm flex-fill" 
                                    onclick="personalTaskManager.toggleTaskStatus(${task.id})">
                                <i class="fas fa-check"></i> 
                                ${task.status === 'DONE' ? 'Mở lại' : 'Hoàn thành'}
                            </button>
							<button class="btn btn-outline-info btn-sm" 
								onclick="personalTaskManager.viewTaskDetails(${task.id})">
							    <i class="fas fa-eye"></i>
							</button>
                            <button class="btn btn-outline-danger btn-sm" 
                                    onclick="personalTaskManager.deleteTask(${task.id}, '${this.escapeHtml(taskTitle)}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            `;
        }).join('');
    }

	showAddTaskModal() {
	    const modalTitle = document.getElementById('taskModalTitle');
	    const taskForm = document.getElementById('taskForm');
	    const taskId = document.getElementById('taskId');
	    const startDateInput = document.getElementById('taskStartDate');
	    const dueDateInput = document.getElementById('taskDueDate');

	    if (!modalTitle || !taskForm || !taskId) return;

	    modalTitle.textContent = 'Thêm công việc mới';
	    taskForm.reset();
	    taskId.value = '';

	    // Thiết lập giá trị mặc định
	    const now = new Date();
	    const nowString = now.toISOString().slice(0,16);
	    
	    if (startDateInput) {
	        startDateInput.value = nowString;
	        
	        // Thiết lập giới hạn thời gian
	        if (this.projectCreatedAt) {
	            const minDateTime = this.projectCreatedAt.toISOString().slice(0,16);
	            startDateInput.min = minDateTime;
	            if (dueDateInput) dueDateInput.min = minDateTime;
	        }

	        if (this.projectDeadline) {
	            const maxDateTime = this.projectDeadline.toISOString().slice(0,16);
	            startDateInput.max = maxDateTime;
	            if (dueDateInput) dueDateInput.max = maxDateTime;
	        } else {
	            // Nếu không có deadline dự án, set max là 1 năm sau
	            const oneYearLater = new Date();
	            oneYearLater.setFullYear(oneYearLater.getFullYear() + 1);
	            const maxDateTime = oneYearLater.toISOString().slice(0,16);
	            startDateInput.max = maxDateTime;
	            if (dueDateInput) dueDateInput.max = maxDateTime;
	        }
	    }

	    const modalElement = document.getElementById('taskModal');
	    if (modalElement) {
	        const modal = new bootstrap.Modal(modalElement);
	        modal.show();
	    }
	}

    editTask(taskId) {
        const task = this.tasks.find(t => t.id === taskId);
        if (!task) return;

        const modalTitle = document.getElementById('taskModalTitle');
        const taskIdInput = document.getElementById('taskId');
        const taskTitle = document.getElementById('taskTitle');
        const taskDescription = document.getElementById('taskDescription');
        const taskPriority = document.getElementById('taskPriority');
        const taskStatus = document.getElementById('taskStatus');
        const startDateInput = document.getElementById('taskStartDate');
        const dueDateInput = document.getElementById('taskDueDate');

        if (!modalTitle || !taskIdInput) return;

        modalTitle.textContent = 'Chỉnh sửa công việc';
        taskIdInput.value = task.id;
        if (taskTitle) taskTitle.value = task.name || '';
        if (taskDescription) taskDescription.value = task.description || '';
        if (taskPriority) taskPriority.value = task.priority;
        
        // Format datetime cho datetime-local input
        if (startDateInput) {
            startDateInput.value = task.startDate ? 
                new Date(task.startDate).toISOString().slice(0,16) : '';
        }
        if (dueDateInput) {
            dueDateInput.value = task.dueDate ? 
                new Date(task.dueDate).toISOString().slice(0,16) : '';
        }

        // Thiết lập giới hạn thời gian
        if (startDateInput && this.projectCreatedAt) {
            const minDateTime = this.projectCreatedAt.toISOString().slice(0,16);
            startDateInput.min = minDateTime;
            if (dueDateInput) dueDateInput.min = minDateTime;
        }

        if (startDateInput && this.projectDeadline) {
            const maxDateTime = this.projectDeadline.toISOString().slice(0,16);
            startDateInput.max = maxDateTime;
            if (dueDateInput) dueDateInput.max = maxDateTime;
        }

        const modalElement = document.getElementById('taskModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }

	async saveTask() {
	    const taskId = document.getElementById('taskId')?.value;
	    const taskTitle = document.getElementById('taskTitle');
	    const taskDescription = document.getElementById('taskDescription');
	    const taskPriority = document.getElementById('taskPriority');
	    // ĐÃ XÓA TASK STATUS
	    const startDateInput = document.getElementById('taskStartDate');
	    const dueDateInput = document.getElementById('taskDueDate');

	    if (!taskTitle) return;

	    const taskData = {
	        name: taskTitle.value.trim(),
	        description: taskDescription ? taskDescription.value.trim() : '',
	        priority: taskPriority ? taskPriority.value : 'MEDIUM',
	        projectId: parseInt(this.projectId),
	    };
	    
	    // XỬ LÝ STATUS: Mặc định là TODO cho task mới, giữ nguyên cho task cũ
	    if (!taskId) {
	        // Task mới: mặc định là TODO
	        taskData.status = 'TODO';
	    } else {
	        // Task cũ: lấy status từ task hiện tại
	        const existingTask = this.tasks.find(t => t.id === parseInt(taskId));
	        taskData.status = existingTask ? existingTask.status : 'TODO';
	    }
	    
	    const startDateValue = startDateInput ? startDateInput.value : '';
	    const dueDateValue = dueDateInput ? dueDateInput.value : '';

	    // Validation
	    if (!this.validateDates()) {
	        return;
	    }

	    if (!taskData.name) {
	        alert('Vui lòng nhập tên công việc');
	        return;
	    }

	    // Xử lý dates
	    if (startDateValue) {
	        taskData.startDate = startDateValue;
	    }
	    if (dueDateValue) {
	        taskData.dueDate = dueDateValue;
	    }

	    try {
	        const url = taskId ? `/tasks/personal/${taskId}` : '/tasks/personal';
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
	            
	            const modalElement = document.getElementById('taskModal');
	            if (modalElement) {
	                const modal = bootstrap.Modal.getInstance(modalElement);
	                if (modal) modal.hide();
	            }
	            this.loadTasks();
	        } else {
	            const errorText = await response.text();
	            console.error('Chi tiết lỗi từ server:', errorText);

	            if (response.status === 400) {
	                alert('Dữ liệu không hợp lệ. Vui lòng kiểm tra lại thông tin.');
	            } else {
	                alert('Lỗi khi lưu công việc: ' + response.statusText);
	            }
	        }
	    } catch (error) {
	        console.error('Lỗi lưu công việc:', error);
	        alert('Lỗi kết nối đến server');
	    }
	}
    deleteTask(taskId, taskTitle) {
        this.taskToDelete = taskId;
        const taskToDeleteName = document.getElementById('taskToDeleteName');
        if (taskToDeleteName) {
            taskToDeleteName.textContent = taskTitle;
        }
        
        const modalElement = document.getElementById('deleteTaskModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }

    async confirmDeleteTask() {
        if (!this.taskToDelete) return;

        try {
            const response = await fetch(`/tasks/personal/${this.taskToDelete}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                const modalElement = document.getElementById('deleteTaskModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();
                }
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
            const response = await fetch(`/tasks/personal/${taskId}/status`, {
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
		window.location.href = `tasks_details_details_personal.html?taskId=${taskId}&projectId=${this.projectId}`;
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
        const statusFilter = document.getElementById('filterStatus')?.value;
        const priorityFilter = document.getElementById('filterPriority')?.value;

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
let personalTaskManager;
document.addEventListener('DOMContentLoaded', function() {
    personalTaskManager = new PersonalTaskManager();
});