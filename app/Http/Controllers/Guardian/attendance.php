<?php
/**
 * Guardian Attendance API
 * Returns detailed attendance history and statistics for the child
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

// Permission check (Simple role check)
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

    // 2. Fetch Attendance History (Last 12 months by default or current academic year)
    // Filter by date range if provided
    $startDate = $_GET['start_date'] ?? date('Y-m-01', strtotime('-11 months'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');

    $stmt = $db->prepare("
        SELECT attendance_date, status, remarks
        FROM attendance
        WHERE student_id = :sid AND tenant_id = :tid 
        AND attendance_date BETWEEN :start AND :end
        ORDER BY attendance_date DESC
    ");
    $stmt->execute([
        'sid' => $studentId, 
        'tid' => $tenantId,
        'start' => $startDate,
        'end' => $endDate
    ]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Overall Statistics
    $stmt = $db->prepare("
        SELECT 
            status, COUNT(*) as count
        FROM attendance
        WHERE student_id = :sid AND tenant_id = :tid
        GROUP BY status
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $statsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = [
        'present' => 0,
        'absent' => 0,
        'late' => 0,
        'half_day' => 0,
        'total' => 0
    ];
    
    foreach ($statsRows as $row) {
        $stats[$row['status']] = (int)$row['count'];
        $stats['total'] += (int)$row['count'];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'history' => $history,
            'summary' => $stats,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
