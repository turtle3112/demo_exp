class SimplePersonalDashboard {
    constructor() {
        this.user = JSON.parse(localStorage.getItem('user'));
        this.token = localStorage.getItem('token');
        this.projects = [];
        this.tasks = [];
        
        this.init();
    }

    init() {
        if (!this.user || !this.token) {
            window.location.href = 'login.html';
            return;
        }

        this.loadDashboardData();
    }

    async loadDashboardData() {
        try {
            await this.loadProjects();
            await this.loadAllTasks();
            this.updateDashboard();
        } catch (error) {
            console.error('Lỗi tải dữ liệu:', error);
        }
    }

    async loadProjects() {
        try {
            const response = await fetch('/projects/personal', {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                this.projects = await response.json();
            }
        } catch (error) {
            console.error('Lỗi tải dự án:', error);
        }
    }

    async loadAllTasks() {
        this.tasks = [];
		console.log('bắt đầu tải tasks từ', this.projects.length, 'dự án');
        
        // Load tasks from all projects
        for (const project of this.projects) {
            try {
                const response = await fetch(`/tasks/personal/project/${project.id}`, {
                    headers: {
                        'Authorization': `Bearer ${this.token}`
                    }
                });
				
				console.log('Response status:', response.status);

                if (response.ok) {
                    const projectTasks = await response.json();
					console.log(`Nhận được ${projectTasks.length} tasks từ project ${project.id}`);
                    // Add project name to each task for display
                    projectTasks.forEach(task => {
                        task.projectName = project.name;
						task.projectId = project.id;
                    });
                    this.tasks = this.tasks.concat(projectTasks);
					console.log('Tổng số tasks hiện tại:', this.tasks.length);
                }
            } catch (error) {
                console.error(`Lỗi tải tasks cho project ${project.id}:`, error);
            }
        }
		
		console.log('Kết thúc tải tasks. Tổng số tasks:', this.tasks.length);
		console.log('Chi tiết tasks:', this.tasks);
    }

    updateDashboard() {
        this.updateStats();
        this.updateRecentProjects();
        this.updateUpcomingTasks();
        this.updateWelcomeMessage();
    }

    updateStats() {
        const totalProjects = this.projects.length;
        const totalTasks = this.tasks.length;
        const completedTasks = this.tasks.filter(task => task.status === 'DONE').length;
        const pendingTasks = totalTasks - completedTasks;
        const completionRate = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

        // Update DOM
        document.getElementById('totalProjects').textContent = totalProjects;
        document.getElementById('completedTasks').textContent = completedTasks;
        document.getElementById('pendingTasks').textContent = pendingTasks;
        document.getElementById('completionRate').textContent = `${completionRate}%`;
    }

	updateRecentProjects() {
	    const container = document.getElementById('recentProjects');
	    const recentProjects = this.projects.slice(0, 5); // Get first 5 projects

	    container.innerHTML = recentProjects.map(project => {
	        // QUAN TRỌNG: Sửa cách lọc tasks theo projectId
	        // Đảm bảo so sánh đúng kiểu dữ liệu (number với number)
	        const projectTasks = this.tasks.filter(task => {
	            // Chuyển đổi cả hai về number để so sánh
	            const taskProjectId = parseInt(task.projectId);
	            const projectId = parseInt(project.id);
	            return taskProjectId === projectId;
	        });
	        
	        const completedTasks = projectTasks.filter(task => task.status === 'DONE').length;
	        const totalTasks = projectTasks.length;
	        const progress = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

	        return `
	            <div class="mb-3 pb-2 border-bottom">
	                <h6 class="mb-1">${project.name}</h6>
	                <p class="mb-1 text-muted small">${project.description || 'Không có mô tả'}</p>
	                <div class="d-flex justify-content-between align-items-center">
	                    <small class="text-muted">
	                        ${completedTasks} hoàn thành / ${totalTasks} công việc
	                    </small>
	                    <span class="badge bg-primary">${progress}%</span>
	                </div>
	            </div>
	        `;
	    }).join('');
	}

    updateUpcomingTasks() {
		
		console.log('🔍 DEBUG upcoming tasks:');
		console.log('Tổng số tasks:', this.tasks.length);

		const tasksWithDueDate = this.tasks.filter(task => task.dueDate);
		console.log('Tasks có dueDate:', tasksWithDueDate.length);
		tasksWithDueDate.forEach(task => {
		    console.log(`- ${task.name}: dueDate=${task.dueDate}, status=${task.status}`);
		});
		
        const container = document.getElementById('upcomingTasks');
        const today = new Date();
        const nextWeek = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
		
		console.log('Hôm nay:', today.toLocaleDateString('vi-VN'));
		console.log('7 ngày tới:', nextWeek.toLocaleDateString('vi-VN'));

        // Get tasks that are not done and have due date within next week
        const upcomingTasks = this.tasks
            .filter(task => task.status !== 'DONE' && task.dueDate)
            .filter(task => {
                const dueDate = new Date(task.dueDate);
                return dueDate >= today && dueDate <= nextWeek;
            })
            .sort((a, b) => new Date(a.dueDate) - new Date(b.dueDate))
            .slice(0, 5); // Get first 5 tasks

        if (upcomingTasks.length === 0) {
            container.innerHTML = `
                <div class="text-center py-3">
                    <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                    <p class="text-muted">Không có công việc sắp đến hạn</p>
                    <small class="text-muted">Tuyệt vời! Bạn đang kiểm soát tốt công việc.</small>
                </div>
            `;
            return;
        }

        container.innerHTML = upcomingTasks.map(task => {
            const dueDate = new Date(task.dueDate);
            const isUrgent = dueDate.getTime() - today.getTime() < 2 * 24 * 60 * 60 * 1000; // Within 2 days
            const daysLeft = Math.ceil((dueDate.getTime() - today.getTime()) / (24 * 60 * 60 * 1000));

            return `
                <div class="task-item ${isUrgent ? 'urgent' : ''} mb-3">
                    <h6 class="mb-1">${task.name}</h6>
                    <p class="mb-1 text-muted small">${task.projectName}</p>
                    <small class="${isUrgent ? 'text-danger fw-bold' : 'text-muted'}">
                        <i class="fas fa-calendar me-1"></i>
                        Hạn: ${dueDate.toLocaleDateString('vi-VN')} 
                        (${daysLeft} ngày nữa)
                    </small>
                </div>
            `;
        }).join('');
    }

    updateWelcomeMessage() {
        const totalTasks = this.tasks.length;
        const completedTasks = this.tasks.filter(task => task.status === 'DONE').length;
        
        let message = '';
        if (totalTasks === 0) {
            message = 'Hãy bắt đầu tạo dự án và công việc đầu tiên!';
        } else if (completedTasks === totalTasks) {
            message = 'Tuyệt vời! Bạn đã hoàn thành tất cả công việc! 🎉';
        } else {
            const completionRate = Math.round((completedTasks / totalTasks) * 100);
            message = `Bạn đã hoàn thành ${completionRate}% công việc. Tiếp tục phát huy nhé! 💪`;
        }
        
        document.getElementById('dashboardSummary').textContent = message;
    }
}

// Khởi tạo dashboard
let simpleDashboard;
document.addEventListener('DOMContentLoaded', function() {
    simpleDashboard = new SimplePersonalDashboard();
});