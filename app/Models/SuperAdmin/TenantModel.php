<?php

namespace App\Models\SuperAdmin;

use PDO;

class TenantModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll($filters = []) {
        $sql = "SELECT t.*, 
                (SELECT COUNT(*) FROM students s WHERE s.tenant_id = t.id) as student_count,
                (SELECT status FROM subscriptions WHERE tenant_id = t.id ORDER BY end_date DESC LIMIT 1) as sub_status
                FROM tenants t WHERE 1=1";
        
        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['plan'])) {
            $sql .= " AND t.plan = :plan";
            $params['plan'] = $filters['plan'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (t.name LIKE :search OR t.subdomain LIKE :search2)";
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY t.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM tenants WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO tenants (name, subdomain, plan, status, student_limit, sms_credits) VALUES (:name, :subdomain, :plan, :status, :student_limit, :sms_credits)");
        return $stmt->execute($data);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE tenants SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function assignPlan($id, $plan) {
        $stmt = $this->db->prepare("UPDATE tenants SET plan = ? WHERE id = ?");
        return $stmt->execute([$plan, $id]);
    }
    public function getFeatures($tenantId) {
        $stmt = $this->db->prepare("SELECT feature_id FROM institute_feature_access WHERE tenant_id = ? AND is_enabled = 1");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

}
