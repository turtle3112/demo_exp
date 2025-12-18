const API_BASE_URL = "http://localhost:8080";

class BusinessDashboard {
    constructor() {
        this.user = JSON.parse(localStorage.getItem('user') || '{}');
        this.token = localStorage.getItem('token');
        this.dashboardData = {
            totalMembers: 0,
            totalProjects: 0,
            activeTasks: 0,
            completedTasks: 0,
            recentProjects: [],
            members: [],
            invitations: [],
            auditLogs: []
        };
        
        this.projects = [];
        this.tasks = [];
        
        this.init();
    }

    init() {
        if (!this.user || !this.token) {
            window.location.href = 'login.html';
            return;
        }

        this.setupEventListeners();
        this.loadDashboardData();
        this.updateUI();
    }

    setupEventListeners() {
        // Sidebar toggle for mobile
        $('#sidebarToggle').on('click', () => {
            $('.sidebar').toggleClass('active');
            $('.main-content, .navbar-custom').toggleClass('active');
        });

        // Navigation
        $('.nav-link').on('click', function(e) {
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
        });

        // Invite member
        $('#btnSendInvite').on('click', () => this.sendInvite());

        // Auto-refresh every 30 seconds
        setInterval(() => {
            this.loadDashboardData();
        }, 30000);
    }

    updateUI() {
        // Update user info
        $('#currentUsername').text(this.user.fullName || this.user.username);
        $('#sidebarUsername').text(this.user.fullName || this.user.username);

        // Show/hide features based on role
        if (this.user.role !== 'ADMIN') {
            $('#btnInviteMember').hide();
        }

        // Hide settings for personal accounts
        if (this.user.accountType === 'PERSONAL') {
            $('.settings-menu-item').hide();
        }

        // Show back button for personal accounts AND personal users who joined team
        if (this.user.accountType === 'PERSONAL' || 
            this.user.role === 'EMPLOYEE' || 
            this.user.role === 'MEMBER') {
            $('#btnBackToPersonal').show();
        } else {
            $('#btnBackToPersonal').hide();
        }
    }

    async loadDashboardData() {
        try {
            console.log('🔄 Loading dashboard data...');
            await Promise.all([
                this.loadOverview(),
                this.loadRecentProjects(),
                this.loadMembers()
            ]);
            console.log('✅ Dashboard data loaded successfully');
        } catch (error) {
            console.error('❌ Lỗi tải dashboard:', error);
            this.showErrorMessage('Không thể tải dữ liệu dashboard');
        }
    }

    async loadOverview() {
        try {
            console.log('📊 Loading overview data...');
            
            // Reset counters
            this.dashboardData.activeTasks = 0;
            this.dashboardData.completedTasks = 0;

            // ✅ SỬA: Dùng endpoint /groups/members thay vì /business/members
            try {
                const membersResponse = await fetch(`${API_BASE_URL}/groups/members`, {
                    headers: { 'Authorization': `Bearer ${this.token}` }
                });
                if (membersResponse.ok) {
                    const response = await membersResponse.json();
                    // Lấy members từ response.members (vì API trả về object)
                    const members = response.members || [];
                    this.dashboardData.totalMembers = members.length;
                    console.log(`👥 Total members: ${members.length}`);
                }
            } catch (error) {
                console.warn('⚠️ Could not load members:', error);
            }

            // Load projects và tasks
            await this.loadAllProjectsAndTasks();
            
            // Tính toán số liệu
            this.dashboardData.totalProjects = this.projects.length;
            this.dashboardData.activeTasks = this.tasks.filter(task => 
                task.status && task.status !== 'DONE' && task.status !== 'COMPLETED'
            ).length;
            this.dashboardData.completedTasks = this.tasks.filter(task => 
                task.status && (task.status === 'DONE' || task.status === 'COMPLETED')
            ).length;

            console.log('📈 Dashboard statistics:', {
                projects: this.dashboardData.totalProjects,
                activeTasks: this.dashboardData.activeTasks,
                completedTasks: this.dashboardData.completedTasks,
                members: this.dashboardData.totalMembers
            });

            this.renderOverview();
        } catch (error) {
            console.error('❌ Lỗi tải tổng quan:', error);
        }
    }

    renderOverview() {
        $('#totalMembers').text(this.dashboardData.totalMembers);
        $('#totalProjects').text(this.dashboardData.totalProjects);
        $('#activeTasks').text(this.dashboardData.activeTasks);
        $('#completedTasks').text(this.dashboardData.completedTasks);
    }

