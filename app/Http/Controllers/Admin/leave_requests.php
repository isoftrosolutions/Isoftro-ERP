<?php
/**
 * Leave Requests API Controller
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

$method = $_SERVER['REQUEST_METHOD'];

try {
    $leaveModel = new \App\Models\LeaveRequest();
    
    if ($method === 'GET') {
        if ($role === 'student') {
            // Student gets only their own requests
            $studentModel = new \App\Models\Student();
            $studentRows = $studentModel->getByTenant($tenantId);
            // In a real scenario we'd match user_id to student_id
            $studentId = $studentRows[0]['id'] ?? 0;
            
            $requests = $leaveModel->getByStudent($studentId, $tenantId);
            echo json_encode(['success' => true, 'data' => $requests]);
        } else {
            // Admin gets all pending or filtered
            $status = $_GET['status'] ?? null;
            if ($status === 'pending') {
                $requests = $leaveModel->getPending($tenantId);
            } else {
                // Get all - simplified for now
                $requests = $leaveModel->getPending($tenantId);
            }
            echo json_encode(['success' => true, 'data' => $requests]);
        }
    }
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $input['tenant_id'] = $tenantId;
        // In real app, associate user to student. Using a mock student_id here from input.
        if (!isset($input['student_id'])) {
            throw new \Exception("Student ID missing");
        }
        
        $req = $leaveModel->create($input);
        echo json_encode(['success' => true, 'data' => $req, 'message' => 'Leave request submitted']);
    }
    elseif ($method === 'PUT' || $method === 'PATCH') {
        if (!in_array($role, ['instituteadmin', 'superadmin', 'frontdesk'])) {
            throw new \Exception("Unauthorized");
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $id = $input['id'] ?? null;
        $action = $input['action'] ?? null;
        
        if (!$id || !$action) throw new \Exception("ID and action required");
        
        if ($action === 'approve') {
            $req = $leaveModel->approve($id, $userId);
            
            // Process the approved leave to mark attendance automatically
            $service = new \App\Services\AttendanceService();
            $service->processApprovedLeave($id, $tenantId);
            
            echo json_encode(['success' => true, 'data' => $req, 'message' => 'Leave approved']);
        } elseif ($action === 'reject') {
            $req = $leaveModel->reject($id, $userId);
            echo json_encode(['success' => true, 'data' => $req, 'message' => 'Leave rejected']);
        } else {
            throw new \Exception("Invalid action");
        }
    }
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
