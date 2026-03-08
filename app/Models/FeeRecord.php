<?php
/**
 * FeeRecord Model
 */

namespace App\Models;

use App\Helpers\AuditLogger;

class FeeRecord {
    protected $table = 'fee_records';
    protected $db;
    
    public function __construct() {
        if (class_exists('\Illuminate\Support\Facades\DB') && \Illuminate\Support\Facades\DB::getFacadeRoot()) {
            $this->db = \Illuminate\Support\Facades\DB::connection()->getPdo();
        } elseif (function_exists('getDBConnection')) {
            $this->db = getDBConnection();
        }
    }
    
    /**
     * Find fee record by ID
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result : null;
    }
    
    /**
     * Get fee records for a student
     */
    public function getByStudent($studentId, $tenantId) {
        $query = "SELECT fr.*, fi.name as fee_item_name, fi.type as fee_item_type 
                  FROM {$this->table} fr
                  JOIN fee_items fi ON fr.fee_item_id = fi.id
                  WHERE fr.student_id = ? AND fr.tenant_id = ?
                  ORDER BY fr.due_date ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$studentId, $tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get fee records by batch and month
     */
    public function getByBatchAndMonth($batchId, $month, $year, $tenantId) {
        $query = "SELECT fr.*, s.full_name, s.roll_no 
                  FROM {$this->table} fr
                  JOIN students s ON fr.student_id = s.id
                  WHERE fr.batch_id = ? AND fr.tenant_id = ? 
                  AND MONTH(fr.due_date) = ? AND YEAR(fr.due_date) = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$batchId, $tenantId, $month, $year]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Create multiple fee records (Bulk insert for installments)
     */
    public function bulkCreate($dataArray) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO {$this->table} (tenant_id, student_id, batch_id, fee_item_id, installment_no, amount_due, due_date, status, academic_year)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            foreach ($dataArray as $data) {
                $stmt->execute([
                    $data['tenant_id'],
                    $data['student_id'],
                    $data['batch_id'] ?? null,
                    $data['fee_item_id'],
                    $data['installment_no'],
                    $data['amount_due'],
                    $data['due_date'],
                    $data['status'] ?? 'pending',
                    $data['academic_year'] ?? null
                ]);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Update payment info
     */
    public function recordPayment($id, $data) {
        $oldValues = $this->find($id);
        $oldPaid = $oldValues['amount_paid'] ?? 0;
        $amountPaid = floatval($data['amount_paid']);
        
        $query = "UPDATE {$this->table} SET 
                  amount_paid = amount_paid + ?, 
                  paid_date = ?, 
                  receipt_no = ?, 
                  receipt_path = ?, 
                  payment_mode = ?, 
                  cashier_user_id = ?, 
                  fine_applied = fine_applied + ?, 
                  status = ?, 
                  updated_at = CURRENT_TIMESTAMP 
                  WHERE id = ?";
                  
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $amountPaid,
            $data['paid_date'] ?? date('Y-m-d'),
            $data['receipt_no'],
            $data['receipt_path'] ?? null,
            $data['payment_mode'],
            $data['cashier_user_id'],
            floatval($data['fine_applied'] ?? 0),
            $data['status'] ?? 'paid',
            $id
        ]);
        
        // Log the payment record update with in-memory data
        if (class_exists('\App\Helpers\AuditLogger')) {
            $newValues = $oldValues;
            $newValues['amount_paid'] += $amountPaid;
            \App\Helpers\AuditLogger::log('PAYMENT_RECORDED', $this->table, $id, $oldValues, $newValues);
        }
        
        return true;
    }

    /**
     * Get student total outstanding balance
     */
    public function getStudentBalance($studentId, $tenantId) {
        $query = "SELECT 
                    SUM(amount_due) as total_due,
                    SUM(amount_paid) as total_paid,
                    SUM(amount_due + fine_applied - amount_paid - fine_waived) as balance 
                  FROM {$this->table} 
                  WHERE student_id = ? AND tenant_id = ? AND status != 'cancelled'";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$studentId, $tenantId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
        return [
            'total_due' => (float)($result['total_due'] ?? 0.00),
            'total_paid' => (float)($result['total_paid'] ?? 0.00),
            'balance' => (float)($result['balance'] ?? 0.00)
        ];
    }
}