    async loadAllProjectsAndTasks() {
        try {
            console.log('🔄 Loading all projects and tasks...');
            
            // ✅ Tải tất cả projects (ENDPOINT ĐÚNG: /projects/business)
            const projectsResponse = await fetch(`${API_BASE_URL}/projects/business`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
            
            if (!projectsResponse.ok) {
                console.error(`❌ Failed to load projects: ${projectsResponse.status}`);
                return;
            }
            
            const projects = await projectsResponse.json();
            this.projects = projects;
            console.log(`✅ Loaded ${projects.length} projects`);
            
            // Reset tasks array
            this.tasks = [];
            
            // ✅ SỬA: Dùng endpoint /tasks/project/{projectId} hoặc /tasks/groups/project/{projectId}
            const taskPromises = projects.map(async (project) => {
                try {
                    console.log(`📋 Loading tasks for project ${project.id} (${project.name})...`);
                    
                    // THỬ CÁC ENDPOINT CÓ THỂ CÓ:
                    // 1. /tasks/project/{projectId}
                    // 2. /tasks/groups/project/{projectId}
                    // 3. /tasks/groups/project/{projectId}
                    
                    let tasksResponse;
                    
                    // Thử endpoint đầu tiên
                    tasksResponse = await fetch(`${API_BASE_URL}/tasks/project/${project.id}`, {
                        headers: { 'Authorization': `Bearer ${this.token}` }
                    });
                    
                    if (!tasksResponse.ok) {
                        // Thử endpoint thứ hai
                        tasksResponse = await fetch(`${API_BASE_URL}/tasks/groups/project/${project.id}`, {
                            headers: { 'Authorization': `Bearer ${this.token}` }
                        });
                    }
                    
                    if (tasksResponse.ok) {
                        const tasks = await tasksResponse.json();
                        console.log(`   ✅ Project ${project.name} has ${tasks.length} tasks`);
                        
                        // Thêm project reference vào mỗi task
                        return tasks.map(task => ({
                            ...task,
                            projectId: project.id,
                            projectName: project.name,
                            project: project
                        }));
                    } else {
                        console.warn(`   ⚠️ No tasks found for project ${project.id} or API error: ${tasksResponse.status}`);
                        return [];
                    }
                } catch (error) {
                    console.error(`   ❌ Error loading tasks for project ${project.id}:`, error);
                    return [];
                }
            });
            
            // Đợi tất cả tasks được tải
            const tasksArrays = await Promise.all(taskPromises);
            this.tasks = tasksArrays.flat(); // Kết hợp tất cả tasks
            
            console.log(`🎉 Total tasks loaded: ${this.tasks.length}`);
            
            // Debug: Hiển thị chi tiết tasks
            this.debugTasks();
            
        } catch (error) {
            console.error('❌ Error loading projects and tasks:', error);
        }
    }

    debugTasks() {
        console.log('=== DEBUG TASKS ===');
        console.log(`Total tasks: ${this.tasks.length}`);
        
        if (this.tasks.length === 0) {
            console.log('No tasks found.');
            return;
        }
        
        // Nhóm tasks theo project
        const tasksByProject = {};
        this.tasks.forEach((task) => {
            const projectId = task.projectId || 'unknown';
            if (!tasksByProject[projectId]) {
                tasksByProject[projectId] = {
                    projectName: task.projectName || 'Unknown',
                    tasks: []
                };
            }
            tasksByProject[projectId].tasks.push({
                id: task.id,
                name: task.name,
                status: task.status || 'Unknown'
            });
        });
        
        console.log('Tasks grouped by project:', tasksByProject);
        
        // Hiển thị tổng quan
        Object.keys(tasksByProject).forEach(projectId => {
            const project = tasksByProject[projectId];
            console.log(`Project ${projectId} (${project.projectName}): ${project.tasks.length} tasks`);
        });
    }

    async loadRecentProjects() {
        try {
            console.log('🔄 Loading recent projects...');
            
            // Nếu chưa có projects, tải lại
            if (this.projects.length === 0) {
                await this.loadAllProjectsAndTasks();
            }
            
            // Sắp xếp projects theo thời gian tạo mới nhất
            const recentProjects = [...this.projects]
                .sort((a, b) => {
                    const dateA = new Date(a.createdAt || 0);
                    const dateB = new Date(b.createdAt || 0);
                    return dateB - dateA;
                })
                .slice(0, 5);

            this.dashboardData.recentProjects = recentProjects;
            console.log(`📊 Recent projects to display: ${recentProjects.length}`);
            
            this.renderRecentProjects();
        } catch (error) {
            console.error('❌ Lỗi tải dự án gần đây:', error);
            this.showNoProjects();
        }
    }

    renderRecentProjects() {
        const container = $('#recentActivity');
        
        if (!this.dashboardData.recentProjects || this.dashboardData.recentProjects.length === 0) {
            console.log('⚠️ No recent projects to display');
            this.showNoProjects();
            return;
        }

        console.log('🎨 Rendering recent projects...');
        
        const projectsHTML = this.dashboardData.recentProjects.map(project => {
            // Tìm tất cả tasks thuộc project này
            const projectTasks = this.tasks.filter(task => {
                const taskProjectId = String(task.projectId || '');
                const currentProjectId = String(project.id);
                return taskProjectId === currentProjectId;
            });

            console.log(`   📋 Project ${project.name} has ${projectTasks.length} tasks`);
            
            // Tính toán số liệu
            const completedTasks = projectTasks.filter(task => 
                task.status && (task.status === 'DONE' || task.status === 'COMPLETED')
            ).length;
            const totalTasks = projectTasks.length;
            const progress = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

            // Tạo HTML cho tasks (nếu có)
            let tasksHTML = '';
            if (projectTasks.length > 0) {
                tasksHTML = `
                    <div class="mt-2 task-details" style="font-size: 0.85rem;">
                        <small class="text-muted">Công việc gần đây:</small>
                        <ul class="mb-0 ps-3" style="max-height: 100px; overflow-y: auto;">
                            ${projectTasks.slice(0, 3).map(task => `
                                <li class="${task.status === 'DONE' || task.status === 'COMPLETED' ? 'text-success' : 'text-warning'}">
                                    <i class="fas fa-${task.status === 'DONE' || task.status === 'COMPLETED' ? 'check-circle' : 'clock'} me-1"></i>
                                    ${this.escapeHtml(task.name || 'Unnamed Task')}
                                    <small class="badge bg-${task.status === 'DONE' || task.status === 'COMPLETED' ? 'success' : 'warning'} ms-1">
                                        ${task.status === 'DONE' || task.status === 'COMPLETED' ? 'Hoàn thành' : 'Đang làm'}
                                    </small>
                                </li>
                            `).join('')}
                            ${projectTasks.length > 3 ? `
                                <li class="text-muted">
                                    <i class="fas fa-ellipsis-h me-1"></i>
                                    ... và ${projectTasks.length - 3} công việc khác
                                </li>
                            ` : ''}
                        </ul>
                    </div>
                `;
            }

            return `
                <div class="d-flex align-items-center mb-3 p-3 border rounded shadow-sm recent-project-item" 
                     data-project-id="${project.id}"
                     style="background: white; cursor: pointer; transition: all 0.2s;"
                     onmouseover="this.style.boxShadow='0 0.5rem 1rem rgba(0, 0, 0, 0.15)'"
                     onmouseout="this.style.boxShadow='0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)'">
                    <div class="flex-shrink-0">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px;">
                            <i class="fas fa-project-diagram text-white fs-5"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0 fw-bold text-primary">${this.escapeHtml(project.name)}</h6>
                            <small class="text-muted">${this.formatTimeAgo(project.createdAt)}</small>
                        </div>
                        <p class="mb-2 text-muted small">${this.escapeHtml(project.description || 'Không có mô tả')}</p>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-tasks me-1"></i>
                                    ${completedTasks} hoàn thành / ${totalTasks} công việc
                                </small>
                            </div>
                            <div>
                                <span class="badge ${progress === 100 ? 'bg-success' : 'bg-primary'}">
                                    <i class="fas fa-chart-line me-1"></i>${progress}%
                                </span>
                            </div>
                        </div>
                        
                        ${tasksHTML}
                        
                        <div class="mt-2">
                            <button class="btn btn-sm btn-outline-primary view-project-btn" 
                                    data-project-id="${project.id}"
                                    style="font-size: 0.8rem;">
                                <i class="fas fa-external-link-alt me-1"></i>Xem chi tiết
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        container.html(projectsHTML);
        
        // Thêm event listener cho nút xem chi tiết
        $('.view-project-btn').on('click', (e) => {
            e.stopPropagation();
            const projectId = $(e.target).closest('.view-project-btn').data('project-id');
            this.viewProjectDetails(projectId);
        });
        
        // Thêm event listener cho toàn bộ project item
        $('.recent-project-item').on('click', (e) => {
            if (!$(e.target).closest('.view-project-btn').length) {
                const projectId = $(e.currentTarget).data('project-id');
                this.viewProjectDetails(projectId);
            }
        });
        
        console.log('✅ Recent projects rendered successfully');
    }

    viewProjectDetails(projectId) {
        console.log(`🔍 Viewing project details for ID: ${projectId}`);
        // Chuyển hướng đến trang chi tiết project
        window.location.href = `project_business.html?id=${projectId}`;
    }

    showNoProjects() {
        $('#recentActivity').html(`
            <div class="text-center py-5">
                <i class="fas fa-project-diagram fa-4x text-muted mb-3"></i>
                <h5 class="text-muted mb-3">Chưa có dự án nào</h5>
                <p class="text-muted mb-4">Bắt đầu bằng cách tạo dự án đầu tiên của bạn</p>
                <button class="btn btn-primary" id="btnCreateFirstProject">
                    <i class="fas fa-plus me-2"></i>Tạo dự án mới
                </button>
            </div>
        `);
        
        $('#btnCreateFirstProject').on('click', () => {
            window.location.href = 'project_business.html?action=create';
        });
    }

    async loadMembers() {
        if (this.user.role !== 'ADMIN') return;

        try {
            // ✅ SỬA: Dùng endpoint /groups/members thay vì /business/members
            const response = await fetch(`${API_BASE_URL}/groups/members`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });

            if (response.ok) {
                const data = await response.json();
                // Lấy members từ response.members
                this.dashboardData.members = data.members || [];
                this.renderMembers();
            }
        } catch (error) {
            console.error('Lỗi tải thành viên:', error);
        }
    }

    renderMembers() {
        const container = $('#memberList');
        
        if (!this.dashboardData.members || this.dashboardData.members.length === 0) {
            container.html(`
                <div class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Chưa có thành viên nào</p>
                </div>
            `);
            return;
        }

        const membersHTML = this.dashboardData.members.map(member => `
            <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                <div class="d-flex align-items-center">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                         style="width: 45px; height: 45px;">
                        <span class="text-white fw-bold">
                            ${(member.fullName || member.username || 'U').charAt(0).toUpperCase()}
                        </span>
                    </div>
                    <div>
                        <h6 class="mb-1">${this.escapeHtml(member.fullName || member.username || 'Unknown')}</h6>
                        <small class="text-muted">
                            <i class="fas fa-envelope me-1"></i>${this.escapeHtml(member.email || 'No email')}
                        </small>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-user-tag me-1"></i>
                            ${member.role === 'ADMIN' ? 'Quản trị viên' : 
                              member.role === 'MANAGER' ? 'Quản lý' : 
                              member.role === 'EMPLOYEE' ? 'Nhân viên' : 'Thành viên'}
                        </small>
                    </div>
                </div>
                <div>
                    <span class="badge ${member.role === 'ADMIN' ? 'bg-danger' : 
                                       member.role === 'MANAGER' ? 'bg-warning' : 'bg-secondary'}">
                        ${member.role || 'MEMBER'}
                    </span>
                </div>
            </div>
        `).join('');

        container.html(membersHTML);
    }

    sendInvite() {
        const email = $('#inviteEmail').val().trim();
        
        if (!email) {
            this.showAlert('Vui lòng nhập email', 'warning');
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            this.showAlert('Email không đúng định dạng', 'warning');
            return;
        }

        // ✅ SỬA: Dùng endpoint /groups/invitations thay vì /invitations/send
        $.ajax({
            url: `${API_BASE_URL}/groups/invitations`,
            method: 'POST',
            headers: {
                Authorization: `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({ email: email }),
            success: () => {
                $('#inviteMemberModal').modal('hide');
                $('#inviteEmail').val('');
                this.showAlert('Đã gửi lời mời thành công!', 'success');
            },
            error: (xhr) => {
                const message = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Gửi lời mời thất bại';
                this.showAlert(message, 'error');
            }
        });
    }

