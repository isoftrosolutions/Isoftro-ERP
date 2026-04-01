<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/**
 * iSoftro ERP — Main Router (Laravel 11)
 * Auth-enabled with RBAC
 */

// Load config for auth functions
require_once base_path('config/config.php');

// ─── Public Routes ───────────────────────────────────────────

// Landing page = root
Route::get('/', function () {
    require_once resource_path('views/landing.php');
});

// Alias: /landing also works
Route::get('/landing', function () {
    require_once resource_path('views/landing.php');
});

// Login page (GET)
Route::get('/auth/login', function () {
    // If already logged in, redirect to dashboard
    if (isLoggedIn()) {
        $user = getCurrentUser();
        if ($user) {
            $role = str_replace(['_', ' '], '-', strtolower($user['role']));
            // Map role names to URL slugs
            $roleSlugMap = [
                'superadmin' => 'super-admin',
                'instituteadmin' => 'admin',
                'frontdesk' => 'front-desk',
                'teacher' => 'teacher',
                'student' => 'student',
                'guardian' => 'guardian',
            ];
            $slug = $roleSlugMap[$user['role']] ?? $role;
            header('Location: ' . APP_URL . '/dash/' . $slug);
            exit;
        }
    }
    require_once resource_path('views/auth/login.php');
});

// Legacy root redirect to login
Route::get('/login', function() {
    return redirect('/auth/login');
});

// Login API (POST) — JWT authentication via tymon/jwt-auth
// login.js posts to /api/login; this is the single authoritative login endpoint
Route::post('/api/login', [App\Http\Controllers\API\AuthController::class, 'login']);

// Alias: web form may also post to /auth/login — redirect to the same handler
Route::post('/auth/login', [App\Http\Controllers\API\AuthController::class, 'login']);

// Token refresh (used by frontend when access token expires)
Route::post('/api/auth/refresh', [App\Http\Controllers\API\AuthController::class, 'refresh'])->middleware('auth:api');

// Change Password API (Internal)
Route::post('/api/auth/change-password', [App\Http\Controllers\API\AuthController::class, 'changePassword'])->middleware('auth:api');

// Forgot Password Page (GET)
Route::get('/auth/forgot-password', function () {
    require_once resource_path('views/auth/forgot-password.php');
});

// Send Password Reset (POST)
Route::post('/auth/send_password_reset', function () {
    // CSRF Protection
    if (!\App\Helpers\CsrfHelper::validateCsrfToken()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh the page.']);
        exit;
    }
    require_once resource_path('views/auth/send_password_reset.php');
});

// Reset Password Page (GET/POST)
Route::match(['get', 'post'], '/auth/reset-password', function () {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF Protection
        if (!\App\Helpers\CsrfHelper::validateCsrfToken()) {
            die('Security token expired. Please go back and try again.');
        }
    }
    require_once resource_path('views/auth/reset-password.php');
});

// Verify OTP (POST)
Route::post('/auth/verify-otp', function () {
    // CSRF Protection
    if (!\App\Helpers\CsrfHelper::validateCsrfToken()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh the page.']);
        exit;
    }
    require_once resource_path('views/auth/verify_otp.php');
});

// Logout — Handled by /logout.php (standalone PHP file outside Laravel routing)
// No routes needed - direct file access is the most reliable approach
// All views updated to use /logout.php directly

// Loading screen route - shown after successful login
Route::get('/loading', function () {
    $controller = new \App\Http\Controllers\LoadingScreenController();
    $controller->show();
    exit;
});


// ─── Protected Dashboard Routes ──────────────────────────────

// Role slug → DB role mapping
$roleMap = [
    'super-admin' => 'superadmin',
    'admin' => 'instituteadmin',
    'front-desk' => 'frontdesk',
    'teacher' => 'teacher',
    'student' => 'student',
    'guardian' => 'guardian',
];

Route::any('/api/admin/communications', function() {
    requireAuth();
    requireModule('communication');
    require_once app_path('Http/Controllers/Admin/communications.php');
});

