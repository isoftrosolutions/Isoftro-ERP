<?php
/**
 * isoftro — Configuration File
 * Platform Blueprint V3.0
 */

// Path Constants - must be defined first
if (!defined('APP_ROOT'))
    define('APP_ROOT', realpath(__DIR__ . '/../'));

// Load environment variables from .env
if (file_exists(APP_ROOT . '/.env')) {
    $lines = file(APP_ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                $value = trim($value, "\"' ");
                putenv("$key=$value");
            }
        }
    }
}

// Database Configuration
if (!defined('DB_HOST'))
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_NAME'))
    define('DB_NAME', getenv('DB_DATABASE') ?: 'hamrolabs_db');
if (!defined('DB_USER'))
    define('DB_USER', getenv('DB_USERNAME') ?: 'root');
if (!defined('DB_PASS'))
    define('DB_PASS', getenv('DB_PASSWORD') ?: '');

// Application URL - needed for session params
if (!defined('APP_URL'))
    define('APP_URL', getenv('APP_URL') ?: 'http://localhost/erp');

// Initialize session with secure params - DEPRECATED for JWT
// But kept as minimal fallback for temporary view-state if absolutely needed.
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Application Configuration
if (!defined('APP_NAME')) {
    // Try to get tenant name from JWT if available, else env
    $tenantName = null;
    try {
        if (function_exists('auth') && auth('api')->check()) {
            $user = auth('api')->user();
            // We'll need a way to get tenant name here, maybe from a cache or join
            // For now fallback to env
        }
    } catch (\Exception $e) {}
    
    $dynamicAppName = $_SESSION['tenant_name'] ?? getenv('APP_NAME') ?: 'isoftro';
    define('APP_NAME', $dynamicAppName);
}
if (!defined('APP_VERSION'))
    define('APP_VERSION', '3.0');

if (!defined('APP_ENV'))
    define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Path Constants - must be defined first
if (!defined('APP_ROOT'))
    define('APP_ROOT', realpath(__DIR__ . '/../'));

if (!defined('VIEWS_PATH'))
    define('VIEWS_PATH', APP_ROOT . '/resources/views');

if (!defined('UPLOAD_PATH'))
    define('UPLOAD_PATH', 'uploads/');

if (!defined('MAX_FILE_SIZE'))
    define('MAX_FILE_SIZE', 5242880); // 5MB in bytes

if (!defined('ALLOWED_FILE_TYPES'))
    define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Security Configuration - Sourced from .env for SaaS grade security
if (!defined('HASH_ALGO'))
    define('HASH_ALGO', 'sha256');

if (!defined('SESSION_LIFETIME'))
    define('SESSION_LIFETIME', getenv('SESSION_LIFETIME') ?: 3600);

if (!defined('MAX_LOGIN_ATTEMPTS'))
    define('MAX_LOGIN_ATTEMPTS', 5);

if (!defined('LOGIN_LOCKOUT_TIME'))
    define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

if (!defined('JWT_SECRET'))
    define('JWT_SECRET', getenv('JWT_SECRET') ?: 'PLEASE_SET_JWT_SECRET_IN_ENV');

if (!defined('JWT_ALGORITHM'))
    define('JWT_ALGORITHM', getenv('JWT_ALGORITHM') ?: 'HS256');

if (!defined('PII_ENCRYPTION_KEY'))
    define('PII_ENCRYPTION_KEY', getenv('PII_ENCRYPTION_KEY') ?: 'PLEASE_SET_PII_ENCRYPTION_KEY_IN_ENV');

// Email Configuration - Sourced from .env
if (!defined('SMTP_HOST'))
    define('SMTP_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');

if (!defined('SMTP_PORT'))
    define('SMTP_PORT', getenv('MAIL_PORT') ?: 465);

if (!defined('SMTP_USERNAME'))
    define('SMTP_USERNAME', getenv('MAIL_USERNAME') ?: 'isoftrosolutions@gmail.com');

