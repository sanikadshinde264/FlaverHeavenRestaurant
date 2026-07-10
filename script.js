/* ============ Menu Data ============ */
/* Menu items are loaded entirely from the `menu_items` table via api/menu.php,
   so anything the admin adds/edits/deletes in Menu Management shows up here
   automatically. There is no hardcoded fallback list - if the API can't be
   reached, an error state is shown instead of stale/duplicated data. */
let menuItems = [];
let menuLoadFailed = false;
let menuLoading = true;
const foodCategories = ['pizza', 'burgers', 'pasta', 'snacks', 'salads', 'maincourse', 'chefspecial'];
const drinkCategories = ['tea', 'coffee', 'beverages'];
const menuGrid = document.getElementById('menu-grid');

/* Load the live menu from the database (populated/managed by the admin panel). */
async function loadMenuFromServer() {
    try {
        const response = await fetch('api/menu.php');
        const result = await readJsonResponse(response);
        if (response.ok && result.success && Array.isArray(result.data?.items) && result.data.items.length) {
            menuItems = result.data.items.map((item) => ({
                id: item.id,
                type: item.type,
                category: item.category,
                name: item.name,
                description: item.description,
                price: item.price,
                rating: item.rating || 4.5,
                image: item.image || ''
            }));
            menuLoadFailed = false;
        } else {
            menuLoadFailed = true;
        }
    } catch (error) {
        menuLoadFailed = true;
    }
    menuLoading = false;
    renderMenu();
}
const typeButtons = document.querySelectorAll('.menu-toggle-btn');
const categoryButtons = document.querySelectorAll('.filter-btn');
let activeType = 'veg';
let activeCategory = 'pizza';
const fallbackImage = 'Images/Image1.jpg';

/* ============ Cart state ============ */
let cart = []; // { name, price, qty }

function resolveMenuImage(item) {
    const fileName = item?.image?.split('/').pop() || 'placeholder.jpg';
    const folderMap = {
        pizza: 'Pizza',
        burgers: 'Burger',
        pasta: 'Pasta',
        snacks: 'Snacks',
        salads: 'Salad',
        maincourse: item?.type === 'veg' ? 'MainCource' : 'MainCourse',
        chefspecial: 'Chefspecial',
        coffee: 'Coffee',
        tea: 'Tea',
        beverages: 'Beverages',
        desserts: 'Desserts'
    };

    const folder = folderMap[item?.category] || '';

    if (item?.category === 'desserts') {
        return `Images/FoodItems/Desserts/${fileName}`;
    }

    if (item?.type === 'veg' && drinkCategories.includes(item.category)) {
        return `Images/FoodItems/Drink/${folder}/${fileName}`;
    }

    if (item?.type === 'veg' && foodCategories.includes(item.category)) {
        return `Images/FoodItems/Veg/${folder}/${fileName}`;
    }

    if (item?.type === 'nonveg') {
        return `Images/FoodItems/NonVeg/${folder}/${fileName}`;
    }

    return fallbackImage;
}

function updateCategoryVisibility() {
    const isDrinksView = activeType === 'drinks';
    const isDessertsView = activeType === 'desserts';

    categoryButtons.forEach((button) => {
        const category = button.dataset.category;
        const shouldShowFood = foodCategories.includes(category);
        const shouldShowDrink = drinkCategories.includes(category);
        const shouldShow = isDrinksView ? shouldShowDrink : isDessertsView ? false : shouldShowFood;
        button.classList.toggle('is-hidden', !shouldShow);
    });

    if (isDrinksView) {
        if (!drinkCategories.includes(activeCategory)) {
            activeCategory = 'coffee';
        }
    } else if (isDessertsView) {
        activeCategory = 'desserts';
    } else {
        if (!foodCategories.includes(activeCategory)) {
            activeCategory = 'pizza';
        }
    }

    categoryButtons.forEach((button) => button.classList.remove('active'));
    const activeButton = document.querySelector(`.filter-btn[data-category="${activeCategory}"]`);
    if (activeButton && !activeButton.classList.contains('is-hidden')) {
        activeButton.classList.add('active');
    }
}

