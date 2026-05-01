<?php
/**
 * Timetable API Controller
 * Handles timetable slot management for the current tenant
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
        if (isset($_GET['stats'])) {
            // Active Batches
            $stmt = $db->prepare("SELECT COUNT(*) FROM batches WHERE tenant_id = :tid AND (status = 'active' OR status IS NULL)");
            $stmt->execute(['tid' => $tenantId]);
            $activeBatches = $stmt->fetchColumn();

            // Total Students
            $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND deleted_at IS NULL");
            $stmt->execute(['tid' => $tenantId]);
            $totalStudents = $stmt->fetchColumn();

            // Total Teachers
            $stmt = $db->prepare("SELECT COUNT(DISTINCT teacher_id) FROM timetable_slots WHERE tenant_id = :tid");
            $stmt->execute(['tid' => $tenantId]);
            $assignedTeachers = $stmt->fetchColumn();

            // Total Slots
            $stmt = $db->prepare("SELECT COUNT(*) FROM timetable_slots WHERE tenant_id = :tid");
            $stmt->execute(['tid' => $tenantId]);
            $totalSlots = $stmt->fetchColumn();

            // Online Classes count
            $stmt = $db->prepare("SELECT COUNT(*) FROM timetable_slots WHERE tenant_id = :tid AND (class_type = 'online' OR (online_link IS NOT NULL AND online_link != ''))");
            $stmt->execute(['tid' => $tenantId]);
            $onlineClasses = $stmt->fetchColumn();

            echo json_encode([
                'success' => true,
                'data' => [
                    'batches' => $activeBatches,
                    'students' => $totalStudents,
                    'teachers' => $assignedTeachers,
                    'slots' => $totalSlots,
                    'online_classes' => $onlineClasses
                ]
            ]);
            exit;
        }

        $batchId = $_GET['batch_id'] ?? null;
        
        // Get timetable slots for a specific batch
        $query = "SELECT ts.*, 
                  b.name as batch_name, 
                  t.full_name as teacher_name,
                  s.name as subject_name,
                  s.code as subject_code,
                  c.name as course_name
                  FROM timetable_slots ts
                  JOIN batches b ON ts.batch_id = b.id
                  LEFT JOIN teachers t ON ts.teacher_id = t.id
                  LEFT JOIN subjects s ON ts.subject_id = s.id
                  LEFT JOIN courses c ON b.course_id = c.id
                  WHERE ts.tenant_id = :tid";
        
        $params = ['tid' => $tenantId];
        
        if ($batchId) {
            $query .= " AND ts.batch_id = :batch_id";
            $params['batch_id'] = $batchId;
        }
        
        $query .= " ORDER BY ts.day_of_week, ts.start_time";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $timetable = $stmt->fetchAll();
        
        // Group by day of week
        $grouped = [];
        $days = [1 => 'Sunday', 2 => 'Monday', 3 => 'Tuesday', 4 => 'Wednesday', 5 => 'Thursday', 6 => 'Friday', 7 => 'Saturday'];
        
        foreach ($timetable as $slot) {
            $day = $slot['day_of_week'];
            if (!isset($grouped[$day])) {
                $grouped[$day] = [
                    'day_name' => $days[$day] ?? 'Unknown',
                    'day_of_week' => $day,
                    'slots' => []
                ];
            }
            // Use derived name if exists
            $slot['subject'] = $slot['subject_name'] ?? 'Unknown Subject';
            $grouped[$day]['slots'][] = $slot;
        }
        
        echo json_encode([
            'success' => true, 
            'data' => $timetable,
            'grouped' => array_values($grouped)
        ]);

    } else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $action = $input['action'] ?? 'create';

        if ($action === 'create') {
            // Create new timetable slot
            $batchId = $input['batch_id'] ?? null;
            $teacherId = $input['teacher_id'] ?? null;
            $subjectId = $input['subject_id'] ?? null;
            $dayOfWeek = $input['day_of_week'] ?? null;
            $startTime = $input['start_time'] ?? null;
            $endTime = $input['end_time'] ?? null;
            $room = $input['room'] ?? null;
            $onlineLink = $input['online_link'] ?? null;
            $classType = $input['class_type'] ?? 'offline';

            if (empty($batchId) || empty($teacherId) || empty($subjectId) || empty($dayOfWeek) || empty($startTime) || empty($endTime)) {
                throw new Exception("Batch, Teacher, Subject, Day, Start Time and End Time are required");
            }

            // Check for time conflicts
            $stmt = $db->prepare("
                SELECT ts.*, b.name as batch_name 
                FROM timetable_slots ts
                JOIN batches b ON ts.batch_id = b.id
                WHERE ts.batch_id = :batch_id 
                AND ts.day_of_week = :day_of_week
                AND ((ts.start_time <= :s1 AND ts.end_time > :s2)
                    OR (ts.start_time < :e1 AND ts.end_time >= :e2)
                    OR (ts.start_time >= :s3 AND ts.end_time <= :e3))
            ");
            $stmt->execute([
                'batch_id' => $batchId,
                'day_of_week' => $dayOfWeek,
                's1' => $startTime,
                's2' => $startTime,
                'e1' => $endTime,
                'e2' => $endTime,
                's3' => $startTime,
                'e3' => $endTime
            ]);
            
            if ($stmt->fetch()) {
                throw new Exception("Time slot conflicts with an existing class in this batch");
            }

            // Check teacher conflict
            $stmt = $db->prepare("
                SELECT ts.*, t.full_name as teacher_name
                FROM timetable_slots ts
                JOIN teachers t ON ts.teacher_id = t.id
                WHERE ts.teacher_id = :teacher_id
                AND ts.day_of_week = :day_of_week
                AND ((ts.start_time <= :s1 AND ts.end_time > :s2)
                    OR (ts.start_time < :e1 AND ts.end_time >= :e2)
                    OR (ts.start_time >= :s3 AND ts.end_time <= :e3))
            ");
            $stmt->execute([
                'teacher_id' => $teacherId,
                'day_of_week' => $dayOfWeek,
                's1' => $startTime,
                's2' => $startTime,
                'e1' => $endTime,
                'e2' => $endTime,
                's3' => $startTime,
                'e3' => $endTime
            ]);
            
            if ($stmt->fetch()) {
                throw new Exception("Teacher has a conflict at this time");
            }

            $stmt = $db->prepare("
                INSERT INTO timetable_slots 
                (tenant_id, batch_id, teacher_id, subject_id, day_of_week, start_time, end_time, room, online_link, class_type, created_at, updated_at)
                VALUES 
                (:tid, :batch_id, :teacher_id, :subject_id, :day_of_week, :start_time, :end_time, :room, :online_link, :class_type, NOW(), NOW())
            ");

            $stmt->execute([
                'tid' => $tenantId,
                'batch_id' => $batchId,
                'teacher_id' => $teacherId,
                'subject_id' => $subjectId,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $room,
                'online_link' => $onlineLink,
                'class_type' => $classType
            ]);

            $slotId = $db->lastInsertId();

            echo json_encode([
                'success' => true, 
                'message' => 'Timetable slot created successfully',
                'data' => ['id' => $slotId]
            ]);

        } else if ($action === 'update') {
            // Update existing timetable slot
            $slotId = $input['id'] ?? null;
            $batchId = $input['batch_id'] ?? null;
            $teacherId = $input['teacher_id'] ?? null;
            $subjectId = $input['subject_id'] ?? null;
            $dayOfWeek = $input['day_of_week'] ?? null;
            $startTime = $input['start_time'] ?? null;
            $endTime = $input['end_time'] ?? null;
            $room = $input['room'] ?? null;
            $onlineLink = $input['online_link'] ?? null;
            $classType = $input['class_type'] ?? 'offline';

            if (empty($slotId)) {
                throw new Exception("Slot ID is required");
            }

            // Verify ownership
            $stmt = $db->prepare("SELECT id FROM timetable_slots WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => $slotId, 'tid' => $tenantId]);
            if (!$stmt->fetch()) {
                throw new Exception("Timetable slot not found");
            }

            $stmt = $db->prepare("
                UPDATE timetable_slots 
                SET batch_id = :batch_id,
                    teacher_id = :teacher_id,
                    subject_id = :subject_id,
                    day_of_week = :day_of_week,
                    start_time = :start_time,
                    end_time = :end_time,
                    room = :room,
                    online_link = :online_link,
                    class_type = :class_type,
                    updated_at = NOW()
                WHERE id = :id AND tenant_id = :tid
            ");

            $stmt->execute([
                'id' => $slotId,
                'tid' => $tenantId,
                'batch_id' => $batchId,
                'teacher_id' => $teacherId,
                'subject_id' => $subjectId,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $room,
                'online_link' => $onlineLink,
                'class_type' => $classType
            ]);

            echo json_encode([
                'success' => true, 
                'message' => 'Timetable slot updated successfully'
            ]);

        } else if ($action === 'delete') {
            // Delete timetable slot
            $slotId = $input['id'] ?? null;

            if (empty($slotId)) {
                throw new Exception("Slot ID is required");
            }

            $stmt = $db->prepare("DELETE FROM timetable_slots WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => $slotId, 'tid' => $tenantId]);

            echo json_encode([
                'success' => true, 
                'message' => 'Timetable slot deleted successfully'
            ]);
        } else {
            throw new Exception("Invalid action");
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
