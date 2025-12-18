// Cập nhật username trong sidebar
const user = JSON.parse(localStorage.getItem('user'));
if (user) {
    document.getElementById('sidebarUsername').innerText = user.fullName || user.username;
}

let currentDocuments = [];

// THAY THẾ sampleDocuments bằng API call
async function loadUserDocuments() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/attachments/personal', {
            headers: {
                'Authorization': 'Bearer ' + token
            }
        });
        
        if (response.ok) {
            const documents = await response.json();
            currentDocuments = documents; // LƯU VÀO BIẾN TOÀN CỤC
            renderDocuments(documents);
            updateStats(documents);
        } else {
            console.error('Failed to load documents');
        }
    } catch (error) {
        console.error('Error loading documents:', error);
    }
}

// Cập nhật stats
function updateStats(documents) {
    if (!documents || documents.length === 0) {
        // Reset về 0 nếu không có documents
        resetStatsToZero();
        return;
    }

    // 1. Tổng documents
    const totalDocs = documents.length;
    document.getElementById('totalDocuments').textContent = totalDocs;

    // 2. Documents gần đây (7 ngày)
    const recentCount = calculateRecentDocuments(documents);
    document.getElementById('recentDocuments').textContent = recentCount;

    // 3. Dung lượng
    const { usedMB, remainingMB } = calculateStorage(documents);
    document.getElementById('usedStorage').textContent = `${usedMB} MB`;
    document.getElementById('remainingStorage').textContent = `${remainingMB} MB`;
}

function calculateRecentDocuments(documents) {
    const sevenDaysAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
    
    return documents.filter(doc => {
        if (!doc.uploadedAt) return false;
        try {
            const uploadDate = new Date(doc.uploadedAt);
            return uploadDate >= sevenDaysAgo;
        } catch (e) {
            return false;
        }
    }).length;
}

function calculateStorage(documents) {
    const totalBytes = documents.reduce((total, doc) => {
        return total + (doc.fileSize || 0);
    }, 0);
    
    const usedMB = (totalBytes / (1024 * 1024)).toFixed(2);
    const totalAllowedMB = 1000; // 1GB storage
    const remainingMB = Math.max(0, (totalAllowedMB - parseFloat(usedMB))).toFixed(2);
    
    return { usedMB, remainingMB };
}

function resetStatsToZero() {
    document.getElementById('totalDocuments').textContent = '0';
    document.getElementById('recentDocuments').textContent = '0';
    document.getElementById('usedStorage').textContent = '0 MB';
    document.getElementById('remainingStorage').textContent = '1000 MB';
}

// Sau khi upload thành công
function onUploadSuccess() {
    // Trigger custom event để các trang khác biết
    const event = new CustomEvent('documentsUpdated');
    window.dispatchEvent(event);
}

// Trong document_personal.html, lắng nghe event
window.addEventListener('documentsUpdated', function() {
    loadUserDocuments(); // Reload documents
});

