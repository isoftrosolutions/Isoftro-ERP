<?php
namespace App\Models;

class Attendance {
    protected $table = 'attendance';
    private $db;
    
    public function __construct() {
        $this->db = \DB::connection()->getPdo();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (tenant_id, student_id, batch_id, course_id, attendance_date, status, marked_by, locked, created_at, updated_at)
            VALUES
            (:tenant_id, :student_id, :batch_id, :course_id, :attendance_date, :status, :marked_by, :locked, NOW(), NOW())
        ");
        
        $stmt->execute([
            'tenant_id' => $data['tenant_id'],
            'student_id' => $data['student_id'],
            'batch_id' => $data['batch_id'],
            'course_id' => $data['course_id'],
            'attendance_date' => $data['attendance_date'],
            'status' => $data['status'] ?? 'present',
            'marked_by' => $data['marked_by'] ?? null,
            'locked' => $data['locked'] ?? 0
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
    
    public function getByBatch($batchId, $date, $tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE batch_id = :batch_id AND attendance_date = :date AND tenant_id = :tenant_id");
        $stmt->execute(['batch_id' => $batchId, 'date' => $date, 'tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }
    
    public function getByStudent($studentId, $dateRange, $tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE student_id = :student_id AND attendance_date BETWEEN :start_date AND :end_date AND tenant_id = :tenant_id ORDER BY attendance_date ASC");
        $stmt->execute([
            'student_id' => $studentId, 
            'start_date' => $dateRange['start'], 
            'end_date' => $dateRange['end'], 
            'tenant_id' => $tenantId
        ]);
        return $stmt->fetchAll();
    }
    
    public function getByTenant($tenantId, $filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id";
        $params = ['tenant_id' => $tenantId];
        
        if (!empty($filters['date'])) {
            $sql .= " AND attendance_date = :date";
            $params['date'] = $filters['date'];
        }
        if (!empty($filters['batch_id'])) {
            $sql .= " AND batch_id = :batch_id";
            $params['batch_id'] = $filters['batch_id'];
        }
        
        $sql .= " ORDER BY attendance_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function bulkUpsert($records, $tenantId) {
        foreach($records as $rec) {
            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE student_id = :sid AND batch_id = :bid AND attendance_date = :date AND tenant_id = :tid");
            $stmt->execute(['sid' => $rec['student_id'], 'bid' => $rec['batch_id'], 'date' => $rec['attendance_date'], 'tid' => $tenantId]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                $this->update($exists['id'], ['status' => $rec['status'], 'marked_by' => $rec['marked_by'] ?? null]);
            } else {
                $rec['tenant_id'] = $tenantId;
                $this->create($rec);
            }
        }
        return true;
    }

    public function markLocked($ids, $locked = true) {
        if(empty($ids)) return false;
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("UPDATE {$this->table} SET locked = ?, updated_at = NOW() WHERE id IN ($inQuery)");
        
        $params = array_merge([$locked ? 1 : 0], $ids);
        return $stmt->execute($params);
    }
    
    public function getStudentStats($studentId, $batchId, $tenantId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days
            FROM {$this->table} 
            WHERE student_id = :student_id AND batch_id = :batch_id AND tenant_id = :tenant_id
        ");
        $stmt->execute(['student_id' => $studentId, 'batch_id' => $batchId, 'tenant_id' => $tenantId]);
        return $stmt->fetch();
    }

    public function getBatchStudentsStats($batchId, $tenantId) {
        $stmt = $this->db->prepare("
            SELECT 
                student_id,
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days
            FROM {$this->table} 
            WHERE batch_id = :batch_id AND tenant_id = :tenant_id
            GROUP BY student_id
        ");
        $stmt->execute(['batch_id' => $batchId, 'tenant_id' => $tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function getBatchStats($batchId, $dateRange, $tenantId) {
        $stmt = $this->db->prepare("
            SELECT 
                attendance_date,
                COUNT(*) as total_students,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
            FROM {$this->table} 
            WHERE batch_id = :batch_id AND tenant_id = :tenant_id 
              AND attendance_date BETWEEN :start_date AND :end_date
            GROUP BY attendance_date
            ORDER BY attendance_date ASC
        ");
        $stmt->execute([
            'batch_id' => $batchId,
            'tenant_id' => $tenantId,
            'start_date' => $dateRange['start'],
            'end_date' => $dateRange['end']
        ]);
        return $stmt->fetchAll();
    }
    
    public function getTodayStats($tenantId) {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_marked,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_count
            FROM {$this->table} 
            WHERE tenant_id = :tenant_id AND attendance_date = :today
        ");
        $stmt->execute(['tenant_id' => $tenantId, 'today' => $today]);
        return $stmt->fetch();
    }
}
