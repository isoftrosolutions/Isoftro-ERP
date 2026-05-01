<?php
/**
 * Front Desk Announcements API
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');
require_once __DIR__ . '/../../Middleware/FrontDeskMiddleware.php';
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];

try {
    $db = getDBConnection();
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $stmt = $db->prepare("
            SELECT id, title, content as `desc`, DATE_FORMAT(created_at, '%b %d, %Y') as date, 
                   notice_type as category, priority, status, created_at
            FROM notices
            WHERE tenant_id = :tid
            ORDER BY created_at DESC
        ");
        $stmt->execute(['tid' => $tenantId]);
        $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Map category colors and icons
        foreach ($notices as &$n) {
            $cat = strtolower($n['category']);
            if ($cat === 'urgent' || $n['priority'] === 'high') {
                $n['color'] = '#EF4444'; $n['bg'] = '#FEF2F2'; $n['icon'] = 'fa-circle-exclamation';
            } else if ($cat === 'holiday') {
                $n['color'] = '#3B82F6'; $n['bg'] = '#EFF6FF'; $n['icon'] = 'fa-calendar-day';
            } else if ($cat === 'exam') {
                $n['color'] = '#F59E0B'; $n['bg'] = '#FFFBEB'; $n['icon'] = 'fa-file-invoice';
            } else {
                $n['color'] = '#10B981'; $n['bg'] = '#F0FDF4'; $n['icon'] = 'fa-bullhorn';
            }
        }

        echo json_encode(['success' => true, 'data' => $notices]);
    }
} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
