<?php
/**
 * Guardian Notices API
 * Returns all notices relevant to the guardian or their child
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
    
    // 1. Get student info for batch filtering
    $stmt = $db->prepare("
        SELECT s.batch_id
        FROM guardians g
        JOIN students s ON g.student_id = s.id
        WHERE g.user_id = :uid AND g.tenant_id = :tid 
        LIMIT 1
    ");
    $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
    $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $batchId = $studentInfo['batch_id'] ?? null;

    // 2. Fetch Notices
    $stmt = $db->prepare("
        SELECT * FROM notices 
        WHERE tenant_id = :tid 
        AND (
            target_type IN ('all', 'guardians') 
            OR (target_type = 'batch' AND target_id = :bid)
        )
        AND status = 'active'
        ORDER BY created_at DESC
    ");
    $stmt->execute(['tid' => $tenantId, 'bid' => $batchId]);
    $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $notices
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
