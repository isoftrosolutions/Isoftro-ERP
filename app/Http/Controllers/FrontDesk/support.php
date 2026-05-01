<?php
/**
 * Front Desk Support Tickets API
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
            SELECT st.*, u.name as created_by
            FROM support_tickets st
            LEFT JOIN users u ON st.user_id = u.id
            WHERE st.tenant_id = :tid
            ORDER BY st.created_at DESC
        ");
        $stmt->execute(['tid' => $tenantId]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $tickets]);
    } else if ($action === 'view') {
        $id = intval($_GET['id'] ?? 0);
        $stmt = $db->prepare("
            SELECT st.*, u.name as created_by
            FROM support_tickets st
            LEFT JOIN users u ON st.user_id = u.id
            WHERE st.id = :id AND st.tenant_id = :tid
        ");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ticket) throw new Exception("Ticket not found");
        echo json_encode(['success' => true, 'data' => $ticket]);
    } else if ($action === 'create') {
        // Support both JSON and FormData
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }
        if (!$input) throw new Exception("Invalid input");

        $subject = sanitizeInput($input['subject'] ?? '');
        $description = sanitizeInput($input['description'] ?? '');
        $priority = $input['priority'] ?? 'normal';

        if (empty($subject)) throw new Exception("Subject is required");

        $stmt = $db->prepare("
            INSERT INTO support_tickets (tenant_id, user_id, subject, description, priority, status)
            VALUES (:tid, :uid, :subject, :desc, :priority, 'open')
        ");
        $stmt->execute([
            'tid' => $tenantId,
            'uid' => $auth['id'] ?? null,
            'subject' => $subject,
            'desc' => $description,
            'priority' => $priority
        ]);

        echo json_encode(['success' => true, 'message' => 'Ticket created successfully']);
    }
} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
