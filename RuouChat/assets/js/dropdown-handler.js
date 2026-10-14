// dropdown-handler.js

// Lấy tham số từ URL
function getDropdownParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        type: params.get('type'),
        country: params.get('country'),
        region: params.get('region'),
        grape: params.get('grape')
    };
}

// Tự động tick checkbox dựa trên URL params (gọi sau khi filter sidebar đã được tạo)
function applyFiltersFromDropdown() {
    const params = getDropdownParams();
    let changed = false;

    const trySetCheckbox = (value, filterAttr) => {
        if (!value) return false;
        // Tìm checkbox có data-filter tương ứng và value chính xác
        const checkbox = document.querySelector(`.filter-item input[data-filter="${filterAttr}"][value="${value.replace(/"/g, '&quot;')}"]`);
        if (checkbox && !checkbox.checked) {
            checkbox.checked = true;
            return true;
        }
        return false;
    };

    if (params.type) changed = trySetCheckbox(params.type, 'wine_type') || changed;
    if (params.country) changed = trySetCheckbox(params.country, 'country') || changed;
    if (params.region) changed = trySetCheckbox(params.region, 'region') || changed;
    if (params.grape) changed = trySetCheckbox(params.grape, 'grape_type') || changed;

    if (changed) {
        // Kích hoạt sự kiện change để lọc sản phẩm
        if (typeof handleFilterChange === 'function') {
            handleFilterChange();
        } else {
            // Nếu handleFilterChange chưa được định nghĩa, có thể gọi trực tiếp applyFiltersAndRender
            console.warn('handleFilterChange chưa sẵn sàng, thử gọi applyFiltersAndRender');
            if (typeof applyFiltersAndRender === 'function') applyFiltersAndRender();
        }
        // Cuộn nhẹ đến khu vực sản phẩm
        setTimeout(() => { window.scrollTo({ top: 450, behavior: 'smooth' }); }, 100);
    }
}

// Khởi tạo: đợi DOM và filter sidebar load xong mới áp dụng
function initDropdownHandler() {
    // Đợi filter sidebar được render (có thể dùng MutationObserver hoặc setTimeout)
    const checkInterval = setInterval(() => {
        if (document.querySelector('.filter-item input[data-filter]')) {
            clearInterval(checkInterval);
            applyFiltersFromDropdown();
        }
    }, 100);
    // Fallback sau 2 giây
    setTimeout(() => clearInterval(checkInterval), 2000);
}

// Tự động chạy khi DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDropdownHandler);
} else {
    initDropdownHandler();
}