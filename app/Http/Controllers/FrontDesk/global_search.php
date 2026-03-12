<?php
/**
 * Global search for Institute Admin
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
$q = $_GET['q'] ?? '';

if (strlen($q) < 2) {
    echo json_encode(['success' => true, 'students' => [], 'teachers' => [], 'batches' => [], 'courses' => [], 'total' => 0]);
    exit;
}

try {
    $db = getDBConnection();
    $term = '%' . $q . '%';
    $results = [
        'students' => [],
        'teachers' => [],
        'batches' => [],
        'courses' => [],
        'total' => 0
    ];

    // Students
    $stmt = $db->prepare("SELECT s.id, s.roll_no, u.name as name, u.email, u.phone 
                          FROM students s 
                          JOIN users u ON s.user_id = u.id
                          WHERE s.tenant_id = :tid 
                          AND (u.name LIKE :q OR s.roll_no LIKE :q OR u.phone LIKE :q OR u.email LIKE :q) 
                          AND s.deleted_at IS NULL LIMIT 5");
    $stmt->execute(['tid' => $tenantId, 'q' => $term]);
    $results['students'] = $stmt->fetchAll();

    // Teachers
    $stmt = $db->prepare("SELECT id, full_name as name, phone, email FROM teachers WHERE tenant_id = :tid AND (full_name LIKE :q OR phone LIKE :q OR email LIKE :q) AND deleted_at IS NULL LIMIT 5");
    $stmt->execute(['tid' => $tenantId, 'q' => $term]);
    $results['teachers'] = $stmt->fetchAll();

    // Batches
    $stmt = $db->prepare("SELECT b.id, b.name, c.name as course_name FROM batches b LEFT JOIN courses c ON b.course_id = c.id WHERE b.tenant_id = :tid AND b.name LIKE :q AND b.deleted_at IS NULL LIMIT 5");
    $stmt->execute(['tid' => $tenantId, 'q' => $term]);
    $results['batches'] = $stmt->fetchAll();

    // Courses
    $stmt = $db->prepare("SELECT id, name FROM courses WHERE tenant_id = :tid AND (name LIKE :q OR code LIKE :q) AND deleted_at IS NULL LIMIT 5");
    $stmt->execute(['tid' => $tenantId, 'q' => $term]);
    $results['courses'] = $stmt->fetchAll();

    $results['total'] = count($results['students']) + count($results['teachers']) + count($results['batches']) + count($results['courses']);
    $results['success'] = true;

    echo json_encode($results);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
