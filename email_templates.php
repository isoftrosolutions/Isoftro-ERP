<?php

// ============================================================
// HAMRO LABS ERP — Email Template Design System
// Professional, clean email templates — no emojis
// Brand: #009E7E (primary), contextual status colors
// Font: Plus Jakarta Sans
// ============================================================

// ── BASE WRAPPER ─────────────────────────────────────────────
function emailBase(string $content, string $accentColor = '#009E7E'): string {
    return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:\'Plus Jakarta Sans\',\'Helvetica Neue\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:36px 16px;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

      <!-- HEADER -->
      <tr>
        <td style="background:' . $accentColor . ';border-radius:16px 16px 0 0;padding:28px 48px;">
          <p style="margin:0;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,0.65);">{{institute_name}}</p>
        </td>
      </tr>

      <!-- BODY -->
      <tr>
        <td style="background:#ffffff;padding:44px 48px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
          ' . $content . '
        </td>
      </tr>

      <!-- FOOTER -->
      <tr>
        <td style="background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 16px 16px;padding:24px 48px;text-align:center;">
          <p style="margin:0;font-size:14px;font-weight:700;color:' . $accentColor . ';">{{institute_name}}</p>
          <p style="margin:8px 0 0;font-size:12px;color:#94a3b8;">{{institute_phone}} &nbsp;&nbsp;|&nbsp;&nbsp; {{institute_email}}</p>
          <p style="margin:12px 0 0;font-size:11px;color:#cbd5e1;">This is an automated message. Please do not reply directly to this email.</p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>';
}

// ── REUSABLE COMPONENTS ──────────────────────────────────────

function greeting(): string {
    return '<p style="font-size:16px;margin:0 0 20px;line-height:1.6;color:#334155;">
        Dear <strong style="color:#0f172a;">{{student_name}}</strong>,
    </p>';
}

function headingBlock(string $title, string $subtitle = '', string $color = '#009E7E'): string {
    $sub = $subtitle
        ? '<p style="margin:8px 0 0;font-size:14px;color:#64748b;font-weight:400;">' . $subtitle . '</p>'
        : '';
    return '<div style="margin-bottom:32px;padding-bottom:24px;border-bottom:2px solid #f1f5f9;">
        <h2 style="margin:0;font-size:24px;font-weight:800;color:' . $color . ';letter-spacing:-0.4px;">' . $title . '</h2>
        ' . $sub . '
    </div>';
}

function infoCard(array $rows, string $title = '', string $accentColor = '#009E7E'): string {
    $rowsHtml = '';
    foreach ($rows as $label => $value) {
        if ($value === '') {
            $rowsHtml .= '<tr>
                <td colspan="2" style="padding:9px 0;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9;">' . $label . '</td>
            </tr>';
        } else {
            $rowsHtml .= '<tr>
                <td style="padding:11px 0;font-size:13px;color:#64748b;font-weight:600;width:44%;border-bottom:1px solid #f1f5f9;vertical-align:top;">' . $label . '</td>
                <td style="padding:11px 0;font-size:13px;color:#1e293b;border-bottom:1px solid #f1f5f9;vertical-align:top;">' . $value . '</td>
            </tr>';
        }
    }
    $titleHtml = $title
        ? '<p style="margin:0 0 16px;font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:#94a3b8;">' . $title . '</p>'
        : '';
    return '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin:24px 0;">
        ' . $titleHtml . '
        <table width="100%" cellpadding="0" cellspacing="0">' . $rowsHtml . '</table>
    </div>';
}

function statusBadge(string $label, string $color, string $bg): string {
    return '<span style="display:inline-block;background:' . $bg . ';color:' . $color . ';font-size:11px;font-weight:700;padding:4px 12px;border-radius:4px;letter-spacing:0.8px;text-transform:uppercase;">' . $label . '</span>';
}

function ctaButton(string $label, string $url = '{{login_url}}', string $color = '#009E7E'): string {
    return '<div style="text-align:center;margin:32px 0;">
        <a href="' . $url . '" style="display:inline-block;background:' . $color . ';color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;padding:14px 40px;border-radius:8px;letter-spacing:0.5px;text-transform:uppercase;">' . $label . '</a>
    </div>';
}

