<?php
/**
 * Subject Allocation API Controller
 * Handles allocating teachers to subjects within batches
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
        // List allocations, usually filtered by batch_id
        $batchId = $_GET['batch_id'] ?? null;
        
        $query = "SELECT bsa.*, s.name as subject_name, s.code as subject_code, 
                  t.full_name as teacher_name
                  FROM batch_subject_allocations bsa
                  JOIN subjects s ON bsa.subject_id = s.id AND s.deleted_at IS NULL
                  LEFT JOIN teachers t ON bsa.teacher_id = t.id AND t.deleted_at IS NULL
                  WHERE bsa.tenant_id = :tid";
        
        $params = ['tid' => $tenantId];

        if ($batchId) {
            $query .= " AND bsa.batch_id = :bid";
            $params['bid'] = $batchId;
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $allocations = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $allocations]);
    }

    else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $batchId = $input['batch_id'] ?? null;
        $teacherId = $input['teacher_id'] ?? null;
        $subjectId = $input['subject_id'] ?? null;

        if (!$batchId || !$teacherId || !$subjectId) {
            throw new Exception("Batch, Teacher, and Subject are required");
        }

        // Check if allocation already exists
        $stmt = $db->prepare("SELECT id FROM batch_subject_allocations WHERE batch_id = :bid AND subject_id = :sid AND tenant_id = :tid");
        $stmt->execute(['bid' => $batchId, 'sid' => $subjectId, 'tid' => $tenantId]);
        if ($stmt->fetch()) {
            throw new Exception("This subject is already allocated in this batch. Please remove existing allocation first.");
        }

        $stmt = $db->prepare("
            INSERT INTO batch_subject_allocations (tenant_id, batch_id, teacher_id, subject_id) 
            VALUES (:tid, :bid, :teid, :sid)
        ");

        $stmt->execute([
            'tid' => $tenantId,
            'bid' => $batchId,
            'teid' => $teacherId,
            'sid' => $subjectId
        ]);

        echo json_encode(['success' => true, 'message' => 'Subject allocated successfully', 'id' => $db->lastInsertId()]);
    }

    else if ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Allocation ID is required");
        
        $teacherId = $input['teacher_id'] ?? null;
        if (!$teacherId) throw new Exception("Teacher ID is required");

        $stmt = $db->prepare("UPDATE batch_subject_allocations SET teacher_id = :teid WHERE id = :id AND tenant_id = :tid");
        $stmt->execute([
            'teid' => $teacherId,
            'id' => $id,
            'tid' => $tenantId
        ]);

        echo json_encode(['success' => true, 'message' => 'Allocation updated successfully']);
    }

    else if ($method === 'DELETE') {
        // Usually delete by ID
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
        }

        if (!$id) throw new Exception("Allocation ID is required");

        $stmt = $db->prepare("DELETE FROM batch_subject_allocations WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);

        echo json_encode(['success' => true, 'message' => 'Allocation removed successfully']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
