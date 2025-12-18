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
            await Promise.all([
                this.loadOverview(),
                this.loadRecentProjects(),
                this.loadMembers(),
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

            // Load members count - FIXED: Xử lý nhiều định dạng response
            const membersResponse = await fetch(`${API_BASE_URL}/groups/members`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
            
            if (membersResponse.ok) {
                const data = await membersResponse.json();
                console.log('Members API response:', data); // Debug
                
                // Xử lý nhiều định dạng response
                let members = [];
                if (Array.isArray(data)) {
                    members = data;
                } else if (data && Array.isArray(data.members)) {
                    members = data.members;
                } else if (data && Array.isArray(data.data)) {
                    members = data.data;
                } else if (data && data.content && Array.isArray(data.content)) {
                    members = data.content;
                } else if (data && typeof data === 'object') {
                    // Nếu là object đơn, chuyển thành mảng
                    members = [data];
                }
                
                this.dashboardData.totalMembers = members.length;
                
                // Cập nhật dashboardData.members để đồng bộ
                if (this.user.role === 'ADMIN') {
                    this.dashboardData.members = members;
                }
            }

            // Load projects và tasks
            await this.loadAllTasks();
            
            // Tính toán số liệu
            this.dashboardData.totalProjects = this.projects.length;
            this.dashboardData.activeTasks = this.tasks.filter(task => task.status !== 'DONE').length;
            this.dashboardData.completedTasks = this.tasks.filter(task => task.status === 'DONE').length;

            this.renderOverview();
            this.loadRecentProjects();
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

    renderRecentProjects() {
        const container = $('#recentActivity');
        
        if (!this.dashboardData.recentProjects || this.dashboardData.recentProjects.length === 0) {
            this.showNoProjects();
            return;
        }

        const projectsHTML = this.dashboardData.recentProjects.map(project => {
            if (!project) return '';
            
            // Lọc tasks cho project hiện tại
            const projectTasks = this.tasks.filter(task => {
                const taskProjectId = task.project ? task.project.id : 
                                    (task.projectReference ? task.projectReference.id : 
                                    (task.projectId ? task.projectId : null));
                const projectId = parseInt(project.id);
                return taskProjectId === projectId;
            });

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

    async loadAllTasks() {
        try {
            this.tasks = []; // Reset tasks
            
            const projectsResponse = await fetch(`${API_BASE_URL}/projects/groups`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
            
            if (projectsResponse.ok) {
                const projects = await projectsResponse.json();
                this.projects = projects || [];
                
                // Load tasks cho từng project
                for (const project of this.projects) {
                    try {
                        const tasksResponse = await fetch(`${API_BASE_URL}/tasks/groups/project/${project.id}`, {
                            headers: { 'Authorization': `Bearer ${this.token}` }
                        });
                        
                        if (tasksResponse.ok) {
                            const tasks = await tasksResponse.json();
                            // Gán project reference cho mỗi task
                            if (Array.isArray(tasks)) {
                                tasks.forEach(task => {
                                    task.projectReference = project;
                                    task.projectId = project.id;
                                });
                                this.tasks = this.tasks.concat(tasks);
                            }
                        }
                    } catch (error) {
                        console.error(`Lỗi tải tasks cho project ${project.id}:`, error);
                    }
                }
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

    async loadMembers() {
        if (this.user.role !== 'ADMIN') return;

        try {
            const response = await fetch(`${API_BASE_URL}/groups/members`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });

            if (response.ok) {
                const data = await response.json();
                console.log('Members data from API:', data); // Debug
                
                // Xử lý nhiều định dạng response - FIXED
                let members = [];
                if (Array.isArray(data)) {
                    members = data;
                } else if (data && Array.isArray(data.members)) {
                    members = data.members;
                } else if (data && Array.isArray(data.data)) {
                    members = data.data;
                } else if (data && data.content && Array.isArray(data.content)) {
                    members = data.content;
                } else if (data && typeof data === 'object') {
                    // Nếu là object đơn, chuyển thành mảng
                    members = [data];
                }
                
                this.dashboardData.members = members;
                this.renderMembers();
            } else {
                console.error('Failed to load members:', response.status);
                this.dashboardData.members = [];
                this.renderMembers();
            }
        } catch (error) {
            console.error('Lỗi tải thành viên:', error);
            this.dashboardData.members = [];
            this.renderMembers();
        }
    }

    renderMembers() {
        const container = $('#memberList');
        
        // Đảm bảo members là mảng - FIXED
        if (!Array.isArray(this.dashboardData.members)) {
            console.warn('Members is not an array, converting:', this.dashboardData.members);
            this.dashboardData.members = [];
        }
        
        if (this.dashboardData.members.length === 0) {
            container.html(`
                <div class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Chưa có thành viên nào</p>
                </div>
            `);
            return;
        }

        const membersHTML = this.dashboardData.members.map(member => {
            // Kiểm tra member có tồn tại không
            if (!member) return '';
            
            const displayName = this.escapeHtml(member.fullName || member.username || 'Unknown');
            const email = this.escapeHtml(member.email || 'No email');
            const role = member.role || 'MEMBER';
            const initial = (displayName && displayName.length > 0) ? displayName.charAt(0).toUpperCase() : 'U';
            
            return `
                <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                             style="width: 45px; height: 45px;">
                            <span class="text-white fw-bold">${initial}</span>
                        </div>
                        <div>
                            <h6 class="mb-1">${displayName}</h6>
                            <small class="text-muted">
                                <i class="fas fa-envelope me-1"></i>${email}
                            </small>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-user-tag me-1"></i>${role === 'ADMIN' ? 'Quản trị viên' : 'Thành viên'}
                            </small>
                        </div>
                    </div>
                    <div>
                        <span class="badge ${role === 'ADMIN' ? 'bg-danger' : 'bg-secondary'}">
                            ${role === 'ADMIN' ? 'ADMIN' : 'MEMBER'}
                        </span>
                    </div>
                </div>
            `;
        }).join('');

        container.html(membersHTML);
    }

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