// ─── Super Admin Impersonation ───────────────────────────
Route::post('/api/super-admin/impersonate/{id}', function ($id) {
    if (!isLoggedIn()) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    $user = getCurrentUser();
    if ($user['role'] !== 'superadmin' && $user['role'] !== 'super-admin') return response()->json(['success' => false, 'message' => 'Forbidden'], 403);

    require_once app_path('Models/SuperAdmin/AuditLogModel.php');
    require_once app_path('Http/Controllers/SuperAdmin/SuperAdminController.php');
    $controller = new SuperAdminController();
    $result = $controller->impersonate($id);
    
    return response()->json($result);
});

Route::get('/impersonate-login', function () {
    $token = request('token');
    if (!$token) abort(400, "Token missing");

    require_once app_path('Http/Controllers/SuperAdmin/SuperAdminController.php');
    $controller = new SuperAdminController();
    $controller->impersonateLogin($token);
});

Route::get('/dash/super-admin/stop-impersonating', function () {
    if (!isLoggedIn()) return redirect('/auth/login');
    
    require_once app_path('Http/Controllers/SuperAdmin/SuperAdminController.php');
    $controller = new SuperAdminController();
    $controller->endImpersonation();

    return redirect('/dash/super-admin');
});

Route::get('/dash/{role}/{page?}', function ($role, $page = 'index') use ($roleMap) {
    // Auth check — must be logged in
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . APP_URL . '/login');
        exit;
    }

    $user = getCurrentUser();
    $dbRole = $roleMap[$role] ?? null;

    // RBAC check — user's role must match the dashboard they're accessing
    // Super admin can access any dashboard
    if ($user['role'] !== 'superadmin' && $dbRole !== null && $user['role'] !== $dbRole) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><title>Access Denied</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
        <style>body{font-family:Poppins,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8f9fa;}
        .box{text-align:center;padding:60px;background:#fff;border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:480px;}
        .box h1{color:#d32f2f;font-size:4rem;margin:0;} .box h2{color:#333;margin:10px 0 16px;}
        .box p{color:#666;margin-bottom:24px;} .box a{display:inline-block;padding:12px 28px;background:#006D44;color:#fff;text-decoration:none;border-radius:50px;font-weight:600;transition:0.3s;}
        .box a:hover{background:#005538;transform:translateY(-2px);}</style></head>
        <body><div class="box"><h1>403</h1><h2>Access Denied</h2>
        <p>You don\'t have permission to access this dashboard. Your role: <strong>' . htmlspecialchars($user['role']) . '</strong></p>
        <a href="' . APP_URL . '/dash/' . array_search($user['role'], $roleMap) . '">Go to My Dashboard</a>
        </div></body></html>';
        return;
    }

    $roleDir = str_replace(['_', ' '], '-', strtolower($role));
    $page = $page ?: 'index';
    $page = preg_replace('/\.php$/', '', $page);

    $fileAttempts = [
        resource_path("views/{$roleDir}/{$page}.blade.php") => 'blade',
        resource_path("views/{$roleDir}/index.blade.php") => 'blade',
        resource_path("views/{$roleDir}/{$page}.php") => 'php',
        resource_path("views/{$roleDir}/index.php") => 'php',
    ];

    foreach ($fileAttempts as $file => $type) {
        if (file_exists($file)) {
            $viewDir = dirname($file);
            set_include_path(get_include_path() . PATH_SEPARATOR . $viewDir . PATH_SEPARATOR . resource_path('views/layouts'));

            $pageTitle = ucfirst($role) . ' Dashboard';
            
            if ($type === 'blade') {
                $viewName = basename($file) === 'index.blade.php' ? "{$roleDir}.index" : "{$roleDir}.{$page}";
                echo view($viewName, [
                    'pageTitle' => $pageTitle,
                    'roleCSS' => "{$roleDir}.css"
                ])->render();
            } else {
                $PDO = getDBConnection();
                $pdo = $PDO;

                // For super-admin views, we need to include sidebar.php before the main file
                // The sidebar contains renderSidebar() and getSuperAdminMenu() functions
                // that are called by the view files
                $sidebarFile = $viewDir . '/sidebar.php';
                
                if ($roleDir === 'super-admin' && file_exists($sidebarFile)) {
                    require_once $sidebarFile;
                }

                require_once $file;
            }
            return;
        }
    }

    abort(404, "Page not found.");
});


// ─── API Routes ──────────────────────────────────────────────




Route::get('/api/admin/stats', function() {
    requireAuth();
    require_once app_path('Http/Controllers/Admin/dashboard_stats.php');
});

Route::any('/api/admin/students', function() {
    requireAuth();
    requireModule('student');
    require_once app_path('Http/Controllers/Admin/students.php');
});

Route::post('/api/admin/students/email', function() {
    requireAuth();
    requireModule('student');
    require_once app_path('Http/Controllers/Admin/students.php');
});

Route::any('/api/admin/courses', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/Admin/courses.php');
});

