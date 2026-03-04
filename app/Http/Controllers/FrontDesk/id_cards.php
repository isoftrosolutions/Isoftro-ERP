<?php
/**
 * ID Cards Controller
 * Manages student ID card statuses in the 'students' table
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$tenantId = $user['tenant_id'] ?? null;
$role = $user['role'] ?? '';

require_once app_path('Http/Middleware/FrontDeskMiddleware.php');
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];
$role = $auth['role'];

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDBConnection();

    if ($method === 'GET') {
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? null;

        $query = "SELECT id, full_name, roll_no, id_card_status, id_card_issued_at FROM students WHERE tenant_id = :tid";
        $params = ['tid' => $tenantId];

        if ($status) {
            $query .= " AND id_card_status = :status";
            $params['status'] = $status;
        }
        if ($search) {
            $query .= " AND (full_name LIKE :search OR roll_no LIKE :search)";
            $params['search'] = "%$search%";
        }

        $query .= " ORDER BY id_card_status DESC, updated_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } 
    elseif ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $id = $input['id'] ?? null;
        $action = $input['action'] ?? '';

        if (!$id) throw new Exception("Student ID required.");

        if ($action === 'request') {
            $stmt = $db->prepare("UPDATE students SET id_card_status = 'requested', updated_at = NOW() WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            echo json_encode(['success' => true, 'message' => 'ID card requested']);
        } 
        elseif ($action === 'processing') {
            $stmt = $db->prepare("UPDATE students SET id_card_status = 'processing', updated_at = NOW() WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            echo json_encode(['success' => true, 'message' => 'ID card set to processing']);
        }
        elseif ($action === 'issue') {
            $stmt = $db->prepare("UPDATE students SET id_card_status = 'issued', id_card_issued_at = NOW(), updated_at = NOW() WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            echo json_encode(['success' => true, 'message' => 'ID card marked as issued']);
        }
        else {
            throw new Exception("Invalid action.");
        }
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
