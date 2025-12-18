const API_BASE_URL = "http://localhost:8080";

class BusinessTaskDetailManager {
    constructor() {
        console.log("BusinessTaskDetailManager constructor called");
        this.user = JSON.parse(localStorage.getItem('user'));
        this.token = localStorage.getItem('token');
        this.taskId = this.getTaskIdFromURL();
        this.projectId = this.getProjectIdFromURL();
        this.businessId = this.getBusinessIdFromURL();
        this.task = null;
        this.attachments = [];
        this.comments = [];
        this.itemToDelete = null;
        this.deleteType = null;
        
        console.log("Initial values:", {
            user: this.user,
            tokenExists: !!this.token,
            taskId: this.taskId,
            businessId: this.businessId,
            projectId: this.projectId
        });
        
        this.init();
    }

    init() {
        console.log("init() called");
        
        if (!this.user || !this.token) {
            console.error("No user or token found");
            window.location.href = 'login.html';
            return;
        }

        console.log("URL parameters:", {
            fullURL: window.location.href,
            search: window.location.search,
            hash: window.location.hash
        });

        if (!this.taskId) {
            console.error("Task ID is missing");
            alert('Không tìm thấy ID task. Vui lòng quay lại danh sách task.');
            window.location.href = 'tasks_details_business.html';
            return;
        }

        // Business ID is optional in URL
        if (!this.businessId) {
            console.warn("Business ID not found in URL, trying localStorage");
            this.businessId = localStorage.getItem('currentBusinessId') || 
                            localStorage.getItem('lastBusinessId');
        }

        console.log("Final IDs:", {
            taskId: this.taskId,
            businessId: this.businessId
        });

        this.setupEventListeners();
        this.loadTaskDetails();
        this.loadAttachments();
        this.loadComments();
        
        // Update UI with user info
        $('#currentUsername').text(this.user.fullName || this.user.username);
        $('#sidebarUsername').text(this.user.fullName || this.user.username);
        
        // Update back link
        const backUrl = this.businessId 
            ? `tasks_details_business.html?businessId=${this.businessId}`
            : 'tasks_details_business.html';
        $('#backToTasksLink').attr('href', backUrl);
        
        // Mobile sidebar toggle
        $('#sidebarToggle').on('click', function() {
            $('.sidebar').toggleClass('active');
            $('.main-content, .navbar-custom').toggleClass('active');
        });
        
        console.log("init() completed successfully");
    }

    getTaskIdFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        let taskId = urlParams.get('taskId');
        
        // Alternative: check for taskId in hash
        if (!taskId && window.location.hash) {
            const hashMatch = window.location.hash.match(/taskId=([^&]+)/);
            if (hashMatch) {
                taskId = hashMatch[1];
            }
        }
        
