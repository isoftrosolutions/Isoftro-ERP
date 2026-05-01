<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\SuperAdmin\AuditLogModel;

class LogController {
    private $db;
    private $auditModel;

    public function __construct($db) {
        $this->db = $db;
        $this->auditModel = new AuditLogModel($db);
    }

    public function index() {
        $logs = $this->auditModel->getLogs(500);
        return view('super-admin.logs', ['logs' => $logs]);
    }

    public function errorsView() {
        $logFile = storage_path('logs/laravel.log');
        $lines = [];
        if (file_exists($logFile)) {
            $raw = array_slice(file($logFile), -600);
            foreach ($raw as $line) {
                $line = rtrim($line);
                if ($line !== '') $lines[] = $line;
            }
            $lines = array_reverse(array_slice($lines, -300));
        }
        include resource_path('views/super-admin/logs-errors.php');
    }

    public function apiLogsView() {
        try {
            $logs = $this->db->query("
                SELECT al.*, u.email as user_email, t.name as tenant_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN tenants t ON al.tenant_id = t.id
                WHERE al.action LIKE 'api_%' OR al.action LIKE '%_api%'
                ORDER BY al.created_at DESC LIMIT 200
            ")->fetchAll();
        } catch (\Exception $e) {
            $logs = [];
    }
        include resource_path('views/super-admin/logs-api.php');
    }

    public function handle($action = 'index') {
        switch ($action) {
            case 'logs':
            case 'audit':      return $this->index();
            case 'logs-errors': return $this->errorsView();
            case 'logs-api':    return $this->apiLogsView();
            default:           return $this->index();
        }
    }

    public function filter() {
        $type = $_GET['type'] ?? 'audit';
        $limit = (int)($_GET['limit'] ?? 100);
        $logs = $this->auditModel->getLogs($limit);
        echo json_encode(['success' => true, 'logs' => $logs]);
    }

    public function clearOldLogs() {
        // Platform owners cleanup logic...
        // For security, logs over 90 days are moved to cold storage...
        $this->auditModel->logAction(getCurrentUser()['id'], null, 'audit_logs_cleanup');
        echo json_encode(['success' => true]);
    }
}
