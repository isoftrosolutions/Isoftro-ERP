<?php
/**
 * Payment Background Worker
 * This script processes the 'job_queue' table.
 * Usage: php scripts/payment_worker.php
 */

require_once __DIR__ . '/../config/config.php';

if (!function_exists('base_path')) {
    function base_path($path = '') {
        return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}

require_once base_path('vendor/autoload.php');

use App\Services\QueueService;
use App\Helpers\MailHelper;
use Dompdf\Dompdf;
use Dompdf\Options;

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting Payment Worker...\n";

$db = getDBConnection();
$queueService = new QueueService();

// Loop settings
$maxIterations = 100; // Run for X jobs then restart (to prevent memory leaks)
$iteration = 0;

while ($iteration < $maxIterations) {
    $jobs = $queueService->getPendingJobs(5);
    
    if (empty($jobs)) {
        echo ".";
        sleep(2);
        continue;
    }

    foreach ($jobs as $job) {
        $jobId = $job['id'];
        $tenantId = $job['tenant_id'];
        $payload = json_decode($job['payload'], true);
        $jobType = $job['job_type'];

        echo "\nProcessing Job #$jobId ($jobType)... ";

        try {
            if ($jobType === 'payment_receipt') {
                processPaymentReceipt($db, $tenantId, $payload);
            } else {
                // Handle all other email-related jobs generically via MailHelper
                MailHelper::processJob($db, $tenantId, $jobType, $payload);
            }
            
            $queueService->updateStatus($jobId, 'completed');
            echo "Done.";
        } catch (\Throwable $e) {

            $queueService->updateStatus($jobId, 'failed', $e->getMessage());
            echo "Failed: " . $e->getMessage();
        }
    }
    
    $iteration++;
}

echo "\nWorker cycle finished.\n";

/**
 * Worker Logic for PDF generation and initial email
 */
function processPaymentReceipt($db, $tenantId, $payload) {
    $transactionId = $payload['transaction_id'] ?? null;
    $receiptNo = $payload['receipt_no'] ?? null;

    if (!$transactionId) return;

    // 1. Generate PDF
    $pdfPath = generateReceiptPdf($db, $tenantId, $transactionId, $receiptNo);
    
    if ($pdfPath) {
        $relativePath = 'uploads/receipts/' . basename($pdfPath);
        
        // 2. Update DB directly - update receipt_path in payment_transactions
        $stmt = $db->prepare("UPDATE payment_transactions SET receipt_path = :path WHERE id = :id");
        $stmt->execute(['path' => $relativePath, 'id' => $transactionId]);

        // 3. Trigger Email
        processEmailReceipt($db, $tenantId, array_merge($payload, ['pdf_path' => $pdfPath]));
    }
}

/**
 * Worker Logic for Emailing
 */
function processEmailReceipt($db, $tenantId, $payload) {
    $transactionId = $payload['transaction_id'] ?? null;
    $receiptNo = $payload['receipt_no'] ?? null;
    $pdfPath = $payload['pdf_path'] ?? null;

    if (!$transactionId && !$receiptNo) {
        echo "[Error] No transaction_id or receipt_no in payload.";
        return;
    }

    // Fetch details with JOINS to ensure placeholders like course_name, amount_due, etc. are available
    $query = "
        SELECT pt.*, u.name as name, u.email as email,
               c.name as course_name, b.name as batch_name,
               fr.amount_due, fr.amount_paid as fr_amount_paid, fr.fine_applied
        FROM payment_transactions pt
        JOIN students s ON pt.student_id = s.id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN enrollments en ON s.id = en.student_id AND en.status = 'active'
        LEFT JOIN batches b ON en.batch_id = b.id
        LEFT JOIN courses c ON b.course_id = c.id
        LEFT JOIN fee_records fr ON pt.fee_record_id = fr.id
        WHERE ";
    
    if ($transactionId) {
        $query .= "pt.id = :tid AND pt.tenant_id = :tenant";
        $stmt = $db->prepare($query);
        $stmt->execute(['tid' => $transactionId, 'tenant' => $tenantId]);
    } else {
        $query .= "pt.receipt_number = :rno AND pt.tenant_id = :tenant";
        $stmt = $db->prepare($query);
        $stmt->execute(['rno' => $receiptNo, 'tenant' => $tenantId]);
    }
    $txn = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$txn) {
        echo "[Error] Transaction not found for ID: $transactionId or No: $receiptNo";
        return;
    }

    if (empty($txn['email'])) {
        echo "[Warning] Student {$txn['name']} has no email. Skipping.";
        return;
    }
    
    // Re-assign transactionId for subsequent logic
    $transactionId = $txn['id'];

    // If PDF path not provided, check DB or generate
    if (!$pdfPath) {
        $pdfPath = !empty($txn['receipt_path']) ? base_path('public/' . $txn['receipt_path']) : null;
        if (!$pdfPath || !file_exists($pdfPath)) {
            $pdfPath = generateReceiptPdf($db, $tenantId, $transactionId, $txn['receipt_number']);
        }
    }

    if ($pdfPath && file_exists($pdfPath)) {
        // Build receipt data: Prefer payload (from controller) but fallback to DB (worker query)
        $receiptData = array_merge($txn, [
            'course_name'    => $payload['course_name']  ?? ($txn['course_name'] ?? 'N/A'),
            'student_name'   => $payload['student_name'] ?? ($txn['name'] ?? 'Student'),
            'student_email'  => $payload['student_email'] ?? ($txn['email'] ?? ''),
            'amount'         => $payload['amount']       ?? ($txn['amount'] ?? 0),
            'amount_due'     => $payload['amount_due']   ?? ($txn['amount_due'] ?? 0),
            'paid_date'      => $payload['paid_date']    ?? date('Y-m-d', strtotime($txn['payment_date'] ?? 'now')),
            'payment_mode'   => $payload['payment_mode'] ?? ($txn['payment_method'] ?? 'Online'),
            'receipt_no'     => $payload['receipt_no']   ?? ($txn['receipt_number'] ?? 'N/A'),
            'transaction_id' => $transactionId,
            'pdf_path'       => $pdfPath
        ]);

        $sent = MailHelper::sendPaymentReceiptEmail($db, $tenantId, $receiptData, $pdfPath);
    }
}

/**
 * PDF Generation Helper (copied logic from controller but headless)
 */
function generateReceiptPdf($db, $tenantId, $transactionId, $receiptNo) {
    // We need to simulate the environment for the template
    $html = getReceiptHtmlInternal($db, $tenantId, $transactionId, $receiptNo);
    if (!$html) return null;

    $pdfDir = __DIR__ . '/../public/uploads/receipts/';
    if (!is_dir($pdfDir)) mkdir($pdfDir, 0777, true);
    
    $filename = 'receipt_' . ($receiptNo ?: $transactionId) . '.pdf';
    $pdfPath = $pdfDir . $filename;

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    file_put_contents($pdfPath, $dompdf->output());
    return $pdfPath;
}

/**
 * Internal HTML Generator Wrapper
 */
function getReceiptHtmlInternal($db, $tenantId, $transactionId, $receiptNo) {
    return \App\Helpers\ReceiptHelper::getHtml($db, $tenantId, $transactionId, $receiptNo);
}
