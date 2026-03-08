<?php
/**
 * receipt-pdf.php
 * Hamro Academic ERP — Server-side PDF Receipt Generator
 *
 * Uses DOMPDF (recommended) for reliable, server-side PDF generation.
 * Triggered via: GET /receipt-pdf.php?receipt_no=RCP-2025-001
 * Or called internally after payment recording.
 *
 * Install DOMPDF:
 *   composer require dompdf/dompdf
 *
 * ─────────────────────────────────────────────────────────────
 * WHY DOMPDF over alternatives?
 *
 * | Library         | Best for                         | Notes                        |
 * |-----------------|----------------------------------|------------------------------|
 * | DOMPDF          | PHP-native, no external deps     | ✅ Best for this project      |
 * | wkhtmltopdf     | Complex layouts, JS rendering    | Requires binary on server    |
 * | Snappy (Laravel)| Laravel wrapper for wkhtmltopdf  | Not needed for plain PHP     |
 * | mPDF            | Unicode, multilingual content    | Alternative to DOMPDF        |
 * | html2pdf.js     | Client-side, no server needed    | ❌ Unreliable for complex CSS |
 *
 * Verdict: DOMPDF is best because:
 *  • Pure PHP, composer install only
 *  • No binary/server dependencies
 *  • Supports CSS 2.1 + partial CSS3
 *  • Table layouts render correctly
 *  • Works on shared hosting
 *  • Active maintenance, MIT license
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

// ── Bootstrap ──────────────────────────────────────────────────
require_once __DIR__ . '/vendor/autoload.php'; // Composer autoload

use Dompdf\Dompdf;
use Dompdf\Options;

// ── Security: Only logged-in staff can generate receipts ───────
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['tenant_id'])) {
    http_response_code(403);
    echo 'Unauthorized'; exit;
}

// ── Input ──────────────────────────────────────────────────────
$receiptNo  = trim($_GET['receipt_no']    ?? '');
$txnId      = trim($_GET['transaction_id']?? '');
$forceEmail = !empty($_GET['email']);  // send as email attachment instead of download

if (!$receiptNo && !$txnId) {
    http_response_code(400);
    echo 'Missing receipt_no or transaction_id'; exit;
}

$tenantId   = (int)$_SESSION['tenant_id'];

// ── Database ───────────────────────────────────────────────────
// Assumes $db is a PDO connection available via your app bootstrap.
// Replace with your actual DB connection method.
require_once __DIR__ . '/config/database.php'; // your DB config

/**
 * Fetch full receipt data from DB.
 * Joins: payment_transactions, fee_records, fee_items,
 *        students, enrollments, courses, batches, users, tenants, fee_settings
 */
