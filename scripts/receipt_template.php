<?php
/**
 * Payment Receipt Template — Refactored
 *
 * Layout based on the design mockup:
 *   - PAN top-right
 *   - Logo left  |  Institute name + contact + address center  (header)
 *   - "RECEIPT" badge
 *   - Receipt No / Date row
 *   - Student Name full-width
 *   - Course full-width
 *   - 4-column payment table (2 label+value pairs per row)
 *   - "Received By" footer with logged-in user name & role from session
 *
 * Expected $receiptData keys:
 *   institute_name, institute_address, institute_contact, institute_email,
 *   institute_logo_url, institute_pan,
 *   receipt_no, date_ad, date_bs,
 *   student_name, course_name, batch_name,
 *   course_fee (= total amount), paid_amount, remaining,
 *   contact_number, payment_mode,
 *   transaction_id, remarks, fine_amount,
 *   received_by_name, received_by_role,
 *   items[]  (optional multi-item breakdown)
 *
 * Optional flag:
 *   $isDownload : bool – auto-trigger PDF via html2pdf (unused in print mode)
 */

if (!isset($receiptData)) {
    echo "Error: No receipt data provided.";
    exit;
}

/* ── Helpers ──────────────────────────────────────────────────────── */
if (!function_exists('fmtMoney')) {
    function fmtMoney($val) {
        $f = floatval($val);
        return 'Rs. ' . number_format($f, 2);
    }
}

if (!function_exists('payModeLabel')) {
    function payModeLabel($mode) {
        $map = [
            'cash'          => 'Cash',
            'esewa'         => 'eSewa',
            'khalti'        => 'Khalti',
            'bank_transfer' => 'Bank Transfer',
            'cheque'        => 'Cheque',
            'fonepay'       => 'FonePay',
        ];
        return $map[strtolower((string)$mode)] ?? ucfirst((string)$mode) ?: 'Cash';
    }
}

if (!function_exists('roleLabel')) {
    function roleLabel($role) {
        $map = [
            'instituteadmin' => 'Admin',
            'frontdesk'      => 'Front Desk',
            'superadmin'     => 'Super Admin',
            'teacher'        => 'Teacher',
        ];
        return $map[strtolower((string)$role)] ?? ucwords(str_replace('_', ' ', (string)$role));
    }
}

/* ── Shorthand binding ────────────────────────────────────────────── */
$r = $receiptData;

$inst        = htmlspecialchars($r['institute_name']    ?? 'Institute');
$addr        = htmlspecialchars($r['institute_address'] ?? '');
$phone       = htmlspecialchars($r['institute_contact'] ?? '');
$email       = htmlspecialchars($r['institute_email']   ?? '');
$pan         = htmlspecialchars($r['institute_pan']     ?? '');
$logoUrl     = $r['institute_logo_url'] ?? '';

$recNo       = htmlspecialchars($r['receipt_no'] ?? '');
$dateAD      = htmlspecialchars($r['date_ad']    ?? date('Y-m-d'));
$dateBS      = htmlspecialchars($r['date_bs']    ?? '');

$student     = htmlspecialchars($r['student_name'] ?? '');
$course      = htmlspecialchars($r['course_name']  ?? '');
$batch       = htmlspecialchars($r['batch_name']   ?? '');
$contact     = htmlspecialchars($r['contact_number'] ?? '');

$totalAmt    = floatval($r['course_fee']    ?? 0);
$paidAmt     = floatval($r['paid_amount']   ?? 0);
$remaining   = floatval($r['remaining']     ?? max(0, $totalAmt - $paidAmt));
$fine        = floatval($r['fine_amount']   ?? 0);
$payMode     = $r['payment_mode'] ?? 'cash';
$remarks     = htmlspecialchars($r['remarks'] ?? '');

// "Received By" – comes from session (dynamic user attribution) or injected via ReceiptHelper
$receivedByName = htmlspecialchars(
    $r['received_by_name'] ??
    ($_SESSION['userData']['name'] ?? 'Staff')
);
$receivedByRole = roleLabel(
    $r['received_by_role'] ??
    ($_SESSION['userData']['role'] ?? '')
);

// Due = total - paid (computed, never stored)
$dueAmt = max(0, $totalAmt - $paidAmt);

