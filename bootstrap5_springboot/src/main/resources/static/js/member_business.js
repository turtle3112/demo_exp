// ==================== CẤU HÌNH CƠ BẢN ====================
const API_BASE_URL = "http://localhost:8080";

window.addEventListener("DOMContentLoaded", () => {

    // ==================== HIỂN THỊ DANH SÁCH USER ====================
    async function loadUsers() {
        const token = localStorage.getItem("token");
        const tbody = document.getElementById("userTableBody");

        if (!token) {
            alert("⚠️ Phiên đăng nhập hết hạn, vui lòng đăng nhập lại!");
            window.location.href = "login.html";
            return;
        }

        try {
            const res = await fetch(`${API_BASE_URL}/users/business-members`, {
                method: "GET",
                headers: {
                    "Authorization": "Bearer " + token
                }
            });

            if (!res.ok) {
                console.error("❌ Lỗi HTTP:", res.status);
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">❌ Lỗi tải danh sách người dùng</td></tr>`;
                }
                return;
            }

            const users = await res.json();

            if (!tbody) {
                console.warn("⚠️ Không tìm thấy element #userTableBody");
                return;
            }

            if (!Array.isArray(users) || users.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Không có tài khoản nào</td></tr>`;
                return;
            }

            tbody.innerHTML = users.map((u, i) => `
                <tr>
                    <td>${i + 1}</td>
                    <td>${u.username}</td>
                    <td>${u.fullName || "—"}</td>
                    <td>${u.employeeId || "—"}</td>
                    <td>${u.email || "—"}</td>
                    <td>${u.role || "—"}</td>
                    <td>
                        ${u.role !== "ADMIN" ? `
                            <button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id})">
                                <i class="fas fa-trash"></i> Xoá
                            </button>` 
                        : '<span class="text-muted">Không thể xoá</span>'}
                    </td>
                </tr>
            `).join("");

        } catch (err) {
            console.error("❌ Lỗi khi tải danh sách user:", err);
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Lỗi kết nối tới máy chủ</td></tr>`;
            }
        }
    }

    // ==================== TẠO NHIỀU TÀI KHOẢN NHÂN VIÊN (BATCH) ====================
    // Hàm này sử dụng API tạo nhiều tài khoản bằng cách gọi API tạo từng tài khoản trong vòng lặp
    window.createMultipleStaffAccounts = async function (baseUsername, accountCount, staffCodePrefix, organizationId) {
        const token = localStorage.getItem('token');
        
        if (!token) {
            alert("Bạn cần đăng nhập lại!");
            window.location.href = 'login.html';
            return { success: false, message: "Chưa đăng nhập" };
        }

        if (!baseUsername || !accountCount) {
            return { success: false, message: "Vui lòng nhập đầy đủ thông tin bắt buộc" };
        }

        if (accountCount > 50) {
            return { success: false, message: "Số lượng tài khoản tối đa là 50" };
        }

        const createdAccounts = [];
        const failedAccounts = [];

        try {
            // Tạo từng tài khoản một trong vòng lặp
            for (let i = 1; i <= accountCount; i++) {
                try {
                    // Tạo username: baseUsername + số thứ tự
                    const username = `${baseUsername}${i}`;
                    
                    // Tạo email dựa trên username
                    const email = `${username}@company.com`; // Có thể tùy chỉnh domain
                    
                    // Tạo employeeId nếu có prefix
                    const employeeId = staffCodePrefix ? 
                        `${staffCodePrefix}${i.toString().padStart(3, '0')}` : 
                        `NV${i.toString().padStart(3, '0')}`;
                    
                    // Gọi API tạo từng tài khoản
                    const result = await createSingleStaffAccountWithPasswordAsEmail(
                        username, email, "Nhân viên " + i, email, employeeId, organizationId
                    );
                    
                    if (result.success) {
                        createdAccounts.push({
                            username: username,
                            password: email, // Mật khẩu là email
                            email: email,
                            employeeId: employeeId
                        });
                    } else {
                        failedAccounts.push({
                            username: username,
                            error: result.message
                        });
                    }
                } catch (error) {
                    failedAccounts.push({
                        username: `${baseUsername}${i}`,
                        error: error.message
                    });
                }
            }

            if (createdAccounts.length > 0) {
                return { 
                    success: true, 
                    createdCount: createdAccounts.length,
                    usernames: createdAccounts.map(acc => acc.username),
                    accounts: createdAccounts,
                    failedCount: failedAccounts.length,
                    failedAccounts: failedAccounts,
                    message: `✅ Đã tạo ${createdAccounts.length} tài khoản thành công${failedAccounts.length > 0 ? `, ${failedAccounts.length} tài khoản thất bại` : ''}`
                };
            } else {
                return { 
                    success: false, 
                    message: "❌ Không thể tạo bất kỳ tài khoản nào",
                    failedAccounts: failedAccounts
                };
            }
        } catch (error) {
            console.error("❌ Lỗi khi tạo tài khoản hàng loạt:", error);
            return { 
                success: false, 
                message: "Có lỗi xảy ra khi tạo tài khoản: " + error.message 
            };
        }
    };

    // ==================== TẠO MỘT TÀI KHOẢN NHÂN VIÊN VỚI MẬT KHẨU LÀ EMAIL ====================
    async function createSingleStaffAccountWithPasswordAsEmail(username, email, fullName, password, employeeId, organizationId) {
        const token = localStorage.getItem('token');
        
        if (!token) {
            return { success: false, message: "Chưa đăng nhập" };
        }

        if (!username || !email) {
            return { success: false, message: "Vui lòng nhập đầy đủ thông tin bắt buộc" };
        }

        // Nếu không có mật khẩu, sử dụng email làm mật khẩu
        const finalPassword = password || email;

        try {
            const requestBody = {
                username: username,
                password: finalPassword, // Sử dụng email làm mật khẩu
                fullName: fullName || username,
                email: email,
                employeeId: employeeId || `NV${Date.now().toString().slice(-3)}`
            };
            
            console.log("Request body:", requestBody);
            
            // Nếu có organizationId, gọi API mới
            if (organizationId) {
                const response = await fetch(`${API_BASE_URL}/api/organizations/${organizationId}/employees`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify(requestBody)
                });

                if (response.ok) {
                    return { 
                        success: true, 
                        message: "✅ Tạo tài khoản nhân viên thành công!" 
                    };
                } else {
                    const errorText = await response.text();
                    console.error("API error:", errorText);
                    throw new Error(errorText || 'Failed to create employee');
                }
            } else {
                // Thử gọi API tạo tài khoản nhân viên (endpoint mới)
                const response = await fetch(`${API_BASE_URL}/users/register-staff`, {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestBody)
                });

                console.log("Response status:", response.status);
                
                if (response.ok) {
                    return { 
                        success: true, 
                        message: "✅ Tạo tài khoản nhân viên thành công!" 
                    };
                } else {
                    const errorText = await response.text();
                    console.error("API error:", errorText);
                    
                    // Thử endpoint khác nếu endpoint trên không hoạt động
                    const response2 = await fetch(`${API_BASE_URL}/users/create`, {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            ...requestBody,
                            role: "EMPLOYEE",
                            accountType: "ENTERPRISE"
                        })
                    });
                    
                    if (response2.ok) {
                        return { 
                            success: true, 
                            message: "✅ Tạo tài khoản nhân viên thành công!" 
                        };
                    } else {
                        const errorText2 = await response2.text();
                        throw new Error(errorText2 || 'Failed to create employee');
                    }
                }
            }

        } catch (error) {
            console.error("❌ Lỗi khi tạo tài khoản:", error);
            return { 
                success: false, 
                message: "Lỗi khi tạo tài khoản: " + error.message 
            };
        }
    }

    // ==================== TẠO MỘT TÀI KHOẢN NHÂN VIÊN (SINGLE) ====================
    window.createSingleStaffAccount = async function (username, password, fullName, email, employeeId, organizationId) {
        return await createSingleStaffAccountWithPasswordAsEmail(username, email, fullName, password, employeeId, organizationId);
    };

    // ==================== GẮN SỰ KIỆN CHO MODAL CŨ ====================
    const confirmBtn = document.getElementById('confirmCreate');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async function () {
            const username = document.getElementById('username')?.value.trim();
            const count = parseInt(document.getElementById('accountCount')?.value || 0);
            const code = document.getElementById('staffCode')?.value.trim();

            if (!username || !count || !code) {
                alert("⚠️ Vui lòng nhập đầy đủ thông tin!");
                return;
            }

            // Lấy organizationId từ user
            const user = JSON.parse(localStorage.getItem('user'));
            const organizationId = user?.organizationId || (user?.organization ? user.organization.id : null);

            const result = await window.createMultipleStaffAccounts(username, count, code, organizationId);
            
            if (result.success) {
                // Hiển thị thông tin tài khoản đã tạo
                let message = `✅ Đã tạo ${result.createdCount} tài khoản:\n`;
                result.accounts.forEach(acc => {
                    message += `\n• Tài khoản: ${acc.username}\n  Mật khẩu: ${acc.password}\n  Email: ${acc.email}\n`;
                });
                
                alert(message);
                
                const modal = document.getElementById('createAccountModal');
                if (modal) modal.style.display = 'none';
                loadUsers(); // Cập nhật lại danh sách
            } else {
                alert(result.message);
            }
        });
    }

    // ==================== MỞ / ĐÓNG FORM TẠO TÀI KHOẢN ====================
    const openModalLink = document.querySelector('a[href="#1"]');
    if (openModalLink) {
        openModalLink.addEventListener('click', function (e) {
            e.preventDefault();
            const modal = document.getElementById('createAccountModal');
            if (modal) modal.style.display = 'flex';
        });
    }

    const closeModalBtn = document.getElementById('closeModal');
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function () {
            const modal = document.getElementById('createAccountModal');
            if (modal) modal.style.display = 'none';
        });
    }

    // ==================== HIỂN THỊ TÊN NGƯỜI DÙNG Ở SIDEBAR ====================
    const user = JSON.parse(localStorage.getItem('user'));
    const sidebarUser = document.getElementById('sidebarUsername');
    if (user && sidebarUser) {
        sidebarUser.innerText = user.fullName || user.username;
    }

    // ==================== GỌI LOAD DANH SÁCH KHI TRANG LOAD ====================
    loadUsers();
    
    // ==================== XOÁ TÀI KHOẢN ====================
    window.deleteUser = async function (id) {
        const token = localStorage.getItem("token");
        if (!token) {
            alert("⚠️ Phiên đăng nhập hết hạn, vui lòng đăng nhập lại!");
            window.location.href = "login.html";
            return;
        }

        if (!confirm("❗ Bạn có chắc chắn muốn xoá tài khoản này không?")) return;

        try {
            // Lấy organizationId từ user (nếu có)
            const user = JSON.parse(localStorage.getItem('user'));
            const organizationId = user?.organizationId || (user?.organization ? user.organization.id : null);
            
            let response;
            
            // Nếu có organizationId, sử dụng API mới
            if (organizationId) {
                response = await fetch(`${API_BASE_URL}/api/organizations/${organizationId}/employees/${id}`, {
                    method: "DELETE",
                    headers: {
                        "Authorization": "Bearer " + token,
                        "Content-Type": "application/json"
                    }
                });
            } else {
                // Sử dụng API cũ
                response = await fetch(`${API_BASE_URL}/users/${id}`, {
                    method: "DELETE",
                    headers: {
                        "Authorization": "Bearer " + token
                    }
                });
            }

            const result = await response.json();

            if (response.ok) {
                alert("✅ " + result.message);
                loadUsers();
            } else {
                alert("❌ " + (result.message || "Không thể xoá tài khoản"));
            }

        } catch (error) {
            console.error("❌ Lỗi khi xoá tài khoản:", error);
            alert("Có lỗi xảy ra khi xoá tài khoản!");
        }
    };

    // ==================== CẬP NHẬT TÀI KHOẢN ====================
    window.updateUser = async function (id, fullName, email, employeeId) {
        const token = localStorage.getItem("token");
        if (!token) {
            alert("⚠️ Phiên đăng nhập hết hạn, vui lòng đăng nhập lại!");
            window.location.href = "login.html";
            return { success: false, message: "Chưa đăng nhập" };
        }

        if (!fullName || !email) {
            return { success: false, message: "Vui lòng nhập đầy đủ thông tin bắt buộc" };
        }

        try {
            // Lấy organizationId từ user (nếu có)
            const user = JSON.parse(localStorage.getItem('user'));
            const organizationId = user?.organizationId || (user?.organization ? user.organization.id : null);
            
            let response;
            const requestBody = {
                fullName: fullName,
                email: email,
                employeeId: employeeId
            };
            
            // Nếu có organizationId, sử dụng API mới
            if (organizationId) {
                response = await fetch(`${API_BASE_URL}/api/organizations/${organizationId}/employees/${id}`, {
                    method: "PUT",
                    headers: {
                        "Authorization": "Bearer " + token,
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(requestBody)
                });
            } else {
                // Sử dụng API cũ
                response = await fetch(`${API_BASE_URL}/users/${id}`, {
                    method: "PUT",
                    headers: {
                        "Authorization": "Bearer " + token,
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(requestBody)
                });
            }

            if (response.ok) {
                return { 
                    success: true, 
                    message: "✅ Cập nhật thông tin nhân viên thành công!" 
                };
            } else {
                const errorData = await response.text();
                throw new Error(errorData || 'Failed to update employee');
            }

        } catch (error) {
            console.error("❌ Lỗi khi cập nhật tài khoản:", error);
            return { 
                success: false, 
                message: "Có lỗi xảy ra khi cập nhật tài khoản: " + error.message 
            };
        }
    };

    // ==================== LẤY DANH SÁCH NHÂN VIÊN THEO ORGANIZATION ====================
    window.loadEmployeesByOrganization = async function (organizationId) {
        const token = localStorage.getItem("token");
        
        if (!token) {
            alert("⚠️ Phiên đăng nhập hết hạn, vui lòng đăng nhập lại!");
            window.location.href = "login.html";
            return { success: false, message: "Chưa đăng nhập", employees: [] };
        }

        try {
            const response = await fetch(`${API_BASE_URL}/api/organizations/${organizationId}/employees`, {
                method: "GET",
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json"
                }
            });
            
            if (response.status === 401) {
                alert('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
                window.location.href = 'login.html';
                return { success: false, message: "Phiên đăng nhập hết hạn", employees: [] };
            }
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
            }
            
            const employees = await response.json();
            return { 
                success: true, 
                message: "✅ Tải danh sách nhân viên thành công",
                employees: employees 
            };
            
        } catch (error) {
            console.error("❌ Lỗi khi tải danh sách nhân viên:", error);
            return { 
                success: false, 
                message: "Có lỗi xảy ra khi tải danh sách nhân viên: " + error.message,
                employees: [] 
            };
        }
    };

    // ==================== KIỂM TRA API ENDPOINTS ====================
    window.testApiEndpoints = async function () {
        const token = localStorage.getItem("token");
        if (!token) return;
        
        console.log("=== Testing API Endpoints ===");
        
        const endpoints = [
            `${API_BASE_URL}/users/create-staff-accounts`,
            `${API_BASE_URL}/users/register-staff`,
            `${API_BASE_URL}/users/create`
        ];
        
        for (const endpoint of endpoints) {
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ test: true })
                });
                console.log(`${endpoint}: ${response.status} ${response.statusText}`);
            } catch (error) {
                console.log(`${endpoint}: ERROR - ${error.message}`);
            }
        }
    };

});