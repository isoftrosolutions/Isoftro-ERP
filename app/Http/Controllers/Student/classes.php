<?php
/**
 * Student Classes/Timetable API
 * Handles timetable viewing for students
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
$tenantId = $user['tenant_id'] ?? null;
$studentId = $_SESSION['userData']['student_id'] ?? null;

if (!$tenantId || !$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student record not found']);
    exit;
}

$action = $_GET['action'] ?? 'today';

try {
    $db = getDBConnection();
    
    // Get student's batch info
    $stmt = $db->prepare("
        SELECT batch_id FROM enrollments WHERE student_id = :sid AND tenant_id = :tid AND status = 'active' LIMIT 1
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $batchId = $student['batch_id'] ?? null;
    
    if (!$batchId) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'No batch assigned yet'
        ]);
        exit;
    }
    
    $dayMap = [
        'Sunday'    => 1,
        'Monday'    => 2,
        'Tuesday'   => 3,
        'Wednesday' => 4,
        'Thursday'  => 5,
        'Friday'    => 6,
        'Saturday'  => 7
    ];

    switch ($action) {
        case 'today':
            // Get today's classes
            $dayOfWeek = date('l');
            $dayNum = $dayMap[$dayOfWeek];
            
            $stmt = $db->prepare("
                SELECT t.*, s.name as subject_name, s.code as subject_code,
                       tr.full_name as teacher_name, tr.phone as teacher_contact,
                       t.room as room_name
                FROM timetable_slots t
                LEFT JOIN subjects s ON t.subject_id = s.id
                LEFT JOIN teachers tr ON t.teacher_id = tr.id
                WHERE t.batch_id = :bid 
                  AND t.day_of_week = :day
                  AND t.tenant_id = :tid
                ORDER BY t.start_time ASC
            ");
            $stmt->execute(['bid' => $batchId, 'day' => $dayNum, 'tid' => $tenantId]);
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add status based on current time
            $currentTime = date('H:i:s');
            foreach ($classes as &$class) {
                if ($currentTime < $class['start_time']) {
                    $class['status'] = 'upcoming';
                } elseif ($currentTime >= $class['start_time'] && $currentTime <= $class['end_time']) {
                    $class['status'] = 'ongoing';
                } else {
                    $class['status'] = 'completed';
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $classes,
                'day' => $dayOfWeek,
                'date' => date('Y-m-d')
            ]);
            break;
            
        case 'weekly':
            // Get full week schedule
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $weeklySchedule = [];
            
            foreach ($days as $dayName) {
                $dayNum = $dayMap[$dayName];
                $stmt = $db->prepare("
                    SELECT t.*, s.name as subject_name, s.code as subject_code,
                           tr.full_name as teacher_name, t.room as room_name
                    FROM timetable_slots t
                    LEFT JOIN subjects s ON t.subject_id = s.id
                    LEFT JOIN teachers tr ON t.teacher_id = tr.id
                    WHERE t.batch_id = :bid 
                      AND t.day_of_week = :day
                      AND t.tenant_id = :tid
                    ORDER BY t.start_time ASC
                ");
                $stmt->execute(['bid' => $batchId, 'day' => $dayNum, 'tid' => $tenantId]);
                $weeklySchedule[$dayName] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $weeklySchedule
            ]);
            break;
            
        case 'calendar':
            // Get academic calendar events
            $month = $_GET['month'] ?? date('m');
            $year = $_GET['year'] ?? date('Y');
            
            $stmt = $db->prepare("
                SELECT *, start_date as event_date FROM academic_calendar 
                WHERE tenant_id = :tid
                  AND MONTH(start_date) = :month
                  AND YEAR(start_date) = :year
                  AND (batch = 'all' OR batch = 'batch' OR batch = :bid)
                  AND deleted_at IS NULL
                ORDER BY start_date ASC
            ");
            $stmt->execute(['tid' => $tenantId, 'month' => $month, 'year' => $year, 'bid' => $batchId]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $events,
                'month' => $month,
                'year' => $year
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    error_log("Student Classes Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'code' => 'DB_ERROR'
    ]);
} catch (Exception $e) {
    error_log("Student Classes Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred',
        'code' => 'GENERAL_ERROR'
    ]);
}
