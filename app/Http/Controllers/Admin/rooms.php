<?php
/**
 * Rooms API Controller
 * Handles CRUD for classrooms/rooms within a tenant
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
        // List rooms
        $query = "SELECT * FROM rooms WHERE tenant_id = :tid AND deleted_at IS NULL";
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
        $rooms = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $rooms]);
    }

    else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $name = $input['name'] ?? '';
        $code = $input['code'] ?? '';
        $capacity = $input['capacity'] ?? null;
        $description = $input['description'] ?? '';

        if (empty($name)) {
            throw new Exception("Room Name is required");
        }

        $stmt = $db->prepare("
            INSERT INTO rooms (tenant_id, name, code, capacity, description, is_active, created_at, updated_at) 
            VALUES (:tid, :name, :code, :capacity, :description, 1, NOW(), NOW())
        ");

        $stmt->execute([
            'tid' => $tenantId,
            'name' => $name,
            'code' => $code,
            'capacity' => $capacity,
            'description' => $description
        ]);

        $roomId = $db->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Room created successfully', 'id' => $roomId]);
    }

    else if ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Room ID is required");

        $fields = [];
        $params = ['id' => $id, 'tid' => $tenantId];
        $allowed = ['name', 'code', 'capacity', 'description', 'is_active'];

        foreach ($allowed as $f) {
            if (isset($input[$f])) {
                $fields[] = "$f = :$f";
                $params[$f] = $input[$f];
            }
        }

        if (empty($fields)) throw new Exception("No fields to update");

        $stmt = $db->prepare("UPDATE rooms SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id AND tenant_id = :tid");
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
    }

    else if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_GET;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Room ID is required");

        // Soft delete
        $stmt = $db->prepare("UPDATE rooms SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);

        echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
    }
} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
