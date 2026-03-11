<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class LeaveRequest extends Model {
    use TenantScoped;

    protected $table = 'leave_requests';
    protected $fillable = [
        'tenant_id', 'student_id', 'from_date', 'to_date', 'reason', 'status', 'approved_by', 'approved_at'
    ];
    
    public function student() {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public static function getByStudent($studentId) {
        return self::where('student_id', $studentId)->orderBy('created_at', 'DESC')->get();
    }
    
    public static function getPending() {
        return self::with('student')
            ->where('status', 'pending')
            ->orderBy('created_at', 'ASC')
            ->get();
    }
    
    public static function getApprovedForDate($date) {
        return self::with('student')
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->get();
    }
    
    public function approve($approvedBy) {
        return $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now()
        ]);
    }
    
    public function reject($approvedBy) {
        return $this->update([
            'status' => 'rejected',
            'approved_by' => $approvedBy,
            'approved_at' => now()
        ]);
    }
}
