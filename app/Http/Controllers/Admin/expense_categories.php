<?php
// Fetches Expense categories directly from the Chart of Accounts to unify the UI
require_once app_path('Helpers/auth.php');
requireAuth();

$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'] ?? null;

if (!$tenantId && function_exists('getCurrentUser')) {
     $user = getCurrentUser();
     $tenantId = $user['tenant_id'] ?? null;
}

try {
    $stmt = $db->prepare("SELECT id, name, code FROM acc_accounts WHERE tenant_id = ? AND type = 'expense' AND deleted_at IS NULL ORDER BY name ASC");
    $stmt->execute([$tenantId]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
exit;
