<?php
/**
 * Payment Receipt Template
 * Generates a standalone HTML receipt page with dual-copy layout.
 * Used by: fees.php API (generate_receipt_html action) and server-side PDF generation.
 *
 * Expected variables (set before including this file):
 *   $receiptData = [
 *     'institute_name', 'institute_address', 'institute_contact', 'institute_email',
 *     'institute_logo_url',
 *     'receipt_no', 'date_ad', 'date_bs',
 *     'student_name', 'course_name', 'course_fee', 'paid_amount', 'remaining',
 *     'address', 'contact_number', 'payment_mode', 'transaction_id', 'remarks',
 *     'batch_name', 'fine_amount'
 *   ]
 *   $isDownload = true|false  (if true, auto-triggers PDF download via html2pdf.js)
 */

if (!isset($receiptData)) {
    echo "Error: No receipt data provided.";
    exit;
}

// Shorthand
$r = $receiptData;
$inst     = htmlspecialchars($r['institute_name'] ?? 'Institute');
$addr     = htmlspecialchars($r['institute_address'] ?? '');
$phone    = htmlspecialchars($r['institute_contact'] ?? '');
$email    = htmlspecialchars($r['institute_email'] ?? '');
$logoUrl  = $r['institute_logo_url'] ?? '';
$recNo    = htmlspecialchars($r['receipt_no'] ?? '');
$dateAD   = htmlspecialchars($r['date_ad'] ?? date('Y-m-d'));
$dateBS   = htmlspecialchars($r['date_bs'] ?? '');
$student  = htmlspecialchars($r['student_name'] ?? '');
$course   = htmlspecialchars($r['course_name'] ?? '');
$batch    = htmlspecialchars($r['batch_name'] ?? '');
$courseFee= htmlspecialchars($r['course_fee'] ?? '0');
$paidAmt  = htmlspecialchars($r['paid_amount'] ?? '0');
$remaining= htmlspecialchars($r['remaining'] ?? '0');
$address  = htmlspecialchars($r['address'] ?? '');
$contact  = htmlspecialchars($r['contact_number'] ?? '');
$payMode  = strtolower($r['payment_mode'] ?? 'cash');
$txnId    = htmlspecialchars($r['transaction_id'] ?? '');
$remarks  = htmlspecialchars($r['remarks'] ?? '');
$fine     = htmlspecialchars($r['fine_amount'] ?? '0');
$autoDownload = isset($isDownload) ? $isDownload : true;

// Payment mode display with highlight
if (!function_exists('paymentModeDisplay')) {
    function paymentModeDisplay($mode) {
        $modes = ['cash' => 'Cash', 'esewa' => 'eSewa', 'khalti' => 'Khalti', 'bank_transfer' => 'Bank', 'cheque' => 'Cheque', 'fonepay' => 'FonePay'];
        $parts = [];
        foreach ($modes as $key => $label) {
            if ($key === $mode) {
                $parts[] = '<strong style="text-decoration:underline; color:#c0392b;">' . $label . '</strong>';
            } else {
                $parts[] = $label;
            }
        }
        return implode('&nbsp; / &nbsp;', $parts);
    }
}

