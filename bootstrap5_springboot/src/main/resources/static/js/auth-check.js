// auth-check.js - Thêm vào tất cả các trang HTML
class AuthManager {
    constructor() {
        this.token = localStorage.getItem('token');
        this.user = JSON.parse(localStorage.getItem('user') || '{}');
        this.init();
    }

    init() {
        this.checkAuthentication();
        this.setupAjaxHeaders();
        this.setupErrorHandling(); // THÊM: Xử lý lỗi toàn cục
    }

    // THÊM: Thiết lập xử lý lỗi toàn cục
    setupErrorHandling() {
        // Xử lý lỗi WebSocket (nếu có)
        window.addEventListener('error', (event) => {
            if (event.message && event.message.includes('WebSocket')) {
                console.warn('⚠️ WebSocket error (có thể bỏ qua):', event.message);
                event.preventDefault();
                return false;
            }
        });

        // Xử lý lỗi Promise không được catch
        window.addEventListener('unhandledrejection', (event) => {
            console.error('⚠️ Unhandled promise rejection:', event.reason);
            // Ngăn thông báo lỗi mặc định
            event.preventDefault();
        });
    }

    checkAuthentication() {
        console.log('🔐 auth-check.js - Kiểm tra xác thực...');
        console.log('Token exists:', !!this.token);
        console.log('User exists:', !!this.user && Object.keys(this.user).length > 0);
        
        // THÊM: Kiểm tra nếu đang ở trang công khai thì không cần auth
        const currentPage = window.location.pathname.split('/').pop();
        if (this.isPublicPage(currentPage)) {
            console.log('📄 Trang công khai, bỏ qua kiểm tra auth');
            return;
        }
        
        if (!this.token) {
            console.log('❌ Không tìm thấy token, chuyển hướng đến login');
            this.redirectToLogin();
            return;
        }

        // Kiểm tra token hợp lệ bằng API call - VÔ HIỆU HÓA XỬ LÝ LỖI TOÀN CỤC
        const originalErrorHandler = $.ajaxSettings.error;
        $.ajaxSetup({ error: null }); // Tạm thời vô hiệu hóa
        
        $.ajax({
            url: '/users/current',
            method: 'GET',
            headers: { 'Authorization': 'Bearer ' + this.token },
            success: (currentUser) => {
                console.log('✅ Token hợp lệ, user:', currentUser);
                
                // THÊM: Cập nhật user info trong localStorage
                localStorage.setItem('user', JSON.stringify(currentUser));
                this.user = currentUser;
                
                this.validatePageAccess(currentUser);
                
                // Khôi phục error handler
                $.ajaxSetup({ error: originalErrorHandler });
            },
            error: (xhr, status, error) => {
                console.error('❌ Token không hợp lệ:', status, error);
                console.error('Chi tiết lỗi:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText
                });
                
                // THÊM: Xử lý các loại lỗi khác nhau
                if (xhr.status === 0) {
                    console.warn('⚠️ Lỗi kết nối mạng - Có thể server không chạy');
                    // Không đăng xuất ngay, cho phép làm việc offline
                    this.showOfflineWarning();
                } else if (xhr.status === 401 || xhr.status === 403) {
                    this.clearAuthAndRedirect();
                } else if (xhr.status === 500) {
                    console.error('💥 Lỗi server 500');
                    // Vẫn cho tiếp tục nhưng cảnh báo
                    this.showServerErrorWarning();
                    // Vẫn validate với user hiện tại (có thể không có quyền mới nhất)
                    if (this.user && this.user.id) {
                        this.validatePageAccess(this.user);
                    }
                } else {
                    this.clearAuthAndRedirect();
                }
                
                // Khôi phục error handler
                $.ajaxSetup({ error: originalErrorHandler });
            }
        });
    }

    validatePageAccess(currentUser) {
        const currentPage = window.location.pathname.split('/').pop();
        const userRole = currentUser.role;
        const accountType = currentUser.accountType;
        
        console.log(`🔍 Validating access: Page=${currentPage}, Role=${userRole}, AccountType=${accountType}`);

        // THÊM: Kiểm tra trước nếu là trang công khai
        if (this.isPublicPage(currentPage)) {
            console.log('✅ Trang công khai - cho phép truy cập');
            return;
        }

        let hasAccess = false;

        // PERSONAL pages
        if (currentPage.includes('personal')) {
            hasAccess = (accountType === 'PERSONAL') || 
                       (accountType === 'TEAM' && userRole === 'MEMBER');
            console.log(`PERSONAL page check: ${hasAccess}`);
        } 
        // GROUPS pages
        else if (currentPage.includes('groups')) {
            hasAccess = (accountType === 'TEAM' || accountType === 'ENTERPRISE');
            console.log(`GROUPS page check: ${hasAccess}`);
        }
        // BUSINESS pages
        else if (currentPage.includes('business')) {
            hasAccess = accountType === 'ENTERPRISE';
            console.log(`BUSINESS page check: ${hasAccess}`);
        }
        // Các trang chung (dashboard, profile, settings không có suffix)
        else if (this.isCommonPage(currentPage)) {
            hasAccess = true; // Cho phép tất cả user types truy cập
            console.log(`COMMON page: ${currentPage} - cho phép truy cập`);
        }
        // Mặc định cho các trang khác
        else {
            console.warn(`⚠️ Trang không xác định: ${currentPage}, kiểm tra quyền mặc định`);
            // THÊM: Mặc định cho phép nhưng ghi log cảnh báo
            hasAccess = true;
        }

        console.log(`Kết quả kiểm tra: ${hasAccess ? '✅ CÓ quyền' : '❌ KHÔNG có quyền'}`);
        
        if (!hasAccess) {
            this.redirectToDefaultPage(currentUser);
        } else {
            console.log('✅ Quyền truy cập hợp lệ, tiếp tục...');
        }
    }

    // Phương thức mới: Kiểm tra trang công khai
    isPublicPage(page) {
        const publicPages = [
            'login.html',
            'register.html',
            'index.html',
            'forgot-password.html',
            'reset-password.html',
            '404.html',
            '500.html',
            'about.html',
            'contact.html',
            ''
        ];
        return publicPages.some(publicPage => page.includes(publicPage));
    }

    // THÊM: Kiểm tra trang chung
    isCommonPage(page) {
        const commonPages = [
            'dashboard.html',
            'profile.html',
            'settings.html',
            'notifications.html',
            'help.html'
        ];
        return commonPages.some(commonPage => page === commonPage);
    }

    redirectToDefaultPage(user) {
        const accountType = user.accountType;
        const role = user.role;
        const currentPage = window.location.pathname.split('/').pop();
        
        console.log('🔄 auth-check.js - Chuyển hướng trang mặc định...');
        console.log('🔄 Current page:', currentPage);
        console.log('🔄 AccountType:', accountType);
        console.log('🔄 Role:', role);
        
        // DANH SÁCH CÁC TRANG CHO PHÉP THEO ACCOUNT TYPE VÀ ROLE
        const allowedPages = {
            'PERSONAL': [
                'projects_personal.html', 
                'tasks_details_personal.html', 
                'dashboard_personal.html',
                'document_personal.html',
                'profile_personal.html',
                'settings_personal.html'
            ],
            'TEAM': [
                'projects_groups.html', 
                'tasks_details_groups.html', 
                'dashboard_groups.html', 
                'settings_groups.html',
                'document_group.html',
                'profile_groups.html',
                'group_members.html',
                'group_invitations.html'
            ],
            'ENTERPRISE': [
                'project_business.html',
                'projects_business.html',
                'tasks_business.html',
                'dashboard_business.html',
                'settings_business.html',
                'document_business.html',
                'profile_business.html',
                'business_members.html'
            ]
        };
        
        // THÊM: TEAM với role MEMBER được phép vào trang cá nhân
        if (accountType === 'TEAM' && role === 'MEMBER') {
            allowedPages.TEAM = allowedPages.TEAM.concat([
                'projects_personal.html',
                'tasks_details_personal.html',
                'document_personal.html'
            ]);
        }
        
        // THÊM: ENTERPRISE với role EMPLOYEE có thể có các trang riêng
        if (accountType === 'ENTERPRISE') {
            // Có thể thêm logic riêng cho các role trong enterprise
            if (role === 'EMPLOYEE') {
                allowedPages.ENTERPRISE = allowedPages.ENTERPRISE.concat([
                    'tasks_business.html',
                    'dashboard_business.html'
                ]);
            }
        }
        
        // KIỂM TRA: Nếu đang ở trang được phép thì không chuyển hướng
        const pages = allowedPages[accountType] || [];
        const isAllowedPage = pages.some(page => currentPage.includes(page));
        
        if (isAllowedPage) {
            console.log('✅ Đang ở trang được phép - Không chuyển hướng');
            return;
        }
        
        // LOG chi tiết để debug
        console.log(`❌ Trang ${currentPage} không được phép cho ${accountType}`);
        console.log(`Danh sách trang được phép cho ${accountType}:`, pages);
        
        // CHUYỂN HƯỚNG THEO LOẠI TÀI KHOẢN
        let redirectUrl = '';
        
        if (accountType === 'PERSONAL') {
            redirectUrl = 'projects_personal.html';
        } 
        else if (accountType === 'TEAM') {
            redirectUrl = 'projects_groups.html';
        }
        else if (accountType === 'ENTERPRISE') {
            redirectUrl = 'project_business.html';
        }
        else {
            // Mặc định về login nếu không xác định được account type
            redirectUrl = 'login.html';
        }
        
        console.log(`🔄 Chuyển hướng đến: ${redirectUrl}`);
        
        // THÊM: Kiểm tra xem có phải đang ở trang đích không để tránh vòng lặp
        if (currentPage === redirectUrl) {
            console.log('⚠️ Đã ở trang đích, không chuyển hướng nữa');
            return;
        }
        
        // Thêm delay nhỏ để có thể đọc log
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 100);
    }

    // THÊM: Hiển thị cảnh báo offline
    showOfflineWarning() {
        console.log('⚠️ Ứng dụng đang ở chế độ offline');
        // Có thể hiển thị thông báo cho người dùng
        if ($('#offline-warning').length === 0) {
            $('body').append(`
                <div id="offline-warning" class="alert alert-warning alert-dismissible fade show position-fixed bottom-0 end-0 m-3" style="z-index: 9999;">
                    <i class="fas fa-wifi-slash me-2"></i>
                    <strong>Chế độ offline:</strong> Không thể kết nối đến server. Một số tính năng có thể bị hạn chế.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
        }
    }

    // THÊM: Hiển thị cảnh báo lỗi server
    showServerErrorWarning() {
        console.log('⚠️ Lỗi server 500 - NextServer could not exist');
        if ($('#server-error-warning').length === 0) {
            $('body').append(`
                <div id="server-error-warning" class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999; min-width: 300px;">
                    <i class="fas fa-server me-2"></i>
                    <strong>Lỗi server:</strong> Không thể kết nối đến dịch vụ. Vui lòng thử lại sau.
                    <br><small>Nếu lỗi tiếp tục xảy ra, hãy liên hệ quản trị viên.</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
        }
    }

    // Phương thức mới: Xóa thông tin auth và chuyển hướng
    clearAuthAndRedirect() {
        console.log('🧹 auth-check.js - Xóa thông tin xác thực...');
        
        // Xóa tất cả thông tin auth khỏi localStorage
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('selectedProjectId');
        localStorage.removeItem('selectedTaskId');
        
        // Clear ajax headers
        $.ajaxSetup({
            headers: {}
        });
        
        // Chuyển hướng đến trang login
        console.log('🔄 Chuyển hướng đến trang login...');
        
        // Thêm thông báo nếu cần
        if (!window.location.pathname.includes('login.html')) {
            // Lưu URL hiện tại để redirect lại sau khi login (nếu cần)
            sessionStorage.setItem('redirectAfterLogin', window.location.href);
            
            // Hiển thị thông báo
            this.showLogoutMessage();
        }
        
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 100);
    }

    // THÊM: Hiển thị thông báo logout
    showLogoutMessage() {
        // Chỉ hiển thị nếu chưa có thông báo nào
        if ($('#logout-message').length === 0) {
            $('body').append(`
                <div id="logout-message" class="alert alert-info alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999; min-width: 300px;">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Thông báo:</strong> Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            
            // Tự động xóa sau 3 giây
            setTimeout(() => {
                $('#logout-message').alert('close');
            }, 3000);
        }
    }

    // Phương thức mới: Chuyển hướng đến trang login
    redirectToLogin() {
        console.log('🔐 Chuyển hướng đến trang đăng nhập...');
        
        // Lưu URL hiện tại để redirect lại sau khi login
        if (!window.location.pathname.includes('login.html') && 
            !window.location.pathname.includes('register.html')) {
            sessionStorage.setItem('redirectAfterLogin', window.location.href);
        }
        
        // Xóa thông tin auth
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        
        // Chuyển hướng
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 100);
    }

    setupAjaxHeaders() {
        if (this.token) {
            // KHÔNG thiết lập error handler toàn cục
            $.ajaxSetup({
                headers: {
                    'Authorization': 'Bearer ' + this.token
                }
            });
            console.log('✅ Đã thiết lập AJAX headers với token');
        } else {
            console.warn('⚠️ Không có token, không thiết lập AJAX headers');
            $.ajaxSetup({
                headers: {}
            });
        }
    }

    // THÊM: Hiển thị thông báo không có quyền
    showPermissionDeniedMessage() {
        if ($('#permission-denied-message').length === 0) {
            $('body').append(`
                <div id="permission-denied-message" class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999; min-width: 300px;">
                    <i class="fas fa-ban me-2"></i>
                    <strong>Truy cập bị từ chối:</strong> Bạn không có quyền thực hiện thao tác này.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            
            // Tự động xóa sau 5 giây
            setTimeout(() => {
                $('#permission-denied-message').alert('close');
            }, 5000);
        }
    }
    
    // Phương thức tiện ích: Kiểm tra xem user có phải là admin không
    isAdmin() {
        return this.user && this.user.role === 'ADMIN';
    }
    
    // Phương thức tiện ích: Kiểm tra xem user có permission không
    hasPermission(requiredRole) {
        if (!this.user || !this.user.role) return false;
        
        const roleHierarchy = {
            'ADMIN': 3,
            'MANAGER': 2,
            'MEMBER': 1,
            'EMPLOYEE': 1
        };
        
        const userLevel = roleHierarchy[this.user.role] || 0;
        const requiredLevel = roleHierarchy[requiredRole] || 0;
        
        return userLevel >= requiredLevel;
    }
}