Route::any('/api/admin/course-categories', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/Admin/course_categories.php');
});

Route::any('/api/admin/batches', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/Admin/batches.php');
});

Route::any('/api/admin/subjects', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/Admin/subjects.php');
});

Route::any('/api/admin/subject_allocation', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/Admin/subject_allocation.php');
});

Route::any('/api/admin/inquiries', function() {
    requireAuth();
    requireModule('inquiry');
    require_once app_path('Http/Controllers/Admin/inquiries.php');
});

Route::any('/api/admin/email-settings', function() {
    require_once app_path('Http/Controllers/Admin/email_settings.php');
});

Route::any('/api/admin/email-settings/test', function() {
    require_once app_path('Http/Controllers/Admin/email_settings.php');
});

Route::any('/api/admin/email_templates', function() {
    require_once app_path('Http/Controllers/Admin/email_templates.php');
});

// Front Desk API Routes
Route::get('/api/frontdesk/stats', function() {
    require_once app_path('Http/Controllers/FrontDesk/frontdesk_stats.php');
});

Route::any('/api/frontdesk/students', function() {
    require_once app_path('Http/Controllers/FrontDesk/students.php');
});

Route::any('/api/admin/fees', function() {
    requireAuth();
    requireModule('finance');
    require_once app_path('Http/Controllers/Admin/fees.php');
});

Route::any('/api/admin/exams', function() {
    requireAuth();
    requireModule('exams');
    require_once app_path('Http/Controllers/Admin/exams.php');
});

Route::any('/api/admin/salary', function() {
    requireAuth();
    requireModule('payroll');
    require_once app_path('Http/Controllers/Admin/staff_salary.php');
});

Route::any('/api/admin/staff', function() {
    requireAuth();
    requireModule('frontdesk');
    require_once app_path('Http/Controllers/Admin/staff.php');
});

Route::get('/api/admin/date-convert', function() {
    require_once app_path('Http/Controllers/Admin/date_convert.php');
});

Route::any('/api/admin/timetable', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/Admin/timetable.php');
});

Route::any('/api/admin/rooms', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/Admin/rooms.php');
});

Route::any('/api/admin/academic-calendar', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/Admin/academic_calendar.php');
});

Route::any('/api/admin/homework', function() {
    requireAuth();
    requireModule('homework');
    require_once app_path('Http/Controllers/Admin/homework.php');
});

Route::any('/api/admin/homework/store', function() {
    requireAuth();
    requireModule('homework');
    require_once app_path('Http/Controllers/Admin/homework_store.php');
});

// Expenses Module Routes
Route::any('/api/admin/expenses', function() {
    requireAuth();
    requireModule('finance');
    require_once app_path('Http/Controllers/Admin/expenses.php');
});

Route::get('/api/admin/expenses/stats', function() {
    requireAuth();
    requireModule('finance');
    require_once app_path('Http/Controllers/Admin/expenses_stats.php');
});

Route::any('/api/admin/expense-categories', function() {
    requireAuth();
    requireModule('finance');
    require_once app_path('Http/Controllers/Admin/expense_categories.php');
});

