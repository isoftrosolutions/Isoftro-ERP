<?php
/**
 * Guardian Fees API
 * Returns fee status, payment history, and dues for the child
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

// Permission check
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

    // 2. Outstanding Dues
    $stmt = $db->prepare("
        SELECT * FROM fee_records 
        WHERE student_id = :sid AND tenant_id = :tid AND status != 'paid'
        ORDER BY due_date ASC
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $dues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Payment History
    $stmt = $db->prepare("
        SELECT * FROM fee_records 
        WHERE student_id = :sid AND tenant_id = :tid AND status = 'paid'
        ORDER BY updated_at DESC LIMIT 20
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'outstanding' => $dues,
            'history' => $history
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
