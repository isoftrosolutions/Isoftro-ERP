<?php
/**
 * FinanceService
 * Handles core financial logic (Payments, Refunds, Summaries)
 */

namespace App\Services;

use App\Models\FeeRecord;
use App\Models\PaymentTransaction;
use App\Models\FeeSettings;
use App\Services\FeeCalculationService;
use Exception;

class FinanceService {
    private $db;
    private $feeRecordModel;
    private $transactionModel;
    private $settingsModel;
    private $calculationService;

    public function __construct() {
        if (class_exists('\Illuminate\Support\Facades\DB') && \Illuminate\Support\Facades\DB::getFacadeRoot()) {
            $this->db = \Illuminate\Support\Facades\DB::connection()->getPdo();
        } elseif (function_exists('getDBConnection')) {
            $this->db = getDBConnection();
        }
        $this->feeRecordModel = new FeeRecord();
        $this->transactionModel = new PaymentTransaction();
        $this->settingsModel = new FeeSettings();
        $this->calculationService = new FeeCalculationService();
    }

    /**
     * Record a student payment with transaction safety
     */
    public function recordPayment($input, $tenantId) {
        $this->db->beginTransaction();

        try {
            $feeRecordId = $input['fee_record_id'];
            $amountPaid = floatval($input['amount_paid']);
            $paidDate = $input['paid_date'] ?? date('Y-m-d');
            $paymentMode = strtolower(str_replace(' ', '_', $input['payment_mode'] ?? 'cash'));
            $receiptNo = $input['receipt_no'] ?? null;
            $fineAmount = floatval($input['fine_amount'] ?? 0);
            $notes = $input['notes'] ?? null;

            // 1. Get current fee record
            $feeRecord = $this->feeRecordModel->find($feeRecordId);
            if (!$feeRecord || $feeRecord['tenant_id'] != $tenantId) {
                throw new Exception("Fee record not found");
            }

            // Fetch student name
            $stmtS = $this->db->prepare("SELECT u.name as full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
            $stmtS->execute([$feeRecord['student_id']]);
            $student = $stmtS->fetch();
            $studentName = $student ? $student['full_name'] : 'Student';

            // 2. Generate receipt number if not provided
            if (!$receiptNo) {
                // generateDocNumber is in FeeCalculationService
                $receiptNo = $this->calculationService->generateDocNumber($tenantId, 'receipt');
                $this->settingsModel->incrementNumber($tenantId, 'receipt');
            }

            // 3. Calculate status
            $totalAmountToPay = floatval($feeRecord['amount_due']) + $fineAmount;
            $newPaidTotal = floatval($feeRecord['amount_paid']) + $amountPaid;
            $status = ($newPaidTotal >= $totalAmountToPay) ? 'paid' : 'partial';

            // 4. Record payment in fee_records (Audit logged inside Model)
            $this->feeRecordModel->recordPayment($feeRecordId, [
                'amount_paid' => $amountPaid,
                'paid_date' => $paidDate,
                'receipt_no' => $receiptNo,
                'receipt_path' => null,
                'payment_mode' => $paymentMode,
                'cashier_user_id' => $_SESSION['userData']['id'] ?? null,
                'fine_applied' => $fineAmount,
                'status' => $status
            ]);

            // 5. Sync with student_fee_summary 
            // V3.1 Update: Ensure we update the specific enrollment associated with this fee record
            $query = "UPDATE student_fee_summary SET 
                      paid_amount = paid_amount + ?,
                      due_amount = due_amount - ?,
                      fee_status = CASE 
                          WHEN (due_amount - ?) <= 0 THEN 'paid'
                          WHEN (paid_amount + ?) > 0 THEN 'partial'
                          ELSE 'unpaid'
                      END
                      WHERE student_id = ? AND tenant_id = ? 
                      AND (enrollment_id = ? OR enrollment_id IS NULL)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $amountPaid, $amountPaid, 
                $amountPaid, $amountPaid, 
                $feeRecord['student_id'], $tenantId,
                ($feeRecord['enrollment_id'] ?? $feeRecord['batch_id'] ?? null) // Fallback for safety
            ]);

            // If fee_records don't have enrollment_id directly, we might need to find it from batch_id
            if ($stmt->rowCount() == 0) {
                 // Try finding by mapping batch to enrollment
                 $stmtMap = $this->db->prepare("SELECT id FROM enrollments WHERE student_id = ? AND batch_id = ? LIMIT 1");
                 $stmtMap->execute([$feeRecord['student_id'], $feeRecord['batch_id']]);
                 $actualEnrollmentId = $stmtMap->fetchColumn();
                 
                 if ($actualEnrollmentId) {
                     $stmt->execute([
                        $amountPaid, $amountPaid, 
                        $amountPaid, $amountPaid, 
                        $feeRecord['student_id'], $tenantId,
                        $actualEnrollmentId
                    ]);
                 }
            }

            // 6. Log Transaction (Audit logged inside Model)
            $transactionId = $this->transactionModel->create([
                'tenant_id' => $tenantId,
                'student_id' => $feeRecord['student_id'],
                'fee_record_id' => $feeRecordId,
                'amount' => $amountPaid,
                'payment_method' => $paymentMode,
                'receipt_number' => $receiptNo,
                'receipt_path' => null,
                'payment_date' => $paidDate,
                'recorded_by' => $_SESSION['userData']['id'] ?? null,
                'notes' => $notes,
                'status' => 'completed'
            ]);

            // 7. Log to General Ledger (Bonus)
            $this->logLedgerEntry($tenantId, $feeRecord['student_id'], 'payment', $transactionId, $amountPaid, 'credit', "Fee Payment - Receipt #$receiptNo", $paidDate);

            $this->db->commit();
            return [
                'success' => true,
                'receipt_no' => $receiptNo,
                'transaction_id' => $transactionId,
                'fee_record' => $feeRecord,
                'amount_paid' => $amountPaid,
                'student_name' => $studentName,
                'payment_mode' => $paymentMode,
                'paid_date' => $paidDate
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Record a bulk payment by distributing amount across outstanding records
     * OPTIMIZED: Accept pre-fetched records to avoid duplicate queries
     */
    public function recordBulkPayment($input, $tenantId, $prefetchedRecords = null) {
        $studentId = $input['student_id'];
        $totalAmountPaid = floatval($input['amount']);
        $paidDate = $input['payment_date'] ?? date('Y-m-d');
        $paymentMode = strtolower(str_replace(' ', '_', $input['payment_mode'] ?? 'cash'));
        $notes = $input['notes'] ?? null;

        $this->db->beginTransaction();

        try {
            // 1. Generate a single receipt number for the entire bulk transaction
            $receiptNo = $this->calculationService->generateDocNumber($tenantId, 'receipt');
            $this->settingsModel->incrementNumber($tenantId, 'receipt');

            // Fetch student name - single query
            $stmtS = $this->db->prepare("SELECT u.name as full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
            $stmtS->execute([$studentId]);
            $student = $stmtS->fetch();
            $studentName = $student ? $student['full_name'] : 'Student';

            // 2. Use prefetched records OR fetch outstanding records for this student, oldest first
            $records = $prefetchedRecords;
            if ($records === null) {
                $stmt = $this->db->prepare("
                    SELECT id, amount_due, amount_paid, batch_id 
                    FROM fee_records 
                    WHERE student_id = :sid AND tenant_id = :tid 
                    AND amount_due > amount_paid
                    ORDER BY due_date ASC
                ");
                $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
                $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            $remainingAmount = $totalAmountPaid;
            $processedRecords = [];
            $transactionIds = [];

            // OPTIMIZATION: Prepare statements once outside loop
            $updateFeeStmt = null;
            $insertTxnStmt = null;
            $insertLedgerStmt = null;
            $insertFeeLedgerStmt = null;

            // Prepare reusable statements
            $updateFeeStmt = $this->db->prepare("
                UPDATE fee_records 
                SET amount_paid = amount_paid + ?,
                    paid_date = ?,
                    receipt_no = ?,
                    payment_mode = ?,
                    cashier_user_id = ?,
                    status = ?
                WHERE id = ?
            ");

            $insertTxnStmt = $this->db->prepare("
                INSERT INTO payment_transactions 
                (tenant_id, student_id, fee_record_id, amount, payment_method, receipt_number, payment_date, recorded_by, notes, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $insertLedgerStmt = $this->db->prepare("
                INSERT INTO ledger_entries (tenant_id, student_id, reference_type, reference_id, amount, type, description, entry_date, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $insertFeeLedgerStmt = $this->db->prepare("
                INSERT INTO fee_ledger (tenant_id, student_id, payment_transaction_id, fee_record_id, entry_date, entry_type, amount, description)
                VALUES (:tid, :sid, :ptid, :frid, :edate, :etype, :amt, :desc)
            ");

            foreach ($records as $record) {
                if ($remainingAmount <= 0) break;

                $netDue = floatval($record['amount_due']) - floatval($record['amount_paid']);
                $paymentForThisRecord = min($remainingAmount, $netDue);
                
                $newPaidTotal = floatval($record['amount_paid']) + $paymentForThisRecord;
                $status = ($newPaidTotal >= floatval($record['amount_due'])) ? 'paid' : 'partial';
                $cashierUserId = $_SESSION['userData']['id'] ?? null;

                // Update Fee Record using prepared statement
                $updateFeeStmt->execute([
                    $paymentForThisRecord,
                    $paidDate,
                    $receiptNo,
                    $paymentMode,
                    $cashierUserId,
                    $status,
                    $record['id']
                ]);

                // Insert Transaction using prepared statement
                $insertTxnStmt->execute([
                    $tenantId,
                    $studentId,
                    $record['id'],
                    $paymentForThisRecord,
                    $paymentMode,
                    $receiptNo,
                    $paidDate,
                    $cashierUserId,
                    $notes . " (Bulk Payment Part)",
                    'completed'
                ]);
                $transactionIds[] = $this->db->lastInsertId();

                // Log to General Ledger using prepared statement
                $insertLedgerStmt->execute([
                    $tenantId,
                    $studentId,
                    'payment',
                    $this->db->lastInsertId(),
                    $paymentForThisRecord,
                    'credit',
                    "Bulk Fee Payment - Receipt #$receiptNo",
                    $paidDate
                ]);

                // Log to Fee Ledger using prepared statement
                $lastId = $this->db->lastInsertId();
                $insertFeeLedgerStmt->execute([
                    'tid' => $tenantId,
                    'sid' => $studentId,
                    'ptid' => $lastId,
                    'frid' => null,
                    'edate' => $paidDate,
                    'etype' => 'credit',
                    'amt' => $paymentForThisRecord,
                    'desc' => "Bulk Fee Payment - Receipt #$receiptNo"
                ]);

                $remainingAmount -= $paymentForThisRecord;
                $processedRecords[] = $record['id'];

                // V3.1: Accumulate updates per enrollment/batch
                $batchId = $record['batch_id'] ?? null;
                if (!isset($enrollmentPayments[$batchId])) $enrollmentPayments[$batchId] = 0;
                $enrollmentPayments[$batchId] += $paymentForThisRecord;
            }

            // 3. Update Student Summary per enrollment affected
            foreach ($enrollmentPayments as $batchId => $paidAmt) {
                // Find enrollment_id for this batch
                $stmtE = $this->db->prepare("SELECT id FROM enrollments WHERE student_id = ? AND batch_id = ? LIMIT 1");
                $stmtE->execute([$studentId, $batchId]);
                $eid = $stmtE->fetchColumn();

                if ($eid) {
                    $query = "UPDATE student_fee_summary SET 
                              paid_amount = paid_amount + ?,
                              due_amount = due_amount - ?,
                              fee_status = CASE 
                                  WHEN (due_amount - ?) <= 0 THEN 'paid'
                                  WHEN (paid_amount + ?) > 0 THEN 'partial'
                                  ELSE 'unpaid'
                              END
                              WHERE student_id = ? AND enrollment_id = ?";
                    $stmtSum = $this->db->prepare($query);
                    $stmtSum->execute([
                        $paidAmt, $paidAmt, 
                        $paidAmt, $paidAmt, 
                        $studentId, $eid
                    ]);
                }
            }

            $this->db->commit();
            return [
                'success' => true,
                'receipt_no' => $receiptNo,
                'amount_paid' => $totalAmountPaid,
                'student_name' => $studentName,
                'transaction_ids' => $transactionIds,
                'records_affected' => count($processedRecords)
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Log a general ledger entry
     */
    private function logLedgerEntry($tenantId, $studentId, $refType, $refId, $amount, $type, $description, $date) {
        // 1. Log to existing general ledger (legacy/general)
        $query1 = "INSERT INTO ledger_entries (tenant_id, student_id, reference_type, reference_id, amount, type, description, entry_date, created_at, updated_at) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt1 = $this->db->prepare($query1);
        $stmt1->execute([$tenantId, $studentId, $refType, $refId, $amount, $type, $description, $date]);

        // 2. Log to new dedicated fee ledger (Dr/Cr format)
        $query2 = "INSERT INTO fee_ledger (tenant_id, student_id, payment_transaction_id, fee_record_id, entry_date, entry_type, amount, description)
                   VALUES (:tid, :sid, :ptid, :frid, :edate, :etype, :amt, :desc)";
        $stmt2 = $this->db->prepare($query2);
        
        $params2 = [
            'tid' => $tenantId,
            'sid' => $studentId,
            'ptid' => ($refType === 'payment') ? $refId : null,
            'frid' => ($refType === 'fee_record') ? $refId : null,
            'edate' => $date,
            'etype' => ($type === 'credit' ? 'credit' : 'debit'),
            'amt' => $amount,
            'desc' => $description
        ];
        
        return $stmt2->execute($params2);
    }
}
