/**
 *  ERP — Global Authentication Helper
 * Handles JWT injection, automatic token refresh, and redirection.
 */

// Global configuration
window.APP_URL = window.APP_URL || (typeof location !== 'undefined' ? location.origin + '/erp' : '');

/**
 * Drop-in replacement for fetch() that automatically:
 *   1. Injects Authorization: Bearer <token> from sessionStorage
 *   2. Auto-refreshes the token if it's about to expire (within 60s)
 *   3. Redirects to /auth/login on 401 (token fully invalid)
 *
 * Usage:
 *   const data = await authFetch('/api/admin/students').then(r => r.json());
 */
window.authFetch = async function(url, options = {}) {
    // Resolve relative URLs to absolute if needed
    if (url.startsWith('/api/') && typeof APP_URL !== 'undefined') {
        url = APP_URL + url;
    }

    const token     = sessionStorage.getItem('access_token');
    const expiresAt = parseInt(sessionStorage.getItem('token_expires_at') || '0', 10);
    const soonExpires = expiresAt && Date.now() > (expiresAt - 60_000); // 60s grace window

    // 1. Auto-refresh when close to expiry
    if (token && soonExpires) {
        try {
            const refreshRes = await fetch(window.APP_URL + '/api/refresh', {
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
            } else if (refreshRes.status === 401) {
                // Refresh failed — token is dead, force re-login
                sessionStorage.clear();
                window.location.href = window.APP_URL + '/auth/login';
                return null;
            }
        } catch (err) {
            console.warn('[authFetch] Token refresh failed:', err);
        }
    }

    // 2. Inject Authorization header
    const currentToken = sessionStorage.getItem('access_token');
    if (currentToken) {
        options.headers = Object.assign({
            'Authorization':    'Bearer ' + currentToken,
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }, options.headers || {});
    }

    // 3. Perform the actual fetch
    const res = await fetch(url, options);

    // 4. Global 401 handler — token revoked, blacklisted, or expired
    if (res.status === 401) {
        // Clear state and kick to login
        sessionStorage.clear();
        window.location.href = window.APP_URL + '/auth/login?expired=1';
        return res;
    }

    return res;
};

/**
 * Handle Logout across all tabs
 * Clears client-side state and navigates to /logout.php
 * Note: /logout.php is a standalone PHP file (not a Laravel route)
 */
window.authLogout = function() {
    // Clear all client-side authentication state
    sessionStorage.removeItem('access_token');
    sessionStorage.removeItem('token_expires_at');
    localStorage.removeItem('access_token');
    localStorage.removeItem('token_expires_at');

    // Redirect to standalone logout handler
    // /logout.php clears token cookie, session, and redirects to /auth/login
    window.location.href = (window.APP_URL || '') + '/logout.php';
};
