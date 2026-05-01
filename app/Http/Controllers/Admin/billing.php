<?php
/**
 * Institute Admin — Billing Controller
 */
header('Content-Type: application/json');

$user = getCurrentUser();
if (!$user || ($user['role'] !== 'instituteadmin' && $user['role'] !== 'superadmin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenantId = $user['tenant_id'];
$db = getDBConnection();

try {
    // 1. Fetch Tenant Stats for Limits
    $stmt = $db->prepare("SELECT plan, student_limit, sms_credits FROM tenants WHERE id = :tid");
    $stmt->execute(['tid' => $tenantId]);
    $tenant = $stmt->fetch();

    // 2. Count current students
    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND deleted_at IS NULL");
    $stmt->execute(['tid' => $tenantId]);
    $studentCount = $stmt->fetchColumn();

    // 3. Fetch Payment History
    $stmt = $db->prepare("SELECT plan, amount, status, paid_at FROM tenant_payments WHERE tenant_id = :tid ORDER BY created_at DESC LIMIT 10");
    $stmt->execute(['tid' => $tenantId]);
    $history = $stmt->fetchAll();

    $data = [
        'plan' => $tenant['plan'] ?? 'starter',
        'student_limit' => (int)($tenant['student_limit'] ?? 100),
        'student_count' => (int)$studentCount,
        'sms_limit' => (int)($tenant['sms_credits'] ?? 500),
        'sms_credits' => (int)($tenant['sms_credits'] ?? 0), // Assuming current is same for mock
        'history' => $history ?: []
    ];

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Throwable $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
