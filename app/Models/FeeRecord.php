<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;
use App\Helpers\AuditLogger;

class FeeRecord extends Model {
    use TenantScoped;

    protected $table = 'fee_records';
    protected $fillable = [
        'tenant_id', 'student_id', 'batch_id', 'fee_item_id', 'installment_no', 'amount_due', 
        'amount_paid', 'due_date', 'paid_date', 'receipt_no', 'receipt_path', 'payment_mode', 
        'cashier_user_id', 'fine_applied', 'fine_waived', 'status', 'academic_year'
    ];
    
    /**
     * Get fee records for a student
     */
    public static function getByStudent($studentId) {
        return self::with('feeItem')
            ->where('student_id', $studentId)
            ->orderBy('due_date', 'ASC')
            ->get();
    }
    
    /**
     * Relationship with FeeItem
     */
    public function feeItem() {
        return $this->belongsTo(FeeItem::class, 'fee_item_id');
    }

    /**
     * Record payment info
     */
    public function recordPayment($data) {
        $oldValues = $this->toArray();
        $amountPaid = floatval($data['amount_paid']);
        
        $this->update([
            'amount_paid' => $this->amount_paid + $amountPaid,
            'paid_date' => $data['paid_date'] ?? date('Y-m-d'),
            'receipt_no' => $data['receipt_no'],
            'receipt_path' => $data['receipt_path'] ?? null,
            'payment_mode' => $data['payment_mode'],
            'cashier_user_id' => $data['cashier_user_id'],
            'fine_applied' => $this->fine_applied + floatval($data['fine_applied'] ?? 0),
            'status' => $data['status'] ?? 'paid'
        ]);
        
        AuditLogger::log('PAYMENT_RECORDED', null, null, [
            'record_id' => $this->id,
            'old' => $oldValues,
            'new' => $this->toArray()
        ]);
        
        return true;
    }

    /**
     * Get student total outstanding balance
     */
    public static function getStudentBalance($studentId) {
        $result = self::where('student_id', $studentId)
            ->where('status', '!=', 'cancelled')
            ->selectRaw("
                SUM(amount_due) as total_due,
                SUM(amount_paid) as total_paid,
                SUM(amount_due + fine_applied - amount_paid - fine_waived) as balance 
            ")->first();
            
        return [
            'total_due' => (float)($result->total_due ?? 0.00),
            'total_paid' => (float)($result->total_paid ?? 0.00),
            'balance' => (float)($result->balance ?? 0.00)
        ];
    }
}
