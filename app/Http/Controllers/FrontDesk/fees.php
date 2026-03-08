<?php
/**
 * Front Desk — Fee Collection API
 * Handles recording payments, generating receipts, and sending emails.
 */

require_once __DIR__ . '/../../../../config/config.php';
require_once app_path('Http/Middleware/FrontDeskMiddleware.php');
require_once base_path('vendor/autoload.php');

use App\Http\Middleware\FrontDeskMiddleware;
use App\Services\QueueService;
use App\Helpers\ReceiptHelper;

header('Content-Type: application/json');

// Check authentication and get tenant/user info
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];
$userId = $auth['user_id'];

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'get_outstanding';
        
        if ($action === 'get_outstanding') {
            $studentId = $_GET['student_id'] ?? null;
            if (!$studentId) throw new Exception("Student ID required");
            
            $stmt = $db->prepare("
                SELECT fr.*, fi.name as fee_name, fi.type as fee_type
                FROM fee_records fr
                JOIN fee_items fi ON fr.fee_item_id = fi.id
                WHERE fr.student_id = :sid AND fr.tenant_id = :tid 
                AND fr.amount_due > fr.amount_paid
                ORDER BY fr.due_date ASC
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $data]);
        }
        else if ($action === 'get_recent_payments') {
            $query = "SELECT pt.*, fi.name as fee_item_name, s.full_name as student_name, pt.receipt_number as receipt_no, pt.amount as amount_paid
                      FROM payment_transactions pt
                      JOIN fee_records fr ON pt.fee_record_id = fr.id
                      JOIN fee_items fi ON fr.fee_item_id = fi.id
                      JOIN students s ON pt.student_id = s.id
                      WHERE pt.tenant_id = :tid
                      ORDER BY pt.payment_date DESC, pt.id DESC
                      LIMIT 50";
            $stmt = $db->prepare($query);
            $stmt->execute(['tid' => $tenantId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $data]);
        }
        else if ($action === 'get_payment_details') {
            $transactionId = $_GET['transaction_id'] ?? null;
            $receiptNo = $_GET['receipt_no'] ?? null;
            if (!$transactionId && !$receiptNo) throw new Exception("Transaction ID or Receipt Number required");
            
            $where = $transactionId ? "pt.id = :tid" : "pt.receipt_number = :rno";
            $params = ['tenant' => $tenantId];
            if ($transactionId) $params['tid'] = $transactionId;
            else $params['rno'] = $receiptNo;

            $query = "
                SELECT pt.*, s.full_name as student_name, s.id as student_id,
                       t.name as institute_name, t.address as institute_address, 
                       t.phone as institute_contact, t.email as institute_email,
                       (SELECT pdf_path FROM payment_receipts WHERE receipt_no = pt.receipt_number LIMIT 1) as pdf_url
                FROM payment_transactions pt
                JOIN students s ON pt.student_id = s.id
                JOIN tenants t ON pt.tenant_id = t.id
                WHERE $where AND pt.tenant_id = :tenant
            ";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) throw new Exception("Payment record not found");
            
            // Normalize pdf_url path
            if (!empty($data['pdf_url'])) {
                $data['pdf_url'] = str_replace(ABS_PATH . '/', '', $data['pdf_url']);
            }

            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }
        else if ($action === 'generate_receipt_html') {
            $receiptNo = $_GET['receipt_no'] ?? null;
            if (!$receiptNo) throw new Exception("Receipt Number required");

            $html = ReceiptHelper::getHtml($db, $tenantId, null, $receiptNo);
            if (!$html) throw new Exception("Receipt not found");

            if (!empty($_GET['is_pdf'])) {
                $pdfPath = ReceiptHelper::generatePdf($db, $tenantId, null, $receiptNo);
                if (!$pdfPath) throw new Exception("Failed to generate PDF");
                
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="Receipt_' . $receiptNo . '.pdf"');
                readfile($pdfPath);
                exit;
            }

            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        else if ($action === 'job_status') {
            $jobId = $_GET['job_id'] ?? null;
            if (!$jobId) throw new Exception("Job ID required");

            $stmt = $db->prepare("SELECT id, status, job_type, error_message FROM job_queue WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$jobId, $tenantId]);
            $job = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$job) throw new Exception("Job not found");
            echo json_encode(['success' => true, 'data' => $job]);
            exit;
        }
        else if ($action === 'get_receipt_details') {
            $receiptNo = $_GET['receipt_no'] ?? null;
            if (!$receiptNo) throw new Exception("Receipt number required");

            $stmt = $db->prepare("
                SELECT pt.*, s.full_name as student_name, s.roll_no, 
                       (SELECT pdf_path FROM payment_receipts WHERE payment_id = pt.id LIMIT 1) as pdf_path
                FROM payment_transactions pt
                JOIN students s ON pt.student_id = s.id
                WHERE pt.receipt_number = ? AND pt.tenant_id = ?
            ");
            $stmt->execute([$receiptNo, $tenantId]);
            $receipt = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$receipt) throw new Exception("Receipt not found");
            echo json_encode(['success' => true, 'data' => $receipt]);
            exit;
        }
    } 
    elseif ($method === 'POST') {
        // CSRF Protection for all state-changing actions
        \App\Helpers\CsrfHelper::requireCsrfToken();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $input['action'] ?? '';
        
        if ($action === 'record_payment') {
            $financeService = new FinanceService();
            $result = $financeService->recordBulkPayment($tenantId, $input);
            
            if ($result['success']) {
                $transactionId = $result['transaction_ids'][0] ?? null;
                $receiptNo = $result['receipt_no'] ?? 'REC-' . time();

                // 1. Dispatch Background Job (Instant <1ms)
                $queueService = new QueueService();
                $jobId = $queueService->dispatch('payment_receipt', [
                    'transaction_id' => $transactionId,
                    'receipt_no' => $receiptNo,
                    'student_id' => $input['student_id']
                ], $tenantId);

                echo json_encode([
                    'success' => true,
                    'data' => [
                        'receipt_no' => $receiptNo,
                        'transaction_id' => $transactionId,
                        'job_id' => $jobId,
                        'redirect_url' => '?page=fee-details&receipt_no=' . $receiptNo
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
            exit;
        }
        else if ($action === 'trigger_email') {
            $receiptNo = $input['receipt_no'] ?? null;
            if (!$receiptNo) throw new Exception("Receipt number required");

            $stmt = $db->prepare("SELECT id, student_id FROM payment_transactions WHERE receipt_number = ? AND tenant_id = ?");
            $stmt->execute([$receiptNo, $tenantId]);
            $txn = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$txn) throw new Exception("Payment not found");

            $queueService = new QueueService();
            $jobId = $queueService->dispatch('payment_receipt', [
                'transaction_id' => $txn['id'],
                'receipt_no' => $receiptNo,
                'student_id' => $txn['student_id']
            ], $tenantId);

            echo json_encode(['success' => true, 'job_id' => $jobId]);
            exit;
        }
        else if ($action === 'send_receipt_email' || $action === 'send_email_receipt') {
            $receiptNo = $input['receipt_no'] ?? $_GET['receipt_no'] ?? null;
            if (!$receiptNo) throw new Exception("Receipt number required");

            $stmt = $db->prepare("
                SELECT pt.*, s.full_name, COALESCE(NULLIF(s.email, ''), u.email) as email 
                FROM payment_transactions pt
                JOIN students s ON pt.student_id = s.id
                LEFT JOIN users u ON s.user_id = u.id
                WHERE pt.receipt_number = :rno AND pt.tenant_id = :tid
            ");
            $stmt->execute(['rno' => $receiptNo, 'tid' => $tenantId]);
            $pay = $stmt->fetch();

            if (!$pay) throw new Exception("Payment record not found");
            if (empty($pay['email'])) throw new Exception("Student has no email address on file.");

            $queueService = new QueueService();
            $queueService->dispatch('send_email_receipt', [
                'tenant_id' => $tenantId,
                'student_id' => $pay['student_id'],
                'recipient_email' => $pay['email'],
                'recipient_name' => $pay['full_name'],
                'receipt_no' => $receiptNo
            ]);

            echo json_encode(['success' => true, 'message' => 'Email receipt has been queued.']);
            exit;
        }
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
