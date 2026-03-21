<?php
/**
 * receipt-template.php
 * Hamro Academic ERP — Professional Payment Receipt Template
 * Refactored to SINGLE COPY to match branding image.
 *
 * Expected: $receiptData array
 */

if (!isset($receiptData)) {
    http_response_code(400);
    echo "Error: No receipt data provided.";
    exit;
}

// ── Helpers ────────────────────────────────────────────────────
if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('fmtMoney')) {
    function fmtMoney($v) { return 'Rs. ' . number_format(floatval($v ?? 0), 2); }
}

// ── Unpack data ────────────────────────────────────────────────
$r          = $receiptData;
$inst       = h($r['institute_name']    ?? 'Institute Name');
$addr       = h($r['institute_address'] ?? '');
$phone      = h($r['institute_contact'] ?? '');
$email      = h($r['institute_email']   ?? '');
$logoUrl    = $r['institute_logo_url']  ?? '';

$recNo      = h($r['receipt_no']        ?? '');
$dateAD     = h($r['date_ad']           ?? date('Y-m-d'));
$dateBS     = h($r['date_bs']           ?? '');

$studentName = h($r['student_name']    ?? '');
$studentId   = h($r['student_id']      ?? '');
$course      = h($r['course_name']     ?? '');
$batch       = h($r['batch_name']      ?? '');
$contact     = h($r['contact_number']  ?? '');

$courseFee   = floatval($r['course_fee']    ?? 0);
$paidAmt     = floatval($r['paid_amount']   ?? 0);
$previousPaid= floatval($r['previous_payments'] ?? 0);
$totalPaid   = $previousPaid + $paidAmt;
$remaining   = max(0, $courseFee - $totalPaid);
$payMethod   = h($r['payment_mode'] ?? 'Cash');

$operator    = h($r['received_by_name'] ?? $r['operator_name'] ?? 'Admin');
$operatorRole= h($r['received_by_role'] ?? 'Admin');

$isEmailMode = !empty($r['is_email']);
$isPdfRender = isset($isPdfRender) ? (bool)$isPdfRender : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt — <?= $recNo ?> | <?= $inst ?></title>
    <?php if (!$isPdfRender): ?>
    <link rel="stylesheet" href="receipt-style.css">
    <?php else: ?>
    <style><?php include __DIR__ . '/receipt-style.css'; ?></style>
    <?php endif; ?>
</head>
<body>

<?php if (!$isEmailMode && !$isPdfRender): ?>
<!-- ════ TOOLBAR ════ -->
<div class="toolbar no-print" style="background:#fff; padding:15px; text-align:center; border-bottom:1px solid #e2e8f0; margin-bottom: 20px;">
    <button class="btn-print" onclick="window.print()" style="padding:10px 25px; background:#1e293b; color:#fff; border:none; border-radius:5px; cursor:pointer; font-weight:700;">🖨 Print Receipt</button>
    <button class="btn-back" onclick="window.history.back()" style="padding:10px 25px; background:#64748b; color:#fff; border:none; border-radius:5px; cursor:pointer; margin-left:10px;">← Back</button>
</div>
<?php endif; ?>

<div class="receipt-container">
    <!-- Header -->
    <header class="receipt-header">
        <div class="logo-wrap">
            <?php if ($logoUrl): ?>
                <img src="<?= $logoUrl ?>" class="circular-logo" alt="Logo">
            <?php else: ?>
                <div class="logo-placeholder">H</div>
            <?php endif; ?>
        </div>
        <div class="institute-branding">
            <h1><?= $inst ?></h1>
            <p>Contact No.: <?= $phone ?></p>
            <p><?= $addr ?></p>
        </div>
    </header>

    <!-- Receipt Badge -->
    <div class="receipt-badge-container">
        <div class="receipt-badge">RECEIPT</div>
    </div>

    <!-- Meta Info -->
    <div class="meta-info">
        <div>Receipt No : <?= $recNo ?></div>
        <div>Date : <?= $dateBS ?> (BS) / <?= $dateAD ?> (AD)</div>
    </div>

    <!-- Student & Course Info -->
    <div class="student-info">
        <div class="info-line">
            <span class="info-label">Student Name:-</span>
            <span class="info-value"><?= $studentName ?></span>
        </div>
        <div class="info-line">
            <span class="info-label">Course :-</span>
            <span class="info-value"><?= $course ?><?= $batch ? " ($batch)" : '' ?></span>
        </div>
    </div>

    <!-- Financial Breakdown Grid -->
    <table class="financial-grid">
        <tr>
            <td class="grid-label">Total Course Fee</td>
            <td class="grid-value"><?= fmtMoney($courseFee) ?></td>
            <td class="grid-label">Payment Method</td>
            <td class="grid-value"><?= $payMethod ?></td>
        </tr>
        <tr>
            <td class="grid-label">Amount Paid Today</td>
            <td class="grid-value"><?= fmtMoney($paidAmt) ?></td>
            <td class="grid-label">Contact Number</td>
            <td class="grid-value"><?= $contact ?: '—' ?></td>
        </tr>
        <tr>
            <td class="grid-label">Previous Payments</td>
            <td class="grid-value"><?= fmtMoney($previousPaid) ?></td>
            <td class="grid-label">Total Paid</td>
            <td class="grid-value"><?= fmtMoney($totalPaid) ?></td>
        </tr>
        <tr class="balance-row">
            <td class="grid-label">BALANCE DUE</td>
            <td class="grid-value"><?= fmtMoney($remaining) ?></td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <!-- Footer -->
    <footer class="receipt-footer">
        <div class="received-info">
            <div class="received-by">Received By: <?= $operator ?></div>
            <div class="received-role">Role: <?= $operatorRole ?></div>
        </div>
    </footer>
</div>

</body>
</html>
