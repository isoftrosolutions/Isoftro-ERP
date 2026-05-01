<?php
/**
 * Communications Controller — SMS & Email Broadcast, Templates, Message Log
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['userData']['role'] ?? '';
if (!in_array($role, ['instituteadmin', 'frontdesk', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

// Requirements
require_once __DIR__ . '/../../../Helpers/SMSHelper.php';
require_once __DIR__ . '/../../../Helpers/MailHelper.php';

use App\Helpers\SMSHelper;
use App\Helpers\MailHelper;

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

try {
    $db = getDBConnection();

    // ----------------------------------------------------
    // Message Templates CRUD
    // ----------------------------------------------------
    if ($action === 'list_templates' && $method === 'GET') {
        $type = $_GET['type'] ?? ''; // sms, email
        $whereSql = "tenant_id = :tid";
        $params = [':tid' => $tenantId];
        
        if ($type) {
            $whereSql .= " AND type = :type";
            $params[':type'] = $type;
        }
        
        $stmt = $db->prepare("SELECT * FROM message_templates WHERE $whereSql ORDER BY name ASC");
        $stmt->execute($params);
        $templates = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $templates]);
        exit;
    }

    if ($action === 'save_template' && in_array($method, ['POST', 'PUT'])) {
        $id      = $input['id'] ?? null;
        $name    = $input['name'] ?? '';
        $type    = $input['type'] ?? 'sms';
        $subject = $input['subject'] ?? '';
        $content = $input['content'] ?? '';
        
        if (empty($name) || empty($content)) throw new Exception("Name and Content are required");
        
        if ($id) {
            $stmt = $db->prepare("UPDATE message_templates SET name=?, type=?, subject=?, content=? WHERE id=? AND tenant_id=?");
            $stmt->execute([$name, $type, $subject, $content, $id, $tenantId]);
            
            \App\Helpers\AuditLogger::log('UPDATE', 'message_templates', $id, null, $input);
            $msg = "Template updated successfully";
        } else {
            $stmt = $db->prepare("INSERT INTO message_templates (tenant_id, name, type, subject, content) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$tenantId, $name, $type, $subject, $content]);
            $newId = $db->lastInsertId();
            
            \App\Helpers\AuditLogger::log('CREATE', 'message_templates', $newId, null, $input);
            $msg = "Template added successfully";
        }
        
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    if ($action === 'delete_template' && $method === 'DELETE') {
        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Template ID required");
        $stmt = $db->prepare("DELETE FROM message_templates WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);
        
        \App\Helpers\AuditLogger::log('DELETE', 'message_templates', $id);
        echo json_encode(['success' => true, 'message' => 'Template deleted successfully']);
        exit;
    }


    // ----------------------------------------------------
    // Message Log
    // ----------------------------------------------------
    if ($action === 'list_logs' && $method === 'GET') {
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $whereSql = "tenant_id = :tid";
        $params = [':tid' => $tenantId];
        
        if ($type) {
            $whereSql .= " AND type = :type";
            $params[':type'] = $type;
        }
        if ($status) {
            $whereSql .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $stmt = $db->prepare("SELECT * FROM communication_logs WHERE $whereSql ORDER BY created_at DESC LIMIT 100");
        $stmt->execute($params);
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $logs]);
        exit;
    }


    // ----------------------------------------------------
    // Bulk Send (Broadcast)
    // ----------------------------------------------------
    if ($action === 'bulk_send' && $method === 'POST') {
        $type       = $input['type'] ?? 'sms'; // sms, email
        $recipientIds = $input['recipient_ids'] ?? []; // student ids
        $subject    = $input['subject'] ?? '';
        $message    = $input['message'] ?? '';
        
        if (empty($recipientIds) || empty($message)) throw new Exception("Recipients and message are required");
        if ($type === 'email' && empty($subject)) throw new Exception("Subject is required for email");

        // Fetch recipients
        $placeholders = implode(',', array_fill(0, count($recipientIds), '?'));
        $stmtR = $db->prepare("SELECT id, full_name, phone, email FROM students WHERE id IN ($placeholders) AND tenant_id = ? AND deleted_at IS NULL");
        $stmtR->execute(array_merge($recipientIds, [$tenantId]));
        $recipients = $stmtR->fetchAll(\PDO::FETCH_ASSOC);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($recipients as $row) {
            $success = false;
            
            if ($type === 'sms' && !empty($row['phone'])) {
                $content = str_ireplace(['{{name}}', '{{student_name}}'], $row['full_name'], $message);
                $success = SMSHelper::send($db, $tenantId, $row['phone'], $content, $row['id'], 'student');
            } elseif ($type === 'email' && !empty($row['email'])) {
                $body = str_ireplace(['{{name}}', '{{student_name}}'], $row['full_name'], $message);
                $success = MailHelper::sendDirect($db, $tenantId, $row['email'], $row['full_name'], $subject, $body);
            }

            if ($success) $sentCount++;
            else $failedCount++;
        }

        echo json_encode([
            'success' => true,
            'message' => "Broadcast complete: $sentCount sent, $failedCount failed.",
            'sent' => $sentCount,
            'failed' => $failedCount
        ]);
        exit;
    }


    // ----------------------------------------------------
    // Settings CRUD
    // ----------------------------------------------------
    if ($action === 'get_settings' && $method === 'GET') {
        $stmt = $db->prepare("SELECT * FROM sms_settings WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        $settings = $stmt->fetch(\PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $settings]);
        exit;
    }

    if ($action === 'save_settings' && $method === 'POST') {
        $provider = $input['provider'] ?? 'mock';
        $apiKey   = $input['api_key'] ?? '';
        $apiSecret = $input['api_secret'] ?? '';
        $senderId = $input['sender_id'] ?? 'HamroLabs';
        $isActive = (int)($input['is_active'] ?? 1);

        $stmt = $db->prepare("
            INSERT INTO sms_settings (tenant_id, provider, api_key, api_secret, sender_id, is_active)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                provider = VALUES(provider), 
                api_key = VALUES(api_key), 
                api_secret = VALUES(api_secret), 
                sender_id = VALUES(sender_id),
                is_active = VALUES(is_active)
        ");
        $stmt->execute([$tenantId, $provider, $apiKey, $apiSecret, $senderId, $isActive]);
        echo json_encode(['success' => true, 'message' => 'SMS settings updated successfully']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => "Invalid action '{$action}'."]);

} catch (Exception $e) {
    error_log("Communications Controller Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
