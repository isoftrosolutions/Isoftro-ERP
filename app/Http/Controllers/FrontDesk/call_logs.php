<?php
/**
 * Call Logs Controller
 * Uses 'inquiries' table with inquiry_type = 'call_log'
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$tenantId = $user['tenant_id'] ?? null;
$role = $user['role'] ?? '';

require_once __DIR__ . '/../../Middleware/FrontDeskMiddleware.php';
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];
$role = $auth['role'];

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDBConnection();

    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM inquiries WHERE id = :id AND tenant_id = :tid AND inquiry_type = 'call_log' AND deleted_at IS NULL");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
        } else {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Count total
            $countStmt = $db->prepare("SELECT COUNT(*) FROM inquiries WHERE tenant_id = :tid AND inquiry_type = 'call_log' AND deleted_at IS NULL");
            $countStmt->execute(['tid' => $tenantId]);
            $totalRecords = (int)$countStmt->fetchColumn();

            $stmt = $db->prepare("SELECT * FROM inquiries WHERE tenant_id = :tid AND inquiry_type = 'call_log' AND deleted_at IS NULL ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':tid', $tenantId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $logs,
                'total' => $totalRecords,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalRecords / $limit)
            ]);
        }
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $name = $input['full_name'] ?? '';
        $phone = $input['phone'] ?? '';
        $notes = $input['notes'] ?? '';
        $call_type = $input['call_type'] ?? 'incoming'; // incoming/outgoing

        if (empty($name) || empty($phone)) {
            throw new Exception("Name and phone are required.");
        }

        // We use 'source' column to store call type for now, or just notes
        $stmt = $db->prepare("
            INSERT INTO inquiries (tenant_id, inquiry_type, full_name, phone, notes, source, created_at, updated_at)
            VALUES (:tid, 'call_log', :name, :phone, :notes, :call_type, NOW(), NOW())
        ");
        $stmt->execute([
            'tid' => $tenantId,
            'name' => $name,
            'phone' => $phone,
            'notes' => $notes,
            'call_type' => $call_type
        ]);

        echo json_encode(['success' => true, 'message' => 'Call logged successfully', 'id' => $db->lastInsertId()]);
    } 
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");

        $stmt = $db->prepare("UPDATE inquiries SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid AND inquiry_type = 'call_log'");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        echo json_encode(['success' => true, 'message' => 'Call log archived']);
    }

} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
