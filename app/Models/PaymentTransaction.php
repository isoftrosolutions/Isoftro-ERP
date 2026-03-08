<?php
/**
 * PaymentTransaction Model
 */

namespace App\Models;

use App\Helpers\AuditLogger;

class PaymentTransaction {
    protected $table = 'payment_transactions';
    protected $db;
    
    public function __construct() {
        if (class_exists('\Illuminate\Support\Facades\DB') && \Illuminate\Support\Facades\DB::getFacadeRoot()) {
            $this->db = \Illuminate\Support\Facades\DB::connection()->getPdo();
        } elseif (function_exists('getDBConnection')) {
            $this->db = getDBConnection();
        }
    }
    
    /**
     * Find transaction by ID
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result : null;
    }
    
    /**
     * Get student transactions
     */
    public function getByStudent($studentId, $tenantId) {
        $query = "SELECT pt.*, fi.name as fee_item_name 
                  FROM {$this->table} pt
                  LEFT JOIN fee_records fr ON pt.fee_record_id = fr.id
                  LEFT JOIN fee_items fi ON fr.fee_item_id = fi.id
                  WHERE pt.student_id = ? AND pt.tenant_id = ?
                  ORDER BY pt.payment_date DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$studentId, $tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Record new transaction
     */
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (tenant_id, student_id, fee_record_id, invoice_id, amount, payment_method, transaction_id, receipt_number, receipt_path, payment_date, recorded_by, notes, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                  
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['tenant_id'],
            $data['student_id'],
            $data['fee_record_id'] ?? null,
            $data['invoice_id'] ?? null,
            $data['amount'],
            $data['payment_method'],
            $data['transaction_id'] ?? null,
            $data['receipt_number'] ?? null,
            $data['receipt_path'] ?? null,
            $data['payment_date'] ?? date('Y-m-d'),
            $data['recorded_by'] ?? null,
            $data['notes'] ?? null,
            $data['status'] ?? 'completed'
        ]);
        
        $transactionId = $this->db->lastInsertId();
        
        // Log asynchronously or with in-memory data
        if (class_exists('\App\Helpers\AuditLogger')) {
            \App\Helpers\AuditLogger::log('TRANSACTION_CREATED', $this->table, $transactionId, null, $data);
        }

        return $transactionId;
    }
}
