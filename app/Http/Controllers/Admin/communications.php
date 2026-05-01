<?php
/**
 * Communications Controller
 * Handles masu.emailing and campaigns
 */

require_once realpath(__DIR__ . '/../../../../config/config.php');
requireAuth();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$tenantId = $_SESSION['tenant_id'];
$db = getDBConnection();

// Initial Setup / Migration
function self_migrate_comms($db) {
    // email_campaigns table
    $db->exec("CREATE TABLE IF NOT EXISTS email_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        campaign_name VARCHAR(255) NOT NULL,
        target_group VARCHAR(50) NOT NULL,
        target_id INT DEFAULT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        total_recipients INT DEFAULT 0,
        sent_count INT DEFAULT 0,
        status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // email_logs (if not exists)
    $db->exec("CREATE TABLE IF NOT EXISTS email_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        student_id INT DEFAULT 0,
        campaign_id INT DEFAULT 0,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status ENUM('queued', 'processing', 'sent', 'failed') DEFAULT 'queued',
        error_message TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // sms_logs (if not exists)
    $db->exec("CREATE TABLE IF NOT EXISTS sms_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        recipient_phone VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('queued', 'sent', 'delivered', 'failed') DEFAULT 'queued',
        error_message TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

self_migrate_comms($db);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'list_campaigns') {
            $stmt = $db->prepare("SELECT * FROM email_campaigns WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$tenantId]);
            $campaigns = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Meta stats
            $stmt = $db->prepare("SELECT SUM(sent_count) as total FROM email_campaigns WHERE tenant_id = ?");
            $stmt->execute([$tenantId]);
            $totalSent = $stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0;

            echo json_encode([
                'success' => true, 
                'data' => $campaigns,
                'meta' => ['total_sent' => (int)$totalSent]
            ]);
            exit;
        }

        if ($action === 'list_logs') {
            $type = $_GET['type'] ?? 'email';
            if ($type === 'email') {
                $stmt = $db->prepare("
                    SELECT el.*, u.name as student_name 
                    FROM email_logs el 
                    LEFT JOIN students s ON el.student_id = s.id 
                    WHERE el.tenant_id = ? 
                    ORDER BY el.created_at DESC 
                    LIMIT 100
                ");
                $stmt->execute([$tenantId]);
                $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $stmt = $db->prepare("SELECT * FROM sms_logs WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 100");
                $stmt->execute([$tenantId]);
                $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            echo json_encode([
                'success' => true, 
                'data' => $logs
            ]);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'launch_campaign') {
            $name = $_POST['name'] ?? '';
            $target = $_POST['target'] ?? '';
            $targetId = $_POST['target_id'] ?? null;
            $subject = $_POST['subject'] ?? '';
            $message = $_POST['message'] ?? '';

            if (!$name || !$subject || !$message) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }

            // 1. Fetch Recipients
            $recipients = [];
            if ($target === 'all_students') {
                $stmt = $db->prepare("SELECT s.id, u.name as name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE tenant_id = ? AND status = 'active' AND (email IS NOT NULL AND email != '')");
                $stmt->execute([$tenantId]);
                $recipients = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } elseif ($target === 'all_teachers') {
                $stmt = $db->prepare("SELECT id, name, email FROM teachers WHERE tenant_id = ? AND status = 'active' AND (email IS NOT NULL AND email != '')");
                $stmt->execute([$tenantId]);
                $recipients = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } elseif ($target === 'by_course') {
                $stmt = $db->prepare("
                    SELECT s.id, u.name as name, u.email 
                    FROM students s
                    JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' JOIN batches b ON e.batch_id = b.id
                    WHERE s.tenant_id = ? AND b.course_id = ? AND s.status = 'active' AND (u.email IS NOT NULL AND u.email != '')
                ");
                $stmt->execute([$tenantId, $targetId]);
                $recipients = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } elseif ($target === 'by_batch') {
                $stmt = $db->prepare("SELECT s.id, u.name as name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE tenant_id = ? AND batch_id = ? AND status = 'active' AND (email IS NOT NULL AND email != '')");
                $stmt->execute([$tenantId, $targetId]);
                $recipients = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            if (empty($recipients)) {
                echo json_encode(['success' => false, 'message' => 'No recipients found for this group']);
                exit;
            }

            // 2. Create Campaign Record
            $stmt = $db->prepare("INSERT INTO email_campaigns (tenant_id, campaign_name, target_group, target_id, subject, message, total_recipients, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tenantId, $name, $target, $targetId, $subject, $message, count($recipients), 'processing']);
            $campaignId = $db->lastInsertId();

            // 3. Dispatch to Queue
            $queue = new \App\Services\QueueService();
            $dispatched = 0;
            foreach ($recipients as $r) {
                $payload = [
                    'recipient_email' => $r['email'],
                    'recipient_name'  => $r['name'],
                    'subject'         => $subject,
                    'body'            => $message,
                    'campaign_id'     => $campaignId,
                    'student_id'      => $r['id'],
                    'template_key'    => 'generic_broadcast'
                ];
                
                if ($queue->dispatch('send_email', $payload, $tenantId)) {
                    $dispatched++;
                }
            }

            if ($dispatched === 0) {
                $db->prepare("UPDATE email_campaigns SET status = 'failed' WHERE id = ?")->execute([$campaignId]);
            } else {
                $db->prepare("UPDATE email_campaigns SET status = 'completed' WHERE id = ?")->execute([$campaignId]);
            }

            echo json_encode(['success' => true, 'count' => $dispatched]);
            exit;
        }
    }
} catch (\Throwable $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    exit;
    }