/* ============ Toast helper ============ */
function showToast(title, message) {
    const stack = document.getElementById('toastStack');
    if (!stack) return;
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<strong>${title}</strong>${message}`;
    stack.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 350);
    }, 3200);
}

/* ============ Action popup helper ============ */
// Small centered popup (not a corner toast) used to guide the user
// between the reservation form and the shared payment box, e.g.
// "please pay first" -> pay -> "now go book your table".
function showActionPopup(title, message, actionLabel, onAction) {
    const overlay = document.getElementById('popupOverlay');
    const titleEl = document.getElementById('popupTitle');
    const messageEl = document.getElementById('popupMessage');
    const actionBtn = document.getElementById('popupActionBtn');
    const closeBtn = document.getElementById('popupCloseBtn');
    if (!overlay || !titleEl || !messageEl || !actionBtn || !closeBtn) return;

    titleEl.textContent = title;
    messageEl.textContent = message;
    actionBtn.textContent = actionLabel;

    const closePopup = () => {
        overlay.classList.remove('open');
    };

    // Clone the buttons so we don't stack duplicate listeners on repeat calls.
    const newActionBtn = actionBtn.cloneNode(true);
    actionBtn.parentNode.replaceChild(newActionBtn, actionBtn);
    newActionBtn.addEventListener('click', () => {
        closePopup();
        if (typeof onAction === 'function') onAction();
    });

    const newCloseBtn = closeBtn.cloneNode(true);
    closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);
    newCloseBtn.addEventListener('click', closePopup);

    overlay.onclick = (event) => {
        if (event.target === overlay) closePopup();
    };

    overlay.classList.add('open');
}

/* ============ Cart logic ============ */
function addItemToCart(name, price) {
    const existing = cart.find((c) => c.name === name);
    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({ name, price, qty: 1 });
    }
    updateCartUI();
}

function removeItemFromCart(name) {
    cart = cart.filter((c) => c.name !== name);
    updateCartUI();
}

function cartTotal() {
    return cart.reduce((sum, c) => sum + c.price * c.qty, 0);
}

function cartCount() {
    return cart.reduce((sum, c) => sum + c.qty, 0);
}

function updateCartUI() {
    const countEl = document.getElementById('cart-count');
    if (countEl) countEl.textContent = cartCount();
    renderCartSummary();

    const cartBtn = document.querySelector('.cart');
    if (cartBtn) {
        cartBtn.classList.remove('cart-bump');
        void cartBtn.offsetWidth;
        cartBtn.classList.add('cart-bump');
    }

    const itemsEl = document.getElementById('cartDrawerItems');
    const totalEl = document.getElementById('cartDrawerTotal');
    if (itemsEl) {
        if (!cart.length) {
            itemsEl.innerHTML = '<p class="cart-drawer-empty">Your cart is empty. Add something delicious!</p>';
        } else {
            itemsEl.innerHTML = cart.map((c) => `
                <div class="cart-drawer-item">
                    <div class="cart-drawer-item-name">
                        ${c.name}
                        <span>Qty ${c.qty} • ₹${c.price * c.qty}</span>
                    </div>
                    <button class="cart-remove-btn" type="button" data-remove="${c.name}">Remove</button>
                </div>
            `).join('');
        }
    }
    if (totalEl) totalEl.textContent = `₹${cartTotal()}`;

    // Auto-select "Food Order" as the payment purpose once there's something
    // in the cart, so the Amount field fills in on its own instead of
    // requiring the user to manually pick it from the dropdown first.
    // Don't override it if a table reservation is already in progress.
    const paymentForSelect = document.getElementById('paymentFor');
    if (paymentForSelect && cart.length > 0 && paymentForSelect.value !== 'table reservation') {
        paymentForSelect.value = 'food order';
    }

    syncPaymentAmount();
}

function openCartDrawer() {
    document.getElementById('cartOverlay')?.classList.add('open');
    document.getElementById('cartDrawer')?.classList.add('open');
}

function closeCartDrawer() {
    document.getElementById('cartOverlay')?.classList.remove('open');
    document.getElementById('cartDrawer')?.classList.remove('open');
}

function goToCheckout() {
    if (!cart.length) {
        showToast('Cart is empty', 'Add a dish before checking out.');
        return;
    }
    closeCartDrawer();
    renderCartSummary();
    document.getElementById('order')?.scrollIntoView({ behavior: 'smooth' });
    showToast('Ready to checkout', `${cartCount()} item(s) • Total ₹${cartTotal()}. Fill in your details below.`);
}

function renderCartSummary() {
    const container = document.getElementById("cartSummary");

    if (cart.length === 0) {
        container.innerHTML = "<p>Your cart is empty.</p>";
        return;
    }

    let html = "<h4>Order Summary</h4>";

    cart.forEach(item => {
        html += `
            <div class="cart-item-row">
                <span class="item-name">${item.name}</span>
                <span class="item-qty">Qty : ${item.qty}</span>
                <span class="item-price">Price : ₹${item.price * item.qty}</span>
            </div>
        `;
    });

    html += `
        <div class="cart-total-row">
            <span>Total</span>
            <span>₹${cartTotal()}</span>
        </div>
    `;

    container.innerHTML = html;
}

/* ============ Menu rendering ============ */
function renderMenu() {
    let filteredItems = [];

    if (activeType === 'desserts') {
        filteredItems = menuItems.filter((item) => item.category === 'desserts');
    } else if (activeType === 'drinks') {
        filteredItems = menuItems.filter((item) => item.category === activeCategory);
    } else {
        filteredItems = menuItems.filter((item) => item.type === activeType && item.category === activeCategory);
    }

    menuGrid.classList.remove('is-visible');

    setTimeout(() => {
        if (menuLoading) {
            menuGrid.innerHTML = '<div class="menu-empty-state">Loading menu...</div>';
        } else if (menuLoadFailed) {
            menuGrid.innerHTML = '<div class="menu-empty-state">We couldn\'t load the menu right now. Please check your connection and try again shortly.</div>';
        } else if (!filteredItems.length) {
            menuGrid.innerHTML = '<div class="menu-empty-state">No dishes match this combination yet. Please try another category or preference.</div>';
        } else {
            menuGrid.innerHTML = filteredItems.map((item) => `
                <article class="menu-card">
                    <div class="menu-card-image">
                        <img src="${resolveMenuImage(item)}" alt="${item.name}" loading="lazy" onerror="this.onerror=null;this.src='${fallbackImage}';">
                    </div>
                    <div class="menu-card-content">
                        <span class="menu-card-badge ${item.type === 'veg' ? 'veg' : 'nonveg'}">${item.type === 'veg' ? '🟢 Veg' : '🔴 Non-Veg'}</span>
                        <h3>${item.name}</h3>
                        <p>${item.description}</p>
                        <div class="menu-card-footer">
                            <span class="menu-card-rating">⭐ ${item.rating.toFixed(1)}</span>
                            <span class="menu-card-price">₹${item.price}</span>
                        </div>
                        <button class="add-to-cart-btn" type="button" data-name="${item.name}" data-price="${item.price}">Add to Cart</button>
                    </div>
                </article>
            `).join('');
        }

        requestAnimationFrame(() => {
            menuGrid.classList.add('is-visible');
        });
    }, 180);
}

typeButtons.forEach((button) => {
    button.addEventListener('click', () => {
        typeButtons.forEach((btn) => btn.classList.remove('active'));
        button.classList.add('active');
        activeType = button.dataset.type;

        if (activeType === 'drinks') {
            activeCategory = 'coffee';
        } else if (activeType === 'desserts') {
            activeCategory = 'desserts';
        } else {
            activeCategory = 'pizza';
        }

        updateCategoryVisibility();
        renderMenu();
    });
});

categoryButtons.forEach((button) => {
    button.addEventListener('click', () => {
        if (button.classList.contains('is-hidden')) return;
        categoryButtons.forEach((btn) => btn.classList.remove('active'));
        button.classList.add('active');
        activeCategory = button.dataset.category;
        renderMenu();
    });
});

menuGrid.addEventListener('click', (event) => {
    const button = event.target.closest('.add-to-cart-btn');
    if (!button) return;
    const name = button.dataset.name;
    const price = Number(button.dataset.price);
    addItemToCart(name, price);
    showToast('Added to cart', `${name} • ₹${price}`);
    button.classList.add('added');
    const originalLabel = button.textContent;
    button.textContent = 'Added ✓';
    setTimeout(() => {
        button.classList.remove('added');
        button.textContent = originalLabel;
    }, 1200);
});

/* ============ Cart drawer wiring ============ */
document.getElementById('cartButton')?.addEventListener('click', openCartDrawer);
document.getElementById('cartCloseBtn')?.addEventListener('click', closeCartDrawer);
document.getElementById('cartOverlay')?.addEventListener('click', closeCartDrawer);
document.getElementById('cartDrawerItems')?.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-remove]');
    if (!btn) return;
    removeItemFromCart(btn.dataset.remove);
});

/* ============ Mobile nav toggle ============ */
const navToggle = document.getElementById('navToggle');
const navRight = document.getElementById('navRight');

navToggle?.addEventListener('click', () => {
    const isOpen = navRight.classList.toggle('open');
    navToggle.classList.toggle('open', isOpen);
    navToggle.setAttribute('aria-expanded', String(isOpen));
});

document.querySelectorAll('.nav-anchor').forEach((link) => {
    link.addEventListener('click', () => {
        navRight?.classList.remove('open');
        navToggle?.classList.remove('open');
        navToggle?.setAttribute('aria-expanded', 'false');
    });
});

/* Highlight active nav link while scrolling */
const sections = ['home', 'menu', 'reservation', 'events', 'order', 'contact']
    .map((id) => document.getElementById(id))
    .filter(Boolean);

function updateActiveNavLink() {
    let currentId = sections[0]?.id;
    const scrollPos = window.scrollY + 120;
    sections.forEach((section) => {
        if (section.offsetTop <= scrollPos) {
            currentId = section.id;
        }
    });
    document.querySelectorAll('.nav-anchor').forEach((link) => {
        link.classList.toggle('active-link', link.getAttribute('href') === `#${currentId}`);
    });
}

