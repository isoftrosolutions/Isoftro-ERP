<?php
/**
 * Student Portal — Contact / Support Tickets Controller
 * Allows students to submit and track support tickets to admin
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user     = getCurrentUser();
$tenantId = $user['tenant_id'] ?? null;
$userId   = $user['id'] ?? null;

if (!$tenantId || !$userId) {
    echo json_encode(['success' => false, 'message' => 'Session error']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($method === 'POST' ? 'submit' : 'list');

try {
    $db = getDBConnection();

    switch ($action) {

        case 'list':
            // Get all tickets submitted by this user
            $stmt = $db->prepare("
                SELECT id, subject, priority, status, created_at, updated_at
                FROM support_tickets
                WHERE user_id = :uid AND tenant_id = :tid
                ORDER BY created_at DESC
                LIMIT 50
            ");
            $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $tickets]);
            break;

        case 'view':
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception('Ticket ID required');

            $stmt = $db->prepare("
                SELECT * FROM support_tickets
                WHERE id = :id AND user_id = :uid AND tenant_id = :tid
                LIMIT 1
            ");
            $stmt->execute(['id' => $id, 'uid' => $userId, 'tid' => $tenantId]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                echo json_encode(['success' => false, 'message' => 'Ticket not found']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $ticket]);
            break;

        case 'submit':
            if ($method !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;

            $subject     = trim($input['subject'] ?? '');
            $description = trim($input['description'] ?? '');
            $priority    = $input['priority'] ?? 'normal';

            if (empty($subject) || empty($description)) {
                echo json_encode(['success' => false, 'message' => 'Subject and description are required']);
                exit;
            }
            if (!in_array($priority, ['low', 'normal', 'high', 'critical'])) {
                $priority = 'normal';
            }
            if (strlen($subject) > 255) {
                echo json_encode(['success' => false, 'message' => 'Subject must be 255 characters or less']);
                exit;
            }

            $stmt = $db->prepare("
                INSERT INTO support_tickets (tenant_id, user_id, subject, description, priority, status, created_at, updated_at)
                VALUES (:tid, :uid, :subject, :desc, :priority, 'open', NOW(), NOW())
            ");
            $stmt->execute([
                'tid'      => $tenantId,
                'uid'      => $userId,
                'subject'  => $subject,
                'desc'     => $description,
                'priority' => $priority,
            ]);

            $ticketId = $db->lastInsertId();
            echo json_encode([
                'success' => true,
                'message' => 'Your message has been sent! We will get back to you soon.',
                'data'    => ['id' => $ticketId]
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    error_log('Student contact error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
