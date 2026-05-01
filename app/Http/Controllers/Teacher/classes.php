<?php
/**
 * Teacher Classes API
 * Returns class schedule and timetable data
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

$action = $_GET['action'] ?? 'today';

// Quick lookup
$teacherId = $_SESSION['userData']['teacher_id'] ?? null;
if (!$teacherId && $userId) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = :uid AND tenant_id = :tid LIMIT 1");
        $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $teacherId = $row['id'];
            $_SESSION['userData']['teacher_id'] = $teacherId;
        }
    } catch (Exception $e) {}
    }

if (!$teacherId) {
    echo json_encode(['success' => false, 'message' => 'Teacher profile not mapped']);
    exit;
}

try {
    $db = getDBConnection();
    
    if ($action === 'today') {
        $dayOfWeek = date('w') + 1; // 1=Sunday
        
        $stmt = $db->prepare("
            SELECT t.*, s.name as subject_name,
                   b.name as batch_name, c.name as course_name
            FROM timetable_slots t
            LEFT JOIN subjects s ON t.subject_id = s.id
            LEFT JOIN batches b ON t.batch_id = b.id
            LEFT JOIN courses c ON b.course_id = c.id
            WHERE t.teacher_id = :tid 
              AND t.day_of_week = :day
              AND t.tenant_id = :tenant_id
            ORDER BY t.start_time ASC
        ");
        $stmt->execute(['tid' => $teacherId, 'day' => $dayOfWeek, 'tenant_id' => $tenantId]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $formatted = [];
        foreach ($classes as $cls) {
            $formatted[] = [
                'id' => $cls['id'],
                'start_time' => $cls['start_time'],
                'end_time' => $cls['end_time'],
                'subject_name' => $cls['subject_name'],
                'batch_name' => $cls['batch_name'],
                'room' => $cls['room']
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $formatted]);
        exit;
    }
    
    if ($action === 'weekly') {
        $stmt = $db->prepare("
            SELECT t.*, s.name as subject_name, b.name as batch_name
            FROM timetable_slots t
            LEFT JOIN subjects s ON t.subject_id = s.id
            LEFT JOIN batches b ON t.batch_id = b.id
            WHERE t.teacher_id = :tid 
              AND t.tenant_id = :tenant_id
            ORDER BY t.day_of_week ASC, t.start_time ASC
        ");
        $stmt->execute(['tid' => $teacherId, 'tenant_id' => $tenantId]);
        $weekly = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $weekly]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    
} catch (PDOException $e) {
    error_log("Teacher Classes Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
    } catch (Exception $e) {
    error_log("Teacher Classes Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error']);
    }
