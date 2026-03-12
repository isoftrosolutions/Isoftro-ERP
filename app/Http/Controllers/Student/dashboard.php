<?php
/**
 * Student Dashboard API
 * Returns aggregated data for student dashboard overview
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

// Get student_id from session or user data
$studentId = $_SESSION['userData']['student_id'] ?? null;

// If role is student but no student_id, try to fetch it
if ($role === 'student' && !$studentId && $userId) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id FROM students WHERE user_id = :uid AND tenant_id = :tid LIMIT 1");
        $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $studentId = $result['id'];
            $_SESSION['userData']['student_id'] = $studentId;
        }
    } catch (Exception $e) {
        error_log("Failed to fetch student_id: " . $e->getMessage());
    }
}

if (!$tenantId || !$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student record not found']);
    exit;
}

try {
    $db = getDBConnection();
    $dashboard = [
        'student_info' => null,
        'today_classes' => [],
        'attendance_summary' => [],
        'pending_assignments' => 0,
        'fee_summary' => [],
        'recent_notices' => [],
        'upcoming_exams' => [],
        'study_materials_count' => 0
    ];
    
    // 1. Get student basic info with batch/course details
    $stmt = $db->prepare("
        SELECT s.*, b.name as batch_name, b.id as batch_id, 
               c.name as course_name, c.id as course_id,
               t.name as institute_name, t.logo_path as institute_logo
        FROM students s
        LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
        LEFT JOIN batches b ON e.batch_id = b.id
        LEFT JOIN courses c ON b.course_id = c.id
        LEFT JOIN tenants t ON s.tenant_id = t.id
        WHERE s.id = :sid AND s.tenant_id = :tid
        LIMIT 1
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $dashboard['student_info'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $batchId = $dashboard['student_info']['batch_id'] ?? null;
    
    // 2. Fetch today's classes from timetable
    if ($batchId) {
        // Map day name to numeric day (1=Sunday, 2=Monday, ..., 7=Saturday)
        $dayOfWeek = date('w') + 1; 
        
        $stmt = $db->prepare("
            SELECT t.*, s.name as subject_name, s.code as subject_code,
                   tea.full_name as teacher_name
            FROM timetable_slots t
            LEFT JOIN subjects s ON t.subject_id = s.id
            LEFT JOIN teachers tea ON t.teacher_id = tea.id
            WHERE t.batch_id = :bid 
              AND t.day_of_week = :day
              AND t.tenant_id = :tid
            ORDER BY t.start_time ASC
        ");
        $stmt->execute(['bid' => $batchId, 'day' => $dayOfWeek, 'tid' => $tenantId]);
        $dashboard['today_classes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 3. Calculate attendance summary
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
        FROM attendance 
        WHERE student_id = :sid AND tenant_id = :tid
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $attendanceData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalDays = (int)($attendanceData['total_days'] ?? 0);
    $presentDays = (int)($attendanceData['present_days'] ?? 0);
    $lateDays = (int)($attendanceData['late_days'] ?? 0);
    
    $dashboard['attendance_summary'] = [
        'total_days' => $totalDays,
        'present_days' => $presentDays,
        'late_days' => $lateDays,
        'absent_days' => (int)($attendanceData['absent_days'] ?? 0),
        'percentage' => $totalDays > 0 ? round((($presentDays + ($lateDays * 0.5)) / $totalDays) * 100, 2) : 0
    ];
    
    // 4. Fetch fee summary
    $stmt = $db->prepare("
        SELECT 
            SUM(amount_due) as total_due,
            SUM(amount_paid) as total_paid,
            SUM(amount_due - amount_paid) as outstanding,
            COUNT(*) as total_items,
            COUNT(CASE WHEN amount_due > amount_paid THEN 1 END) as pending_items
        FROM fee_records 
        WHERE student_id = :sid AND tenant_id = :tid
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $dashboard['fee_summary'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 5. Count pending assignments (placeholder - assignments table may not exist yet)
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM assignment_submissions subs
            JOIN assignments a ON subs.assignment_id = a.id
            WHERE subs.student_id = :sid 
              AND subs.marks_obtained IS NULL
              AND a.due_date >= CURDATE()
        ");
        $stmt->execute(['sid' => $studentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $dashboard['pending_assignments'] = (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        $dashboard['pending_assignments'] = 0;
    }
    
    // 6. Fetch recent notices (limit 5)
    $stmt = $db->prepare("
        SELECT n.*, 
               CASE 
                   WHEN n.target_type = 'batch' AND n.target_id = :batch_id THEN 1
                   WHEN n.target_type = 'all' THEN 1
                   ELSE 0
               END as is_relevant
        FROM notices n
        WHERE n.tenant_id = :tid
          AND n.status = 'active'
          AND (n.target_type = 'all' OR (n.target_type = 'batch' AND n.target_id = :batch_id2))
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $stmt->execute(['tid' => $tenantId, 'batch_id' => $batchId, 'batch_id2' => $batchId]);
    $dashboard['recent_notices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. Fetch upcoming exams (next 7 days)
    try {
        $stmt = $db->prepare("
            SELECT e.*, s.name as subject_name
            FROM exams e
            LEFT JOIN subjects s ON e.subject_id = s.id
            WHERE e.batch_id = :bid
              AND e.exam_date >= CURDATE()
              AND e.exam_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
              AND e.tenant_id = :tid
            ORDER BY e.exam_date ASC
            LIMIT 3
        ");
        $stmt->execute(['bid' => $batchId, 'tid' => $tenantId]);
        $dashboard['upcoming_exams'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $dashboard['upcoming_exams'] = [];
    }
    
    // 8. Count available study materials
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM study_materials sm
            WHERE sm.tenant_id = :tid
              AND sm.status = 'active'
              AND sm.deleted_at IS NULL
              AND (sm.access_type = 'public' 
                   OR (sm.access_type = 'batch' AND sm.batch_id = :batch_id)
                   OR (sm.access_type = 'student' AND sm.student_id = :sid))
        ");
        $stmt->execute(['tid' => $tenantId, 'batch_id' => $batchId, 'sid' => $studentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $dashboard['study_materials_count'] = (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        $dashboard['study_materials_count'] = 0;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $dashboard,
        'timestamp' => date('c')
    ]);
    
} catch (PDOException $e) {
    error_log("Student Dashboard Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'code' => 'DB_ERROR'
    ]);
} catch (Exception $e) {
    error_log("Student Dashboard Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred',
        'code' => 'GENERAL_ERROR'
    ]);
}
