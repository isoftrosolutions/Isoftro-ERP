<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\SuperAdmin\TenantModel;
use App\Models\SuperAdmin\AuditLogModel;

class DashboardController {
    private $db;
    private $tenantModel;
    private $auditModel;

    public function __construct($db) {
        $this->db = $db;
        $this->tenantModel = new TenantModel($db);
        $this->auditModel = new AuditLogModel($db);
    }

    public function index() {
        $stats = $this->getDashboardStats();
        $recentTenants = $this->tenantModel->getAll(['limit' => 5]);
        $sysHealth = $this->getSystemHealth();

        // Render dashboard view
        include resource_path('views/super-admin/overview.php');
    }

    private function getDashboardStats() {
        return [
            'total_tenants' => $this->db->query("SELECT COUNT(*) FROM tenants")->fetchColumn(),
            'active_subscribers' => $this->db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn(),
            'mrr' => $this->db->query("SELECT SUM(amount) FROM subscriptions WHERE status = 'active' AND billing_cycle = 'monthly'")->fetchColumn() ?: 0,
            'security_alerts' => $this->db->query("SELECT COUNT(*) FROM failed_logins WHERE attempted_at > (NOW() - INTERVAL 24 HOUR)")->fetchColumn()
        ];
    }

    private function getSystemHealth() {
        // Mocking real-time system metrics - in production, these would fetch from Redis/Monitoring APIs
        return [
            'uptime' => '99.98%',
            'api_p95' => '142ms',
            'queue_depth' => 0,
            'db_status' => 'Healthy'
        ];
    }
}
