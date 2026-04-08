<?php
/**
 * Attendance API Controller
 * Handles all attendance operations for Institute Admin and Front Desk
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$tenantId = $user['tenant_id'] ?? null;
$userId = $user['id'] ?? null;
$role = $user['role'] ?? '';

if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

// Allow frontdesk, instituteadmin, and superadmin roles
$allowedRoles = ['instituteadmin', 'frontdesk', 'superadmin'];
if (!in_array($role, $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Insufficient permissions.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $service = new \App\Services\AttendanceService();
    
    // GET Requests
    if ($method === 'GET') {
        $action = $_GET['action'] ?? null;
        
        if ($action === 'report') {
            $db = getDBConnection();
            $batchId = $_GET['batch_id'] ?? null;
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');

            // 1) Overall summary stats
            $summSql = "SELECT 
                    COUNT(*) as total_records,
                    SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status='leave' THEN 1 ELSE 0 END) as on_leave
                FROM attendance WHERE tenant_id = :tid AND attendance_date BETWEEN :sd AND :ed";
            $summParams = ['tid' => $tenantId, 'sd' => $startDate, 'ed' => $endDate];
            if ($batchId) { $summSql .= " AND batch_id = :bid"; $summParams['bid'] = $batchId; }
            $stmt = $db->prepare($summSql);
            $stmt->execute($summParams);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2) Daily trend data
            $trendSql = "SELECT attendance_date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) as late
                FROM attendance WHERE tenant_id = :tid AND attendance_date BETWEEN :sd AND :ed";
            $trendParams = ['tid' => $tenantId, 'sd' => $startDate, 'ed' => $endDate];
            if ($batchId) { $trendSql .= " AND batch_id = :bid"; $trendParams['bid'] = $batchId; }
            $trendSql .= " GROUP BY attendance_date ORDER BY attendance_date ASC";
            $stmt = $db->prepare($trendSql);
            $stmt->execute($trendParams);
            $trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3) Top absentees — students with most absences
            $absSql = "SELECT a.student_id, u.name, s.roll_no, s.photo_url, b.name as batch_name,
                    SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) as absent_days,
                    COUNT(*) as total_days
                FROM attendance a
                JOIN students s ON s.id = a.student_id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN batches b ON b.id = a.batch_id
                WHERE a.tenant_id = :tid AND a.attendance_date BETWEEN :sd AND :ed";
            $absParams = ['tid' => $tenantId, 'sd' => $startDate, 'ed' => $endDate];
            if ($batchId) { $absSql .= " AND a.batch_id = :bid"; $absParams['bid'] = $batchId; }
            $absSql .= " GROUP BY a.student_id, u.name, s.roll_no, s.photo_url, b.name
                         HAVING absent_days > 0
                         ORDER BY absent_days DESC LIMIT 10";
            $stmt = $db->prepare($absSql);
            $stmt->execute($absParams);
            $topAbsentees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 4) Batch-wise breakdown
            $batchSql = "SELECT a.batch_id, b.name as batch_name,
                    COUNT(*) as total,
                    SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END) as late
                FROM attendance a
                LEFT JOIN batches b ON b.id = a.batch_id
                WHERE a.tenant_id = :tid AND a.attendance_date BETWEEN :sd AND :ed";
            $batchParams = ['tid' => $tenantId, 'sd' => $startDate, 'ed' => $endDate];
            if ($batchId) { $batchSql .= " AND a.batch_id = :bid"; $batchParams['bid'] = $batchId; }
            $batchSql .= " GROUP BY a.batch_id, b.name ORDER BY b.name";
            $stmt = $db->prepare($batchSql);
            $stmt->execute($batchParams);
            $batchStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 5) Today's absent count
            $todaySql = "SELECT SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent_today
                FROM attendance WHERE tenant_id = :tid AND attendance_date = CURDATE()";
            $todayParams = ['tid' => $tenantId];
            if ($batchId) { $todaySql .= " AND batch_id = :bid"; $todayParams['bid'] = $batchId; }
            $stmt = $db->prepare($todaySql);
            $stmt->execute($todayParams);
            $todayRow = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'trend' => $trend,
                    'top_absentees' => $topAbsentees,
                    'batch_stats' => $batchStats,
                    'absent_today' => (int)($todayRow['absent_today'] ?? 0)
                ]
            ]);
        } elseif ($action === 'export') {
            // CSV export
            $db = getDBConnection();
            $batchId = $_GET['batch_id'] ?? null;
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');

            $sql = "SELECT a.attendance_date, u.name AS full_name, s.roll_no, b.name as batch_name, a.status
                FROM attendance a
                JOIN students s ON s.id = a.student_id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN batches b ON b.id = a.batch_id
                WHERE a.tenant_id = :tid AND a.attendance_date BETWEEN :sd AND :ed";
            $params = ['tid' => $tenantId, 'sd' => $startDate, 'ed' => $endDate];
            if ($batchId) { $sql .= " AND a.batch_id = :bid"; $params['bid'] = $batchId; }
            $sql .= " ORDER BY a.attendance_date DESC, u.name ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $rows]);
        } elseif ($action === 'stats') {
            $stats = \App\Models\Attendance::getTodayStats();
            echo json_encode(['success' => true, 'data' => $stats]);
        } elseif (!empty($_GET['batch_id']) && !empty($_GET['date'])) {
            $batchId = $_GET['batch_id'];
            $date = $_GET['date'];
            
            $records = \App\Models\Attendance::getByBatch($batchId, $date)->toArray();
            $students = \App\Models\Student::getByBatch($batchId);
            $allStats = \App\Models\Attendance::getBatchStudentsStats($batchId)->toArray();
            $leaves = \App\Models\LeaveRequest::getApprovedForDate($date)->toArray();
            
            $result = [];
            foreach ($students as $student) {
                // Ensure student is treated as array if it's a model
                $sData = is_array($student) ? $student : $student->toArray();
                $sId = $sData['id'];

                $statusRow = array_filter($records, fn($r) => $r['student_id'] == $sId);
                $statusRec = reset($statusRow);
                
                $statsRow = array_filter($allStats, fn($s) => $s['student_id'] == $sId);
                $stats = reset($statsRow);
                
                $onLeave = !empty(array_filter($leaves, fn($l) => $l['student_id'] == $sId));
                
                $perc = 0;
                if ($stats && $stats['total_days'] > 0) {
                    $total = $stats['total_days'];
                    $present = $stats['present_days'] + $stats['late_days'];
                    $perc = round(($present / $total) * 100, 1);
                }

                $result[] = [
                    'student_id' => $sId,
                    'roll_no' => $sData['roll_no'],
                    'name' => $sData['full_name'] ?? ($sData['user']['name'] ?? 'N/A'),
                    'full_name' => $sData['full_name'] ?? ($sData['user']['name'] ?? 'N/A'),
                    'photo_url' => $sData['photo_url'],
                    'percentage' => $perc,
                    'on_leave' => $onLeave,
                    'attendance' => $statusRec ? [
                        'id' => $statusRec['id'],
                        'status' => $statusRec['status'],
                        'locked' => $statusRec['locked'],
                    ] : null
                ];
            }
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            // List attendance with pagination
            echo json_encode(['success' => true, 'data' => []]);
        }
    }
    
    // POST - Take attendance or Lock/Unlock
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $action = $input['action'] ?? 'take';
        
        switch ($action) {
            case 'take':
            case 'bulk':
                $result = $service->takeAttendance($input, $userId, $tenantId, $role);
                echo json_encode(['success' => true, 'message' => 'Attendance saved successfully']);
                break;
            case 'lock':
                $ids = $input['ids'] ?? [];
                $service->lockAttendance($ids, $tenantId);
                echo json_encode(['success' => true, 'message' => 'Records locked']);
                break;
            case 'unlock':
                // Check if admin
                if (!in_array($role, ['instituteadmin', 'superadmin'])) {
                    throw new \Exception("Unauthorized to unlock");
                }
                $ids = $input['ids'] ?? [];
                $service->unlockAttendance($ids, $userId, $tenantId);
                echo json_encode(['success' => true, 'message' => 'Records unlocked']);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    }
    
    // PUT/PATCH - Edit attendance
    elseif ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $id = $input['id'] ?? null;
        if (!$id) throw new \Exception("Attendance ID required");
        
        $record = $service->editAttendance($id, $input, $userId, $tenantId, $role);
        echo json_encode(['success' => true, 'data' => $record, 'message' => 'Attendance updated']);
    }

} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