Route::any('/api/admin/academic-calendar/{action}', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/Admin/academic_calendar.php');
});

// Attendance API Routes
Route::any('/api/admin/attendance', function() {
    requireAuth();
    requireModule('attendance');
    require_once app_path('Http/Controllers/Admin/attendance.php');
});

Route::any('/api/admin/attendance/{action}', function($action) {
    requireAuth();
    requireModule('attendance');
    require_once app_path('Http/Controllers/Admin/attendance.php');
});

// Leave Requests API Routes
Route::any('/api/admin/leave-requests', function() {
    requireAuth();
    requireModule('attendance');
    require_once app_path('Http/Controllers/Admin/leave_requests.php');
});

// Audit Logs API Routes
Route::any('/api/admin/audit-logs', function() {
    requireAuth();
    requireModule('system');
    require_once app_path('Http/Controllers/Admin/audit_logs.php');
});

// Front Desk API Routes - Dedicated Front Desk controllers
Route::any('/api/frontdesk/attendance', function() {
    requireAuth();
    requireModule('attendance');
    require_once app_path('Http/Controllers/FrontDesk/attendance.php');
});

Route::any('/api/frontdesk/attendance/{action}', function($action) {
    requireAuth();
    requireModule('attendance');
    require_once app_path('Http/Controllers/FrontDesk/attendance.php');
});

Route::any('/api/frontdesk/attendance/take', function() {
    requireAuth();
    requireModule('attendance');
    require_once app_path('Http/Controllers/FrontDesk/attendance.php');
});

Route::any('/api/frontdesk/inquiries', function() {
    requireAuth();
    requireModule('inquiry');
    require_once app_path('Http/Controllers/FrontDesk/inquiries.php');
});

Route::any('/api/frontdesk/library', function() {
    requireAuth();
    requireModule('library');
    require_once app_path('Http/Controllers/FrontDesk/library.php');
});

Route::any('/api/frontdesk/communications', function() {
    requireAuth();
    requireModule('communication');
    require_once app_path('Http/Controllers/FrontDesk/communications.php');
});

Route::any('/api/frontdesk/batches', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/FrontDesk/batches.php');
});

Route::any('/api/frontdesk/courses', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/FrontDesk/courses.php');
});

Route::any('/api/frontdesk/fee-reports', function() {
    requireAuth();
    requireModule('finance');
    require_once app_path('Http/Controllers/FrontDesk/FeeReports.php');
});

Route::any('/api/frontdesk/leave-requests', function() {
    requireAuth();
    requireModule('attendance');
    require_once app_path('Http/Controllers/FrontDesk/leave_requests.php');
});

Route::any('/api/frontdesk/announcements', function() {
    requireAuth();
    requireModule('communication');
    require_once app_path('Http/Controllers/FrontDesk/announcements.php');
});

Route::any('/api/frontdesk/support', function() {
    requireAuth();
    require_once app_path('Http/Controllers/FrontDesk/support.php');
});



Route::any('/api/student/fees', function() {
    require_once app_path('Http/Controllers/Student/fees.php');
});

// Student Portal API Routes
Route::any('/api/student/dashboard', function() {
    require_once app_path('Http/Controllers/Student/dashboard.php');
});

Route::any('/api/student/classes', function() {
    require_once app_path('Http/Controllers/Student/classes.php');
});

Route::any('/api/student/attendance', function() {
    require_once app_path('Http/Controllers/Student/attendance.php');
});

Route::any('/api/student/leave', function() {
    require_once app_path('Http/Controllers/Student/leave.php');
});

// Phase 2 - New Front Desk Features (Reception Group)
Route::any('/api/frontdesk/visitor-log', function() {
    requireAuth();
    require_once app_path('Http/Controllers/FrontDesk/visitor_log.php');
});

Route::any('/api/frontdesk/appointments', function() {
    requireAuth();
    require_once app_path('Http/Controllers/FrontDesk/appointments.php');
});

Route::any('/api/frontdesk/call-logs', function() {
    requireAuth();
    require_once app_path('Http/Controllers/FrontDesk/call_logs.php');
});

