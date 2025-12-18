const API_BASE_URL = "http://localhost:8080";

class PersonalTaskDetailManager {
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

    // ================== KHỞI TẠO ==================
    init() {
        if (!this.user || !this.token) {
            window.location.href = 'login.html';
            return;
        }

        if (!this.taskId || !this.projectId) {
            alert('Không tìm thấy thông tin công việc');
            window.location.href = 'tasks_details_personal.html';
            return;
        }

        this.setupEventListeners();
        this.loadTaskDetails();
        this.loadAttachments();
        this.loadComments();

        // Hiển thị tên người dùng
        $('#currentUsername').text(this.user.fullName || this.user.username);
        $('#sidebarUsername').text(this.user.fullName || this.user.username);

        // Toggle sidebar trên mobile
        $('#sidebarToggle').click(function () {
            $('.sidebar').toggleClass('active');
            $('.main-content, .navbar-custom').toggleClass('active');
        });
    }

    getTaskIdFromURL() {
        return new URLSearchParams(window.location.search).get('taskId');
    }

    getProjectIdFromURL() {
        return new URLSearchParams(window.location.search).get('projectId');
    }

    setupEventListeners() {
        $('#uploadFileForm').on('submit', (e) => this.uploadFile(e));
        $('#addCommentForm').on('submit', (e) => this.addComment(e));
        $('#confirmDelete').on('click', () => this.confirmDelete());
    }

