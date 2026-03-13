<?php
/**
 * Data Reconciliation Script: Fix Missing Fee Records
 * Finds students who have a balance in student_fee_summary but no records in fee_records.
 * Creates a "Base Course Fee" record so the Collect Fee page works correctly.
 */

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

echo "Starting Fee Record Reconciliation...\n";

$db = getDBConnection();

// 1. Find inconsistencies
$query = "
    SELECT s.id as student_id, s.tenant_id, e.batch_id, u.name as full_name, sfs.total_fee, sfs.due_amount
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN student_fee_summary sfs ON s.id = sfs.student_id
    LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
    WHERE sfs.due_amount > 0 AND s.deleted_at IS NULL
      AND NOT EXISTS (
          SELECT 1 FROM fee_records fr WHERE fr.student_id = s.id
      )
";

$stmt = $db->query($query);
$inconsistentStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($inconsistentStudents) . " students with due amounts but no fee records.\n";

if (empty($inconsistentStudents)) {
    echo "No inconsistencies found. Exiting.\n";
    exit;
}

$db->beginTransaction();

try {
    foreach ($inconsistentStudents as $student) {
        $studentId = $student['student_id'];
        $tenantId = $student['tenant_id'];
        $batchId = $student['batch_id'] ?: 0;
        $totalFee = $student['total_fee'];
        
        echo "Fixing ID {$studentId} ({$student['full_name']}) - Missing Rs {$totalFee} record.\n";
        
        // Ensure a "Base Course Fee" generic fee_item exists for this tenant/batch
        // We'll just create a dummy fee_item if we have to, or use a generic one.
        // First try to find ANY fee item for the tenant
        $stmtItem = $db->prepare("SELECT id FROM fee_items WHERE tenant_id = :tid LIMIT 1");
        $stmtItem->execute(['tid' => $tenantId]);
        $feeItem = $stmtItem->fetch();
        
        $feeItemId = null;
        if ($feeItem) {
            $feeItemId = $feeItem['id'];
        } else {
            // Need a dummy fee item
            $stmtInsertItem = $db->prepare("
                INSERT INTO fee_items (tenant_id, course_id, name, type, amount, installments, is_active)
                VALUES (:tid, NULL, 'System Reconciled Base Fee', 'one_time', 0, 1, 1)
            ");
            $stmtInsertItem->execute(['tid' => $tenantId]);
            $feeItemId = $db->lastInsertId();
        }
        
        // Insert missing fee_record corresponding to the total_fee
        $stmtRecord = $db->prepare("
            INSERT INTO fee_records (
                tenant_id, student_id, batch_id, fee_item_id, installment_no, 
                amount_due, amount_paid, fine_applied, due_date, status, academic_year
            ) VALUES (
                :tid, :sid, :bid, :fiid, 1,
                :amt, 0, 0, CURDATE(), 'pending', :ay
            )
        ");
        
        $academicYear = date('Y') . '-' . (date('Y') + 1);
        $stmtRecord->execute([
            'tid' => $tenantId,
            'sid' => $studentId,
            'bid' => $batchId,
            'fiid' => $feeItemId,
            'amt' => $totalFee,
            'ay' => $academicYear
        ]);
        
        // Now if the student had already paid some amount, we need to register that against this record
        $paidAmount = $student['total_fee'] - $student['due_amount'];
        if ($paidAmount > 0) {
            // Re-apply the payment to this newly created fee record
            $feeRecordId = $db->lastInsertId();
            $status = ($paidAmount >= $totalFee) ? 'paid' : 'pending';
            
            $stmtUpdateRecord = $db->prepare("
                UPDATE fee_records 
                SET amount_paid = :paid, status = :status
                WHERE id = :id
            ");
            $stmtUpdateRecord->execute([
                'paid' => $paidAmount,
                'status' => $status,
                'id' => $feeRecordId
            ]);
            echo "   -> Applied partial payment of Rs {$paidAmount} to new record.\n";
        }
    }
    
    $db->commit();
    echo "Reconciliation complete. " . count($inconsistentStudents) . " students fixed.\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "Error processing reconciliation: " . $e->getMessage() . "\n";
}