Route::any('/api/frontdesk/complaints', function() {
    requireAuth();
    require_once app_path('Http/Controllers/FrontDesk/complaints.php');
});

Route::any('/api/frontdesk/audit-logs', function() {
    requireAuth();
    requireModule('system');
    require_once app_path('Http/Controllers/FrontDesk/audit_logs.php');
});

Route::any('/api/frontdesk/staff', function() {
    requireAuth();
    requireModule('staff');
    require_once app_path('Http/Controllers/FrontDesk/staff.php');
});

Route::any('/api/frontdesk/subjects', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/FrontDesk/subjects.php');
});

Route::any('/api/frontdesk/timetable', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/FrontDesk/timetable.php');
});

Route::any('/api/frontdesk/subject_allocation', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/FrontDesk/subject_allocation.php');
});

Route::any('/api/frontdesk/homework', function() {
    requireAuth();
    requireModule('exams');
    require_once app_path('Http/Controllers/FrontDesk/homework.php');
});

Route::any('/api/frontdesk/homework/store', function() {
    requireAuth();
    requireModule('exams');
    require_once app_path('Http/Controllers/FrontDesk/homework_store.php');
});

Route::any('/api/frontdesk/salary', function() {
    requireAuth();
    requireModule('finance');
    require_once app_path('Http/Controllers/FrontDesk/staff_salary.php');
});

Route::any('/api/frontdesk/profile', function() {
    requireAuth();
    require_once app_path('Http/Controllers/FrontDesk/profile.php');
});

Route::any('/api/frontdesk/email-settings', function() {
    requireAuth();
    requireModule('system');
    require_once app_path('Http/Controllers/FrontDesk/email_settings.php');
});

Route::any('/api/frontdesk/automation-rules', function() {
    requireAuth();
    requireModule('system');
    require_once app_path('Http/Controllers/FrontDesk/automation_rules.php');
});

Route::any('/api/frontdesk/email_templates', function() {
    requireAuth();
    requireModule('system');
    require_once app_path('Http/Controllers/FrontDesk/email_templates.php');
});

Route::any('/api/frontdesk/global-search', function() {
    requireAuth();
    require_once app_path('Http/Controllers/FrontDesk/global_search.php');
});

Route::any('/api/frontdesk/academic-calendar', function() {
    requireAuth();
    requireModule('academic');
    require_once app_path('Http/Controllers/FrontDesk/academic_calendar.php');
});

Route::any('/api/frontdesk/date-convert', function() {
    requireAuth();
    require_once app_path('Http/Controllers/FrontDesk/date_convert.php');
});

Route::any('/api/frontdesk/billing', function() {
    requireAuth();
    requireModule('finance');
    require_once app_path('Http/Controllers/FrontDesk/billing.php');
});

Route::any('/api/frontdesk/lms', function() {
    requireAuth();
    requireModule('lms');
    require_once app_path('Http/Controllers/FrontDesk/lms.php');
});

Route::any('/api/frontdesk/2fa_setup', function() {
    require_once app_path('Http/Controllers/FrontDesk/profile.php');
});

Route::any('/api/frontdesk/id-card-requests', function() {
    require_once app_path('Http/Controllers/FrontDesk/id_cards.php');
});

Route::any('/api/student/assignments', function() {
    require_once app_path('Http/Controllers/Student/assignments.php');
});

Route::any('/api/student/exams', function() {
    require_once app_path('Http/Controllers/Student/exams.php');
});

Route::any('/api/student/notices', function() {
    require_once app_path('Http/Controllers/Student/notices.php');
});

Route::any('/api/student/library', function() {
    require_once app_path('Http/Controllers/Student/library.php');
});

// Teacher Portal API Routes
Route::any('/api/teacher/dashboard', function() {
    require_once app_path('Http/Controllers/Teacher/dashboard.php');
});

Route::any('/api/teacher/classes', function() {
    require_once app_path('Http/Controllers/Teacher/classes.php');
});

