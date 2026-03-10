<?php
/**
 * Student Leave Request API
 * Handles leave applications for students
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
$userId = $user['id'] ?? null;
$studentId = $_SESSION['userData']['student_id'] ?? null;

if (!$tenantId || !$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student record not found']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $db = getDBConnection();
    
    switch ($action) {
        case 'list':
        case 'my_requests':
            // Get student's leave requests
            $status = $_GET['status'] ?? null;
            
            $query = "
                SELECT * FROM leave_requests 
                WHERE student_id = :sid AND tenant_id = :tid
            ";
            $params = ['sid' => $studentId, 'tid' => $tenantId];
            
            if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
                $query .= " AND status = :status";
                $params['status'] = $status;
            }
            
            $query .= " ORDER BY created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get counts
            $stmt = $db->prepare("
                SELECT status, COUNT(*) as count 
                FROM leave_requests 
                WHERE student_id = :sid AND tenant_id = :tid
                GROUP BY status
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $statusCounts = [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0
            ];
            foreach ($counts as $c) {
                $statusCounts[$c['status']] = (int)$c['count'];
                $statusCounts['total'] += (int)$c['count'];
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'requests' => $requests,
                    'counts' => $statusCounts
                ]
            ]);
            break;
            
        case 'create':
        case 'submit':
            // Create new leave request
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            
            $fromDate = $input['from_date'] ?? null;
            $toDate = $input['to_date'] ?? null;
            $reason = $input['reason'] ?? null;
            
            // Validation
            if (!$fromDate || !$toDate) {
                echo json_encode(['success' => false, 'message' => 'Please provide from and to dates']);
                exit;
            }
            
            if (strtotime($fromDate) > strtotime($toDate)) {
                echo json_encode(['success' => false, 'message' => 'From date cannot be after To date']);
                exit;
            }
            
            if (strtotime($fromDate) < strtotime(date('Y-m-d'))) {
                echo json_encode(['success' => false, 'message' => 'From date cannot be in the past']);
                exit;
            }
            
            if (empty($reason) || strlen(trim($reason)) < 10) {
                echo json_encode(['success' => false, 'message' => 'Please provide a reason (at least 10 characters)']);
                exit;
            }
            
            // Insert leave request
            $stmt = $db->prepare("
                INSERT INTO leave_requests 
                (tenant_id, student_id, from_date, to_date, reason, status, created_at, updated_at)
                VALUES 
                (:tid, :sid, :from_date, :to_date, :reason, 'pending', NOW(), NOW())
            ");
            
            $stmt->execute([
                'tid' => $tenantId,
                'sid' => $studentId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'reason' => trim($reason)
            ]);
            
            $leaveId = $db->lastInsertId();
            
            // Get the inserted record
            $stmt = $db->prepare("SELECT * FROM leave_requests WHERE id = :id");
            $stmt->execute(['id' => $leaveId]);
            $newRequest = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Leave request submitted successfully',
                'data' => $newRequest
            ]);
            break;
            
        case 'cancel':
            // Cancel a pending leave request
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            
            $leaveId = $input['id'] ?? null;
            
            if (!$leaveId) {
                echo json_encode(['success' => false, 'message' => 'Leave request ID required']);
                exit;
            }
            
            // Verify ownership and check status
            $stmt = $db->prepare("
                SELECT * FROM leave_requests 
                WHERE id = :id AND student_id = :sid AND tenant_id = :tid
            ");
            $stmt->execute(['id' => $leaveId, 'sid' => $studentId, 'tid' => $tenantId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing) {
                echo json_encode(['success' => false, 'message' => 'Leave request not found']);
                exit;
            }
            
            if ($existing['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Only pending requests can be cancelled']);
                exit;
            }
            
            // Delete the request
            $stmt = $db->prepare("DELETE FROM leave_requests WHERE id = :id");
            $stmt->execute(['id' => $leaveId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Leave request cancelled successfully'
            ]);
            break;
            
        case 'stats':
            // Get leave statistics
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM leave_requests 
                WHERE student_id = :sid AND tenant_id = :tid
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get total days of approved leave
            $stmt = $db->prepare("
                SELECT SUM(DATEDIFF(to_date, from_date) + 1) as total_days
                FROM leave_requests 
                WHERE student_id = :sid AND tenant_id = :tid AND status = 'approved'
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $days = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total' => (int)($stats['total'] ?? 0),
                    'pending' => (int)($stats['pending'] ?? 0),
                    'approved' => (int)($stats['approved'] ?? 0),
                    'rejected' => (int)($stats['rejected'] ?? 0),
                    'total_days' => (int)($days['total_days'] ?? 0)
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
