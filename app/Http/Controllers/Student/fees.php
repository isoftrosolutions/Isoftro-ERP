<?php
/**
 * Student Portal — Fee Controller
 * Handles student-specific fee queries and payment history
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user     = getCurrentUser();
$tenantId = $user['tenant_id'] ?? null;
$userId   = $user['id'] ?? null;

// Resolve student_id from session or DB
$studentId = $_SESSION['userData']['student_id'] ?? null;
if (!$studentId && $userId) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id FROM students WHERE user_id = :uid AND tenant_id = :tid LIMIT 1");
        $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $studentId = $row['id'];
            $_SESSION['userData']['student_id'] = $studentId;
        }
    } catch (Exception $e) {
        error_log('Student fees - failed to resolve student_id: ' . $e->getMessage());
    }
}

if (!$tenantId || !$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student record not found']);
    exit;
}

try {
    $db     = getDBConnection();
    $action = $_GET['action'] ?? 'get_ledger';

    switch ($action) {
        case 'get_ledger':
        default:
            // Fee records with item name
            $stmt = $db->prepare("
                SELECT fr.id, fr.fee_item_id, fr.batch_id, fr.installment_no,
                       fr.amount_due, fr.amount_paid, fr.discount_amount,
                       fr.due_date, fr.paid_date, fr.receipt_no, fr.payment_mode,
                       fr.fine_applied, fr.fine_waived, fr.notes,
                       fr.academic_year, fr.status,
                       fi.name  AS fee_item_name,
                       fi.type  AS fee_item_type
                FROM fee_records fr
                LEFT JOIN fee_items fi ON fr.fee_item_id = fi.id
                WHERE fr.student_id = :sid AND fr.tenant_id = :tid
                ORDER BY fr.due_date DESC
                LIMIT 50
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $ledger = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Payment transactions
            $stmt = $db->prepare("
                SELECT pt.id, pt.fee_record_id, pt.amount,
                       pt.payment_method  AS payment_mode,
                       pt.receipt_number, pt.receipt_path,
                       pt.payment_date, pt.notes, pt.status,
                       fi.name AS fee_item_name
                FROM payment_transactions pt
                LEFT JOIN fee_records fr ON pt.fee_record_id = fr.id
                LEFT JOIN fee_items fi   ON fr.fee_item_id   = fi.id
                WHERE pt.student_id = :sid AND pt.tenant_id = :tid
                ORDER BY pt.payment_date DESC
                LIMIT 30
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Build summary from fee_records
            $totalDue  = 0;
            $totalPaid = 0;
            foreach ($ledger as $l) {
                $totalDue  += floatval($l['amount_due']  ?? 0);
                $totalPaid += floatval($l['amount_paid'] ?? 0);
            }

            echo json_encode([
                'success' => true,
                'data'    => [
                    'records'      => $ledger,
                    'payments'     => $transactions,
                    'total_fee'    => $totalDue,
                    'total_paid'   => $totalPaid,
                    'balance'      => $totalDue - $totalPaid,
                    'summary'      => [
                        'total_due'   => $totalDue,
                        'total_paid'  => $totalPaid,
                        'outstanding' => $totalDue - $totalPaid
                    ]
                ]
            ]);
            break;

        case 'get_outstanding':
            $stmt = $db->prepare("
                SELECT fr.id, fr.amount_due, fr.amount_paid,
                       fr.due_date, fr.status,
                       fi.name AS fee_item_name
                FROM fee_records fr
                LEFT JOIN fee_items fi ON fr.fee_item_id = fi.id
                WHERE fr.student_id = :sid AND fr.tenant_id = :tid
                  AND fr.amount_due > fr.amount_paid
                ORDER BY fr.due_date ASC
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
    }

} catch (Exception $e) {
    error_log('Student fees controller error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
