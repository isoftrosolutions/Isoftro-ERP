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

    $email = $_POST['username'] ?? $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = $_POST['remember'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    $db = getDBConnection();

    try {
        // Find user by email
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND status = 'active' LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            exit;
        }

        // Check account lock
        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            echo json_encode(['success' => false, 'message' => 'Account locked. Try again later.']);
            exit;
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            // Record failed attempt
            try {
                $stmt = $db->prepare("INSERT INTO failed_logins (user_id, ip_address, attempted_at) VALUES (:uid, :ip, NOW())");
                $stmt->execute([':uid' => $user['id'], ':ip' => $_SERVER['REMOTE_ADDR']]);
            } catch (Exception $e) { /* table may not exist yet */ }

            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            exit;
        }

        // Build role slug for redirect
        $roleSlugMap = [
            'superadmin' => 'super-admin',
            'instituteadmin' => 'admin',
            'frontdesk' => 'front-desk',
            'teacher' => 'teacher',
            'student' => 'student',
            'guardian' => 'guardian',
        ];
        $slug = $roleSlugMap[$user['role']] ?? strtolower($user['role']);

        // Fetch tenant logo if user has a tenant
        $tenantLogo = null;
        if (!empty($user['tenant_id'])) {
            $stmtTenant = $db->prepare("SELECT logo_path FROM tenants WHERE id = :tid LIMIT 1");
            $stmtTenant->execute([':tid' => $user['tenant_id']]);
            $tenantData = $stmtTenant->fetch();
            if ($tenantData && !empty($tenantData['logo_path'])) {
                $tenantLogo = $tenantData['logo_path'];
                // Fix old paths that don't have /public prefix
                if (strpos($tenantLogo, '/uploads/') === 0 && strpos($tenantLogo, '/public/') !== 0) {
                    $tenantLogo = '/public' . $tenantLogo;
                }
            }
        }

        // Create session
        session_regenerate_id(true);
        $_SESSION['userData'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['full_name'] ?? $user['name'] ?? $user['email'],
            'role' => $user['role'],
            'tenant_id' => $user['tenant_id'],
            'avatar' => $user['avatar'] ?? $user['photo_url'] ?? null,
            'last_login' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
        ];
        
        // Also set tenant logo in session for easy access
        $_SESSION['tenant_logo'] = $tenantLogo;
        $_SESSION['institute_logo'] = $tenantLogo;
        $_SESSION['last_activity'] = time();

        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $user['id']]);

        // Clear failed logins
        try {
            $stmt = $db->prepare("DELETE FROM failed_logins WHERE user_id = :uid");
            $stmt->execute([':uid' => $user['id']]);
        } catch (Exception $e) { /* table may not exist yet */ }

        // Remember me cookie
        if ($remember === 'on') {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60);
            setcookie('remember_token', $token, $expiry, '/', '', false, true);
            try {
                $stmt = $db->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:uid, :token, DATE_ADD(NOW(), INTERVAL 30 DAY))");
                $stmt->execute([':uid' => $user['id'], ':token' => hash('sha256', $token)]);
            } catch (Exception $e) { /* table may not exist yet */ }
        }

        $redirect = $_SESSION['redirect_after_login'] ?? (APP_URL . '/dash/' . $slug);
        unset($_SESSION['redirect_after_login']);
        
        // Generate loading screen URL with session token
        $loadingToken = bin2hex(random_bytes(32));
        $_SESSION['loading_token'] = $loadingToken;
        $_SESSION['loading_token_expires'] = time() + 60; // 1 minute expiry
        $_SESSION['pending_redirect'] = $redirect;
        
        $loadingScreenUrl = APP_URL . '/loading?token=' . urlencode($loadingToken) . '&redirect=' . urlencode($redirect);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful!',
            'redirect' => $redirect,
            'loading_screen' => $loadingScreenUrl,
            'user' => [
                'id' => $user['id'],
                'name' => $user['full_name'] ?? $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'avatar' => $user['avatar'] ?? $user['photo_url'] ?? null,
            ]
        ]);
        exit;

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'System error. Please try again.']);
        exit;
    }
});

// Forgot Password Page (GET)
Route::get('/auth/forgot-password', function () {
    require_once resource_path('views/auth/forgot-password.php');
});

// Send Password Reset (POST)
Route::post('/auth/send_password_reset', function () {
    require_once resource_path('views/auth/send_password_reset.php');
});

// Reset Password Page (GET/POST)
Route::match(['get', 'post'], '/auth/reset-password', function () {
    require_once resource_path('views/auth/reset-password.php');
});

// Verify OTP (POST)
Route::post('/auth/verify-otp', function () {
    require_once resource_path('views/auth/verify_otp.php');
});

// Logout
Route::match(['get', 'post'], '/auth/logout', function () {
    // Destroy session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    // Clear remember cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }

    header('Location: ' . APP_URL . '/auth/login');
    exit;
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
        resource_path("views/{$roleDir}/{$page}.php"),
        resource_path("views/{$roleDir}/index.php"),
    ];

    foreach ($fileAttempts as $file) {
        if (file_exists($file)) {
            $viewDir = dirname($file);
            set_include_path(get_include_path() . PATH_SEPARATOR . $viewDir . PATH_SEPARATOR . resource_path('views/layouts'));

            $pageTitle = ucfirst($role) . ' Dashboard';
            $PDO = getDBConnection();
            $pdo = $PDO;

            $sidebarFile = $viewDir . '/sidebar.php';
            if (file_exists($sidebarFile)) {
                require_once $sidebarFile;
            }

            require_once $file;
            return;
        }
    }

    abort(404, "Page not found.");
});


// ─── API Routes ──────────────────────────────────────────────

Route::get('/api/super_admin_stats.php', function() {
    require_once app_path('Http/Controllers/SuperAdmin/super_admin_stats.php');
});

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

// Front Desk API Routes - Reuse Admin controllers with frontdesk role access
Route::any('/api/frontdesk/attendance', function() {
    require_once app_path('Http/Controllers/Admin/attendance.php');
});

Route::any('/api/frontdesk/attendance/{action}', function($action) {
    require_once app_path('Http/Controllers/Admin/attendance.php');
});

Route::any('/api/frontdesk/inquiries', function() {
    require_once app_path('Http/Controllers/Admin/inquiries.php');
});

Route::any('/api/frontdesk/library', function() {
    require_once app_path('Http/Controllers/Admin/library.php');
});

Route::any('/api/frontdesk/communications', function() {
    require_once app_path('Http/Controllers/Admin/communications.php');
});

Route::any('/api/frontdesk/batches', function() {
    require_once app_path('Http/Controllers/Admin/batches.php');
});

Route::any('/api/frontdesk/courses', function() {
    require_once app_path('Http/Controllers/Admin/courses.php');
});

Route::any('/api/frontdesk/fee-reports', function() {
    require_once app_path('Http/Controllers/Admin/FeeReports.php');
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

Route::any('/api/student/profile', function() {
    require_once app_path('Http/Controllers/Student/profile.php');
});

Route::any('/api/frontdesk/fees', function() {
    require_once app_path('Http/Controllers/Admin/fees.php');
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

// Report Engine Routes
Route::post('/api/super-admin/reports/generate', [App\Http\Controllers\SuperAdmin\ReportController::class, 'generate']);

// Tenant Management Routes
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