Route::any('/api/teacher/attendance', function() {
    require_once app_path('Http/Controllers/Teacher/attendance.php');
});

Route::any('/api/teacher/profile', function() {
    require_once app_path('Http/Controllers/Teacher/profile.php');
});

Route::any('/api/teacher/payments', function() {
    require_once app_path('Http/Controllers/Teacher/payments.php');
});

// Guardian Portal API Routes
Route::any('/api/guardian/dashboard', function() {
    require_once app_path('Http/Controllers/Guardian/dashboard.php');
});

Route::any('/api/guardian/attendance', function() {
    require_once app_path('Http/Controllers/Guardian/attendance.php');
});

Route::any('/api/guardian/exams', function() {
    require_once app_path('Http/Controllers/Guardian/exams.php');
});

Route::any('/api/guardian/fees', function() {
    require_once app_path('Http/Controllers/Guardian/fees.php');
});

Route::any('/api/guardian/contact', function() {
    require_once app_path('Http/Controllers/Guardian/contact.php');
});

Route::any('/api/guardian/homework', function() {
    require_once app_path('Http/Controllers/Guardian/homework.php');
});

Route::any('/api/guardian/notices', function() {
    require_once app_path('Http/Controllers/Guardian/notices.php');
});


Route::any('/api/student/profile', function() {
    require_once app_path('Http/Controllers/Student/profile.php');
});

Route::any('/api/student/study-materials', function() {
    require_once app_path('Http/Controllers/Student/study_materials.php');
});

Route::any('/api/student/contact', function() {
    require_once app_path('Http/Controllers/Student/contact.php');
});

Route::any('/api/student/leaderboard', function() {
    require_once app_path('Http/Controllers/Student/leaderboard.php');
});

Route::any('/api/frontdesk/fees', function() {
    require_once app_path('Http/Controllers/FrontDesk/fees.php');
});

Route::any('/api/admin/fee-reports', function() {
    requireAuth();
    requireModule('finance');
    require_once app_path('Http/Controllers/Admin/FeeReports.php');
});

Route::any('/api/admin/global-search', function() {
    requireAuth();
    require_once app_path('Http/Controllers/Admin/global_search.php');
});

Route::any('/api/admin/lms', function() {
    requireAuth();
    requireModule('lms');
    require_once app_path('Http/Controllers/Admin/lms.php');
});

Route::any('/api/admin/library', function() {
    requireAuth();
    requireModule('library');
    require_once app_path('Http/Controllers/Admin/library.php');
});

Route::any('/api/admin/communications', function() {
    requireAuth();
    requireModule('communication');
    require_once app_path('Http/Controllers/Admin/communications.php');
});

Route::any('/api/admin/profile', function() {
    require_once app_path('Http/Controllers/Admin/profile.php');
});

Route::any('/api/admin/billing', function() {
    require_once app_path('Http/Controllers/Admin/billing.php');
});

Route::any('/api/admin/accounting', function() {
    enforceAccess('expenses.view', 'finance');
    require_once app_path('Http/Controllers/Admin/accounting.php');
});

Route::get('/api/notifications/count', function() {
    require_once app_path('Http/Controllers/Admin/notifications.php');
});

Route::any('/api/admin/feedback/submit', function() {
    require_once app_path('Http/Controllers/Admin/feedback.php');
});

Route::any('/api/admin/automation-rules', function() {
    requireAuth();
    requireModule('system');
    require_once app_path('Http/Controllers/Admin/automation_rules.php');
});

// Report Engine Routes


