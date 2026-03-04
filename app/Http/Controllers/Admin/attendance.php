<?php
/**
 * Attendance API Controller
 * Handles all attendance operations for Institute Admin and Front Desk
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
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
            // Placeholder: Student-wise report
            echo json_encode(['success' => true, 'data' => []]);
        } elseif ($action === 'export') {
            // Placeholder: Export data
            echo json_encode(['success' => true, 'data' => []]);
        } elseif ($action === 'stats') {
            $attendanceModel = new \App\Models\Attendance();
            $stats = $attendanceModel->getTodayStats($tenantId);
            echo json_encode(['success' => true, 'data' => $stats]);
        } elseif (!empty($_GET['batch_id']) && !empty($_GET['date'])) {
            $batchId = $_GET['batch_id'];
            $date = $_GET['date'];
            $attendanceModel = new \App\Models\Attendance();
            $records = $attendanceModel->getByBatch($batchId, $date, $tenantId);
            
            // Also fetch students in this batch if attendance is marked and combine
            $studentModel = new \App\Models\Student();
            $students = $studentModel->getByBatch($batchId, $tenantId);
            
            $result = [];
            foreach ($students as $student) {
                $statusRow = array_filter($records, fn($r) => $r['student_id'] == $student['id']);
                $statusRec = reset($statusRow);
                
                $result[] = [
                    'student_id' => $student['id'],
                    'roll_no' => $student['roll_no'],
                    'full_name' => $student['full_name'],
                    'photo_url' => $student['photo_url'],
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

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
