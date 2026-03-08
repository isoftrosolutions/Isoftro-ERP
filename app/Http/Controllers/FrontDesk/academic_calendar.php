<?php
/**
 * Academic Calendar API Controller
 * Handles fetching, saving, deleting events
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

// Always return JSON — prevent HTML error pages breaking the JS client
header('Content-Type: application/json');

// Global error safety net: catches fatal errors & converts to JSON
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $err['message']]);
    }
});


if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$db = getDBConnection();
$uri = $_SERVER['REQUEST_URI'] ?? '';

// Determine action based on URI or Method
$action = 'list';
if (strpos($uri, '/save') !== false) {
    $action = 'save';
} else if (strpos($uri, '/delete') !== false) {
    $action = 'delete';
}

try {
    if ($action === 'list' && $method === 'GET') {
        $stmt = $db->prepare("SELECT id, title, type, start_date as start, end_date as end, batch, description as `desc` 
                              FROM academic_calendar 
                              WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY start_date ASC");
        $stmt->execute(['tid' => $tenantId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $events]);
    }
    else if ($action === 'save' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $title = trim($input['title'] ?? '');
        $type = $input['type'] ?? 'notice';
        $start = $input['start'] ?? '';
        $end = $input['end'] ?? $start;
        $batch = $input['batch'] ?? 'All';
        $desc = trim($input['description'] ?? '');

        if (!$title || !$start) {
            throw new Exception("Title and start date are required");
        }

        $stmt = $db->prepare("
            INSERT INTO academic_calendar (tenant_id, title, type, start_date, end_date, batch, description)
            VALUES (:tid, :title, :type, :start, :end, :batch, :desc)
        ");
        
        $stmt->execute([
            'tid' => $tenantId,
            'title' => $title,
            'type' => $type,
            'start' => $start,
            'end' => $end,
            'batch' => $batch,
            'desc' => $desc
        ]);

        echo json_encode(['success' => true, 'message' => 'Event saved']);
    }
    else if ($action === 'delete' && $method === 'POST') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
        }

        if (!$id) throw new Exception("ID required");

        // Hard delete or soft delete, let's soft delete
        $stmt = $db->prepare("UPDATE academic_calendar SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);

        echo json_encode(['success' => true, 'message' => 'Event deleted']);
    }
    else {
        throw new Exception("Invalid request");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