if (!defined('SMTP_PASSWORD'))
    define('SMTP_PASSWORD', getenv('MAIL_PASSWORD') ?: '');

if (!defined('FROM_EMAIL'))
    define('FROM_EMAIL', getenv('MAIL_FROM_ADDRESS') ?: 'isoftrosolutions@gmail.com');

if (!defined('FROM_NAME'))
    define('FROM_NAME', getenv('MAIL_FROM_NAME') ?: (defined('APP_NAME') ? APP_NAME : 'iSoftro ERP'));

// Pagination Configuration
if (!defined('RECORDS_PER_PAGE'))
    define('RECORDS_PER_PAGE', 20);

if (!defined('MAX_PAGE_LINKS'))
    define('MAX_PAGE_LINKS', 10);

// Date and Time Configuration
if (!defined('DATE_FORMAT'))
    define('DATE_FORMAT', 'Y-m-d');
if (!defined('DATETIME_FORMAT'))
    define('DATETIME_FORMAT', 'Y-m-d H:i:s');
if (!defined('TIMEZONE'))
    define('TIMEZONE', 'Asia/Kathmandu');

// Role-Based Access Control (RBAC)
global $ROLES;
$ROLES = [
    'superadmin' => [
        'name' => 'Super Admin',
        'permissions' => ['*'], // Access to all features
        'dashboard' => '/dash/super-admin',
        'color' => '#8141A5'
    ],
    'instituteadmin' => [
        'name' => 'Institute Admin',
        'permissions' => [
            'dashboard.view',
            'students.view', 'students.add', 'students.edit', 'students.delete',
            'teachers.view', 'teachers.add', 'teachers.edit', 'teachers.delete',
            'courses.view', 'courses.add', 'courses.edit', 'courses.delete',
            'classes.view', 'classes.add', 'classes.edit', 'classes.delete',
            'attendance.view', 'attendance.mark',
            'exams.view', 'exams.add', 'exams.edit', 'exams.delete',
            'grades.view', 'grades.add', 'grades.edit', 'grades.delete',
            'reports.view',
            'settings.view', 'settings.edit',
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete', 'expense_categories.view'
        ],
        'dashboard' => '/dash/admin',
        'color' => '#00B894'
    ],
    'teacher' => [
        'name' => 'Teacher',
        'permissions' => [
            'dashboard.view',
            'attendance.view', 'attendance.mark',
            'exams.view', 'exams.add', 'exams.edit',
            'grades.view', 'grades.add', 'grades.edit',
            'students.view',
            'reports.view'
        ],
        'dashboard' => '/dash/teacher',
        'color' => '#3B82F6'
    ],
    'student' => [
        'name' => 'Student',
        'permissions' => [
            'dashboard.view',
            'attendance.view',
            'exams.view',
            'grades.view',
            'timetable.view',
            'fees.view',
            'reports.view'
        ],
        'dashboard' => '/dash/student',
        'color' => '#F59E0B'
    ],
    'guardian' => [
        'name' => 'Guardian',
        'permissions' => [
            'dashboard.view',
            'attendance.view',
            'exams.view',
            'grades.view',
            'timetable.view',
            'fees.view',
            'reports.view',
            'messages.view', 'messages.send'
        ],
        'dashboard' => '/dash/guardian',
        'color' => '#009E7E'
    ],
    'frontdesk' => [
        'name' => 'Front Desk',
        'permissions' => [
            'dashboard.view',
            'students.view', 'students.add', 'students.edit',
            'teachers.view', 'teachers.add', 'teachers.edit',
            'attendance.view',
            'fees.view', 'fees.add', 'fees.edit',
            'visitors.view', 'visitors.add', 'visitors.edit',
            'messages.view', 'messages.send',
            'reports.view'
        ],
        'dashboard' => '/dash/front-desk',
        'color' => '#E11D48'
    ]
];