    // Helper methods
    formatTimeAgo(timestamp) {
        if (!timestamp) return 'Vừa xong';
        
        try {
            const date = new Date(timestamp);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Vừa xong';
            if (diffMins < 60) return `${diffMins} phút trước`;
            if (diffHours < 24) return `${diffHours} giờ trước`;
            if (diffDays < 7) return `${diffDays} ngày trước`;
            
            return date.toLocaleDateString('vi-VN');
        } catch (error) {
            return 'Không xác định';
        }
    }

    showAlert(message, type = 'info') {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';
        
        const icon = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        }[type] || 'info-circle';
        
        // Tạo alert element
        const alertId = 'custom-alert-' + Date.now();
        const alertHTML = `
            <div id="${alertId}" class="alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3" 
                 style="z-index: 9999; min-width: 300px;">
                <i class="fas fa-${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('body').append(alertHTML);
        
        // Tự động đóng sau 5 giây
        setTimeout(() => {
            $(`#${alertId}`).alert('close');
        }, 5000);
    }
    
    showErrorMessage(message) {
        this.showAlert(message, 'error');
    }

    escapeHtml(unsafe) {
        if (unsafe === undefined || unsafe === null) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

// Initialize the dashboard when DOM is ready
let businessDashboard;
$(document).ready(function() {
    console.log('🚀 Initializing Business Dashboard...');
    businessDashboard = new BusinessDashboard();
    
    // Thêm global helper
    window.businessDashboard = businessDashboard;
});