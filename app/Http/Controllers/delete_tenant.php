<?php
/**
 * iSoftro ERP — Delete Tenant API
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // 0. Security Checks
    $currentUserId = $_SESSION['userData']['id'] ?? null;
    if (!$currentUserId) {
        throw new Exception("Unauthorized: No active session.");
    }
    
    // CSRF Check
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        throw new Exception("Security violation: Invalid CSRF token.");
    }

    // Get tenant ID
    $id = sanitizeInput($_POST['id'] ?? '');
    
    if (!$id) {
        throw new Exception("Tenant ID is required.");
    }
    
    $pdo->beginTransaction();
    
    // Check if tenant exists
    $stmt = $pdo->prepare("SELECT name FROM tenants WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    $tenant = $stmt->fetch();
    
    if (!$tenant) {
        throw new Exception("Tenant not found.");
    }
    
    // Soft delete tenant
    $stmt = $pdo->prepare("UPDATE tenants SET deleted_at = NOW(), status = 'suspended' WHERE id = ?");
    $stmt->execute([$id]);
    
    // Suspend all users in this tenant
    $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE tenant_id = ?");
    $stmt->execute([$id]);
    
    // Log the action
    $userIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, description, created_at) VALUES (?, 'Tenant Deleted', ?, ?, ?, NOW())");
    $stmt->execute([$currentUserId, $userIp, $userAgent, "Tenant '{$tenant['name']}' (ID: $id) was soft-deleted and users suspended."]);
    
    $pdo->commit();
    
    // Send back the new CSRF token in header for synchronized AJAX requests
    if (function_exists('getCsrfToken')) {
        header('X-CSRF-Token: ' . getCsrfToken());
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Institute deleted successfully!'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
