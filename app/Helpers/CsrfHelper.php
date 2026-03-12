<?php
namespace App\Helpers;

class CsrfHelper
{
    /**
     * Generate a CSRF token and store in session
     * @return string The generated token
     */
    public static function generateCsrfToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tokenName = 'csrf_token';
        $token = bin2hex(random_bytes(32));

        // Store token in session
        $_SESSION[$tokenName] = $token;
        $_SESSION['csrf_token_time'] = time();

        return $token;
    }

    /**
     * Get current CSRF token from session, generate if not exists
     * @return string The CSRF token
     */
    public static function getCsrfToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tokenName = 'csrf_token';

        if (!isset($_SESSION[$tokenName]) || self::isCsrfTokenExpired()) {
            return self::generateCsrfToken();
        }

        return $_SESSION[$tokenName];
    }

    /**
     * Check if CSRF token is expired (default 30 minutes)
     * @param int $expiryTime Token expiry time in seconds
     * @return bool True if expired
     */
    public static function isCsrfTokenExpired($expiryTime = 1800)
    {
        $tokenTime = $_SESSION['csrf_token_time'] ?? 0;
        return (time() - $tokenTime) > $expiryTime;
    }

    /**
     * Validate CSRF token from request
     * @param string|null $token Token from request (POST/GET/header)
     * @return bool True if valid
     */
    public static function validateCsrfToken($token = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tokenName = 'csrf_token';

        // If no token provided, try to get from various sources
        if ($token === null) {
            // Check header first
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

            // Check POST
            if ($token === null) {
                $token = $_POST['csrf_token'] ?? null;
            }

            // Check GET
            if ($token === null) {
                $token = $_GET['csrf_token'] ?? null;
            }
        }

        // No token provided and none in session
        if ($token === null || !isset($_SESSION[$tokenName])) {
            return false;
        }

        // Check token match with timing-safe comparison
        $sessionToken = $_SESSION[$tokenName];
        $isValid = hash_equals($sessionToken, $token);

        // NOTE: We intentionally do NOT regenerate the token here.
        // Regenerating on every validation causes stale-token failures in SPA
        // partial-page loads where the <head> meta tag is not re-rendered.
        // Token rotation is handled naturally by the 30-minute expiry.

        return $isValid;
    }

    /**
     * Require CSRF validation - throws exception if invalid
     * @param string|null $token Token to validate
     * @throws \Exception If CSRF validation fails
     */
    public static function requireCsrfToken($token = null)
    {
        if (!self::validateCsrfToken($token)) {
            throw new \Exception('CSRF token validation failed. Please refresh the page and try again.');
        }
    }

    /**
     * Get CSRF token for forms - creates hidden input HTML
     * @return string HTML hidden input with token
     */
    public static function csrfField()
    {
        $token = self::getCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Get CSRF token for AJAX requests - creates meta tag content
     * @return string HTML meta tag with token
     */
    public static function csrfMetaTag()
    {
        $token = self::getCsrfToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Add CSRF token header to AJAX requests (JavaScript snippet)
     * @return string JavaScript code to set CSRF header
     */
    public static function csrfJsHeader()
    {
        return "<script>
            // Add CSRF token to all AJAX requests and keep it synchronized
            (function() {
                const updateToken = (newToken) => {
                    if (!newToken) return;
                    const meta = document.querySelector('meta[name=\"csrf-token\"]');
                    if (meta) meta.setAttribute('content', newToken);
                    window.csrfToken = newToken; // Legacy global support
                    window.CSRF_TOKEN = newToken; // Ensure uppercase version is also synced
                };

                const originalFetch = window.fetch;
                window.fetch = function(...args) {
                    const [resource, config] = args;
                    const freshToken = document.querySelector('meta[name=\"csrf-token\"]')?.content;
                    
                    if (config && freshToken && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(config.method?.toUpperCase())) {
                        config.headers = config.headers || {};
                        // Handle both Headers object and plain object
                        if (config.headers instanceof Headers) {
                            config.headers.set('X-CSRF-Token', freshToken);
                        } else {
                            config.headers['X-CSRF-Token'] = freshToken;
                        }
                    }

                    return originalFetch.apply(this, args).then(response => {
                        // Synchronize token if provided in response header
                        const newToken = response.headers.get('X-CSRF-Token');
                        if (newToken) updateToken(newToken);
                        return response;
                    });
                };
            })();
        </script>";
    }
}
