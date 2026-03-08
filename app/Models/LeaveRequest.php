<?php
namespace App\Models;

class LeaveRequest {
    protected $table = 'leave_requests';
    private $db;
    
    public function __construct() {
        $this->db = \DB::connection()->getPdo();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (tenant_id, student_id, from_date, to_date, reason, status, created_at, updated_at)
            VALUES
            (:tenant_id, :student_id, :from_date, :to_date, :reason, :status, NOW(), NOW())
        ");
        $stmt->execute([
            'tenant_id' => $data['tenant_id'],
            'student_id' => $data['student_id'],
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'reason' => $data['reason'],
            'status' => $data['status'] ?? 'pending'
        ]);
        return $this->find($this->db->lastInsertId());
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[$key] = $value;
        }
        $fields[] = "updated_at = NOW()";
        
        $stmt = $this->db->prepare("UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id");
        $stmt->execute($params);
        return $this->find($id);
    }
    
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    public function getByStudent($studentId, $tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE student_id = :student_id AND tenant_id = :tenant_id ORDER BY created_at DESC");
        $stmt->execute(['student_id' => $studentId, 'tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }
    
    public function getPending($tenantId) {
        $stmt = $this->db->prepare("
            SELECT l.*, s.full_name, s.roll_no, s.photo_url 
            FROM {$this->table} l
            JOIN students s ON s.id = l.student_id
            WHERE l.tenant_id = :tenant_id AND l.status = 'pending' 
            ORDER BY l.created_at ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }
    
    public function getApprovedForDate($date, $tenantId) {
        $stmt = $this->db->prepare("
            SELECT l.*, s.full_name, s.roll_no, s.photo_url 
            FROM {$this->table} l
            JOIN students s ON s.id = l.student_id
            WHERE l.tenant_id = :tenant_id 
              AND l.status = 'approved' 
              AND :date BETWEEN l.from_date AND l.to_date
        ");
        $stmt->execute(['tenant_id' => $tenantId, 'date' => $date]);
        return $stmt->fetchAll();
    }
    
    public function approve($id, $approvedBy) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'approved', approved_by = :approved_by, approved_at = NOW(), updated_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id, 'approved_by' => $approvedBy]);
        return $this->find($id);
    }
    
    public function reject($id, $approvedBy) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'rejected', approved_by = :approved_by, approved_at = NOW(), updated_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id, 'approved_by' => $approvedBy]);
        return $this->find($id);
    }
}
