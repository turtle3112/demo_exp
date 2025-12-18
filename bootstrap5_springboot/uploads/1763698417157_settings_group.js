document.addEventListener("DOMContentLoaded", () => {
  console.log("✅ settings_group.js loaded");

  const form = document.querySelector(".setting-form-wrapper form");
  const emailInput = form.querySelector('input[type="email"]');
  const passwordInputs = form.querySelectorAll('input[type="password"]');
  const currentPasswordInput = passwordInputs[0];
  const newPasswordInput = passwordInputs[1];
  const confirmPasswordInput = passwordInputs[2];

  // ✅ Gán dữ liệu người dùng từ localStorage nếu có
  const user = JSON.parse(localStorage.getItem("user"));
  if (user) {
    emailInput.value = user.email || "";
    document.getElementById("sidebarUsername").innerText = user.fullName || user.username || "";
  }

  // ================== GỬI FORM ==================
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = emailInput.value.trim();
	const currentPassword = currentPasswordInput.value.trim();
    const newPassword = newPasswordInput.value.trim();
    const confirmPassword = confirmPasswordInput.value.trim();

    // ==== Kiểm tra dữ liệu nhập ====
    if (!email) {
      alert("⚠️ Vui lòng nhập email!");
      return;
    }

	if (newPassword) {

		      // 1️⃣ KIỂM TRA ĐỘ DÀI MẬT KHẨU MỚI
		      if (newPassword.length < 8) {
		        alert("❌ Mật khẩu mới phải có ít nhất 8 ký tự!");
		        return;
		      }

		      // 2️⃣ KIỂM TRA KHÔNG TRÙNG MẬT KHẨU CŨ
		      if (!currentPassword) {
		        alert("⚠️ Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu!");
		        return;
		      }

		      if (newPassword === currentPassword) {
		        alert("❌ Mật khẩu mới không được trùng với mật khẩu hiện tại!");
		        return;
		      }

		      // 3️⃣ KIỂM TRA XÁC NHẬN MẬT KHẨU
		      if (newPassword !== confirmPassword) {
		        alert("❌ Mật khẩu xác nhận không khớp!");
		        return;
		      }
		    }

    // ==== Tạo object người dùng ====
    const updatedUser = {
      email,
      password: newPassword,
      confirmPassword: confirmPassword,
    };

    try {
      const token = localStorage.getItem("token");
      if (!token) {
        alert("🚫 Bạn chưa đăng nhập!");
        return;
      }

      // ==== Gửi yêu cầu PUT đến backend ====
      const response = await fetch("http://localhost:8080/users/profile/update", {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          "Authorization": "Bearer " + token,
        },
        body: JSON.stringify(updatedUser),
      });

      if (response.ok) {
        const result = await response.json();
        alert("✅ Cập nhật thông tin thành công!");

        // Cập nhật localStorage (lưu lại thông tin user mới)
        localStorage.setItem("user", JSON.stringify(result.user));

        // Hiển thị lại tên ở sidebar nếu có thay đổi
        document.getElementById("sidebarUsername").innerText =
          result.user.fullName || result.user.username || "";

      } else if (response.status === 400) {
        const error = await response.json();
        alert("⚠️ Lỗi: " + (error.error || "Không thể cập nhật!"));
      } else if (response.status === 403) {
        alert("🚫 Bạn không có quyền thực hiện thao tác này!");
      } else {
        alert("⚠️ Cập nhật thất bại, vui lòng thử lại!");
      }
    } catch (error) {
      console.error("Lỗi khi gửi yêu cầu:", error);
      alert("🚫 Không thể kết nối đến máy chủ!");
    }
  });
});