// Tenant Management Routes
// Tenant Management Routes (Protected)
Route::middleware(['auth.superadmin'])->group(function () {
    // Super Admin SPA Pages - Refactored to use MVC architecture
    Route::get('/pages/super_admin/{page}', function ($page) {
        // Autoload/require the models and controllers needed
        require_once app_path('Models/SuperAdmin/TenantModel.php');
        require_once app_path('Models/SuperAdmin/AuditLogModel.php');
        require_once app_path('Models/SuperAdmin/PlanModel.php');
        require_once app_path('Http/Controllers/SuperAdmin/DashboardController.php');
        require_once app_path('Http/Controllers/SuperAdmin/TenantController.php');
        require_once app_path('Http/Controllers/SuperAdmin/RevenueController.php');
        require_once app_path('Http/Controllers/SuperAdmin/LogController.php');
        require_once app_path('Http/Controllers/SuperAdmin/SystemController.php');
        require_once app_path('Http/Controllers/SuperAdmin/SuperAdminRouter.php');

        $router = new \App\Http\Controllers\SuperAdmin\SuperAdminRouter();
        return $router->handle($page);
    });
    
    Route::post('/api/super-admin/reports/generate', [App\Http\Controllers\SuperAdmin\ReportController::class, 'generate']);
    Route::get('/api/super_admin_stats.php', function() {
        require_once app_path('Http/Controllers/SuperAdmin/super_admin_stats.php');
    });
    
    // New SPA API endpoints
    Route::match(['get', 'post'], '/api/superadmin/TenantsApi.php', function() {
        require_once app_path('Http/Controllers/SuperAdmin/TenantsApi.php');
    });
    Route::get('/api/superadmin/PlansApi.php', function() {
        require_once app_path('Http/Controllers/SuperAdmin/PlansApi.php');
    });
    Route::get('/api/superadmin/RevenueApi.php', function() {
        require_once app_path('Http/Controllers/SuperAdmin/RevenueApi.php');
    });
    Route::get('/api/superadmin/SupportApi.php', function() {
        require_once app_path('Http/Controllers/SuperAdmin/SupportApi.php');
    });
    
    Route::get('/api/super-admin/tenants', [App\Http\Controllers\API\SuperAdminController::class, 'tenants']);
    Route::post('/api/super-admin/tenants/save', [App\Http\Controllers\API\SuperAdminController::class, 'saveTenant']);
    Route::post('/api/super-admin/tenants/suspend/{id}', [App\Http\Controllers\API\SuperAdminController::class, 'suspendTenant']);
    Route::post('/api/super-admin/tenants/activate/{id}', [App\Http\Controllers\API\SuperAdminController::class, 'activateTenant']);

    Route::post('/api/super-admin/tenants/update', [App\Http\Controllers\API\SuperAdminController::class, 'updateTenant']);
    Route::match(['post', 'delete'], '/api/super-admin/tenants/delete/{id?}', [App\Http\Controllers\API\SuperAdminController::class, 'deleteTenant']);
    Route::post('/api/super-admin/tenants/update-plan', [App\Http\Controllers\API\SuperAdminController::class, 'updatePlan']);

    // Plan and Pricing Management
    Route::get('/api/get_plan_features.php', function() {
        require_once app_path('Http/Controllers/SuperAdmin/get_plan_features.php');
    });
    Route::post('/api/update_plan_features.php', function() {
        require_once app_path('Http/Controllers/SuperAdmin/update_plan_features.php');
    });
});


// Debug route (dev only)
if (defined('APP_ENV') && APP_ENV === 'development') {
    Route::get('/debug-uri', function() {
        return [
            'uri' => request()->getRequestUri(),
            'path' => request()->getPathInfo(),
            'base' => request()->getBaseUrl(),
            'url' => request()->url(),
            'logged_in' => isLoggedIn(),
            'user' => getCurrentUser(),
        ];
    });

    // CSRF Debug endpoint - for troubleshooting only
    Route::get('/api/debug/csrf', function() {
        header('Content-Type: application/json');
        
        $debug = \App\Helpers\CsrfHelper::debugCsrf();
        echo json_encode($debug, JSON_PRETTY_PRINT);
    });

    // CSRF token refresh endpoint - returns new token without validation
    Route::post('/api/csrf/refresh', function() {
        header('Content-Type: application/json');
        
        $newToken = \App\Helpers\CsrfHelper::generateCsrfToken();
        
        // Return new token in both body and header
        header('X-CSRF-Token: ' . $newToken);
        
        echo json_encode([
            'success' => true,
            'csrf_token' => $newToken
        ]);
    });
}