// Database Connection
if (!function_exists('getDBConnection')) {
    function getDBConnection()
    {
        static $pdo = null;
        if ($pdo !== null) {
            return $pdo;
        }
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Native prepares preferred with explicit binding
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        }
        catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            if (APP_ENV === 'development') {
                die("Database connection failed: " . $e->getMessage());
            }
            else {
                die("Database connection failed. Please try again later.");
            }
        }
    }
}

// Helper Functions
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

// Security & CSRF
if (!class_exists('App\Helpers\CsrfHelper')) {
    require_once APP_ROOT . '/app/Helpers/CsrfHelper.php';
}

if (!class_exists('App\Helpers\Logger')) {
    require_once APP_ROOT . '/app/Helpers/Logger.php';
}

if (!class_exists('App\Helpers\StatsHelper')) {
    require_once APP_ROOT . '/app/Helpers/StatsHelper.php';
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken()
    {
        return \App\Helpers\CsrfHelper::getCsrfToken();
    }
}

if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token)
    {
        // JWT is its own security, CSRF is disabled globally in this migration
        return true; 
    }
}

if (!function_exists('getCsrfToken')) {
    function getCsrfToken()
    {
        return \App\Helpers\CsrfHelper::getCsrfToken();
    }
}

if (!function_exists('csrfMetaTag')) {
    function csrfMetaTag()
    {
        return '<!-- CSRF disabled - Using JWT -->';
    }
}

if (!function_exists('csrfJsHeader')) {
    function csrfJsHeader()
    {
        return '<!-- CSRF disabled - Using JWT -->';
    }
}

if (!function_exists('redirect')) {
    function redirect($url)
    {
        header("Location: $url");
        exit();
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn()
    {
        try {
            // Strictly check JWT from cookie or standard Authorization header
            $token = $_COOKIE['token'] ?? null;

            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
                if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
                    $token = $matches[1];
                }
            }

            if ($token) {
                // Ensure the token represents a valid active session via decodable payload
                $parts = explode('.', $token);
                if (count($parts) === 3) {
                    $payload = json_decode(base64_decode($parts[1]), true);
                    if (isset($payload['exp']) && $payload['exp'] > time() && isset($payload['user_id'])) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
             // Silence errors
        }

        return false;
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser()
    {
        try {
            $token = $_COOKIE['token'] ?? null;
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
                if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
                    $token = $matches[1];
                }
            }

            if ($token) {
                // Decode token to get essential info without hitting DB on every request.
                // Token already validated for structure in isLoggedIn().
                $parts = explode('.', $token);
                if (count($parts) === 3) {
                    $payload = json_decode(base64_decode($parts[1]), true);
                    if ($payload && isset($payload['user_id']) && $payload['exp'] > time()) {
                        $userArray = [
                            'id' => $payload['user_id'],
                            'tenant_id' => $payload['tenant_id'] ?? null,
                            'role' => $payload['role'] ?? null,
                            'is_jwt' => true
                        ];
                        // Hydrate legacy session explicitly for file dependencies but NOT for auth checks
                        $_SESSION['userData'] = $userArray;
                        return $userArray;
                    }
                }
            }
        } catch (\Exception $e) {}
        
        return null;
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission)
    {
        if (!isLoggedIn())
            return false;
        $user = getCurrentUser();
        global $ROLES;
        $role = $user['role'] ?? '';
        if (!isset($ROLES[$role]))
            return false;
        $perms = $ROLES[$role]['permissions'];
        // Wildcard = all permissions
        if (in_array('*', $perms))
            return true;
        return in_array($permission, $perms);
    }
}

/**
 * --- SIMPLE FEATURE SYSTEM ---
 * Load all enabled features for a specific institute into session.
 */
