<?php
/**
 * Super Admin Support API
 * Returns JSON data for support tickets management
 */

if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Auth check
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
if (!$user || ($user['role'] ?? '') !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// CSRF check for POST/PUT/DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    try {
        \App\Helpers\CsrfHelper::requireCsrfToken();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'CSRF token mismatch.']);
        exit;
    }
}

$action = $_GET['action'] ?? 'list';

try {
    $db = getDBConnection();
    
    switch ($action) {
        case 'list':
            $status = $_GET['status'] ?? 'open';
            $priority = $_GET['priority'] ?? null;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;
            
            $where = [];
            $params = [];
            
            if ($status !== 'all') {
                $where[] = 'st.status = :status';
                $params['status'] = $status;
            }
            
            if ($priority) {
                $where[] = 'st.priority = :priority';
                $params['priority'] = $priority;
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Get total count
            $countStmt = $db->prepare("SELECT COUNT(*) FROM support_tickets st $whereClause");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get tickets
            $stmt = $db->prepare("
                SELECT st.*, t.name as tenant_name, t.subdomain, u.name as user_name, u.email as user_email
                FROM support_tickets st
                LEFT JOIN tenants t ON st.tenant_id = t.id
                LEFT JOIN users u ON st.user_id = u.id
                $whereClause
                ORDER BY 
                    CASE st.priority 
                        WHEN 'critical' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'normal' THEN 3 
                        ELSE 4 
                    END,
                    st.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $tickets = $stmt->fetchAll();
            
            // Get counts by status
            $statusCounts = [];
            $statusStmt = $db->query("SELECT status, COUNT(*) as count FROM support_tickets GROUP BY status");
            while ($row = $statusStmt->fetch()) {
                $statusCounts[$row['status']] = (int)$row['count'];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $tickets,
                'status_counts' => $statusCounts,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'get':
            $id = (int)$_GET['id'];
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Ticket ID required']);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT st.*, t.name as tenant_name, t.subdomain, u.name as user_name, u.email as user_email
                FROM support_tickets st
                LEFT JOIN tenants t ON st.tenant_id = t.id
                LEFT JOIN users u ON st.user_id = u.id
                WHERE st.id = :id
            ");
            $stmt->execute(['id' => $id]);
            $ticket = $stmt->fetch();
            
            if (!$ticket) {
                echo json_encode(['success' => false, 'message' => 'Ticket not found']);
                exit;
            }
            
            // Get replies
            $repliesStmt = $db->prepare("
                SELECT sr.*, u.name as user_name, u.role
                FROM support_replies sr
                LEFT JOIN users u ON sr.user_id = u.id
                WHERE sr.ticket_id = :id
                ORDER BY sr.created_at ASC
            ");
            $repliesStmt->execute(['id' => $id]);
            $ticket['replies'] = $repliesStmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $ticket]);
            break;
            
        case 'update':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)$input['id'];
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Ticket ID required']);
                exit;
            }
            
            $fields = [];
            $params = ['id' => $id];
            
            $allowedFields = ['status', 'priority', 'assigned_to', 'resolution_notes'];
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = :$field";
                    $params[$field] = $input[$field];
                }
            }
            
            if (empty($fields)) {
                echo json_encode(['success' => false, 'message' => 'No fields to update']);
                exit;
            }
            
            // Add resolved_at if status is being set to resolved
            if (isset($input['status']) && $input['status'] === 'resolved') {
                $fields[] = 'resolved_at = NOW()';
            }
            
            $stmt = $db->prepare("UPDATE support_tickets SET " . implode(', ', $fields) . " WHERE id = :id");
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Ticket updated successfully']);
            break;
            
        case 'reply':
            $input = json_decode(file_get_contents('php://input'), true);
            $ticketId = (int)$input['ticket_id'];
            $message = $input['message'] ?? '';
            
            if (!$ticketId || !$message) {
                echo json_encode(['success' => false, 'message' => 'Ticket ID and message are required']);
                exit;
            }
            
            $stmt = $db->prepare("
                INSERT INTO support_replies (ticket_id, user_id, message, created_at)
                VALUES (:ticket_id, :user_id, :message, NOW())
            ");
            
            $stmt->execute([
                'ticket_id' => $ticketId,
                'user_id' => $user['id'],
                'message' => $message
            ]);
            
            // Update ticket status to pending if it was resolved
            $updateStmt = $db->prepare("UPDATE support_tickets SET status = 'in_progress', updated_at = NOW() WHERE id = ? AND status = 'resolved'");
            $updateStmt->execute([$ticketId]);
            
            echo json_encode(['success' => true, 'message' => 'Reply added successfully']);
            break;
            
        case 'stats':
            $stats = [
                'open' => 0,
                'in_progress' => 0,
                'resolved' => 0,
                'closed' => 0,
                'total' => 0,
                'critical' => 0,
                'high' => 0,
                'normal' => 0,
                'low' => 0
            ];
            
            $stmt = $db->query("SELECT status, priority, COUNT(*) as count FROM support_tickets GROUP BY status, priority");
            while ($row = $stmt->fetch()) {
                if (isset($stats[$row['status']])) {
                    $stats[$row['status']] = (int)$row['count'];
                }
                if (isset($stats[$row['priority']])) {
                    $stats[$row['priority']] = (int)$row['count'];
                }
                $stats['total'] += (int)$row['count'];
            }
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'feedbacks':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;
            
            // Get total
            $total = $db->query("SELECT COUNT(*) FROM feedbacks")->fetchColumn();
            
            // Get feedbacks
            $stmt = $db->prepare("
                SELECT f.*, t.name as tenant_name, u.name as user_name
                FROM feedbacks f
                LEFT JOIN tenants t ON f.tenant_id = t.id
                LEFT JOIN users u ON f.user_id = u.id
                ORDER BY f.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $feedbacks = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $feedbacks,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("[DB-ERROR] SupportApi error: " . $e->getMessage());
    $msg = (defined('APP_ENV') && APP_ENV === 'development') ? $e->getMessage() : 'An internal error occurred. Please try again.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