function alertBox(string $message, string $type = 'warning'): string {
    $styles = [
        'warning' => ['bg' => '#fffbeb', 'border' => '#f59e0b', 'text' => '#78350f', 'label' => 'Notice'],
        'danger'  => ['bg' => '#fef2f2', 'border' => '#ef4444', 'text' => '#7f1d1d', 'label' => 'Important'],
        'info'    => ['bg' => '#eff6ff', 'border' => '#3b82f6', 'text' => '#1e3a8a', 'label' => 'Information'],
        'success' => ['bg' => '#f0fdf4', 'border' => '#22c55e', 'text' => '#14532d', 'label' => 'Confirmed'],
    ];
    $s = $styles[$type] ?? $styles['info'];
    return '<div style="background:' . $s['bg'] . ';border-left:3px solid ' . $s['border'] . ';border-radius:0 8px 8px 0;padding:14px 18px;margin:20px 0;">
        <p style="margin:0 0 3px;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:' . $s['border'] . ';">' . $s['label'] . '</p>
        <p style="margin:0;font-size:13px;color:' . $s['text'] . ';line-height:1.6;">' . $message . '</p>
    </div>';
}

function scoreBlock(): string {
    return '<div style="text-align:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:32px;margin:24px 0;">
        <p style="margin:0 0 4px;font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:#94a3b8;">Your Result</p>
        <p style="margin:0;font-size:52px;font-weight:800;color:#009E7E;letter-spacing:-2px;">{{percentage}}%</p>
        <p style="margin:8px 0 0;font-size:14px;font-weight:600;color:#475569;">Grade: {{grade}} &nbsp;&nbsp;|&nbsp;&nbsp; {{result_status}}</p>
    </div>';
}

function otpBlock(): string {
    return '<div style="text-align:center;margin:32px 0;">
        <p style="margin:0 0 12px;font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:#94a3b8;">Verification Code</p>
        <div style="display:inline-block;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 48px;">
            <span style="font-size:36px;font-weight:800;letter-spacing:12px;color:#0f172a;font-variant-numeric:tabular-nums;">{{reset_token}}</span>
        </div>
    </div>';
}

function divider(): string {
    return '<hr style="border:none;border-top:1px solid #f1f5f9;margin:28px 0;">';
}

function infoNote(string $text): string {
    return '<p style="font-size:12px;color:#94a3b8;text-align:center;margin:16px 0 0;line-height:1.6;">' . $text . '</p>';
}

function remarksBlock(string $heading, string $content): string {
    return '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;margin:24px 0;">
        <p style="margin:0 0 8px;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#94a3b8;">' . $heading . '</p>
        <p style="margin:0;font-size:13px;color:#475569;line-height:1.8;">' . $content . '</p>
    </div>';
}

