<?php
/**
 * Complaints Controller
 * Uses 'inquiries' table with inquiry_type = 'complaint'
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

require_once app_path('Http/Middleware/FrontDeskMiddleware.php');
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];
$role = $auth['role'];

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDBConnection();

    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM inquiries WHERE id = :id AND tenant_id = :tid AND inquiry_type = 'complaint' AND deleted_at IS NULL");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
        } else {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Count total
            $countStmt = $db->prepare("SELECT COUNT(*) FROM inquiries WHERE tenant_id = :tid AND inquiry_type = 'complaint' AND deleted_at IS NULL");
            $countStmt->execute(['tid' => $tenantId]);
            $totalRecords = (int)$countStmt->fetchColumn();

            $stmt = $db->prepare("SELECT * FROM inquiries WHERE tenant_id = :tid AND inquiry_type = 'complaint' AND deleted_at IS NULL ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':tid', $tenantId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $complaints,
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

        if (empty($name) || empty($notes)) {
            throw new Exception("Complainant name and description are required.");
        }

        $stmt = $db->prepare("
            INSERT INTO inquiries (tenant_id, inquiry_type, full_name, phone, notes, status, created_at, updated_at)
            VALUES (:tid, 'complaint', :name, :phone, :notes, 'open', NOW(), NOW())
        ");
        $stmt->execute([
            'tid' => $tenantId,
            'name' => $name,
            'phone' => $phone,
            'notes' => $notes
        ]);

        echo json_encode(['success' => true, 'message' => 'Complaint registered successfully', 'id' => $db->lastInsertId()]);
    } 
    elseif ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $id = $input['id'] ?? null;
        $status = $input['status'] ?? '';

        if (!$id || !$status) throw new Exception("ID and status required.");

        $stmt = $db->prepare("UPDATE inquiries SET status = :status, updated_at = NOW() WHERE id = :id AND tenant_id = :tid AND inquiry_type = 'complaint'");
        $stmt->execute(['status' => $status, 'id' => $id, 'tid' => $tenantId]);
        
        echo json_encode(['success' => true, 'message' => 'Complaint status updated to ' . $status]);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");

        $stmt = $db->prepare("UPDATE inquiries SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid AND inquiry_type = 'complaint'");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        echo json_encode(['success' => true, 'message' => 'Complaint record archived']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
