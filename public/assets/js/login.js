/**
 * Hamro ERP — Login Page Script
 * Session-based authentication with role-based redirect
 */
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const rememberCheckbox = document.getElementById('remember');
    const loginAlert = document.getElementById('loginAlert');
    const loginBtn = document.getElementById('loginBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    // Base URL from PHP
    const baseUrl = (typeof APP_URL !== 'undefined') ? APP_URL : '/erp';

    // Form submission
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = usernameInput.value.trim();
        const password = passwordInput.value;

        // Basic validation
        if (!email) {
            showAlert('Please enter your email address.', 'error');
            usernameInput.focus();
            return;
        }
        if (!password) {
            showAlert('Please enter your password.', 'error');
            passwordInput.focus();
            return;
        }

        // Loading state
        loginBtn.disabled = true;
        btnText.textContent = 'Signing in...';
        if (btnSpinner) btnSpinner.style.display = 'inline-block';
        hideAlert();

        try {
            const formData = new FormData();
            formData.append('username', email);
            formData.append('password', password);
            if (rememberCheckbox && rememberCheckbox.checked) {
                formData.append('remember', 'on');
            }

            const response = await fetch(baseUrl + '/api/login', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                showAlert('Login successful! Redirecting...', 'success');

                // Store user info in sessionStorage for client-side use
                if (data.user) {
                    sessionStorage.setItem('userData', JSON.stringify(data.user));
                }

                // Redirect to loading screen, which will then redirect to dashboard
                setTimeout(() => {
                    window.location.href = data.loading_screen || data.redirect || baseUrl + '/dash/admin';
                }, 500);
            } else {
                showAlert(data.message || 'Invalid email or password.', 'error');
            }
        } catch (error) {
            console.error('Login error:', error);
            showAlert('Connection error. Please try again.', 'error');
        } finally {
            loginBtn.disabled = false;
            btnText.textContent = 'Login';
            if (btnSpinner) btnSpinner.style.display = 'none';
        }
    });

    // Alert helpers
    function showAlert(message, type) {
        if (!loginAlert) return;
        loginAlert.textContent = message;
        loginAlert.className = 'lp-alert lp-alert--' + type;
    }

    function hideAlert() {
        if (!loginAlert) return;
        loginAlert.className = 'lp-alert';
        loginAlert.textContent = '';
    }

    // Password toggle
    const togglePassword = document.getElementById('togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = togglePassword.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                togglePassword.style.color = 'var(--green)';
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                togglePassword.style.color = 'var(--text-light)';
            }
        });
    }

    // Input focus effects
    const inputs = loginForm.querySelectorAll('.lp-input');
    inputs.forEach(input => {
        input.addEventListener('focus', () => input.parentElement.classList.add('focused'));
        input.addEventListener('blur', () => input.parentElement.classList.remove('focused'));
    });

    // Enter key shortcut
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            loginForm.dispatchEvent(new Event('submit'));
        }
    });

    // Auto-focus email field
    usernameInput.focus();
});
