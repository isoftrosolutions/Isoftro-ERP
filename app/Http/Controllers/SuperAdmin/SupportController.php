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

    public function handle($action = 'index') {
        switch ($action) {
            case 'support':
            case 'open':  return $this->index();
            default:      return $this->index();
        }
    }
}
