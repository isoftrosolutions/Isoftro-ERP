<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class Attendance extends Model {
    use TenantScoped;

    protected $table = 'attendance';
    protected $fillable = [
        'tenant_id', 'student_id', 'batch_id', 'course_id', 'attendance_date', 'status', 'marked_by', 'locked'
    ];

    public static function getByBatch($batchId, $date) {
        return self::where('batch_id', $batchId)->where('attendance_date', $date)->get();
    }
    
    public static function getByStudent($studentId, $dateRange) {
        return self::where('student_id', $studentId)
            ->whereBetween('attendance_date', [$dateRange['start'], $dateRange['end']])
            ->orderBy('attendance_date', 'ASC')
            ->get();
    }
    
    public static function getByFilters($filters = []) {
        $query = self::query();
        if (!empty($filters['date'])) $query->where('attendance_date', $filters['date']);
        if (!empty($filters['batch_id'])) $query->where('batch_id', $filters['batch_id']);
        return $query->orderBy('attendance_date', 'DESC')->get();
    }

    public static function bulkUpsert($records) {
        foreach($records as $rec) {
            self::updateOrCreate(
                [
                    'student_id' => $rec['student_id'], 
                    'batch_id' => $rec['batch_id'], 
                    'attendance_date' => $rec['attendance_date']
                ],
                [
                    'status' => $rec['status'], 
                    'marked_by' => $rec['marked_by'] ?? null
                ]
            );
        }
        return true;
    }

    public static function markLocked($ids, $locked = true) {
        return self::whereIn('id', $ids)->update(['locked' => $locked ? 1 : 0]);
    }
    
    public static function getStudentStats($studentId, $batchId) {
        return self::where('student_id', $studentId)
            ->where('batch_id', $batchId)
            ->selectRaw("
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days
            ")->first();
    }

    public static function getBatchStudentsStats($batchId) {
        return self::where('batch_id', $batchId)
            ->groupBy('student_id')
            ->selectRaw("
                student_id,
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days
            ")->get();
    }
    
    public static function getBatchStats($batchId, $dateRange) {
        return self::where('batch_id', $batchId)
            ->whereBetween('attendance_date', [$dateRange['start'], $dateRange['end']])
            ->groupBy('attendance_date')
            ->selectRaw("
                attendance_date,
                COUNT(*) as total_students,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
            ")
            ->orderBy('attendance_date', 'ASC')
            ->get();
    }
    
    public static function getTodayStats() {
        return self::where('attendance_date', date('Y-m-d'))
            ->selectRaw("
                COUNT(*) as total_marked,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_count
            ")->first();
    }
}
