// dashboard_groups.js
const API_BASE_URL = "http://localhost:8080";

class GroupDashboard {
    constructor() {
        this.user = JSON.parse(localStorage.getItem('user'));
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

        // Invite member - SAO CHÉP NGUYÊN TỪ projects_groups.js
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

	    // FIXED: Show back button for personal accounts AND personal users who joined team
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
            await Promise.all([
                this.loadOverview(),
                this.loadRecentProjects(),
                this.loadMembers(),
                // this.loadInvitations(), // Tạm thời comment để tránh lỗi
                // this.loadAuditLogs() // Tạm thời comment để tránh lỗi
            ]);
        } catch (error) {
            console.error('Lỗi tải dashboard:', error);
        }
    }

	async loadOverview() {
	    try {
	        // Reset counters
	        this.dashboardData.activeTasks = 0;
	        this.dashboardData.completedTasks = 0;

	        // Load members count
	        const membersResponse = await fetch(`${API_BASE_URL}/groups/members`, {
	            headers: { 'Authorization': `Bearer ${this.token}` }
	        });
	        if (membersResponse.ok) {
	            const members = await membersResponse.json();
	            this.dashboardData.totalMembers = members.length;
	        }

	        // Load projects và tasks
	        await this.loadAllTasks();
	        
	        // Tính toán số liệu
	        this.dashboardData.totalProjects = this.projects.length;
	        this.dashboardData.activeTasks = this.tasks.filter(task => task.status !== 'DONE').length;
	        this.dashboardData.completedTasks = this.tasks.filter(task => task.status === 'DONE').length;

	        this.renderOverview();
	        this.loadRecentProjects(); // Đảm bảo load recent projects sau khi có tasks
	    } catch (error) {
	        console.error('Lỗi tải tổng quan:', error);
	    }
	}

    renderOverview() {
        $('#totalMembers').text(this.dashboardData.totalMembers);
        $('#totalProjects').text(this.dashboardData.totalProjects);
        $('#activeTasks').text(this.dashboardData.activeTasks);
        $('#completedTasks').text(this.dashboardData.completedTasks);
    }

    async loadRecentProjects() {
        try {
            const recentProjects = this.projects
                .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
                .slice(0, 5);

            this.dashboardData.recentProjects = recentProjects;
            this.renderRecentProjects();
        } catch (error) {
            console.error('Lỗi tải dự án gần đây:', error);
            this.showNoProjects();
        }
    }
	
	debugTasks() {
	    console.log('=== DEBUG TASKS ===');
	    console.log('Total tasks:', this.tasks.length);
	    this.tasks.forEach((task, index) => {
	        console.log(`Task ${index}:`, {
	            id: task.id,
	            name: task.name,
	            status: task.status,
	            project: task.project,
	            projectId: task.project ? task.project.id : 'No project object',
	            projectReference: task.projectReference
	        });
	    });
	}

	renderRecentProjects() {
	    const container = $('#recentActivity');
	    
	    if (this.dashboardData.recentProjects.length === 0) {
	        this.showNoProjects();
	        return;
	    }

	    console.log('=== DEBUG RENDER RECENT PROJECTS ===');
	    console.log('All tasks:', this.tasks);
	    console.log('Recent projects:', this.dashboardData.recentProjects);

	    const projectsHTML = this.dashboardData.recentProjects.map(project => {
	        // SỬA LẠI CÁCH LỌC TASK - QUAN TRỌNG!
	        const projectTasks = this.tasks.filter(task => {
	            // Debug từng task
	            const taskProjectId = task.project ? task.project.id : 
	                                 (task.projectReference ? task.projectReference.id : null);
	            const projectId = parseInt(project.id);
	            
	            console.log(`Comparing: Task ${task.id} (project: ${taskProjectId}) vs Project ${projectId} - Match: ${taskProjectId === projectId}`);
	            
	            return taskProjectId === projectId;
	        });

	        console.log(`Project ${project.id} (${project.name}) has ${projectTasks.length} tasks:`, projectTasks);

	        const completedTasks = projectTasks.filter(task => task.status === 'DONE').length;
	        const totalTasks = projectTasks.length;
	        const progress = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

	        return `
	            <div class="d-flex align-items-center mb-3 p-2 border-bottom">
	                <div class="flex-shrink-0">
	                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
	                         style="width: 40px; height: 40px;">
	                        <i class="fas fa-project-diagram text-white"></i>
	                    </div>
	                </div>
	                <div class="flex-grow-1 ms-3">
	                    <div class="d-flex justify-content-between align-items-center">
	                        <h6 class="mb-0">${this.escapeHtml(project.name)}</h6>
	                        <small class="text-muted">${this.formatTimeAgo(project.createdAt)}</small>
	                    </div>
	                    <p class="mb-1 text-muted small">${this.escapeHtml(project.description || 'Không có mô tả')}</p>
	                    <div class="d-flex justify-content-between align-items-center">
	                        <small class="text-muted">
	                            ${completedTasks} hoàn thành / ${totalTasks} công việc
	                        </small>
	                        <span class="badge bg-primary">${progress}%</span>
	                    </div>
	                </div>
	            </div>
	        `;
	    }).join('');

	    container.html(projectsHTML);
	}

	// THÊM HÀM LOAD TASKS RIÊNG ĐỂ ĐẢM BẢO DỮ LIỆU
	async loadAllTasks() {
	    try {
	        this.tasks = []; // Reset tasks
	        
	        const projectsResponse = await fetch(`${API_BASE_URL}/projects/groups`, {
	            headers: { 'Authorization': `Bearer ${this.token}` }
	        });
	        
	        if (projectsResponse.ok) {
	            const projects = await projectsResponse.json();
	            this.projects = projects;
	            
	            // Load tasks cho từng project
	            for (const project of projects) {
	                try {
	                    const tasksResponse = await fetch(`${API_BASE_URL}/tasks/groups/project/${project.id}`, {
	                        headers: { 'Authorization': `Bearer ${this.token}` }
	                    });
	                    
	                    if (tasksResponse.ok) {
	                        const tasks = await tasksResponse.json();
	                        // Gán project reference cho mỗi task
	                        tasks.forEach(task => {
	                            task.projectReference = project;
	                            task.projectId = project.id; // Đảm bảo có projectId
	                        });
	                        this.tasks = this.tasks.concat(tasks);
	                    }
	                } catch (error) {
	                    console.error(`Lỗi tải tasks cho project ${project.id}:`, error);
	                }
	            }
	            
	            console.log('All tasks loaded:', this.tasks);
	        }
	    } catch (error) {
	        console.error('Lỗi tải tasks:', error);
	    }
	}
	

    showNoProjects() {
        $('#recentActivity').html(`
            <div class="text-center py-4">
                <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                <p class="text-muted">Chưa có dự án nào</p>
            </div>
        `);
    }
	
	

    // SAO CHÉP NGUYÊN VẸN TỪ projects_groups.js
    async loadMembers() {
        if (this.user.role !== 'ADMIN') return;

        try {
            const response = await fetch(`${API_BASE_URL}/groups/members`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });

            if (response.ok) {
                this.dashboardData.members = await response.json();
                this.renderMembers();
            }
        } catch (error) {
            console.error('Lỗi tải thành viên:', error);
        }
    }

    renderMembers() {
        const container = $('#memberList');
        
        if (this.dashboardData.members.length === 0) {
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
                        <span class="text-white fw-bold">${(member.fullName || member.username || 'U').charAt(0).toUpperCase()}</span>
                    </div>
                    <div>
                        <h6 class="mb-1">${this.escapeHtml(member.fullName || member.username || 'Unknown')}</h6>
                        <small class="text-muted">
                            <i class="fas fa-envelope me-1"></i>${this.escapeHtml(member.email || 'No email')}
                        </small>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-user-tag me-1"></i>${member.role === 'ADMIN' ? 'Quản trị viên' : 'Thành viên'}
                        </small>
                    </div>
                </div>
                <div>
                    <span class="badge ${member.role === 'ADMIN' ? 'bg-danger' : 'bg-secondary'}">
                        ${member.role === 'ADMIN' ? 'ADMIN' : 'MEMBER'}
                    </span>
                </div>
            </div>
        `).join('');

        container.html(membersHTML);
    }

    // SAO CHÉP NGUYÊN VẸN TỪ projects_groups.js
    sendInvite() {
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
                Authorization: `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({ email: email }),
            success: () => {
                $('#inviteMemberModal').modal('hide');
                $('#inviteEmail').val('');
                this.showSuccess('Đã gửi lời mời thành công!');
            },
            error: (xhr) => {
                alert('Gửi lời mời thất bại: ' + (xhr.responseJSON?.message || ''));
            }
        });
    }

    // Helper methods
    formatTimeAgo(timestamp) {
        if (!timestamp) return 'Vừa xong';
        
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
    }

    showSuccess(message) {
        console.log('Success:', message);
        alert(message);
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
let groupDashboard;
$(document).ready(function() {
    groupDashboard = new GroupDashboard();
});

