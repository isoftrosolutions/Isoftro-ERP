<?php
namespace App\Models;

class NotificationAutomationRule {
    protected $table = 'notification_automation_rules';
    private $db;
    
    public function __construct() {
        if (function_exists('getDBConnection')) {
            $this->db = getDBConnection();
        } elseif (class_exists('\DB') && method_exists('\DB', 'connection')) {
            $this->db = \DB::connection()->getPdo();
        }
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (tenant_id, name, trigger_type, conditions, message_template, is_active, created_at, updated_at)
            VALUES
            (:tenant_id, :name, :trigger_type, :conditions, :message_template, :is_active, NOW(), NOW())
        ");
        
        $stmt->execute([
            'tenant_id' => $data['tenant_id'],
            'name' => $data['name'],
            'trigger_type' => $data['trigger_type'],
            'conditions' => isset($data['conditions']) ? json_encode($data['conditions']) : null,
            'message_template' => $data['message_template'],
            'is_active' => $data['is_active'] ?? 1
        ]);
        
        return $this->find($this->db->lastInsertId());
    }

    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        foreach ($data as $key => $value) {
            if ($key === 'conditions') {
                $fields[] = "$key = :$key";
                $params[$key] = json_encode($value);
            } else {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }
        $fields[] = "updated_at = NOW()";
        
        $stmt = $this->db->prepare("UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id");
        $stmt->execute($params);
        
        return $this->find($id);
    }
    
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        if ($result && $result['conditions']) {
            $result['conditions'] = json_decode($result['conditions'], true);
        }
        return $result;
    }
    
    public function getActiveByTenant($tenantId, $triggerType = null) {
        $sql = "SELECT * FROM {$this->table} WHERE tenant_id = :tid AND is_active = 1";
        $params = ['tid' => $tenantId];
        
        if ($triggerType) {
            $sql .= " AND trigger_type = :trigger";
            $params['trigger'] = $triggerType;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        foreach ($results as &$row) {
            if ($row['conditions']) {
                $row['conditions'] = json_decode($row['conditions'], true);
            }
        }
        return $results;
    }

    public function delete($id, $tenantId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id AND tenant_id = :tid");
        return $stmt->execute(['id' => $id, 'tid' => $tenantId]);
    }
}
