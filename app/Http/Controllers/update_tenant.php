<?php
/**
 * Hamro ERP — Update Tenant API
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

    // Get POST data
    $id = sanitizeInput($_POST['id'] ?? '');
    $name = sanitizeInput($_POST['name'] ?? '');
    $nepaliName = sanitizeInput($_POST['nepaliName'] ?? '');
    $subdomain = sanitizeInput($_POST['subdomain'] ?? '');
    $brandColor = sanitizeInput($_POST['brandColor'] ?? '#009E7E');
    $tagline = sanitizeInput($_POST['tagline'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    $plan = sanitizeInput($_POST['plan'] ?? 'starter');
    $status = sanitizeInput($_POST['status'] ?? 'trial');
    
    if (!$id || !$name || !$subdomain) {
        throw new Exception("Required fields are missing.");
    }
    
    // Check if subdomain exists for other tenants
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE subdomain = ? AND id != ?");
    $stmt->execute([$subdomain, $id]);
    if ($stmt->fetch()) {
        throw new Exception("The subdomain '$subdomain' is already taken.");
    }

    $pdo->beginTransaction();
    
    // Update Tenant
    $stmt = $pdo->prepare("UPDATE tenants SET name = ?, nepali_name = ?, subdomain = ?, brand_color = ?, tagline = ?, address = ?, phone = ?, plan = ?, status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$name, $nepaliName, $subdomain, $brandColor, $tagline, $address, $phone, $plan, $status, $id]);
    
    // Log the action
    $userIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, description, created_at) VALUES (?, 'Tenant Updated', ?, ?, ?, NOW())");
    $stmt->execute([$currentUserId, $userIp, $userAgent, "Tenant '$name' ($subdomain) updated."]);
    
    $pdo->commit();
    
    // Send back the new CSRF token in header for synchronized AJAX requests
    if (function_exists('getCsrfToken')) {
        header('X-CSRF-Token: ' . getCsrfToken());
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Institute updated successfully!'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
