<?php
/**
 * Communications Controller — SMS Broadcast, Email Campaigns, Templates, Message Log
 * STUB: Returns placeholder data + graceful "coming soon" shape.
 * Full SMS gateway integration in V3.1.
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
    case 'log':
        echo json_encode([
            'success' => true,
            'module'  => 'communications',
            'message' => 'Communications module (SMS/Email broadcast) is coming in V3.1.',
            'data'    => [],
            'meta'    => ['total' => 0, 'page' => 1, 'per_page' => 20]
        ]);
        break;

    case 'send':
        // Stub: acknowledge but do not actually send
        echo json_encode([
            'success' => false,
            'message' => 'SMS broadcast functionality is coming in V3.1. No messages sent.'
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => "Communications action '{$action}' not yet implemented."
        ]);
}
exit;
