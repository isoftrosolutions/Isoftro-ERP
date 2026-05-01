<?php
/**
 * Guardian Exams API
 * Returns detailed exam results and performance analysis for the child
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
    
    // 1. Get guardian info to find student_id
    $stmt = $db->prepare("
        SELECT student_id FROM guardians 
        WHERE user_id = :uid AND tenant_id = :tid 
        LIMIT 1
    ");
    $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
    $guardianInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $studentId = $guardianInfo['student_id'] ?? null;
    
    if (!$studentId && isset($_SESSION['userData']['student_id'])) {
        $studentId = $_SESSION['userData']['student_id'];
    }

    if (!$studentId) {
        echo json_encode(['success' => false, 'message' => 'No student linked to this account.']);
        exit;
    }

    // 2. Fetch Detailed Exam Results
    $stmt = $db->prepare("
        SELECT 
            ea.id as attempt_id,
            ea.score,
            ea.total_marks,
            ea.remarks as attempt_remarks,
            e.title as exam_title,
            e.exam_date,
            e.exam_type,
            e.status as exam_status,
            s.name as subject_name
        FROM exam_attempts ea
        JOIN exams e ON ea.exam_id = e.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        WHERE ea.student_id = :sid AND ea.tenant_id = :tid
        ORDER BY e.exam_date DESC
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Subject-wise Analysis (Averages)
    $stmt = $db->prepare("
        SELECT 
            s.name as subject,
            AVG((ea.score / ea.total_marks) * 100) as avg_percentage
        FROM exam_attempts ea
        JOIN exams e ON ea.exam_id = e.id
        JOIN subjects s ON e.subject_id = s.id
        WHERE ea.student_id = :sid AND ea.tenant_id = :tid
        GROUP BY s.id, s.name
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $analysis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'results' => $results,
            'subject_analysis' => $analysis
        ]
    ]);

} catch (PDOException $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    } catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
