<?php
/**
 * API: Get Plan Features
 */
require_once __DIR__ . '/../../../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || getCurrentUser()['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$plan_id = $_GET['plan_id'] ?? null;

if (!$plan_id) {
    echo json_encode(['success' => false, 'message' => 'Plan ID required']);
    exit;
}

try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM plan_features WHERE plan_id = :pid ORDER BY sort_order ASC");
    $stmt->execute(['pid' => $plan_id]);
    $features = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'features' => $features]);
} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