if (!function_exists('loadFeatures')) {
    function loadFeatures($tenantId) {
        if (empty($tenantId)) {
            $_SESSION['enabled_features'] = [];
            $_SESSION['loaded_tenant_id'] = null;
            return;
        }
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("
                SELECT f.feature_key
                FROM system_features f
                JOIN institute_feature_access ifa ON f.id = ifa.feature_id
                WHERE ifa.tenant_id = :tenant_id 
                AND ifa.is_enabled = 1
                AND f.status = 'active'
            ");
            $stmt->execute(['tenant_id' => $tenantId]);
            $features = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $_SESSION['enabled_features'] = $features ?: [];
            $_SESSION['loaded_tenant_id'] = $tenantId;
            $_SESSION['features_loaded_at'] = time();
        } catch (\PDOException $e) {
            error_log("[FEATURE-GATE] DB error: " . $e->getMessage());
            $_SESSION['enabled_features'] = [];
            $_SESSION['loaded_tenant_id'] = $tenantId;
        }
    }
}

/**
 * Check if a feature is enabled for the current institute.
 */
if (!function_exists('hasFeature')) {
    function hasFeature($featureKey) {
        $featureKey = strtolower(trim($featureKey));

        // Skip check for superadmin
        $user = getCurrentUser();
        if ($user && (($user['role'] ?? '') === 'superadmin' || ($user['role'] ?? '') === 'super-admin')) {
            return true;
        }

        // Identify currently authenticated tenant
        $tenantId = $user['tenant_id'] ?? $_SESSION['tenant_id'] ?? null;

        if (empty($tenantId)) {
            return false;
        }

        // Core features always enabled
        if (in_array($featureKey, ['dashboard', 'system', 'student', 'academic'])) {
            return true;
        }

        // Feature key aliases for consistency with routes
        $aliases = [
            'finance' => 'accounting',
            'exams' => 'exam',
            'accounting' => 'accounting',
            'exam' => 'exam'
        ];
        
        $searchKey = $aliases[$featureKey] ?? $featureKey;

        // SaaS Improvement: Auto-refresh features every 5 minutes to avoid stale cache
        $lastLoaded = $_SESSION['features_loaded_at'] ?? 0;
        $isStale = (time() - $lastLoaded) > 300; // 5 minute TTL

        if (!isset($_SESSION['enabled_features']) || ($_SESSION['loaded_tenant_id'] ?? null) != $tenantId || $isStale) {
            loadFeatures($tenantId);
        }
        
        $enabled = $_SESSION['enabled_features'] ?? [];
        return in_array($searchKey, $enabled);
    }
}

/**
 * Enforce feature check, kill execution if disabled.
 */
if (!function_exists('enforceFeature')) {
    function enforceFeature($featureKey) {
        if (!hasFeature($featureKey)) {
            $message = "Access Denied: The '{$featureKey}' feature is disabled for your institute.";
            
            // Check if it's an API request
            $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) ||
                     (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
                     (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
            
            if ($isApi) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }

            http_response_code(403);
            die($message);
        }
    }
}


if (!function_exists('hasFeature')) {
    // Already defined above
}

if (!function_exists('enforceFeature')) {
    // Already defined above
}


if (!function_exists('requireAuth')) {
    function requireAuth()
    {
        if (!isLoggedIn()) {
            if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            } else {
                header('Location: ' . APP_URL . '/auth/login');
                exit;
            }
        }

        $user = getCurrentUser();
        if ($user && !empty($user['tenant_id'])) {
            if (!isset($_SESSION['loaded_tenant_id']) || $_SESSION['loaded_tenant_id'] != $user['tenant_id']) {
                loadFeatures($user['tenant_id']);
            }
        }
    }
}

if (!function_exists('requirePermission')) {
    function requirePermission($permission)
    {
        if (!hasPermission($permission)) {
            $isApi = (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
            
            if ($isApi) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access Denied: You do not have permission to access this resource (' . $permission . ').']);
            } else {
                header('Content-Type: text/plain');
                http_response_code(403);
                echo 'Access Denied: You do not have permission to access this resource (' . $permission . ').';
            }
            exit;
        }
    }
}

/**
 * Require a specific module to be enabled for the current institute.
 * @param string $module
 */