$autoDownload = isset($isDownload) ? (bool)$isDownload : true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt_<?= $recNo ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        /* ── Reset & Base ──────────────────────────────────────────────── */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f2e8e8;
            color: #1a1a1a;
            font-size: 13px;
        }

        /* ── Toolbar (screen only) ─────────────────────────────────────── */
        .toolbar {
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 18px;
            background: #fff;
            border-bottom: 1px solid #e0d0d0;
        }

        .toolbar button, .toolbar a {
            padding: 9px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: opacity 0.15s;
        }
        .toolbar button:hover, .toolbar a:hover { opacity: 0.85; }

        .btn-pdf   { background: #c0392b; color: #fff; }
        .btn-print { background: #27ae60; color: #fff; }
        .btn-back  { background: #7f8c8d; color: #fff; }

        /* ── Page Wrapper (A4 simulation) ──────────────────────────────── */
        .page-wrapper {
            width: 210mm;
            min-height: 148mm;
            margin: 16px auto;
            background: #f5e8e8;   /* pinkish-beige matching the mockup */
            padding: 12mm 14mm 10mm 14mm;
            box-shadow: 0 2px 20px rgba(0,0,0,0.12);
            position: relative;
        }

        /* ── PAN number – top-right corner ────────────────────────────── */
        .pan-line {
            text-align: right;
            font-size: 11px;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }

        /* ── Header ───────────────────────────────────────────────────── */
        .receipt-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            margin-bottom: 10px;
        }

        .logo-wrap {
            flex: 0 0 72px;
        }

        .inst-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            border-radius: 50%;
            border: 2px solid #9ab;
        }

        .logo-placeholder {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #5b82a0;
            border: 3px solid #bcd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: #fff;
        }

        .inst-info {
            flex: 1;
            text-align: center;
        }

        .inst-info h1 {
            font-size: 18px;
            font-weight: 900;
            color: #111;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .inst-info .contact-line {
            font-size: 11.5px;
            font-weight: 700;
            color: #222;
            margin-bottom: 3px;
        }

        .inst-info .addr-line {
            font-size: 12px;
            color: #333;
        }

        /* ── RECEIPT badge ─────────────────────────────────────────────── */
        .receipt-badge-wrap {
            text-align: center;
            margin: 10px 0 14px;
        }

        .receipt-badge {
            display: inline-block;
            border: 2px solid #222;
            border-radius: 4px;
            padding: 3px 28px;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #111;
            background: transparent;
        }

        /* ── Receipt info rows ─────────────────────────────────────────── */
        .info-section {
            margin-bottom: 14px;
        }

        .info-row {
            display: flex;
            align-items: baseline;
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .info-row.split {
            justify-content: space-between;
        }

        .info-label {
            font-size: 14px;
            font-weight: 700;
            color: #111;
            white-space: nowrap;
        }

        .info-dotted {
            flex: 1;
            border-bottom: 1.5px solid #555;
            margin-left: 4px;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 13.5px;
            color: #111;
            font-weight: 500;
            padding-left: 4px;
            padding-right: 6px;
        }

        /* ── Payment details table ─────────────────────────────────────── */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12.5px;
        }

        .payment-table td {
            border: 1.5px solid #444;
            padding: 6px 10px;
            vertical-align: middle;
        }

        /* odd columns (labels) */
        .payment-table td:nth-child(odd) {
            font-weight: 700;
            color: #111;
            width: 22%;
            background: transparent;
        }

        /* even columns (values) */
        .payment-table td:nth-child(even) {
            color: #1a1a1a;
            width: 28%;
        }

        /* Due amount highlight in red when >0 */
        .due-highlight { color: #c0392b; font-weight: 700; }

        /* ── Signature / footer ────────────────────────────────────────── */
        .sig-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 22px;
        }

        .sig-box {
            text-align: center;
            min-width: 180px;
        }

        .sig-line {
            border-top: 1.5px dotted #555;
            padding-top: 5px;
            margin-bottom: 2px;
        }

        .sig-name {
            font-size: 14px;
            font-weight: 700;
            color: #111;
        }

        .sig-role {
            font-size: 11px;
            color: #555;
        }

        /* ── Loader spinner ────────────────────────────────────────────── */
        #loader {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(255,255,255,0.9);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 999;
        }
        .spinner {
            width: 44px; height: 44px;
            border: 4px solid #eee;
            border-top: 4px solid #c0392b;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Print styles ──────────────────────────────────────────────── */
        @media print {
            body { background: #fff; }
            .toolbar, #loader { display: none !important; }
            .page-wrapper {
                width: 100%;
                margin: 0;
                box-shadow: none;
                padding: 10mm 12mm 8mm 12mm;
                background: #fff;
            }
            .logo-placeholder { background: #999; }
        }
    </style>
</head>
<body>

<div id="loader">
    <div class="spinner"></div>
    <p style="margin-top:14px; font-weight:600; color:#555;">Generating PDF…</p>
</div>

<!-- ── Main Printable Receipt ──────────────────────────────────────── -->
<div class="page-wrapper" id="printable">

    <!-- PAN top-right -->
    <?php if ($pan): ?>
    <div class="pan-line">PAN :- <?= $pan ?></div>
    <?php else: ?>
    <div class="pan-line">PAN :- &nbsp;</div>
    <?php endif; ?>

    <!-- Header: logo + institute info -->
    <div class="receipt-header">
        <div class="logo-wrap">
            <?php if ($logoUrl): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" class="inst-logo" alt="Institute Logo">
            <?php else: ?>
                <div class="logo-placeholder">🏫</div>
            <?php endif; ?>
        </div>
        <div class="inst-info">
            <h1><?= $inst ?></h1>
            <?php if ($phone): ?>
            <div class="contact-line">Contact No.:- <?= $phone ?></div>
            <?php endif; ?>
            <?php if ($addr): ?>
            <div class="addr-line"><?= $addr ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RECEIPT badge -->
    <div class="receipt-badge-wrap">
        <span class="receipt-badge">RECEIPT</span>
    </div>

    <!-- Receipt No / Date / Student / Course -->
    <div class="info-section">
        <!-- Row: Receipt No    Date -->
        <div class="info-row split">
            <span>
                <span class="info-label">Receipt No :&nbsp;</span>
                <span class="info-value"><?= $recNo ?: '………' ?></span>
            </span>
            <span>
                <span class="info-label">Date :&nbsp;</span>
                <span class="info-value">
                    <?php if ($dateBS): ?>
                        <?= $dateBS ?> (BS) / <?= $dateAD ?> (AD)
                    <?php else: ?>
                        <?= $dateAD ?>
                    <?php endif; ?>
                </span>
            </span>
        </div>

        <!-- Row: Student Name -->
        <div class="info-row">
            <span class="info-label">Student Name:-</span>
            <span class="info-dotted"></span>
            <span class="info-value"><?= $student ?></span>
        </div>

        <!-- Row: Course (with optional batch) -->
        <div class="info-row">
            <span class="info-label">Course :-</span>
            <span class="info-dotted" style="margin-left:6px;"></span>
            <span class="info-value"><?= $course ?><?= $batch ? " ($batch)" : '' ?></span>
        </div>
    </div>

    <!-- Payment Details Table (2-column per row) -->
    <table class="payment-table">
        <tr>
            <td>Total Amount</td>
            <td><?= fmtMoney($totalAmt) ?></td>
            <td>Payment Method</td>
            <td><?= htmlspecialchars(payModeLabel($payMode)) ?></td>
        </tr>
        <tr>
            <td>Amount Received</td>
            <td><?= fmtMoney($paidAmt) ?></td>
            <td>Contact Number</td>
            <td><?= $contact ?: '—' ?></td>
        </tr>
        <tr>
            <td>Due Amount</td>
            <td class="<?= $dueAmt > 0 ? 'due-highlight' : '' ?>"><?= fmtMoney($dueAmt) ?></td>
            <td>Remaining Due</td>
            <td class="<?= $remaining > 0 ? 'due-highlight' : '' ?>"><?= fmtMoney($remaining) ?></td>
        </tr>
        <?php if ($fine > 0): ?>
        <tr>
            <td>Late Fine</td>
            <td class="due-highlight"><?= fmtMoney($fine) ?></td>
            <td>Remarks</td>
            <td><?= $remarks ?: '—' ?></td>
        </tr>
        <?php elseif ($remarks): ?>
        <tr>
            <td colspan="2" style="font-weight:700;">Remarks</td>
            <td colspan="2"><?= $remarks ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- Footer: Received By (dynamic – logged-in user from session) -->
    <div class="sig-row">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name">Received By: <?= $receivedByName ?></div>
            <?php if ($receivedByRole): ?>
            <div class="sig-role">Role: <?= htmlspecialchars($receivedByRole) ?></div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.page-wrapper -->

<script>
<?php if (!empty($r['student_email'])): ?>
function sendEmail() {
    const btn = document.getElementById('emailBtn');
    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '⏳ Sending…';
    const payload = { action: 'send_payment_email' };
    <?php if (!empty($r['transaction_id'])): ?>
    payload.transaction_id = '<?= (int)$r['transaction_id'] ?>';
    <?php else: ?>
    payload.receipt_no = '<?= addslashes($recNo) ?>';
    <?php endif; ?>
    fetch('<?= defined("APP_URL") ? APP_URL : "" ?>/api/admin/fees', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = '✅ Email Sent!';
            btn.style.background = '#27ae60';
        } else {
            btn.innerHTML = '❌ Failed';
            btn.style.background = '#e74c3c';
            alert(data.message || 'Email sending failed');
        }
    })
    .catch(err => {
        btn.innerHTML = '❌ Error';
        alert('Error: ' + err.message);
    });
}
<?php endif; ?>
</script>
</body>
</html>
