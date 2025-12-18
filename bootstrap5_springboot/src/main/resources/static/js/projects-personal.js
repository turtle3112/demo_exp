console.log('PERSONAL PAGE - User từ localStorage:', JSON.parse(localStorage.getItem('user')));
console.log('PERSONAL PAGE - Token từ localStorage:', localStorage.getItem('token'));
console.log('projects-personal.js ĐANG CHẠY - Version: ' + new Date().toISOString());
// projects-personal.js - DÀNH RIÊNG CHO PERSONAL USERS
class PersonalProjectManager {
    constructor() {
        this.user = JSON.parse(localStorage.getItem('user'));
        this.token = localStorage.getItem('token'); 
        this.projects = [];
        this.projectToDelete = null;
        this.invitations = [];
        this.projectToEdit = null;
        this.init();
    }
    
    // Thêm phương thức mở modal sửa
    editProject(projectId, projectName) {
        this.projectToEdit = projectId;
        this.loadProjectDetails(projectId);
    }

    // Tải chi tiết dự án để hiển thị trong form sửa
    async loadProjectDetails(projectId) {
        try {
            const response = await fetch(`/projects/personal/${projectId}`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                const project = await response.json();
                this.showEditProjectModal(project);
            } else {
                alert('Không thể tải thông tin dự án');
            }
        } catch (error) {
            console.error('Lỗi khi tải thông tin dự án:', error);
            alert('Lỗi kết nối đến server');
        }
    }

    // Hiển thị modal sửa với dữ liệu hiện tại
    showEditProjectModal(project) {
        document.getElementById('editProjectId').value = project.id;
        document.getElementById('editProjectName').value = project.name;
        document.getElementById('editProjectDescription').value = project.description || '';
        
        // Định dạng deadline nếu có
        if (project.deadline) {
            const deadlineDate = new Date(project.deadline).toISOString().split('T')[0];
            document.getElementById('editProjectDeadline').value = deadlineDate;
        } else {
            document.getElementById('editProjectDeadline').value = '';
        }

        // Hiển thị modal
        const editModal = new bootstrap.Modal(document.getElementById('editProjectModal'));
        editModal.show();
    }

