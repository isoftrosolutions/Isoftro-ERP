<?php
/**
 * Guardian Contact API
 * Handles sending messages/support tickets to the institute administration
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

// Only POST method for sending messages
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Return history if GET
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("
            SELECT * FROM support_tickets 
            WHERE user_id = :uid AND tenant_id = :tid 
            ORDER BY created_at DESC LIMIT 20
        ");
        $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $tickets]);
        exit;
    } catch (Exception $e) {
        error_log('Controller exception: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
        exit;
    }
}

// POST Logic
$input = json_decode(file_get_contents('php://input'), true);
$subject = $input['subject'] ?? 'Message from Guardian';
$message = $input['message'] ?? '';
$priority = $input['priority'] ?? 'normal';

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message content is required.']);
    exit;
}

try {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        INSERT INTO support_tickets (tenant_id, user_id, subject, description, priority, status, created_at, updated_at)
        VALUES (:tid, :uid, :sub, :desc, :prio, 'pending', NOW(), NOW())
    ");
    
    $stmt->execute([
        'tid' => $tenantId,
        'uid' => $userId,
        'sub' => $subject,
        'desc' => $message,
        'prio' => $priority
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Your message has been sent to the administration. We will get back to you soon.'
    ]);

} catch (PDOException $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    } catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
