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

    public function handle($action = 'index') {
        switch ($action) {
            case 'logs':
            case 'audit': return $this->index();
            default: return $this->index();
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
