<?php
/**
 * Visitor Log Controller
 * Uses 'inquiries' table with inquiry_type = 'visitor'
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
        $id = $_GET['id'] ?? null;

        if ($id) {
            $stmt = $db->prepare("SELECT * FROM inquiries WHERE id = :id AND tenant_id = :tid AND inquiry_type = 'visitor' AND deleted_at IS NULL");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            $visitor = $stmt->fetch();
            echo json_encode(['success' => true, 'data' => $visitor]);
        } else {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Count total
            $countStmt = $db->prepare("SELECT COUNT(*) FROM inquiries WHERE tenant_id = :tid AND inquiry_type = 'visitor' AND deleted_at IS NULL");
            $countStmt->execute(['tid' => $tenantId]);
            $totalRecords = (int)$countStmt->fetchColumn();

            $stmt = $db->prepare("SELECT * FROM inquiries WHERE tenant_id = :tid AND inquiry_type = 'visitor' AND deleted_at IS NULL ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':tid', $tenantId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $visitors = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'data' => $visitors,
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
        $purpose = $input['notes'] ?? '';

        if (empty($name) || empty($phone)) {
            throw new Exception("Full name and phone are required.");
        }

        $stmt = $db->prepare("
            INSERT INTO inquiries (tenant_id, inquiry_type, full_name, phone, notes, check_in_at, created_at, updated_at)
            VALUES (:tid, 'visitor', :name, :phone, :notes, NOW(), NOW(), NOW())
        ");
        $stmt->execute([
            'tid' => $tenantId,
            'name' => $name,
            'phone' => $phone,
            'notes' => $purpose
        ]);

        echo json_encode(['success' => true, 'message' => 'Visitor checked in successfully', 'id' => $db->lastInsertId()]);
    } 
    elseif ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $id = $input['id'] ?? null;
        $action = $input['action'] ?? '';

        if (!$id) throw new Exception("Visitor ID required.");

        if ($action === 'checkout') {
            $stmt = $db->prepare("UPDATE inquiries SET check_out_at = NOW(), updated_at = NOW() WHERE id = :id AND tenant_id = :tid AND inquiry_type = 'visitor'");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            echo json_encode(['success' => true, 'message' => 'Visitor checked out successfully']);
        } else {
            // General update
            $fields = [];
            $params = ['id' => $id, 'tid' => $tenantId];
            if (isset($input['full_name'])) { $fields[] = "full_name = :name"; $params['name'] = $input['full_name']; }
            if (isset($input['phone'])) { $fields[] = "phone = :phone"; $params['phone'] = $input['phone']; }
            if (isset($input['notes'])) { $fields[] = "notes = :notes"; $params['notes'] = $input['notes']; }
            
            if (empty($fields)) throw new Exception("Nothing to update.");
            
            $stmt = $db->prepare("UPDATE inquiries SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id AND tenant_id = :tid AND inquiry_type = 'visitor'");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'Visitor record updated']);
        }
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");

        $stmt = $db->prepare("UPDATE inquiries SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid AND inquiry_type = 'visitor'");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        echo json_encode(['success' => true, 'message' => 'Visitor record archived']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
