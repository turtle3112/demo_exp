const API_BASE_URL = "http://localhost:8080";

class GroupTaskDetailManager {
    constructor() {
        this.user = JSON.parse(localStorage.getItem('user'));
        this.token = localStorage.getItem('token');
        this.taskId = this.getTaskIdFromURL();
        this.projectId = this.getProjectIdFromURL();
        this.task = null;
        this.attachments = [];
        this.comments = [];
        this.itemToDelete = null;
        this.deleteType = null; // 'attachment' or 'comment'
        
        this.init();
    }

    init() {
        if (!this.user || !this.token) {
            window.location.href = 'login.html';
            return;
        }

        if (!this.taskId || !this.projectId) {
            alert('Không tìm thấy thông tin task');
            window.location.href = 'tasks_details_groups.html';
            return;
        }

        this.setupEventListeners();
        this.loadTaskDetails();
        this.loadAttachments();
        this.loadComments();
        
        // Update UI with user info
        $('#currentUsername').text(this.user.fullName || this.user.username);
        $('#sidebarUsername').text(this.user.fullName || this.user.username);
        
        // Update back link
        $('#backToTasksLink').attr('href', `tasks_details_groups.html?projectId=${this.projectId}`);
        
        // Hide settings menu for personal accounts
        if (this.user.accountType === 'PERSONAL') {
            $('.settings-menu-item').hide();
        }
        
        // Toggle sidebar on mobile
        $('#sidebarToggle').click(function() {
            $('.sidebar').toggleClass('active');
            $('.main-content, .navbar-custom').toggleClass('active');
        });
		const backLink = $('#backToTasksLink');
		if (backLink.length) {
		    backLink.attr('href', `tasks_details_groups.html?projectId=${this.projectId}`);
		}
    }

    getTaskIdFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('taskId');
    }

    getProjectIdFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('projectId');
    }

    setupEventListeners() {
        // File upload
        $('#uploadFileForm').on('submit', (e) => this.uploadFile(e));
        
        // Comment submission
        $('#addCommentForm').on('submit', (e) => this.addComment(e));
        
        // Delete confirmation
        $('#confirmDelete').on('click', () => this.confirmDelete());
    }

    async loadTaskDetails() {
        try {
            const response = await fetch(`${API_BASE_URL}/tasks/groups/${this.taskId}`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                this.task = await response.json();
                this.displayTaskInfo();
            } else {
                throw new Error('Không thể tải thông tin task');
            }
        } catch (error) {
            console.error('Lỗi tải thông tin task:', error);
            alert('Lỗi khi tải thông tin task');
        }
    }

    displayTaskInfo() {
        $('#taskTitle').text(this.task.name);
        $('#taskDescription').text(this.task.description || 'Không có mô tả');
        $('#taskPriority').text(this.getPriorityText(this.task.priority));
        
        
        // Dates
        $('#taskStartDate').text(this.task.startDate ? 
            new Date(this.task.startDate).toLocaleString('vi-VN') : 'Chưa có');
        $('#taskDueDate').text(this.task.dueDate ? 
            new Date(this.task.dueDate).toLocaleString('vi-VN') : 'Không có hạn');
    }

    async loadAttachments() {
        try {
            const response = await fetch(`${API_BASE_URL}/attachments/task/${this.taskId}`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                this.attachments = await response.json();
                this.renderAttachments();
            }
        } catch (error) {
            console.error('Lỗi tải tệp đính kèm:', error);
        }
    }

	renderAttachments() {
	    const container = $('#attachmentsList');
	    
	    if (this.attachments.length === 0) {
	        container.html(`
	            <div class="text-center py-4">
	                <i class="fas fa-folder-open fa-2x text-muted mb-3"></i>
	                <p class="text-muted">Chưa có tệp đính kèm nào</p>
	            </div>
	        `);
	        return;
	    }

	    const attachmentsHtml = this.attachments.map(attachment => {
	        const fileSize = this.formatFileSize(attachment.fileSize);
	        const uploadDate = attachment.uploadedAt ? new Date(attachment.uploadedAt).toLocaleString('vi-VN') : 'Không xác định';
	        const uploader = attachment.uploadedBy ? (attachment.uploadedBy.fullName || attachment.uploadedBy.username) : 'Không xác định';
	        
	        // KIỂM TRA QUYỀN XÓA: ADMIN hoặc người upload
	        const canDelete = this.user.role === 'ADMIN' || 
	                         (attachment.uploadedBy && attachment.uploadedBy.id === this.user.id);
	        
	        const deleteButton = canDelete ? 
	            `<button class="btn btn-outline-danger btn-sm" 
	                    onclick="taskDetailManager.deleteAttachment(${attachment.id}, '${this.escapeHtml(attachment.fileName)}')">
	                <i class="fas fa-trash"></i>
	            </button>` :
	            `<button class="btn btn-outline-secondary btn-sm" disabled title="Bạn không có quyền xóa file này">
	                <i class="fas fa-trash"></i>
	            </button>`;
	        
	        return `
	        <div class="attachment-item">
	            <div class="d-flex justify-content-between align-items-start">
	                <div class="flex-grow-1"  style="max-width: 250px;">
	                    <div class="d-flex align-items-center mb-2">
	                        <i class="fas ${this.getFileIcon(attachment.fileName)} text-primary me-2"></i>
	                        <strong class="text-truncate">${this.escapeHtml(attachment.fileName)}</strong>
	                    </div>
	                    <div class="d-flex justify-content-between align-items-center">
	                        <small class="text-muted">${fileSize}</small>
	                        <small class="text-muted">${uploadDate}</small>
	                    </div>
	                    <small class="text-muted">Tải lên bởi: ${this.escapeHtml(uploader)}</small>
	                </div>
	                <div class="ms-2">
	                    <a href="${API_BASE_URL}/attachments/download/${attachment.id}" 
	                       class="btn btn-outline-primary btn-sm me-1" 
	                       download="${attachment.fileName}">
	                        <i class="fas fa-download"></i>
	                    </a>
	                    ${deleteButton}
	                </div>
	            </div>
	        </div>
	        `;
	    }).join('');

	    container.html(attachmentsHtml);
	}

    async uploadFile(e) {
        e.preventDefault();
        
        const fileInput = $('#fileInput')[0];
		console.log('File input:', fileInput.files);
        if (!fileInput.files.length) {
            alert('Vui lòng chọn tệp để tải lên');
            return;
        }

        const formData = new FormData();
		formData.append('file', fileInput.files[0]);

		console.log('Sending to:', `${API_BASE_URL}/attachments/task/${this.taskId}`);
		console.log('FormData entries:');
		for (let pair of formData.entries()) {
		    console.log(pair[0] + ': ', pair[1]);
		}
        for (let i = 0; i < fileInput.files.length; i++) {
            formData.append('file', fileInput.files[i]);
        }
        formData.append('taskId', fileInput.files[0]);

        try {
            const response = await fetch(`${API_BASE_URL}/attachments/task/${this.taskId}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                },
                body: formData
            });

            if (response.ok) {
                $('#fileInput').val('');
                this.loadAttachments();
                this.showSuccess('Tải lên tệp thành công!');
            } else {
                alert('Lỗi khi tải lên tệp');
            }
        } catch (error) {
            console.error('Lỗi tải lên tệp:', error);
            alert('Lỗi kết nối đến server');
        }
    }

    deleteAttachment(attachmentId, fileName) {
        this.itemToDelete = attachmentId;
        this.deleteType = 'attachment';
        $('#deleteConfirmationText').html(`Bạn có chắc chắn muốn xóa tệp <strong>${this.escapeHtml(fileName)}</strong>?`);
        $('#deleteConfirmationModal').modal('show');
    }

    async loadComments() {
        try {
            const response = await fetch(`${API_BASE_URL}/comments/task/${this.taskId}`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (response.ok) {
                this.comments = await response.json();
                this.renderComments();
            }
        } catch (error) {
            console.error('Lỗi tải bình luận:', error);
        }
    }

	renderComments() {
	    const container = $('#commentsList');
	    
	    if (this.comments.length === 0) {
	        container.html(`
	            <div class="text-center py-4">
	                <i class="fas fa-comments fa-2x text-muted mb-3"></i>
	                <p class="text-muted">Chưa có bình luận nào</p>
	            </div>
	        `);
	        return;
	    }

	    const commentsHtml = this.comments.map(comment => {
	        const commentDate = comment.createdAt ? 
	            new Date(comment.createdAt).toLocaleString('vi-VN') : 'Không có ngày';
	        
	        const author = comment.createdBy ? 
	            (comment.createdBy.fullName || comment.createdBy.username) : 'Không xác định';
	        
	        // KIỂM TRA ROLE ADMIN - THÊM BADGE NẾU LÀ ADMIN
	        const isAdmin = comment.createdBy && comment.createdBy.role === 'ADMIN';
	        const adminBadge = isAdmin ? '<span class="badge bg-danger ms-2">Admin</span>' : '';
	        
	        return `
	        <div class="comment-item">
	            <div class="d-flex justify-content-between align-items-start mb-2">
	                <div>
	                    <strong>${this.escapeHtml(author)}</strong>
	                    ${adminBadge}
	                    <small class="text-muted ms-2">${commentDate}</small>
	                </div>
	                <button class="btn btn-outline-danger btn-sm" 
	                        onclick="taskDetailManager.deleteComment(${comment.id}, '${this.escapeHtml(author)}')">
	                    <i class="fas fa-trash"></i>
	                </button>
	            </div>
	            <p class="mb-0">${this.escapeHtml(comment.content)}</p>
	        </div>
	        `;
	    }).join('');

	    container.html(commentsHtml);
	}

    async addComment(e) {
        e.preventDefault();
        
        const content = $('#commentContent').val().trim();
        if (!content) {
            alert('Vui lòng nhập nội dung bình luận');
            return;
        }

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

            if (response.ok) {
                $('#commentContent').val('');
                this.loadComments();
                this.showSuccess('Thêm bình luận thành công!');
            } else {
                alert('Lỗi khi thêm bình luận');
            }
        } catch (error) {
            console.error('Lỗi thêm bình luận:', error);
            alert('Lỗi kết nối đến server');
        }
    }

    deleteComment(commentId, author) {
        this.itemToDelete = commentId;
        this.deleteType = 'comment';
        $('#deleteConfirmationText').html(`Bạn có chắc chắn muốn xóa bình luận của <strong>${this.escapeHtml(author)}</strong>?`);
        $('#deleteConfirmationModal').modal('show');
    }

    async confirmDelete() {
        if (!this.itemToDelete || !this.deleteType) return;

        try {
            let url, method;
            
            if (this.deleteType === 'attachment') {
                url = `${API_BASE_URL}/attachments/${this.itemToDelete}`;
                method = 'DELETE';
            } else if (this.deleteType === 'comment') {
                url = `${API_BASE_URL}/comments/${this.itemToDelete}`;
                method = 'DELETE';
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
                alert('Lỗi khi xóa');
            }
        } catch (error) {
            console.error('Lỗi xóa:', error);
            alert('Lỗi kết nối đến server');
        } finally {
            this.itemToDelete = null;
            this.deleteType = null;
        }
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

    getPriorityText(priority) {
        const prio = priority || 'LOW';
        switch (prio) {
            case 'HIGH': return 'Cao';
            case 'MEDIUM': return 'Trung bình';
            case 'LOW': return 'Thấp';
            default: return prio;
        }
    }

    getFileIcon(fileName) {
        const extension = fileName.split('.').pop().toLowerCase();
        switch (extension) {
            case 'pdf': return 'fa-file-pdf';
            case 'doc': case 'docx': return 'fa-file-word';
            case 'xls': case 'xlsx': return 'fa-file-excel';
            case 'ppt': case 'pptx': return 'fa-file-powerpoint';
            case 'jpg': case 'jpeg': case 'png': case 'gif': return 'fa-file-image';
            case 'zip': case 'rar': return 'fa-file-archive';
            default: return 'fa-file';
        }
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    showSuccess(message) {
        // Có thể thêm toast notification ở đây
        console.log('Success:', message);
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
let taskDetailManager;
$(document).ready(function() {
    taskDetailManager = new GroupTaskDetailManager();
});