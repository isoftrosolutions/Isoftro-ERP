<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;
use App\Helpers\AuditLogger;

class PaymentTransaction extends Model {
    use TenantScoped;

    protected $table = 'payment_transactions';
    protected $fillable = [
        'tenant_id', 'student_id', 'fee_record_id', 'invoice_id', 'amount', 'payment_method', 
        'transaction_id', 'receipt_number', 'receipt_path', 'payment_date', 'recorded_by', 'notes', 'status'
    ];
    
    public function student() {
        return $this->belongsTo(Student::class, 'student_id');
    }
    
    public function feeRecord() {
        return $this->belongsTo(FeeRecord::class, 'fee_record_id');
    }

    /**
     * Get student transactions
     */
    public static function getByStudent($studentId) {
        return self::with(['student', 'feeRecord.feeItem'])
            ->where('student_id', $studentId)
            ->orderBy('payment_date', 'DESC')
            ->get();
    }
    
    /**
     * Boot the model to handle audit logging on create
     */
    protected static function boot() {
        parent::boot();
        
        static::created(function($model) {
            AuditLogger::log('TRANSACTION_CREATED', null, null, [
                'record_id' => $model->id,
                'data' => $model->toArray()
            ]);
        });
    }
}
