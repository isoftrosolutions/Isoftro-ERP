<?php
/**
 * Notifications Controller — Real notification counts for dashboard header badge.
 * Queries pending leave requests, new inquiries, and unread system notices.
 */
header('Content-Type: application/json');

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user     = getCurrentUser();
$tenantId = $user['tenant_id'] ?? null;
$role     = $user['role']      ?? '';

if (!$tenantId) {
    echo json_encode(['success' => true, 'notifications' => 0, 'messages' => 0]);
    exit;
}

try {
    $db             = getDBConnection();
    $notifCount     = 0;

    // 1. Pending leave requests (admin / frontdesk see all; teacher sees own)
    try {
        if (in_array($role, ['instituteadmin', 'frontdesk', 'superadmin'])) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM leave_requests WHERE tenant_id = :tid AND status = 'pending'");
            $stmt->execute(['tid' => $tenantId]);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM leave_requests WHERE tenant_id = :tid AND user_id = :uid AND status = 'pending'");
            $stmt->execute(['tid' => $tenantId, 'uid' => $user['id']]);
        }
        $notifCount += (int)$stmt->fetchColumn();
    } catch (Exception $e) {}

    // 2. New student inquiries in the last 7 days (admin / frontdesk only)
    if (in_array($role, ['instituteadmin', 'frontdesk', 'superadmin'])) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM inquiries WHERE tenant_id = :tid AND status = 'new' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stmt->execute(['tid' => $tenantId]);
            $notifCount += (int)$stmt->fetchColumn();
    } catch (Exception $e) {}
    }

    // 3. Active notices (unread proxy — count notices posted in the last 24 hours)
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM notices WHERE tenant_id = :tid AND status = 'active' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute(['tid' => $tenantId]);
        $notifCount += (int)$stmt->fetchColumn();
    } catch (Exception $e) {}

    echo json_encode([
        'success'       => true,
        'notifications' => $notifCount,
        'messages'      => 0   // Direct messaging not yet implemented
    ]);
    } catch (Exception $e) {
    error_log('Notifications count error: ' . $e->getMessage());
    echo json_encode(['success' => true, 'notifications' => 0, 'messages' => 0]);
    }
exit;
