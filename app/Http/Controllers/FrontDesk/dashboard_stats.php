<?php
/**
 * Institute Admin Dashboard Stats API V2
 * Optimized for performance and comprehensive V2 requirements
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

// Ensure no previous output (like PHP warnings) ruins the JSON
if (ob_get_length()) ob_clean();

// Auth Check
if (!isLoggedIn() || !in_array($_SESSION['userData']['role'], ['instituteadmin', 'superadmin', 'frontdesk'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;

if ($_SESSION['userData']['role'] === 'superadmin' && isset($_GET['tenant_id'])) {
    $tenantId = $_GET['tenant_id'];
}

try {
    // Check if we need to load Laravel framework for the app() helper
    if (!function_exists('app')) {
        require_once __DIR__ . '/../../../../vendor/autoload.php';
        $app = require_once __DIR__ . '/../../../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    }
    
    $dashboardService = app(\App\Services\DashboardCacheService::class);
    
    if (isset($_GET['nocache'])) {
        $dashboardService->invalidate($tenantId);
    }
    
    $stats = $dashboardService->getStats($tenantId);
    
    echo json_encode(['success' => true, 'data' => $stats]);

} catch (Exception $e) {
    error_log('Dashboard Stats Error V2: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
