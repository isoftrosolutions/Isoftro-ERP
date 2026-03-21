<?php
ob_start(); // Buffer all output so PHP warnings/notices don't corrupt JSON responses
/**
 * Fee Setup API Controller
 * Handles fee items setup for the current tenant
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

require_once base_path('vendor/autoload.php');

use App\Services\QueueService;
use App\Services\FinanceService;
use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json');

// Ensure user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$role = $_SESSION['userData']['role'] ?? '';
// RBAC check
if (!in_array($role, ['instituteadmin', 'frontdesk', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

use App\Helpers\ReceiptHelper;
use App\Helpers\MailHelper;
use App\Helpers\CsrfHelper;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        CsrfHelper::requireCsrfToken();
        // Send the refreshed CSRF token in the header so the client can synchronize
        header('X-CSRF-Token: ' . CsrfHelper::getCsrfToken());
    }

    $db = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Initialize models and services
    $feeItemModel = new \App\Models\FeeItem();
    $feeRecordModel = new \App\Models\FeeRecord();
    $settingsModel = new \App\Models\FeeSettings();
    $invoiceModel = new \App\Models\StudentInvoice();
    $transactionModel = new \App\Models\PaymentTransaction();
    $calculationService = new \App\Services\FeeCalculationService();
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            // List fee items
            $query = "SELECT fi.*, 
                      c.name as course_name,
                      (SELECT COUNT(*) FROM fee_records fr WHERE fr.fee_item_id = fi.id) as total_records
                      FROM fee_items fi
                      LEFT JOIN courses c ON fi.course_id = c.id
                      WHERE fi.tenant_id = :tid AND fi.deleted_at IS NULL";
            
            $params = ['tid' => $tenantId];

            if (!empty($_GET['id'])) {
                $query .= " AND fi.id = :id";
                $params['id'] = $_GET['id'];
            }

            if (!empty($_GET['course_id'])) {
                $query .= " AND fi.course_id = :course_id";
                $params['course_id'] = $_GET['course_id'];
            }

            if (!empty($_GET['type'])) {
                $query .= " AND fi.type = :type";
                $params['type'] = $_GET['type'];
            }

            if (!empty($_GET['search'])) {
                $search = '%' . $_GET['search'] . '%';
                $query .= " AND (fi.name LIKE :search)";
                $params['search'] = $search;
            }

            $query .= " ORDER BY fi.type ASC, fi.name ASC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $feeItems = $stmt->fetchAll();

            // Get courses for dropdown
            $stmt = $db->prepare("SELECT id, name FROM courses WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY name ASC");
            $stmt->execute(['tid' => $tenantId]);
            $courses = $stmt->fetchAll();

            echo json_encode([
                'success' => true, 
                'data' => $feeItems,
                'courses' => $courses
            ]);
        }
        else if ($action === 'get_outstanding') {
            // Get outstanding fees for a student or all students
            $studentId = $_GET['student_id'] ?? null;
            
            if ($studentId) {
                // Get outstanding for specific student + total summary
                $query = "SELECT fr.*, fi.name as fee_item_name, fi.type as fee_type, 
                          u.name as student_name, s.roll_no as student_code
                          FROM fee_records fr
                          JOIN fee_items fi ON fr.fee_item_id = fi.id
                          JOIN students s ON fr.student_id = s.id
                          LEFT JOIN users u ON s.user_id = u.id
                          WHERE fr.tenant_id = :tid AND fr.student_id = :sid 
                          AND fr.amount_due > fr.amount_paid
                          ORDER BY fr.due_date ASC";
                $stmt = $db->prepare($query);
                $stmt->execute(['tid' => $tenantId, 'sid' => $studentId]);
                $records = $stmt->fetchAll();

                // Get summary from student_fee_summary
                $stmt = $db->prepare("SELECT * FROM student_fee_summary WHERE student_id = :sid");
                $stmt->execute(['sid' => $studentId]);
                $summary = $stmt->fetch();
                
                if ($summary && $summary['due_amount'] > 0 && count($records) === 0) {
                    try {
                        $db->beginTransaction();
                        $stmtB = $db->prepare("SELECT batch_id FROM enrollments WHERE student_id = :sid AND status = 'active' LIMIT 1");
                        $stmtB->execute(['sid' => $studentId]);
                        $batchInfo = $stmtB->fetch();
                        $batchId = $batchInfo ? $batchInfo['batch_id'] : null;

                        $stmtF = $db->prepare("SELECT id FROM fee_items WHERE tenant_id = :tid LIMIT 1");
                        $stmtF->execute(['tid' => $tenantId]);
                        $feeItem = $stmtF->fetch();
                        if (!$feeItem) {
                             $db->prepare("INSERT INTO fee_items (tenant_id, name, type, amount, installments, is_active) VALUES (?, 'Base Course Fee', 'one_time', 0, 1, 1)")->execute([$tenantId]);
                             $feeItemId = $db->lastInsertId();
                        } else {
                             $feeItemId = $feeItem['id'];
                        }

                        $recordAmt = (float)$summary['total_fee'];
                        $paid = $recordAmt - (float)$summary['due_amount'];
                        $status = ($paid >= $recordAmt) ? 'paid' : 'pending';

                        $stmtR = $db->prepare("INSERT INTO fee_records (tenant_id, student_id, batch_id, fee_item_id, installment_no, amount_due, amount_paid, due_date, status, academic_year) VALUES (?, ?, ?, ?, 1, ?, ?, CURDATE(), ?, ?)");
                        $stmtR->execute([
                            $tenantId, 
                            $studentId, 
                            $batchId, 
                            $feeItemId, 
                            $recordAmt,
                            $paid,
                            $status,
                            date('Y') . '-' . (date('Y') + 1)
                        ]);
                        $db->commit();
                        
                        // Refetch records
                        $stmtFetch = $db->prepare($query);
                        $stmtFetch->execute(['tid' => $tenantId, 'sid' => $studentId]);
                        $records = $stmtFetch->fetchAll();
                        
                    } catch (Exception $e) {
                         if ($db->inTransaction()) $db->rollBack();
                    }
                }

                $response = [
                    'success' => true, 
                    'data' => $records,
                    'summary' => $summary
                ];

                echo json_encode($response);
            } else {
                // Get summary for all students with outstanding
                // Optimized: Join with pre-calculated counts from fee_records to avoid correlated subqueries
                $query = "SELECT s.id as student_id, u.name as student_name, c.id as course_id, c.name as course_name,
                                 sfs.total_fee as total_due, sfs.paid_amount as total_paid, sfs.due_amount as current_balance,
                                 fr_stats.next_due_date, fr_stats.outstanding_count
                          FROM student_fee_summary sfs
                          JOIN students s ON sfs.student_id = s.id
                          LEFT JOIN users u ON s.user_id = u.id
                          LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id
                          LEFT JOIN courses c ON b.course_id = c.id
                          LEFT JOIN (
                              SELECT student_id, MIN(due_date) as next_due_date, COUNT(*) as outstanding_count
                              FROM fee_records
                              WHERE amount_due > amount_paid AND tenant_id = :tid2
                              GROUP BY student_id
                          ) fr_stats ON s.id = fr_stats.student_id
                          WHERE sfs.due_amount > 0 AND sfs.tenant_id = :tid
                          AND s.deleted_at IS NULL AND s.status = 'active'
                          ORDER BY sfs.due_amount DESC 
                          LIMIT 1000";
                $stmt = $db->prepare($query);
                $stmt->execute(['tid' => $tenantId, 'tid2' => $tenantId]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
            }
            return;
        }
        else if ($action === 'get_payment_history') {
            // Get payment history from transactions table
            $search = $_GET['search'] ?? null;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            
            $query = "SELECT pt.*, fi.name as fee_item_name, u.name as student_name, pt.receipt_number as receipt_no, pt.amount as amount_paid
                      FROM payment_transactions pt
                      JOIN fee_records fr ON pt.fee_record_id = fr.id
                      JOIN fee_items fi ON fr.fee_item_id = fi.id
                      JOIN students s ON pt.student_id = s.id 
                      LEFT JOIN users u ON s.user_id = u.id
                      WHERE pt.tenant_id = :tid";
            
            $params = ['tid' => $tenantId];
            
            if ($search) {
                $query .= " AND (u.name LIKE :s1 OR pt.receipt_number LIKE :s2)";
                $params['s1'] = "%$search%";
                $params['s2'] = "%$search%";
            }
            
            if ($dateFrom) {
                $query .= " AND pt.payment_date >= :date_from";
                $params['date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $query .= " AND pt.payment_date <= :date_to";
                $params['date_to'] = $dateTo;
            }
            
            $query .= " ORDER BY pt.payment_date DESC, pt.id DESC LIMIT 100";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $data]);
        }
        else if ($action === 'get_calculated_fine') {
            $feeRecordId = $_GET['fee_record_id'] ?? null;
            if (!$feeRecordId) throw new Exception("Fee record ID required");

            $fine = $calculationService->calculateLateFine($feeRecordId);

            echo json_encode([
                'success' => true,
                'data' => ['fine' => $fine]
            ]);
        }
        else if ($action === 'get_student_ledger') {
            $studentId = $_GET['student_id'] ?? null;
            if (!$studentId) throw new Exception("Student ID required");

            $ledger = $feeRecordModel->getByStudent($studentId, $tenantId);
            $transactions = $transactionModel->getByStudent($studentId, $tenantId);
            $balance = $feeRecordModel->getStudentBalance($studentId, $tenantId);

            echo json_encode([
                'success' => true,
                'data' => [
                    'ledger' => $ledger,
                    'transactions' => $transactions,
                    'balance' => $balance
                ]
            ]);
        }
        

        else if ($action === 'get_payment_details') {
            $transactionId = $_GET['transaction_id'] ?? null;
            $receiptNo = $_GET['receipt_no'] ?? null;
            if (!$transactionId && !$receiptNo) throw new Exception("Transaction ID or Receipt Number required");
            
            $whereClause = $transactionId ? "pt.id = :id" : "pt.receipt_number = :receipt_no";
            $params = ['tenant' => $tenantId];
            if ($transactionId) {
                $params['id'] = $transactionId;
            } else {
                $params['receipt_no'] = $receiptNo;
            }

            $stmt = $db->prepare("
                SELECT pt.*, fr.fee_item_id, fi.name as fee_item_name, u.name as student_name, u.email,
                       c.name as course_name, b.name as batch_name, fr.amount_due, fr.fine_applied
                FROM payment_transactions pt
                LEFT JOIN fee_records fr ON pt.fee_record_id = fr.id
                LEFT JOIN fee_items fi ON fr.fee_item_id = fi.id
                JOIN students s ON pt.student_id = s.id 
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id
                LEFT JOIN courses c ON b.course_id = c.id
                WHERE $whereClause AND pt.tenant_id = :tenant
            ");
            $stmt->execute($params);
            $txn = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$txn) throw new Exception("Transaction not found");
            
            // Receipt URL now points to the HTML receipt page
            $receiptUrl = APP_URL . '/api/admin/fees?action=generate_receipt_html&transaction_id=' . ($txn['id'] ?? '') . '&receipt_no=' . ($txn['receipt_number'] ?? '');
            $imageUrl = $txn['receipt_path'] ? APP_URL . '/public/' . $txn['receipt_path'] : null;

            echo json_encode([
                'success' => true,
                'data' => [
                    'transaction' => $txn,
                    'receipt_url' => $receiptUrl,
                    'pdf_url' => $receiptUrl,
                    'image_url' => $imageUrl
                ]
            ]);
        }
        else if ($action === 'get_payment_init_data') {
            $studentId = $_GET['student_id'] ?? null;
            if (!$studentId) throw new Exception("Student ID required");

            // 1. Fetch Student Details
            $stmtS = $db->prepare("
                SELECT s.id, u.name as name, s.roll_no, s.photo_url,
                       c.name as course_name, b.name as batch_name
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' 
                LEFT JOIN batches b ON e.batch_id = b.id
                LEFT JOIN courses c ON b.course_id = c.id
                WHERE s.id = :sid AND s.tenant_id = :tid
            ");
            $stmtS->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $student = $stmtS->fetch(\PDO::FETCH_ASSOC);
            if (!$student) throw new Exception("Student not found");

            // 2. Fetch Institute (Tenant) Details
            $stmtI = $db->prepare("SELECT name, logo_path, address, phone, email FROM tenants WHERE id = :tid");
            $stmtI->execute(['tid' => $tenantId]);
            $institute = $stmtI->fetch(\PDO::FETCH_ASSOC);

            // 3. Fetch Fee Summary
            $stmtSum = $db->prepare("SELECT * FROM student_fee_summary WHERE student_id = :sid");
            $stmtSum->execute(['sid' => $studentId]);
            $summary = $stmtSum->fetch(\PDO::FETCH_ASSOC);

            // 4. Fetch Outstanding Records
            $stmtRecs = $db->prepare("
                SELECT fr.*, fi.name as fee_item_name, fi.type as fee_type
                FROM fee_records fr
                JOIN fee_items fi ON fr.fee_item_id = fi.id
                WHERE fr.student_id = :sid AND fr.tenant_id = :tid 
                AND fr.amount_due > fr.amount_paid
                ORDER BY fr.due_date ASC
            ");
            $stmtRecs->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $records = $stmtRecs->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'institute' => $institute,
                    'summary' => $summary,
                    'records' => $records
                ]
            ]);
        }
        else if ($action === 'generate_receipt_html') {
            $transactionId = $_GET['transaction_id'] ?? null;
            $receiptNo = $_GET['receipt_no'] ?? null;
            
            if (!$transactionId && !$receiptNo) throw new Exception("Transaction ID or Receipt Number required");        
            $html = ReceiptHelper::getHtml($db, $tenantId, $transactionId, $receiptNo);
            if (!$html) throw new Exception("Payment not found");
            
            if (!empty($_GET['is_pdf'])) {
                $pdfPath = ReceiptHelper::generatePdf($db, $tenantId, $transactionId, $receiptNo);
                if (!$pdfPath) throw new Exception("Failed to generate PDF");
                
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="Receipt_' . ($receiptNo ?: $transactionId) . '.pdf"');
                readfile($pdfPath);
                exit;
            }

            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    }

    else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $action = $input['action'] ?? 'create';

        if ($action === 'send_email_receipt') {
            $receiptNo = $input['receipt_no'] ?? null;
            if (!$receiptNo) throw new Exception("Receipt number required");

            $stmt = $db->prepare("
                SELECT pt.id, pt.student_id, pt.receipt_number as receipt_no, pt.amount, pt.payment_method, pt.payment_date,
                    u.name as student_name, u.email as student_email, s.roll_no,
                    c.name as course_name, b.name as batch_name
                FROM payment_transactions pt 
                LEFT JOIN students s ON pt.student_id = s.id JOIN users u ON s.user_id = u.id 
                LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id
                LEFT JOIN courses c ON b.course_id = c.id
                WHERE pt.receipt_number = :rno AND pt.tenant_id = :tid
            ");
            $stmt->execute(['rno' => $receiptNo, 'tid' => $tenantId]);
            $pay = $stmt->fetch();

            if (!$pay) throw new Exception("Payment record not found");

            // Dispatch to Background Worker with complete student and payment data
            $queue = new QueueService();
            $queue->dispatch('payment_receipt', [
                'transaction_id' => $pay['id'],
                'receipt_no' => $pay['receipt_no'],
                'student_id' => $pay['student_id'],
                'student_name' => $pay['student_name'] ?? 'Student',
                'student_email' => $pay['student_email'] ?? '',
                'roll_no' => $pay['roll_no'] ?? '',
                'course_name' => $pay['course_name'] ?? '',
                'batch_name' => $pay['batch_name'] ?? '',
                'amount' => $pay['amount'] ?? 0,
                'paid_date' => !empty($pay['payment_date']) ? date('Y-m-d', strtotime($pay['payment_date'])) : date('Y-m-d'),
                'payment_mode' => $pay['payment_method'] ?? 'cash',
                'login_url' => (defined('APP_URL') ? APP_URL : '') . '/?page=login'
            ], $tenantId);

            echo json_encode([
                'success' => true, 
                'message' => 'Email receipt request has been queued in the background.'
            ]);
            exit;
        }

        if ($action === 'create' || $action === 'update') {
            $name = $input['name'] ?? '';
            $courseId = $input['course_id'] ?? null;
            $type = $input['type'] ?? 'monthly';
            $amount = floatval($input['amount'] ?? 0);
            $installments = intval($input['installments'] ?? 1);
            $lateFinePerDay = floatval($input['late_fine_per_day'] ?? 0);
            $isActive = isset($input['is_active']) ? ($input['is_active'] ? 1 : 0) : 1;

            if (empty($name)) {
                throw new Exception("Fee item name is required");
            }
            if (empty($courseId)) {
                throw new Exception("Please select a course");
            }
            if ($amount <= 0) {
                throw new Exception("Amount must be greater than 0");
            }

            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO fee_items (tenant_id, course_id, name, type, amount, installments, late_fine_per_day, is_active) 
                    VALUES (:tid, :course_id, :name, :type, :amount, :installments, :late_fine, :is_active)
                ");

                $stmt->execute([
                    'tid' => $tenantId,
                    'course_id' => $courseId,
                    'name' => $name,
                    'type' => $type,
                    'amount' => $amount,
                    'installments' => $installments,
                    'late_fine' => $lateFinePerDay,
                    'is_active' => $isActive
                ]);

                $feeItemId = $db->lastInsertId();

                echo json_encode([
                    'success' => true, 
                    'message' => 'Fee item created successfully',
                    'data' => ['id' => $feeItemId]
                ]);
            } else {
                $id = $input['id'] ?? null;
                if (!$id) {
                    throw new Exception("Fee item ID is required for update");
                }

                $stmt = $db->prepare("
                    UPDATE fee_items 
                    SET name = :name, course_id = :course_id, type = :type, 
                        amount = :amount, installments = :installments, 
                        late_fine_per_day = :late_fine, is_active = :is_active
                    WHERE id = :id AND tenant_id = :tid
                ");

                $stmt->execute([
                    'id' => $id,
                    'tid' => $tenantId,
                    'course_id' => $courseId,
                    'name' => $name,
                    'type' => $type,
                    'amount' => $amount,
                    'installments' => $installments,
                    'late_fine' => $lateFinePerDay,
                    'is_active' => $isActive
                ]);

                echo json_encode([
                    'success' => true, 
                    'message' => 'Fee item updated successfully'
                ]);
            }
        } 
        else if ($action === 'delete') {
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception("Fee item ID is required");
            }

            // Check if there are fee records
            $stmt = $db->prepare("SELECT COUNT(*) FROM fee_records WHERE fee_item_id = :id");
            $stmt->execute(['id' => $id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                // Soft delete - just mark as inactive
                $stmt = $db->prepare("UPDATE fee_items SET is_active = 0 WHERE id = :id AND tenant_id = :tid");
                $stmt->execute(['id' => $id, 'tid' => $tenantId]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Fee item deactivated (has existing fee records)'
                ]);
            } else {
                // Hard delete
                $stmt = $db->prepare("DELETE FROM fee_items WHERE id = :id AND tenant_id = :tid");
                $stmt->execute(['id' => $id, 'tid' => $tenantId]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Fee item deleted successfully'
                ]);
            }
        }
        else if ($action === 'toggle') {
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception("Fee item ID is required");
            }

            $stmt = $db->prepare("UPDATE fee_items SET is_active = NOT is_active WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);

            echo json_encode([
                'success' => true, 
                'message' => 'Fee item status updated'
            ]);
        }
        else if ($action === 'record_payment') {
            $financeService = new \App\Services\FinanceService();
            $result = $financeService->recordPayment($input, $tenantId);

            if ($result['success']) {
                $transactionId = $result['transaction_id'];
                $receiptNo = $result['receipt_no'];

                // 1. Dispatch Background Job or Send Synchronously
                $syncEmail = $input['sync_email'] ?? false;
                $emailStatus = 'queued';
                $jobId = null;

                if ($syncEmail) {
                    try {
                        $pdfPath = ReceiptHelper::generatePdf($db, $tenantId, $transactionId, $receiptNo);
                        
                        // Fetch student info for template (with course/batch JOINs AND financial summary)
                        $stmt = $db->prepare("SELECT u.name as name, u.email as email, s.roll_no,
                                                     c.name as course_name, b.name as batch_name,
                                                     sfs.total_fee, sfs.paid_amount, sfs.due_amount
                                            FROM students s LEFT JOIN users u ON s.user_id = u.id
                                            LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                                            LEFT JOIN batches b ON e.batch_id = b.id
                                            LEFT JOIN courses c ON b.course_id = c.id
                                            LEFT JOIN student_fee_summary sfs ON s.id = sfs.student_id
                                            WHERE s.id = ?");
                        $stmt->execute([$result['fee_record']['student_id']]);
                        $student = $stmt->fetch();

                        if ($student && !empty($student['email'])) {
                            $sent = MailHelper::sendPaymentReceiptEmail($db, $tenantId, [
                                'transaction_id' => $transactionId,
                                'receipt_no' => $receiptNo,
                                'student_id' => $result['fee_record']['student_id'],
                                'student_name' => $student['name'],
                                'email' => $student['email'],
                                'roll_no' => $student['roll_no'] ?? '',
                                'course_name' => $student['course_name'] ?? '',
                                'batch_name' => $student['batch_name'] ?? '',
                                'amount' => $result['amount_paid'] ?? 0,
                                'course_fee' => $student['total_fee'] ?? 0,
                                'previous_payments' => (float)($student['paid_amount'] ?? 0) - (float)($result['amount_paid'] ?? 0),
                                'balance' => $student['due_amount'] ?? 0,
                                'paid_date' => date('Y-m-d'),
                                'payment_mode' => $result['payment_method'] ?? 'cash',
                                'login_url' => (defined('APP_URL') ? APP_URL : '') . '/?page=login'
                            ], $pdfPath);
                            $emailStatus = $sent ? 'sent' : 'failed';
                        }
                    } catch (\Exception $e) {
                        $emailStatus = 'failed';
                    }
                } else {
                    $queueService = new QueueService();
                    $studentId = $result['fee_record']['student_id'];
                    // Fetch student and payment details
                    $stdStmt = $db->prepare("SELECT u.name as student_name, u.email as student_email, s.roll_no, c.name as course_name, b.name as batch_name, sfs.total_fee, sfs.paid_amount, sfs.due_amount FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id LEFT JOIN courses c ON b.course_id = c.id LEFT JOIN student_fee_summary sfs ON s.id = sfs.student_id WHERE s.id = :sid");
                    $stdStmt->execute(['sid' => $studentId]);
                    $studentInfo = $stdStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $jobId = $queueService->dispatch('payment_receipt', [
                        'transaction_id' => $transactionId,
                        'receipt_no' => $receiptNo,
                        'student_id' => $studentId,
                        'student_name' => $studentInfo['student_name'] ?? 'Student',
                        'student_email' => $studentInfo['student_email'] ?? '',
                        'roll_no' => $studentInfo['roll_no'] ?? '',
                        'course_name' => $studentInfo['course_name'] ?? '',
                        'batch_name' => $studentInfo['batch_name'] ?? '',
                        'amount' => $result['amount_paid'] ?? 0,
                        'course_fee' => $studentInfo['total_fee'] ?? 0,
                        'previous_payments' => (float)($studentInfo['paid_amount'] ?? 0) - (float)($result['amount_paid'] ?? 0),
                        'balance' => $studentInfo['due_amount'] ?? 0,
                        'paid_date' => date('Y-m-d'),
                        'payment_mode' => $result['payment_method'] ?? 'cash',
                        'login_url' => (defined('APP_URL') ? APP_URL : '') . '/?page=login'
                    ], $tenantId);
                }

                echo json_encode([
                    'success' => true, 
                    'message' => 'Payment recorded successfully!' . ($syncEmail ? " Email $emailStatus." : " Receipt is being prepared."),
                    'data' => [
                        'receipt_no' => $receiptNo,
                        'transaction_id' => $transactionId,
                        'job_id' => $jobId,
                        'amount_paid' => $result['amount_paid'],
                        'student_name' => $result['student_name'],
                        'email_status' => $emailStatus,
                        'student_id' => $result['fee_record']['student_id'],
                        'redirect_url' => '?page=fee-details&receipt_no=' . $receiptNo
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to record payment']);
            }
            exit;
        }
        else if ($action === 'record_bulk_payment') {
            $data = $input;
            $financeService = new \App\Services\FinanceService();
            $result = $financeService->recordBulkPayment($data, $tenantId);
            
            if ($result['success']) {
                $transactionId = $result['transaction_ids'][0] ?? null;
                $receiptNo = $result['receipt_no'];

                // 1. Dispatch Background Job or Send Synchronously
                $syncEmail = $input['sync_email'] ?? false;
                $emailStatus = 'queued';
                $jobId = null;

                if ($syncEmail) {
                    try {
                        $pdfPath = ReceiptHelper::generatePdf($db, $tenantId, $transactionId, $receiptNo);
                        
                        // Fetch student info (with course/batch JOINs AND summary)
                        $stmt = $db->prepare("SELECT u.name as name, u.email as email, s.roll_no,
                                                     c.name as course_name, b.name as batch_name,
                                                     sfs.total_fee, sfs.paid_amount, sfs.due_amount
                                            FROM students s LEFT JOIN users u ON s.user_id = u.id
                                            LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                                            LEFT JOIN batches b ON e.batch_id = b.id
                                            LEFT JOIN courses c ON b.course_id = c.id
                                            LEFT JOIN student_fee_summary sfs ON s.id = sfs.student_id
                                            WHERE s.id = ?");
                        $stmt->execute([$data['student_id']]);
                        $student = $stmt->fetch();

                        if ($student && !empty($student['email'])) {
                            $sent = MailHelper::sendPaymentReceiptEmail($db, $tenantId, [
                                'transaction_id' => $transactionId,
                                'receipt_no' => $receiptNo,
                                'student_id' => $data['student_id'],
                                'student_name' => $student['name'],
                                'email' => $student['email'],
                                'roll_no' => $student['roll_no'] ?? '',
                                'course_name' => $student['course_name'] ?? '',
                                'batch_name' => $student['batch_name'] ?? '',
                                'amount' => $result['amount_paid'] ?? 0,
                                'course_fee' => $student['total_fee'] ?? 0,
                                'previous_payments' => (float)($student['paid_amount'] ?? 0) - (float)($result['amount_paid'] ?? 0),
                                'balance' => $student['due_amount'] ?? 0,
                                'paid_date' => date('Y-m-d'),
                                'payment_mode' => $data['payment_mode'] ?? ($result['payment_method'] ?? 'cash'),
                                'login_url' => (defined('APP_URL') ? APP_URL : '') . '/?page=login'
                            ], $pdfPath);
                            $emailStatus = $sent ? 'sent' : 'failed';
                        }
                    } catch (\Exception $e) {
                        $emailStatus = 'failed';
                    }
                } else {
                    $queueService = new QueueService();
                    $studentId = $data['student_id'];
                    // Fetch student and payment details
                    $stdStmt = $db->prepare("SELECT u.name as student_name, u.email as student_email, s.roll_no, c.name as course_name, b.name as batch_name, sfs.total_fee, sfs.paid_amount, sfs.due_amount FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id LEFT JOIN courses c ON b.course_id = c.id LEFT JOIN student_fee_summary sfs ON s.id = sfs.student_id WHERE s.id = :sid");
                    $stdStmt->execute(['sid' => $studentId]);
                    $studentInfo = $stdStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $jobId = $queueService->dispatch('payment_receipt', [
                        'transaction_id' => $transactionId,
                        'receipt_no' => $receiptNo,
                        'student_id' => $studentId,
                        'student_name' => $studentInfo['student_name'] ?? 'Student',
                        'student_email' => $studentInfo['student_email'] ?? '',
                        'roll_no' => $studentInfo['roll_no'] ?? '',
                        'course_name' => $studentInfo['course_name'] ?? '',
                        'batch_name' => $studentInfo['batch_name'] ?? '',
                        'amount' => $result['amount_paid'] ?? 0,
                        'course_fee' => $studentInfo['total_fee'] ?? 0,
                        'previous_payments' => (float)($studentInfo['paid_amount'] ?? 0) - (float)($result['amount_paid'] ?? 0),
                        'balance' => $studentInfo['due_amount'] ?? 0,
                        'paid_date' => date('Y-m-d'),
                        'payment_mode' => $result['payment_method'] ?? 'cash',
                        'login_url' => (defined('APP_URL') ? APP_URL : '') . '/?page=login'
                    ], $tenantId);
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Bulk payment recorded!' . ($syncEmail ? " Email $emailStatus." : " Receipt is being prepared."),
                    'data' => [
                        'receipt_no' => $receiptNo,
                        'transaction_ids' => $result['transaction_ids'],
                        'job_id' => $jobId,
                        'amount_paid' => $result['amount_paid'],
                        'student_name' => $result['student_name'] ?? 'Student',
                        'email_status' => $emailStatus
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Failed to record bulk payment']);
            }
            exit;
        }
        else if ($action === 'send_payment_email') {
            $transactionId = $input['transaction_id'] ?? null;
            if (!$transactionId) throw new Exception("Transaction ID is required");

            $stmt = $db->prepare("SELECT pt.id, pt.student_id, pt.receipt_number as receipt_no, pt.amount, pt.payment_method, pt.payment_date,
                u.name as student_name, u.email as student_email, s.roll_no,
                c.name as course_name, b.name as batch_name
                FROM payment_transactions pt 
                LEFT JOIN students s ON pt.student_id = s.id JOIN users u ON s.user_id = u.id 
                LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id
                LEFT JOIN courses c ON b.course_id = c.id
                WHERE pt.id = :tid AND pt.tenant_id = :tenant");
            $stmt->execute(['tid' => $transactionId, 'tenant' => $tenantId]);
            $txn = $stmt->fetch();

            if ($txn) {
                $queue = new QueueService();
                $queue->dispatch('payment_receipt', [
                    'transaction_id' => $txn['id'],
                    'receipt_no' => $txn['receipt_no'],
                    'student_id' => $txn['student_id'],
                    'student_name' => $txn['student_name'] ?? 'Student',
                    'student_email' => $txn['student_email'] ?? '',
                    'roll_no' => $txn['roll_no'] ?? '',
                    'course_name' => $txn['course_name'] ?? '',
                    'batch_name' => $txn['batch_name'] ?? '',
                    'amount' => $txn['amount'] ?? 0,
                    'course_fee' => $txn['course_fee'] ?? 0,
                    'previous_payments' => (float)($txn['paid_amount'] ?? 0) - (float)($txn['amount'] ?? 0),
                    'balance' => $txn['due_amount'] ?? 0,
                    'paid_date' => !empty($txn['payment_date']) ? date('Y-m-d', strtotime($txn['payment_date'])) : date('Y-m-d'),
                    'payment_mode' => $txn['payment_method'] ?? 'cash',
                    'login_url' => (defined('APP_URL') ? APP_URL : '') . '/?page=login'
                ], $tenantId);
            }

            echo json_encode(['success' => true, 'message' => 'Email receipt request has been queued in the background.']);
            exit;
        }
        else if ($action === 'generate_fees_on_enroll') {
            $studentId = $input['student_id'] ?? null;
            $batchId = $input['batch_id'] ?? null;
            $courseId = $input['course_id'] ?? null;

            if (!$studentId || !$batchId || !$courseId) {
                throw new Exception("Student, Batch and Course IDs are required");
            }

            $calculationService->generateFeesForEnrollment($studentId, $batchId, $courseId, $tenantId);
            echo json_encode(['success' => true, 'message' => 'Fees generated successfully']);
            exit;
        }
        else if ($action === 'update_payment') {
            // 'update_payment' is superseded by 'edit_payment'.
            // Redirect to edit_payment for backward compatibility.
            $input['action'] = 'edit_payment';
            // fall through intentionally — continue to edit_payment block below
            // (handled by routing the action key; clients should use edit_payment directly)
            echo json_encode(['success' => false, 'message' => 'Use action=edit_payment to update a payment record.']);
            exit;
        }
        // Moved GET endpoints to the $method === 'GET' block above
        else if ($action === 'edit_payment') {
            $transactionId = $input['transaction_id'] ?? null;
            $amountPaid = floatval($input['amount_paid'] ?? 0);
            $paidDate = $input['paid_date'] ?? date('Y-m-d');
            $paymentMode = $input['payment_mode'] ?? 'cash';
            $notes = $input['notes'] ?? null;
            $resendEmail = !empty($input['resend_email']);

            if (!$transactionId) throw new Exception("Transaction ID required");
            if ($amountPaid <= 0) throw new Exception("Amount must be greater than 0");

            $txn = $transactionModel->find($transactionId);
            if (!$txn || $txn['tenant_id'] != $tenantId) throw new Exception("Transaction not found");

            $feeRecordId = $txn['fee_record_id'];
            $feeRecord = $feeRecordModel->find($feeRecordId);

            $receiptPath = $txn['receipt_path'];
            // Removing image upload from edit_payment per user request

            $amountDiff = $amountPaid - floatval($txn['amount']);
            $newAmountPaidTotal = floatval($feeRecord['amount_paid']) + $amountDiff;
            $totalAmountDue = floatval($feeRecord['amount_due']) + floatval($feeRecord['fine_applied']);
            
            $isOverdue = (strtotime($feeRecord['due_date']) < time());
            $status = ($newAmountPaidTotal >= $totalAmountDue) ? 'paid' : ($newAmountPaidTotal > 0 ? 'partial' : ($isOverdue ? 'overdue' : 'pending'));

            $stmt = $db->prepare("UPDATE fee_records SET amount_paid = amount_paid + :diff, status = :status WHERE id = :fid");
            $stmt->execute(['diff' => $amountDiff, 'status' => $status, 'fid' => $feeRecordId]);

            $stmt = $db->prepare("UPDATE payment_transactions SET amount = :amt, payment_date = :pdate, payment_method = :pmode, receipt_path = :rpath, notes = :notes WHERE id = :tid");
            $stmt->execute(['amt' => $amountPaid, 'pdate' => $paidDate, 'pmode' => $paymentMode, 'rpath' => $receiptPath, 'notes' => $notes, 'tid' => $transactionId]);

            $stmt = $db->prepare("SELECT u.name as student_name, u.email, c.name as course_name, b.name as batch_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id LEFT JOIN courses c ON b.course_id = c.id WHERE s.id = :sid AND s.tenant_id = :tid");
            $stmt->execute(['sid' => $txn['student_id'], 'tid' => $tenantId]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT name FROM tenants WHERE id = :tid");
            $stmt->execute(['tid' => $tenantId]);
            $institute = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($resendEmail) {
                $queue = new QueueService();
                // Fetch complete student and payment details
                $stdStmt = $db->prepare("SELECT u.name as student_name, u.email as student_email, s.roll_no, c.name as course_name, b.name as batch_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id LEFT JOIN courses c ON b.course_id = c.id WHERE s.id = :sid");
                $stdStmt->execute(['sid' => $txn['student_id']]);
                $studentInfo = $stdStmt->fetch(PDO::FETCH_ASSOC);
                
                $queue->dispatch('payment_receipt', [
                    'transaction_id' => $transactionId,
                    'receipt_no' => $txn['receipt_number'],
                    'student_id' => $txn['student_id'],
                    'student_name' => $studentInfo['student_name'] ?? 'Student',
                    'student_email' => $studentInfo['student_email'] ?? '',
                    'roll_no' => $studentInfo['roll_no'] ?? '',
                    'course_name' => $studentInfo['course_name'] ?? '',
                    'batch_name' => $studentInfo['batch_name'] ?? '',
                    'amount' => $amountPaid,
                    'paid_date' => $paidDate,
                    'payment_mode' => $paymentMode,
                    'login_url' => (defined('APP_URL') ? APP_URL : '') . '/?page=login'
                ], $tenantId);
            }

            // $resendEmail is already defined above; use it to build the status message
            $emailQueued = $resendEmail;
            echo json_encode(['success' => true, 'message' => 'Payment updated successfully' . ($emailQueued ? ' and email receipt queued.' : '.')]);
        }
        else if ($action === 'delete_payment') {
            $transactionId = $input['transaction_id'] ?? null;
            if (!$transactionId) throw new Exception("Transaction ID required");

            $txn = $transactionModel->find($transactionId);
            if (!$txn || $txn['tenant_id'] != $tenantId) throw new Exception("Transaction not found");

            $feeRecordId = $txn['fee_record_id'];
            $feeRecord = $feeRecordModel->find($feeRecordId);
            
            $amountDiff = -floatval($txn['amount']);
            $newAmountPaidTotal = floatval($feeRecord['amount_paid']) + $amountDiff;
            $totalAmountDue = floatval($feeRecord['amount_due']) + floatval($feeRecord['fine_applied']);
            $isOverdue = (strtotime($feeRecord['due_date']) < time());
            $status = ($newAmountPaidTotal >= $totalAmountDue) ? 'paid' : ($newAmountPaidTotal > 0 ? 'partial' : ($isOverdue ? 'overdue' : 'pending'));
            
            $stmt = $db->prepare("UPDATE fee_records SET amount_paid = amount_paid + :diff, status = :status WHERE id = :fid");
            $stmt->execute(['diff' => $amountDiff, 'status' => $status, 'fid' => $feeRecordId]);

            $stmt = $db->prepare("DELETE FROM payment_transactions WHERE id = :tid");
            $stmt->execute(['tid' => $transactionId]);

            echo json_encode(['success' => true, 'message' => 'Payment deleted and ledger reverted']);
        }
        else {
            throw new Exception("Invalid action: " . $action);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (\Throwable $e) {
    ob_end_clean(); // Discard any stray output before sending JSON error
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
