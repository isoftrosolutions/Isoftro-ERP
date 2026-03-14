<?php

namespace App\Helpers;

// Directly load PHPMailer core files (no autoloader dependency)
$_base = __DIR__ . '/../../vendor/phpmailer/phpmailer/src/';
require_once $_base . 'Exception.php';
require_once $_base . 'PHPMailer.php';
require_once $_base . 'SMTP.php';
unset($_base);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Base MailHelper class providing core SMTP setup, branding, and queue dispatching.
 */
class MailHelper
{
    // ── Load system SMTP config ──────────────────────────────
    public static function systemConfig(): array
    {
        static $cfg = null;
        if ($cfg === null) {
            $file = __DIR__ . '/../../config/mail.php';
            $cfg  = file_exists($file) ? require $file : [];
        }
        return $cfg;
    }

    // ── Fetch institute branding for this tenant ─────────────
    public static function getTenantBranding(\PDO $db, int $tenantId): array
    {
        $row = null;
        try {
            $stmt = $db->prepare(
                "SELECT sender_name AS from_name, reply_to_email AS from_email, is_active
                 FROM   tenant_email_settings
                 WHERE  tenant_id = :tid LIMIT 1"
            );
            $stmt->execute(['tid' => $tenantId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $row = null;
        }

        $instituteName  = 'Your Institute';
        $institutePhone = '';
        $instituteEmail = '';
        try {
            $s = $db->prepare("SELECT name, phone, email FROM tenants WHERE id = :tid LIMIT 1");
            $s->execute(['tid' => $tenantId]);
            $t = $s->fetch(\PDO::FETCH_ASSOC);
            if (!empty($t['name']))  $instituteName  = $t['name'];
            if (!empty($t['phone'])) $institutePhone = $t['phone'];
            if (!empty($t['email'])) $instituteEmail = $t['email'];
        } catch (\Throwable $e) {}

        return [
            'sender_name'      => $row['from_name']   ?? $instituteName,
            'reply_to_email'   => $row['from_email']  ?? null,
            'is_active'        => ($row['is_active'] ?? 1) ? true : false,
            'institute_name'   => $instituteName,
            'institute_phone'  => $institutePhone,
            'institute_email'  => $instituteEmail,
        ];
    }

    // ── Log Email Activity ──────────────────────────────────
    public static function logEmail(
        \PDO $db,
        int $tenantId,
        int $studentId,
        string $subject,
        string $email,
        string $status,
        ?string $error = null,
        int $campaignId = 0
    ): void {
        try {
            $stmt = $db->prepare("
                INSERT INTO email_logs (tenant_id, student_id, campaign_id, email, subject, status, error_message)
                VALUES (:tid, :sid, :cid, :email, :subj, :status, :err)
            ");
            $stmt->execute([
                'tid' => $tenantId,
                'sid' => $studentId,
                'cid' => $campaignId,
                'email' => $email,
                'subj' => $subject,
                'status' => $status,
                'err' => $error
            ]);
        } catch (\Throwable $e) {
            error_log("[MailHelper] Logging Error: " . $e->getMessage());
        }
    }

    // ── Build a configured PHPMailer instance ────────────────
    public static function buildMailer(array $branding): PHPMailer
    {
        $sys = self::systemConfig();

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host     = $sys['smtp_host']  ?? 'smtp.gmail.com';
        $mail->Port     = (int)($sys['smtp_port'] ?? 587);
        $mail->SMTPAuth = true;
        $mail->Username = $sys['smtp_user']  ?? '';
        $mail->Password = $sys['smtp_pass']  ?? '';
        $mail->Timeout  = (int)($sys['timeout'] ?? 5);
        
        $debugLevel = (int)($sys['debug'] ?? 0); 
        $mail->SMTPDebug = $debugLevel;
        $mail->Debugoutput = function($str, $level) {
            error_log("[PHPMailer Debug] " . trim($str));
        };

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $enc = strtolower($sys['smtp_encryption'] ?? 'tls');
        $mail->SMTPSecure = ($enc === 'ssl')
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;

        $fromEmail = $sys['system_from_email'] ?? $sys['smtp_user'] ?? '';
        $fromName  = $branding['sender_name'];
        $mail->setFrom($fromEmail, $fromName);

        if (!empty($branding['reply_to_email'])) {
            $mail->addReplyTo($branding['reply_to_email'], $fromName);
        }

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        return $mail;
    }

    /**
     * Dispatch an email to the background queue
     */
    public static function dispatch(string $jobType, array $payload, ?int $tenantId = null): bool
    {
        try {
            $queue = new \App\Services\QueueService();
            return (bool)$queue->dispatch($jobType, $payload, $tenantId);
        } catch (\Throwable $e) {
            error_log("[MailHelper] Dispatch Error: " . $e->getMessage());
            return false;
        }
    }

    // ── Fetch Static Template and Replace Placeholders ──────
    public static function getStaticTemplate(string $templateKey, array $data): ?array
    {
        $templates = require __DIR__ . '/../../templates_array.php';

        if (!isset($templates[$templateKey])) {
            return null;
        }

        $subject = $templates[$templateKey]['subject'];
        $body = $templates[$templateKey]['body'];

        $data['institute_name'] = $data['institute_name'] ?? 'Your Institute';
        
        // Auto-compute financial fields if components are present
        if (isset($data['amount_due']) && isset($data['amount_paid']) && !isset($data['balance'])) {
            $data['balance'] = (float)$data['amount_due'] - (float)$data['amount_paid'];
        }
        if (isset($data['balance']) && isset($data['fine_applied']) && !isset($data['total_payable'])) {
            $data['total_payable'] = (float)$data['balance'] + (float)$data['fine_applied'];
        }
        if (!isset($data['login_url'])) {
            $data['login_url'] = (defined('APP_URL') ? APP_URL : '') . '/?page=login';
        }

        foreach ($data as $key => $val) {
            if (is_scalar($val)) {
                $search = '{{' . $key . '}}';
                $valStr = (string)$val;
                // Format numbers that look like money
                if (in_array($key, ['amount', 'amount_due', 'amount_paid', 'balance', 'total_payable', 'fine_applied'])) {
                    $valStr = number_format((float)$val, 2);
                }
                $subject = str_ireplace($search, $valStr, $subject);
                $body = str_ireplace($search, $valStr, $body);
            }
        }

        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Process a job from the queue
     */
    public static function processJob(\PDO $db, int $tenantId, string $jobType, array $payload): bool
    {
        error_log("[MailHelper] Processing Job: {$jobType} for tenant {$tenantId}");
        
        $toEmail = $payload['email'] ?? $payload['student_email'] ?? $payload['staff_email'] ?? $payload['recipient_email'] ?? '';
        $toName  = $payload['name']  ?? $payload['student_name']  ?? $payload['staff_name']  ?? $payload['recipient_name']  ?? 'User';
        
        if (!$toEmail) {
            error_log("[MailHelper] Error: No recipient email in payload.");
            return false;
        }

        if ($jobType === 'student_welcome' || $jobType === 'student_registration_success') {
            // Specialized logic for credentials
            $branding = self::getTenantBranding($db, $tenantId);
            $tplData = array_merge($payload, [
                'institute_name'  => $branding['institute_name'],
                'institute_phone' => $branding['institute_phone'],
                'institute_email' => $branding['institute_email'],
                'student_name'    => $payload['student_name'] ?? $toName,
                'name'            => $payload['name'] ?? $toName
            ]);
            $tpl = self::getStaticTemplate('student_registration_success', $tplData);
            if (!$tpl) return false;
            return self::sendDirect($db, $tenantId, $toEmail, $toName, $tpl['subject'], $tpl['body']);
        }

        if ($jobType === 'payment_receipt' || $jobType === 'send_email_receipt') {
            // Use FinanceEmailHelper templates or generic logic
            $templateKey = $payload['template_key'] ?? (($payload['amount_due'] ?? 0) > 0 ? 'payment_success_partial' : 'payment_success_full');
            $branding = self::getTenantBranding($db, $tenantId);
            $tplData = array_merge($payload, [
                'institute_name' => $branding['institute_name'],
                'student_name'    => $payload['student_name'] ?? $toName,
                'name'            => $payload['name'] ?? $toName
            ]);
            $tpl = self::getStaticTemplate($templateKey, $tplData);
            if ($tpl) {
                return self::sendDirect($db, $tenantId, $toEmail, $toName, $tpl['subject'], $tpl['body'], $payload['pdf_path'] ?? '');
            }
        }

        // --- NEW: Simple Email Broadcast / Campaign handling ---
        if ($jobType === 'send_email' || $jobType === 'generic_broadcast') {
            $subject = $payload['subject'] ?? 'Notification from ' . $toName;
            $body = $payload['body'] ?? '';
            if (empty($body)) return false;
            
            $campaignId = (int)($payload['campaign_id'] ?? 0);
            
            // Log it before sending
            self::logEmail($db, $tenantId, $payload['student_id'] ?? 0, $subject, $toEmail, 'processing', null, $campaignId);
            return self::sendDirect($db, $tenantId, $toEmail, $toName, $subject, $body, '', $campaignId);
        }

        // Generic template-based processing fallback
        $templateKey = $payload['template_key'] ?? $jobType;

        $branding = self::getTenantBranding($db, $tenantId);
        $tplData = array_merge($payload, [
            'institute_name' => $branding['institute_name'],
            'student_name'   => $payload['student_name'] ?? $toName,
            'staff_name'     => $payload['staff_name'] ?? $toName,
            'name'           => $payload['name'] ?? $toName,
            'recipient_name' => $payload['recipient_name'] ?? $toName
        ]);

        $tpl = self::getStaticTemplate($templateKey, $tplData);
        if ($tpl) {
            return self::sendDirect($db, $tenantId, $toEmail, $toName, $tpl['subject'], $tpl['body'], $payload['pdf_path'] ?? '');
        }

        error_log("[MailHelper] Error: No template found for {$templateKey}");
        return false;
    }

    /**
     * Shared send logic (for internal use by specialized helpers)
     */

    public static function sendDirect(\PDO $db, int $tenantId, string $toEmail, string $toName, string $subject, string $htmlBody, string $attachmentPath = '', int $campaignId = 0): bool
    {
        $branding = self::getTenantBranding($db, $tenantId);
        $sys = self::systemConfig();
        if (empty($sys['smtp_pass'])) return false;

        try {
            $mail = self::buildMailer($branding);
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);
            
            if (!empty($attachmentPath) && file_exists($attachmentPath)) {
                $mail->addAttachment($attachmentPath);
            }

            $success = $mail->send();
            self::logEmail($db, $tenantId, 0, $subject, $toEmail, $success ? 'sent' : 'failed', null, $campaignId);
            return $success;
        } catch (\Throwable $e) {
            self::logEmail($db, $tenantId, 0, $subject, $toEmail, 'failed', $e->getMessage(), $campaignId);
            error_log("[MailHelper] Send Direct Error: " . $e->getMessage());
            return false;
        }
    }

    // ── LEGACY PROXIES (For backward compatibility during migration) ──
    public static function sendStudentCredentials(\PDO $db, int $tenantId, array $studentData): bool
    {
        return self::processJob($db, $tenantId, 'student_welcome', $studentData);
    }

    public static function sendPaymentReceiptEmail(\PDO $db, int $tenantId, array $receiptData, string $pdfPath = ''): bool
    {
        $payload = array_merge($receiptData, ['pdf_path' => $pdfPath]);
        return self::processJob($db, $tenantId, 'payment_receipt', $payload);
    }
    
    public static function send(\PDO $db, int $tenantId, string $to, string $name, string $subject, string $html): bool
    {
        return self::sendDirect($db, $tenantId, $to, $name, $subject, $html);
    }
}