// Function to render documents
// Function to render documents - SỬA LẠI
function renderDocuments(documents) {
    const container = document.getElementById('documentsList');
    const emptyState = document.getElementById('emptyState');

    if (!documents || documents.length === 0) {
        container.classList.add('d-none');
        emptyState.classList.remove('d-none');
        return;
    }

    container.classList.remove('d-none');
    emptyState.classList.add('d-none');

    container.innerHTML = documents.map(doc => {
        const fileType = getFileType(doc.fileName);
        const iconClass = `file-icon ${fileType}`;
        const icon = getFileIcon(fileType);
        
        // SỬA: Sử dụng uploadedAt thay vì createdAt
        const fileSize = doc.fileSize ? formatFileSize(doc.fileSize) : 'Đang cập nhật';
        const uploadDate = doc.uploadedAt ? formatDate(doc.uploadedAt) : 'Đang cập nhật';

        return `
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card document-card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="${iconClass}">
                            <i class="fas ${icon}"></i>
                        </div>
                        <span class="badge bg-secondary category-badge">${fileType.toUpperCase()}</span>
                    </div>
                    <h6 class="card-title text-truncate" title="${doc.fileName}">${doc.fileName}</h6>
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">${fileSize}</small>
                            <small class="text-muted">${uploadDate}</small>
                        </div>
                        <div class="document-actions d-grid gap-2">
                            <button class="btn btn-outline-primary btn-sm" 
                                    onclick="previewDocument(${doc.id})">
                                <i class="fas fa-eye me-1"></i> Xem
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="downloadDocument(${doc.id})">
                                <i class="fas fa-download me-1"></i> Tải xuống
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;
    }).join('');
}

// Hàm xác định loại file
function getFileType(fileName) {
    if (!fileName) return 'other';
    
    const ext = fileName.split('.').pop().toLowerCase();
    if (['pdf'].includes(ext)) return 'pdf';
    if (['doc', 'docx'].includes(ext)) return 'doc';
    if (['xls', 'xlsx'].includes(ext)) return 'xls';
    if (['ppt', 'pptx'].includes(ext)) return 'ppt';
    if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'].includes(ext)) return 'img';
    if (['zip', 'rar', '7z'].includes(ext)) return 'zip';
    if (['txt'].includes(ext)) return 'txt';
    return 'other';
}

// Hàm lấy icon
function getFileIcon(fileType) {
    switch(fileType) {
        case 'pdf': return 'fa-file-pdf';
        case 'doc': return 'fa-file-word';
        case 'xls': return 'fa-file-excel';
        case 'ppt': return 'fa-file-powerpoint';
        case 'img': return 'fa-file-image';
        case 'zip': return 'fa-file-archive';
        case 'txt': return 'fa-file-alt';
        default: return 'fa-file';
    }
}

// Hàm format kích thước file
function formatFileSize(bytes) {
    if (!bytes) return 'N/A';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

// Hàm format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('vi-VN');
}

// Function to preview document
// Function to preview document - SỬA LẠI HOÀN TOÀN
async function previewDocument(docId) {
    try {
        const doc = currentDocuments.find(d => d.id === docId);
        if (!doc) {
            alert('Không tìm thấy tài liệu');
            return;
        }
        
        document.getElementById('previewDocumentName').textContent = doc.fileName;
        document.getElementById('infoFileName').textContent = doc.fileName;
        document.getElementById('infoFileType').textContent = getFileType(doc.fileName).toUpperCase();
        document.getElementById('infoFileSize').textContent = doc.fileSize ? formatFileSize(doc.fileSize) : 'Đang cập nhật';
        document.getElementById('infoUploadDate').textContent = doc.uploadedAt ? formatDate(doc.uploadedAt) : 'Đang cập nhật';

        // Xử lý preview
        const previewImage = document.getElementById('previewImage');
        const previewPlaceholder = document.getElementById('previewPlaceholder');
        
        const fileType = getFileType(doc.fileName);
        if (fileType === 'img') {
            // Sử dụng fetch với Authorization header thay vì URL parameter
            const token = localStorage.getItem('token');
            try {
                const response = await fetch(`/attachments/preview/${doc.id}`, {
                    headers: {
                        'Authorization': 'Bearer ' + token
                    }
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const imageUrl = URL.createObjectURL(blob);
                    previewImage.src = imageUrl;
                    previewImage.classList.remove('d-none');
                    previewPlaceholder.classList.add('d-none');
                    
                    // Lưu URL object để cleanup sau này
                    previewImage._blobUrl = imageUrl;
                } else {
                    throw new Error('Không thể tải ảnh');
                }
            } catch (error) {
                console.error('Lỗi tải ảnh:', error);
                previewImage.classList.add('d-none');
                previewPlaceholder.classList.remove('d-none');
            }
        } else {
            previewImage.classList.add('d-none');
            previewPlaceholder.classList.remove('d-none');
            const icon = previewPlaceholder.querySelector('i');
            icon.className = `fas ${getFileIcon(fileType)} fa-5x`;
            previewPlaceholder.className = `file-icon ${fileType}`;
        }

        const modal = new bootstrap.Modal(document.getElementById('documentPreviewModal'));
        modal.show();
    } catch (error) {
        console.error('Error previewing document:', error);
        alert('Không thể xem trước tài liệu');
    }
}

// Initialize page - ĐÃ SỬA
document.addEventListener('DOMContentLoaded', function() {
    loadUserDocuments(); // Gọi API thực
});




// Thêm các biến toàn cục
let currentFilter = '';
let currentSort = 'newest';
let currentSearch = '';

// Khởi tạo event listeners - THÊM VÀO SAU document.addEventListener('DOMContentLoaded')
function initializeEventListeners() {
    // Bộ lọc danh mục
    document.getElementById('filterCategory').addEventListener('change', function(e) {
        currentFilter = e.target.value;
        applyFilters();
    });

    // Sắp xếp
    document.getElementById('sortDocuments').addEventListener('change', function(e) {
        currentSort = e.target.value;
        applyFilters();
    });

    // Tìm kiếm
    document.getElementById('searchDocuments').addEventListener('input', function(e) {
        currentSearch = e.target.value.toLowerCase();
        applyFilters();
    });

    // Nút tìm kiếm
    document.querySelector('#searchDocuments + button').addEventListener('click', function() {
        applyFilters();
    });
}

// Hàm áp dụng tất cả bộ lọc
function applyFilters() {
    let filteredDocs = [...currentDocuments]; // Copy mảng gốc

    // 1. Lọc theo danh mục
    if (currentFilter) {
        filteredDocs = filteredDocs.filter(doc => {
            const fileType = getFileType(doc.fileName);
            return fileType === currentFilter;
        });
    }

    // 2. Lọc theo tìm kiếm
    if (currentSearch) {
        filteredDocs = filteredDocs.filter(doc => 
            doc.fileName.toLowerCase().includes(currentSearch)
        );
    }

    // 3. Sắp xếp
    filteredDocs = sortDocuments(filteredDocs, currentSort);

    // 4. Hiển thị kết quả
    renderDocuments(filteredDocs);
}

// Hàm sắp xếp documents
function sortDocuments(documents, sortType) {
    const sorted = [...documents];
    
    switch(sortType) {
        case 'newest':
            return sorted.sort((a, b) => new Date(b.uploadedAt) - new Date(a.uploadedAt));
        case 'oldest':
            return sorted.sort((a, b) => new Date(a.uploadedAt) - new Date(b.uploadedAt));
        case 'name':
            return sorted.sort((a, b) => a.fileName.localeCompare(b.fileName));
        case 'size':
            return sorted.sort((a, b) => (b.fileSize || 0) - (a.fileSize || 0));
        default:
            return sorted;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadUserDocuments(); // Gọi API thực
    initializeEventListeners(); // THÊM DÒNG NÀY - QUAN TRỌNG!
});
