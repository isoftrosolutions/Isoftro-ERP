<?php
/**
 * Notifications Controller — Count and List Notifications/Messages
 * Placeholder implementation for dashboard UI.
 */
header('Content-Type: application/json');

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Return dummy counts for the dashboard header
echo json_encode([
    'success' => true,
    'notifications' => 3,
    'messages' => 0
]);
exit;