window.addEventListener('scroll', updateActiveNavLink);

/* ============ Field validation helper ============ */
// Prevent picking a date in the past for any booking/payment date field.
(function restrictPastDates() {
    const today = new Date().toISOString().split('T')[0];
    ['date', 'paymentDate', 'orderDate'].forEach((id) => {
        const field = document.getElementById(id);
        if (field) field.min = today;
    });
})();

/* ============ Auto-fill payment amount ============ */
// Reads the price from the selected Occasion (table reservation) or the
// current cart total (food order) and fills the read-only Amount field
// in the shared Payment Details form.
function syncPaymentAmount() {
    const amountField = document.getElementById('paymentAmount');
    const paymentFor = document.getElementById('paymentFor')?.value || '';
    if (!amountField) return;

    if (paymentFor === 'food order') {
        amountField.value = cartTotal() > 0 ? `₹${cartTotal()}` : '';
        return;
    }

    if (paymentFor === 'table reservation') {
        const eventType = document.getElementById('eventType');
        const selectedOption = eventType?.options[eventType.selectedIndex];
        const price = selectedOption?.dataset?.price;
        amountField.value = price ? `₹${price}` : '';
        return;
    }

    amountField.value = '';
}

document.getElementById('eventType')?.addEventListener('change', syncPaymentAmount);
document.getElementById('paymentFor')?.addEventListener('change', syncPaymentAmount);