        return taskId;
    }

    getProjectIdFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('projectId');
    }

    getBusinessIdFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        let businessId = urlParams.get('businessId');
        
        if (!businessId && window.location.hash) {
            const hashMatch = window.location.hash.match(/businessId=([^&]+)/);
            if (hashMatch) {
                businessId = hashMatch[1];
            }
        }
        
        return businessId;
    }

    setupEventListeners() {
        console.log("Setting up event listeners");
        
        $('#uploadFileForm').on('submit', (e) => {
            e.preventDefault();
            this.uploadFile(e);
        });
        
        $('#addCommentForm').on('submit', (e) => {
            e.preventDefault();
            this.addComment(e);
        });
        
        $('#confirmDelete').on('click', () => this.confirmDelete());
        
        const statusSelect = $('#taskStatusSelect');
        if (statusSelect.length) {
            statusSelect.on('change', (e) => this.updateTaskStatus(e));
        }
        
        $('#logoutBtn').on('click', () => {
            localStorage.clear();
            window.location.href = 'login.html';
        });
    }

    async loadTaskDetails() {
        console.log(`Loading task details for taskId: ${this.taskId}`);
        
        try {
            // Sử dụng endpoint giống như group task, vì backend có thể không phân biệt
            const endpoint = `${API_BASE_URL}/tasks/${this.taskId}`;
            console.log(`Fetching from: ${endpoint}`);
            
            const response = await fetch(endpoint, {
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                }
            });

            console.log(`Response status: ${response.status} ${response.statusText}`);
            
            if (response.ok) {
                this.task = await response.json();
                console.log("Task data received:", this.task);
                this.displayTaskInfo();
            } else if (response.status === 401) {
                console.error("Unauthorized - token may be invalid");
                alert('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
                window.location.href = 'login.html';
            } else if (response.status === 404) {
                console.error(`Task not found: ${this.taskId}`);
                
                // Thử một endpoint khác nếu cần
                await this.tryAlternativeEndpoints();
            } else {
                const errorText = await response.text();
                console.error(`Server error ${response.status}:`, errorText);
                this.showTaskLoadError(`Lỗi server: ${response.status}`);
            }
        } catch (error) {
            console.error('Error loading task details:', error);
            
            if (error.message.includes('Failed to fetch')) {
                this.showTaskLoadError('Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.');
            } else {
                this.showTaskLoadError('Lỗi khi tải thông tin task: ' + error.message);
            }
        }
    }

    async tryAlternativeEndpoints() {
        console.log("Trying alternative endpoints...");
        
        // Thử các endpoint khác nhau
        const endpoints = [
            `${API_BASE_URL}/api/tasks/${this.taskId}`,
            `${API_BASE_URL}/task/${this.taskId}`,
            `${API_BASE_URL}/api/task/${this.taskId}`,
        ];
        
        for (const endpoint of endpoints) {
            try {
                console.log(`Trying alternative endpoint: ${endpoint}`);
                const response = await fetch(endpoint, {
                    headers: {
                        'Authorization': `Bearer ${this.token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    this.task = await response.json();
                    console.log(`Success with endpoint: ${endpoint}`, this.task);
                    
                    // Lưu endpoint thành công cho lần sau
                    localStorage.setItem('taskEndpoint', endpoint.replace(this.taskId, '{id}'));
                    
                    this.displayTaskInfo();
                    return;
                }
            } catch (error) {
                console.log(`Failed with endpoint ${endpoint}:`, error.message);
            }
        }
        
        // Nếu tất cả đều thất bại
        this.showTaskLoadError(`Không tìm thấy task với ID: ${this.taskId}`);
    }

    showTaskLoadError(message = 'Không thể tải thông tin task') {
        const errorHtml = `
            <div class="alert alert-danger mt-3">
                <h4><i class="fas fa-exclamation-triangle"></i> Lỗi</h4>
                <p>${message}</p>
                <p>Task ID: <strong>${this.taskId}</strong></p>
                <div class="mt-3">
                    <button class="btn btn-primary" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </button>
                    <button class="btn btn-secondary ms-2" onclick="location.reload()">
                        <i class="fas fa-redo"></i> Tải lại
                    </button>
                </div>
            </div>
        `;
        
        // Hiển thị lỗi trong task info container
        $('#taskInfoContainer').html(errorHtml);
    }

    displayTaskInfo() {
        if (!this.task) {
            console.error("No task data to display");
            return;
        }
        
        console.log("Displaying task info");
        
        // Basic info
        $('#taskTitle').text(this.task.name || 'Không có tiêu đề');
        $('#taskDescription').text(this.task.description || 'Không có mô tả');
        $('#taskPriority').text(this.getPriorityText(this.task.priority));
        $('#taskStatus').text(this.getStatusText(this.task.status));
        
        // Status badge
        const statusBadge = $('#taskStatusBadge');
        if (statusBadge.length) {
            statusBadge.text(this.getStatusText(this.task.status));
            statusBadge.removeClass().addClass('badge ' + this.getStatusBadgeClass(this.task.status));
        }
        
        // Dates
        $('#taskStartDate').text(this.task.startDate ? 
            this.formatDate(this.task.startDate) : 'Chưa có');
        $('#taskDueDate').text(this.task.dueDate ? 
            this.formatDate(this.task.dueDate) : 'Không có hạn');
        
        // Assignee info
        if (this.task.assignee) {
            $('#taskAssignee').text(this.task.assignee.fullName || this.task.assignee.username || 'Không xác định');
        } else {
            $('#taskAssignee').text('Chưa giao');
        }
        
        // Creator info
        if (this.task.createdBy) {
            $('#taskCreator').text(this.task.createdBy.fullName || this.task.createdBy.username || 'Không xác định');
        }
        
        // Status select
        const statusSelect = $('#taskStatusSelect');
        if (statusSelect.length && this.task.status) {
            statusSelect.val(this.task.status);
        }
        
        // Hiển thị thông tin project/business nếu có
        if (this.task.project) {
            $('#taskProject').text(this.task.project.name || 'Không xác định');
        } else if (this.task.business) {
            $('#taskProject').text(this.task.business.name || 'Không xác định');
        }
        
        console.log("Task info displayed successfully");
    }

    async loadAttachments() {
        console.log(`Loading attachments for task: ${this.taskId}`);
        
        try {
            // Endpoint cho attachments (giống group task)
            const endpoint = `${API_BASE_URL}/attachments/task/${this.taskId}`;
            console.log(`Fetching attachments from: ${endpoint}`);
            
            const response = await fetch(endpoint, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            
            if (response.ok) {
                this.attachments = await response.json();
                console.log(`Loaded ${this.attachments.length} attachments`);
                this.renderAttachments();
            } else if (response.status === 404) {
                console.log("Attachments endpoint returned 404, trying alternatives...");
                await this.tryAlternativeAttachmentEndpoints();
            } else {
                console.warn(`Failed to load attachments: ${response.status}`);
                this.attachments = [];
                this.renderAttachments();
            }
        } catch (error) {
            console.error('Error loading attachments:', error);
            this.attachments = [];
            this.renderAttachments();
        }
    }

    async tryAlternativeAttachmentEndpoints() {
        const endpoints = [
            `${API_BASE_URL}/api/attachments/task/${this.taskId}`,
            `${API_BASE_URL}/attachments/tasks/${this.taskId}`,
            `${API_BASE_URL}/api/attachments/tasks/${this.taskId}`,
        ];
        
        for (const endpoint of endpoints) {
            try {
                console.log(`Trying attachment endpoint: ${endpoint}`);
                const response = await fetch(endpoint, {
                    headers: {
                        'Authorization': `Bearer ${this.token}`
                    }
                });
                
                if (response.ok) {
                    this.attachments = await response.json();
                    console.log(`Success with endpoint: ${endpoint}`);
                    this.renderAttachments();
                    return;
                }
            } catch (error) {
                console.log(`Failed with endpoint ${endpoint}:`, error.message);
            }
        }
        
        // Nếu không tìm thấy endpoint nào
        this.attachments = [];
        this.renderAttachments();
    }

    renderAttachments() {
        const container = $('#attachmentsList');
        
        if (!container.length) {
            console.error("Attachments container not found");
            return;
        }
        
        if (!this.attachments || this.attachments.length === 0) {
            container.html(`
                <div class="text-center py-4">
                    <i class="fas fa-folder-open fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Chưa có tệp đính kèm nào</p>
                </div>
            `);
            return;
        }

        const attachmentsHtml = this.attachments.map((attachment) => {
            const fileSize = this.formatFileSize(attachment.fileSize || 0);
            const uploadDate = attachment.uploadedAt ? 
                this.formatDateTime(attachment.uploadedAt) : 'Không xác định';
            const uploader = attachment.uploadedBy ? 
                (attachment.uploadedBy.fullName || attachment.uploadedBy.username || 'Không xác định') : 
                'Không xác định';
            
            const canDelete = this.user.role === 'ADMIN' || 
                             (attachment.uploadedBy && attachment.uploadedBy.id === this.user.id) ||
                             (this.task && this.task.assignee && this.task.assignee.id === this.user.id);
            
            const deleteButton = canDelete ? 
                `<button class="btn btn-outline-danger btn-sm" 
                        onclick="taskDetailManager.deleteAttachment(${attachment.id}, '${this.escapeHtml(attachment.fileName)}')">
                    <i class="fas fa-trash"></i>
                </button>` :
                `<button class="btn btn-outline-secondary btn-sm" disabled title="Bạn không có quyền xóa file này">
                    <i class="fas fa-trash"></i>
                </button>`;
            
            return `
            <div class="attachment-item mb-3 p-3 border rounded">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1" style="max-width: 250px;">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas ${this.getFileIcon(attachment.fileName)} text-primary me-2"></i>
                            <strong class="text-truncate">${this.escapeHtml(attachment.fileName)}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">${fileSize}</small>
                            <small class="text-muted">${uploadDate}</small>
                        </div>
                        <small class="text-muted">Tải lên bởi: ${this.escapeHtml(uploader)}</small>
                    </div>
                    <div class="ms-3 d-flex align-items-center">
                        <a href="${API_BASE_URL}/attachments/download/${attachment.id}" 
                           class="btn btn-outline-primary btn-sm me-2" 
                           download="${this.escapeHtml(attachment.fileName)}"
                           title="Tải xuống">
                            <i class="fas fa-download"></i>
                        </a>
                        ${deleteButton}
                    </div>
                </div>
            </div>
            `;
        }).join('');

        container.html(attachmentsHtml);
        console.log(`Rendered ${this.attachments.length} attachments`);
    }

    async uploadFile(e) {
        console.log("Upload file called");
        
        const fileInput = $('#fileInput')[0];
        if (!fileInput.files.length) {
            alert('Vui lòng chọn tệp để tải lên');
            return;
        }

        const file = fileInput.files[0];
        console.log(`Selected file: ${file.name} (${file.size} bytes)`);
        
        const formData = new FormData();
        formData.append('file', file);

        try {
            const endpoint = `${API_BASE_URL}/attachments/task/${this.taskId}`;
            console.log(`Uploading to: ${endpoint}`);
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                },
                body: formData
            });

            console.log(`Upload response: ${response.status}`);
            
            if (response.ok) {
                $('#fileInput').val('');
                this.loadAttachments();
                this.showSuccess('Tải lên tệp thành công!');
            } else {
                const errorText = await response.text();
                console.error('Upload failed:', errorText);
                alert('Lỗi khi tải lên tệp: ' + response.statusText);
            }
        } catch (error) {
            console.error('Error uploading file:', error);
            alert('Lỗi kết nối đến server');
        }
    }

    deleteAttachment(attachmentId, fileName) {
        console.log(`Request to delete attachment: ${attachmentId} - ${fileName}`);
        this.itemToDelete = attachmentId;
        this.deleteType = 'attachment';
        $('#deleteConfirmationText').html(`Bạn có chắc chắn muốn xóa tệp <strong>${this.escapeHtml(fileName)}</strong>?`);
        $('#deleteConfirmationModal').modal('show');
    }

    async loadComments() {
        console.log(`Loading comments for task: ${this.taskId}`);
        
        try {
            // Endpoint cho comments (giống group task)
            const endpoint = `${API_BASE_URL}/comments/task/${this.taskId}`;
            console.log(`Fetching comments from: ${endpoint}`);
            
            const response = await fetch(endpoint, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            
            if (response.ok) {
                this.comments = await response.json();
                console.log(`Loaded ${this.comments.length} comments`);
                this.renderComments();
            } else if (response.status === 404) {
                console.log("Comments endpoint returned 404, trying alternatives...");
                await this.tryAlternativeCommentEndpoints();
            } else {
                console.warn(`Failed to load comments: ${response.status}`);
                this.comments = [];
                this.renderComments();
            }
        } catch (error) {
            console.error('Error loading comments:', error);
            this.comments = [];
            this.renderComments();
        }
    }

    async tryAlternativeCommentEndpoints() {
        const endpoints = [
            `${API_BASE_URL}/api/comments/task/${this.taskId}`,
            `${API_BASE_URL}/comments/tasks/${this.taskId}`,
            `${API_BASE_URL}/api/comments/tasks/${this.taskId}`,
        ];
        
        for (const endpoint of endpoints) {
            try {
                console.log(`Trying comment endpoint: ${endpoint}`);
                const response = await fetch(endpoint, {
                    headers: {
                        'Authorization': `Bearer ${this.token}`
                    }
                });
                
                if (response.ok) {
                    this.comments = await response.json();
                    console.log(`Success with endpoint: ${endpoint}`);
                    this.renderComments();
                    return;
                }
            } catch (error) {
                console.log(`Failed with endpoint ${endpoint}:`, error.message);
            }
        }
        
        // Nếu không tìm thấy endpoint nào
        this.comments = [];
        this.renderComments();
    }

    renderComments() {
        const container = $('#commentsList');
        
        if (!container.length) {
            console.error("Comments container not found");
            return;
        }
        
        if (!this.comments || this.comments.length === 0) {
            container.html(`
                <div class="text-center py-4">
                    <i class="fas fa-comments fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Chưa có bình luận nào</p>
                </div>
            `);
            return;
        }

        const sortedComments = [...this.comments].sort((a, b) => 
            new Date(b.createdAt) - new Date(a.createdAt)
        );

        const commentsHtml = sortedComments.map(comment => {
            const commentDate = comment.createdAt ? 
                this.formatDateTime(comment.createdAt) : 'Không có ngày';
            
            const author = comment.createdBy ? 
                (comment.createdBy.fullName || comment.createdBy.username || 'Không xác định') : 
                'Không xác định';
            
            const isAdmin = comment.createdBy && comment.createdBy.role === 'ADMIN';
            const isManager = comment.createdBy && comment.createdBy.role === 'MANAGER';
            
            let roleBadge = '';
            if (isAdmin) {
                roleBadge = '<span class="badge bg-danger ms-1">Admin</span>';
            } else if (isManager) {
                roleBadge = '<span class="badge bg-warning ms-1">Manager</span>';
            }
            
            const canDelete = this.user.role === 'ADMIN' || 
                             (comment.createdBy && comment.createdBy.id === this.user.id) ||
                             (this.task && this.task.assignee && this.task.assignee.id === this.user.id);
            
            const deleteButton = canDelete ? 
                `<button class="btn btn-outline-danger btn-sm ms-2" 
                        onclick="taskDetailManager.deleteComment(${comment.id}, '${this.escapeHtml(author)}')"
                        title="Xóa bình luận">
                    <i class="fas fa-trash"></i>
                </button>` :
                `<button class="btn btn-outline-secondary btn-sm ms-2" disabled title="Bạn không có quyền xóa bình luận này">
                    <i class="fas fa-trash"></i>
                </button>`;
            
            return `
            <div class="comment-item mb-3 p-3 border rounded">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center">
                        <strong class="me-2">${this.escapeHtml(author)}</strong>
                        ${roleBadge}
                        <small class="text-muted ms-2">${commentDate}</small>
                    </div>
                    ${deleteButton}
                </div>
                <p class="mb-0" style="white-space: pre-wrap;">${this.escapeHtml(comment.content)}</p>
            </div>
            `;
        }).join('');

        container.html(commentsHtml);
        console.log(`Rendered ${this.comments.length} comments`);
    }

    async addComment(e) {
        console.log("Add comment called");
        
        const content = $('#commentContent').val().trim();
        if (!content) {
            alert('Vui lòng nhập nội dung bình luận');
            return;
        }

        console.log(`Adding comment: ${content.substring(0, 50)}...`);

        try {
            const response = await fetch(`${API_BASE_URL}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify({
                    content: content,
                    taskId: parseInt(this.taskId)
                })
            });

            console.log(`Add comment response: ${response.status}`);
            
            if (response.ok) {
                $('#commentContent').val('');
                this.loadComments();
                this.showSuccess('Thêm bình luận thành công!');
            } else {
                const errorText = await response.text();
                console.error('Add comment failed:', errorText);
                alert('Lỗi khi thêm bình luận: ' + response.statusText);
            }
        } catch (error) {
            console.error('Error adding comment:', error);
            alert('Lỗi kết nối đến server');
        }
    }

    async updateTaskStatus(e) {
        const newStatus = e.target.value;
        console.log(`Updating task status to: ${newStatus}`);
        
        try {
            // Sử dụng endpoint chung cho status update
            const endpoint = `${API_BASE_URL}/tasks/${this.taskId}/status`;
            console.log(`Updating status at: ${endpoint}`);
            
            const response = await fetch(endpoint, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify({
                    status: newStatus
                })
            });

            if (response.ok) {
                this.task.status = newStatus;
                this.displayTaskInfo();
                this.showSuccess('Cập nhật trạng thái thành công!');
            } else {
                alert('Lỗi khi cập nhật trạng thái');
                e.target.value = this.task.status; // Revert to old value
            }
        } catch (error) {
            console.error('Error updating task status:', error);
            alert('Lỗi kết nối đến server');
            e.target.value = this.task.status; // Revert to old value
        }
    }

    deleteComment(commentId, author) {
        console.log(`Request to delete comment: ${commentId} by ${author}`);
        this.itemToDelete = commentId;
        this.deleteType = 'comment';
        $('#deleteConfirmationText').html(`Bạn có chắc chắn muốn xóa bình luận của <strong>${this.escapeHtml(author)}</strong>?`);
        $('#deleteConfirmationModal').modal('show');
    }

    async confirmDelete() {
        if (!this.itemToDelete || !this.deleteType) {
            console.error("No item to delete");
            return;
        }

        console.log(`Confirming delete: ${this.deleteType} ${this.itemToDelete}`);

        try {
            let url, method;
            
            if (this.deleteType === 'attachment') {
                url = `${API_BASE_URL}/attachments/${this.itemToDelete}`;
                method = 'DELETE';
            } else if (this.deleteType === 'comment') {
                url = `${API_BASE_URL}/comments/${this.itemToDelete}`;
                method = 'DELETE';
            } else {
                console.error("Unknown delete type:", this.deleteType);
                return;
            }

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                $('#deleteConfirmationModal').modal('hide');
                
                if (this.deleteType === 'attachment') {
                    this.loadAttachments();
                    this.showSuccess('Xóa tệp thành công!');
                } else if (this.deleteType === 'comment') {
                    this.loadComments();
                    this.showSuccess('Xóa bình luận thành công!');
                }
            } else {
                const errorText = await response.text();
                console.error('Delete failed:', errorText);
                alert('Lỗi khi xóa: ' + response.statusText);
            }
        } catch (error) {
            console.error('Error during delete:', error);
            alert('Lỗi kết nối đến server');
        } finally {
            this.itemToDelete = null;
            this.deleteType = null;
        }
    }

    // Helper Methods
    formatDate(dateString) {
        if (!dateString) return '';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        } catch (e) {
            console.error("Error formatting date:", dateString, e);
            return dateString;
        }
    }

    formatDateTime(dateString) {
        if (!dateString) return '';
        try {
            const date = new Date(dateString);
            return date.toLocaleString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            console.error("Error formatting datetime:", dateString, e);
            return dateString;
        }
    }

    getStatusBadgeClass(status) {
        switch (status) {
            case 'TODO': return 'bg-secondary';
            case 'IN_PROGRESS': return 'bg-primary';
            case 'REVIEW': return 'bg-warning';
            case 'DONE': return 'bg-success';
            case 'CANCELLED': return 'bg-danger';
            default: return 'bg-secondary';
        }
    }

    getStatusText(status) {
        switch (status) {
            case 'TODO': return 'Chưa bắt đầu';
            case 'IN_PROGRESS': return 'Đang làm';
            case 'REVIEW': return 'Cần review';
            case 'DONE': return 'Hoàn thành';
            case 'CANCELLED': return 'Đã hủy';
            default: return status;
        }
    }

    getPriorityText(priority) {
        if (!priority) return 'Không xác định';
        
        switch (priority) {
            case 'HIGH': return 'Cao';
            case 'MEDIUM': return 'Trung bình';
            case 'LOW': return 'Thấp';
            default: return priority;
        }
    }

    getFileIcon(fileName) {
        if (!fileName) return 'fa-file';
        
        const extension = fileName.split('.').pop().toLowerCase();
        switch (extension) {
            case 'pdf': return 'fa-file-pdf';
            case 'doc': case 'docx': return 'fa-file-word';
            case 'xls': case 'xlsx': return 'fa-file-excel';
            case 'ppt': case 'pptx': return 'fa-file-powerpoint';
            case 'jpg': case 'jpeg': case 'png': case 'gif': case 'bmp': case 'svg':
                return 'fa-file-image';
            case 'zip': case 'rar': case '7z': case 'tar': case 'gz':
                return 'fa-file-archive';
            case 'txt': case 'text': return 'fa-file-alt';
            case 'mp3': case 'wav': case 'ogg': return 'fa-file-audio';
            case 'mp4': case 'avi': case 'mov': case 'wmv': return 'fa-file-video';
            default: return 'fa-file';
        }
    }

    formatFileSize(bytes) {
        if (!bytes || bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    showSuccess(message) {
        console.log('Success:', message);
        
        // Create a simple alert
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" 
                 style="z-index: 1050; min-width: 300px;" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('body').append(alertHtml);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            $('.alert-success').alert('close');
        }, 3000);
    }

    escapeHtml(unsafe) {
        if (unsafe === undefined || unsafe === null) {
            return '';
        }
        return String(unsafe)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

// Global variable for access from HTML onclick handlers
let taskDetailManager;

$(document).ready(function() {
    console.log("DOM ready, initializing BusinessTaskDetailManager");
    
    try {
        taskDetailManager = new BusinessTaskDetailManager();
        console.log("BusinessTaskDetailManager initialized successfully");
    } catch (error) {
        console.error("Failed to initialize BusinessTaskDetailManager:", error);
        
        // Show error message to user
        const errorHtml = `
            <div class="alert alert-danger m-3">
                <h4><i class="fas fa-exclamation-triangle"></i> Lỗi khởi tạo ứng dụng</h4>
                <p>Đã xảy ra lỗi khi khởi tạo ứng dụng. Vui lòng:</p>
                <ol>
                    <li>Kiểm tra console (F12) để xem lỗi chi tiết</li>
                    <li>Đảm bảo bạn đã đăng nhập</li>
                    <li>Thử tải lại trang</li>
                </ol>
                <div class="mt-3">
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-redo"></i> Tải lại trang
                    </button>
                    <button class="btn btn-secondary ms-2" onclick="window.location.href='login.html'">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập lại
                    </button>
                </div>
            </div>
        `;
        
        $('body').html(errorHtml);
    }
});