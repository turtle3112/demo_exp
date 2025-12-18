// document_group.js - Phiên bản đã sửa lỗi
const API_BASE_URL = "http://localhost:8080";

class DocumentGroup {
    constructor() {
        this.user = JSON.parse(localStorage.getItem('user'));
        this.token = localStorage.getItem('token');
        this.documents = [];
        this.storageStats = {
            totalDocuments: 0,
            recentDocuments: 0,
            usedStorage: 0,
            remainingStorage: 0,
            maxStorage: 1024 * 1024 * 1024 // 1GB mặc định
        };
        this.init();
    }

    init() {
        if (!this.user || !this.token) {
            window.location.href = 'login.html';
            return;
        }

        this.setupEventListeners();
        this.loadData();
        this.updateUI();
    }

    async loadData() {
        try {
            // Load song song documents và stats
            await Promise.all([
                this.loadDocuments(),
                this.loadStorageStats()
            ]);
        } catch (error) {
            console.error('Lỗi tải dữ liệu:', error);
        }
    }

    async loadDocuments() {
        try {
            // Sử dụng endpoint groups thay vì personal
            const response = await fetch(`${API_BASE_URL}/attachments/groups`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });

            if (response.ok) {
                this.documents = await response.json();
                this.renderDocuments();
            } else {
                this.showEmptyState();
            }
        } catch (error) {
            console.error('Lỗi tải tài liệu nhóm:', error);
            this.showEmptyState();
        }
    }

    async loadStorageStats() {
        try {
            const response = await fetch(`${API_BASE_URL}/attachments/groups/stats`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });

            if (response.ok) {
                this.storageStats = await response.json();
                this.updateStats();
            }
        } catch (error) {
            console.error('Lỗi tải thống kê dung lượng:', error);
            // Sử dụng giá trị mặc định nếu có lỗi
            this.updateStats();
        }
    }

    renderDocuments() {
        const container = $('#documentsList');
        
        if (this.documents.length === 0) {
            this.showEmptyState();
            return;
        }

        $('#emptyState').addClass('d-none');

        const documentsHTML = this.documents.map(doc => {
            const fileType = this.getFileType(doc.fileName);
            const fileIcon = this.getFileIcon(fileType);
            const fileSize = this.formatFileSize(doc.fileSize);
            const uploadDate = this.formatTimeAgo(doc.uploadedAt);
            const uploaderName = doc.uploadedBy ? 
                (doc.uploadedBy.email || doc.uploadedBy.username || 'Không xác định') : 
                'Không xác định';

            // Kiểm tra xem file có phải của user hiện tại không
            const isCurrentUser = doc.uploadedBy && doc.uploadedBy.id === this.user.id;
            const userBadge = isCurrentUser ? 
                '<span class="badge bg-primary ms-1">Của bạn</span>' : '';

            return `
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                    <div class="card document-card h-100 border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom-0 pb-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="file-badge">
                                    <span class="badge bg-light text-dark border">
                                        ${this.getFileExtension(doc.fileName).toUpperCase()}
                                    </span>
                                    ${userBadge}
                                </div>
                                <div class="file-actions">
                                    <button class="btn btn-sm btn-link text-muted p-0 download-document" 
                                            data-id="${doc.id}" data-filename="${doc.fileName}"
                                            title="Tải xuống">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body text-center d-flex flex-column">
                            <div class="file-icon-wrapper mb-3">
                                <div class="file-icon ${fileType} mx-auto">
                                    <i class="${fileIcon} fa-3x"></i>
                                </div>
                            </div>
                            
                            <h6 class="card-title text-truncate" title="${doc.fileName}">
                                ${this.escapeHtml(doc.fileName)}
                            </h6>
                            
                            <div class="document-meta mt-auto">
                                <div class="file-size text-muted small mb-2">
                                    <i class="fas fa-database me-1"></i>
                                    <span>${fileSize}</span>
                                </div>
                                
                                <div class="upload-info">
                                    <div class="uploader text-muted small mb-1">
                                        <i class="fas fa-user me-1"></i>
                                        <span class="text-truncate d-inline-block" style="max-width: 120px;">
                                            ${this.escapeHtml(uploaderName)}
                                        </span>
                                    </div>
                                    <div class="upload-date text-muted small">
                                        <i class="fas fa-clock me-1"></i>
                                        <span>${uploadDate}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent border-top-0 pt-0">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm view-document" 
                                        data-id="${doc.id}">
                                    <i class="fas fa-eye me-1"></i>
                                    Xem chi tiết
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        container.html(documentsHTML);
        this.setupDocumentEvents();
    }

    updateStats() {
        $('#totalDocuments').text(this.storageStats.totalDocuments || 0);
        $('#recentDocuments').text(this.storageStats.recentDocuments || 0);
        $('#usedStorage').text(this.formatFileSize(this.storageStats.usedStorage || 0));
        $('#remainingStorage').text(this.formatFileSize(this.storageStats.remainingStorage || 0));

        // Cập nhật progress bar nếu có
        this.updateStorageProgress();
    }

    updateStorageProgress() {
        const used = this.storageStats.usedStorage || 0;
        const max = this.storageStats.maxStorage || (1024 * 1024 * 1024);
        const percentage = max > 0 ? Math.round((used / max) * 100) : 0;
        
        // Có thể thêm progress bar vào giao diện nếu muốn
        console.log(`Dung lượng đã dùng: ${percentage}%`);
    }

    // THÊM PHƯƠNG THỨC updateUI BỊ THIẾU
    updateUI() {
        $('#sidebarUsername').text(this.user.fullName || this.user.username);
    }

    // THÊM PHƯƠNG THỨC setupEventListeners ĐẦY ĐỦ
    setupEventListeners() {
        $('#bellBtn').on('click', (e) => {
            e.stopPropagation();
            $('#notiBox').toggleClass('show');
        });

        $(document).on('click', (e) => {
            if (!$(e.target).closest('#notiBox, #bellBtn').length) {
                $('#notiBox').removeClass('show');
            }
        });

        $('#searchDocuments').on('input', () => this.filterDocuments());
        $('#filterCategory').on('change', () => this.filterDocuments());
        $('#sortDocuments').on('change', () => this.sortDocuments());
        
        $('.btn-outline-secondary').on('click', () => this.filterDocuments());
    }

    // THÊM PHƯƠNG THỨC setupDocumentEvents BỊ THIẾU
    setupDocumentEvents() {
        // Xem chi tiết
        $('.view-document').on('click', (e) => {
            const docId = $(e.currentTarget).data('id');
            this.previewDocument(docId);
        });

        // Download từ icon
        $('.download-document').on('click', (e) => {
            e.stopPropagation();
            const docId = $(e.currentTarget).data('id');
            const fileName = $(e.currentTarget).data('filename');
            this.downloadDocument(docId, fileName);
        });
    }

    async previewDocument(docId) {
        try {
            const doc = this.documents.find(d => d.id === docId);
            if (!doc) return;

            const uploaderName = doc.uploadedBy ? 
                (doc.uploadedBy.email || doc.uploadedBy.username || 'Không xác định') : 
                'Không xác định';
            const uploadDateFull = new Date(doc.uploadedAt).toLocaleDateString('vi-VN');
            const uploadTime = new Date(doc.uploadedAt).toLocaleTimeString('vi-VN');

            $('#previewDocumentName').text(doc.fileName);
            $('#infoFileName').text(doc.fileName);
            $('#infoFileType').text(this.getFileType(doc.fileName).toUpperCase());
            $('#infoFileSize').text(this.formatFileSize(doc.fileSize));
            $('#infoUploadDate').text(`${uploadDateFull} ${uploadTime}`);
            $('#infoUploader').text(uploaderName);

            const fileType = this.getFileType(doc.fileName);
            
            $('#previewImage').addClass('d-none');
            $('#previewPlaceholder').addClass('d-none');

            if (fileType === 'img') {
                await this.loadImageWithAuth(docId);
            } else {
                $('#previewPlaceholder').removeClass('d-none')
                                      .removeClass('pdf doc xls ppt img zip txt other')
                                      .addClass(fileType)
                                      .html(`<i class="${this.getFileIcon(fileType)} fa-5x"></i>`);
            }

            $('.modal-footer .btn-primary').off('click').on('click', () => {
                this.downloadDocument(docId, doc.fileName);
            });

            $('#documentPreviewModal').modal('show');
        } catch (error) {
            console.error('Lỗi xem tài liệu:', error);
            this.showError('Không thể xem tài liệu này');
        }
    }

    async loadImageWithAuth(docId) {
        try {
            const response = await fetch(`${API_BASE_URL}/attachments/preview/${docId}`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });

            if (response.ok) {
                const blob = await response.blob();
                const imageUrl = URL.createObjectURL(blob);
                $('#previewImage').attr('src', imageUrl).removeClass('d-none');
                
                $('#documentPreviewModal').on('hidden.bs.modal', () => {
                    URL.revokeObjectURL(imageUrl);
                });
            } else {
                throw new Error('Không thể tải ảnh');
            }
        } catch (error) {
            console.error('Lỗi tải ảnh:', error);
            $('#previewPlaceholder').removeClass('d-none')
                                  .addClass('img')
                                  .html('<i class="fas fa-file-image fa-5x"></i>');
        }
    }

    async downloadDocument(docId, fileName) {
        try {
            this.showLoading('Đang tải xuống...');
            
            const response = await fetch(`${API_BASE_URL}/attachments/download/${docId}`, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = fileName;
                
                document.body.appendChild(a);
                a.click();
                
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                this.showSuccess('Tải xuống thành công!');
            } else {
                throw new Error('Không thể tải file');
            }
        } catch (error) {
            console.error('Lỗi tải tài liệu:', error);
            this.showError('Không thể tải tài liệu này');
        } finally {
            this.hideLoading();
        }
    }

    filterDocuments() {
        const searchTerm = $('#searchDocuments').val().toLowerCase();
        const categoryFilter = $('#filterCategory').val();
        
        const filtered = this.documents.filter(doc => {
            const matchesSearch = doc.fileName.toLowerCase().includes(searchTerm);
            const matchesCategory = !categoryFilter || this.getFileType(doc.fileName) === categoryFilter;
            return matchesSearch && matchesCategory;
        });
        
        this.renderFilteredDocuments(filtered);
    }

    renderFilteredDocuments(filteredDocuments) {
        const container = $('#documentsList');
        
        if (filteredDocuments.length === 0) {
            container.html(`
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Không tìm thấy tài liệu phù hợp</h5>
                        <p class="text-muted">Thử điều chỉnh từ khóa tìm kiếm hoặc bộ lọc</p>
                    </div>
                </div>
            `);
            return;
        }

        const documentsHTML = filteredDocuments.map(doc => {
            const fileType = this.getFileType(doc.fileName);
            const fileIcon = this.getFileIcon(fileType);
            const fileSize = this.formatFileSize(doc.fileSize);
            const uploadDate = this.formatTimeAgo(doc.uploadedAt);
            const uploaderName = doc.uploadedBy ? 
                (doc.uploadedBy.email || doc.uploadedBy.username || 'Không xác định') : 
                'Không xác định';

            const isCurrentUser = doc.uploadedBy && doc.uploadedBy.id === this.user.id;
            const userBadge = isCurrentUser ? 
                '<span class="badge bg-primary ms-1">Của bạn</span>' : '';

            return `
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                    <div class="card document-card h-100 border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom-0 pb-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="file-badge">
                                    <span class="badge bg-light text-dark border">
                                        ${this.getFileExtension(doc.fileName).toUpperCase()}
                                    </span>
                                    ${userBadge}
                                </div>
                                <div class="file-actions">
                                    <button class="btn btn-sm btn-link text-muted p-0 download-document" 
                                            data-id="${doc.id}" data-filename="${doc.fileName}"
                                            title="Tải xuống">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body text-center d-flex flex-column">
                            <div class="file-icon-wrapper mb-3">
                                <div class="file-icon ${fileType} mx-auto">
                                    <i class="${fileIcon} fa-3x"></i>
                                </div>
                            </div>
                            
                            <h6 class="card-title text-truncate" title="${doc.fileName}">
                                ${this.escapeHtml(doc.fileName)}
                            </h6>
                            
                            <div class="document-meta mt-auto">
                                <div class="file-size text-muted small mb-2">
                                    <i class="fas fa-database me-1"></i>
                                    <span>${fileSize}</span>
                                </div>
                                
                                <div class="upload-info">
                                    <div class="uploader text-muted small mb-1">
                                        <i class="fas fa-user me-1"></i>
                                        <span class="text-truncate d-inline-block" style="max-width: 120px;">
                                            ${this.escapeHtml(uploaderName)}
                                        </span>
                                    </div>
                                    <div class="upload-date text-muted small">
                                        <i class="fas fa-clock me-1"></i>
                                        <span>${uploadDate}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent border-top-0 pt-0">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm view-document" 
                                        data-id="${doc.id}">
                                    <i class="fas fa-eye me-1"></i>
                                    Xem chi tiết
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        container.html(documentsHTML);
        this.setupDocumentEvents();
    }

    sortDocuments() {
        const sortBy = $('#sortDocuments').val();
        const sorted = [...this.documents].sort((a, b) => {
            switch (sortBy) {
                case 'newest':
                    return new Date(b.uploadedAt) - new Date(a.uploadedAt);
                case 'oldest':
                    return new Date(a.uploadedAt) - new Date(b.uploadedAt);
                case 'name':
                    return a.fileName.localeCompare(b.fileName);
                case 'size':
                    return (b.fileSize || 0) - (a.fileSize || 0);
                default:
                    return 0;
            }
        });
        
        this.renderFilteredDocuments(sorted);
    }

    showEmptyState() {
        $('#documentsList').html('');
        $('#emptyState').removeClass('d-none');
    }

    // Helper methods
    getFileType(fileName) {
        const ext = this.getFileExtension(fileName);
        if (['pdf'].includes(ext)) return 'pdf';
        if (['doc', 'docx'].includes(ext)) return 'doc';
        if (['xls', 'xlsx'].includes(ext)) return 'xls';
        if (['ppt', 'pptx'].includes(ext)) return 'ppt';
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'].includes(ext)) return 'img';
        if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return 'zip';
        if (['txt', 'log', 'md'].includes(ext)) return 'txt';
        return 'other';
    }

    getFileExtension(fileName) {
        return fileName.split('.').pop().toLowerCase();
    }

    getFileIcon(fileType) {
        const icons = {
            'pdf': 'fas fa-file-pdf',
            'doc': 'fas fa-file-word',
            'xls': 'fas fa-file-excel',
            'ppt': 'fas fa-file-powerpoint',
            'img': 'fas fa-file-image',
            'zip': 'fas fa-file-archive',
            'txt': 'fas fa-file-alt',
            'other': 'fas fa-file'
        };
        return icons[fileType] || 'fas fa-file';
    }

    formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

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

    escapeHtml(unsafe) {
        if (unsafe === undefined || unsafe === null) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    showLoading(message) {
        // Implement loading indicator
        console.log('Loading:', message);
    }

    hideLoading() {
        // Hide loading indicator
    }

    showSuccess(message) {
        console.log('Success:', message);
    }

    showError(message) {
        console.error('Error:', message);
        alert(message);
    }
}

// Initialize
let documentGroup;
$(document).ready(function() {
    documentGroup = new DocumentGroup();
});