function markInvalid(field) {
    field.classList.add('input-error');
    field.addEventListener('input', () => field.classList.remove('input-error'), { once: true });
}

function validateForm(form) {
    let valid = true;
    form.querySelectorAll('[required]').forEach((field) => {
        if (!field.checkValidity()) {
            valid = false;
            markInvalid(field);
        }
    });
    return valid;
}

/* ============ Reservation form ============ */
function formatReservationTimeRange(startTime, endTime) {
    return `${startTime} to ${endTime}`;
}

async function readJsonResponse(response) {
    const text = await response.text();
    if (!text) {
        return { success: false, message: 'The server returned an empty response.' };
    }

    try {
        return JSON.parse(text);
    } catch (error) {
        return { success: false, message: text };
    }
}

document.getElementById('reservationForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.target;
    if (!validateForm(form)) {
        showToast('Missing details', 'Please fill in every field to book your table.');
        return;
    }

    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;

    if (endTime <= startTime) {
        markInvalid(document.getElementById('endTime'));
        showToast('Check reservation time', 'End time must be later than the start time.');
        return;
    }

    const paymentMethod = document.getElementById('paymentMethod').value.trim();
    const paymentDate = document.getElementById('paymentDate').value;
    const paymentTime = document.getElementById('paymentTime').value;
    const isCashOnDelivery = paymentMethod === 'Cash On Delivery';

    if (!isCashOnDelivery && !paymentConfirmed) {
        showActionPopup(
            '💳 Payment Required',
            'Please complete your payment details first to confirm this table booking.',
            'Go to Payment',
            () => {
                const paymentForSelect = document.getElementById('paymentFor');
                if (paymentForSelect) paymentForSelect.value = 'table reservation';
                syncPaymentAmount();
                document.getElementById('sharedPayment')?.scrollIntoView({ behavior: 'smooth' });
                document.getElementById('cardHolderName')?.focus();
            }
        );
        return;
    }

    const payload = {
        name: document.getElementById('name').value.trim(),
        email: document.getElementById('email').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        reservation_date: document.getElementById('date').value,
        reservation_time: formatReservationTimeRange(startTime, endTime),
        guests: document.getElementById('guests').value,
        event_type: document.getElementById('eventType').value,
        amount: (document.getElementById('paymentAmount')?.value || '').replace(/[^\d.]/g, ''),
        payment_for: document.getElementById('paymentFor')?.value || '',
        payment_method: paymentMethod,
        payment_date: paymentDate,
        payment_time: paymentTime,
        payment_confirmed: isCashOnDelivery ? false : true
    };

    try {
        const response = await fetch('api/submit_reservation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await readJsonResponse(response);
        if (!response.ok || !result.success) {
            showToast('Reservation issue', result.message || 'Could not save your booking.');
            return;
        }
        showToast('Table reserved!', `${payload.name}, your reservation has been saved.`);
        form.reset();
        paymentConfirmed = false;
        document.getElementById('paymentForm')?.reset();

        showActionPopup(
            '🎉 Table Booked!',
            'Your reservation has been saved. You can view its status anytime in your profile.',
            'View My Profile',
            () => {
                window.location.href = 'profile.php';
            }
        );
    } catch (error) {
        showToast('Reservation issue', 'Please ensure your PHP server is running.');
    }
});