// ── TEMPLATES ────────────────────────────────────────────────

        return [

// ──────────────────────────────────────────────────────────────
// 1. STUDENT REGISTRATION SUCCESS
// ──────────────────────────────────────────────────────────────
'student_registration_success' => [
    'subject' => 'Registration Successful — {{institute_name}}',
    'body' => emailBase(
        headingBlock('Registration Successful', 'Your enrollment has been confirmed.') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            You have been successfully enrolled in <strong style="color:#0f172a;">{{course_name}}</strong>. We are pleased to welcome you.
        </p>' .
        infoCard([
            'Course'      => '{{course_name}}',
            'Roll Number' => '{{roll_no}}',
            'Batch'       => '{{batch_name}}',
        ], 'Enrollment Details') .
        infoCard([
            'Portal URL' => '<a href="{{login_url}}" style="color:#009E7E;text-decoration:none;">{{login_url}}</a>',
            'Username'   => '{{student_email}}',
            'Password'   => '<code style="background:#f1f5f9;padding:2px 8px;border-radius:4px;font-size:13px;font-family:monospace;">{{temp_password}}</code>',
        ], 'Login Credentials') .
        alertBox('Please update your password immediately after your first login.', 'info') .
        ctaButton('Access Student Portal', '{{login_url}}')
    ),
],

// ──────────────────────────────────────────────────────────────
// 2. STUDENT ACCOUNT VERIFICATION
// ──────────────────────────────────────────────────────────────
'student_account_verification' => [
    'subject' => 'Verify Your Email Address — {{institute_name}}',
    'body' => emailBase(
        headingBlock('Email Verification Required', 'Activate your account to get started.', '#2563EB') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Thank you for registering at <strong style="color:#0f172a;">{{institute_name}}</strong>. Please verify your email address to complete your account setup.
        </p>' .
        infoCard([
            'Roll Number' => '{{roll_no}}',
            'Email'       => '{{student_email}}',
            'Course'      => '{{course_name}}',
        ], 'Account Details', '#2563EB') .
        ctaButton('Verify Email Address', '{{verification_link}}', '#2563EB') .
        alertBox('This verification link will expire in 24 hours.', 'warning') .
        divider() .
        infoNote('If you did not create this account, please contact us at {{institute_email}}.'),
        '#2563EB'
    ),
],

// ──────────────────────────────────────────────────────────────
// 3. STUDENT PROFILE UPDATED
// ──────────────────────────────────────────────────────────────
'student_profile_updated' => [
    'subject' => 'Profile Updated — {{institute_name}}',
    'body' => emailBase(
        headingBlock('Profile Updated Successfully', 'Your information has been saved.') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Your profile was updated on <strong style="color:#0f172a;">{{current_date}}</strong>. The following details are now on record.
        </p>' .
        infoCard([
            'Name'        => '{{student_name}}',
            'Roll Number' => '{{roll_no}}',
            'Phone'       => '{{phone}}',
            'Email'       => '{{student_email}}',
        ], 'Updated Details') .
        alertBox('If you did not authorise these changes, please contact the administration office at {{institute_phone}} immediately.', 'danger') .
        ctaButton('Review My Profile', '{{login_url}}')
    ),
],

// ──────────────────────────────────────────────────────────────
// 4. PASSWORD RESET REQUEST
// ──────────────────────────────────────────────────────────────
'password_reset_request' => [
    'subject' => 'Password Reset Request — {{institute_name}}',
    'body' => emailBase(
        headingBlock('Password Reset', 'Use the code below to reset your password.', '#7C3AED') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            We received a request to reset your password. Enter the verification code below to proceed. This code is valid for <strong>30 minutes</strong>.
        </p>' .
        otpBlock() .
        ctaButton('Reset Password', '{{reset_link}}', '#7C3AED') .
        divider() .
        alertBox('If you did not request a password reset, you may safely ignore this email. Your password will remain unchanged.', 'warning'),
        '#7C3AED'
    ),
],

// ──────────────────────────────────────────────────────────────
// 5. PASSWORD CHANGED SUCCESS
// ──────────────────────────────────────────────────────────────
'password_changed_success' => [
    'subject' => 'Password Changed — {{institute_name}}',
    'body' => emailBase(
        headingBlock('Password Changed Successfully', 'Your account security has been updated.') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Your account password was changed on <strong style="color:#0f172a;">{{current_date}}</strong>.
        </p>' .
        alertBox('If you did not make this change, reset your password immediately and contact our security team at {{institute_phone}}.', 'danger') .
        infoCard([
            'Never share your password with anyone'           => '',
            'Use a unique password for your account'          => '',
            'Enable two-factor authentication if available'   => '',
        ], 'Security Recommendations') .
        ctaButton('Go to My Portal', '{{login_url}}')
    ),
],

// ──────────────────────────────────────────────────────────────
// 6. PAYMENT SUCCESS — FULL
// ──────────────────────────────────────────────────────────────
'payment_success_full' => [
    'subject' => 'Payment Confirmed — Receipt #{{receipt_no}}',
    'body' => emailBase(
        headingBlock('Payment Received', 'Your full fee payment has been recorded.') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Your payment for <strong style="color:#0f172a;">{{course_name}}</strong> has been received and confirmed.
        </p>' .
        infoCard([
            'Receipt No.'    => '<strong>{{receipt_no}}</strong>',
            'Date'           => '{{paid_date}}',
            'Amount Paid'    => '<strong style="color:#009E7E;font-size:15px;">NPR {{amount}}</strong>',
            'Status'         => statusBadge('Paid in Full', '#14532d', '#dcfce7'),
        ], 'Receipt Details') .
        ctaButton('Download Receipt', '{{login_url}}')
    ),
],

// ──────────────────────────────────────────────────────────────
// 7. PAYMENT SUCCESS — PARTIAL
// ──────────────────────────────────────────────────────────────
'payment_success_partial' => [
    'subject' => 'Partial Payment Received — Receipt #{{receipt_no}}',
    'body' => emailBase(
        headingBlock('Partial Payment Received', 'A balance amount remains outstanding.', '#D97706') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Your partial payment for <strong style="color:#0f172a;">{{course_name}}</strong> has been received and recorded.
        </p>' .
        infoCard([
            'Receipt No.'       => '<strong>{{receipt_no}}</strong>',
            'Date'              => '{{paid_date}}',
            'Amount Paid'       => '<strong style="color:#009E7E;">NPR {{amount}}</strong>',
            'Remaining Balance' => '<strong style="color:#DC2626;">NPR {{balance}}</strong>',
            'Due Date'          => '<strong>{{due_date}}</strong>',
            'Status'            => statusBadge('Partially Paid', '#92400e', '#fef3c7'),
        ], 'Receipt Details', '#D97706') .
        alertBox('Please clear the remaining balance before {{due_date}} to avoid late payment charges.', 'warning') .
        ctaButton('Pay Remaining Balance', '{{login_url}}', '#D97706'),
        '#D97706'
    ),
],

// ──────────────────────────────────────────────────────────────
// 8. PAYMENT FAILED
// ──────────────────────────────────────────────────────────────
'payment_failed' => [
    'subject' => 'Payment Unsuccessful — Action Required',
    'body' => emailBase(
        headingBlock('Payment Failed', 'Your transaction could not be completed.', '#DC2626') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            We were unable to process your recent payment. Please review the details below and try again.
        </p>' .
        infoCard([
            'Date'           => '{{current_date}}',
            'Amount'         => 'NPR {{amount}}',
            'Payment Method' => '{{payment_mode}}',
            'Transaction ID' => '{{transaction_id}}',
            'Status'         => statusBadge('Failed', '#7f1d1d', '#fee2e2'),
        ], 'Transaction Details', '#DC2626') .
        '<p style="font-size:12px;font-weight:700;color:#475569;margin:20px 0 10px;text-transform:uppercase;letter-spacing:0.8px;">Common Reasons for Failure</p>
        <ul style="margin:0 0 20px;padding-left:20px;color:#64748b;font-size:13px;line-height:2.2;">
            <li>Insufficient account balance</li>
            <li>Network or connectivity issues</li>
            <li>Bank or payment gateway timeout</li>
            <li>Transaction limit exceeded</li>
        </ul>' .
        alertBox('Amount due: NPR {{amount_due}}. Please complete payment before {{due_date}} to avoid late charges.', 'danger') .
        ctaButton('Retry Payment', '{{login_url}}', '#DC2626'),
        '#DC2626'
    ),
],

// ──────────────────────────────────────────────────────────────
// 9. PAYMENT PENDING
// ──────────────────────────────────────────────────────────────
'payment_pending' => [
    'subject' => 'Payment Under Verification — {{institute_name}}',
    'body' => emailBase(
        headingBlock('Payment Under Verification', 'Our team is reviewing your transaction.', '#D97706') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            We have received your payment submission. It is currently under review by our accounts team. You will be notified once verification is complete.
        </p>' .
        infoCard([
            'Transaction Ref.' => '{{transaction_id}}',
            'Date Submitted'   => '{{current_date}}',
            'Amount'           => 'NPR {{amount}}',
            'Payment Method'   => '{{payment_mode}}',
            'Status'           => statusBadge('Pending Verification', '#78350f', '#fef3c7'),
        ], 'Transaction Details', '#D97706') .
        infoCard([
            'Bank Transfer'  => '1 – 2 business days',
            'Cheque'         => '3 – 5 business days',
            'Online Payment' => 'Within 24 hours',
        ], 'Expected Verification Time') .
        ctaButton('Track Payment Status', '{{login_url}}', '#D97706'),
        '#D97706'
    ),
],

// ──────────────────────────────────────────────────────────────
// 10. FEE REMINDER — 7 DAYS
// ──────────────────────────────────────────────────────────────
'fee_reminder_7days' => [
    'subject' => 'Fee Payment Reminder — Due in 7 Days',
    'body' => emailBase(
        headingBlock('Payment Due in 7 Days', 'Please arrange payment at your earliest convenience.', '#0369A1') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            This is a reminder that your fee payment for <strong style="color:#0f172a;">{{course_name}}</strong> is due in <strong>7 days</strong>.
        </p>' .
        infoCard([
            'Amount Due'  => '<strong style="color:#DC2626;">NPR {{amount_due}}</strong>',
            'Amount Paid' => 'NPR {{amount_paid}}',
            'Balance'     => 'NPR {{balance}}',
            'Due Date'    => '<strong>{{due_date}}</strong>',
            'Installment' => '{{installment_no}}',
        ], 'Fee Summary', '#0369A1') .
        alertBox('Late payment will incur daily fines and may restrict access to examinations.', 'warning') .
        ctaButton('Pay Now', '{{login_url}}', '#0369A1') .
        infoNote('Already paid? Please upload your payment proof via the portal or send it to {{institute_email}}.'),
        '#0369A1'
    ),
],

// ──────────────────────────────────────────────────────────────
// 11. FEE REMINDER — 3 DAYS
// ──────────────────────────────────────────────────────────────
'fee_reminder_3days' => [
    'subject' => 'Urgent: Fee Payment Due in 3 Days — {{institute_name}}',
    'body' => emailBase(
        headingBlock('Payment Due in 3 Days', 'Immediate action is required.', '#EA580C') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Your fee payment for <strong style="color:#0f172a;">{{course_name}}</strong> is due in <strong>3 days</strong>. Please complete your payment to avoid penalties.
        </p>' .
        infoCard([
            'Total Due'    => '<strong style="color:#DC2626;font-size:17px;">NPR {{balance}}</strong>',
            'Paid to Date' => 'NPR {{amount_paid}}',
            'Due Date'     => '<strong style="color:#EA580C;">{{due_date}}</strong>',
            'Installment'  => '{{installment_no}}',
            'Roll Number'  => '{{roll_no}}',
        ], 'Outstanding Payment', '#EA580C') .
        alertBox('After {{due_date}}: Daily late fines will apply. Examination access may be suspended. Class attendance may be restricted.', 'danger') .
        ctaButton('Pay Immediately', '{{login_url}}', '#EA580C'),
        '#EA580C'
    ),
],

// ──────────────────────────────────────────────────────────────
// 12. FEE OVERDUE NOTICE
// ──────────────────────────────────────────────────────────────
'fee_overdue_notice' => [
    'subject' => 'Overdue Notice — Immediate Payment Required',
    'body' => emailBase(
        headingBlock('Payment Overdue', 'Your fee payment deadline has passed.', '#DC2626') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Your fee payment for <strong style="color:#0f172a;">{{course_name}}</strong> is overdue. Please settle the outstanding balance immediately to avoid further consequences.
        </p>' .
        infoCard([
            'Original Amount'     => 'NPR {{amount_due}}',
            'Amount Paid'         => 'NPR {{amount_paid}}',
            'Outstanding Balance' => '<strong style="color:#DC2626;">NPR {{balance}}</strong>',
            'Days Overdue'        => '<strong style="color:#DC2626;">{{days_overdue}} days</strong>',
            'Late Fine Applied'   => 'NPR {{fine_applied}}',
            'Total Payable Now'   => '<strong style="color:#DC2626;font-size:17px;">NPR {{total_payable}}</strong>',
        ], 'Overdue Summary', '#DC2626') .
        alertBox('Academic activities are currently restricted. Results and certificates will be withheld until all dues are cleared.', 'danger') .
        ctaButton('Clear Dues Now', '{{login_url}}', '#DC2626') .
        remarksBlock('Facing a Financial Difficulty?', 'Please visit our accounts office to discuss installment plans, fine waiver requests, or payment extensions. Our team is available to assist you.'),
        '#DC2626'
    ),
],

// ──────────────────────────────────────────────────────────────
// 13. INVOICE GENERATED
// ──────────────────────────────────────────────────────────────
'invoice_generated' => [
    'subject' => 'New Invoice Generated — #{{invoice_number}}',
    'body' => emailBase(
        headingBlock('Invoice Issued', 'A new fee invoice has been generated for your account.', '#0369A1') .
        greeting() .
        infoCard([
            'Invoice Number' => '<strong>{{invoice_number}}</strong>',
            'Issue Date'     => '{{invoice_date}}',
            'Due Date'       => '<strong style="color:#EA580C;">{{due_date}}</strong>',
            'Total Amount'   => '<strong style="color:#009E7E;font-size:16px;">NPR {{total_amount}}</strong>',
            'Course'         => '{{course_name}}',
            'Batch'          => '{{batch_name}}',
        ], 'Invoice Details', '#0369A1') .
        ctaButton('Download Invoice', '{{login_url}}', '#0369A1') .
        alertBox('Payment must be completed before {{due_date}} to avoid late charges.', 'warning'),
        '#0369A1'
    ),
],

// ──────────────────────────────────────────────────────────────
// 14. PAYMENT REFUND PROCESSED
// ──────────────────────────────────────────────────────────────
'payment_refund_processed' => [
    'subject' => 'Refund Processed — {{receipt_no}}',
    'body' => emailBase(
        headingBlock('Refund Processed', 'Your refund has been approved and initiated.') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Your refund request has been reviewed and processed. Please allow the standard processing time for the amount to reflect in your account.
        </p>' .
        infoCard([
            'Original Receipt'      => '{{receipt_no}}',
            'Original Payment Date' => '{{original_payment_date}}',
            'Refund Amount'         => '<strong style="color:#009E7E;font-size:15px;">NPR {{amount}}</strong>',
            'Refund Date'           => '{{refund_date}}',
            'Refund Method'         => '{{refund_method}}',
            'Transaction ID'        => '{{transaction_id}}',
        ], 'Refund Details') .
        infoCard([
            'Bank Transfer' => '3 – 5 business days',
            'Cheque'        => 'Please collect from the accounts office',
        ], 'Processing Timeline') .
        ctaButton('Track Refund Status', '{{login_url}}')
    ),
],

// ──────────────────────────────────────────────────────────────
// 15. COURSE ENROLLMENT SUCCESS
// ──────────────────────────────────────────────────────────────
'course_enrollment_success' => [
    'subject' => 'Enrollment Confirmed — {{course_name}}',
    'body' => emailBase(
        headingBlock('Enrollment Confirmed', 'Welcome to your new course.') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            You have been successfully enrolled in <strong style="color:#0f172a;">{{course_name}}</strong>. We wish you the very best in your studies.
        </p>' .
        infoCard([
            'Course'      => '{{course_name}}',
            'Course Code' => '{{course_code}}',
            'Batch'       => '{{batch_name}}',
            'Shift'       => '{{batch_shift}}',
            'Roll Number' => '{{roll_no}}',
            'Start Date'  => '{{batch_start_date}}',
            'Duration'    => '{{course_duration}}',
        ], 'Enrollment Details') .
        ctaButton('Access Student Portal', '{{login_url}}')
    ),
],

// ──────────────────────────────────────────────────────────────
// 16. EXAM SCHEDULE PUBLISHED
// ──────────────────────────────────────────────────────────────
'exam_schedule_published' => [
    'subject' => 'Exam Schedule Published — {{exam_name}}',
    'body' => emailBase(
        headingBlock('Exam Schedule Announced', '{{exam_name}}', '#7C3AED') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            The examination schedule for <strong style="color:#0f172a;">{{exam_name}}</strong> has been published. Please review the details and prepare accordingly.
        </p>' .
        infoCard([
            'Exam Name'   => '<strong>{{exam_name}}</strong>',
            'Date'        => '{{exam_date}}',
            'Time'        => '{{exam_time}}',
            'Duration'    => '{{exam_duration}} minutes',
            'Total Marks' => '{{total_marks}}',
            'Venue'       => '{{exam_venue}}',
            'Room Number' => '{{room_number}}',
        ], 'Examination Details', '#7C3AED') .
        alertBox('Report 15 minutes before the scheduled time. Carry your ID card and admit card. Students who arrive late will not be permitted entry. Fee clearance is mandatory.', 'warning') .
        ctaButton('Download Admit Card', '{{login_url}}', '#7C3AED'),
        '#7C3AED'
    ),
],

// ──────────────────────────────────────────────────────────────
// 17. EXAM RESULTS PUBLISHED
// ──────────────────────────────────────────────────────────────
'exam_results_published' => [
    'subject' => 'Exam Results Available — {{exam_name}}',
    'body' => emailBase(
        headingBlock('Results Published', '{{exam_name}}') .
        greeting() .
        scoreBlock() .
        infoCard([
            'Marks Obtained' => '{{marks_obtained}} / {{total_marks}}',
            'Percentage'     => '{{percentage}}%',
            'Grade'          => '{{grade}}',
            'Class Rank'     => '{{rank}} of {{total_students}}',
            'Class Average'  => '{{class_average}}%',
            'Highest Score'  => '{{highest_score}}',
        ], 'Result Summary') .
        ctaButton('View Detailed Results', '{{login_url}}')
    ),
],

// ──────────────────────────────────────────────────────────────
// 18. NEW ASSIGNMENT
// ──────────────────────────────────────────────────────────────
'assignment_new' => [
    'subject' => 'New Assignment — {{assignment_title}}',
    'body' => emailBase(
        headingBlock('New Assignment Posted', '{{subject_name}} — {{course_name}}', '#0369A1') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            A new assignment has been posted for your course. Please review the details and submit before the deadline.
        </p>' .
        infoCard([
            'Title'     => '<strong>{{assignment_title}}</strong>',
            'Subject'   => '{{subject_name}}',
            'Posted On' => '{{posted_date}}',
            'Due Date'  => '<strong style="color:#EA580C;">{{assignment_due_date}}</strong>',
            'Max Marks' => '{{max_marks}}',
        ], 'Assignment Details', '#0369A1') .
        alertBox('Late submissions may incur a grade penalty. Plagiarism will result in zero marks.', 'warning') .
        ctaButton('View Assignment', '{{login_url}}', '#0369A1'),
        '#0369A1'
    ),
],

// ──────────────────────────────────────────────────────────────
// 19. ASSIGNMENT SUBMISSION CONFIRMED
// ──────────────────────────────────────────────────────────────
'assignment_submission_confirmed' => [
    'subject' => 'Assignment Submitted — {{assignment_title}}',
    'body' => emailBase(
        headingBlock('Assignment Submitted', 'Your submission has been received successfully.') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Your assignment has been submitted. Your teacher will evaluate it and update your marks on the portal.
        </p>' .
        infoCard([
            'Assignment'   => '<strong>{{assignment_title}}</strong>',
            'Submitted On' => '{{submitted_at}}',
            'Due Date'     => '{{assignment_due_date}}',
            'File'         => '{{submission_filename}}',
            'Status'       => statusBadge('Submitted', '#14532d', '#dcfce7'),
        ], 'Submission Details') .
        ctaButton('View Submission', '{{login_url}}')
    ),
],

// ──────────────────────────────────────────────────────────────
// 20. ASSIGNMENT GRADED
// ──────────────────────────────────────────────────────────────
'assignment_graded' => [
    'subject' => 'Assignment Evaluated — {{assignment_title}}',
    'body' => emailBase(
        headingBlock('Assignment Graded', 'Your teacher has completed the evaluation.') .
        greeting() .
        infoCard([
            'Assignment'     => '<strong>{{assignment_title}}</strong>',
            'Submitted On'   => '{{submitted_at}}',
            'Evaluated On'   => '{{graded_at}}',
            'Marks Obtained' => '<strong style="color:#009E7E;font-size:16px;">{{marks_awarded}} / {{max_marks}}</strong>',
            'Percentage'     => '{{assignment_percentage}}%',
            'Grade'          => '{{assignment_grade}}',
            'Class Average'  => '{{class_average_marks}}',
        ], 'Evaluation Results') .
        '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin:24px 0;">
            <p style="margin:0 0 10px;font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:#94a3b8;">Teacher Feedback</p>
            <p style="margin:0;font-size:14px;color:#334155;line-height:1.8;font-style:italic;">{{feedback}}</p>
        </div>' .
        ctaButton('View Full Evaluation', '{{login_url}}')
    ),
],

// ──────────────────────────────────────────────────────────────
// 21. ATTENDANCE WARNING
// ──────────────────────────────────────────────────────────────
'attendance_warning' => [
    'subject' => 'Attendance Warning — {{institute_name}}',
    'body' => emailBase(
        headingBlock('Low Attendance Alert', 'Your attendance has fallen below the required minimum.', '#DC2626') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Our records show that your attendance for <strong style="color:#0f172a;">{{course_name}}</strong> has dropped below the required percentage. Please take corrective action immediately.
        </p>' .
        infoCard([
            'Current Attendance' => '<strong style="color:#DC2626;">{{attendance_percentage}}%</strong>',
            'Required Minimum'   => '{{required_attendance}}%',
            'Total Classes'      => '{{total_classes}}',
            'Classes Attended'   => '{{classes_attended}}',
            'Classes Missed'     => '{{classes_missed}}',
            'Period'             => '{{attendance_period}}',
        ], 'Attendance Summary', '#DC2626') .
        alertBox('Students who do not meet the minimum attendance requirement may be declared ineligible for final examinations as per institute policy.', 'danger') .
        ctaButton('View Attendance Record', '{{login_url}}', '#DC2626') .
        infoNote('For genuine medical absences, please submit a certificate to the administration office or email {{institute_email}}.'),
        '#DC2626'
    ),
],

// ──────────────────────────────────────────────────────────────
// 22. GENERAL ANNOUNCEMENT
// ──────────────────────────────────────────────────────────────
'general_announcement' => [
    'subject' => '{{announcement_title}} — {{institute_name}}',
    'body' => emailBase(
        headingBlock('{{announcement_title}}', 'Official Announcement — {{announcement_date}}', '#0369A1') .
        greeting() .
        '<div style="font-size:15px;color:#475569;line-height:1.8;margin:0 0 28px;">
            {{announcement_content}}
        </div>' .
        ctaButton('View on Portal', '{{login_url}}', '#0369A1'),
        '#0369A1'
    ),
],

// ──────────────────────────────────────────────────────────────
// 23. ACCOUNT SUSPENDED
// ──────────────────────────────────────────────────────────────
'account_suspended' => [
    'subject' => 'Account Suspension Notice — {{institute_name}}',
    'body' => emailBase(
        headingBlock('Account Suspended', 'Your student account has been temporarily suspended.', '#DC2626') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Your student account at <strong style="color:#0f172a;">{{institute_name}}</strong> has been suspended. During this period, access to all academic and portal services is restricted.
        </p>' .
        infoCard([
            'Account Status' => statusBadge('Suspended', '#7f1d1d', '#fee2e2'),
            'Date'           => '{{suspension_date}}',
            'Roll Number'    => '{{roll_no}}',
            'Reason'         => '{{suspension_reason}}',
        ], 'Suspension Details', '#DC2626') .
        remarksBlock('Steps to Restore Your Account', '{{restoration_instructions}}') .
        alertBox('Please contact the administration office immediately to resolve this matter and continue your studies.', 'danger'),
        '#DC2626'
    ),
],

// ──────────────────────────────────────────────────────────────
// 24. ACCOUNT REACTIVATED
// ──────────────────────────────────────────────────────────────
'account_reactivated' => [
    'subject' => 'Account Reactivated — {{institute_name}}',
    'body' => emailBase(
        headingBlock('Account Reactivated', 'Your full portal access has been restored.') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Your student account has been reactivated as of <strong style="color:#0f172a;">{{reactivation_date}}</strong>. You now have full access to all institute services.
        </p>' .
        infoCard([
            'Account Status'  => statusBadge('Active', '#14532d', '#dcfce7'),
            'Reactivated On'  => '{{reactivation_date}}',
            'Roll Number'     => '{{roll_no}}',
        ], 'Account Details') .
        ctaButton('Login to Portal', '{{login_url}}') .
        infoNote('Welcome back. We are glad to have you continuing your studies with us.')
    ),
],

// ──────────────────────────────────────────────────────────────
// 25. DOCUMENT VERIFICATION REQUIRED
// ──────────────────────────────────────────────────────────────
'document_verification_required' => [
    'subject' => 'Document Submission Required — {{institute_name}}',
    'body' => emailBase(
        headingBlock('Documents Required', 'Please submit the following documents at your earliest.', '#7C3AED') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Your student profile requires the following documents for verification. Please submit them before the deadline to avoid any disruption to your academic services.
        </p>' .
        infoCard([
            'Roll Number'         => '{{roll_no}}',
            'Course'              => '{{course_name}}',
            'Documents Required'  => '{{document_list}}',
            'Submission Deadline' => '<strong style="color:#EA580C;">{{submission_deadline}}</strong>',
        ], 'Submission Details', '#7C3AED') .
        '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;margin:24px 0;">
            <p style="margin:0 0 10px;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#94a3b8;">Submission Guidelines</p>
            <ul style="margin:0;padding-left:18px;font-size:13px;color:#475569;line-height:2.2;">
                <li>Accepted formats: PDF or JPG</li>
                <li>Maximum file size: 5 MB per file</li>
                <li>Ensure documents are clear and legible</li>
                <li>Office submissions: Bring originals with 2 photocopies</li>
            </ul>
        </div>' .
        ctaButton('Upload Documents', '{{login_url}}', '#7C3AED'),
        '#7C3AED'
    ),
],

// ──────────────────────────────────────────────────────────────
// 26. LEAVE REQUEST STATUS
// ──────────────────────────────────────────────────────────────
'leave_request_status' => [
    'subject' => 'Leave Application {{leave_status}} — {{institute_name}}',
    'body' => emailBase(
        headingBlock('Leave Application Update', 'Your application has been reviewed.') .
        greeting() .
        '<p style="font-size:15px;color:#475569;line-height:1.7;margin:0 0 24px;">
            Your leave application has been reviewed by the administration. Please find the details below.
        </p>' .
        infoCard([
            'Application ID' => '{{leave_request_id}}',
            'From Date'      => '{{from_date}}',
            'To Date'        => '{{to_date}}',
            'Duration'       => '{{leave_duration}} days',
            'Reason'         => '{{leave_reason}}',
            'Status'         => '<strong>{{leave_status}}</strong>',
            'Reviewed By'    => '{{reviewed_by}}',
            'Review Date'    => '{{review_date}}',
        ], 'Leave Details') .
        remarksBlock('Remarks', '{{leave_remarks}}') .
        ctaButton('View Leave History', '{{login_url}}')
    ),
],

        ];
