document.addEventListener("DOMContentLoaded", () => {
  console.log("✅ settings_group.js loaded");

  const form = document.querySelector(".setting-form-wrapper form");
  const emailInput = form.querySelector('input[type="email"]');
  const passwordInputs = form.querySelectorAll('input[type="password"]');
  const currentPasswordInput = passwordInputs[0];
  const newPasswordInput = passwordInputs[1];
  const confirmPasswordInput = passwordInputs[2];

  // ====== API CHECK PASSWORD ======
  async function verifyCurrentPassword(password) {
    const token = localStorage.getItem("token");
    try {
      const response = await fetch("http://localhost:8080/users/profile/check-password", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Authorization": "Bearer " + token,
        },
        body: JSON.stringify({ password }),
      });

      const result = await response.json();
      return result.valid === true;
    } catch (error) {
      console.error("Lỗi verify password:", error);
      return false;
    }
  }

  // ====== KIỂM TRA TỰ ĐỘNG KHI NHẬP XONG MẬT KHẨU HIỆN TẠI ======
  currentPasswordInput.addEventListener("blur", async () => {
    const currentPassword = currentPasswordInput.value.trim();
    if (!currentPassword) return;

    const valid = await verifyCurrentPassword(currentPassword);
    if (!valid) {
      alert("❌ Mật khẩu hiện tại không chính xác!");
    }
  });

  // ====== GÁN DỮ LIỆU NGƯỜI DÙNG ======
  const user = JSON.parse(localStorage.getItem("user"));
  if (user) {
    emailInput.value = user.email || "";
    document.getElementById("sidebarUsername").innerText =
      user.fullName || user.username || "";
  }

  // ====== HÀM KIỂM TRA MẬT KHẨU MỚI ======
  function validateNewPassword(current, newPass, confirm) {
    if (newPass.length < 8) return "❌ Mật khẩu mới phải có ít nhất 8 ký tự!";
    if (newPass === current) return "❌ Mật khẩu mới không được trùng mật khẩu hiện tại!";
    if (newPass !== confirm) return "❌ Mật khẩu xác nhận không khớp!";
    return null; // hợp lệ
  }

  // ================== GỬI FORM ==================
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = emailInput.value.trim();
    const currentPassword = currentPasswordInput.value.trim();
    const newPassword = newPasswordInput.value.trim();
    const confirmPassword = confirmPasswordInput.value.trim();

    // ===== KIỂM TRA NHẬP ĐẦY ĐỦ =====
    const allInputs = [emailInput, currentPasswordInput, newPasswordInput, confirmPasswordInput];
    const anyEmpty = allInputs.some(input => input.value.trim() === "");
    if (anyEmpty) {
      alert("⚠️ Hãy nhập đầy đủ các thông tin!");
      return;
    }

    // ========== KIỂM TRA ĐỔI MẬT KHẨU ==========
    if (newPassword) {
      // Kiểm tra mật khẩu hiện tại
      if (!currentPassword) {
        alert("⚠️ Vui lòng nhập mật khẩu hiện tại!");
        return;
      }

      const isCorrect = await verifyCurrentPassword(currentPassword);
      if (!isCorrect) {
        alert("❌ Mật khẩu hiện tại không chính xác!");
        return;
      }

      // Kiểm tra mật khẩu mới
      const errorMsg = validateNewPassword(currentPassword, newPassword, confirmPassword);
      if (errorMsg) {
        alert(errorMsg);
        return;
      }
    }

    // ==== tạo object gửi lên backend ====
    const updatedUser = { email };
    if (newPassword) {
      updatedUser.password = newPassword;
      updatedUser.confirmPassword = confirmPassword;
    }

    try {
      const token = localStorage.getItem("token");
      if (!token) {
        alert("🚫 Bạn chưa đăng nhập!");
        return;
      }

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

        localStorage.setItem("user", JSON.stringify(result.user));
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
