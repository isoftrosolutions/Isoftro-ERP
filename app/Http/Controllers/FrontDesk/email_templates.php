<?php
/**
 * Email Templates API Controller
 *
 * Handles CRUD operations for the 10 customizable email templates.
 */

$_root = dirname(__DIR__, 4);

if (!defined('APP_NAME')) {
    require_once $_root . '/config/config.php';
}
unset($_root);

header('Content-Type: application/json');

// Ensure any uncaught error returns JSON
set_exception_handler(function(Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
});
set_error_handler(function($errno, $errstr) {
    throw new \ErrorException($errstr, 0, $errno);
}, E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);

if (!isLoggedIn() || $_SESSION['userData']['role'] !== 'instituteadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path   = $_SERVER['REQUEST_URI'];

try {
    $db = getDBConnection();

    // ── GET /api/admin/email_templates (List all templates) ──
    if ($method === 'GET') {
        // Support getting a single template if 'id' is provided
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $db->prepare("SELECT id, template_key, template_name, subject, body_content, is_active FROM email_templates WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                echo json_encode(['success' => false, 'message' => 'Template not found']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $template]);
            exit;
        }

        // Return all templates for this tenant
        $stmt = $db->prepare("SELECT id, template_key, template_name, subject, body_content, is_active FROM email_templates WHERE tenant_id = :tid ORDER BY id ASC");
        $stmt->execute(['tid' => $tenantId]);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $templates]);
        exit;
    }

    // ── POST /api/admin/email_templates (Update a template) ──
    if ($method === 'POST') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$id) {
            throw new Exception('Template ID is required for update.');
        }

        $subject     = trim($_POST['subject'] ?? '');
        $bodyContent = trim($_POST['body_content'] ?? '');
        $isActive    = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (empty($subject) || empty($bodyContent)) {
            throw new Exception('Subject and body content cannot be empty.');
        }

        // Verify ownership
        $stmt = $db->prepare("SELECT id FROM email_templates WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        if (!$stmt->fetch()) {
            throw new Exception('Unauthorized to update this template or it does not exist.');
        }

        $updateStmt = $db->prepare("
            UPDATE email_templates 
            SET subject = :subject, 
                body_content = :body_content, 
                is_active = :is_active, 
                updated_at = NOW() 
            WHERE id = :id AND tenant_id = :tid
        ");
        
        $updateStmt->execute([
            'subject'      => $subject,
            'body_content' => $bodyContent,
            'is_active'    => $isActive,
            'id'           => $id,
            'tid'          => $tenantId
        ]);

        echo json_encode([
            'success' => true, 
            'message' => 'Template updated successfully.'
        ]);
        exit;
    }

    throw new Exception('Invalid Request Method');

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
