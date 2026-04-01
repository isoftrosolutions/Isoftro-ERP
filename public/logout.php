<?php
/**
 * iSoftro ERP — Logout Handler (Direct PHP)
 * Bypasses Laravel routing to avoid framework issues.
 *
 * Clears:
 * - JWT token cookie
 * - Session data
 * - Client-side token storage redirect
 */

// Get host for cookie domain
$host = $_SERVER['HTTP_HOST'] ?? '';
$cookieDomain = null;
if ($host && !in_array($host, ['localhost', '127.0.0.1'])) {
    $parts = explode('.', $host);
    if (count($parts) >= 2) {
        $cookieDomain = '.' . implode('.', array_slice($parts, -2));
    }
}

$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

// Clear JWT token cookie
setcookie('token', '', time() - 42000, '/', $cookieDomain, $secure, true);

// Clear session cookie if exists
if (function_exists('session_name')) {
    session_start();
    $_SESSION = [];
    setcookie(session_name(), '', time() - 42000, '/', $cookieDomain, $secure, true);
    session_destroy();
}

// Determine response based on request type
$isJsonRequest = (
    isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false ||
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'
);

if ($isJsonRequest) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Successfully logged out'
    ]);
} else {
    // Redirect to login page
    $appUrl = defined('APP_URL') ? APP_URL : '/erp';
    header("Location: {$appUrl}/auth/login");
}

exit;
?>
