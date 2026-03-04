<?php
/**
 * Front Desk — Fee Collection API
 * Handles recording payments, generating receipts, and sending emails.
 */

require_once __DIR__ . '/../../../config/config.php';
require_once app_path('Http/Middleware/FrontDeskMiddleware.php');
require_once base_path('vendor/autoload.php');

use App\Http\Middleware\FrontDeskMiddleware;
use App\Services\FinanceService;
use App\Helpers\MailHelper;
use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json');

// Check authentication and get tenant/user info
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];
$userId = $auth['user_id'];

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Helper for generating PDF receipts (identical to Admin logic)
if (!function_exists('getReceiptHtmlString')) {
    function getReceiptHtmlString($db, $tenantId, $receiptNo) {
        $query = "
            SELECT pt.*, fr.fee_item_id, fi.name as fee_item_name, fi.amount as fee_item_amount,
                   s.full_name as student_name, COALESCE(NULLIF(s.email, ''), u.email) as student_email, s.phone,
                   COALESCE(JSON_UNQUOTE(JSON_EXTRACT(s.permanent_address, '$.district')), '') as student_address,
                   s.roll_no, c.name as course_name, b.name as batch_name,
                   fr.amount_due, fr.amount_paid as record_paid, fr.fine_applied,
                   t.name as institute_name, t.address as institute_address,
                   t.phone as institute_contact, t.email as institute_email,
                   t.logo_path as institute_logo
            FROM payment_transactions pt
            JOIN fee_records fr ON pt.fee_record_id = fr.id
            JOIN fee_items fi ON fr.fee_item_id = fi.id
            JOIN students s ON pt.student_id = s.id
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN batches b ON s.batch_id = b.id
            LEFT JOIN courses c ON b.course_id = c.id
            LEFT JOIN tenants t ON pt.tenant_id = t.id
            WHERE pt.tenant_id = :tenant AND pt.receipt_number = :rno
        ";

        $stmt = $db->prepare($query);
        $stmt->execute(['tenant' => $tenantId, 'rno' => $receiptNo]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$transactions) return "";
        
        $txn = $transactions[0];
        $logoPath = $txn['institute_logo'] ?? '';
        $logoUrl = '';
        if ($logoPath) {
            if (strpos($logoPath, '/uploads/') === 0 && strpos($logoPath, '/public/') !== 0) {
                $logoPath = '/public' . $logoPath;
            }
            $logoUrl = (defined('APP_URL') ? APP_URL : '') . $logoPath;
        }

        $totalPaid = 0;
        $items = [];
        foreach ($transactions as $t) {
            $totalPaid += floatval($t['amount']);
            $items[] = [
                'name' => $t['fee_item_name'],
                'amount' => $t['amount']
            ];
        }

        $receiptData = [
            'institute_name'    => $txn['institute_name'] ?? 'Institute',
            'institute_address' => $txn['institute_address'] ?? '',
            'institute_contact' => $txn['institute_contact'] ?? '',
            'institute_email'   => $txn['institute_email'] ?? '',
            'institute_logo_url'=> $logoUrl,
            'receipt_no'        => $txn['receipt_number'],
            'date_ad'           => $txn['payment_date'],
            'date_bs'           => '', 
            'student_name'      => $txn['student_name'],
            'student_email'     => $txn['student_email'] ?? '',
            'course_name'       => $txn['course_name'] ?? '',
            'batch_name'        => $txn['batch_name'] ?? '',
            'course_fee'        => floatval($txn['amount_due']),
            'paid_amount'       => $totalPaid,
            'remaining'         => max(0, floatval($txn['amount_due']) - floatval($txn['record_paid'])),
            'fine_amount'       => $txn['fine_applied'] ?? 0,
            'address'           => $txn['student_address'] ?? '',
            'contact_number'    => $txn['phone'] ?? '',
            'payment_mode'      => $txn['payment_method'],
            'transaction_id'    => $txn['id'],
            'remarks'           => $txn['notes'] ?? '',
            'items'             => $items,
            'is_email'          => true
        ];

        ob_start();
        $isDownload = true;
        require base_path('scripts/receipt_template.php');
        return ob_get_clean();
    }
}

if (!function_exists('getReceiptPdfPath')) {
    function getReceiptPdfPath($db, $tenantId, $receiptNo) {
        $html = getReceiptHtmlString($db, $tenantId, $receiptNo);
        if (!$html) return null;

        $pdfDir = base_path('public/uploads/receipts/');
        if (!is_dir($pdfDir)) mkdir($pdfDir, 0777, true);
        
        $filename = 'receipt_' . $receiptNo . '.pdf';
        $pdfPath = $pdfDir . $filename;

        try {
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            file_put_contents($pdfPath, $dompdf->output());
            return $pdfPath;
        } catch (\Exception $e) {
            error_log("FrontDesk Fee PDF Error: " . $e->getMessage());
            return null;
        }
    }
}

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
        else if ($action === 'generate_receipt_html') {
            $receiptNo = $_GET['receipt_no'] ?? null;
            if (!$receiptNo) throw new Exception("Receipt Number required");
            
            $html = getReceiptHtmlString($db, $tenantId, $receiptNo);
            if (!$html) throw new Exception("Payment not found");
            
            if (!empty($_GET['is_pdf'])) {
                $pdfPath = getReceiptPdfPath($db, $tenantId, $receiptNo);
                if ($pdfPath && file_exists($pdfPath)) {
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="Receipt_' . $receiptNo . '.pdf"');
                    readfile($pdfPath);
                    exit;
                }
            }

            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $input['action'] ?? 'record_payment';
        
        if ($action === 'record_payment') {
            $financeService = new FinanceService();
            $result = $financeService->recordBulkPayment($input, $tenantId);
            
            if ($result['success']) {
                $receiptNo = $result['receipt_no'];
                $amountPaid = $result['amount_paid'];
                
                // 1. Fetch Transaction ID for linking
                $transactionId = null;
                $stmt = $db->prepare("SELECT id FROM payment_transactions WHERE receipt_number = :rno AND tenant_id = :tid ORDER BY id DESC LIMIT 1");
                $stmt->execute(['rno' => $receiptNo, 'tid' => $tenantId]);
                $txnRow = $stmt->fetch(PDO::FETCH_ASSOC);
                $transactionId = $txnRow ? $txnRow['id'] : null;

                // 2. Generate PDF (using Admin-parity path)
                $pdfPath = getReceiptPdfPath($db, $tenantId, $receiptNo);
                
                // 3. Email Logic
                $emailStatus = 'failed';
                $stmt = $db->prepare("
                    SELECT s.full_name as name, COALESCE(NULLIF(s.email, ''), u.email) as email 
                    FROM students s 
                    LEFT JOIN users u ON s.user_id = u.id 
                    WHERE s.id = :sid AND s.tenant_id = :tid
                ");
                $stmt->execute(['sid' => $input['student_id'], 'tid' => $tenantId]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (empty($student['email'])) {
                    $emailStatus = 'no_email';
                } elseif ($pdfPath && file_exists($pdfPath)) {
                    $sent = MailHelper::sendPaymentReceiptEmail(
                        $db, $tenantId, $student['email'], $student['name'], 
                        $receiptNo, $pdfPath, null, $amountPaid
                    );
                    $emailStatus = $sent ? 'sent' : 'failed';
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment recorded successfully!',
                    'data' => [
                        'receipt_no' => $receiptNo,
                        'transaction_id' => $transactionId,
                        'amount_paid' => $amountPaid,
                        'email_status' => $emailStatus,
                        'student_name' => $student['name'] ?? 'Student'
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to record payment']);
            }
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
