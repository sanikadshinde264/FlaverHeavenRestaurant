const signupForm = document.getElementById("signupForm");
const usernameField = document.getElementById("username");
const emailField = document.getElementById("email");
const passwordField = document.getElementById("password");
const confirmField = document.getElementById("confirmPassword");

const usernameError = document.getElementById("usernameError");
const emailError = document.getElementById("emailError");
const passwordError = document.getElementById("passwordError");
const confirmPasswordError = document.getElementById("confirmPasswordError");

const msg = document.getElementById("msg");
const btn = document.getElementById("signupBtn");

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function markInvalid(field, errorEl, text) {
    if (!field) return;
    field.classList.add("input-error");
    if (errorEl) errorEl.textContent = text || "";
    field.addEventListener("input", () => {
        field.classList.remove("input-error");
        if (errorEl) errorEl.textContent = "";
    }, { once: true });
}

function clearFieldErrors() {
    [usernameField, emailField, passwordField, confirmField].forEach((f) => f.classList.remove("input-error"));
    [usernameError, emailError, passwordError, confirmPasswordError].forEach((e) => { if (e) e.textContent = ""; });
}

signupForm.addEventListener("submit", async function (e) {
    e.preventDefault();

    const username = usernameField.value.trim();
    const email = emailField.value.trim();
    const password = passwordField.value;
    const confirm = confirmField.value;

    msg.className = "";
    msg.textContent = "";
    clearFieldErrors();

    let valid = true;

    if (!username) {
        markInvalid(usernameField, usernameError, "Enter a username.");
        valid = false;
    } else if (username.length < 3) {
        markInvalid(usernameField, usernameError, "Username must be at least 3 characters.");
        valid = false;
    }

    if (!email) {
        markInvalid(emailField, emailError, "Enter your email address.");
        valid = false;
    } else if (!EMAIL_PATTERN.test(email)) {
        markInvalid(emailField, emailError, "Enter a valid email address.");
        valid = false;
    }

    if (!password) {
        markInvalid(passwordField, passwordError, "Enter a password.");
        valid = false;
    } else if (password.length < 4) {
        markInvalid(passwordField, passwordError, "Password must be at least 4 characters.");
        valid = false;
    }

    if (!confirm) {
        markInvalid(confirmField, confirmPasswordError, "Confirm your password.");
        valid = false;
    } else if (password && confirm !== password) {
        markInvalid(confirmField, confirmPasswordError, "Passwords do not match.");
        valid = false;
    }

    if (!valid) return;

    btn.disabled = true;
    btn.textContent = "SIGNING UP...";

    try {
        const response = await fetch("api/auth.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "register", username, email, password })
        });
        const result = await response.json();

        if (result.success) {
            msg.classList.add("success");
            msg.textContent = result.message || "Account created successfully.";
            setTimeout(() => { window.location.href = "login.html"; }, 1500);
        } else {
            // Short, Gmail-style inline error placed under the most relevant field
            const text = (result.message || "").toLowerCase();
            if (text.includes("email")) {
                markInvalid(emailField, emailError, "That email is already in use.");
            } else if (text.includes("username")) {
                markInvalid(usernameField, usernameError, "That username is already taken.");
            } else {
                msg.classList.add("error");
                msg.textContent = result.message || "Unable to create your account.";
            }
            btn.disabled = false;
            btn.textContent = "SIGN UP";
        }
    } catch (err) {
        msg.classList.add("error");
        msg.textContent = "Something went wrong. Please try again.";
        btn.disabled = false;
        btn.textContent = "SIGN UP";
    }
});