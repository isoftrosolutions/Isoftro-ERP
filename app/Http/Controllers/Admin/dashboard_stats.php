<?php
/**
 * Institute Admin Dashboard Stats API V2
 * Optimized for performance and comprehensive V2 requirements
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

// Boot Laravel to enable auth() helper and Eloquent
if (!function_exists('app')) {
    require_once __DIR__ . '/../../../../vendor/autoload.php';
    $app = require_once __DIR__ . '/../../../../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
}

// Ensure no previous output (like PHP warnings) ruins the JSON
if (ob_get_length()) ob_clean();

// Auth Check
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
if (!in_array($user['role'], ['instituteadmin', 'superadmin', 'frontdesk'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$tenantId = $user['tenant_id'] ?? null;

if ($user['role'] === 'superadmin' && isset($_GET['tenant_id'])) {
    $tenantId = $_GET['tenant_id'];
}

try {
    $dashboardService = app(\App\Services\DashboardCacheService::class);
    
    if (isset($_GET['nocache'])) {
        $dashboardService->invalidate($tenantId);
    }
    
    $stats = $dashboardService->getStats($tenantId);
    
    echo json_encode(['success' => true, 'data' => $stats]);

} catch (Exception $e) {
    error_log('Dashboard Stats Error V2: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'Backend Error: ' . $e->getMessage()]);
}
