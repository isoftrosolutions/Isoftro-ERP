<?php
/**
 * Hamro ERP — Update Tenant Plan API
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
    
    // Security Checks
    $currentUserId = $_SESSION['userData']['id'] ?? null;
    if (!$currentUserId) {
        throw new Exception("Unauthorized: No active session.");
    }
    
    // CSRF Check
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        throw new Exception("Security violation: Invalid CSRF token.");
    }

    $id = sanitizeInput($_POST['id'] ?? '');
    $plan = sanitizeInput($_POST['plan'] ?? '');
    
    if (!$id || !$plan) {
        throw new Exception("Missing parameters.");
    }

    // Update plan and also student_limit based on plan
    $limits = [
        'starter' => 150,
        'growth' => 500,
        'professional' => 1500,
        'enterprise' => 10000 
    ];
    $limit = $limits[$plan] ?? 100;

    $stmt = $pdo->prepare("UPDATE tenants SET plan = ?, student_limit = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$plan, $limit, $id]);
    
    // Log the action
    $userIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, description, created_at) VALUES (?, 'Plan Updated', ?, ?, ?, NOW())");
    $stmt->execute([$currentUserId, $userIp, $userAgent, "Plan for tenant ID $id updated to $plan ($limit students)"]);

    // Send back the new CSRF token in header
    if (function_exists('getCsrfToken')) {
        header('X-CSRF-Token: ' . getCsrfToken());
    }

    echo json_encode(['success' => true, 'message' => 'Plan updated successfully.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
