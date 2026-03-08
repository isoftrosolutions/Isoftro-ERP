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
        } catch (\Exception $e) {

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
        processEmailReceipt($db, $tenantId, ['transaction_id' => $transactionId, 'pdf_path' => $pdfPath]);
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

    // Fetch details
    if ($transactionId) {
        $stmt = $db->prepare("
            SELECT pt.*, s.full_name as name, COALESCE(NULLIF(s.email, ''), u.email) as email 
            FROM payment_transactions pt
            JOIN students s ON pt.student_id = s.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE pt.id = :tid AND pt.tenant_id = :tenant
        ");
        $stmt->execute(['tid' => $transactionId, 'tenant' => $tenantId]);
    } else {
        $stmt = $db->prepare("
            SELECT pt.*, s.full_name as name, COALESCE(NULLIF(s.email, ''), u.email) as email 
            FROM payment_transactions pt
            JOIN students s ON pt.student_id = s.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE pt.receipt_number = :rno AND pt.tenant_id = :tenant
        ");
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
        $sent = MailHelper::sendPaymentReceiptEmail(
            $db, $tenantId, $txn['email'], $txn['name'], 
            $txn['receipt_number'], $pdfPath, null, $txn['amount']
        );

        if ($sent) {
            $stmt = $db->prepare("INSERT INTO mail_logs (tenant_id, recipient, subject, status) VALUES (?, ?, ?, 'sent')");
            $stmt->execute([$tenantId, $txn['email'], "Payment Receipt - " . $txn['receipt_number']]);
        }
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