// Khởi tạo khi trang load
$(document).ready(() => {
    console.log('📄 auth-check.js - Đang khởi tạo AuthManager...');
    console.log('URL hiện tại:', window.location.href);
    console.log('Pathname:', window.location.pathname);
    
    try {
        new AuthManager();
        console.log('✅ AuthManager đã được khởi tạo thành công');
        
        // THÊM: Kiểm tra và fix hiển thị ngày tháng (nếu có lỗi)
        setTimeout(() => {
            fixDateDisplayIssues();
        }, 1000);
    } catch (error) {
        console.error('❌ Lỗi khi khởi tạo AuthManager:', error);
        // Nếu có lỗi, vẫn chuyển hướng đến login để đảm bảo an toàn
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    }
});

// THÊM: Hàm fix lỗi hiển thị ngày tháng
function fixDateDisplayIssues() {
    console.log('🔧 Đang kiểm tra và sửa lỗi hiển thị ngày tháng...');
    
    // Tìm tất cả các phần tử có thể hiển thị ngày tháng
    $('span, div, td').each(function() {
        const text = $(this).text().trim();
        
        // Kiểm tra các pattern ngày tháng sai
        const datePatterns = [
            /^\d{1,2}\/\d{1,2}\/\d{4}$/, // dd/mm/yyyy
            /^\d{4}-\d{2}-\d{2}$/, // yyyy-mm-dd
            /^\d{1,2}\/\d{1,2}\/\d{2}$/ // dd/mm/yy
        ];
        
        for (const pattern of datePatterns) {
            if (pattern.test(text)) {
                try {
                    const date = new Date(text);
                    if (!isNaN(date.getTime())) {
                        // Format lại ngày tháng
                        const formattedDate = date.toLocaleDateString('vi-VN', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                        
                        // Kiểm tra xem năm có hợp lý không (không phải 2025 nếu chưa đến)
                        const currentYear = new Date().getFullYear();
                        const dateYear = date.getFullYear();
                        
                        if (dateYear > currentYear + 1) {
                            // Năm quá xa trong tương lai, có thể là lỗi
                            console.warn(`⚠️ Phát hiện ngày có thể sai: ${text} -> ${formattedDate}`);
                            // Có thể cập nhật nếu cần
                            // $(this).text(formattedDate);
                        }
                    }
                } catch (e) {
                    console.warn(`⚠️ Không thể parse ngày: ${text}`, e);
                }
            }
        }
    });
}

// Thêm hàm helper toàn cục để kiểm tra auth từ bất kỳ đâu
window.authHelper = {
    checkAuth: function() {
        const token = localStorage.getItem('token');
        const user = localStorage.getItem('user');
        return !!(token && user);
    },
    
    getUserInfo: function() {
        try {
            return JSON.parse(localStorage.getItem('user') || '{}');
        } catch (e) {
            console.error('❌ Lỗi khi parse user info:', e);
            return {};
        }
    },
    
    logout: function() {
        console.log('👋 Đang đăng xuất...');
        localStorage.clear();
        sessionStorage.clear();
        
        // Hiển thị thông báo
        alert('Đã đăng xuất thành công');
        
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 500);
    },
    
    redirectIfNotAuth: function() {
        const token = localStorage.getItem('token');
        if (!token) {
            alert('Vui lòng đăng nhập để tiếp tục');
            window.location.href = 'login.html';
        }
    },
    
    // THÊM: Hàm kiểm tra và sửa lỗi dữ liệu
    fixDataIssues: function() {
        console.log('🔧 Kiểm tra và sửa lỗi dữ liệu...');
        
        // Fix ngày tháng
        fixDateDisplayIssues();
        
        // Kiểm tra và fix các vấn đề khác
        $('*').filter(function() {
            return $(this).text().includes('undefined') || 
                   $(this).text().includes('null') ||
                   $(this).text().includes('NaN');
        }).each(function() {
            console.warn('⚠️ Phát hiện giá trị không hợp lệ:', $(this).text());
        });
    }
};

// THÊM: Tự động fix các vấn đề khi trang load xong
$(window).on('load', function() {
    console.log('🔄 Trang đã load xong, kiểm tra các vấn đề...');
    
    // Đợi thêm một chút để đảm bảo mọi thứ đã render
    setTimeout(() => {
        window.authHelper.fixDataIssues();
    }, 2000);
});