    // ================== TASK DETAILS ==================
    async loadTaskDetails() {
        try {
            const response = await fetch(`${API_BASE_URL}/tasks/personal/${this.taskId}`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });

            if (response.ok) {
                this.task = await response.json();
                this.displayTaskInfo();
            } else {
                throw new Error('Không thể tải thông tin công việc');
            }
        } catch (error) {
            console.error('Lỗi tải chi tiết công việc:', error);
        }
    }

    displayTaskInfo() {
        $('#taskTitle').text(this.task.name);
        $('#taskDescription').text(this.task.description || 'Không có mô tả');
        $('#taskPriority').text(this.getPriorityText(this.task.priority));
        $('#taskStartDate').text(this.task.startDate ?
            new Date(this.task.startDate).toLocaleString('vi-VN') : 'Chưa có');
        $('#taskDueDate').text(this.task.dueDate ?
            new Date(this.task.dueDate).toLocaleString('vi-VN') : 'Không có hạn');
    }

    getPriorityText(priority) {
        switch (priority) {
            case 'HIGH': return 'Cao 🔴';
            case 'MEDIUM': return 'Trung bình 🟡';
            case 'LOW': return 'Thấp 🟢';
            default: return 'Không xác định';
        }
    }

    // ================== FILE ATTACHMENTS ==================
    async loadAttachments() {
        try {
            const response = await fetch(`${API_BASE_URL}/attachments/task/${this.taskId}`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
            if (response.ok) {
                this.attachments = await response.json();
                this.renderAttachments();
            }
        } catch (error) {
            console.error('Lỗi tải file đính kèm:', error);
        }
    }

	renderAttachments() {
	    const container = document.getElementById('attachmentsList');
	    if (!container) {
	        console.error('Không tìm thấy container attachmentsList');
	        return;
	    }
	    
	    container.innerHTML = ''; // Clear loading message
	    
	    if (this.attachments.length === 0) {
	        container.innerHTML = '<p class="text-muted text-center">Chưa có file đính kèm</p>';
	        return;
	    }
	    
	    this.attachments.forEach(attachment => {
	        const attachmentItem = document.createElement('div');
	        attachmentItem.className = 'attachment-item';
	        attachmentItem.innerHTML = `
	            <div class="d-flex justify-content-between align-items-center">
	                <div  style="max-width: 250px;">
	                    <i class="fas fa-paperclip me-2"></i>
	                    <a href="${API_BASE_URL}/attachments/download/${attachment.id}" 
	                       target="_blank" class="text-decoration-none">
	                        ${this.escapeHtml(attachment.fileName)}
	                    </a>
	                    <small class="text-muted d-block">
	                        Uploaded: ${new Date(attachment.uploadedAt).toLocaleString('vi-VN')}
	                    </small>
	                </div>
	                <button class="btn btn-outline-danger btn-sm" 
	                        onclick="taskDetailManager.deleteAttachment(${attachment.id})">
	                    <i class="fas fa-trash"></i>
	                </button>
	            </div>
	        `;
	        container.appendChild(attachmentItem);
	    });
	}
	
	// Thêm hàm deleteAttachment
	async deleteAttachment(attachmentId) {
	    if (!confirm('Bạn có chắc muốn xóa file này?')) return;
	    
	    try {
	        const response = await fetch(`${API_BASE_URL}/attachments/${attachmentId}`, {
	            method: 'DELETE',
	            headers: { 'Authorization': `Bearer ${this.token}` }
	        });
	        
	        if (response.ok) {
	            this.showSuccess('Đã xóa file thành công!');
	            this.loadAttachments();
	        } else {
	            alert('Lỗi khi xóa file');
	        }
	    } catch (error) {
	        console.error('Lỗi xóa file:', error);
	    }
	}
	
	// Thêm hàm escapeHtml
	escapeHtml(unsafe) {
	    return unsafe
	        .replace(/&/g, "&amp;")
	        .replace(/</g, "&lt;")
	        .replace(/>/g, "&gt;")
	        .replace(/"/g, "&quot;")
	        .replace(/'/g, "&#039;");
	}

	async uploadFile(event) {
	    event.preventDefault();
	    console.log('🎯 UPLOAD FILE FUNCTION CALLED');
	    
	    const fileInput = document.getElementById('fileInput');
	    
	    if (!fileInput.files.length) {
	        alert('Vui lòng chọn file');
	        return;
	    }

	    const file = fileInput.files[0];
	    
	    console.log('📁 File details:', {
	        name: file.name,
	        size: file.size + ' bytes',
	        type: file.type,
	        taskId: this.taskId
	    });

	    const formData = new FormData();
	    formData.append('file', file);

	    // QUAN TRỌNG: Sử dụng endpoint CHÍNH /task/{taskId}
	    const UPLOAD_URL = `${API_BASE_URL}/attachments/task/${this.taskId}`;
	    console.log('🔗 Upload URL:', UPLOAD_URL);
	    
	    try {
	        const response = await fetch(UPLOAD_URL, {
	            method: 'POST',
	            headers: { 
	                'Authorization': `Bearer ${this.token}`
	                // KHÔNG đặt Content-Type
	            },
	            body: formData
	        });

	        console.log('📊 Response status:', response.status);
	        console.log('✅ Response ok:', response.ok);
	        
	        if (response.ok) {
	            const result = await response.json();
	            console.log('🎉 Upload success:', result);
	            this.showSuccess(`Tải file "${result.fileName}" thành công!`);
	            this.loadAttachments();
	            document.getElementById('uploadFileForm').reset();
	        } else {
	            const errorText = await response.text();
	            console.error('❌ Upload failed - Status:', response.status, 'Error:', errorText);
	            alert(`Upload thất bại! Mã lỗi: ${response.status}\n${errorText || 'Vui lòng thử lại'}`);
	        }
	    } catch (error) {
	        console.error('🌐 Network error:', error);
	        alert('Lỗi kết nối đến server: ' + error.message);
	    }
	}

    // ================== COMMENTS ==================
    async loadComments() {
        try {
            const response = await fetch(`${API_BASE_URL}/comments/task/${this.taskId}`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
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
        const container = $('#commentList');
        container.empty();
        if (this.comments.length === 0) {
            container.html('<p class="text-muted fst-italic">Chưa có bình luận</p>');
            return;
        }
        this.comments.forEach(comment => {
            container.append(`
                <div class="border rounded p-2 mb-2 bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>${comment.user.fullName || comment.user.username}</strong>
                        <small class="text-muted">${new Date(comment.createdAt).toLocaleString('vi-VN')}</small>
                    </div>
                    <p class="mb-1">${comment.content}</p>
                    ${comment.user.id === this.user.id ? `
                        <div class="text-end">
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="taskDetailManager.confirmDeleteItem('comment', ${comment.id})">
                                <i class="fas fa-trash-alt"></i> Xóa
                            </button>
                        </div>
                    ` : ''}
                </div>
            `);
        });
    }

    async addComment(event) {
        event.preventDefault();
        const content = $('#commentInput').val().trim();
        if (!content) return alert('Vui lòng nhập nội dung bình luận');

        const data = { content, taskId: this.taskId };
        try {
            const response = await fetch(`${API_BASE_URL}/comments`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            if (response.ok) {
                $('#commentInput').val('');
                this.showSuccess('Đã thêm bình luận!');
                this.loadComments();
            } else {
                alert('Không thể thêm bình luận');
            }
        } catch (error) {
            console.error('Lỗi khi thêm bình luận:', error);
        }
    }

    // ================== DELETE CONFIRM ==================
    confirmDeleteItem(type, id) {
        this.itemToDelete = id;
        this.deleteType = type;
        $('#confirmDeleteModal').modal('show');
    }

    async confirmDelete() {
        const { itemToDelete, deleteType } = this;
        if (!itemToDelete) return;

        const endpoint = deleteType === 'attachment'
            ? `${API_BASE_URL}/attachments/${itemToDelete}`
            : `${API_BASE_URL}/comments/${itemToDelete}`;

        try {
            const response = await fetch(endpoint, {
                method: 'DELETE',
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
            if (response.ok) {
                $('#confirmDeleteModal').modal('hide');
                this.showSuccess('Đã xóa thành công!');
                if (deleteType === 'attachment') this.loadAttachments();
                else this.loadComments();
            } else {
                alert('Không thể xóa');
            }
        } catch (error) {
            console.error('Lỗi khi xóa:', error);
        }
    }

    // ================== HELPER ==================
    showSuccess(message) {
        const alert = $(`
            <div class="alert alert-success position-fixed top-0 end-0 m-3 fade show" role="alert">
                ${message}
            </div>
        `);
        $('body').append(alert);
        setTimeout(() => alert.alert('close'), 2000);
    }
}

// ================== KHỞI TẠO ==================
let taskDetailManager;
$(document).ready(function () {
    taskDetailManager = new PersonalTaskDetailManager();
});