/* ============ Order form ============ */
// Tracks whether the card payment (Pay Now box) has actually been completed
// for the currently selected payment method. Cash On Delivery never needs it.
let paymentConfirmed = false;

document.getElementById('orderForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.target;
    if (!validateForm(form)) {
        showToast('Missing details', 'Please complete all order fields before placing your order.');
        return;
    }

    if (!cart.length) {
        showToast('Cart is empty', 'Add food items from the menu before checkout.');
        return;
    }

    const method = document.getElementById('paymentMethod').value;
    const isCashOnDelivery = method === 'Cash On Delivery';

    if (!isCashOnDelivery && !paymentConfirmed) {
        showActionPopup(
            '💳 Payment Required',
            'Please complete your payment details first to place this order.',
            'Go to Payment',
            () => {
                const paymentForSelect = document.getElementById('paymentFor');
                if (paymentForSelect) paymentForSelect.value = 'food order';
                syncPaymentAmount();
                document.getElementById('sharedPayment')?.scrollIntoView({ behavior: 'smooth' });
                document.getElementById('cardHolderName')?.focus();
            }
        );
        return;
    }

    const payload = {
        customer_name: document.getElementById('orderCustomerName').value.trim(),
        mobile: document.getElementById('orderMobile').value.trim(),
        address: document.getElementById('orderAddress').value.trim(),
        delivery_date: document.getElementById('orderDate').value,
        delivery_time: document.getElementById('orderTime').value,
        items: cart,
        special_instructions: document.getElementById('orderInstructions').value.trim(),
        payment_method: method,
        payment_date: document.getElementById('paymentDate').value,
        payment_time: document.getElementById('paymentTime').value,
        total_amount: cartTotal(),
        // The backend trusts this only to distinguish "Cash On Delivery" (always
        // unpaid until delivery) from online methods where the card step above
        // must have actually succeeded first.
        payment_confirmed: isCashOnDelivery ? false : true
    };

    try {
        const response = await fetch('api/submit_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await readJsonResponse(response);
        if (!response.ok || !result.success) {
            showToast('Order issue', result.message || 'Could not place your order.');
            return;
        }
        showToast('Order placed!', `${payload.customer_name}, your order has been placed successfully.`);
        form.reset();
        document.getElementById('paymentForm')?.reset();
        paymentConfirmed = false;
        cart = [];
        updateCartUI();

        showActionPopup(
            '🎉 Order Placed!',
            'Your order has been saved. You can view its status anytime in your profile.',
            'View My Profile',
            () => {
                window.location.href = 'profile.php';
            }
        );
    } catch (error) {
        showToast('Order issue', 'Please ensure your PHP server is running.');
    }
});

