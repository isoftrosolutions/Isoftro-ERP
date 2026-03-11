<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class AttendanceAuditLog extends Model {
    use TenantScoped;

    protected $table = 'attendance_audit_logs';
    protected $fillable = [
        'tenant_id', 'attendance_id', 'user_id', 'action', 'old_values', 'new_values'
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json'
    ];

    public static function logEvent($data) {
        return self::create([
            'attendance_id' => $data['attendance_id'],
            'user_id' => $data['user_id'],
            'action' => $data['action'],
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null
        ]);
    }
    
    public static function getByAttendance($attendanceId) {
        return self::where('attendance_id', $attendanceId)->orderBy('created_at', 'DESC')->get();
    }
    
    public static function getByDateRange($dateRange = null) {
        $query = self::query();
        if ($dateRange) {
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }
        return $query->orderBy('created_at', 'DESC')->get();
    }
}
