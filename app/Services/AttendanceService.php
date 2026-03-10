<?php
namespace App\Services;

class AttendanceService {

    private $db;
    private $attendance;
    private $leaveRequest;
    private $auditLog;
    private $settings;

    public function __construct() {
        $this->db = \DB::connection()->getPdo();
        $this->attendance = new \App\Models\Attendance();
        $this->leaveRequest = new \App\Models\LeaveRequest();
        $this->auditLog = new \App\Models\AttendanceAuditLog();
        $this->settings = new \App\Models\AttendanceSettings();
    }

    public function takeAttendance($data, $userId, $tenantId, $role = '') {
        // Allow front desk to edit attendance by default (can be controlled via settings)
        // If role is frontdesk, check settings - but default to allowing if setting not explicitly disabled
        if ($role === 'frontdesk') {
            $settings = $this->settings->getByTenant($tenantId);
            // Default to allowing frontdesk if setting is not explicitly set to false
            if (isset($settings['allow_frontdesk_edit']) && $settings['allow_frontdesk_edit'] == 0) {
                throw new \Exception("Front Desk is not authorized to mark attendance.");
            }
        }

        // Data format: ['batch_id' => X, 'course_id' => Y, 'attendance_date' => Z, 'attendance' => [['student_id' => 1, 'status' => 'present'], ...]]
        
        if (empty($data['attendance']) || !is_array($data['attendance'])) {
            throw new \Exception("No attendance data provided.");
        }

        // Resolve course_id from batch if not provided
        $courseId = $data['course_id'] ?? null;
        if (empty($courseId) && !empty($data['batch_id'])) {
            $stmt = $this->db->prepare("SELECT course_id FROM batches WHERE id = ?");
            $stmt->execute([$data['batch_id']]);
            $batch = $stmt->fetch();
            $courseId = $batch['course_id'] ?? null;
        }

        if (empty($courseId)) {
            throw new \Exception("Could not determine course. Please select a course.");
        }
        
        $records = [];
        foreach ($data['attendance'] as $item) {
            $records[] = [
                'student_id' => $item['student_id'],
                'batch_id' => $data['batch_id'],
                'course_id' => $courseId,
                'attendance_date' => $data['attendance_date'],
                'status' => $item['status'],
                'marked_by' => $userId
            ];
        }
        
        $result = $this->attendance->bulkUpsert($records, $tenantId);

        // TRIGGER: Automation Engine for Absences
        try {
            $automationService = new \App\Services\NotificationAutomationService();
            foreach ($records as $rec) {
                if ($rec['status'] === 'absent') {
                    $automationService->evalRulesForEvent('absent', [
                        'tenant_id' => $tenantId,
                        'student_id' => $rec['student_id'],
                        'attendance_date' => $rec['attendance_date']
                    ]);
                }
            }
        } catch (\Exception $e) {
            error_log("Automation trigger error (takeAttendance): " . $e->getMessage());
        }

        return $result;
    }

    public function bulkSave($records, $userId, $tenantId) {
        return $this->attendance->bulkUpsert($records, $tenantId);
    }

    public function editAttendance($id, $data, $userId, $tenantId, $role = '') {
        if (!$this->canEdit($id, $tenantId, false, $role)) {
            throw new \Exception("Cannot edit this attendance record: it may be locked, past the timeframe, or you lack permission.");
        }
        
        $oldRecord = $this->attendance->find($id);
        
        $this->attendance->update($id, [
            'status' => $data['status'],
            'marked_by' => $userId
        ]);
        
        $newRecord = $this->attendance->find($id);
        
        // TRIGGER: Automation Engine for Absences
        try {
            if ($newRecord['status'] === 'absent') {
                $automationService = new \App\Services\NotificationAutomationService();
                $automationService->evalRulesForEvent('absent', [
                    'tenant_id' => $tenantId,
                    'student_id' => $newRecord['student_id'],
                    'attendance_date' => $newRecord['attendance_date']
                ]);
            }
        } catch (\Exception $e) {
            error_log("Automation trigger error (editAttendance): " . $e->getMessage());
        }

        $this->auditLog->log([
            'tenant_id' => $tenantId,
            'attendance_id' => $id,
            'user_id' => $userId,
            'action' => 'updated',
            'old_values' => $oldRecord,
            'new_values' => $newRecord
        ]);
        
        return $newRecord;
    }

    public function lockAttendance($ids, $tenantId) {
        return $this->attendance->markLocked($ids, true);
    }

    public function unlockAttendance($ids, $userId, $tenantId) {
        $result = $this->attendance->markLocked($ids, false);
        foreach ($ids as $id) {
            $this->auditLog->log([
                'tenant_id' => $tenantId,
                'attendance_id' => $id,
                'user_id' => $userId,
                'action' => 'unlocked'
            ]);
        }
        return $result;
    }

    public function canEdit($attendanceId, $tenantId, $isSuperAdmin = false, $role = '') {
        $settings = $this->settings->getByTenant($tenantId);
        $lockPeriodHours = $settings['lock_period_hours'] ?? 24;
        
        $attendance = $this->attendance->find($attendanceId);
        if (!$attendance) return false;
        
        if ($isSuperAdmin || $role === 'instituteadmin') {
            return true;
        }

        // Additional check for frontdesk
        if ($role === 'frontdesk' && !($settings['allow_frontdesk_edit'] ?? false)) {
            return false;
        }
        
        if ($attendance['locked']) {
            return false;
        }
        
        $createdAt = strtotime($attendance['created_at']);
        $now = time();
        $hoursDiff = ($now - $createdAt) / 3600;
        
        if ($hoursDiff > $lockPeriodHours) {
            return false;
        }
        
        return true;
    }

    public function processApprovedLeave($leaveId, $tenantId) {
        $leave = $this->leaveRequest->find($leaveId);
        if (!$leave || $leave['status'] !== 'approved') return false;
        
        // Fetch student's current batch - course_id comes from batches table
        $stmt = $this->db->prepare("
            SELECT s.batch_id, b.course_id 
            FROM students s 
            JOIN batches b ON s.batch_id = b.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$leave['student_id']]);
        $student = $stmt->fetch();
        
        if (!$student || !$student['batch_id']) return false;
        
        $start = new \DateTime($leave['from_date']);
        $end = new \DateTime($leave['to_date']);
        $end->modify('+1 day');
        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($start, $interval, $end);
        
        $records = [];
        foreach ($period as $dt) {
            $records[] = [
                'student_id' => $leave['student_id'],
                'batch_id' => $student['batch_id'],
                'course_id' => $student['course_id'],
                'attendance_date' => $dt->format('Y-m-d'),
                'status' => 'leave',
                'marked_by' => $leave['approved_by']
            ];
        }
        
        return $this->attendance->bulkUpsert($records, $tenantId);
    }

    public function calculatePercentage($studentId, $batchId, $tenantId, $excludeLeave = true) {
        $stats = $this->attendance->getStudentStats($studentId, $batchId, $tenantId);
        if (!$stats || $stats['total_days'] == 0) return 0;
        
        $total = $stats['total_days'];
        $present = $stats['present_days'] + $stats['late_days']; // counting late as present for percentage?
        
        if ($excludeLeave) {
            $total -= $stats['leave_days'];
        }
        
        if ($total <= 0) return 0;
        
        return round(($present / $total) * 100, 2);
    }
}
