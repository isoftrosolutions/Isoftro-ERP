<?php
if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

try {
    $stats = \App\Helpers\StatsHelper::getSuperAdminStats();
    
    if ($stats === null) {
        throw new Exception("Could not fetch platform stats.");
    }

    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
