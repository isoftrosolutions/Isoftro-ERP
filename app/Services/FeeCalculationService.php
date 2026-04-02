<?php
/**
 * FeeCalculationService
 * Handles core business logic for the Fee Module
 */

namespace App\Services;

use App\Models\FeeItem;
use App\Models\FeeRecord;
use App\Models\FeeSettings;
use App\Models\StudentInvoice;

class FeeCalculationService {
    private $feeItemModel;
    private $feeRecordModel;
    private $feeSettingsModel;
    private $invoiceModel;
    
    public function __construct() {
        $this->feeItemModel = new FeeItem();
        $this->feeRecordModel = new FeeRecord();
        $this->feeSettingsModel = new FeeSettings();
        $this->invoiceModel = new StudentInvoice();
    }
    
    /**
     * Calculate late fine for a fee record
     */
    public function calculateLateFine($feeRecordId) {
        $record = $this->feeRecordModel->find($feeRecordId);
        if (!$record) return 0.00;
        
        $item = $this->feeItemModel->find($record['fee_item_id']);
        if (!$item || $item['late_fine_per_day'] <= 0) return 0.00;
        
        $settings = $this->feeSettingsModel->getByTenant($record['tenant_id']);
        if (!$settings || !$settings['apply_late_fine']) return 0.00;
        
        $dueDate = new \DateTime($record['due_date']);
        $today = new \DateTime();
        
        if ($today <= $dueDate) return 0.00;
        
        $diff = $today->diff($dueDate)->days;
        $graceDays = (int)($settings['late_fine_grace_days'] ?? 0);
        
        if ($diff <= $graceDays) return 0.00;
        
        // Simple logic: days_past_grace * fine_per_day
        $fineDays = $diff - $graceDays;
        $fine = $fineDays * $item['late_fine_per_day'];
        
        // Optional: Cap fine at 50% of original amount
        $maxFine = $record['amount_due'] * 0.5;
        return min($fine, $maxFine);
    }
    
    /**
     * Generate fee records for a student on enrollment
     * Based on course's active fee items
     */
    public function generateFeesForEnrollment($studentId, $batchId, $courseId, $tenantId) {
        $items = $this->feeItemModel->getByCourse($courseId, $tenantId);
        if (empty($items)) return [];
        
        $feeRecords = [];
        $academicYear = date('Y') . '-' . (date('Y') + 1); // Mock academic year
        
        foreach ($items as $item) {
            $installments = (int)$item['installments'];
            $installmentAmount = round($item['amount'] / $installments, 2);
            
            for ($i = 1; $i <= $installments; $i++) {
                // Logic for due dates: first one today, others monthly
                $dueDate = new \DateTime();
                if ($i > 1) {
                    $dueDate->modify('+' . ($i - 1) . ' month');
                }
                
                $feeRecords[] = [
                    'tenant_id' => $tenantId,
                    'student_id' => $studentId,
                    'batch_id' => $batchId,
                    'fee_item_id' => $item['id'],
                    'installment_no' => $i,
                    'amount_due' => $installmentAmount,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'status' => 'pending',
                    'academic_year' => $academicYear
                ];
            }
        }
        
        return $this->feeRecordModel->bulkCreate($feeRecords);
    }
    
    /**
     * Format a number into currency based on tenant settings
     */
    public function formatAmount($amount, $tenantId) {
        $settings = $this->feeSettingsModel->getByTenant($tenantId);
        $currency = $settings['currency'] ?? 'NPR';
        return $currency . ' ' . number_format($amount, 2);
    }
    
    /**
     * Generate next document number (Invoice or Receipt)
     */
    public function generateDocNumber($tenantId, $type = 'invoice') {
        $settings = $this->feeSettingsModel->getByTenant($tenantId);
        if (!$settings) {
            $settings = $this->feeSettingsModel->createDefault($tenantId);
        }

        $prefix = ($type === 'invoice') ? $settings['invoice_prefix'] : $settings['receipt_prefix'];

        // Generate random 6-digit number, ensure uniqueness
        $db = \DB::connection()->getPdo();
        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $random = random_int(100000, 999999);
            $docNo = $prefix . '-' . $random;

            $stmt = $db->prepare("SELECT COUNT(*) FROM payment_transactions WHERE receipt_number = ? AND tenant_id = ?");
            $stmt->execute([$docNo, $tenantId]);
            if ($stmt->fetchColumn() == 0) {
                return $docNo;
            }
        }

        // Fallback: timestamp-based if all random attempts collide
        return $prefix . '-' . substr(time(), -6);
    }
}
