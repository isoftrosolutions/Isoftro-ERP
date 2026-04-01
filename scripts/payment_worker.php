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

    // Use ReceiptHelper as the single source of truth for PDF generation + DB update
    $pdfPath = \App\Helpers\ReceiptHelper::generatePdf($db, $tenantId, $transactionId, $receiptNo);

    if ($pdfPath) {
        // Trigger Email with the absolute PDF path
        processEmailReceipt($db, $tenantId, array_merge($payload, ['pdf_path' => $pdfPath]));
    } else {
        echo "[Warning] PDF generation returned null for transaction $transactionId";
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

    // If PDF path not provided, check DB or generate via ReceiptHelper
    if (!$pdfPath || !file_exists($pdfPath)) {
        if (!empty($txn['receipt_path'])) {
            // receipt_path is stored relative to public/ (e.g. "uploads/receipts/receipt_123.pdf")
            $pdfPath = base_path('public' . DIRECTORY_SEPARATOR . $txn['receipt_path']);
        }
        if (!$pdfPath || !file_exists($pdfPath)) {
            $pdfPath = \App\Helpers\ReceiptHelper::generatePdf($db, $tenantId, $transactionId, $txn['receipt_number']);
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

