<?php

namespace App\Helpers;

/**
 * Handles all academic-related emails (Exams, Assignments, Enrollment).
 */
class AcademicEmailHelper extends MailHelper
{
    /**
     * Send enrollment confirmation (synchronous).
     */
    public static function sendEnrollmentConfirmation(\PDO $db, int $tenantId, array $enrollData): bool
    {
        $payload = array_merge($enrollData, ['template_key' => 'course_enrollment_success']);
        return self::processJob($db, $tenantId, 'academic_status', $payload);
    }

    /**
     * Send exam schedule (synchronous).
     */
    public static function sendExamSchedule(\PDO $db, int $tenantId, array $examData): bool
    {
        $payload = array_merge($examData, ['template_key' => 'exam_schedule_published']);
        return self::processJob($db, $tenantId, 'academic_notices', $payload);
    }

    /**
     * Send exam results (synchronous).
     */
    public static function sendExamResults(\PDO $db, int $tenantId, array $resultData): bool
    {
        $payload = array_merge($resultData, ['template_key' => 'exam_results_published']);
        return self::processJob($db, $tenantId, 'academic_results', $payload);
    }

    /**
     * Send assignment notifications (synchronous).
     */
    public static function sendAssignmentNotice(\PDO $db, int $tenantId, array $assignmentData, string $type = 'new'): bool
    {
        $templateKey = 'assignment_new';
        if ($type === 'submission') $templateKey = 'assignment_submission_confirmed';
        if ($type === 'graded') $templateKey = 'assignment_graded';
        
        $payload = array_merge($assignmentData, ['template_key' => $templateKey]);
        return self::processJob($db, $tenantId, 'academic_assignments', $payload);
    }

    /**
     * Send attendance warning (synchronous).
     */
    public static function sendAttendanceWarning(\PDO $db, int $tenantId, array $attendanceData): bool
    {
        $payload = array_merge($attendanceData, ['template_key' => 'attendance_warning']);
        return self::processJob($db, $tenantId, 'academic_warnings', $payload);
    }
}
