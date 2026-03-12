<?php
/**
 * Role-Based Access Control Middleware
 * Blocks wrong roles from accessing unauthorized routes
 */

require_once __DIR__ . '/../../config.php';

class RoleMiddleware {
    
    /**
     * Define role-based route prefixes
     */
    private static $roleRoutes = [
        'superadmin' => '/super-admin',
        'instituteadmin' => '/admin',
        'frontdesk' => '/front-desk',
        'teacher' => '/teacher',
        'student' => '/student',
        'guardian' => '/guardian'
    ];
    
    /**
     * Define role permissions matrix
     */
    private static $permissions = [
        'superadmin' => ['*'],
        'instituteadmin' => [
            'dashboard.view', 'dashboard.manage',
            'students.view', 'students.add', 'students.edit', 'students.delete',
            'teachers.view', 'teachers.add', 'teachers.edit', 'teachers.delete',
            'courses.view', 'courses.add', 'courses.edit', 'courses.delete',
            'classes.view', 'classes.add', 'classes.edit', 'classes.delete',
            'attendance.view', 'attendance.mark',
            'exams.view', 'exams.add', 'exams.edit', 'exams.delete',
            'grades.view', 'grades.add', 'grades.edit', 'grades.delete',
            'fees.view', 'fees.add', 'fees.edit', 'fees.delete',
            'reports.view', 'reports.export',
            'settings.view', 'settings.edit',
            'library.view', 'library.manage',
            'messages.view', 'messages.send'
        ],
        'frontdesk' => [
            'dashboard.view',
            'students.view', 'students.add', 'students.edit',
            'attendance.view',
            'fees.view', 'fees.add', 'fees.edit',
            'messages.view', 'messages.send',
            'reports.view'
        ],
        'teacher' => [
            'dashboard.view',
            'attendance.view', 'attendance.mark',
            'exams.view', 'exams.add', 'exams.edit',
            'grades.view', 'grades.add', 'grades.edit',
            'students.view',
            'reports.view',
            'materials.view', 'materials.upload'
        ],
        'student' => [
            'dashboard.view',
            'attendance.view',
            'exams.view', 'exams.take',
            'grades.view',
            'timetable.view',
            'fees.view',
            'materials.view',
            'assignments.view', 'assignments.submit'
        ],
        'guardian' => [
            'dashboard.view',
            'attendance.view',
            'exams.view',
            'grades.view',
            'timetable.view',
            'fees.view',
            'messages.view', 'messages.send'
        ]
    ];
    
    /**
     * Handle the request - check if user has access
     */
    public static function handle($requiredRole = null, $requiredPermission = null) {
        // Get current user
        $user = self::getCurrentUser();
        
        if (!$user) {
            self::unauthorized('Please login to continue');
            return false;
        }
        
        // Check role access
        if ($requiredRole && $user['role'] !== $requiredRole) {
            // Super admin can access everything
            if ($user['role'] !== 'superadmin') {
                self::forbidden('You do not have access to this section');
                return false;
            }
        }
        
        // Check specific permission
        if ($requiredPermission && !self::hasPermission($user['role'], $requiredPermission)) {
            self::forbidden('You do not have permission to perform this action');
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if role has permission
     */
    public static function hasPermission($role, $permission) {
        $rolePerms = self::$permissions[$role] ?? [];
        
        // Super admin has all permissions
        if (in_array('*', $rolePerms)) {
            return true;
        }
        
        // Check exact permission
        if (in_array($permission, $rolePerms)) {
            return true;
        }
        
        // Check permission prefix (e.g., 'students.view' allows 'students.*')
        $permissionParts = explode('.', $permission);
        if (count($permissionParts) >= 2) {
            $prefix = $permissionParts[0] . '.*';
            if (in_array($prefix, $rolePerms)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get current user from session
     */
    private static function getCurrentUser() {
        return $_SESSION['userData'] ?? null;
    }
    
    /**
     * Get user's role
     */
    public static function getRole() {
        $user = self::getCurrentUser();
        return $user['role'] ?? null;
    }
    
    /**
     * Check if user is admin (superadmin or instituteadmin)
     */
    public static function isAdmin() {
        $role = self::getRole();
        return in_array($role, ['superadmin', 'instituteadmin']);
    }
    
    /**
     * Get allowed routes for current role
     */
    public static function getAllowedRoutes($role) {
        $routes = [];
        
        switch ($role) {
            case 'superadmin':
                $routes = [
                    '/super-admin',
                    '/super-admin/dashboard',
                    '/super-admin/tenants',
                    '/super-admin/plans',
                    '/super-admin/revenue',
                    '/super-admin/support',
                    '/super-admin/settings'
                ];
                break;
                
            case 'instituteadmin':
                $routes = [
                    '/admin',
                    '/admin/dashboard',
                    '/admin/students',
                    '/admin/teachers',
                    '/admin/courses',
                    '/admin/attendance',
                    '/admin/exams',
                    '/admin/fees',
                    '/admin/reports',
                    '/admin/settings'
                ];
                break;
                
            case 'frontdesk':
                $routes = [
                    '/front-desk',
                    '/front-desk/dashboard',
                    '/front-desk/students',
                    '/front-desk/fees',
                    '/front-desk/attendance'
                ];
                break;
                
            case 'teacher':
                $routes = [
                    '/teacher',
                    '/teacher/dashboard',
                    '/teacher/attendance',
                    '/teacher/exams',
                    '/teacher/grades',
                    '/teacher/materials'
                ];
                break;
                
            case 'student':
                $routes = [
                    '/student',
                    '/student/dashboard',
                    '/student/attendance',
                    '/student/exams',
                    '/student/grades',
                    '/student/timetable'
                ];
                break;
                
            case 'guardian':
                $routes = [
                    '/guardian',
                    '/guardian/dashboard',
                    '/guardian/attendance',
                    '/guardian/grades',
                    '/guardian/fees'
                ];
                break;
        }
        
        return $routes;
    }
    
    /**
     * Redirect to unauthorized page
     */
    private static function unauthorized($message) {
        // Check if this is an API (JSON) request
        $isApi = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        if ($isApi) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message, 'code' => 401]);
            exit();
        }
        $_SESSION['error'] = $message;
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/auth/login');
        exit();
    }
    
    /**
     * Redirect to forbidden page
     */
    private static function forbidden($message) {
        $isApi = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        if ($isApi) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => $message, 'code' => 403]);
            exit();
        }
        http_response_code(403);
        $_SESSION['error'] = $message;
        // Redirect back or to dashboard
        $dashUrl = (defined('APP_URL') ? APP_URL : '') . '/auth/login';
        header('Location: ' . $dashUrl);
        exit();
    }
}
?>
