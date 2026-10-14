// ✅ SỬA ĐƯỜNG DẪN CHUẨN
const API_BASE = '/winecellar/api/';

// ========================
// CALL API (FIX FULL)
// ========================
async function callAPI(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });

        const text = await response.text(); // đọc raw trước

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('❌ API không trả JSON:', text);
            throw new Error('Server trả về dữ liệu không hợp lệ!');
        }

        if (!response.ok) {
            throw new Error(data.error || 'Có lỗi xảy ra!');
        }

        return data;

    } catch (error) {
        console.error('❌ API Error:', error.message);
        return null; // không alert để tránh spam
    }
}

// ========================
// PRODUCTS
// ========================
async function loadProducts(filters = {}) {
    let url = API_BASE + 'products.php?';

    if (filters.category) url += `category=${filters.category}&`;
    if (filters.category_id) url += `category_id=${filters.category_id}&`;
    if (filters.producer_id) url += `producer_id=${filters.producer_id}&`;
    if (filters.min_price) url += `min_price=${filters.min_price}&`;
    if (filters.max_price) url += `max_price=${filters.max_price}&`;
    if (filters.search) url += `search=${encodeURIComponent(filters.search)}&`;
    if (filters.limit) url += `limit=${filters.limit}&`;
    if (filters.page) url += `page=${filters.page}&`;
    if (filters.featured) url += `featured=1&`;

    return await callAPI(url);
}

async function loadProductDetail(slug) {
    if (!slug) return null;
    return await callAPI(API_BASE + `product_detail.php?slug=${slug}`);
}

// ========================
// CART
// ========================
async function getCart() {
    return await callAPI(API_BASE + 'cart.php');
}

async function addToCart(product_id, quantity = 1) {
    if (!product_id) return null;

    return await callAPI(API_BASE + 'cart.php', {
        method: 'POST',
        body: JSON.stringify({
            action: 'add',
            product_id,
            quantity
        })
    });
}

async function updateCart(product_id, quantity) {
    return await callAPI(API_BASE + 'cart.php', {
        method: 'POST',
        body: JSON.stringify({
            action: 'update',
            product_id,
            quantity
        })
    });
}

async function removeFromCart(product_id) {
    return await callAPI(API_BASE + 'cart.php', {
        method: 'POST',
        body: JSON.stringify({
            action: 'remove',
            product_id
        })
    });
}

// ========================
// ORDER
// ========================
async function placeOrder(orderData) {
    if (!orderData) return null;

    return await callAPI(API_BASE + 'order.php', {
        method: 'POST',
        body: JSON.stringify(orderData)
    });
}

// ========================
// AUTH
// ========================
async function login(username, password) {
    if (!username || !password) return null;

    return await callAPI(API_BASE + 'auth.php', {
        method: 'POST',
        body: JSON.stringify({
            action: 'login',
            username,
            password
        })
    });
}

async function register(userData) {
    if (!userData) return null;

    return await callAPI(API_BASE + 'auth.php', {
        method: 'POST',
        body: JSON.stringify({
            action: 'register',
            ...userData
        })
    });
}

// ========================
// CONTACT
// ========================
async function sendContact(data) {
    return await callAPI(API_BASE + 'contact.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });
}

// ========================
// CART COUNT (FIX CRASH)
// ========================
async function updateCartCount() {
    const cart = await getCart();

    // ✅ chống lỗi null
    if (!cart || typeof cart !== 'object') {
        console.warn('Cart rỗng hoặc lỗi');
        return;
    }

    const count = Object.values(cart).reduce((sum, item) => {
        return sum + (item.quantity || 0);
    }, 0);

    const cartCountElem = document.querySelector('.cart-count');
    if (cartCountElem) {
        cartCountElem.innerText = count;
    }
}