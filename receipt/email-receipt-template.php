<?php
/**
 * email-receipt-template.php
 * Hamro Academic ERP — Email Receipt Template
 *
 * Usage (from your mailer / fees.php email action):
 *
 *   $receiptData = [ ...same as receipt-template.php... ];
 *   $receiptData['is_email'] = true;
 *   ob_start();
 *   include 'email-receipt-template.php';
 *   $emailHtml = ob_get_clean();
 *
 *   // Send via PHPMailer / SwiftMailer / your mail helper:
 *   $mail->isHTML(true);
 *   $mail->Subject = "Payment Receipt #{$receiptData['receipt_no']} — {$receiptData['institute_name']}";
 *   $mail->Body    = $emailHtml;
 *   // Attach PDF:
 *   $pdfBytes = include 'receipt-pdf.php'; // or call your PDF generator
 *   $mail->addStringAttachment($pdfBytes, "receipt_{$receiptData['receipt_no']}.pdf", 'base64', 'application/pdf');
 *   $mail->send();
 */

if (!isset($receiptData)) {
    echo "Error: No receipt data."; exit;
}

$r = $receiptData;
function eh($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function emfmt($v) { return 'Rs. ' . number_format(floatval($v ?? 0), 2); }

$inst       = eh($r['institute_name']    ?? 'Institute');
$phone      = eh($r['institute_contact'] ?? '');
$email      = eh($r['institute_email']   ?? '');
$logoUrl    = eh($r['institute_logo_url']?? '');
$recNo      = eh($r['receipt_no']        ?? '');
$dateAD     = eh($r['date_ad']           ?? date('Y-m-d'));
$dateBS     = eh($r['date_bs']           ?? '');
$txnId      = eh($r['transaction_id']    ?? '');
$payMode    = ucfirst(str_replace('_', ' ', $r['payment_mode'] ?? 'Cash'));
$student    = eh($r['student_name']      ?? '');
$studentId  = eh($r['student_id']        ?? '');
$course     = eh($r['course_name']       ?? '');
$batch      = eh($r['batch_name']        ?? '');
$paidAmt    = floatval($r['paid_amount'] ?? 0);
$remaining  = floatval($r['remaining']   ?? 0);
$fineAmt    = floatval($r['fine_amount'] ?? 0);
$discount   = floatval($r['discount_amount'] ?? 0);
$courseFee  = floatval($r['course_fee']  ?? 0);
$totalDue   = $courseFee + $fineAmt - $discount;
$remarks    = eh($r['remarks']           ?? '');
$verifyUrl  = eh($r['verify_url']        ?? '');
$items      = $r['items'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Payment Receipt — <?= $recNo ?></title>
</head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:'Segoe UI',Arial,sans-serif;font-size:14px;color:#1E293B;">

<!-- ── OUTER WRAPPER ── -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F1F5F9;">
<tr><td align="center" style="padding:24px 16px;">

<!-- ── EMAIL CARD ── -->
<table width="600" cellpadding="0" cellspacing="0" border="0"
       style="background:#ffffff;border-radius:10px;overflow:hidden;
              box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:600px;">

    <!-- ── HEADER BANNER ── -->
    <tr>
        <td style="background:#00B894;padding:20px 28px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <?php if ($logoUrl): ?>
                    <td width="56" valign="middle">
                        <img src="<?= $logoUrl ?>" width="52" height="52"
                             style="border-radius:6px;border:2px solid rgba(255,255,255,.4);"
                             alt="Logo">
                    </td>
                    <td width="12"></td>
                    <?php endif; ?>
                    <td valign="middle">
                        <p style="color:#fff;font-size:18px;font-weight:800;margin:0 0 2px;">
                            <?= $inst ?>
                        </p>
                        <p style="color:rgba(255,255,255,.85);font-size:11px;margin:0;">
                            <?= $phone ?><?= $phone && $email ? ' &bull; ' : '' ?><?= $email ?>
                        </p>
                    </td>
                    <td valign="middle" align="right">
                        <p style="color:#fff;font-size:11px;margin:0 0 3px;opacity:.8;">RECEIPT</p>
                        <p style="color:#fff;font-size:20px;font-weight:900;margin:0;letter-spacing:1px;">
                            <?= $recNo ?>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- ── GREETING ── -->
    <tr>
        <td style="padding:22px 28px 10px;">
            <p style="margin:0 0 6px;font-size:15px;color:#0F172A;">
                Dear <strong><?= $student ?></strong>,
            </p>
            <p style="margin:0;font-size:13px;color:#475569;line-height:1.6;">
                Your payment has been successfully recorded. Please find your receipt details below.
                A PDF copy of this receipt is also attached to this email.
            </p>
        </td>
    </tr>

    <!-- ── RECEIPT META STRIP ── -->
    <tr>
        <td style="padding:0 28px 16px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                   style="background:#F8FAFC;border-radius:8px;border:1px solid #E2E8F0;">
                <tr>
                    <td style="padding:12px 16px;border-right:1px solid #E2E8F0;" width="25%">
                        <p style="margin:0 0 2px;font-size:10px;color:#94A3B8;font-weight:700;text-transform:uppercase;">Date (AD)</p>
                        <p style="margin:0;font-size:13px;font-weight:700;color:#0F172A;"><?= $dateAD ?></p>
                    </td>
                    <?php if ($dateBS): ?>
                    <td style="padding:12px 16px;border-right:1px solid #E2E8F0;" width="25%">
                        <p style="margin:0 0 2px;font-size:10px;color:#94A3B8;font-weight:700;text-transform:uppercase;">Date (BS)</p>
                        <p style="margin:0;font-size:13px;font-weight:700;color:#0F172A;"><?= $dateBS ?></p>
                    </td>
                    <?php endif; ?>
                    <td style="padding:12px 16px;border-right:1px solid #E2E8F0;" width="25%">
                        <p style="margin:0 0 2px;font-size:10px;color:#94A3B8;font-weight:700;text-transform:uppercase;">Payment Mode</p>
                        <p style="margin:0;font-size:13px;font-weight:700;color:#0F172A;"><?= $payMode ?></p>
                    </td>
                    <?php if ($txnId): ?>
                    <td style="padding:12px 16px;" width="25%">
                        <p style="margin:0 0 2px;font-size:10px;color:#94A3B8;font-weight:700;text-transform:uppercase;">Txn ID</p>
                        <p style="margin:0;font-size:12px;font-weight:700;color:#0F172A;word-break:break-all;"><?= $txnId ?></p>
                    </td>
                    <?php endif; ?>
                </tr>
            </table>
        </td>
    </tr>

    <!-- ── STUDENT INFO ── -->
    <tr>
        <td style="padding:0 28px 16px;">
            <p style="margin:0 0 8px;font-size:10px;font-weight:700;letter-spacing:1px;
                      text-transform:uppercase;color:#00B894;border-left:3px solid #00B894;
                      padding-left:8px;">Student Information</p>
            <table width="100%" cellpadding="6" cellspacing="0" border="0"
                   style="border:1px solid #E2E8F0;border-radius:6px;font-size:13px;">
                <tr style="background:#F8FAFC;">
                    <td style="width:40%;font-weight:600;color:#475569;border-bottom:1px solid #F1F5F9;padding:8px 12px;">Student Name</td>
                    <td style="border-bottom:1px solid #F1F5F9;padding:8px 12px;"><strong><?= $student ?></strong></td>
                    <td style="width:30%;font-weight:600;color:#475569;border-bottom:1px solid #F1F5F9;padding:8px 12px;">Student ID</td>
                    <td style="border-bottom:1px solid #F1F5F9;padding:8px 12px;"><?= $studentId ?: '—' ?></td>
                </tr>
                <tr>
                    <td style="font-weight:600;color:#475569;padding:8px 12px;">Course</td>
                    <td style="padding:8px 12px;"><?= $course ?: '—' ?></td>
                    <td style="font-weight:600;color:#475569;padding:8px 12px;">Batch</td>
                    <td style="padding:8px 12px;"><?= $batch ?: '—' ?></td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- ── PAYMENT BREAKDOWN ── -->
    <tr>
        <td style="padding:0 28px 16px;">
            <p style="margin:0 0 8px;font-size:10px;font-weight:700;letter-spacing:1px;
                      text-transform:uppercase;color:#00B894;border-left:3px solid #00B894;
                      padding-left:8px;">Payment Breakdown</p>
            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                   style="border:1px solid #E2E8F0;border-radius:6px;overflow:hidden;font-size:13px;">
                <!-- Header -->
                <tr style="background:#0F172A;">
                    <td style="padding:9px 14px;color:#fff;font-weight:700;font-size:11px;">Description</td>
                    <td style="padding:9px 14px;color:#fff;font-weight:700;font-size:11px;text-align:right;">Amount</td>
                </tr>
                <!-- Items -->
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $i => $item): ?>
                    <tr style="background:<?= $i % 2 === 0 ? '#fff' : '#F8FAFC' ?>;">
                        <td style="padding:8px 14px;border-bottom:1px solid #F1F5F9;">
                            <?= eh($item['name'] ?? '') ?>
                            <span style="font-size:10px;color:#94A3B8;margin-left:6px;">(<?= ucfirst($item['type'] ?? '') ?>)</span>
                        </td>
                        <td style="padding:8px 14px;text-align:right;border-bottom:1px solid #F1F5F9;font-weight:600;">
                            <?= emfmt($item['amount'] ?? 0) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td style="padding:8px 14px;border-bottom:1px solid #F1F5F9;">
                        <?= $course ?><?= $batch ? " ({$batch})" : '' ?>
                    </td>
                    <td style="padding:8px 14px;text-align:right;border-bottom:1px solid #F1F5F9;font-weight:600;">
                        <?= emfmt($courseFee) ?>
                    </td>
                </tr>
                <?php endif; ?>

                <?php if ($fineAmt > 0): ?>
                <tr style="color:#E11D48;">
                    <td style="padding:8px 14px;border-bottom:1px solid #F1F5F9;">Late Fine Applied</td>
                    <td style="padding:8px 14px;text-align:right;border-bottom:1px solid #F1F5F9;font-weight:600;">+ <?= emfmt($fineAmt) ?></td>
                </tr>
                <?php endif; ?>

                <?php if ($discount > 0): ?>
                <tr style="color:#007a62;">
                    <td style="padding:8px 14px;border-bottom:1px solid #F1F5F9;">Discount</td>
                    <td style="padding:8px 14px;text-align:right;border-bottom:1px solid #F1F5F9;font-weight:600;">- <?= emfmt($discount) ?></td>
                </tr>
                <?php endif; ?>

                <!-- Totals -->
                <tr style="background:#0F172A;color:#fff;">
                    <td style="padding:10px 14px;font-weight:700;">Total Due</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:800;font-size:15px;"><?= emfmt($totalDue) ?></td>
                </tr>
                <tr style="background:#dcfce7;color:#16a34a;">
                    <td style="padding:10px 14px;font-weight:800;">&#10003; Amount Paid</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:900;font-size:16px;"><?= emfmt($paidAmt) ?></td>
                </tr>
                <?php if ($remaining > 0): ?>
                <tr style="background:#fff1f4;color:#E11D48;">
                    <td style="padding:10px 14px;font-weight:700;">&#9888; Remaining Balance</td>
                    <td style="padding:10px 14px;text-align:right;font-weight:800;"><?= emfmt($remaining) ?></td>
                </tr>
                <?php else: ?>
                <tr style="background:#dcfce7;color:#16a34a;">
                    <td style="padding:8px 14px;font-size:11px;">&#10003; Fully Paid</td>
                    <td style="padding:8px 14px;text-align:right;font-size:11px;font-weight:700;">Balance: 0.00</td>
                </tr>
                <?php endif; ?>
            </table>
        </td>
    </tr>

    <!-- ── REMARKS ── -->
    <?php if ($remarks): ?>
    <tr>
        <td style="padding:0 28px 16px;">
            <div style="background:#fffbeb;border-left:4px solid #d97706;border-radius:4px;
                        padding:10px 14px;font-size:13px;color:#1E293B;">
                <strong style="color:#d97706;">Remarks:</strong> <?= $remarks ?>
            </div>
        </td>
    </tr>
    <?php endif; ?>

    <!-- ── VERIFY ── -->
    <?php if ($verifyUrl): ?>
    <tr>
        <td style="padding:0 28px 20px;">
            <div style="background:#F0FDF4;border:1px solid #bbf7d0;border-radius:8px;
                        padding:14px 18px;text-align:center;">
                <p style="margin:0 0 6px;font-size:12px;color:#64748B;">
                    Verify this receipt online at:
                </p>
                <a href="<?= $verifyUrl ?>" style="color:#007a62;font-weight:700;font-size:13px;
                   word-break:break-all;"><?= $verifyUrl ?></a>
                <p style="margin:6px 0 0;font-size:11px;color:#94A3B8;">
                    Receipt No: <strong><?= $recNo ?></strong> &bull; A PDF copy is attached.
                </p>
            </div>
        </td>
    </tr>
    <?php endif; ?>

    <!-- ── FOOTER ── -->
    <tr>
        <td style="background:#F8FAFC;border-top:1px solid #E2E8F0;padding:16px 28px;
                   text-align:center;">
            <p style="margin:0 0 4px;font-size:11px;color:#94A3B8;">
                This is a computer-generated receipt. No signature required.
            </p>
            <p style="margin:0;font-size:11px;color:#94A3B8;">
                <?= $inst ?> &bull; <?= $phone ?>
                <?= $email ? ' &bull; ' . $email : '' ?>
            </p>
        </td>
    </tr>

</table><!-- end card -->
</td></tr>
</table><!-- end outer -->

</body>
</html>
