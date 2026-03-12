<?php
/**
 * Teacher Dashboard API
 * Returns aggregated data for teacher dashboard overview
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

// Get teacher_id from session or user data
$teacherId = $_SESSION['userData']['teacher_id'] ?? null;

// If role is teacher but no teacher_id, try to fetch it
if ($role === 'teacher' && !$teacherId && $userId) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = :uid AND tenant_id = :tid LIMIT 1");
        $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $teacherId = $result['id'];
            $_SESSION['userData']['teacher_id'] = $teacherId;
        }
    } catch (Exception $e) {
        error_log("Failed to fetch teacher_id: " . $e->getMessage());
    }
}

if (!$tenantId || !$teacherId) {
    echo json_encode(['success' => false, 'message' => 'Teacher record not found']);
    exit;
}

try {
    $db = getDBConnection();
    $dashboard = [
        'teacher_info' => null,
        'today_classes' => [],
        'stats' => [
            'today_class_count' => 0,
            'attendance_rate' => 0,
            'pending_assignments' => 0,
            'submitted_questions' => 0
        ],
        'announcements' => [],
        'syllabus_coverage' => [],
        'leave_balance' => []
    ];
    
    // 1. Get teacher basic info
    $stmt = $db->prepare("
        SELECT t.*,
               tenant.name as institute_name, tenant.logo_path as institute_logo
        FROM teachers t
        LEFT JOIN tenants tenant ON t.tenant_id = tenant.id
        WHERE t.id = :tid AND t.tenant_id = :tenant_id
        LIMIT 1
    ");
    $stmt->execute(['tid' => $teacherId, 'tenant_id' => $tenantId]);
    $dashboard['teacher_info'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Fetch today's classes from timetable
    $dayOfWeek = date('w') + 1; // 1=Sunday, 2=Monday, ..., 7=Saturday
    
    $stmt = $db->prepare("
        SELECT t.*, s.name as subject_name, s.code as subject_code,
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
    $today_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format classes for frontend
    $formatted_classes = [];
    $now = date('H:i:s');
    foreach ($today_classes as $cls) {
        $status = 'LATER';
        if ($now >= $cls['start_time'] && $now <= $cls['end_time']) {
            $status = 'ONGOING';
        } else if ($now > $cls['end_time']) {
            $status = 'COMPLETED';
        } else {
            $status = 'UPCOMING';
        }
        
        $cls['status'] = $status;
        $formatted_classes[] = $cls;
    }
    $dashboard['today_classes'] = $formatted_classes;
    $dashboard['stats']['today_class_count'] = count($formatted_classes);
    
    // 3b. Attendance rate — calculate from real attendance records for teacher's batches
    try {
        // Get batch IDs this teacher teaches
        $batchStmt = $db->prepare("
            SELECT DISTINCT batch_id FROM timetable_slots
            WHERE teacher_id = :tid AND tenant_id = :tenant_id
        ");
        $batchStmt->execute(['tid' => $teacherId, 'tenant_id' => $tenantId]);
        $batchIds = array_column($batchStmt->fetchAll(PDO::FETCH_ASSOC), 'batch_id');

        if (!empty($batchIds)) {
            $inClause = implode(',', array_fill(0, count($batchIds), '?'));
            $attStmt = $db->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as present
                FROM attendance
                WHERE batch_id IN ($inClause)
                  AND tenant_id = ?
                  AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $attStmt->execute(array_merge($batchIds, [$tenantId]));
            $attRow = $attStmt->fetch(PDO::FETCH_ASSOC);
            $total   = (int)($attRow['total']   ?? 0);
            $present = (int)($attRow['present'] ?? 0);
            $dashboard['stats']['attendance_rate'] = $total > 0 ? round(($present / $total) * 100, 1) : 0;
        } else {
            $dashboard['stats']['attendance_rate'] = 0;
        }
    } catch (Exception $e) {
        $dashboard['stats']['attendance_rate'] = 0;
        error_log('Teacher attendance rate error: ' . $e->getMessage());
    }

    // 4. Announcements
    try {
        $stmt = $db->prepare("
            SELECT * FROM notices 
            WHERE tenant_id = :tid AND target_type IN ('all', 'staff') AND status = 'active'
            ORDER BY created_at DESC LIMIT 3
        ");
        $stmt->execute(['tid' => $tenantId]);
        $dashboard['announcements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // 5. Syllabus Coverage — real data from study_materials or empty array
    try {
        $syllStmt = $db->prepare("
            SELECT s.name as subject,
                   COUNT(sm.id) as material_count
            FROM batch_subject_allocations bsa
            JOIN subjects s ON bsa.subject_id = s.id
            LEFT JOIN study_materials sm ON sm.subject_id = s.id
                AND sm.tenant_id = :tenant_id AND sm.deleted_at IS NULL
            WHERE bsa.teacher_id = :tid AND bsa.tenant_id = :tenant_id2
            GROUP BY s.id, s.name
            ORDER BY material_count DESC
            LIMIT 5
        ");
        $syllStmt->execute(['tid' => $teacherId, 'tenant_id' => $tenantId, 'tenant_id2' => $tenantId]);
        $subjects = $syllStmt->fetchAll(PDO::FETCH_ASSOC);
        // Normalise to a percentage (max 100% at 10+ materials)
        $coverageColors = ['var(--green)', 'var(--blue)', 'var(--amber)', 'var(--purple)', 'var(--red)'];
        $dashboard['syllabus_coverage'] = array_map(function($s, $i) use ($coverageColors) {
            $pct = min(100, (int)$s['material_count'] * 10);
            return [
                'subject'    => $s['subject'],
                'percentage' => $pct,
                'color'      => $coverageColors[$i % count($coverageColors)]
            ];
        }, $subjects, array_keys($subjects));
    } catch (Exception $e) {
        $dashboard['syllabus_coverage'] = [];
    }

    // 6. Leave Balance — real data from leave_requests
    try {
        $leaveStmt = $db->prepare("
            SELECT leave_type,
                   COUNT(*) as used
            FROM leave_requests
            WHERE user_id = :uid AND tenant_id = :tid
              AND status = 'approved'
              AND YEAR(start_date) = YEAR(CURDATE())
            GROUP BY leave_type
        ");
        $leaveStmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        $leaveRows = $leaveStmt->fetchAll(PDO::FETCH_ASSOC);

        $leaveDefaults = ['Casual' => 12, 'Sick' => 8, 'Emergency' => 3];
        $leaveColors   = ['Casual' => 'var(--blue)', 'Sick' => 'var(--red)', 'Emergency' => 'var(--amber)'];
        $leaveBalance  = [];
        $usedMap = [];
        foreach ($leaveRows as $lr) {
            $usedMap[ucfirst($lr['leave_type'])] = (int)$lr['used'];
        }
        foreach ($leaveDefaults as $type => $total) {
            $used = $usedMap[$type] ?? 0;
            $leaveBalance[] = [
                'type'       => $type . ' Leaves',
                'used'       => $used,
                'total'      => $total,
                'percentage' => $total > 0 ? round(($used / $total) * 100) : 0,
                'color'      => $leaveColors[$type] ?? 'var(--blue)'
            ];
        }
        $dashboard['leave_balance'] = $leaveBalance;
    } catch (Exception $e) {
        $dashboard['leave_balance'] = [];
    }

    echo json_encode([
        'success' => true, 
        'data' => $dashboard,
        'timestamp' => date('c')
    ]);
    
} catch (PDOException $e) {
    error_log("Teacher Dashboard Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred', 'code' => 'DB_ERROR']);
} catch (Exception $e) {
    error_log("Teacher Dashboard Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred', 'code' => 'GENERAL_ERROR']);
}
