<?php
/**
 * Payment Receipt Template - Refactored to match EXACT branding & layout
 * 
 * Features:
 * - Circular logo on top left.
 * - Centered Institute branding.
 * - Boxed "RECEIPT" title.
 * - 2x4 grid for financial breakdown.
 * - Shaded "BALANCE DUE" row.
 * - Clean, professional typography.
 */

if (!isset($receiptData)) {
    echo "Error: No receipt data provided.";
    exit;
}

// Helpers
if (!function_exists('fmtMoney')) {
    function fmtMoney($val) {
        return 'Rs. ' . number_format(floatval($val), 2);
    }
}

// Data binding
$r = $receiptData;

$inst        = htmlspecialchars($r['institute_name']    ?? 'Institute Name');
$addr        = htmlspecialchars($r['institute_address'] ?? 'Address');
$phone       = htmlspecialchars($r['institute_contact'] ?? '');
$email       = htmlspecialchars($r['institute_email']   ?? '');
$logoUrl     = $r['institute_logo_url'] ?? '';

$recNo       = htmlspecialchars($r['receipt_no'] ?? 'RCP-000000');
$dateAD      = htmlspecialchars($r['date_ad']    ?? date('Y-m-d'));
$dateBS      = htmlspecialchars($r['date_bs']    ?? '');

$student     = htmlspecialchars($r['student_name'] ?? '');
$stPhone     = htmlspecialchars($r['contact_number'] ?? $r['student_phone'] ?? '');
$course      = htmlspecialchars($r['course_name']  ?? '');
$batch       = htmlspecialchars($r['batch_name']   ?? '');

// Financial Logic
$courseFee      = floatval($r['course_fee']      ?? 0);
$paidToday      = floatval($r['paid_amount']     ?? 0);
$previousPaid   = floatval($r['previous_payments'] ?? 0);
$totalPaid      = $previousPaid + $paidToday;
$balanceDue     = max(0, $courseFee - $totalPaid);
$payMethod      = htmlspecialchars($r['payment_mode'] ?? 'Cash');

$receivedByName = htmlspecialchars($r['received_by_name'] ?? 'Staff');
$receivedByRole = htmlspecialchars($r['received_by_role'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?= $recNo ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #ffffff;
            color: #1e293b;
            padding: 40px;
            line-height: 1.4;
        }

        .receipt-container {
            max-width: 850px;
            margin: 0 auto;
            position: relative;
        }

        /* ════ HEADER SECTION ════ */
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            padding-top: 10px;
        }

        .logo-wrap {
            position: absolute;
            left: 0;
            top: 0;
        }

        .circular-logo {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            border: 2px solid #5d4037; /* Brownish border like the logo */
            padding: 2px;
            background: #fff;
            object-fit: contain;
        }

        .institute-branding h1 {
            font-size: 24px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            color: #0f172a;
        }

        .institute-branding p {
            font-size: 13px;
            color: #334155;
            margin: 2px 0;
            font-weight: 600;
        }

        /* ════ RECEIPT BADGE ════ */
        .receipt-badge-container {
            text-align: center;
            margin: 25px 0;
        }

        .receipt-badge {
            display: inline-block;
            border: 1.5px solid #000;
            padding: 8px 50px;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        /* ════ META INFO (No & Date) ════ */
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 15px;
            font-weight: 700;
        }

        /* ════ STUDENT INFO ════ */
        .student-info {
            margin-bottom: 25px;
        }

        .info-line {
            display: flex;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .info-label {
            font-weight: 800;
            min-width: 140px;
        }

        .info-value {
            font-weight: 600;
        }

        /* ════ FINANCIAL GRID ════ */
        .financial-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .financial-grid td {
            border: 1px solid #94a3b8;
            padding: 10px 15px;
            font-size: 14px;
            width: 25%;
        }

        .grid-label {
            font-weight: 700;
            color: #0f172a;
        }

        .grid-value {
            font-weight: 600;
            color: #1e293b;
        }

        /* Highlight Balance Row */
        .balance-row {
            background-color: #f1f5f9;
        }

        .balance-row .grid-label,
        .balance-row .grid-value {
            font-size: 15px;
            font-weight: 800;
        }

        /* ════ FOOTER ════ */
        .receipt-footer {
            margin-top: 60px;
            border-top: 1px dotted #94a3b8;
            padding-top: 20px;
            text-align: center;
        }

        .received-info {
            margin-top: 10px;
        }

        .received-by {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }

        .received-role {
            font-size: 12px;
            color: #64748b;
            text-transform: capitalize;
        }

        /* ════ PRINT OPTIMIZATION ════ */
        @media print {
            body { padding: 20px; }
            .receipt-container { width: 100%; border: none; }
            @page { size: auto; margin: 5mm; }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <!-- Header -->
    <header class="receipt-header">
        <div class="logo-wrap">
            <?php if ($logoUrl): ?>
                <img src="<?= $logoUrl ?>" class="circular-logo" alt="Logo">
            <?php else: ?>
                <div class="circular-logo" style="display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 30px; color: #5d4037;">H</div>
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
            <span class="info-value"><?= $student ?></span>
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
            <td class="grid-value"><?= fmtMoney($paidToday) ?></td>
            <td class="grid-label">Contact Number</td>
            <td class="grid-value"><?= $stPhone ?: '—' ?></td>
        </tr>
        <tr>
            <td class="grid-label">Previous Payments</td>
            <td class="grid-value"><?= fmtMoney($previousPaid) ?></td>
            <td class="grid-label">Total Paid</td>
            <td class="grid-value"><?= fmtMoney($totalPaid) ?></td>
        </tr>
        <tr class="balance-row">
            <td class="grid-label">BALANCE DUE</td>
            <td class="grid-value"><?= fmtMoney($balanceDue) ?></td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <!-- Footer -->
    <footer class="receipt-footer">
        <div class="received-info">
            <div class="received-by">Received By: <?= $receivedByName ?></div>
            <div class="received-role">Role: <?= $receivedByRole ?></div>
        </div>
    </footer>
</div>

</body>
</html>