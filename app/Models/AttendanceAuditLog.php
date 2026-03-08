<?php
namespace App\Models;

class AttendanceAuditLog {
    protected $table = 'attendance_audit_logs';
    private $db;
    
    public function __construct() {
        $this->db = \DB::connection()->getPdo();
    }
    
    public function log($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} 
            (tenant_id, attendance_id, user_id, action, old_values, new_values, created_at)
            VALUES (:tid, :aid, :uid, :action, :old, :new, NOW())
        ");
        
        $stmt->execute([
            'tid' => $data['tenant_id'],
            'aid' => $data['attendance_id'],
            'uid' => $data['user_id'],
            'action' => $data['action'],
            'old' => isset($data['old_values']) ? json_encode($data['old_values']) : null,
            'new' => isset($data['new_values']) ? json_encode($data['new_values']) : null
        ]);
        return $this->db->lastInsertId();
    }
    
    public function getByAttendance($attendanceId, $tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE attendance_id = :aid AND tenant_id = :tid ORDER BY created_at DESC");
        $stmt->execute(['aid' => $attendanceId, 'tid' => $tenantId]);
        return $stmt->fetchAll();
    }
    
    public function getByTenant($tenantId, $dateRange = null) {
        $sql = "SELECT * FROM {$this->table} WHERE tenant_id = :tid";
        $params = ['tid' => $tenantId];
        
        if ($dateRange) {
            $sql .= " AND created_at BETWEEN :start AND :end";
            $params['start'] = $dateRange['start'];
            $params['end'] = $dateRange['end'];
        }
        
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
