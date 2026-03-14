<?php

use Illuminate\Support\Facades\Route;

/**
 * Hamro ERP — Main Router (Laravel 11)
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

// Login API (POST) — session-based authentication
Route::post('/api/login', function () {
    header('Content-Type: application/json');
    
    $isApi = isset($_GET['api']) || 
             (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') ||
             (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if (!$isApi && !\App\Helpers\CsrfHelper::validateCsrfToken()) {
        echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh the page.']);
        exit;
    }

    $_GET['api'] = 1;
    $_POST['action'] = 'login';
    require_once app_path('Http/Controllers/AuthController.php');
});

// Change Password API (Internal)
Route::post('/api/auth/change-password', function() {
    requireAuth();
    header('Content-Type: application/json');
    if (!\App\Helpers\CsrfHelper::validateCsrfToken()) {
        echo json_encode(['success' => false, 'error' => 'Security token expired. Please refresh the page.']);
        exit;
    }
    
    $_GET['api'] = 1;
    $_POST['action'] = 'change_password';
    require_once app_path('Http/Controllers/AuthController.php');
});

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

// Logout
Route::match(['get', 'post'], '/auth/logout', function () {
    require_once app_path('Http/Controllers/AuthController.php');
    $auth = new \App\Http\Controllers\AuthController();
    $auth->logout();
    
    return redirect('/auth/login');
});

// Legacy logout redirect
Route::get('/logout', function() {
    return redirect('/auth/logout');
});

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
    require_once app_path('Http/Controllers/Admin/communications.php');
});

// ─── Super Admin Impersonation ───────────────────────────
Route::get('/dash/super-admin/impersonate/{id}', function ($id) {
    if (!isLoggedIn()) return redirect('/auth/login');
    $user = getCurrentUser();
    if ($user['role'] !== 'superadmin') abort(403, "Only Super Admins can impersonate.");

    require_once app_path('Http/Controllers/SuperAdmin/SuperAdminController.php');
    $controller = new SuperAdminController();
    $result = $controller->impersonate($id);

    if ($result['success']) {
        return redirect('/dash/admin');
    } else {
        abort(500, "Impersonation failed: " . $result['error']);
    }
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
    require_once app_path('Http/Controllers/Admin/dashboard_stats.php');
});

Route::any('/api/admin/students', function() {
    require_once app_path('Http/Controllers/Admin/students.php');
});

Route::post('/api/admin/students/email', function() {
    require_once app_path('Http/Controllers/Admin/students.php');
});

Route::any('/api/admin/courses', function() {
    require_once app_path('Http/Controllers/Admin/courses.php');
});

Route::any('/api/admin/course-categories', function() {
    require_once app_path('Http/Controllers/Admin/course_categories.php');
});

Route::any('/api/admin/batches', function() {
    require_once app_path('Http/Controllers/Admin/batches.php');
});

Route::any('/api/admin/subjects', function() {
    require_once app_path('Http/Controllers/Admin/subjects.php');
});

Route::any('/api/admin/subject_allocation', function() {
    require_once app_path('Http/Controllers/Admin/subject_allocation.php');
});

Route::any('/api/admin/inquiries', function() {
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
    require_once app_path('Http/Controllers/Admin/fees.php');
});

Route::any('/api/admin/exams', function() {
    require_once app_path('Http/Controllers/Admin/exams.php');
});

Route::any('/api/admin/salary', function() {
    require_once app_path('Http/Controllers/Admin/staff_salary.php');
});

Route::any('/api/admin/staff', function() {
    require_once app_path('Http/Controllers/Admin/staff.php');
});

Route::get('/api/admin/date-convert', function() {
    require_once app_path('Http/Controllers/Admin/date_convert.php');
});

Route::any('/api/admin/timetable', function() {
    require_once app_path('Http/Controllers/Admin/timetable.php');
});

Route::any('/api/admin/academic-calendar', function() {
    require_once app_path('Http/Controllers/Admin/academic_calendar.php');
});

Route::any('/api/admin/homework', function() {
    require_once app_path('Http/Controllers/Admin/homework.php');
});

Route::any('/api/admin/homework/store', function() {
    require_once app_path('Http/Controllers/Admin/homework_store.php');
});

Route::any('/api/admin/academic-calendar/{action}', function() {
    require_once app_path('Http/Controllers/Admin/academic_calendar.php');
});

// Attendance API Routes
Route::any('/api/admin/attendance', function() {
    require_once app_path('Http/Controllers/Admin/attendance.php');
});

Route::any('/api/admin/attendance/{action}', function($action) {
    require_once app_path('Http/Controllers/Admin/attendance.php');
});

// Leave Requests API Routes
Route::any('/api/admin/leave-requests', function() {
    require_once app_path('Http/Controllers/Admin/leave_requests.php');
});

// Audit Logs API Routes
Route::any('/api/admin/audit-logs', function() {
    require_once app_path('Http/Controllers/Admin/audit_logs.php');
});

// Front Desk API Routes - Dedicated Front Desk controllers
Route::any('/api/frontdesk/attendance', function() {
    require_once app_path('Http/Controllers/FrontDesk/attendance.php');
});

Route::any('/api/frontdesk/attendance/{action}', function($action) {
    require_once app_path('Http/Controllers/FrontDesk/attendance.php');
});

Route::any('/api/frontdesk/attendance/take', function() {
    require_once app_path('Http/Controllers/FrontDesk/attendance.php');
});

Route::any('/api/frontdesk/inquiries', function() {
    require_once app_path('Http/Controllers/FrontDesk/inquiries.php');
});

Route::any('/api/frontdesk/library', function() {
    require_once app_path('Http/Controllers/FrontDesk/library.php');
});

Route::any('/api/frontdesk/communications', function() {
    require_once app_path('Http/Controllers/FrontDesk/communications.php');
});

Route::any('/api/frontdesk/batches', function() {
    require_once app_path('Http/Controllers/FrontDesk/batches.php');
});

Route::any('/api/frontdesk/courses', function() {
    require_once app_path('Http/Controllers/FrontDesk/courses.php');
});

Route::any('/api/frontdesk/fee-reports', function() {
    require_once app_path('Http/Controllers/FrontDesk/FeeReports.php');
});

Route::any('/api/frontdesk/leave-requests', function() {
    require_once app_path('Http/Controllers/FrontDesk/leave_requests.php');
});

Route::any('/api/frontdesk/announcements', function() {
    require_once app_path('Http/Controllers/FrontDesk/announcements.php');
});

Route::any('/api/frontdesk/support', function() {
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
    require_once app_path('Http/Controllers/FrontDesk/visitor_log.php');
});

Route::any('/api/frontdesk/appointments', function() {
    require_once app_path('Http/Controllers/FrontDesk/appointments.php');
});

Route::any('/api/frontdesk/call-logs', function() {
    require_once app_path('Http/Controllers/FrontDesk/call_logs.php');
});

Route::any('/api/frontdesk/complaints', function() {
    require_once app_path('Http/Controllers/FrontDesk/complaints.php');
});

Route::any('/api/frontdesk/audit-logs', function() {
    require_once app_path('Http/Controllers/FrontDesk/audit_logs.php');
});

Route::any('/api/frontdesk/staff', function() {
    require_once app_path('Http/Controllers/FrontDesk/staff.php');
});

Route::any('/api/frontdesk/subjects', function() {
    require_once app_path('Http/Controllers/FrontDesk/subjects.php');
});

Route::any('/api/frontdesk/timetable', function() {
    require_once app_path('Http/Controllers/FrontDesk/timetable.php');
});

Route::any('/api/frontdesk/subject_allocation', function() {
    require_once app_path('Http/Controllers/FrontDesk/subject_allocation.php');
});

Route::any('/api/frontdesk/homework', function() {
    require_once app_path('Http/Controllers/FrontDesk/homework.php');
});

Route::any('/api/frontdesk/homework/store', function() {
    require_once app_path('Http/Controllers/FrontDesk/homework_store.php');
});

Route::any('/api/frontdesk/salary', function() {
    require_once app_path('Http/Controllers/FrontDesk/staff_salary.php');
});

Route::any('/api/frontdesk/profile', function() {
    require_once app_path('Http/Controllers/FrontDesk/profile.php');
});

Route::any('/api/frontdesk/email-settings', function() {
    require_once app_path('Http/Controllers/FrontDesk/email_settings.php');
});

Route::any('/api/frontdesk/automation-rules', function() {
    require_once app_path('Http/Controllers/FrontDesk/automation_rules.php');
});

Route::any('/api/frontdesk/email_templates', function() {
    require_once app_path('Http/Controllers/FrontDesk/email_templates.php');
});

Route::any('/api/frontdesk/global-search', function() {
    require_once app_path('Http/Controllers/FrontDesk/global_search.php');
});

Route::any('/api/frontdesk/academic-calendar', function() {
    require_once app_path('Http/Controllers/FrontDesk/academic_calendar.php');
});

Route::any('/api/frontdesk/date-convert', function() {
    require_once app_path('Http/Controllers/FrontDesk/date_convert.php');
});

Route::any('/api/frontdesk/billing', function() {
    require_once app_path('Http/Controllers/FrontDesk/billing.php');
});

Route::any('/api/frontdesk/lms', function() {
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
    require_once app_path('Http/Controllers/Admin/FeeReports.php');
});

Route::any('/api/admin/global-search', function() {
    require_once app_path('Http/Controllers/Admin/global_search.php');
});

Route::any('/api/admin/lms', function() {
    require_once app_path('Http/Controllers/Admin/lms.php');
});

Route::any('/api/admin/library', function() {
    require_once app_path('Http/Controllers/Admin/library.php');
});

Route::any('/api/admin/communications', function() {
    require_once app_path('Http/Controllers/Admin/communications.php');
});

Route::any('/api/admin/profile', function() {
    require_once app_path('Http/Controllers/Admin/profile.php');
});

Route::any('/api/admin/billing', function() {
    require_once app_path('Http/Controllers/Admin/billing.php');
});

Route::get('/api/notifications/count', function() {
    require_once app_path('Http/Controllers/Admin/notifications.php');
});

Route::any('/api/admin/feedback/submit', function() {
    require_once app_path('Http/Controllers/Admin/feedback.php');
});

Route::any('/api/admin/automation-rules', function() {
    require_once app_path('Http/Controllers/Admin/automation_rules.php');
});

// Report Engine Routes


// Tenant Management Routes
// Tenant Management Routes (Protected)
Route::middleware(['auth.superadmin'])->group(function () {
    // Super Admin SPA Pages - serve from resources/views/super-admin/
    Route::get('/pages/super_admin/{page}', function ($page) {
        $page = preg_replace('/\.php$/', '', $page);
        $fileAttempts = [
            resource_path("views/super-admin/{$page}.php") => resource_path("views/super-admin/{$page}.php"),
            resource_path("views/super-admin/{$page}.blade.php") => resource_path("views/super-admin/{$page}.blade.php"),
        ];
        
        foreach ($fileAttempts as $file) {
            if (file_exists($file)) {
                // Ensure sidebar is included as it defines functions used in super-admin views
                $sidebarFile = resource_path('views/super-admin/sidebar.php');
                if (file_exists($sidebarFile)) {
                    require_once $sidebarFile;
                }
                
                require_once $file;
                return;
            }
        }
        http_response_code(404);
        echo 'Page not found';
    });
    
    Route::post('/api/super-admin/reports/generate', [App\Http\Controllers\SuperAdmin\ReportController::class, 'generate']);
    Route::get('/api/super_admin_stats.php', function() {
        require_once app_path('Http/Controllers/SuperAdmin/super_admin_stats.php');
    });
    
    // New SPA API endpoints
    Route::get('/api/superadmin/TenantsApi.php', function() {
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
    
    Route::get('/api/super-admin/tenants', function() {
        require_once app_path('Http/Controllers/tenants.php');
    });
    Route::post('/api/super-admin/tenants/save', function() {
        require_once app_path('Http/Controllers/save_tenant.php');
    });
    Route::post('/api/super-admin/tenants/update', function() {
        require_once app_path('Http/Controllers/update_tenant.php');
    });
    Route::post('/api/super-admin/tenants/delete', function() {
        require_once app_path('Http/Controllers/delete_tenant.php');
    });
    Route::post('/api/super-admin/tenants/update-plan', function() {
        require_once app_path('Http/Controllers/update_plan.php');
    });

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
}
