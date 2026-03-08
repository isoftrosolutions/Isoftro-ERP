<?php
/**
 * PaymentReceipt Model
 */

namespace App\Models;

class PaymentReceipt {
    protected $table = 'payment_receipts';
    protected $db;
    
    public function __construct() {
        if (class_exists('\Illuminate\Support\Facades\DB') && \Illuminate\Support\Facades\DB::getFacadeRoot()) {
            $this->db = \Illuminate\Support\Facades\DB::connection()->getPdo();
        } elseif (function_exists('getDBConnection')) {
            $this->db = getDBConnection();
        }
    }
    
    public function create($data) {
        $query = "INSERT INTO {$this->table} (payment_id, pdf_path) VALUES (?, ?)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            $data['payment_id'],
            $data['pdf_path']
        ]);
    }

    public function findByPayment($paymentId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE payment_id = ?");
        $stmt->execute([$paymentId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
