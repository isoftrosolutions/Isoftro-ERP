<?php
namespace App\Services;

use App\Models\NotificationAutomationRule;
use App\Services\QueueService;

class NotificationAutomationService {
    private $ruleModel;
    private $queueService;
    private $db;

    public function __construct() {
        $this->ruleModel = new NotificationAutomationRule();
        $this->queueService = new QueueService();
        $this->db = getDBConnection();
    }

    /**
     * Evaluate rules for a specific trigger and context
     */
    public function evalRulesForEvent($triggerType, $context) {
        $tenantId = $context['tenant_id'] ?? null;
        if (!$tenantId) return;

        $rules = $this->ruleModel->getActiveByTenant($tenantId, $triggerType);
        if (empty($rules)) return;

        foreach ($rules as $rule) {
            $this->processRule($rule, $context);
        }
    }

    /**
     * Process a single rule
     */
    private function processRule($rule, $context) {
        $triggerType = $rule['trigger_type'];

        if ($triggerType === 'absent') {
            $this->processAbsentRule($rule, $context);
        } elseif ($triggerType === 'fee_due') {
            $this->processFeeDueRule($rule, $context);
        }
    }

    /**
     * Logic for 'absent' trigger
     */
    private function processAbsentRule($rule, $context) {
        $studentId = $context['student_id'] ?? null;
        $attendanceDate = $context['attendance_date'] ?? null;
        $tenantId = $context['tenant_id'] ?? null;

        if (!$studentId || !$attendanceDate) return;

        // Conditions check (e.g., send only if it's the 1st day or Xth day)
        // For now, simplicity: send on every absent mark if rule is active.
        
        $student = $this->getStudentDetails($studentId, $tenantId);
        if (!$student) return;

        $message = $this->formatMessage($rule['message_template'], $student, $context);
        
        $this->queueService->dispatch('sms_notification', [
            'to' => $student['guardian_phone'] ?? $student['phone'],
            'message' => $message,
            'student_id' => $studentId,
            'rule_id' => $rule['id']
        ], $tenantId);
    }

    /**
     * Logic for 'fee_due' trigger
     */
    private function processFeeDueRule($rule, $context) {
        $studentId = $context['student_id'] ?? null;
        $tenantId = $context['tenant_id'] ?? null;
        $feeRecordId = $context['fee_record_id'] ?? null;

        if (!$studentId || !$feeRecordId) return;

        $student = $this->getStudentDetails($studentId, $tenantId);
        if (!$student) return;

        // Add fee specific data to context for formatting
        $stmt = $this->db->prepare("SELECT amount_due, due_date FROM fee_records WHERE id = ?");
        $stmt->execute([$feeRecordId]);
        $fee = $stmt->fetch();
        if ($fee) {
            $context['amount_due'] = $fee['amount_due'];
            $context['due_date'] = $fee['due_date'];
        }

        $message = $this->formatMessage($rule['message_template'], $student, $context);

        $this->queueService->dispatch('sms_notification', [
            'to' => $student['guardian_phone'] ?? $student['phone'],
            'message' => $message,
            'student_id' => $studentId,
            'rule_id' => $rule['id']
        ], $tenantId);
    }

    /**
     * Format message with placeholders
     */
    private function formatMessage($template, $student, $context) {
        $placeholders = [
            '{{student_name}}' => $student['full_name'],
            '{{roll_no}}' => $student['roll_no'],
            '{{date}}' => $context['attendance_date'] ?? date('Y-m-d'),
            '{{amount_due}}' => $context['amount_due'] ?? '0.00',
            '{{due_date}}' => $context['due_date'] ?? '',
            '{{institute_name}}' => $_SESSION['userData']['tenant_name'] ?? 'The Institute'
        ];

        return strtr($template, $placeholders);
    }

    /**
     * Get student details for notification
     */
    private function getStudentDetails($studentId, $tenantId) {
        $stmt = $this->db->prepare("SELECT id, full_name, roll_no, phone, guardian_phone FROM students WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$studentId, $tenantId]);
        return $stmt->fetch();
    }
}
