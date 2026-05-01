<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

header('Content-Type: application/json');

try {
    $db = getDBConnection();

    $status = sanitizeInput($_GET['status'] ?? '');

    // Use prepared statement to prevent SQL injection
    $query = "SELECT * FROM tenants WHERE deleted_at IS NULL";
    $params = [];

    if ($status === 'suspended') {
        $query .= " AND status = ?";
        $params[] = $status;
    }
    $query .= " ORDER BY created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $tenants = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $tenants]);

} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
