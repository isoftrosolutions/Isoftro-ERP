<?php
/**
 * debug_api.php - Diagnostic script for ERP API Endpoints
 * Use this to verify Course/Batch data structure.
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json');

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'app_url' => APP_URL,
    'endpoints' => []
];

$endpoints = [
    'courses' => '/api/admin/courses',
    'batches' => '/api/admin/batches'
];

foreach ($endpoints as $key => $uri) {
    try {
        // We use a internal curl or just check the DB directly to be sure
        // But let's try to simulate a local fetch if possible, or just check DB.
        
        $db = \App\Core\Database::getInstance()->getConnection();
        
        if ($key === 'courses') {
            $stmt = $db->query("SELECT * FROM courses LIMIT 1");
            $sample = $stmt->fetch(PDO::FETCH_ASSOC);
            $results['endpoints']['courses'] = [
                'table' => 'courses',
                'exists' => true,
                'sample_data' => $sample,
                'status' => $sample ? 'OK' : 'Empty Table'
            ];
        } else {
            $stmt = $db->query("SELECT * FROM batches LIMIT 1");
            $sample = $stmt->fetch(PDO::FETCH_ASSOC);
            $results['endpoints']['batches'] = [
                'table' => 'batches',
                'exists' => true,
                'sample_data' => $sample,
                'status' => $sample ? 'OK' : 'Empty Table'
            ];
        }
        
    } catch (\Exception $e) {
        $results['endpoints'][$key] = [
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ];
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
