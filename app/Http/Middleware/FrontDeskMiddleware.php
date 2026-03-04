<?php
/**
 * Front Desk Middleware
 * Centralized security for Front Desk operations
 */

class FrontDeskMiddleware {
    /**
     * Bootstraps security for Front Desk API controllers
     * Checks login, tenant, roles, and CSRF for state-changing methods
     */
    public static function check() {
        if (!isLoggedIn()) {
            self::error(401, 'Unauthorized: Please login');
        }

        $user = getCurrentUser();
        $tenantId = $user['tenant_id'] ?? null;
        $role = $user['role'] ?? '';

        if (!$tenantId) {
            self::error(403, 'Forbidden: Invalid Tenant');
        }

        // Allowed roles for Front Desk operations
        $allowedRoles = ['frontdesk', 'instituteadmin', 'superadmin'];
        if (!in_array($role, $allowedRoles)) {
            self::error(403, 'Forbidden: Insufficient Permissions');
        }

        // CSRF Protection for state-changing methods
        $method = $_SERVER['REQUEST_METHOD'];
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!function_exists('verifyCSRFToken') || !verifyCSRFToken($token)) {
                self::error(419, 'CSRF Token Mismatch');
            }
        }

        return [
            'tenant_id' => $tenantId,
            'user_id' => $user['id'],
            'role' => $role
        ];
    }

    private static function error($code, $msg) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
}
