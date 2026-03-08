<?php
namespace App\Models;

class AttendanceSettings {
    protected $table = 'attendance_settings';
    private $db;
    
    public function __construct() {
        $this->db = \DB::connection()->getPdo();
    }
    
    public function getByTenant($tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE tenant_id = :tid LIMIT 1");
        $stmt->execute(['tid' => $tenantId]);
        $settings = $stmt->fetch();
        
        if (!$settings) {
            return $this->createDefaults($tenantId);
        }
        return $settings;
    }
    
    public function update($tenantId, $data) {
        // Ensure settings exist first
        $this->getByTenant($tenantId);
        
        $fields = [];
        $params = ['tid' => $tenantId];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[$key] = $value;
        }
        $fields[] = "updated_at = NOW()";
        
        $stmt = $this->db->prepare("UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE tenant_id = :tid");
        $stmt->execute($params);
        
        return $this->getByTenant($tenantId);
    }
    
    public function createDefaults($tenantId) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} 
            (tenant_id, lock_period_hours, exclude_leave_from_total, allow_frontdesk_edit, created_at, updated_at) 
            VALUES (:tid, 24, 1, 0, NOW(), NOW())
        ");
        $stmt->execute(['tid' => $tenantId]);
        
        // Fetch the newly created settings
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE tenant_id = :tid LIMIT 1");
        $stmt->execute(['tid' => $tenantId]);
        return $stmt->fetch();
    }
}
