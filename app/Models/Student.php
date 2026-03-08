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
            $citizenship = $student['citizenship_no'];
            if (class_exists('EncryptionHelper')) {
                $student['citizenship_no'] = EncryptionHelper::decrypt($citizenship);
            } elseif (class_exists('\App\Helpers\EncryptionHelper')) {
                $student['citizenship_no'] = \App\Helpers\EncryptionHelper::decrypt($citizenship);
            }
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
        $citizenship = $data['citizenship_no'] ?? null;
        $encryptedCitizenship = $citizenship;
        if (class_exists('EncryptionHelper')) {
            $encryptedCitizenship = EncryptionHelper::encrypt($citizenship);
        } elseif (class_exists('\App\Helpers\EncryptionHelper')) {
            $encryptedCitizenship = \App\Helpers\EncryptionHelper::encrypt($citizenship);
        }
        
        $query = "INSERT INTO {$this->table} 
                  (tenant_id, user_id, batch_id, roll_no, full_name, dob_ad, dob_bs, gender, blood_group, 
                   phone, email, citizenship_no, national_id, father_name, mother_name, husband_name, 
                   guardian_name, guardian_relation,
                   permanent_address, temporary_address, academic_qualifications, 
                   admission_date, photo_url, identity_doc_url, status, 
                   registration_mode, registration_status, id_card_status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                  
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
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $encryptedCitizenship,
            $data['national_id'] ?? null,
            $data['father_name'] ?? null,
            $data['mother_name'] ?? null,
            $data['husband_name'] ?? null,
            $data['guardian_name'] ?? null,
            $data['guardian_relation'] ?? null,
            // ISSUE-U4 FIX: normalizeJson prevents double-encoding when value arrives as a JSON string
            $this->normalizeJson($data['permanent_address'] ?? null),
            $this->normalizeJson($data['temporary_address'] ?? null),
            $this->normalizeJson($data['academic_qualifications'] ?? null, '[]'),
            $data['admission_date'] ?? date('Y-m-d'),
            $data['photo_url'] ?? null,
            $data['identity_doc_url'] ?? null,
            $data['status'] ?? 'active',
            $data['registration_mode'] ?? 'full',
            $data['registration_status'] ?? 'fully_registered',
            $data['id_card_status'] ?? 'none'
        ]);
        
        $studentId = $this->db->lastInsertId();
        
        // MARIADB 12 FIX: Do NOT read newly inserted row within the same transaction.
        // Build result array from input data instead, to avoid "Record has changed since last read" error.
        // Audit logging should be done post-commit by the caller (StudentService).
        $result = $data;
        $result['id'] = (int)$studentId;
        $result['citizenship_no'] = $citizenship; // Return unencrypted value
        $result['roll_no'] = $data['roll_no'];
        $result['created_at'] = date('Y-m-d H:i:s');
        $result['updated_at'] = date('Y-m-d H:i:s');
        
        if (class_exists('\App\Services\StudentCacheService')) {
            try {
                (new \App\Services\StudentCacheService())->invalidate((int)$studentId, $data['tenant_id']);
                if (class_exists('\App\Services\DashboardCacheService')) {
                    (new \App\Services\DashboardCacheService(new \App\Services\CacheManager()))->invalidate($data['tenant_id']);
                }
            } catch (\Exception $e) {
                // Ignore cache failures
            }
        }
        
        return $result;
    }
    
    /**
     * Update student
     */
    public function update($id, $data) {
        $oldStudent = $this->find($id);
        
        // Handle citizenship encryption
        if (isset($data['citizenship_no'])) {
            $citizenship = $data['citizenship_no'];
            if (class_exists('EncryptionHelper')) {
                $data['citizenship_no'] = EncryptionHelper::encrypt($citizenship);
            } elseif (class_exists('\App\Helpers\EncryptionHelper')) {
                $data['citizenship_no'] = \App\Helpers\EncryptionHelper::encrypt($citizenship);
            }
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
        
        if (class_exists('\App\Services\StudentCacheService') && $newStudent) {
            try {
                (new \App\Services\StudentCacheService())->invalidate($id, $newStudent['tenant_id']);
                if (isset($data['batch_id']) || isset($data['status'])) {
                    if (class_exists('\App\Services\DashboardCacheService')) {
                        (new \App\Services\DashboardCacheService(new \App\Services\CacheManager()))->invalidate($newStudent['tenant_id']);
                    }
                }
            } catch (\Exception $e) {
                // Ignore cache failures
            }
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
        
        if ($result && $oldStudent && class_exists('\App\Services\StudentCacheService')) {
            try {
                (new \App\Services\StudentCacheService())->invalidate($id, $oldStudent['tenant_id']);
                if (class_exists('\App\Services\DashboardCacheService')) {
                    (new \App\Services\DashboardCacheService(new \App\Services\CacheManager()))->invalidate($oldStudent['tenant_id']);
                }
            } catch (\Exception $e) {
                // Ignore cache failures
            }
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
        $nextId = (int)$row['max_id'] + 1;
        
        // Get Current Year (AD / BS based on settings or system)
        $year = date('Y');
        if (class_exists('DateUtils')) {
            try { $year = DateUtils::getCurrentYear(); } catch (\Throwable $e) {}
        } elseif (class_exists('\App\Helpers\DateUtils')) {
            try { $year = \App\Helpers\DateUtils::getCurrentYear(); } catch (\Throwable $e) {}
        }

        return "STD-{$year}-" . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
