<?php
/**
 * Student Model
 * Full Nepali admission form data
 */

namespace App\Models;

class Student {
    protected $table = 'students';
    protected $primaryKey = 'id';
    protected $db;
    
    public function __construct() {
        if (class_exists('\Illuminate\Support\Facades\DB') && \Illuminate\Support\Facades\DB::getFacadeRoot()) {
            $this->db = \Illuminate\Support\Facades\DB::connection()->getPdo();
        } elseif (function_exists('getDBConnection')) {
            $this->db = getDBConnection();
        }
    }
    
    /**
     * Get all students
     */
    public function all() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Find student by ID
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($student) {
            $student['citizenship_no'] = \App\Helpers\EncryptionHelper::decrypt($student['citizenship_no']);
            return $student;
        }
        return null;
    }
    
    /**
     * Find student by roll number
     */
    public function findByRollNo($rollNo, $tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE roll_no = ? AND tenant_id = ?");
        $stmt->execute([$rollNo, $tenantId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result : null;
    }
    
    /**
     * Get students by batch
     */
    public function getByBatch($batchId, $tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE batch_id = ? AND tenant_id = ? AND status = 'active' ORDER BY roll_no");
        $stmt->execute([$batchId, $tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get students by tenant
     */
    public function getByTenant($tenantId, $status = null) {
        if ($status) {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE tenant_id = ? AND status = ? ORDER BY created_at DESC");
            $stmt->execute([$tenantId, $status]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE tenant_id = ? ORDER BY created_at DESC");
            $stmt->execute([$tenantId]);
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * ISSUE-U4 FIX: Normalize a value to a clean JSON string.
     * If the value is already a JSON string, it decodes then re-encodes (prevents double-encoding).
     * If it's an array, it encodes directly.
     * If empty/null, returns '{}' or '[]' depending on $emptyFallback.
     */
    private function normalizeJson($val, string $emptyFallback = '{}'): string
    {
        if (empty($val)) return $emptyFallback;
        if (is_array($val)) return json_encode($val);
        // Already a JSON string — decode and re-encode to ensure validity
        $decoded = json_decode($val, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded);
        }
        // Not valid JSON — wrap as plain string value
        return json_encode(['address' => (string)$val]);
    }

    /**
     * Create new student
     */
    public function create($data) {
        $encryptedCitizenship = \App\Helpers\EncryptionHelper::encrypt($data['citizenship_no'] ?? null);
        
        $query = "INSERT INTO {$this->table} 
                  (tenant_id, user_id, batch_id, roll_no, full_name, dob_ad, dob_bs, gender, blood_group, 
                   citizenship_no, father_name, mother_name, husband_name, permanent_address, temporary_address, 
                   academic_qualifications, photo_url, status, admission_date) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                  
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['tenant_id'],
            $data['user_id'] ?? null,
            $data['batch_id'] ?? null,
            $data['roll_no'],
            $data['full_name'],
            $data['dob_ad'] ?? null,
            // ISSUE-U3 FIX: Normalize empty dob_bs string to NULL
            (isset($data['dob_bs']) && $data['dob_bs'] !== '') ? $data['dob_bs'] : null,
            $data['gender'] ?? 'male',
            $data['blood_group'] ?? null,
            $encryptedCitizenship,
            $data['father_name'] ?? null,
            $data['mother_name'] ?? null,
            $data['husband_name'] ?? null,
            // ISSUE-U4 FIX: normalizeJson prevents double-encoding when value arrives as a JSON string
            $this->normalizeJson($data['permanent_address'] ?? null),
            $this->normalizeJson($data['temporary_address'] ?? null),
            $this->normalizeJson($data['academic_qualifications'] ?? null, '[]'),
            $data['photo_url'] ?? null,
            $data['status'] ?? 'active',
            $data['admission_date'] ?? date('Y-m-d')
        ]);
        
        $studentId = $this->db->lastInsertId();
        
        // Log student creation
        if (class_exists('\App\Helpers\AuditLogger')) {
            $student = $this->find($studentId);
            \App\Helpers\AuditLogger::log('CREATE', $this->table, $studentId, null, $student);
        }
        
        return $this->find($studentId);
    }
    
    /**
     * Update student
     */
    public function update($id, $data) {
        $oldStudent = $this->find($id);
        
        // Handle citizenship encryption
        if (isset($data['citizenship_no'])) {
            $data['citizenship_no'] = \App\Helpers\EncryptionHelper::encrypt($data['citizenship_no']);
        }
        
        // Handle JSON encodings — use normalizeJson to avoid double-encoding existing JSON strings
        if (isset($data['permanent_address'])) {
            $data['permanent_address'] = $this->normalizeJson($data['permanent_address']);
        }
        if (isset($data['temporary_address'])) {
            $data['temporary_address'] = $this->normalizeJson($data['temporary_address']);
        }
        if (isset($data['academic_qualifications'])) {
            $data['academic_qualifications'] = $this->normalizeJson($data['academic_qualifications'], '[]');
        }
        // ISSUE-U3 FIX: Normalize empty dob_bs to NULL
        if (isset($data['dob_bs']) && $data['dob_bs'] === '') {
            $data['dob_bs'] = null;
        }
        
        $fields = [];
        $values = [];
        foreach($data as $key => $val) {
            $fields[] = "$key = ?";
            $values[] = $val;
        }
        $values[] = $id;
        
        $query = "UPDATE {$this->table} SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute($values);
        
        $newStudent = $this->find($id);
        
        // Log update
        if (class_exists('\App\Helpers\AuditLogger')) {
            \App\Helpers\AuditLogger::log('UPDATE', $this->table, $id, $oldStudent, $newStudent);
        }
        
        return $newStudent;
    }
    
    /**
     * Delete student (Soft delete logic)
     */
    public function delete($id) {
        $oldStudent = $this->find($id);
        
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'dropped', deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        // Log deletion
        if ($result && class_exists('\App\Helpers\AuditLogger')) {
            \App\Helpers\AuditLogger::log('DELETE', $this->table, $id, $oldStudent, null);
        }
        
        return $result;
    }
    
    /**
     * Search students
     */
    public function search($term, $tenantId) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE tenant_id = ? AND (full_name LIKE ? OR roll_no LIKE ?)
                  ORDER BY full_name LIMIT 20";
        $stmt = $this->db->prepare($query);
        $searchArg = "%{$term}%";
        $stmt->execute([$tenantId, $searchArg, $searchArg]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Generate next roll no
     */
    public function generateRollNo($tenantId) {
        $stmt = $this->db->prepare("SELECT MAX(id) as max_id FROM {$this->table}");
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return 'STD-' . str_pad((int)$row['max_id'] + 1, 4, '0', STR_PAD_LEFT);
    }
}
