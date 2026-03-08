<?php
/**
 * receipt-template.php
 * Hamro Academic ERP — Professional Payment Receipt Template
 * Dual-copy (Student + Office), A4, print & PDF optimized
 *
 * Expected: $receiptData array (see keys below)
 * Optional: $isDownload (bool) — trigger auto PDF download
 *           $isPdfRender (bool) — set true when rendering inside DOMPDF
 *
 * DB source tables:
 *   payment_transactions, fee_records, fee_items, students,
 *   enrollments, courses, batches, users, tenants, fee_settings
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
if (!function_exists('fmtMoneyVal')) {
    function fmtMoneyVal($v) { return number_format(floatval($v ?? 0), 2); }
}
if (!function_exists('rcpPayModeHtml')) {
    function rcpPayModeHtml($active) {
        $modes = [
            'cash'          => 'Cash',
            'esewa'         => 'eSewa',
            'khalti'        => 'Khalti',
            'bank_transfer' => 'Bank Transfer',
            'cheque'        => 'Cheque',
            'card'          => 'Card',
            'fonepay'       => 'FonePay',
        ];
        $parts = [];
        foreach ($modes as $k => $label) {
            $isActive = (strtolower($active) === $k);
            $cls = $isActive ? 'pm-pill active' : 'pm-pill';
            $parts[] = '<span class="' . $cls . '">' . $label . '</span>';
        }
        return implode('', $parts);
    }
}

// ── Unpack data ────────────────────────────────────────────────
$r          = $receiptData;
$inst       = h($r['institute_name']    ?? 'Institute Name');
$tagline    = h($r['institute_tagline'] ?? '');
$addr       = h($r['institute_address'] ?? '');
$phone      = h($r['institute_contact'] ?? '');
$email      = h($r['institute_email']   ?? '');
$logoUrl    = $r['institute_logo_url']  ?? '';

$recNo      = h($r['receipt_no']        ?? '');
$dateAD     = h($r['date_ad']           ?? date('Y-m-d'));
$dateBS     = h($r['date_bs']           ?? '');
$txnId      = h($r['transaction_id']    ?? '');
$payMode    = strtolower($r['payment_mode'] ?? 'cash');
$operator   = h($r['operator_name']     ?? '');
$academicYr = h($r['academic_year']     ?? '');

$studentName = h($r['student_name']    ?? '');
$studentId   = h($r['student_id']      ?? '');  // roll_no
$course      = h($r['course_name']     ?? '');
$batch       = h($r['batch_name']      ?? '');
$contact     = h($r['contact_number']  ?? '');
$address     = h($r['address']         ?? '');
$studentEmail= h($r['student_email']   ?? '');

$courseFee   = floatval($r['course_fee']    ?? 0);
$paidAmt     = floatval($r['paid_amount']   ?? 0);
$remaining   = floatval($r['remaining']     ?? 0);
$fineAmt     = floatval($r['fine_amount']   ?? 0);
$fineWaived  = floatval($r['fine_waived']   ?? 0);
$discountAmt = floatval($r['discount_amount']?? 0);
$remarks     = h($r['remarks']             ?? '');

$items       = $r['items'] ?? [];          // [{name, amount, type}]
$verifyUrl   = $r['verify_url']   ?? '';   // e.g. https://erp.institute.com/verify?r=RCP-001
$qrImgUrl    = $r['qr_image_url'] ?? '';   // base64 or URL of QR code image

$isEmailMode = !empty($r['is_email']);
$isPdfRender = isset($isPdfRender) ? (bool)$isPdfRender : false;
$autoDownload= !$isPdfRender && !$isEmailMode && (isset($isDownload) ? (bool)$isDownload : false);

// Compute totals if not explicitly provided
$totalFee = $courseFee + $fineAmt - $fineWaived - $discountAmt;
if ($remaining == 0 && $totalFee > 0 && $paidAmt > 0) {
    $remaining = max(0, $totalFee - $paidAmt);
}
$isPaidFull = ($remaining <= 0);

// ── Receipt sections helper ─────────────────────────────────────
// We render one receipt "copy" as a function-like include.
// Called twice: once for Student Copy, once for Office Copy.
function renderReceiptCopy(
    $copyLabel, $badgeClass,
    $inst, $tagline, $addr, $phone, $email, $logoUrl,
    $recNo, $dateAD, $dateBS, $txnId, $payMode, $operator, $academicYr,
    $studentName, $studentId, $course, $batch, $contact, $address, $studentEmail,
    $courseFee, $paidAmt, $remaining, $fineAmt, $fineWaived, $discountAmt,
    $totalFee, $isPaidFull,
    $items, $remarks, $verifyUrl, $qrImgUrl, $isEmailMode, $isPdfRender
) {
    $isOffice = ($badgeClass === 'office');
?>
<div class="receipt-copy">
    <span class="copy-badge <?= $badgeClass ?>"><?= $copyLabel ?></span>

    <!-- ════ HEADER ════ -->
    <div class="receipt-header">
        <div class="header-logo-wrap">
            <?php if ($logoUrl): ?>
                <img src="<?= $logoUrl ?>" alt="Logo">
            <?php else: ?>
                <div class="logo-placeholder">🏫</div>
            <?php endif; ?>
        </div>
        <div class="header-inst">
            <h1><?= $inst ?></h1>
            <?php if ($tagline): ?><p class="tagline"><?= $tagline ?></p><?php endif; ?>
            <p class="contact-line">
                <?= $addr ?>
                <?php if ($phone): ?>&nbsp;&bull;&nbsp;<?= $phone ?><?php endif; ?>
                <?php if ($email): ?>&nbsp;&bull;&nbsp;<?= $email ?><?php endif; ?>
            </p>
        </div>
    </div>

    <!-- ════ TITLE ROW ════ -->
    <div class="title-row">
        <h2>Payment Receipt</h2>
        <?php if ($isPaidFull && !$isEmailMode): ?>
            <span class="paid-stamp">PAID</span>
        <?php endif; ?>
        <div class="title-meta">
            <div>
                <span class="meta-label">Receipt No:</span>
                <span class="meta-val receipt-no"><?= $recNo ?: '—' ?></span>
            </div>
            <?php if ($dateBS): ?>
            <div>
                <span class="meta-label">Date (BS):</span>
                <span class="meta-val"><?= $dateBS ?></span>
            </div>
            <?php endif; ?>
            <div>
                <span class="meta-label">Date (AD):</span>
                <span class="meta-val"><?= $dateAD ?></span>
            </div>
            <?php if ($txnId): ?>
            <div>
                <span class="meta-label">Txn ID:</span>
                <span class="meta-val"><?= $txnId ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ════ STUDENT INFORMATION ════ -->
    <p class="section-title">Student Information</p>
    <table class="data-table">
        <tr>
            <td class="lbl">Student Name</td>
            <td class="val"><strong><?= $studentName ?: '—' ?></strong></td>
            <td class="lbl" style="width:120px;">Student ID</td>
            <td class="val" style="width:120px;"><?= $studentId ?: '—' ?></td>
        </tr>
        <tr>
            <td class="lbl">Course</td>
            <td class="val"><?= $course ?: '—' ?></td>
            <td class="lbl">Batch</td>
            <td class="val"><?= $batch ?: '—' ?></td>
        </tr>
        <tr>
            <td class="lbl">Contact No.</td>
            <td class="val"><?= $contact ?: '—' ?></td>
            <?php if ($academicYr): ?>
            <td class="lbl">Academic Year</td>
            <td class="val"><?= $academicYr ?></td>
            <?php else: ?>
            <td class="lbl">Email</td>
            <td class="val"><?= $studentEmail ?: '—' ?></td>
            <?php endif; ?>
        </tr>
        <?php if ($address): ?>
        <tr>
            <td class="lbl">Address</td>
            <td class="val" colspan="3"><?= $address ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- ════ PAYMENT DETAILS ════ -->
    <p class="section-title">Payment Details</p>
    <table class="fee-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Type</th>
                <th style="text-align:right;">Amount (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= h($item['name'] ?? '') ?></td>
                    <td><span style="font-size:10px; color:#64748B;"><?= h(ucfirst($item['type'] ?? 'fee')) ?></span></td>
                    <td><?= fmtMoneyVal($item['amount'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td><?= $course ?: 'Course Fee' ?><?= $batch ? " ({$batch})" : '' ?></td>
                    <td><span style="font-size:10px; color:#64748B;">Monthly</span></td>
                    <td><?= fmtMoneyVal($courseFee) ?></td>
                </tr>
            <?php endif; ?>

            <?php if ($fineAmt > 0): ?>
            <tr class="fine-row">
                <td>Late Fine</td>
                <td><span style="font-size:10px; color:#E11D48;">Fine</span></td>
                <td>+ <?= fmtMoneyVal($fineAmt) ?></td>
            </tr>
            <?php endif; ?>

            <?php if ($fineWaived > 0): ?>
            <tr class="discount-row">
                <td>Fine Waived</td>
                <td><span style="font-size:10px;">Waiver</span></td>
                <td>- <?= fmtMoneyVal($fineWaived) ?></td>
            </tr>
            <?php endif; ?>

            <?php if ($discountAmt > 0): ?>
            <tr class="discount-row">
                <td>Discount Applied</td>
                <td><span style="font-size:10px;">Discount</span></td>
                <td>- <?= fmtMoneyVal($discountAmt) ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="subtotal">
                <td colspan="2">Total Fee Due</td>
                <td><?= fmtMoneyVal($totalFee) ?></td>
            </tr>
            <tr class="paid-row">
                <td colspan="2">&#10003; Amount Paid (This Receipt)</td>
                <td><?= fmtMoneyVal($paidAmt) ?></td>
            </tr>
            <?php if ($remaining > 0): ?>
            <tr class="due-row">
                <td colspan="2">&#9888; Remaining Balance</td>
                <td><?= fmtMoneyVal($remaining) ?></td>
            </tr>
            <?php else: ?>
            <tr class="paid-row">
                <td colspan="2">&#10003; Remaining Balance</td>
                <td>0.00 &nbsp;(Fully Paid)</td>
            </tr>
            <?php endif; ?>
        </tfoot>
    </table>

    <!-- ════ PAYMENT MODE ════ -->
    <table class="data-table" style="margin-bottom:8px;">
        <tr>
            <td class="lbl" style="width:165px;">Payment Mode</td>
            <td class="val"><?= rcpPayModeHtml($payMode) ?></td>
        </tr>
        <?php if ($operator): ?>
        <tr>
            <td class="lbl">Collected By</td>
            <td class="val"><?= $operator ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- ════ REMARKS ════ -->
    <?php if ($remarks): ?>
    <div class="remarks-box">
        <strong>Remarks:</strong> <?= $remarks ?>
    </div>
    <?php endif; ?>

    <!-- ════ VERIFICATION ════ -->
    <?php if ($verifyUrl || $qrImgUrl): ?>
    <div class="verify-row">
        <div class="verify-qr">
            <?php if ($qrImgUrl): ?>
                <img src="<?= h($qrImgUrl) ?>" width="70" height="70" alt="QR">
            <?php else: ?>
                <div class="qr-placeholder">Scan to<br>Verify</div>
            <?php endif; ?>
        </div>
        <div class="verify-info">
            <p><strong>Verify this receipt online:</strong></p>
            <?php if ($verifyUrl): ?>
            <p class="verify-link"><?= h($verifyUrl) ?></p>
            <?php endif; ?>
            <p class="verify-note">
                This is a computer-generated receipt. Scan the QR code or visit the link above
                to verify authenticity. Receipt No: <strong><?= $recNo ?></strong>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ════ SIGNATURES ════ -->
    <?php if (!$isEmailMode): ?>
    <div class="sig-row">
        <div class="sig-box">
            <div class="sig-line">Student / Payer Signature</div>
        </div>
        <div class="stamp-box" style="text-align:center; float:none; display:inline-block; width:44%; margin:0 auto;">
            <div class="stamp-circle">Office<br>Stamp</div>
        </div>
        <div class="sig-box-right">
            <div class="sig-line">Authorized Signature<br><span style="font-size:9px; font-weight:400;">(<?= $operator ?: 'Cashier / Admin' ?>)</span></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ════ FOOTER ════ -->
    <div class="receipt-footer">
        This receipt is valid only with institute seal &bull; <?= $inst ?> &bull; <?= $phone ?>
        <?php if ($email): ?>&bull; <?= $email ?><?php endif; ?>
    </div>

</div>
<?php
} // end renderReceiptCopy()
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt — <?= $recNo ?> | <?= $inst ?></title>
    <?php if (!$isPdfRender): ?>
    <!-- Use bundled CSS in PDF mode; link stylesheet for screen -->
    <link rel="stylesheet" href="receipt-style.css">
    <?php else: ?>
    <!-- Inline styles for DOMPDF — external CSS not reliably loaded -->
    <style><?php include __DIR__ . '/receipt-style.css'; ?></style>
    <?php endif; ?>
</head>
<body>

<!-- ════ LOADER ════ -->
<div id="rcp-loader">
    <div class="spinner"></div>
    <p style="font-weight:600; color:#64748B; font-size:14px;">Generating PDF…</p>
</div>

<!-- ════ TOOLBAR ════ -->
<?php if (!$isEmailMode && !$isPdfRender): ?>
<div class="toolbar no-print">
    <a href="?action=generate_receipt_html&is_pdf=1&receipt_no=<?= urlencode($recNo) ?>" class="btn-pdf">
        ⬇ Download PDF
    </a>
    <button class="btn-print" onclick="window.print()">
        🖨 Print
    </button>
    <?php if ($studentEmail): ?>
    <button class="btn-email" id="emailBtn" onclick="rcpSendEmail()">
        ✉ Email to Student
    </button>
    <?php endif; ?>
    <button class="btn-back" onclick="window.history.back()">
        ← Back
    </button>
</div>
<?php endif; ?>

<!-- ════ PAGE ════ -->
<div class="page-wrapper" id="printable">

    <!-- ═══ COPY 1: STUDENT ═══ -->
    <?php renderReceiptCopy(
        'Student Copy', 'student',
        $inst, $tagline, $addr, $phone, $email, $logoUrl,
        $recNo, $dateAD, $dateBS, $txnId, $payMode, $operator, $academicYr,
        $studentName, $studentId, $course, $batch, $contact, $address, $studentEmail,
        $courseFee, $paidAmt, $remaining, $fineAmt, $fineWaived, $discountAmt,
        $totalFee, $isPaidFull,
        $items, $remarks, $verifyUrl, $qrImgUrl, $isEmailMode, $isPdfRender
    ); ?>

    <!-- ═══ CUT LINE ═══ -->
    <?php if (!$isEmailMode): ?>
    <div class="cut-line">
        <span class="cut-label">✂ &nbsp;Cut Here&nbsp; ✂</span>
    </div>

    <!-- ═══ COPY 2: OFFICE ═══ -->
    <?php renderReceiptCopy(
        'Office Copy', 'office',
        $inst, $tagline, $addr, $phone, $email, $logoUrl,
        $recNo, $dateAD, $dateBS, $txnId, $payMode, $operator, $academicYr,
        $studentName, $studentId, $course, $batch, $contact, $address, $studentEmail,
        $courseFee, $paidAmt, $remaining, $fineAmt, $fineWaived, $discountAmt,
        $totalFee, $isPaidFull,
        $items, $remarks, $verifyUrl, $qrImgUrl, $isEmailMode, $isPdfRender
    ); ?>
    <?php endif; ?>

</div>

<?php if (!$isEmailMode && !$isPdfRender): ?>
<script>
function rcpSendEmail() {
    const btn = document.getElementById('emailBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Sending…';

    fetch('/api/admin/fees', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'send_payment_email',
            receipt_no: '<?= addslashes($recNo) ?>',
            transaction_id: '<?= addslashes($txnId) ?>'
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.textContent = '✅ Email Sent!';
            btn.style.background = '#16a34a';
        } else {
            btn.textContent = '❌ Failed';
            btn.style.background = '#E11D48';
            btn.disabled = false;
            alert(data.message || 'Email sending failed.');
        }
    })
    .catch(err => {
        btn.textContent = '❌ Error';
        btn.disabled = false;
        alert('Network error: ' + err.message);
    });
}

<?php if ($autoDownload): ?>
// Auto-trigger PDF download via server-side endpoint (preferred over html2pdf.js)
window.addEventListener('DOMContentLoaded', function() {
    // Server-side PDF is generated at receipt-pdf.php; auto-redirect if requested
    // window.location.href = '/receipt-pdf.php?receipt_no=<?= urlencode($recNo) ?>';
});
<?php endif; ?>
</script>
<?php endif; ?>
</body>
</html>