/* ============ Payment form ============ */
function formatCardNumber(value) {
    return value.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
}

document.getElementById('cardNumber')?.addEventListener('input', (event) => {
    event.target.value = formatCardNumber(event.target.value);
});

document.getElementById('paymentForm')?.addEventListener('submit', (event) => {
    event.preventDefault();
    const method = document.getElementById('paymentMethod').value;

    if (!method) {
        markInvalid(document.getElementById('paymentMethod'));
        showToast('Select a payment method', 'Choose how you would like to pay.');
        return;
    }

    if (method === 'Cash On Delivery') {
        showToast('No card needed', 'Cash On Delivery does not require card payment. Just click Place Order.');
        return;
    }

    const cardName = document.getElementById('cardHolderName');
    const cardNumber = document.getElementById('cardNumber');
    const cardExpiry = document.getElementById('cardExpiry');
    const cardCvv = document.getElementById('cardCvv');

    const digitsOnly = cardNumber.value.replace(/\s/g, '');
    let valid = true;

    if (!cardName.value.trim()) { markInvalid(cardName); valid = false; }
    if (digitsOnly.length < 12) { markInvalid(cardNumber); valid = false; }
    if (!/^\d{4}-\d{2}$/.test(cardExpiry.value)) { markInvalid(cardExpiry); valid = false; }
    if (cardCvv.value.trim().length < 3) { markInvalid(cardCvv); valid = false; }

    if (!valid) {
        paymentConfirmed = false;
        showToast('Check card details', 'Some payment fields need your attention.');
        return;
    }

    paymentConfirmed = true;
    showToast('Payment verified!', `Card details for ${method} look good.`);

    const paymentFor = document.getElementById('paymentFor')?.value || '';
    const isForOrder = paymentFor === 'food order';

    if (isForOrder) {
        showActionPopup(
            '✅ Payment Successful!',
            'Your payment has been verified. Click below to go place your order.',
            'Go to Place Order',
            () => {
                document.getElementById('order')?.scrollIntoView({ behavior: 'smooth' });
                document.getElementById('orderCustomerName')?.focus();
            }
        );
    } else {
        showActionPopup(
            '✅ Payment Successful!',
            'Your payment has been verified. Click below to go book your table.',
            'Go to Book Table',
            () => {
                document.getElementById('reservation')?.scrollIntoView({ behavior: 'smooth' });
                document.getElementById('name')?.focus();
            }
        );
    }
});

// If the customer changes the payment method after verifying a card,
// the previous confirmation no longer applies.
document.getElementById('paymentMethod')?.addEventListener('change', () => {
    paymentConfirmed = false;
});

/* ============ Auth nav ============ */
async function updateAuthNav() {
    const loginLink = document.querySelector('.nav-right a[href="access.html"]');
    const profileLink = document.querySelector('.nav-right a[href="profile.php"]');

    if (!loginLink || !profileLink) return;

    try {
        const response = await fetch('api/session.php');
        const data = await response.json();
        const isLoggedIn = Boolean(data?.loggedIn);
        loginLink.parentElement.style.display = isLoggedIn ? 'none' : '';
        profileLink.parentElement.style.display = isLoggedIn ? '' : 'none';
    } catch (error) {
        loginLink.parentElement.style.display = '';
        profileLink.parentElement.style.display = 'none';
    }
}

/* ============ Init ============ */
updateCategoryVisibility();
renderMenu();
loadMenuFromServer();
updateCartUI();
updateActiveNavLink();
updateAuthNav();