<?php
/**
 * Subjects API Controller
 * Handles CRUD for subjects within a tenant
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

// Ensure user is logged in
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

try {
    $db = getDBConnection();

    if ($method === 'GET') {
        // List subjects
        $query = "SELECT * FROM subjects WHERE tenant_id = :tid AND deleted_at IS NULL";
        $params = ['tid' => $tenantId];

        if (!empty($_GET['id'])) {
            $query .= " AND id = :id";
            $params['id'] = $_GET['id'];
        }

        if (!empty($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            $query .= " AND (name LIKE :search OR code LIKE :search)";
            $params['search'] = $search;
        }

        $query .= " ORDER BY name ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $subjects = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $subjects]);
    }

    else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $name = $input['name'] ?? '';
        $code = $input['code'] ?? '';
        $description = $input['description'] ?? '';

        if (empty($name) || empty($code)) {
            throw new Exception("Subject Name and Code are required");
        }

        $stmt = $db->prepare("
            INSERT INTO subjects (tenant_id, name, code, description, status) 
            VALUES (:tid, :name, :code, :description, 'active')
        ");

        $stmt->execute([
            'tid' => $tenantId,
            'name' => $name,
            'code' => $code,
            'description' => $description
        ]);

        $subjectId = $db->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Subject created successfully', 'id' => $subjectId]);
    }

    else if ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Subject ID is required");

        $fields = [];
        $params = ['id' => $id, 'tid' => $tenantId];
        $allowed = ['name', 'code', 'description', 'status'];

        foreach ($allowed as $f) {
            if (isset($input[$f])) {
                $fields[] = "$f = :$f";
                $params[$f] = $input[$f];
            }
        }

        if (empty($fields)) throw new Exception("No fields to update");

        $stmt = $db->prepare("UPDATE subjects SET " . implode(', ', $fields) . " WHERE id = :id AND tenant_id = :tid");
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Subject updated successfully']);
    }

    else if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_GET;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Subject ID is required");

        // Soft delete
        $stmt = $db->prepare("UPDATE subjects SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);

        echo json_encode(['success' => true, 'message' => 'Subject deleted successfully']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
