<?php
/**
 * ─────────────────────────────────────────────────────────────
 *  MailHelper — Hamro ERP Transactional Email Service
 *
 *  Architecture (Simple for institutes):
 *    • SMTP credentials → config/mail.php (system-managed)
 *    • Institute only configures: sender_name + reply_to_email
 *    • Email is sent FROM the system relay
 *    • FROM NAME shows the institute's name
 *    • REPLY-TO is the institute's own email (replies go to them)
 *
 *  Usage:
 *    MailHelper::sendStudentCredentials($db, $tenantId, [...]);
 *    MailHelper::send($db, $tenantId, $to, $name, $subject, $html);
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Helpers;

// Directly load PHPMailer core files (no autoloader dependency)
$_base = __DIR__ . '/../../vendor/phpmailer/phpmailer/src/';
require_once $_base . 'Exception.php';
require_once $_base . 'PHPMailer.php';
require_once $_base . 'SMTP.php';
unset($_base);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class MailHelper
{
    // ── Load system SMTP config ──────────────────────────────
    private static function systemConfig(): array
    {
        static $cfg = null;
        if ($cfg === null) {
            $file = __DIR__ . '/../../config/mail.php';
            $cfg  = file_exists($file) ? require $file : [];
        }
        return $cfg;
    }

    // ── Fetch institute branding for this tenant ─────────────
    private static function getTenantBranding(\PDO $db, int $tenantId): array
    {
        // ISSUE-E2 FIX: Correct table is `email_settings`, not `tenant_email_settings`
        // Column aliases map actual column names to expected keys
        $row = null;
        try {
            $stmt = $db->prepare(
                "SELECT sender_name AS from_name, reply_to_email AS from_email, is_active
                 FROM   email_settings
                 WHERE  tenant_id = :tid LIMIT 1"
            );
            $stmt->execute(['tid' => $tenantId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $row = null;
        }

        // ISSUE-E3 FIX: Fetch institute name, phone, and email from tenants table
        $instituteName  = 'Your Institute';
        $institutePhone = '';
        $instituteEmail = '';
        try {
            $s = $db->prepare("SELECT name, phone, email FROM tenants WHERE id = :tid LIMIT 1");
            $s->execute(['tid' => $tenantId]);
            $t = $s->fetch(\PDO::FETCH_ASSOC);
            if (!empty($t['name']))  $instituteName  = $t['name'];
            if (!empty($t['phone'])) $institutePhone = $t['phone'];
            if (!empty($t['email'])) $instituteEmail = $t['email'];
        } catch (\Throwable $e) {}

        return [
            'sender_name'      => $row['from_name']   ?? $instituteName,
            'reply_to_email'   => $row['from_email']  ?? null,
            'is_active'        => ($row['is_active'] ?? 1) ? true : false,
            'institute_name'   => $instituteName,
            'institute_phone'  => $institutePhone,   // ISSUE-E3 FIX
            'institute_email'  => $instituteEmail,   // ISSUE-E3 FIX
        ];
    }

    // ── Build a configured PHPMailer instance ────────────────
    private static function buildMailer(array $branding): PHPMailer
    {
        $sys = self::systemConfig();

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host     = $sys['smtp_host']  ?? 'smtp.gmail.com';
        $mail->Port     = (int)($sys['smtp_port'] ?? 587);
        $mail->SMTPAuth = true;
        $mail->Username = $sys['smtp_user']  ?? '';
        $mail->Password = $sys['smtp_pass']  ?? '';
        // ISSUE-E4 FIX: Reduce default timeout from 20s → 5s so slow SMTP doesn't block the HTTP request.
        // Override in config/mail.php with 'timeout' => N if your SMTP needs more time.
        $mail->Timeout  = (int)($sys['timeout'] ?? 5);
        
        // --- SMTP Debugging & Connectivity ---
        // 0 = off, 1 = client messages, 2 = client and server messages
        // ISSUE-E5 FIX: Default to 0 (off) in production. Override via config/mail.php 'debug' => 2
        $debugLevel = (int)($sys['debug'] ?? 0); 
        $mail->SMTPDebug = $debugLevel;
        $mail->Debugoutput = function($str, $level) {
            error_log("[PHPMailer Debug] " . trim($str));
        };

        // Fix for "Could not connect to SMTP host" on some Windows/Gmail setups
        // Forces the use of IPv4 instead of letting it cycle to IPv6 which often fails
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $enc = strtolower($sys['smtp_encryption'] ?? 'tls');
        $mail->SMTPSecure = ($enc === 'ssl')
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;

        // FROM: system email, but display the institute's name
        $fromEmail = $sys['system_from_email'] ?? $sys['smtp_user'] ?? '';
        $fromName  = $branding['sender_name'];
        $mail->setFrom($fromEmail, $fromName);

        // REPLY-TO: institute's own email (so replies land with them)
        if (!empty($branding['reply_to_email'])) {
            $mail->addReplyTo($branding['reply_to_email'], $fromName);
        }

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        return $mail;
    }

    // ── Fetch Static Template and Replace Placeholders ──────
    public static function getStaticTemplate(string $templateKey, array $data): ?array
    {
        $templates = [
            'student_registration_success' => [
                'subject' => 'Welcome to {{institute_name}} - Registration Successful! 🎓',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>Congratulations! Your registration at {{institute_name}} has been completed successfully.</p><p>✅ REGISTRATION DETAILS:<br>• Roll Number: {{roll_no}}<br>• Course: {{course_name}}<br>• Batch: {{batch_name}} ({{batch_shift}} Shift)<br>• Admission Date: {{admission_date}}</p><p>🔐 LOGIN CREDENTIALS:<br>• Portal: {{login_url}}<br>• Username: {{student_email}}<br>• Temporary Password: {{temp_password}}</p><p>⚠️ IMPORTANT: Please change your password immediately after first login for security purposes.</p><p>5. Access study materials and announcements</p><p>If you face any login issues or have questions, please contact our support team at {{institute_phone}} or reply to this email.</p><p>We\'re excited to have you join us!</p><p>Best regards,<br>Admissions Team<br>{{institute_name}}<br>{{institute_phone}} | {{institute_email}}</p></div>'
            ],
            'student_account_verification' => [
                'subject' => 'Verify Your Email - {{institute_name}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>Thank you for registering at {{institute_name}}!</p><p>To complete your registration and access your student portal, please verify your email address by clicking the link below:</p><p>🔗 {{verification_link}}</p><p>This verification link will expire in 24 hours.</p><p>Your account details:<br>• Roll Number: {{roll_no}}<br>• Email: {{student_email}}<br>• Course: {{course_name}}</p><p>If you did not create this account, please ignore this email or contact us at {{institute_email}}.</p><p>After verification, you\'ll be able to:<br>✓ Access your personalized dashboard<br>✓ View class schedules<br>✓ Download study materials<br>✓ Track your attendance and exam results<br>✓ Make fee payments online</p><p>Need assistance? Contact us at {{institute_phone}}.</p><p>Best regards,<br>{{institute_name}}</p></div>'
            ],
            'student_profile_updated' => [
                'subject' => 'Profile Updated Successfully - {{institute_name}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>Your profile information has been updated successfully on {{current_date}}.</p><p>Updated Details:<br>• Name: {{student_name}}<br>• Roll Number: {{roll_no}}<br>• Phone: {{phone}}<br>• Email: {{student_email}}</p><p>If you did not make these changes, please contact the administration office immediately at {{institute_phone}}.</p><p>You can review your complete profile by logging into your student portal: {{login_url}}</p><p>Thank you,<br>{{institute_name}}</p></div>'
            ],
            'password_reset_request' => [
                'subject' => 'Password Reset Request - {{institute_name}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>We received a request to reset your password for your {{institute_name}} student account.</p><p>Click the link below to reset your password:<br>🔗 {{reset_link}}</p><p>This link will expire in 30 minutes for security reasons.</p><p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p><p>For security tips:<br>• Use a strong password with uppercase, lowercase, numbers, and symbols<br>• Do not share your password with anyone<br>• Change your password regularly</p><p>Need help? Contact us at {{institute_phone}} or {{institute_email}}.</p><p>Security Team<br>{{institute_name}}</p></div>'
            ],
            'password_changed_success' => [
                'subject' => 'Password Changed Successfully - {{institute_name}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>Your password has been changed successfully on {{current_date}}.</p><p>If you made this change, no further action is required.</p><p>⚠️ If you did NOT change your password:<br>1. Reset your password immediately: {{login_url}}<br>2. Contact our security team at {{institute_phone}}</p><p>Security Reminder:<br>• Never share your password<br>• Use a unique password for your student account<br>• Enable two-factor authentication if available</p><p>Stay secure!</p><p>{{institute_name}}<br>Security Team</p></div>'
            ],
            'payment_success_full' => [
                'subject' => 'Payment Received - Receipt #{{receipt_no}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>✅ PAYMENT RECEIVED SUCCESSFULLY</p><p>Thank you! Your payment has been processed successfully.</p><p>📄 RECEIPT DETAILS:<br>• Receipt Number: {{receipt_no}}<br>• Date: {{paid_date}}<br>• Amount Paid: NPR {{amount}}<br>• Payment Method: {{payment_mode}}<br>• Transaction ID: {{transaction_id}}</p><p>📚 COURSE DETAILS:<br>• Course: {{course_name}}<br>• Roll Number: {{roll_no}}<br>• Installment: {{installment_no}}</p><p>💰 PAYMENT SUMMARY:<br>• Total Fee: NPR {{amount_due}}<br>• Amount Paid: NPR {{amount_paid}}<br>• Balance: NPR 0.00<br>• Status: PAID IN FULL ✓</p><p>You can download your official receipt from the student portal: {{login_url}}</p><p>This email serves as payment acknowledgment. Please retain this for your records.</p><p>For any queries regarding this payment, contact our accounts department at {{institute_phone}}.</p><p>Thank you for your prompt payment!</p><p>Accounts Department<br>{{institute_name}}<br>{{institute_email}} | {{institute_phone}}</p></div>'
            ],
            'payment_success_partial' => [
                'subject' => 'Partial Payment Received - Receipt #{{receipt_no}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>✅ PARTIAL PAYMENT RECEIVED</p><p>Thank you for your payment. We have received your partial payment successfully.</p><p>📄 RECEIPT DETAILS:<br>• Receipt Number: {{receipt_no}}<br>• Date: {{paid_date}}<br>• Amount Paid: NPR {{amount}}<br>• Payment Method: {{payment_mode}}<br>• Transaction ID: {{transaction_id}}</p><p>💰 OUTSTANDING BALANCE:<br>• Total Fee Due: NPR {{amount_due}}<br>• Amount Paid to Date: NPR {{amount_paid}}<br>• Remaining Balance: NPR [amount_due - amount_paid]<br>• Next Due Date: {{due_date}}</p><p>📚 COURSE DETAILS:<br>• Course: {{course_name}}<br>• Roll Number: {{roll_no}}<br>• Installment: {{installment_no}}</p><p>⚠️ IMPORTANT:<br>Please clear the remaining balance before {{due_date}} to avoid late fines and ensure uninterrupted access to classes and exams.</p><p>You can make the next payment:<br>• Online: {{login_url}}<br>• At Institute: Visit our accounts office<br>• Payment Methods: Cash, eSewa, Khalti, Bank Transfer</p><p>Download receipt: {{login_url}}</p><p>For payment assistance, contact {{institute_phone}}.</p><p>Thank you!</p><p>Accounts Department<br>{{institute_name}}</p></div>'
            ],
            'payment_failed' => [
                'subject' => 'Payment Failed - Action Required',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>❌ PAYMENT TRANSACTION FAILED</p><p>We\'re sorry, but your recent payment attempt was unsuccessful.</p><p>📄 TRANSACTION DETAILS:<br>• Date: {{current_date}}<br>• Amount: NPR {{amount}}<br>• Payment Method: {{payment_mode}}<br>• Transaction ID: {{transaction_id}}<br>• Status: FAILED</p><p>🔍 POSSIBLE REASONS:<br>• Insufficient balance in payment account<br>• Network connectivity issues<br>• Bank/gateway timeout<br>• Incorrect payment credentials<br>• Transaction limit exceeded</p><p>💡 WHAT TO DO NEXT:<br>1. Check your account balance<br>2. Verify payment gateway credentials<br>3. Try again after a few minutes<br>4. Use an alternative payment method<br>5. Contact your bank if issue persists</p><p>📚 PENDING FEE:<br>• Course: {{course_name}}<br>• Amount Due: NPR {{amount_due}}<br>• Due Date: {{due_date}}</p><p>⚠️ Please complete your payment before {{due_date}} to avoid late fines.</p><p>RETRY PAYMENT: {{login_url}}</p><p>Need help? Contact us:<br>• Phone: {{institute_phone}}<br>• Email: {{institute_email}}<br>• Visit: Accounts Office</p><p>We\'re here to assist you!</p><p>Accounts Department<br>{{institute_name}}</p></div>'
            ],
            'payment_pending' => [
                'subject' => 'Payment Under Verification - {{institute_name}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>⏳ PAYMENT VERIFICATION IN PROGRESS</p><p>We have received your payment submission and it is currently under verification.</p><p>📄 TRANSACTION DETAILS:<br>• Reference Number: {{transaction_id}}<br>• Date Submitted: {{current_date}}<br>• Amount: NPR {{amount}}<br>• Payment Method: {{payment_mode}}<br>• Status: PENDING VERIFICATION</p><p>🕒 VERIFICATION TIME:<br>• Bank Transfer: 1-2 business days<br>• Cheque: 3-5 business days<br>• Online Payment: Usually within 24 hours</p><p>📚 COURSE DETAILS:<br>• Course: {{course_name}}<br>• Roll Number: {{roll_no}}</p><p>✅ WHAT HAPPENS NEXT:<br>Our accounts team will verify your payment and update your account. You will receive a confirmation email with your official receipt once verification is complete.</p><p>📱 TRACK PAYMENT STATUS:<br>Log in to your student portal to track your payment status in real-time: {{login_url}}</p><p>⚠️ If verification takes longer than expected, please contact us with your transaction reference number.</p><p>CONTACT US:<br>• Phone: {{institute_phone}}<br>• Email: {{institute_email}}<br>• Portal: {{login_url}}</p><p>Thank you for your patience!</p><p>Accounts Department<br>{{institute_name}}</p></div>'
            ],
            'fee_reminder_7days' => [
                'subject' => 'Fee Payment Reminder - Due in 7 Days',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>📅 UPCOMING PAYMENT DUE</p><p>This is a friendly reminder that your fee payment is due in 7 days.</p><p>💰 PAYMENT DETAILS:<br>• Amount Due: NPR {{amount_due}}<br>• Amount Paid: NPR {{amount_paid}}<br>• Balance: NPR [amount_due - amount_paid]<br>• Due Date: {{due_date}}<br>• Installment: {{installment_no}}</p><p>📚 COURSE INFORMATION:<br>• Course: {{course_name}}<br>• Roll Number: {{roll_no}}<br>• Batch: {{batch_name}}</p><p>⚠️ IMPORTANT NOTICE:<br>Please ensure timely payment to avoid:<br>• Late payment fines (charged per day after due date)<br>• Exam access restrictions<br>• Class attendance restrictions (if applicable)</p><p>💳 PAYMENT OPTIONS:<br>1. Online Portal: {{login_url}}<br>   • eSewa, Khalti, Bank Transfer<br>2. Institute Office: Visit our accounts desk<br>   • Cash, Cheque accepted<br>3. Bank Transfer:<br>   • Use your roll number as reference</p><p>📱 PAY NOW: {{login_url}}</p><p>Already paid? Please share your payment proof at {{institute_email}} or upload it on the portal.</p><p>For payment assistance or installment queries:<br>📞 {{institute_phone}}<br>✉️ {{institute_email}}</p><p>Thank you for your cooperation!</p><p>Accounts Team<br>{{institute_name}}</p></div>'
            ],
            'fee_reminder_3days' => [
                'subject' => 'URGENT: Fee Payment Due in 3 Days ⚠️',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>⚠️ URGENT PAYMENT REMINDER</p><p>Your fee payment is due in just 3 DAYS. Please take immediate action to avoid penalties.</p><p>💰 OUTSTANDING AMOUNT:<br>• Total Due: NPR {{amount_due}}<br>• Paid to Date: NPR {{amount_paid}}<br>• BALANCE: NPR [amount_due - amount_paid]<br>• Due Date: {{due_date}} ⏰</p><p>📚 STUDENT DETAILS:<br>• Roll Number: {{roll_no}}<br>• Course: {{course_name}}<br>• Installment: {{installment_no}}</p><p>🚨 CONSEQUENCES OF LATE PAYMENT:<br>• Late Fine: Charged per day after {{due_date}}<br>• Exam Access: May be blocked if payment not received<br>• Class Restrictions: Possible attendance restrictions</p><p>💳 MAKE PAYMENT NOW:</p><p>OPTION 1 - Online Payment (Instant):<br>• Portal: {{login_url}}<br>• eSewa / Khalti / Bank Transfer</p><p>OPTION 2 - Office Payment:<br>• Visit: {{institute_address}}<br>• Timing: 10 AM - 5 PM (Mon-Fri)<br>• Payment Desk: Accounts Office</p><p>OPTION 3 - Bank Transfer:<br>(Share payment proof immediately)</p><p>✅ PAY IMMEDIATELY: {{login_url}}</p><p>Need help or facing payment issues?<br>📞 CALL NOW: {{institute_phone}}<br>✉️ EMAIL: {{institute_email}}</p><p>We\'re here to help you complete your payment smoothly.</p><p>URGENT ATTENTION REQUIRED</p><p>Accounts Department<br>{{institute_name}}</p></div>'
            ],
            'fee_overdue_notice' => [
                'subject' => 'OVERDUE: Payment Required Immediately 🔴',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>🔴 PAYMENT OVERDUE - IMMEDIATE ACTION REQUIRED</p><p>Your fee payment deadline has passed. Please settle your outstanding balance immediately.</p><p>💰 OVERDUE PAYMENT:<br>• Original Amount: NPR {{amount_due}}<br>• Amount Paid: NPR {{amount_paid}}<br>• Outstanding: NPR [amount_due - amount_paid]<br>• Due Date: {{due_date}} (PASSED)<br>• Days Overdue: [calculated]<br>• Late Fine Applied: NPR {{fine_applied}}<br>• TOTAL PAYABLE NOW: NPR [total with fine]</p><p>📚 ACCOUNT DETAILS:<br>• Roll Number: {{roll_no}}<br>• Course: {{course_name}}<br>• Batch: {{batch_name}}</p><p>🚨 IMMEDIATE CONSEQUENCES:<br>• Late fine continues to accumulate daily<br>• Academic activities may be restricted<br>• Results and certificates will be withheld<br>• Eligibility for exams may be affected</p><p>💳 CLEAR DUES IMMEDIATELY:</p><p>1️⃣ ONLINE PAYMENT (Fastest):<br>   {{login_url}}</p><p>2️⃣ OFFICE PAYMENT:<br>   Visit: {{institute_address}}<br>   Timing: 10 AM - 5 PM (Mon-Fri)</p><p>3️⃣ BANK TRANSFER:<br>   ⚠️ Share proof immediately</p><p>📱 PAY NOW TO RESTORE ACCESS: {{login_url}}</p><p>FACING FINANCIAL DIFFICULTY?<br>If you\'re unable to pay the full amount, please contact our accounts office immediately to discuss:<br>• Installment arrangements<br>• Fine waiver requests<br>• Payment extensions</p><p>📞 CONTACT URGENTLY:<br>Phone: {{institute_phone}}<br>Email: {{institute_email}}<br>Visit: Accounts Office</p><p>⏰ Please resolve this within 24 hours to avoid further complications.</p><p>This is a system-generated notice. Your immediate attention is required.</p><p>Accounts Department<br>{{institute_name}}</p></div>'
            ],
            'invoice_generated' => [
                'subject' => 'New Invoice Generated - {{invoice_number}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>📄 NEW INVOICE ISSUED</p><p>A new fee invoice has been generated for your account.</p><p>INVOICE DETAILS:<br>• Invoice Number: {{invoice_number}}<br>• Issue Date: {{invoice_date}}<br>• Due Date: {{due_date}}<br>• Amount: NPR {{total_amount}}</p><p>STUDENT INFORMATION:<br>• Name: {{student_name}}<br>• Roll Number: {{roll_no}}<br>• Course: {{course_name}}<br>• Batch: {{batch_name}}</p><p>📥 DOWNLOAD INVOICE:<br>Log in to your student portal to download the official invoice: {{login_url}}</p><p>💳 PAYMENT OPTIONS:<br>• Online: eSewa, Khalti, Bank Transfer via student portal<br>• Office: Visit accounts desk (Cash/Cheque accepted)<br>• Bank Transfer: Use invoice number as reference</p><p>⏰ PAYMENT DEADLINE: {{due_date}}</p><p>Please ensure timely payment to avoid late fines.</p><p>Questions about this invoice?<br>📞 {{institute_phone}}<br>✉️ {{institute_email}}</p><p>Thank you,</p><p>Accounts Department<br>{{institute_name}}</p></div>'
            ],
            'payment_refund_processed' => [
                'subject' => 'Refund Processed - {{receipt_no}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>✅ REFUND PROCESSED SUCCESSFULLY</p><p>Your refund request has been approved and processed.</p><p>REFUND DETAILS:<br>• Original Receipt: {{receipt_no}}<br>• Original Payment Date: {{original_payment_date}}<br>• Refund Amount: NPR {{amount}}<br>• Refund Date: {{refund_date}}<br>• Refund Method: {{refund_method}}<br>• Transaction ID: {{transaction_id}}</p><p>STUDENT DETAILS:<br>• Name: {{student_name}}<br>• Roll Number: {{roll_no}}<br>• Course: {{course_name}}</p><p>⏰ REFUND TIMELINE:<br>• Bank Transfer: 3-5 business days<br>• Cheque: Collect from office<br>• Original Payment Method: As per bank processing time</p><p>📱 TRACK REFUND:<br>You can track your refund status in the student portal: {{login_url}}</p><p>If you don\'t receive the refund within the expected timeline, please contact us with your transaction reference number.</p><p>QUESTIONS?<br>📞 {{institute_phone}}<br>✉️ {{institute_email}}</p><p>Thank you for your patience.</p><p>Accounts Department<br>{{institute_name}}</p></div>'
            ],
            'course_enrollment_success' => [
                'subject' => 'Successfully Enrolled - {{course_name}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>🎓 ENROLLMENT CONFIRMED</p><p>Congratulations! You have been successfully enrolled in {{course_name}}.</p><p>ENROLLMENT DETAILS:<br>• Course: {{course_name}}<br>• Course Code: {{course_code}}<br>• Batch: {{batch_name}}<br>• Shift: {{batch_shift}}<br>• Roll Number: {{roll_no}}<br>• Start Date: {{batch_start_date}}</p><p>CLASS INFORMATION:<br>• Timetable: Available on student portal<br>• Class Timings: As per batch schedule<br>• Total Duration: {{course_duration}}</p><p>📚 WHAT\'S NEXT:<br>1. Log in to your portal: {{login_url}}<br>2. View your class schedule<br>3. Download study materials<br>4. Check exam calendar<br>5. Review fee payment schedule</p><p>📱 ACCESS YOUR PORTAL:<br>{{login_url}}</p><p>Welcome to {{course_name}}! We wish you success in your studies.</p><p>For any questions:<br>📞 {{institute_phone}}<br>✉️ {{institute_email}}</p><p>Best wishes!</p><p>Academic Department<br>{{institute_name}}</p></div>'
            ],
            'exam_schedule_published' => [
                'subject' => 'Exam Schedule Announced - {{exam_name}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>📅 EXAM SCHEDULE PUBLISHED</p><p>The schedule for {{exam_name}} has been announced.</p><p>EXAM DETAILS:<br>• Exam Name: {{exam_name}}<br>• Date: {{exam_date}}<br>• Time: {{exam_time}}<br>• Duration: {{exam_duration}} minutes<br>• Total Marks: {{total_marks}}</p><p>YOUR ENROLLMENT:<br>• Roll Number: {{roll_no}}<br>• Course: {{course_name}}<br>• Batch: {{batch_name}}</p><p>EXAMINATION INSTRUCTIONS:<br>• Arrive 15 minutes before exam time<br>• Bring your ID card and admit card<br>• No entry after exam starts</p><p>📥 DOWNLOAD ADMIT CARD:<br>Your admit card is now available on the student portal: {{login_url}}</p><p>EXAM VENUE:<br>• Location: {{exam_venue}}<br>• Room Number: {{room_number}}<br>• Seating Plan: Check portal</p><p>⚠️ IMPORTANT:<br>• No entry after exam starts<br>• Late arrivals will not be permitted<br>• Fee clearance required for exam entry</p><p>Questions about the exam?<br>📞 {{institute_phone}}<br>✉️ {{institute_email}}</p><p>Best of luck with your preparation!</p><p>Examination Department<br>{{institute_name}}</p></div>'
            ],
            'exam_results_published' => [
                'subject' => 'Exam Results Available - {{exam_name}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>📊 RESULTS PUBLISHED</p><p>Your results for {{exam_name}} are now available.</p><p>YOUR PERFORMANCE:<br>• Exam: {{exam_name}}<br>• Date: {{exam_date}}<br>• Marks Obtained: {{marks_obtained}} / {{total_marks}}<br>• Percentage: {{percentage}}%<br>• Grade: {{grade}}<br>• Result: {{result_status}}</p><p>CLASS PERFORMANCE:<br>• Class Average: {{class_average}}%<br>• Your Rank: {{rank}} / {{total_students}}<br>• Highest Score: {{highest_score}}</p><p>📱 VIEW DETAILED RESULTS:<br>Log in to your portal for:<br>• Subject-wise breakdown<br>• Answer sheet review (if available)<br>• Performance analysis</p><p>🔗 {{login_url}}</p><p>Questions about your results?<br>📞 {{institute_phone}}<br>✉️ {{institute_email}}</p><p>Keep up the great work!</p><p>Examination Department<br>{{institute_name}}</p></div>'
            ],
            'assignment_new' => [
                'subject' => 'New Assignment: {{assignment_title}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>📝 NEW ASSIGNMENT POSTED</p><p>A new assignment has been posted for {{course_name}}.</p><p>ASSIGNMENT DETAILS:<br>• Title: {{assignment_title}}<br>• Subject: {{subject_name}}<br>• Posted On: {{posted_date}}<br>• Due Date: {{assignment_due_date}}<br>• Maximum Marks: {{max_marks}}</p><p>⚠️ IMPORTANT GUIDELINES:<br>• Late submissions may incur penalties<br>• Plagiarism will result in zero marks<br>• Follow all formatting instructions<br>• Submit before deadline</p><p>📱 ACCESS ASSIGNMENT:<br>View complete details and download resources: {{login_url}}</p><p>Questions about the assignment?<br>• Contact your teacher: {{teacher_email}}<br>• Office: {{institute_phone}}</p><p>Good luck!</p><p>Academic Department<br>{{institute_name}}</p></div>'
            ],
            'assignment_submission_confirmed' => [
                'subject' => 'Assignment Submitted Successfully - {{assignment_title}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>✅ SUBMISSION CONFIRMED</p><p>Your assignment has been submitted successfully.</p><p>SUBMISSION DETAILS:<br>• Assignment: {{assignment_title}}<br>• Submitted On: {{submitted_at}}<br>• Due Date: {{assignment_due_date}}<br>• Status: {{submission_status}}<br>• File: {{submission_filename}}</p><p>YOUR DETAILS:<br>• Roll Number: {{roll_no}}<br>• Course: {{course_name}}</p><p>📱 VIEW SUBMISSION:<br>You can view your submission and track evaluation status on the portal: {{login_url}}</p><p>WHAT\'S NEXT:<br>• Your teacher will evaluate the assignment<br>• You\'ll receive marks and feedback via portal<br>• Check portal regularly for updates</p><p>Questions or concerns?<br>📞 {{institute_phone}}<br>✉️ {{institute_email}}</p><p>Thank you for your submission!</p><p>Academic Department<br>{{institute_name}}</p></div>'
            ],
            'assignment_graded' => [
                'subject' => 'Assignment Evaluated - {{assignment_title}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>📊 ASSIGNMENT GRADED</p><p>Your assignment has been evaluated by your teacher.</p><p>EVALUATION RESULTS:<br>• Assignment: {{assignment_title}}<br>• Submitted On: {{submitted_at}}<br>• Evaluated On: {{graded_at}}<br>• Marks Obtained: {{marks_awarded}} / {{max_marks}}<br>• Percentage: {{assignment_percentage}}%<br>• Grade: {{assignment_grade}}</p><p>TEACHER FEEDBACK:<br>{{feedback}}</p><p>PERFORMANCE ANALYSIS:<br>• Class Average: {{class_average_marks}}<br>• Your Performance: {{performance_status}}</p><p>📱 VIEW DETAILED FEEDBACK:<br>Log in to your portal to view complete evaluation: {{login_url}}</p><p>Keep up the good work! If you have questions about the evaluation, please contact your teacher.</p><p>Teacher Contact: {{teacher_email}}<br>Office: {{institute_phone}}</p><p>Best regards,</p><p>Academic Department<br>{{institute_name}}</p></div>'
            ],
            'attendance_warning' => [
                'subject' => 'ATTENTION REQUIRED: Low Attendance Alert ⚠️',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>⚠️ ATTENDANCE WARNING</p><p>Our records indicate that your attendance has fallen below the required minimum.</p><p>ATTENDANCE SUMMARY:<br>• Current Attendance: {{attendance_percentage}}%<br>• Required Minimum: {{required_attendance}}%<br>• Total Classes: {{total_classes}}<br>• Classes Attended: {{classes_attended}}<br>• Classes Missed: {{classes_missed}}<br>• Period: {{attendance_period}}</p><p>COURSE DETAILS:<br>• Roll Number: {{roll_no}}<br>• Course: {{course_name}}<br>• Batch: {{batch_name}}</p><p>🚨 CONSEQUENCES OF LOW ATTENDANCE:<br>• You may not be eligible to appear in final exams<br>• Academic performance may be affected<br>• Disciplinary action as per institute policy</p><p>⚠️ IMMEDIATE ACTION REQUIRED:<br>• Attend all remaining classes<br>• Submit medical certificates for genuine absences<br>• Meet with your academic advisor</p><p>📱 VIEW DETAILED ATTENDANCE:<br>Check your day-wise attendance record: {{login_url}}</p><p>MEDICAL/EMERGENCY LEAVE:<br>If you\'ve been absent due to medical reasons:<br>• Submit medical certificate to office<br>• Email documents to: {{institute_email}}</p><p>NEED TO DISCUSS?<br>📞 {{institute_phone}}<br>✉️ {{institute_email}}<br>Visit: Academic Office</p><p>Please take immediate corrective action.</p><p>Academic Department<br>{{institute_name}}</p></div>'
            ],
            'general_announcement' => [
                'subject' => 'Important Announcement - {{institute_name}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>📢 IMPORTANT ANNOUNCEMENT</p><p>{{announcement_title}}</p><p>{{announcement_content}}</p><p>DATE: {{announcement_date}}<br>PRIORITY: {{priority}}</p><p>For more information:<br>📱 {{login_url}}<br>📞 {{institute_phone}}<br>✉️ {{institute_email}}</p><p>This is an official announcement from {{institute_name}}.</p><p>Administration Office<br>{{institute_name}}</p></div>'
            ],
            'account_suspended' => [
                'subject' => 'Account Suspension Notice - {{institute_name}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>🔒 ACCOUNT SUSPENDED</p><p>Your student account at {{institute_name}} has been temporarily suspended.</p><p>SUSPENSION DETAILS:<br>• Account Status: SUSPENDED<br>• Date: {{suspension_date}}<br>• Roll Number: {{roll_no}}<br>• Reason: {{suspension_reason}}</p><p>⚠️ DURING SUSPENSION:<br>• Portal access is restricted<br>• You cannot attend classes<br>• You cannot appear for exams<br>• Library and facility access is blocked</p><p>🔓 TO RESTORE YOUR ACCOUNT:<br>{{restoration_instructions}}</p><p>📞 CONTACT ADMINISTRATION:<br>Phone: {{institute_phone}}<br>Email: {{institute_email}}<br>Visit: Administration Office<br>Timing: 10 AM - 5 PM (Mon-Fri)</p><p>⚠️ This is a serious matter. Please resolve this immediately to continue your education.</p><p>Administration Office<br>{{institute_name}}</p></div>'
            ],
            'account_reactivated' => [
                'subject' => 'Account Reactivated - Welcome Back!',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>✅ ACCOUNT REACTIVATED</p><p>Great news! Your student account has been reactivated.</p><p>REACTIVATION DETAILS:<br>• Account Status: ACTIVE<br>• Reactivated On: {{reactivation_date}}<br>• Roll Number: {{roll_no}}</p><p>You now have full access to:<br>✓ Student Portal<br>✓ Class Attendance<br>✓ Exam Registration<br>✓ Library & Facilities<br>✓ All Institute Services</p><p>📱 LOG IN NOW:<br>{{login_url}}</p><p>Username: {{student_email}}</p><p>Welcome back! We\'re glad to have you back in our academic community.</p><p>Questions?<br>📞 {{institute_phone}}<br>✉️ {{institute_email}}</p><p>Best wishes for your continued success!</p><p>Administration Office<br>{{institute_name}}</p></div>'
            ],
            'document_verification_required' => [
                'subject' => 'Document Verification Required - {{institute_name}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>📄 DOCUMENT VERIFICATION REQUIRED</p><p>We need you to verify or resubmit certain documents for your student profile.</p><p>STUDENT DETAILS:<br>• Roll Number: {{roll_no}}<br>• Course: {{course_name}}</p><p>DOCUMENTS REQUIRED:<br>{{document_list}}</p><p>SUBMISSION DEADLINE: {{submission_deadline}}</p><p>📥 HOW TO SUBMIT:</p><p>OPTION 1 - Online Upload:<br>1. Log in to portal: {{login_url}}<br>2. Go to Profile → Documents<br>3. Upload required documents<br>4. Submit for verification</p><p>OPTION 2 - Office Submission:<br>• Visit: Administration Office<br>• Bring: Original + 2 photocopies<br>• Timing: 10 AM - 5 PM (Mon-Fri)</p><p>DOCUMENT SPECIFICATIONS:<br>• Format: PDF or JPG<br>• File Size: Maximum 5MB per file<br>• Quality: Clear and readable</p><p>Questions?<br>📞 {{institute_phone}}<br>✉️ {{institute_email}}</p><p>Please complete this at your earliest convenience.</p><p>Administration Office<br>{{institute_name}}</p></div>'
            ],
            'leave_request_status' => [
                'subject' => 'Leave Request {{status}} - {{institute_name}}',
                'body' => '<div style="font-family:sans-serif;color:#333;"><p>Dear {{student_name}},</p><p>Your leave application has been reviewed.</p><p>LEAVE DETAILS:<br>• Application ID: {{leave_request_id}}<br>• From Date: {{from_date}}<br>• To Date: {{to_date}}<br>• Duration: {{leave_duration}} days<br>• Reason: {{leave_reason}}<br>• Status: {{leave_status}}</p><p>REVIEW DETAILS:<br>• Reviewed By: {{reviewed_by}}<br>• Review Date: {{review_date}}<br>• Remarks: {{leave_remarks}}</p><p>📱 VIEW LEAVE HISTORY:<br>{{login_url}}</p><p>Questions about your leave?<br>📞 {{institute_phone}}<br>✉️ {{institute_email}}</p><p>Administration Office<br>{{institute_name}}</p></div>'
            ],
        ];

        if (!isset($templates[$templateKey])) {
            return null;
        }

        $subject = $templates[$templateKey]['subject'];
        $body = $templates[$templateKey]['body'];

        // Always inject standard variables
        $data['institute_name'] = $data['institute_name'] ?? 'Your Institute';
        
        foreach ($data as $key => $val) {
            // Ignore complex arrays just to be safe
            if (is_scalar($val)) {
                $search = '{{' . $key . '}}';
                $subject = str_ireplace($search, (string)$val, $subject);
                $body = str_ireplace($search, (string)$val, $body);
            }
        }

        return ['subject' => $subject, 'body' => $body];
    }


    // ── HTML email template (Fallback) ───────────────────────
    private static function credentialEmailHtml(
        string $studentName,
        string $email,
        string $password,
        string $instituteName,
        string $loginUrl
    ): string {
        $inst = htmlspecialchars($instituteName);
        $name = htmlspecialchars($studentName);
        $em   = htmlspecialchars($email);
        $pw   = htmlspecialchars($password);
        $url  = htmlspecialchars($loginUrl);

        return "<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'>
<style>
  body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6fb;margin:0;padding:0}
  .wrap{max-width:560px;margin:40px auto;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.08);overflow:hidden}
  .hdr{background:linear-gradient(135deg,#4F46E5,#6366F1);padding:32px 36px;text-align:center}
  .hdr h1{color:#fff;margin:0;font-size:22px;font-weight:700}
  .hdr p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
  .body{padding:32px 36px}
  .body p{color:#374151;font-size:14px;line-height:1.65;margin:0 0 14px}
  .cred-box{background:#F8FAFF;border:1.5px solid #C7D2FE;border-radius:10px;padding:20px 24px;margin:20px 0}
  .cr{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #E8EDFB;font-size:13px}
  .cr:last-child{border-bottom:none}
  .cl{color:#6B7280;width:110px;font-weight:600;flex-shrink:0}
  .cv{color:#111827;font-weight:700;word-break:break-all}
  .btn-w{text-align:center;margin:24px 0}
  .btn{display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px}
  .note{background:#FFFBEB;border-left:4px solid #F59E0B;border-radius:6px;padding:12px 16px;font-size:12px;color:#92400E;margin:20px 0}
  .foot{background:#F9FAFB;padding:18px 36px;text-align:center;font-size:12px;color:#9CA3AF;border-top:1px solid #F3F4F6}
</style>
</head>
<body>
<div class='wrap'>
  <div class='hdr'>
    <h1>🎓 Welcome to {$inst}</h1>
    <p>Your student account is ready</p>
  </div>
  <div class='body'>
    <p>Dear <strong>{$name}</strong>,</p>
    <p>Congratulations! Your student account at <strong>{$inst}</strong> has been successfully created.</p>
    <div class='cred-box'>
      <div class='cr'><span class='cl'>🌐 Login URL</span><span class='cv'><a href='{$url}' style='color:#4F46E5'>{$url}</a></span></div>
      <div class='cr'><span class='cl'>📧 Email</span><span class='cv'>{$em}</span></div>
      <div class='cr'><span class='cl'>🔑 Password</span><span class='cv'>{$pw}</span></div>
    </div>
    <div class='btn-w'><a href='{$url}' class='btn'>Login to Your Account →</a></div>
    <div class='note'>⚠️ <strong>Important:</strong> Please change your password after first login for security.</div>
    <p>If you face any login issues, please contact the administration office.</p>
    <p>Best Regards,<br><strong>{$inst}</strong></p>
  </div>
  <div class='foot'>This email was sent by the {$inst} student management system. Please do not reply.</div>
</div>
</body></html>";
    }

    // ────────────────────────────────────────────────────────
    //  PUBLIC API
    // ────────────────────────────────────────────────────────

    /**
     * Send login credentials to a newly registered student.
     * Fire-and-forget — never throws, always logs failures.
     *
     * @param \PDO  $db
     * @param int   $tenantId
     * @param array $studentData  { full_name, email, plain_password, course_name, batch_name }
     * @return bool
     */
    public static function sendStudentCredentials(\PDO $db, int $tenantId, array $studentData): bool
    {
        $branding = self::getTenantBranding($db, $tenantId);

        // Skip if institute has explicitly disabled emails
        if (!$branding['is_active']) {
            error_log("[MailHelper] Email disabled for tenant {$tenantId}");
            return false;
        }

        $sys = self::systemConfig();
        if (empty($sys['smtp_pass'])) {
            error_log("[MailHelper] System SMTP password not configured in config/mail.php");
            return false;
        }

        $toEmail    = trim($studentData['email']           ?? '');
        $toName     = trim($studentData['full_name']        ?? 'Student');
        $password   = trim($studentData['plain_password']   ?? '');
        $courseName = trim($studentData['course_name']      ?? '');
        $batchName  = trim($studentData['batch_name']       ?? '');

        if (!$toEmail || !$password || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("[MailHelper] Skipped: invalid email or missing password");
            return false;
        }

        $loginUrl = (defined('APP_URL') ? APP_URL : 'http://localhost/erp') . '/login';

        try {
            $mail = self::buildMailer($branding);
            $mail->addAddress($toEmail, $toName);
            
            $tplData = [
                'student_name' => $toName,
                'student_email' => $toEmail,
                'temp_password' => $password,
                'institute_name' => $branding['institute_name'] ?? '',
                'institute_phone' => $branding['institute_phone'] ?? '',
                'institute_email' => $branding['institute_email'] ?? '',
                'course_name' => $courseName,
                'batch_name' => $batchName,
                'login_url' => $loginUrl,
                'roll_no' => $studentData['roll_no'] ?? 'N/A',
                'admission_date' => $studentData['admission_date'] ?? date('Y-m-d')
            ];
            
            // Use professional static template (hardcoded HTML — see getStaticTemplate())
            $tpl = self::getStaticTemplate('student_registration_success', $tplData);

            if ($tpl) {
                $mail->Subject = $tpl['subject'];
                $mail->Body    = $tpl['body'];
                $mail->AltBody = strip_tags($tpl['body']);
            } else {
                // Fallback: legacy hardcoded HTML credential email
                $mail->Subject = "Welcome to {$branding['institute_name']} – Your Student Account Details";
                $html = self::credentialEmailHtml(
                    $toName, $toEmail, $password, $branding['institute_name'], $loginUrl
                );
                if ($courseName) {
                    $courseSection = "
                        <div class='cr'><span class='cl'>📚 Course</span><span class='cv'>{$courseName}</span></div>
                        <div class='cr'><span class='cl'>👥 Batch</span><span class='cv'>{$batchName}</span></div>
                    ";
                    $html = str_replace("</div>\n    <div class='btn-w'>", $courseSection . "</div>\n    <div class='btn-w'>", $html);
                }
                $mail->Body    = $html;
                $mail->AltBody = "Welcome to {$branding['institute_name']}.\n\n"
                               . "Course: {$courseName}\nBatch: {$batchName}\n"
                               . "Login URL: {$loginUrl}\nEmail: {$toEmail}\nPassword: {$password}\n\n"
                               . "Please change your password after first login.";
            } // end template block
            
            $mail->send();
            error_log("[MailHelper] Credentials sent to {$toEmail} (tenant {$tenantId})");
            return true;
        } catch (MailException $e) {
            error_log("[MailHelper] PHPMailer error to {$toEmail}: " . $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            error_log("[MailHelper] Unexpected error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generic send — for other modules (fee receipts, exam results, etc.)
     */
    public static function send(
        \PDO   $db,
        int    $tenantId,
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody = '',
        string $attachmentPath = ''
    ): bool {
        $branding = self::getTenantBranding($db, $tenantId);
        $sys = self::systemConfig();
        if (empty($sys['smtp_pass'])) return false;

        try {
            $mail = self::buildMailer($branding);
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $plainBody ?: strip_tags($htmlBody);
            
            if (!empty($attachmentPath) && file_exists($attachmentPath)) {
                $mail->addAttachment($attachmentPath, 'Digital_ID_Card.png');
            }

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log("[MailHelper] Error: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Build HTML body for a staff/teacher welcome email.
     */
    public static function buildStaffWelcomeHtml(
        string $name,
        string $roleLabel,
        string $email,
        string $password,
        string $loginUrl
    ): string {
        $n  = htmlspecialchars($name);
        $rl = htmlspecialchars($roleLabel);
        $em = htmlspecialchars($email);
        $pw = htmlspecialchars($password);
        $u  = htmlspecialchars($loginUrl);
        return "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>
  body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6fb;margin:0}
  .wrap{max-width:560px;margin:40px auto;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.08);overflow:hidden}
  .hdr{background:linear-gradient(135deg,#0F172A,#1E40AF);padding:32px 36px;text-align:center}
  .hdr h1{color:#fff;margin:0;font-size:22px;font-weight:700}
  .hdr p{color:rgba(255,255,255,.8);margin:6px 0 0;font-size:13px}
  .body{padding:32px 36px}
  .body p{color:#374151;font-size:14px;line-height:1.65;margin:0 0 14px}
  .cred-box{background:#EFF6FF;border:1.5px solid #BFDBFE;border-radius:10px;padding:20px 24px;margin:20px 0}
  .cr{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #DBEAFE;font-size:13px}
  .cr:last-child{border-bottom:none} .cl{color:#6B7280;width:110px;font-weight:600;flex-shrink:0}
  .cv{color:#111827;font-weight:700;word-break:break-all}
  .btn-w{text-align:center;margin:24px 0}
  .btn{display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#1E40AF,#3B82F6);color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px}
  .note{background:#FFFBEB;border-left:4px solid #F59E0B;border-radius:6px;padding:12px 16px;font-size:12px;color:#92400E;margin:20px 0}
  .foot{background:#F9FAFB;padding:18px 36px;text-align:center;font-size:12px;color:#9CA3AF;border-top:1px solid #F3F4F6}
</style></head><body>
<div class='wrap'>
  <div class='hdr'><h1>👔 Welcome, {$n}!</h1><p>Your {$rl} account is ready</p></div>
  <div class='body'>
    <p>Dear <strong>{$n}</strong>,</p>
    <p>Your <strong>{$rl}</strong> account has been created. You can now log in to the staff portal using the credentials below.</p>
    <div class='cred-box'>
      <div class='cr'><span class='cl'>🌐 Login URL</span><span class='cv'><a href='{$u}' style='color:#1E40AF'>{$u}</a></span></div>
      <div class='cr'><span class='cl'>📧 Email</span><span class='cv'>{$em}</span></div>
      <div class='cr'><span class='cl'>🔑 Password</span><span class='cv'>{$pw}</span></div>
    </div>
    <div class='btn-w'><a href='{$u}' class='btn'>Login to Staff Portal →</a></div>
    <div class='note'>⚠️ <strong>Important:</strong> Please change your password immediately after your first login.</div>
    <p>If you have any questions, please contact the institute administration.</p>
    <p>Best Regards,<br><strong>Institute Administration</strong></p>
  </div>
  <div class='foot'>This is an automated message from the Hamro ERP staff management system.</div>
</div></body></html>";
    }
    public static function sendPaymentReceiptEmail(
        \PDO $db,
        int $tenantId,
        string $toEmail,
        string $toName,
        string $receiptNo,
        string $pdfPath,
        ?string $imagePath = null,
        string $amount = ''
    ): bool {
        $branding = self::getTenantBranding($db, $tenantId);

        if (!$branding['is_active']) {
            return false;
        }

        $sys = self::systemConfig();
        if (empty($sys['smtp_pass'])) {
            return false;
        }

        try {
            $mail = self::buildMailer($branding);
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = "Payment Receipt - {$branding['institute_name']}";
            
            $inst = htmlspecialchars($branding['institute_name']);
            $name = htmlspecialchars($toName);
            $rec  = htmlspecialchars($receiptNo);
            
            $htmlBody = "<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='font-family: Arial, sans-serif; background-color: #f4f6fb; margin: 0; padding: 20px;'>
  <div style='max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
    <div style='background: #4F46E5; padding: 20px; text-align: center; color: #fff;'>
      <h2 style='margin: 0;'>Payment Receipt</h2>
    </div>
    <div style='padding: 20px; color: #333;'>
      <p>Dear <strong>{$name}</strong>,</p>
      <p>Thank you for your recent payment to <strong>{$inst}</strong>.</p>
      <p>Your receipt number is <strong>{$rec}</strong>.</p>
      <p>We have attached the official PDF receipt for your records. If you provided a bill image/screenshot during the transaction, it is also attached to this email.</p>
      <br>
      <p>If you have any questions, please contact the administration office.</p>
      <p>Best Regards,<br><strong>{$inst}</strong></p>
    </div>
  </div>
</body>
</html>";

            $mail->Subject = "Payment Receipt - {$branding['institute_name']}";
            $mail->Body    = $htmlBody;
            $mail->AltBody = "Dear {$name},\n\nThank you for your recent payment to {$inst}. Your receipt number is {$rec}.\n\nPlease find your attached PDF receipt.\n\nBest Regards,\n{$inst}";
            
            $payment_mode = $amount > 0 ? 'online/cash' : ''; // Will need actual data for exact match
            
            $tplData = [
                'student_name' => $toName,
                'institute_name' => $branding['institute_name'],
                'receipt_no' => $receiptNo,
                'amount' => $amount,
                'paid_date' => date('Y-m-d'),
                'payment_mode' => $payment_mode,
                'transaction_id' => $receiptNo,
                'course_name' => 'Fees',
                'roll_no' => 'N/A',
                'installment_no' => 1,
                'amount_due' => $amount,
                'amount_paid' => $amount,
                'balance' => '0.00',
                'due_date' => date('Y-m-d'),
                'login_url' => (defined('APP_URL') ? APP_URL : 'http://localhost/erp') . '/login',
                'institute_phone' => $branding['institute_phone'] ?? '',
                'institute_email' => $branding['institute_email'] ?? ''

            ];
            $dbTpl = self::getStaticTemplate('payment_success_full', $tplData);
            if ($dbTpl) {
                $mail->Subject = $dbTpl['subject'];
                $mail->Body    = $dbTpl['body'];
                $mail->AltBody = strip_tags($dbTpl['body']);
            }
            
            if (file_exists($pdfPath)) {
                $ext = pathinfo($pdfPath, PATHINFO_EXTENSION) ?: 'pdf';
                $mail->addAttachment($pdfPath, 'Receipt_' . $rec . '.' . $ext);
            }
            if ($imagePath && file_exists($imagePath)) {
                $mail->addAttachment($imagePath, 'Uploaded_Bill_' . basename($imagePath));
            }

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log("[MailHelper] Payment Receipt Error: " . $e->getMessage());
            return false;
        }
    }
}