// Format money
if (!function_exists('fmtMoney')) {
    function fmtMoney($val) {
        return number_format(floatval($val), 2);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt_<?= $recNo ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f0f0;
            color: #222;
        }

        .page-wrapper {
            width: 210mm;
            margin: 10mm auto;
            background: #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .receipt-copy {
            padding: 12mm 15mm 8mm 15mm;
            position: relative;
        }

        /* Dashed cut line between copies */
        .cut-line {
            border: none;
            border-top: 2px dashed #bbb;
            margin: 0;
            position: relative;
        }
        .cut-line::after {
            content: '✂ Cut Here';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            padding: 0 12px;
            font-size: 11px;
            color: #999;
        }

        /* Header */
        .receipt-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #c0392b;
            overflow: hidden;
        }

        .inst-logo {
            float: left;
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 4px;
            margin-right: 15px;
        }

        .inst-logo-placeholder {
            float: left;
            width: 60px;
            height: 60px;
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: flex; /* flex is ok for small single items sometimes, but let's be safe */
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-right: 15px;
        }

        .inst-info h1 {
            font-size: 20px;
            color: #c0392b;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .inst-info p {
            font-size: 11px;
            color: #555;
            line-height: 1.5;
        }

        /* Title row */
        .title-row {
            margin-bottom: 15px;
            overflow: hidden;
            width: 100%;
        }

        .title-row h2 {
            float: left;
            font-size: 16px;
            font-weight: 800;
            color: #222;
            letter-spacing: 1px;
            border-bottom: 2px solid #c0392b;
            padding-bottom: 3px;
        }

        .title-meta {
            float: right;
            text-align: right;
            font-size: 12px;
            line-height: 1.8;
        }

        .title-meta span {
            color: #555;
        }

        .title-meta strong {
            display: inline-block;
            min-width: 60px;
        }

        .meta-line {
            border-bottom: 1px solid #333;
            display: inline-block;
            min-width: 130px;
            text-align: center;
            padding-bottom: 1px;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 12.5px;
        }

        .data-table td {
            padding: 7px 10px;
            border: 1px solid #ccc;
            vertical-align: middle;
        }

        .data-table td:first-child {
            font-weight: 700;
            width: 170px;
            background: #fafafa;
            color: #333;
        }

        .data-table td:last-child {
            color: #111;
        }

        .highlight-row td {
            background: #fff5f5 !important;
        }

        /* Signature Row */
        .sig-row {
            margin-top: 25px;
            padding-top: 5px;
            font-size: 11px;
            color: #555;
            overflow: hidden;
        }

        .sig-box {
            float: left;
            text-align: center;
            width: 45%;
        }
        .sig-box-right {
            float: right;
            text-align: center;
            width: 45%;
        }

        .sig-box .sig-line {
            border-top: 1px solid #333;
            margin-bottom: 4px;
            padding-top: 5px;
        }

        /* Copy label */
        .copy-label {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 9px;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }

        /* Toolbar (no-print) */
        .toolbar {
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 20px;
            background: #f8f8f8;
        }

        .toolbar button, .toolbar a {
            padding: 10px 22px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }

        .btn-pdf { background: #c0392b; color: #fff; }
        .btn-pdf:hover { background: #a93226; }
        .btn-email { background: #2980b9; color: #fff; }
        .btn-email:hover { background: #2471a3; }
        .btn-print { background: #27ae60; color: #fff; }
        .btn-print:hover { background: #229954; }
        .btn-back { background: #7f8c8d; color: #fff; }
        .btn-back:hover { background: #6c7a7b; }

        /* Loader */
        #loader {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(255,255,255,0.92);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .spinner {
            width: 45px; height: 45px;
            border: 4px solid #eee;
            border-top: 4px solid #c0392b;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        @media print {
            body { background: #fff; }
            .page-wrapper { box-shadow: none; margin: 0; }
            .toolbar, #loader { display: none !important; }
            .receipt-copy { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <div id="loader">
        <div class="spinner"></div>
        <p style="margin-top:15px; font-weight:600; color:#555;">Generating PDF...</p>
    </div>

    <!-- Toolbar -->
    <?php if (empty($r['is_email'])): ?>
    <div class="toolbar no-print">
        <a href="?action=generate_receipt_html&is_pdf=1&<?= !empty($r['transaction_id']) ? 'transaction_id='.$r['transaction_id'] : 'receipt_no='.$recNo ?>" class="btn-pdf">
            <i class="fa-solid fa-file-pdf"></i> ⬇ Download PDF
        </a>
        <?php if (!empty($r['student_email'])): ?>
        <button class="btn-email" onclick="sendEmail()" id="emailBtn">
            ✉ Send to Email
        </button>
        <?php endif; ?>
        <button class="btn-print" onclick="window.print()">
            🖨 Print
        </button>
        <button class="btn-back" onclick="window.close(); window.history.back();">
            ← Back
        </button>
    </div>
    <?php endif; ?>

    <!-- Printable Content -->
    <div class="page-wrapper" id="printable">

        <!-- ═══ COPY 1: STUDENT COPY ═══ -->
        <div class="receipt-copy">
            <div class="copy-label">Student Copy</div>

            <div class="receipt-header">
                <?php if ($logoUrl): ?>
                    <img src="<?= $logoUrl ?>" class="inst-logo" alt="Logo">
                <?php else: ?>
                    <div class="inst-logo-placeholder">🏫</div>
                <?php endif; ?>
                <div class="inst-info">
                    <h1><?= $inst ?></h1>
                    <p>
                        <?= $addr ?><br>
                        <?php if ($phone): ?>Contact: <?= $phone ?><?php endif; ?>
                        <?php if ($phone && $email): ?> &nbsp;|&nbsp; <?php endif; ?>
                        <?php if ($email): ?>Email: <?= $email ?><?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="title-row">
                <h2>PAYMENT RECEIPT</h2>
                <div class="title-meta">
                    <div><strong>Receipt No:</strong> <span class="meta-line"><?= $recNo ?></span></div>
                    <?php if ($dateBS): ?>
                    <div><strong>Date (BS):</strong> <span class="meta-line"><?= $dateBS ?></span></div>
                    <?php endif; ?>
                    <div><strong>Date (AD):</strong> <span class="meta-line"><?= $dateAD ?></span></div>
                </div>
            </div>

            <table class="data-table">
             <?php if (!empty($r['items']) && count($r['items']) > 1): ?>
                <tr>
                    <td>Payment Breakdown</td>
                    <td style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <?php foreach ($r['items'] as $item): ?>
                            <tr>
                                <td style="border: none; border-bottom: 1px solid #eee; padding: 5px 10px; background: transparent; font-weight: normal; width: auto;"><?= htmlspecialchars($item['name']) ?></td>
                                <td style="border: none; border-bottom: 1px solid #eee; padding: 5px 10px; text-align: right; width: 100px;">Rs. <?= fmtMoney($item['amount']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </td>
                </tr>
                <?php else: ?>
                <tr><td>Course / Service</td><td><?= $course ?><?= $batch ? " ({$batch})" : '' ?></td></tr>
                <?php endif; ?>
                
                <tr><td>Course Fee (Rs.)</td><td>Rs. <?= fmtMoney($courseFee) ?></td></tr>
                <tr class="highlight-row"><td>Paid Amount (Rs.)</td><td><strong>Rs. <?= fmtMoney($paidAmt) ?></strong></td></tr>
                <?php if (floatval($fine) > 0): ?>
                <tr><td>Late Fine (Rs.)</td><td style="color:#c0392b;">Rs. <?= fmtMoney($fine) ?></td></tr>
                <?php endif; ?>
                <tr><td>Remaining (Rs.)</td><td style="color:<?= floatval($remaining) > 0 ? '#c0392b' : '#27ae60' ?>;">Rs. <?= fmtMoney($remaining) ?></td></tr>
                <tr><td>Address</td><td><?= $address ?: '—' ?></td></tr>
                <tr><td>Contact Number</td><td><?= $contact ?: '—' ?></td></tr>
                <tr><td>Payment Mode</td><td><?= paymentModeDisplay($payMode) ?></td></tr>
                <tr><td>Transaction ID</td><td><?= $txnId ?: ($recNo ?: '—') ?></td></tr>
                <tr><td>Remarks</td><td><?= $remarks ?: '—' ?></td></tr>
            </table>

            <div class="sig-row">
                <div class="sig-box">
                    <div class="sig-line">Paid By (Student Signature)</div>
                </div>
                <div class="sig-box-right">
                    <div class="sig-line">Received By (Staff Signature & Stamp)</div>
                </div>
            </div>
        </div>

        <!-- ═══ CUT LINE ═══ -->
        <?php if (empty($r['is_email'])): ?>
        <hr class="cut-line">

        <!-- ═══ COPY 2: OFFICE COPY ═══ -->
        <div class="receipt-copy">
            <div class="copy-label">Office Copy</div>

            <div class="receipt-header">
                <?php if ($logoUrl): ?>
                    <img src="<?= $logoUrl ?>" class="inst-logo" alt="Logo">
                <?php else: ?>
                    <div class="inst-logo-placeholder">🏫</div>
                <?php endif; ?>
                <div class="inst-info">
                    <h1><?= $inst ?></h1>
                    <p>
                        <?= $addr ?><br>
                        <?php if ($phone): ?>Contact: <?= $phone ?><?php endif; ?>
                        <?php if ($phone && $email): ?> &nbsp;|&nbsp; <?php endif; ?>
                        <?php if ($email): ?>Email: <?= $email ?><?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="title-row">
                <h2>PAYMENT RECEIPT</h2>
                <div class="title-meta">
                    <div><strong>Receipt No:</strong> <span class="meta-line"><?= $recNo ?></span></div>
                    <?php if ($dateBS): ?>
                    <div><strong>Date (BS):</strong> <span class="meta-line"><?= $dateBS ?></span></div>
                    <?php endif; ?>
                    <div><strong>Date (AD):</strong> <span class="meta-line"><?= $dateAD ?></span></div>
                </div>
            </div>

            <table class="data-table">
                <tr><td>Name of Student</td><td><?= $student ?></td></tr>
                
                <?php if (!empty($r['items']) && count($r['items']) > 1): ?>
                <tr>
                    <td>Payment Breakdown</td>
                    <td style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <?php foreach ($r['items'] as $item): ?>
                            <tr>
                                <td style="border: none; border-bottom: 1px solid #eee; padding: 5px 10px; background: transparent; font-weight: normal; width: auto;"><?= htmlspecialchars($item['name']) ?></td>
                                <td style="border: none; border-bottom: 1px solid #eee; padding: 5px 10px; text-align: right; width: 100px;">Rs. <?= fmtMoney($item['amount']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </td>
                </tr>
                <?php else: ?>
                <tr><td>Course / Service</td><td><?= $course ?><?= $batch ? " ({$batch})" : '' ?></td></tr>
                <?php endif; ?>

                <tr><td>Course Fee (Rs.)</td><td>Rs. <?= fmtMoney($courseFee) ?></td></tr>
                <tr class="highlight-row"><td>Paid Amount (Rs.)</td><td><strong>Rs. <?= fmtMoney($paidAmt) ?></strong></td></tr>
                <?php if (floatval($fine) > 0): ?>
                <tr><td>Late Fine (Rs.)</td><td style="color:#c0392b;">Rs. <?= fmtMoney($fine) ?></td></tr>
                <?php endif; ?>
                <tr><td>Remaining (Rs.)</td><td style="color:<?= floatval($remaining) > 0 ? '#c0392b' : '#27ae60' ?>;">Rs. <?= fmtMoney($remaining) ?></td></tr>
                <tr><td>Address</td><td><?= $address ?: '—' ?></td></tr>
                <tr><td>Contact Number</td><td><?= $contact ?: '—' ?></td></tr>
                <tr><td>Payment Mode</td><td><?= paymentModeDisplay($payMode) ?></td></tr>
                <tr><td>Transaction ID</td><td><?= $txnId ?: ($recNo ?: '—') ?></td></tr>
                <tr><td>Remarks</td><td><?= $remarks ?: '—' ?></td></tr>
            </table>

            <div class="sig-row">
                <div class="sig-box">
                    <div class="sig-line">Paid By (Student Signature)</div>
                </div>
                <div class="sig-box-right">
                    <div class="sig-line">Received By (Staff Signature & Stamp)</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <script>
        function sendEmail() {
            const btn = document.getElementById('emailBtn');
            btn.disabled = true;
            btn.innerHTML = '⏳ Sending...';

            const payload = {
                action: 'send_payment_email'
            };
            
            <?php if (!empty($r['transaction_id'])): ?>
            payload.transaction_id = '<?= $r['transaction_id'] ?>';
            <?php else: ?>
            payload.receipt_no = '<?= $recNo ?>';
            <?php endif; ?>

            fetch('<?= defined("APP_URL") ? APP_URL : "" ?>/api/admin/fees', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
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
    </script>
</body>
</html>
