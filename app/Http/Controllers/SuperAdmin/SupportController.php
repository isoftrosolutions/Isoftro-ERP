<?php

namespace App\Http\Controllers\SuperAdmin;

use PDO;

class SupportController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        try {
            $tickets = $this->db->query("
                SELECT st.*, t.name as tenant_name, u.email as user_email
                FROM support_tickets st
                LEFT JOIN tenants t ON st.tenant_id = t.id
                LEFT JOIN users u ON st.user_id = u.id
                WHERE st.status != 'resolved'
                ORDER BY 
                    CASE st.priority WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END,
                    st.created_at DESC
                LIMIT 100
            ")->fetchAll();
        } catch (\Exception $e) {
            $tickets = [];
    }

        return view('super-admin.support', ['tickets' => $tickets]);
    }

    public function resolvedView() {
        try {
            $tickets = $this->db->query("
                SELECT st.*, t.name as tenant_name, u.email as user_email
                FROM support_tickets st
                LEFT JOIN tenants t ON st.tenant_id = t.id
                LEFT JOIN users u ON st.user_id = u.id
                WHERE st.status = 'resolved'
                ORDER BY st.updated_at DESC LIMIT 100
            ")->fetchAll();
        } catch (\Exception $e) {
            $tickets = [];
    }
        include resource_path('views/super-admin/support-resolved.php');
    }

    public function impersonateLogView() {
        try {
            $logs = $this->db->query("
                SELECT al.*, u.email as admin_email, t.name as tenant_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN tenants t ON al.tenant_id = t.id
                WHERE al.action IN ('impersonate_start','impersonate_end','tenant_impersonate')
                   OR al.action LIKE '%impersonat%'
                ORDER BY al.created_at DESC LIMIT 200
            ")->fetchAll();
        } catch (\Exception $e) {
            $logs = [];
    }
        include resource_path('views/super-admin/support-impersonate.php');
    }

    public function handle($action = 'index') {
        switch ($action) {
            case 'support':
            case 'open':               return $this->index();
            case 'support-resolved':   return $this->resolvedView();
            case 'support-impersonate':return $this->impersonateLogView();
            default:                   return $this->index();
        }
    }
}
