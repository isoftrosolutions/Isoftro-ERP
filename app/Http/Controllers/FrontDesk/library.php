<?php
/**
 * Library Controller — Catalog, Issue/Return, Overdue Tracking, Stock Report
 * STUB: Returns placeholder data + graceful "coming soon" shape.
 * Full implementation in V3.1.
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['userData']['role'] ?? '';
if (!in_array($role, ['instituteadmin', 'frontdesk', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {
    case 'list':
        echo json_encode([
            'success' => true,
            'module'  => 'library',
            'message' => 'Library module is coming in V3.1.',
            'data'    => [],
            'meta'    => ['total' => 0, 'page' => 1, 'per_page' => 20]
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => "Library action '{$action}' not yet implemented."
        ]);
}
exit;
