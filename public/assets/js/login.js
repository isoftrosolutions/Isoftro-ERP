/**
 * Hamro ERP — Login Page Script
 * JWT authentication:
 *   - Server sets HttpOnly cookie 'token' (primary auth mechanism, immune to XSS)
 *   - Client stores access_token in sessionStorage for manual Authorization headers
 */
document.addEventListener('DOMContentLoaded', () => {
    const loginForm      = document.getElementById('loginForm');
    const usernameInput  = document.getElementById('username');
    const passwordInput  = document.getElementById('password');
    const rememberCheckbox = document.getElementById('remember');
    const loginAlert     = document.getElementById('loginAlert');
    const loginBtn       = document.getElementById('loginBtn');
    const btnText        = document.getElementById('btnText');
    const btnSpinner     = document.getElementById('btnSpinner');

    // Base URL injected by PHP in login.php: window.APP_URL = '<?= $BASE ?>'
    const baseUrl = (typeof APP_URL !== 'undefined') ? APP_URL : '/erp';

    // ─── Form Submission ──────────────────────────────────────────
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email    = usernameInput.value.trim();
        const password = passwordInput.value;

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

                // Store non-sensitive user metadata for client-side display (role badge, name, etc.)
                if (data.user) {
                    sessionStorage.setItem('userData', JSON.stringify(data.user));
                }

                // Store access_token for Authorization: Bearer headers in subsequent fetch() calls.
                // The JWT is ALSO in an HttpOnly cookie (set server-side) — that's the primary auth
                // mechanism for page loads. This sessionStorage entry is for frontend-initiated API calls.
                if (data.access_token) {
                    sessionStorage.setItem('access_token', data.access_token);
                    sessionStorage.setItem('token_expires_at',
                        Date.now() + ((data.expires_in || 28800) * 1000)
                    );
                }

                // Redirect to loading screen, which will forward to the role-based dashboard
                setTimeout(() => {
                    window.location.href = data.loading_screen || data.redirect || (baseUrl + '/dash/admin');
                }, 400);

            } else {
                showAlert(data.message || 'Invalid email or password.', 'error');
            }

        } catch (error) {
            console.error('[Login] Fetch error:', error);
            showAlert('Connection error. Please check your internet and try again.', 'error');
        } finally {
            loginBtn.disabled = false;
            btnText.textContent = 'Sign In';
            if (btnSpinner) btnSpinner.style.display = 'none';
        }
    });

    // ─── Alert Helpers ────────────────────────────────────────────
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

    // ─── Password Toggle ──────────────────────────────────────────
    const togglePassword = document.getElementById('togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = togglePassword.querySelector('i');
            if (type === 'text') {
                icon.classList.replace('fa-eye', 'fa-eye-slash');
                togglePassword.style.color = 'var(--g1)';
            } else {
                icon.classList.replace('fa-eye-slash', 'fa-eye');
                togglePassword.style.color = 'var(--text-m)';
            }
        });
    }

    // ─── Input Focus Effects ──────────────────────────────────────
    loginForm.querySelectorAll('.lp-input').forEach(input => {
        input.addEventListener('focus', () => input.parentElement.classList.add('focused'));
        input.addEventListener('blur',  () => input.parentElement.classList.remove('focused'));
    });

    // ─── Keyboard Shortcut: Ctrl/Cmd + Enter to submit ───────────
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            loginForm.dispatchEvent(new Event('submit'));
        }
    });

    // Auto-focus email field on load
    usernameInput.focus();
});

// ═══════════════════════════════════════════════════════════════════
// Global authFetch() — authenticated API helper
// ═══════════════════════════════════════════════════════════════════
/**
 * Drop-in replacement for fetch() that automatically:
 *   1. Injects Authorization: Bearer <token> from sessionStorage
 *   2. Auto-refreshes the token if it's about to expire (within 60s)
 *   3. Redirects to /auth/login on 401 (token fully invalid)
 *
 * Usage (replace all bare fetch() calls for protected API endpoints):
 *   const data = await authFetch('/api/admin/students').then(r => r.json());
 */
window.authFetch = async function(url, options = {}) {
    const base      = (typeof APP_URL !== 'undefined') ? APP_URL : '/erp';
    const token     = sessionStorage.getItem('access_token');
    const expiresAt = parseInt(sessionStorage.getItem('token_expires_at') || '0', 10);
    const soonExpires = expiresAt && Date.now() > (expiresAt - 60_000); // 60s grace window

    // Auto-refresh when close to expiry
    if (token && soonExpires) {
        try {
            const refreshRes = await fetch(base + '/api/auth/refresh', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            if (refreshRes.ok) {
                const rd = await refreshRes.json();
                if (rd.access_token) {
                    sessionStorage.setItem('access_token', rd.access_token);
                    sessionStorage.setItem('token_expires_at',
                        Date.now() + ((rd.expires_in || 28800) * 1000)
                    );
                }
            } else {
                // Refresh failed — token is dead, force re-login
                sessionStorage.clear();
                window.location.href = base + '/auth/login';
                return null;
            }
        } catch (err) {
            console.warn('[authFetch] Token refresh failed:', err);
        }
    }

    // Inject Authorization header into the outgoing request
    const currentToken = sessionStorage.getItem('access_token');
    if (currentToken) {
        options.headers = Object.assign({
            'Authorization':    'Bearer ' + currentToken,
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }, options.headers || {});
    }

    const res = await fetch(url, options);

    // Global 401 handler — token revoked or blacklisted
    if (res.status === 401) {
        sessionStorage.clear();
        window.location.href = base + '/auth/login';
        return res;
    }

    return res;
};
