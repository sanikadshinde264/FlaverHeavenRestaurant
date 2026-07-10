const loginForm = document.getElementById('loginForm');
const signUpForm = document.getElementById('signUpForm'); // Added missing form reference
const emailField = document.getElementById('email');
const passwordField = document.getElementById('password');
const emailError = document.getElementById('emailError');
const passwordError = document.getElementById('passwordError');
const msg = document.getElementById('msg');
const loginBtn = document.getElementById('loginBtn');

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function setMessage(text, type) {
    msg.textContent = text;
    msg.className = type || '';
}

function markInvalid(field, errorEl, text) {
    if (!field) return;
    field.classList.add('input-error');
    if (errorEl) errorEl.textContent = text || '';
    field.addEventListener('input', () => {
        field.classList.remove('input-error');
        if (errorEl) errorEl.textContent = '';
    }, { once: true });
}

function clearFieldErrors() {
    [emailField, passwordField].forEach((f) => f.classList.remove('input-error'));
    if (emailError) emailError.textContent = '';
    if (passwordError) passwordError.textContent = '';
}

// === LOGIN LOGIC ===
loginForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    setMessage('', '');
    clearFieldErrors();

    const username = emailField.value.trim();
    const password = passwordField.value;
    let valid = true;

    if (!username) {
        markInvalid(emailField, emailError, 'Enter your username or email.');
        valid = false;
    } else if (username.includes('@') && !EMAIL_PATTERN.test(username)) {
        // Only enforce email format when the person is actually typing an email
        markInvalid(emailField, emailError, 'Enter a valid email address.');
        valid = false;
    }

    if (!password) {
        markInvalid(passwordField, passwordError, 'Enter your password.');
        valid = false;
    } else if (password.length < 4) {
        markInvalid(passwordField, passwordError, 'Password must be at least 4 characters.');
        valid = false;
    }

    if (!valid) {
        return;
    }

    loginBtn.disabled = true;
    loginBtn.textContent = 'SIGNING IN...';
    setMessage('Signing you in...', '');

    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'login', username, password })
        });
        const result = await response.json();

        if (!result.success) {
            // Short, Gmail-style inline error instead of a generic banner
            markInvalid(emailField, null, '');
            markInvalid(passwordField, passwordError, 'Wrong email or password.');
            loginBtn.disabled = false;
            loginBtn.textContent = 'LOGIN';
            return;
        }

        localStorage.setItem('flaverheaven_user', JSON.stringify({ username, loggedInAt: Date.now() }));
        setMessage(result.message || 'Welcome back! Redirecting...', 'success');
        const target = result?.user?.role === 'admin' ? 'admin/dashboard.php' : 'index.html';
        window.location.href = target;
    } catch (error) {
        setMessage('Login failed. Make sure the PHP server is running.', 'error');
        loginBtn.disabled = false;
        loginBtn.textContent = 'LOGIN';
    }
});

// === NAVIGATION / UTILITY LINKS ===
document.getElementById('forgotPasswordLink')?.addEventListener('click', () => {
    setMessage('Password reset link would be sent to your email in a live system.', '');
});

document.getElementById('signUpLink')?.addEventListener('click', (event) => {
    event.preventDefault();
    window.location.href = 'signup.html';
});

// === SIGN UP LOGIC ===
// Fixed the orphaned code block below by wrapping it in an event listener
signUpForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    setMessage('', '');

    // Adjust these IDs if your signup form HTML uses different field IDs
    const regUsername = document.getElementById('regUsername')?.value.trim();
    const regEmail = document.getElementById('regEmail')?.value.trim();
    const regPassword = document.getElementById('regPassword')?.value;

    if (!regUsername || !regEmail || !regPassword) {
        setMessage('Please fill all fields to create your account.', 'error');
        return;
    }

    fetch('api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'register', username: regUsername, email: regEmail, password: regPassword })
    })
        .then(async (response) => {
            const result = await response.json();
            if (!result.success) {
                setMessage(result.message || 'Unable to create your account.', 'error');
                return;
            }
            localStorage.setItem('flaverheaven_user', JSON.stringify({ username: regUsername, email: regEmail, loggedInAt: Date.now() }));
            setMessage(result.message || 'Account created successfully.', 'success');
            window.location.href = 'login.html';
        })
        .catch(() => setMessage('Sign up failed. Make sure the PHP server is running.', 'error'));
});