function fetchReceiptData(PDO $db, int $tenantId, string $receiptNo, string $txnId): ?array
{
    $where = $txnId
        ? 'pt.transaction_id = :key AND pt.tenant_id = :tid'
        : 'pt.receipt_number = :key AND pt.tenant_id = :tid';
    $key = $txnId ?: $receiptNo;

    $sql = "
        SELECT
            -- Transaction
            pt.id            AS pt_id,
            pt.receipt_number,
            pt.transaction_id,
            pt.amount        AS paid_amount,
            pt.payment_method,
            pt.payment_date,
            pt.notes         AS txn_notes,
            pt.status        AS txn_status,

            -- Fee record
            fr.amount_due    AS course_fee,
            fr.discount_amount,
            fr.fine_applied,
            fr.fine_waived,
            fr.academic_year,
            fr.installment_no,

            -- Fee item
            fi.name          AS fee_item_name,
            fi.type          AS fee_item_type,

            -- Student
            s.id             AS student_db_id,
            s.roll_no        AS student_id,
            s.full_name      AS student_name,
            s.phone          AS contact_number,
            s.email          AS student_email,
            s.temporary_address AS address,

            -- Batch / Enrollment
            b.name           AS batch_name,

            -- Course
            c.name           AS course_name,
            c.fee            AS course_total_fee,

            -- Operator
            u.name           AS operator_name,

            -- Tenant (Institute)
            t.name           AS institute_name,
            t.address        AS institute_address,
            t.phone          AS institute_contact,
            t.email          AS institute_email,
            t.logo_url       AS institute_logo_url

        FROM payment_transactions pt
        LEFT JOIN fee_records fr     ON fr.id = pt.fee_record_id
        LEFT JOIN fee_items fi       ON fi.id = fr.fee_item_id
        LEFT JOIN students s         ON s.id  = pt.student_id AND s.tenant_id = pt.tenant_id
        LEFT JOIN enrollments e      ON e.student_id = s.id AND e.tenant_id = pt.tenant_id
        LEFT JOIN batches b          ON b.id  = e.batch_id
        LEFT JOIN courses c          ON c.id  = e.course_id
        LEFT JOIN users u            ON u.id  = pt.recorded_by
        LEFT JOIN tenants t          ON t.id  = pt.tenant_id
        WHERE {$where}
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':key' => $key, ':tid' => $tenantId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;

    // Nepali date conversion (if your app has a BS converter)
    $dateBS = '';
    if (function_exists('adToBs')) {
        $dateBS = adToBs($row['payment_date']);
    }

    // Remaining balance
    $remaining = max(0,
        floatval($row['course_fee'])
        + floatval($row['fine_applied'])
        - floatval($row['fine_waived'])
        - floatval($row['discount_amount'])
        - floatval($row['paid_amount'])
    );

    // Verify URL (customize your app's verify endpoint)
    $appUrl    = defined('APP_URL') ? APP_URL : '';
    $verifyUrl = $appUrl . '/verify-receipt?r=' . urlencode($row['receipt_number']);

    // QR code — generate base64 using your QR library or external API
    $qrImgUrl = '';
    if (function_exists('generateQrBase64')) {
        $qrImgUrl = generateQrBase64($verifyUrl);
    }

    return [
        'receipt_no'         => $row['receipt_number'],
        'transaction_id'     => $row['transaction_id'],
        'date_ad'            => $row['payment_date'],
        'date_bs'            => $dateBS,
        'payment_mode'       => $row['payment_method'],
        'operator_name'      => $row['operator_name'],
        'academic_year'      => $row['academic_year'],

        'student_name'       => $row['student_name'],
        'student_id'         => $row['student_id'],
        'course_name'        => $row['course_name'],
        'batch_name'         => $row['batch_name'],
        'contact_number'     => $row['contact_number'],
        'address'            => $row['address'],
        'student_email'      => $row['student_email'],

        'course_fee'         => $row['course_fee'],
        'paid_amount'        => $row['paid_amount'],
        'remaining'          => $remaining,
        'fine_amount'        => $row['fine_applied'],
        'fine_waived'        => $row['fine_waived'],
        'discount_amount'    => $row['discount_amount'],
        'remarks'            => $row['txn_notes'],

        // Multi-item: single item from fee_items
        'items' => [[
            'name'   => $row['fee_item_name'],
            'amount' => $row['course_fee'],
            'type'   => $row['fee_item_type'],
        ]],

        'institute_name'     => $row['institute_name'],
        'institute_address'  => $row['institute_address'],
        'institute_contact'  => $row['institute_contact'],
        'institute_email'    => $row['institute_email'],
        'institute_logo_url' => $row['institute_logo_url'],

        'verify_url'         => $verifyUrl,
        'qr_image_url'       => $qrImgUrl,
    ];
}

// ── Fetch data ─────────────────────────────────────────────────
$receiptData = fetchReceiptData($db, $tenantId, $receiptNo, $txnId);

if (!$receiptData) {
    http_response_code(404);
    echo 'Receipt not found.'; exit;
}

// ── Render HTML via template ────────────────────────────────────
$isPdfRender = true;   // tells template to inline CSS, hide toolbar
ob_start();
include __DIR__ . '/receipt-template.php';
$html = ob_get_clean();

// ── DOMPDF Configuration ────────────────────────────────────────
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');      // Unicode support
$options->set('isRemoteEnabled', true);            // Allow logo images from URL
$options->set('isHtml5ParserEnabled', true);
$options->set('chroot', realpath(__DIR__));        // Security: restrict file access
$options->set('dpi', 150);                         // Higher DPI = crisper print

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// ── Save receipt to disk (optional) ────────────────────────────
$receiptFilename = 'receipt_' . preg_replace('/[^a-zA-Z0-9\-_]/', '_', $receiptData['receipt_no']) . '.pdf';
$savePath = __DIR__ . '/storage/receipts/' . $receiptFilename;

if (!is_dir(dirname($savePath))) {
    mkdir(dirname($savePath), 0755, true);
}
file_put_contents($savePath, $dompdf->output());

// Optionally update receipt_path in DB
// $db->prepare("UPDATE payment_transactions SET receipt_path=? WHERE receipt_number=?")->execute([$receiptFilename, $receiptData['receipt_no']]);

// ── Output ─────────────────────────────────────────────────────
if ($forceEmail) {
    // Return PDF as string for attachment (called from email sender)
    return $dompdf->output();
} else {
    // Stream to browser as download
    $dompdf->stream($receiptFilename, [
        'Attachment' => 1,   // 1 = download, 0 = inline preview
    ]);
}