    // Cập nhật dự án
    async updateProject() {
        const projectId = document.getElementById('editProjectId').value;
        const name = document.getElementById('editProjectName').value.trim();
        const description = document.getElementById('editProjectDescription').value.trim();
        const deadline = document.getElementById('editProjectDeadline').value;

        if (!name) {
            alert('Vui lòng nhập tên dự án');
            return;
        }

        try {
            const projectData = {
                name: name,
                description: description
            };

            // Thêm deadline nếu có
            if (deadline) {
                projectData.deadlineDate = deadline;
            }

            const response = await fetch(`/projects/personal/${projectId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify(projectData)
            });

            if (response.ok) {
                // Đóng modal
                bootstrap.Modal.getInstance(document.getElementById('editProjectModal')).hide();
                
                // Thông báo thành công
                alert('Cập nhật dự án thành công!');
                
                // Reload danh sách
                this.loadPersonalProjects();
            } else {
                const errorText = await response.text();
                alert('Cập nhật dự án thất bại: ' + errorText);
            }
        } catch (error) {
            console.error('Lỗi cập nhật dự án:', error);
            alert('Lỗi kết nối đến server');
        }
    }
    
    // THÊM PHƯƠNG THỨC HIỂN THỊ TRẠNG THÁI
    getStatusBadge(project) {
        const now = new Date();
        const deadline = project.deadline ? new Date(project.deadline) : null;
        
        // Kiểm tra trạng thái từ backend trước
        if (project.status === 'COMPLETED') {
            return { text: 'Đã hoàn thành', class: 'bg-success' };
        } 
        else if (project.status === 'EXPIRED') {
            return { text: 'Hết hạn', class: 'bg-danger' };
        }
        // Nếu không có status từ backend, tính toán dựa trên deadline
        else if (deadline && deadline < now) {
            return { text: 'Hết hạn', class: 'bg-danger' };
        }
        else {
            return { text: 'Chưa hoàn thành', class: 'bg-warning' };
        }
    }

    async init() {
        if (!this.user) {
            window.location.href = 'login.html';
            return;
        }    

        let accountType = (this.user.accountType || '').toUpperCase();
        const role = (this.user.role || '').toUpperCase();

        console.log('Trang cá nhân - Loại tài khoản: ', accountType);
        console.log('Trang cá nhân - Vai trò: ', role);

        // ✅ SỬA: CHO PHÉP CẢ PERSONAL VÀ TEAM TRUY CẬP TRANG CÁ NHÂN
        // Chỉ chuyển hướng nếu là ENTERPRISE hoặc BUSINESS
        if ((accountType === 'ENTERPRISE' || accountType === 'BUSINESS') && role !== 'EMPLOYEE') {
            console.log('User là ENTERPRISE/BUSINESS, chuyển hướng...');
            window.location.href = 'projects_groups.html';
            return;
        }
        
        // TẢI LỜI MỜI NGAY KHI KHỞI TẠO - QUAN TRỌNG!
        await this.loadMyInvitations();
        
        // Kiểm tra ORGANIZATION trước khi setup giao diện
        await this.checkUserOrganization();

        this.setupEventListeners();
        this.loadPersonalProjects();
        this.checkTrialStatus();
        this.setupInvitationHandlers();
        
        // Kiểm tra lời mời mỗi 30 giây
        setInterval(() => this.loadMyInvitations(), 30000);
        
        // HIỂN THỊ THÔNG BÁO NẾU CÓ LỜI MỜI
        this.showInvitationNotification();
    }
    
    // THÊM PHƯƠNG THỨC HIỂN THỊ THÔNG BÁO LỜI MỜI
    showInvitationNotification() {
        if (this.invitations.length > 0) {
            console.log("🔔 Có", this.invitations.length, "lời mời đang chờ xử lý");
            this.showToast('Bạn có ' + this.invitations.length + ' lời mời tham gia nhóm!', 'info');
        }
    }
    
    // THÊM PHƯƠNG THỨC HIỂN THỊ TOAST THÔNG BÁO
    showToast(message, type = 'info') {
        // Tạo toast container nếu chưa có
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        const toastId = 'toast-' + Date.now();
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        
        // Hiển thị toast
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();
        
        // Xóa toast khi bị ẩn
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
    
    // PHƯƠNG THỨC KIỂM TRA USER CÓ ORGANIZATION HAY KHÔNG - PHIÊN BẢN ĐÃ SỬA
    async checkUserOrganization() {
        try {
            console.log("🔍 Đang kiểm tra thông tin organization của user...");
            
            // 🚨 SỬA QUAN TRỌNG: Dùng API lấy thông tin user thay vì API groups/members
            const response = await fetch('http://localhost:8080/users/current', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                }
            });

            console.log("🔧 Response status:", response.status);
            
            if (response.ok) {
                const userInfo = await response.json();
                console.log("👤 User info từ API /users/current:", userInfo);
                
                // Kiểm tra xem user có organization không
                const hasOrganization = userInfo.organization !== null && userInfo.organization !== undefined;
                
                this.toggleGroupButton(hasOrganization);
                console.log("✅ Kết luận - User có organization:", hasOrganization);
                
                // Cập nhật thông tin user trong localStorage
                if (hasOrganization) {
                    localStorage.setItem('user', JSON.stringify(userInfo));
                    this.user = userInfo;
                }
                
            } else {
                this.toggleGroupButton(false);
                console.log("❌ User không có organization - API trả về lỗi");
            }
        } catch (error) {
            console.error("❌ Lỗi khi kiểm tra organization:", error);
            this.toggleGroupButton(false);
        }
    }

    // PHƯƠNG THỨC HIỆN/ẨN NÚT "ĐẾN TRANG NHÓM"
    toggleGroupButton(hasOrganization) {
        const btnToGroups = document.getElementById('btnToGroups');
        if (btnToGroups) {
            if (hasOrganization) {
                btnToGroups.classList.remove('d-none')
                console.log("✅ HIỆN nút 'Đến trang nhóm'");
            } else {
                btnToGroups.classList.add('d-none');
                console.log("❌ ẨN nút 'Đến trang nhóm'");
            }
        }
    }

    setupInvitationHandlers() {
        // Khi modal lời mời được mở
        document.getElementById('invitationsModal').addEventListener('show.bs.modal', () => {
            this.loadMyInvitations();
        });

        // Nút xem lời mời
        document.getElementById('btnViewInvitations').addEventListener('click', () => {
            this.loadMyInvitations();
        });
    }

    // SỬA QUAN TRỌNG: Sửa endpoint lấy lời mời
    async loadMyInvitations() {
        try {
            console.log("🔔 Đang tải lời mời...");
            console.log("📤 Gửi request đến: http://localhost:8080/groups/invitations/my");
            console.log("🔑 Token:", this.token ? 'Có' : 'Không');
            
            const response = await fetch('http://localhost:8080/groups/invitations/my', {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + this.token,
                    'Content-Type': 'application/json'
                }
            });

            console.log("📥 Response status:", response.status);
            console.log("📥 Response ok:", response.ok);

            // Kiểm tra status trước khi parse JSON
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log("✅ DỮ LIỆU LỜI MỜI NHẬN ĐƯỢC:", data);
            console.log("📋 Số lượng lời mời:", data.length);
            
            this.invitations = data;
            this.renderInvitations();
            this.updateInvitationBadge();
            
        } catch (error) {
            console.error('❌ Lỗi tải lời mời:', error);
            // Hiển thị thông báo cho user
            this.showError('Không thể tải danh sách lời mời: ' + error.message);
        }
    }

    showError(message) {
        console.error('Lỗi:', message);
        // Có thể hiển thị thông báo lỗi trên giao diện
        const errorContainer = document.getElementById('error-container');
        if (errorContainer) {
            errorContainer.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        } else {
            // Fallback
            alert('Lỗi: ' + message);
        }
    }
    
    // SỬA QUAN TRỌNG: Render invitations với invitationId thay vì token
    renderInvitations() {
        const container = document.getElementById('invitationsList');
        
        if (this.invitations.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Không có lời mời nào</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.invitations.map(invitation => {
            const organizationName = invitation.organizationName || 'Tổ chức không xác định';
            const invitedByName = invitation.invitedBy || 'Người dùng';
            const description = invitation.organizationDescription || 'Không có mô tả';
            const projectName = invitation.projectName || 'Tất cả dự án trong nhóm';
            
            console.log("📨 Rendering invitation:", invitation);
            
            return `
                <div class="card mb-3 invitation-card border-primary">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-users me-2"></i>
                        Lời mời tham gia nhóm
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="card-title text-primary">
                                    ${this.escapeHtml(organizationName)}
                                </h6>
                                <p class="card-text mb-2">
                                    <small class="text-muted">
                                        ${this.escapeHtml(description)}
                                    </small>
                                </p>
                                <div class="invitation-details">
                                    <p class="mb-1">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            Được mời bởi: <strong>${this.escapeHtml(invitedByName)}</strong>
                                        </small>
                                    </p>
                                    <p class="mb-1">
                                        <small class="text-muted">
                                            <i class="fas fa-project-diagram me-1"></i>
                                            Dự án: <strong>${this.escapeHtml(projectName)}</strong>
                                        </small>
                                    </p>
                                    <p class="mb-0">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Ngày mời: ${new Date(invitation.invitedAt).toLocaleString('vi-VN')}
                                        </small>
                                    </p>
                                </div>
                            </div>
                            <div class="ms-3">
                                <div class="btn-group-vertical">
                                    <button class="btn btn-success btn-sm" 
                                            onclick="personalProjectManager.acceptInvitation(${invitation.id})">
                                        <i class="fas fa-check me-1"></i> Chấp nhận
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm mt-1" 
                                            onclick="personalProjectManager.declineInvitation(${invitation.id})">
                                        <i class="fas fa-times me-1"></i> Từ chối
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    updateInvitationBadge() {
        const badge = document.getElementById('invitationBadge');
        if (this.invitations.length > 0) {
            badge.textContent = this.invitations.length;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }

    // SỬA QUAN TRỌNG: Sửa endpoint chấp nhận lời mời
    async acceptInvitation(invitationId) {
        try {
            const response = await fetch(`http://localhost:8080/groups/invitations/${invitationId}/accept`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                const result = await response.json();
                alert('🎉 ' + result.message);

                // CẬP NHẬT THÔNG TIN USER
                await this.updateUserOrganizationInfo();
                await this.checkUserOrganization();

                // Đóng modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('invitationsModal'));
                if (modal) modal.hide();

                this.loadMyInvitations();

                // ✅ CHUYỂN HƯỚNG NGAY LẬP TỨC - KHÔNG HỎI
                console.log("🔄 Tự động chuyển hướng đến trang nhóm...");
                setTimeout(() => {
                    window.location.href = 'projects_groups.html';
                }, 1000);
                
            } else {
                const errorText = await response.text();
                alert('Chấp nhận lời mời thất bại: ' + errorText);
            }
        } catch (error) {
            console.error('Lỗi chấp nhận lời mời:', error);
            alert('Lỗi kết nối đến server');
        }
    }

    // THÊM PHƯƠNG THỨC MỚI: CẬP NHẬT THÔNG TIN ORGANIZATION CỦA USER
    async updateUserOrganizationInfo() {
        try {
            // Gọi API để lấy thông tin user mới nhất (có organization)
            const response = await fetch('http://localhost:8080/users/current', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                const updatedUser = await response.json();
                console.log("🔄 Cập nhật thông tin user:", updatedUser);
                
                // CẬP NHẬT LOCALSTORAGE VÀ BIẾN THIS.USER
                localStorage.setItem('user', JSON.stringify(updatedUser));
                this.user = updatedUser;
                
                console.log("✅ Đã cập nhật thông tin organization trong localStorage");
            }
        } catch (error) {
            console.error("❌ Lỗi khi cập nhật thông tin user:", error);
            // Fallback: tự động thêm organization vào user
            this.user.organization = true;
            this.user.organizationId = 1; // hoặc giá trị mặc định
            localStorage.setItem('user', JSON.stringify(this.user));
        }
    }
    
    // SỬA QUAN TRỌNG: Sửa endpoint từ chối lời mời
    async declineInvitation(invitationId) {
        if (!confirm('Bạn có chắc chắn muốn từ chối lời mời này?')) {
            return;
        }

        try {
            const response = await fetch(`http://localhost:8080/groups/invitations/${invitationId}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                alert('✅ Đã từ chối lời mời thành công!');
                this.loadMyInvitations();
            } else {
                const errorText = await response.text();
                alert('Từ chối lời mời thất bại: ' + errorText);
            }
        } catch (error) {
            console.error('❌ Lỗi từ chối lời mời:', error);
            alert('Lỗi kết nối đến server');
        }
    }
    
    checkTrialStatus() {
        // Kiểm tra trial period cho PERSONAL users
        if (this.user.trialExpired) {
            this.disableCreationFeatures();
            this.showTrialExpiredMessage();
        }
    }
    
    showCreateProjectModal() {
        // Set ngày tạo là hiện tại
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('newProjectCreatedDate').value = today;
        
        // Set min date cho deadline là ngày hiện tại
        document.getElementById('newProjectDeadline').min = today;
        
        // Reset form
        document.getElementById('newProjectName').value = '';
        document.getElementById('newProjectDescription').value = '';
        document.getElementById('newProjectDeadline').value = '';
    }

    disableCreationFeatures() {
        document.getElementById('btnCreateProject').classList.add('d-none');
    }

    showTrialExpiredMessage() {
        const container = document.querySelector('.container');
        const alertHTML = `
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>Bản dùng thử đã hết hạn!</strong> 
                Vui lòng nâng cấp để tiếp tục tạo dự án mới.
                <button type="button" class="btn btn-sm btn-outline-primary ms-2" 
                        onclick="window.location.href='pricing.html'">Nâng cấp ngay</button>
            </div>
        `;
        container.insertAdjacentHTML('afterbegin', alertHTML);
    }

    setupEventListeners() {
        // SỬA: Hiển thị nút tạo project cho PERSONAL và TEAM members
        const btnCreateProject = document.getElementById('btnCreateProject');
        if (btnCreateProject) {
            // ✅ SỬA: CHO PHÉP CẢ PERSONAL VÀ TEAM MEMBERS
            const canCreatePersonalProject = 
                this.user.accountType === 'PERSONAL' || 
                (this.user.accountType === 'TEAM' && this.user.role === 'MEMBER');
            
            if (canCreatePersonalProject) {
                btnCreateProject.classList.remove('d-none');
                console.log('✅ HIỆN nút tạo dự án cá nhân - USER: ' + this.user.accountType + ', ROLE: ' + this.user.role);
            } else {
                btnCreateProject.classList.add('d-none');
                console.log('❌ ẨN nút tạo dự án cá nhân - USER: ' + this.user.accountType + ', ROLE: ' + this.user.role);
            }
        }
        // khi modal sắp hiển thị
        const createProjectModal = document.getElementById('createProjectModal');
        if (createProjectModal) {
            createProjectModal.addEventListener('show.bs.modal', () => {
                this.showCreateProjectModal();
            });
        }
        
        // Event tạo project
        const btnSubmitCreateProject = document.getElementById('btnSubmitCreateProject');
        if (btnSubmitCreateProject) {
            btnSubmitCreateProject.addEventListener('click', () => {
                this.createPersonalProject();
            });
        }

        // Tìm kiếm và sắp xếp
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterProjects(e.target.value);
            });
        }

        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.sortProjects(e.target.value);
            });
        }
            
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => {
                this.confirmDeleteProject();
            });
        }
        
        // Event sửa project
        const btnSubmitEditProject = document.getElementById('btnSubmitEditProject');
        if (btnSubmitEditProject) {
            btnSubmitEditProject.addEventListener('click', () => {
                this.updateProject();
            });
        }
    }
    
    // Hàm hiển thị modal xác nhận xóa
    deleteProject(projectId, projectName) {
        this.projectToDelete = projectId;
        document.getElementById('projectToDeleteName').textContent = projectName;
        
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        deleteModal.show();
    }
    
    // Hàm xác nhận xóa
    async confirmDeleteProject() {
        if (!this.projectToDelete) return;

        try {
            const response = await fetch(`/projects/personal/${this.projectToDelete}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.ok) {
                // Đóng modal
                bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
                
                // Hiển thị thông báo thành công
                alert('Xóa dự án thành công!');
                
                // Reload danh sách
                this.loadPersonalProjects();
            } else {
                alert('Xóa dự án thất bại: ' + response.statusText);
            }
        } catch (error) {
            console.error('Lỗi xóa dự án:', error);
            alert('Lỗi kết nối đến server');
        } finally {
            this.projectToDelete = null;
        }
    }
    
    // Thêm vào constructor hoặc methods
    resetCreateForm() {
        document.getElementById('newProjectName').value = '';
        document.getElementById('newProjectDescription').value = '';
        document.getElementById('newProjectDeadline').value = '';
        
        // Set lại ngày tạo là hiện tại
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('newProjectCreatedDate').value = today;
    }
    
    // Sửa hàm renderProjects để thêm nút xóa
    // Sửa hàm renderProjects để thêm trạng thái và nút chuyển đổi
    renderProjects(projects) {
        const container = document.getElementById('projectList');
        
        if (projects.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                        <p>Chưa có dự án nào. Hãy tạo dự án đầu tiên của bạn!</p>
                    </div>
                </div>
            `;
            return;
        }

        container.innerHTML = projects.map(project => {
            // Định dạng ngày
            const createdDate = project.createdAt ? 
                new Date(project.createdAt).toLocaleDateString('vi-VN') : 'Chưa có';
                
            const createdTime = project.createdAt ? 
                new Date(project.createdAt).toLocaleTimeString('vi-VN') : '';

            // Kiểm tra deadline
            const isOverdue = project.deadline && this.isDeadlineOverdue(project.deadline);
            const deadlineClass = isOverdue ? 'text-danger fw-bold' : 'text-muted';
            const deadlineDate = project.deadline ? 
                new Date(project.deadline).toLocaleDateString('vi-VN') : 'Không có';
            const deadlineTime = project.deadline ? 
                new Date(project.deadline).toLocaleTimeString('vi-VN') : '';
            
            // Lấy trạng thái dự án
            const status = this.getStatusBadge(project);

            return `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card project-card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <!-- Header với tên và trạng thái -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title text-truncate me-2" title="${this.escapeHtml(project.name)}">
                                ${this.escapeHtml(project.name)}
                            </h5>
                            <span class="badge ${status.class} align-self-start">${status.text}</span>
                        </div>
                        
                        <!-- Mô tả -->
                        <p class="card-text text-muted small flex-grow-1 mb-3">
                            ${project.description ? this.escapeHtml(project.description) : '<em class="text-muted">Không có mô tả</em>'}
                        </p>
                        
                        <!-- Thông tin chi tiết -->
                        <div class="project-info mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-tasks me-1"></i>
                                    ${project.taskCount || 0} công việc
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-calendar-plus me-1"></i>
                                    ${createdDate}
                                </small>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-hourglass-end me-1"></i>
                                    Hạn hoàn thành:
                                </small>
                                <small class="${deadlineClass}">
                                    ${deadlineDate}
                                    ${isOverdue ? '<i class="fas fa-exclamation-triangle ms-1"></i>' : ''}
                                </small>
                            </div>
                            
                            ${deadlineTime ? `
                            <div class="d-flex justify-content-end">
                                <small class="text-muted">
                                    ${deadlineTime}
                                </small>
                            </div>
                            ` : ''}
                        </div>
                        
                        <!-- Nút hành động -->
                        <div class="d-grid gap-2 mt-auto">
                            <button class="btn btn-primary btn-sm" 
                                    onclick="personalProjectManager.viewProject(${project.id})">
                                <i class="fas fa-folder-open me-1"></i>
                                Mở dự án
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" 
                                    onclick="personalProjectManager.editProject(${project.id})">
                                <i class="fas fa-edit me-1"></i>
                                Sửa
                            </button>
                            <button class="btn btn-outline-danger btn-sm" 
                                    onclick="personalProjectManager.deleteProject(${project.id}, '${this.escapeHtml(project.name)}')">
                                <i class="fas fa-trash me-1"></i>
                                Xóa dự án
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            `;
        }).join('');
    }

    async loadPersonalProjects() {
        try {
            const response = await fetch('/projects/personal', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });
            

            if (response.ok) {
                this.projects = await response.json();
                console.log('DỮ LIỆU NHẬN ĐƯỢC TỪ API:', this.projects);
                if (this.projects.length > 0) {
                    console.log('DỰ ÁN ĐẦU TIÊN:', this.projects[0]);
                    console.log('CÁC TRƯỜNG CÓ TRONG DỰ ÁN:', Object.keys(this.projects[0]));
                }
                this.renderProjects(this.projects);
            }
        } catch (error) {
            console.error('Lỗi tải dự án:', error);
        }
    }

    async createPersonalProject() {
        const name = document.getElementById('newProjectName').value.trim();
        const description = document.getElementById('newProjectDescription').value.trim();
        const deadline = document.getElementById('newProjectDeadline').value;
        const createdDate = document.getElementById('newProjectCreatedDate').value;

        if (!name) {
            alert('Vui lòng nhập tên dự án');
            return;
        }
        
        // Validate deadline không được trước ngày tạo

        if (deadline && deadline < createdDate) {
            alert('Hạn hoàn thành không được trước ngày tạo');
            return;
        }

        try {
            console.log("Dang gui request tao du an...");
            
            // Tạo object dữ liệu với deadline
            const projectData = {
                name: name,
                description: description
            };

            // Thêm deadline nếu có
            if (deadline) {
                projectData.deadlineDate = deadline;
            }
            
            const response = await fetch('/projects/personal', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify(projectData)
            });
            console.log("response status: ", response.status);

            if (response.ok) {
                // Đóng modal và reset form
                bootstrap.Modal.getInstance(document.getElementById('createProjectModal')).hide();
                this.resetCreateForm();
                
                // Reload danh sách
                this.loadPersonalProjects();
            } else {
                const errorText = await response.text();
                console.error("Chi tiết lỗi từ server:", errorText);
                alert('Tạo dự án thất bại' + (errorText || response.statusText));
            }
        } catch (error) {
            console.error('Lỗi tạo dự án:', error);
            alert('Lỗi kết nối đến server');
        }
    }

    filterProjects(searchTerm) {
        const filtered = this.projects.filter(project =>
            project.name.toLowerCase().includes(searchTerm.toLowerCase())
        );
        this.renderProjects(filtered);
    }

    sortProjects(sortType) {
        const sorted = [...this.projects];
        switch (sortType) {
            case 'name-asc':
                sorted.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case 'name-desc':
                sorted.sort((a, b) => b.name.localeCompare(a.name));
                break;
        }
        this.renderProjects(sorted);
    }

    viewProject(projectId) {
        window.location.href = `tasks_details_personal.html?projectId=${projectId}`;
    }
    
    isDeadlineOverdue(deadline) {
        if (!deadline) return false;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const deadlineDate = new Date(deadline);
        deadlineDate.setHours(0, 0, 0, 0);
        return deadlineDate < today;
    }

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

// Khởi tạo khi trang load
let personalProjectManager;
document.addEventListener('DOMContentLoaded', function() {
    personalProjectManager = new PersonalProjectManager();
});