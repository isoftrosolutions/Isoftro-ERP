<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class StudentInvoice extends Model {
    use TenantScoped;

    protected $table = 'student_invoices';
    protected $fillable = [
        'invoice_number', 'tenant_id', 'student_id', 'batch_id', 'academic_year', 
        'invoice_date', 'due_date', 'total_amount', 'paid_amount', 'status', 'notes'
    ];
    
    /**
     * Get student invoices
     */
    public static function getByStudent($studentId) {
        return self::where('student_id', $studentId)
            ->orderBy('invoice_date', 'DESC')
            ->get();
    }
}
