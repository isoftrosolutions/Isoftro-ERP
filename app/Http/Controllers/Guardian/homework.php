<?php
/**
 * Guardian Homework API
 * Returns list of homework assignments and submission status for the child
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

$user = getCurrentUser();
$role = $user['role'] ?? '';
$tenantId = $user['tenant_id'] ?? null;
$userId = $user['id'] ?? null;

// Permission check
if ($role !== 'guardian' && $role !== 'superadmin' && $role !== 'instituteadmin') {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

try {
    $db = getDBConnection();
    
    // 1. Get guardian info to find student_id and batch_id
    $stmt = $db->prepare("
        SELECT g.student_id, e.batch_id, b.course_id
        FROM guardians g
        JOIN students s ON g.student_id = s.id
        LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
        LEFT JOIN batches b ON e.batch_id = b.id
        WHERE g.user_id = :uid AND g.tenant_id = :tid 
        LIMIT 1
    ");
    $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
    $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $studentId = $studentInfo['student_id'] ?? null;
    $batchId = $studentInfo['batch_id'] ?? null;
    $courseId = $studentInfo['course_id'] ?? null;
    
    if (!$studentId && isset($_SESSION['userData']['student_id'])) {
        $studentId = $_SESSION['userData']['student_id'];
        // Re-fetch batch info if session used
        $stmt = $db->prepare("
            SELECT e.batch_id, b.course_id 
            FROM students s
            LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
            LEFT JOIN batches b ON e.batch_id = b.id
            WHERE s.id = :sid LIMIT 1
        ");
        $stmt->execute(['sid' => $studentId]);
        $si = $stmt->fetch(PDO::FETCH_ASSOC);
        $batchId = $si['batch_id'] ?? null;
        $courseId = $si['course_id'] ?? null;
    }

    if (!$studentId) {
        echo json_encode(['success' => false, 'message' => 'No student linked to this account.']);
        exit;
    }

    // 2. Fetch Homework for the child's batch
    $stmt = $db->prepare("
        SELECT 
            h.id as homework_id,
            h.title,
            h.description,
            h.due_date,
            s.name as subject_name,
            hs.status as submission_status,
            hs.marks_obtained,
            h.total_marks
        FROM homework h
        LEFT JOIN subjects s ON h.subject_id = s.id
        LEFT JOIN homework_submissions hs ON h.id = hs.homework_id AND hs.student_id = :sid
        WHERE h.batch_id = :bid AND h.tenant_id = :tid AND h.status = 'published'
        ORDER BY h.due_date DESC
    ");
    $stmt->execute(['sid' => $studentId, 'bid' => $batchId, 'tid' => $tenantId]);
    $homeworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $homeworks
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
