<?php
/**
 * Admin Homework API
 * Route: /api/admin/homework
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('exams.view')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenant_id = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenant_id) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

try {
    $db = getDBConnection();
    
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
    $batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
    $status = isset($_GET['status']) ? $_GET['status'] : '';

    $where = ["h.tenant_id = :tenant_id"];
    $params = [':tenant_id' => $tenant_id];

    if ($course_id > 0) {
        $where[] = "h.course_id = :course_id";
        $params[':course_id'] = $course_id;
    }
    
    if ($batch_id > 0) {
        $where[] = "h.batch_id = :batch_id";
        $params[':batch_id'] = $batch_id;
    }

    if (!empty($status)) {
        $where[] = "h.status = :status";
        $params[':status'] = $status;
    }

    $whereClause = implode(" AND ", $where);

    $sql = "
        SELECT 
            h.id, h.title, h.due_date, h.total_marks, h.status,
            c.name as course_name, 
            b.name as batch_name, 
            s.name as subject_name
        FROM homework h
        LEFT JOIN courses c ON h.course_id = c.id
        LEFT JOIN batches b ON h.batch_id = b.id
        LEFT JOIN subjects s ON h.subject_id = s.id
        WHERE {$whereClause}
        ORDER BY h.created_at DESC
        LIMIT 100
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $homework = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true, 
        'homework' => $homework
    ]);

} catch (PDOException $e) {
    error_log("Homework Load Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
    }
