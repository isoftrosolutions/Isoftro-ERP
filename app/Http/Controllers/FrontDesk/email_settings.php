<?php
/**
 * Email Settings API Controller — Simplified
 *
 * Institute only sets: sender_name, reply_to_email, is_active
 * All SMTP credentials are managed by the system (config/mail.php)
 */

// Project root = 4 levels up from: app/Http/Controllers/Admin/
$_root = dirname(__DIR__, 4);

if (!defined('APP_NAME')) {
    require_once $_root . '/config/config.php';
}
// MailHelper: Admin/ → 3 up → app/, then /Helpers/
if (!class_exists('App\\Helpers\\MailHelper')) {
    require_once __DIR__ . '/../../../Helpers/MailHelper.php';
}
unset($_root);

use App\Helpers\MailHelper;

header('Content-Type: application/json');

// Ensure any uncaught error returns JSON — never HTML
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

    // ── Ensure new columns exist (auto-migration) ────────────────
    self_migrate($db);

    // ── TEST EMAIL ───────────────────────────────────────────────
    if (str_contains($path, '/test')) {
        if ($method !== 'POST') throw new Exception('Method not allowed');

        $testEmail  = trim($_POST['test_email']  ?? '');
        $senderName = trim($_POST['sender_name'] ?? 'Hamro ERP');

        if (!$testEmail || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        // Get institute name
        $stmt = $db->prepare("SELECT name FROM tenants WHERE id = :tid LIMIT 1");
        $stmt->execute(['tid' => $tenantId]);
        $tenant = $stmt->fetch();
        $instituteName = $senderName ?: ($tenant['name'] ?? 'Your Institute');
        $loginUrl = (defined('APP_URL') ? APP_URL : 'http://localhost/erp') . '/login';

        // Override branding with form values for the test
        $sent = MailHelper::send(
            $db,
            (int)$tenantId,
            $testEmail,
            'Test Recipient',
            "Test: Welcome to {$instituteName} – Your Student Account Details",
            buildTestHtml($instituteName, $testEmail, 'Demo@1234', $loginUrl)
        );

        if ($sent) {
            echo json_encode(['success' => true, 'message' => "Test email sent to {$testEmail}"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Email send failed. Check that system SMTP credentials are configured in config/mail.php']);
        }
        exit;
    }

    // ── GET settings ─────────────────────────────────────────────
    if ($method === 'GET') {
        $stmt = $db->prepare("SELECT sender_name, reply_to_email, is_active FROM tenant_email_settings WHERE tenant_id = :tid");
        $stmt->execute(['tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Also try to backfill from old fields if new ones are empty
        if ($row && empty($row['sender_name'])) {
            $stmt2 = $db->prepare("SELECT from_name AS sender_name, from_email AS reply_to_email, is_active FROM tenant_email_settings WHERE tenant_id = :tid");
            $stmt2->execute(['tid' => $tenantId]);
            $row = $stmt2->fetch(PDO::FETCH_ASSOC);
        }

        echo json_encode(['success' => true, 'data' => $row ?: null]);
        exit;
    }

    // ── POST — Save settings ─────────────────────────────────────
    if ($method === 'POST') {
        $senderName   = trim($_POST['sender_name']    ?? '');
        $replyToEmail = trim($_POST['reply_to_email'] ?? '');
        $isActive     = isset($_POST['is_active']) ? 1 : 0;

        if (!$senderName) throw new Exception('Sender name is required.');

        // Upsert
        $stmt = $db->prepare("SELECT id FROM tenant_email_settings WHERE tenant_id = :tid");
        $stmt->execute(['tid' => $tenantId]);
        $exists = $stmt->fetch();

        if ($exists) {
            $db->prepare("
                UPDATE tenant_email_settings
                SET sender_name = :sn, reply_to_email = :rt, is_active = :act, updated_at = NOW()
                WHERE tenant_id = :tid
            ")->execute(['sn' => $senderName, 'rt' => $replyToEmail ?: null, 'act' => $isActive, 'tid' => $tenantId]);
        } else {
            $db->prepare("
                INSERT INTO tenant_email_settings (tenant_id, sender_name, reply_to_email, is_active)
                VALUES (:tid, :sn, :rt, :act)
            ")->execute(['tid' => $tenantId, 'sn' => $senderName, 'rt' => $replyToEmail ?: null, 'act' => $isActive]);
        }

        echo json_encode(['success' => true, 'message' => 'Email notification settings saved successfully.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ── DB auto-migration: create table and add columns ───────────
function self_migrate(PDO $db): void {
    // 1. Create table if missing
    $db->exec("CREATE TABLE IF NOT EXISTS tenant_email_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        sender_name VARCHAR(255) NULL,
        reply_to_email VARCHAR(255) NULL,
        from_name VARCHAR(255) NULL,
        from_email VARCHAR(255) NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Backwards compatibility: add columns if table existed but was old
    $columns = [
        'sender_name'    => 'ALTER TABLE tenant_email_settings ADD COLUMN sender_name VARCHAR(255) NULL AFTER from_name',
        'reply_to_email' => 'ALTER TABLE tenant_email_settings ADD COLUMN reply_to_email VARCHAR(255) NULL AFTER sender_name'
    ];

    foreach ($columns as $col => $sql) {
        try {
            $db->query("SELECT $col FROM tenant_email_settings LIMIT 1");
        } catch (\Throwable $e) {
            try { $db->exec($sql); } catch (\Throwable $ex) {}
        }
    }
}

// ── HTML for test email ───────────────────────────────────────
function buildTestHtml(string $inst, string $email, string $pass, string $loginUrl): string {
    $i = htmlspecialchars($inst); $e = htmlspecialchars($email); $p = htmlspecialchars($pass); $u = htmlspecialchars($loginUrl);
    return "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>
        body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6fb;margin:0}
        .wrap{max-width:540px;margin:30px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}
        .hdr{background:linear-gradient(135deg,#4F46E5,#6366F1);padding:28px;text-align:center;color:#fff}
        .hdr h1{margin:0;font-size:20px} .hdr p{margin:5px 0 0;opacity:.8;font-size:12px}
        .body{padding:28px} .body p{color:#374151;font-size:13px;line-height:1.6}
        .box{background:#F8FAFF;border:1.5px solid #C7D2FE;border-radius:10px;padding:16px 20px;margin:16px 0}
        .row{display:flex;padding:7px 0;border-bottom:1px solid #E8EDFB;font-size:12px}
        .row:last-child{border:none} .lbl{color:#6B7280;width:100px;font-weight:600}
        .val{color:#111827;font-weight:700} .badge{background:#FEF3C7;color:#92400E;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;display:inline-block;margin-bottom:12px}
        .foot{background:#F9FAFB;padding:14px;text-align:center;font-size:11px;color:#9CA3AF}
    </style></head>
    <body><div class='wrap'>
        <div class='hdr'><h1>🎓 {$i}</h1><p>Student Account Created</p></div>
        <div class='body'>
            <div class='badge'>⚑ THIS IS A TEST EMAIL</div>
            <p>This is a preview of the welcome email your students will receive when they are registered.</p>
            <div class='box'>
                <div class='row'><span class='lbl'>🌐 Login URL</span><span class='val'><a href='{$u}' style='color:#4F46E5'>{$u}</a></span></div>
                <div class='row'><span class='lbl'>📧 Email</span><span class='val'>{$e}</span></div>
                <div class='row'><span class='lbl'>🔑 Password</span><span class='val'>{$p}</span></div>
            </div>
            <p style='font-size:12px;color:#64748b;'>In a real email, the password will be the student's actual login password.</p>
            <p>Best Regards,<br><strong>{$i}</strong></p>
        </div>
        <div class='foot'>Sent by Hamro ERP &mdash; Student Notification System</div>
    </div></body></html>";
}