if (!function_exists('requireModule')) {
    function requireModule($moduleName)
    {
        if (!hasFeature($moduleName)) {
            $isApi = (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
            
            if ($isApi) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access Denied: This module/feature (' . $moduleName . ') is not enabled for your institute.']);
            } else {
                header('Content-Type: text/plain');
                http_response_code(403);
                echo 'Access Denied: This module/feature (' . $moduleName . ') is not enabled for your institute.';
            }
            exit;
        }
    }
}

/**
 * Combined check for permission AND feature.
 * @param string $permission
 * @param string|null $feature
 */
if (!function_exists('enforceAccess')) {
    function enforceAccess($permission, $feature = null)
    {
        requirePermission($permission);
        if ($feature) {
            enforceFeature($feature);
        }
    }
}


if (!function_exists('showTenantNotFound')) {
    /**
     * Display tenant not found error page
     * @param string|null $subdomain The subdomain that was not found
     * @param string|null $tenantName The tenant name that was not found
     */
    function showTenantNotFound($subdomain = null, $tenantName = null)
    {
        // Extract subdomain from current request if not provided
        if (empty($subdomain)) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $host = explode(':', $host)[0];
            $parts = explode('.', $host);
            if (count($parts) > 2) {
                $subdomain = $parts[0];
            }
        }

        // Include the error view
        $errorFile = VIEWS_PATH . '/errors/tenant-not-found.php';
        if (file_exists($errorFile)) {
            include $errorFile;
        }
        else {
            // Fallback if view file doesn't exist
            http_response_code(404);
            echo '<!DOCTYPE html><html><head><title>404 - Institute Not Found</title></head>';
            echo '<body style="font-family: sans-serif; text-align: center; padding: 50px;">';
            echo '<h1>404 - Institute Not Found</h1>';
            echo '<p>The institute you are looking for could not be found.</p>';
            echo '</body></html>';
        }
        exit;
    }
}

// Set timezone
date_default_timezone_set(TIMEZONE);

if (!function_exists('checkRememberMe')) {
    function checkRememberMe()
    {
        if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $db = getDBConnection();
            try {
                $stmt = $db->prepare("SELECT user_id FROM remember_tokens WHERE token = :token AND expires_at > NOW() LIMIT 1");
                $stmt->execute([':token' => hash('sha256', $token)]);
                $result = $stmt->fetch();

                if ($result) {
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND status = 'active' LIMIT 1");
                    $stmt->execute([':id' => $result['user_id']]);
                    $user = $stmt->fetch();

                    if ($user) {
                        session_regenerate_id(true);

                        $tenantLogo = null;
                        if (!empty($user['tenant_id'])) {
                            $stmtTenant = $db->prepare("SELECT logo_path FROM tenants WHERE id = :tid LIMIT 1");
                            $stmtTenant->execute([':tid' => $user['tenant_id']]);
                            $tenantData = $stmtTenant->fetch();
                            if ($tenantData && !empty($tenantData['logo_path'])) {
                                $tenantLogo = $tenantData['logo_path'];
                                if (strpos($tenantLogo, '/uploads/') === 0 && strpos($tenantLogo, '/public/') !== 0) {
                                    $tenantLogo = '/public' . $tenantLogo;
                                }
                            }
                        }

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

                        $_SESSION['tenant_logo'] = $tenantLogo;
                        $_SESSION['institute_logo'] = $tenantLogo;
                        $_SESSION['last_activity'] = time();

                        // Load features into session
                        if (!empty($user['tenant_id'])) {
                            loadFeatures($user['tenant_id']);
                        }

                        $stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
                        $stmt->execute([':id' => $user['id']]);

                        return true;
                    }
                }
            }
            catch (Exception $e) {
            // Ignore errors
            }

            // Clear invalid token
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
        return false;
    }
}

// Check for remember me cookie
checkRememberMe();

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'logs/error.log');
}

// Start output buffering
if (ob_get_level() == 0)
    ob_start();
