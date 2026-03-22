<?php

namespace App\Models\SuperAdmin;

class AuditLogModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function logAction($userId, $tenantId, $action, $metadata = []) {
        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (user_id, tenant_id, action, metadata, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $userId, 
            $tenantId, 
            $action, 
            json_encode($metadata), 
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    }

    public function getLogs($limit = 100) {
        $stmt = $this->db->prepare("
            SELECT al.*, u.email as user_email, t.name as tenant_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN tenants t ON al.tenant_id = t.id
            ORDER BY al.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getLogsByTenant($tenantId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT al.*, u.email as user_email
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.tenant_id = :tid
            ORDER BY al.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':tid', $tenantId);
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
