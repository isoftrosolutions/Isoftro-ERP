<?php
/**
 * Students API Controller
 * Handles fetching students for the current tenant
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

// Load Composer autoload (PHPMailer etc.)
if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../../../vendor/autoload.php';
}

// Fallback for MailHelper if no autoloader
if (!class_exists('App\Helpers\MailHelper') && file_exists(__DIR__ . '/../../../Helpers/MailHelper.php')) {
    require_once __DIR__ . '/../../../Helpers/MailHelper.php';
}
use App\Helpers\MailHelper;
require_once __DIR__ . '/../../../Services/QueueService.php';
use App\Services\QueueService;


// Ensure user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['userData']['role'] ?? '';
// RBAC check
if (!in_array($role, ['instituteadmin', 'frontdesk', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Method spoofing for multipart/form-data PUT/PATCH
if ($method === 'POST' && isset($_POST['_method'])) {
    $spoofedMethod = strtoupper($_POST['_method']);
    if (in_array($spoofedMethod, ['PUT', 'PATCH', 'DELETE'])) {
        $method = $spoofedMethod;
    }
}

try {
    $db = getDBConnection();

    // Handle CSV Export
    if ($method === 'GET' && isset($_GET['export']) && $_GET['export'] === 'csv') {
        $whereSql = "WHERE s.tenant_id = :tid AND s.deleted_at IS NULL";
        $params = ['tid' => $tenantId];

        if (!empty($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            $whereSql .= " AND (s.full_name LIKE :search OR s.roll_no LIKE :search OR s.email LIKE :search OR s.phone LIKE :search)";
            $params['search'] = $search;
        }
        if (!empty($_GET['status'])) {
            $whereSql .= " AND s.status = :status";
            $params['status'] = $_GET['status'];
        }
        if (!empty($_GET['batch_id'])) {
            $whereSql .= " AND s.batch_id = :batch_id";
            $params['batch_id'] = $_GET['batch_id'];
        }
        if (!empty($_GET['ids'])) {
            $ids = explode(',', $_GET['ids']);
            $placeholders = [];
            foreach ($ids as $i => $idVal) {
                $p = "id" . $i;
                $placeholders[] = ":$p";
                $params[$p] = (int)$idVal;
            }
            $whereSql .= " AND s.id IN (" . implode(',', $placeholders) . ")";
        }

        // Get all students for CSV (no pagination limit)
        $query = "SELECT s.roll_no, s.full_name, s.email, s.phone, s.gender, s.dob_bs, s.dob_ad,
                         s.citizenship_no, s.father_name, s.mother_name, s.guardian_name, s.guardian_relation,
                         s.permanent_address, s.temporary_address,
                         b.name as batch_name, c.name as course_name,
                         s.status, s.registration_status, s.admission_date, s.created_at
                  FROM students s 
                  LEFT JOIN batches b ON s.batch_id = b.id 
                  LEFT JOIN courses c ON b.course_id = c.id
                  $whereSql
                  ORDER BY s.roll_no ASC";

        $stmt = $db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build CSV
        $csv = "Roll No,Full Name,Email,Phone,Gender,DOB (BS),DOB (AD),Citizenship No,Father's Name,Mother's Name,Guardian Name,Guardian Relation,Permanent Address,Temporary Address,Batch,Course,Status,Registration Status,Admission Date,Joined Date\n";

        foreach ($students as $s) {
            $addr = is_array($s['permanent_address']) ? json_encode($s['permanent_address']) : ($s['permanent_address'] ?: '');
            $taddr = is_array($s['temporary_address']) ? json_encode($s['temporary_address']) : ($s['temporary_address'] ?: '');
            $csv .= '"' . ($s['roll_no'] ?? '') . '",' .
                    '"' . str_replace('"', '""', ($s['full_name'] ?? '')) . '",' .
                    '"' . ($s['email'] ?? '') . '",' .
                    '"' . ($s['phone'] ?? '') . '",' .
                    '"' . ($s['gender'] ?? '') . '",' .
                    '"' . ($s['dob_bs'] ?? '') . '",' .
                    '"' . ($s['dob_ad'] ?? '') . '",' .
                    '"' . ($s['citizenship_no'] ?? '') . '",' .
                    '"' . ($s['father_name'] ?? '') . '",' .
                    '"' . ($s['mother_name'] ?? '') . '",' .
                    '"' . ($s['guardian_name'] ?? '') . '",' .
                    '"' . ($s['guardian_relation'] ?? '') . '",' .
                    '"' . str_replace('"', '""', $addr) . '",' .
                    '"' . str_replace('"', '""', $taddr) . '",' .
                    '"' . ($s['batch_name'] ?? '') . '",' .
                    '"' . ($s['course_name'] ?? '') . '",' .
                    '"' . ($s['status'] ?? '') . '",' .
                    '"' . ($s['registration_status'] ?? '') . '",' .
                    '"' . ($s['admission_date'] ?? '') . '",' .
                    '"' . ($s['created_at'] ?? '') . '"\n';
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }

    // Handle Stats
    if ($method === 'GET' && isset($_GET['stats'])) {
        $stats = [];

        // Total students
        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['tid' => $tenantId]);
        $stats['total'] = (int)$stmt->fetchColumn();

        // Count new students this month
        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND deleted_at IS NULL");
        $stmt->execute(['tid' => $tenantId]);
        $stats['this_month'] = (int)$stmt->fetchColumn();

        // Active students
        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND status = 'active' AND deleted_at IS NULL");
        $stmt->execute(['tid' => $tenantId]);
        $stats['active'] = (int)$stmt->fetchColumn();

        // Inactive students
        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND status = 'inactive' AND deleted_at IS NULL");
        $stmt->execute(['tid' => $tenantId]);
        $stats['inactive'] = (int)$stmt->fetchColumn();

        // Alumni
        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND status = 'alumni' AND deleted_at IS NULL");
        $stmt->execute(['tid' => $tenantId]);
        $stats['alumni'] = (int)$stmt->fetchColumn();

        // Active Batches
        $stmt = $db->prepare("SELECT COUNT(*) FROM batches WHERE tenant_id = :tid AND status = 'active' AND deleted_at IS NULL");
        $stmt->execute(['tid' => $tenantId]);
        $stats['batches'] = (int)$stmt->fetchColumn();

        // Fee Overdue count
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT sfs.student_id) 
            FROM student_fee_summary sfs
            JOIN enrollments e ON sfs.enrollment_id = e.id
            WHERE sfs.tenant_id = :tid 
              AND e.status = 'active'
              AND sfs.fee_status = 'overdue'
        ");
        $stmt->execute(['tid' => $tenantId]);
        $stats['overdue'] = (int)$stmt->fetchColumn();

        echo json_encode(['success' => true, 'stats' => $stats]);
        exit;
    }

    if ($method === 'GET') {
        // Pagination setup
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $perPage;

        // Base query
        $whereSql = "WHERE s.tenant_id = :tid AND s.deleted_at IS NULL";
        $params = ['tid' => $tenantId];

        // Search filter
        if (!empty($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            $whereSql .= " AND (s.full_name LIKE :search OR s.roll_no LIKE :search OR s.email LIKE :search OR s.phone LIKE :search)";
            $params['search'] = $search;
        }

        // Single ID filter
        if (!empty($_GET['id'])) {
            $whereSql .= " AND s.id = :sid";
            $params['sid'] = $_GET['id'];
        }

        // Status filter
        if (!empty($_GET['status'])) {
            $whereSql .= " AND s.status = :status";
            $params['status'] = $_GET['status'];
        }

        if (!empty($_GET['registration_status'])) {
            $whereSql .= " AND s.registration_status = :reg_status";
            $params['reg_status'] = $_GET['registration_status'];
        }

        // Course filter
        if (!empty($_GET['course_id'])) {
            $whereSql .= " AND b.course_id = :course_id";
            $params['course_id'] = $_GET['course_id'];
        }

        // Batch filter
        if (!empty($_GET['batch_id'])) {
            $whereSql .= " AND s.batch_id = :batch_id";
            $params['batch_id'] = $_GET['batch_id'];
        }

        // Count total for pagination
        $countQuery = "SELECT COUNT(*) FROM students s 
                       LEFT JOIN batches b ON s.batch_id = b.id 
                       $whereSql";
        $stmtCount = $db->prepare($countQuery);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Data query
        $query = "SELECT s.*, s.registration_mode, s.registration_status, s.admission_date,
                         b.name as batch_name, b.course_id as course_id, c.name as course_name,
                         COALESCE(sfs.fee_status, 'no_fees') as fee_status,
                         COALESCE(sfs.total_fee, 0) as total_fee,
                         COALESCE(sfs.paid_amount, 0) as paid_amount,
                         COALESCE(sfs.due_amount, 0) as due_amount
                  FROM students s 
                  LEFT JOIN batches b ON s.batch_id = b.id 
                  LEFT JOIN courses c ON b.course_id = c.id
                  LEFT JOIN student_fee_summary sfs ON s.id = sfs.student_id
                  $whereSql
                  ORDER BY s.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate fee status for each student (only if not requesting single detailed view)
        if (empty($_GET['id']) || ($_GET['include'] ?? '') !== 'details') {
            foreach ($students as &$student) {
                $sid = $student['id'];
                
                // Get accurate fee status, total due, and total paid from unified flow table
                $feeStmt = $db->prepare("
                    SELECT 
                        sfs.fee_status,
                        COALESCE(sfs.total_fee, 0) as total_due,
                        COALESCE(sfs.paid_amount, 0) as total_paid,
                        COALESCE(sfs.due_amount, 0) as pending_amount
                    FROM student_fee_summary sfs
                    JOIN enrollments e ON sfs.enrollment_id = e.id
                    WHERE sfs.student_id = :sid AND sfs.tenant_id = :tid AND e.status = 'active'
                    LIMIT 1
                ");
                $feeStmt->execute(['sid' => $sid, 'tid' => $tenantId]);
                $feeData = $feeStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$feeData) {
                    $student['fee_status'] = 'no_fees';
                    $student['fee_status_label'] = 'No Fees';
                    $student['total_due'] = 0;
                    $student['total_paid'] = 0;
                } else {
                    $student['fee_status'] = $feeData['fee_status'];
                    $student['fee_status_label'] = ucfirst(str_replace('_', ' ', $feeData['fee_status']));
                    $student['total_due'] = (float)$feeData['total_due'];
                    $student['total_paid'] = (float)$feeData['total_paid'];
                }
            }
            unset($student); // break reference
        }

        // If specific ID and details requested, fetch related data
        if (!empty($_GET['id']) && ($_GET['include'] ?? '') === 'details' && count($students) > 0) {
            $student = &$students[0];
            $sid = $student['id'];

            // 1. Fee Summary & Payments (Unified Flow)
            $feeSummaryStmt = $db->prepare("
                SELECT * FROM student_fee_summary
                WHERE student_id = :sid AND tenant_id = :tid
            ");
            $feeSummaryStmt->execute(['sid' => $sid, 'tid' => $tenantId]);
            $student['fee_summary'] = $feeSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

            $paymentStmt = $db->prepare("
                SELECT * FROM student_payments
                WHERE student_id = :sid AND tenant_id = :tid
                ORDER BY payment_date DESC, id DESC
            ");
            $paymentStmt->execute(['sid' => $sid, 'tid' => $tenantId]);
            $student['payments'] = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);


            // 2. Exam Results
            $examStmt = $db->prepare("
                SELECT a.*, e.title as exam_title, e.total_marks as max_marks, e.start_at as exam_date
                FROM exam_attempts a
                JOIN exams e ON a.exam_id = e.id
                WHERE a.student_id = :sid AND a.tenant_id = :tid
                ORDER BY e.start_at DESC
            ");
            $examStmt->execute(['sid' => $sid, 'tid' => $tenantId]);
            $student['exams'] = $examStmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Assignments
            $assignStmt = $db->prepare("
                SELECT s.*, a.title as assignment_title, a.max_marks, a.due_date
                FROM assignment_submissions s
                JOIN assignments a ON s.assignment_id = a.id
                WHERE s.student_id = :sid AND s.tenant_id = :tid
                ORDER BY s.submitted_at DESC
            ");
            $assignStmt->execute(['sid' => $sid, 'tid' => $tenantId]);
            $student['assignments'] = $assignStmt->fetchAll(PDO::FETCH_ASSOC);

            // 4. Attendance Summary (Last 30 days)
            $attStmt = $db->prepare("
                SELECT status, COUNT(*) as count
                FROM attendance
                WHERE student_id = :sid AND tenant_id = :tid AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY status
            ");
            $attStmt->execute(['sid' => $sid, 'tid' => $tenantId]);
            $student['attendance_summary'] = $attStmt->fetchAll(PDO::FETCH_ASSOC);

            // 5. Historical Enrollments (Proper Table Data)
            $enrStmt = $db->prepare("
                SELECT e.*, b.name as batch_name, c.name as course_name, c.code as course_code
                FROM enrollments e
                JOIN batches b ON e.batch_id = b.id
                JOIN courses c ON b.course_id = c.id
                WHERE e.student_id = :sid AND e.tenant_id = :tid
                ORDER BY e.enrollment_date DESC
            ");
            $enrStmt->execute(['sid' => $sid, 'tid' => $tenantId]);
            $student['enrollments'] = $enrStmt->fetchAll(PDO::FETCH_ASSOC);

            // 6. Course Subjects & Teachers (for current batch)
            if ($student['batch_id']) {
                $subStmt = $db->prepare("
                    SELECT s.name as subject, t.full_name as teacher_name, t.phone as teacher_contact
                    FROM batch_subject_allocations bsa
                    JOIN teachers t ON bsa.teacher_id = t.id
                    JOIN subjects s ON bsa.subject_id = s.id
                    WHERE bsa.batch_id = :bid AND bsa.tenant_id = :tid
                ");
                $subStmt->execute(['bid' => $student['batch_id'], 'tid' => $tenantId]);
                $student['batch_subjects'] = $subStmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $student['batch_subjects'] = [];
            }
        }

        echo json_encode([
            'success' => true, 
            'data' => (!empty($_GET['id']) && count($students) === 1) ? $students[0] : $students,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
    } 
    
    else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $action = $input['action'] ?? '';

        // --- Action: Send Email ---
        if ($action === 'send_email') {
            $studentId = $input['student_id'] ?? null;
            $subject = $input['subject'] ?? '';
            $message = $input['message'] ?? '';
            $sendCredentials = !empty($input['send_credentials']);

            if (!$studentId) throw new Exception("Student ID is required");

            // Fetch student email and name
            $stmt = $db->prepare("SELECT full_name, email, registration_mode FROM students WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL");
            $stmt->execute(['id' => $studentId, 'tid' => $tenantId]);
            $student = $stmt->fetch();

            if (!$student) throw new Exception("Student not found");
            if (empty($student['email'])) throw new Exception("Student does not have a registered email address");

            $success = false;

            if ($sendCredentials) {
                // Use default password if not provided
                $password = $input['password'] ?? 'Student@123'; 
                $success = \App\Helpers\StudentEmailHelper::sendWelcomeEmail($db, $tenantId, [
                    'full_name' => $student['full_name'],
                    'email' => $student['email'],
                    'plain_password' => $password
                ]);
            } else {
                if (empty($subject) || empty($message)) throw new Exception("Subject and message are required");
                // For custom admin emails, we can use AdminEmailHelper to dispatch a general announcement or custom message
                $success = \App\Helpers\AdminEmailHelper::sendAnnouncement($db, $tenantId, [
                    'email' => $student['email'],
                    'name' => $student['full_name'],
                    'subject' => $subject,
                    'body' => $message // sendAnnouncement will use 'general_announcement' template
                ]);
            }


            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
            } else {
                throw new Exception("Failed to send email. Please check SMTP settings.");
            }
            exit;
        }

        // --- Action: Bulk Send Email ---
        if ($action === 'bulk_send_email') {
            $studentIds = $input['student_ids'] ?? [];
            $subject = $input['subject'] ?? '';
            $message = $input['message'] ?? '';

            if (empty($studentIds)) throw new Exception("No students selected");
            if (empty($subject) || empty($message)) throw new Exception("Subject and message are required");

            // Fetch all students with email addresses
            $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
            $stmt = $db->prepare("SELECT id, full_name, email FROM students WHERE id IN ($placeholders) AND tenant_id = ? AND email IS NOT NULL AND email != '' AND deleted_at IS NULL");
            $stmt->execute(array_merge($studentIds, [$tenantId]));
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($students)) throw new Exception("No students with valid email addresses found");

            $sentCount = 0;
            $failedCount = 0;
            $failedEmails = [];

            foreach ($students as $student) {
                $success = \App\Helpers\AdminEmailHelper::sendAnnouncement($db, $tenantId, [
                    'email' => $student['email'],
                    'name' => $student['full_name'],
                    'subject' => $subject,
                    'body' => $message
                ]);
                if ($success) $sentCount++;
                else $failedCount++;
            }


            echo json_encode([
                'success' => true, 
                'message' => "Email sent to $sentCount students" . ($failedCount > 0 ? ", $failedCount failed" : ""),
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'failed_emails' => $failedEmails
            ]);
            exit;
        }

        // --- Action: Bulk Drop ---
        if ($action === 'bulk_drop') {
            $studentIds = $input['student_ids'] ?? [];
            if (empty($studentIds)) throw new Exception("No students selected");
            
            $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
            $sql = "UPDATE students SET status = 'dropped' WHERE id IN ($placeholders) AND tenant_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge($studentIds, [$tenantId]));
            
            echo json_encode(['success' => true, 'message' => 'Status updated to dropped for selected students']);
            exit;
        }

        // --- Action: Record Payment ---
        if ($action === 'record_payment') {
            $studentId = $input['student_id'] ?? null;
            $amount = $input['amount'] ?? 0;
            $paymentMode = $input['payment_mode'] ?? 'cash';
            $reference = $input['reference'] ?? null;
            $paymentDate = $input['payment_date'] ?? date('Y-m-d');
            $userId = $_SESSION['user_id'] ?? null;

            if (!$studentId || $amount <= 0) {
                throw new Exception("Student ID and valid amount are required.");
            }

            try {
                $db->beginTransaction();

                // 1. Get active enrollment and fee summary
                $stmtAuth = $db->prepare("
                    SELECT sfs.id as fee_summary_id, sfs.enrollment_id, sfs.total_fee, sfs.paid_amount, sfs.due_amount
                    FROM student_fee_summary sfs
                    JOIN enrollments e ON sfs.enrollment_id = e.id
                    WHERE sfs.student_id = :sid AND sfs.tenant_id = :tid AND e.status = 'active'
                ");
                $stmtAuth->execute(['sid' => $studentId, 'tid' => $tenantId]);
                $summary = $stmtAuth->fetch(PDO::FETCH_ASSOC);

                if (!$summary) {
                    throw new Exception("No active fee summary found for this student.");
                }

                if ($amount > $summary['due_amount']) {
                    throw new Exception("Payment amount exceeds due amount. Maximum allowed payment is " . $summary['due_amount']);
                }

                // 2. Insert into student_payments
                $stmtPay = $db->prepare("
                    INSERT INTO student_payments (tenant_id, student_id, enrollment_id, amount, payment_mode, reference, payment_date, collected_by)
                    VALUES (:tid, :sid, :eid, :amt, :mode, :ref, :pdate, :cby)
                ");
                $stmtPay->execute([
                    'tid' => $tenantId,
                    'sid' => $studentId,
                    'eid' => $summary['enrollment_id'],
                    'amt' => $amount,
                    'mode' => $paymentMode,
                    'ref' => $reference,
                    'pdate' => $paymentDate,
                    'cby' => $userId
                ]);

                // 3. Update student_fee_summary
                $newPaidAmount = $summary['paid_amount'] + $amount;
                $newDueAmount = $summary['total_fee'] - $newPaidAmount;
                
                $feeStatus = 'unpaid';
                if ($newDueAmount <= 0) {
                    $feeStatus = 'paid';
                } elseif ($newPaidAmount > 0) {
                    $feeStatus = 'partial';
                }

                $stmtUpdate = $db->prepare("
                    UPDATE student_fee_summary
                    SET paid_amount = :paid, due_amount = :due, fee_status = :status
                    WHERE id = :id AND tenant_id = :tid
                ");
                $stmtUpdate->execute([
                    'paid' => $newPaidAmount,
                    'due' => $newDueAmount,
                    'status' => $feeStatus,
                    'id' => $summary['fee_summary_id'],
                    'tid' => $tenantId
                ]);

                // 4. Generate Receipt Number and log to payment_transactions for Dashboard visibility
                $receiptNo = 'RCPT-' . strtoupper(uniqid());
                // We'll set fee_record_id to NULL or 0 since this is a general payment not tied to a specific fee item
                $stmtTransPay = $db->prepare("
                    INSERT INTO payment_transactions (tenant_id, student_id, fee_record_id, amount, payment_method, payment_date, receipt_number, notes)
                    VALUES (:tid, :sid, 0, :amt, :mode, :pdate, :rno, 'Direct Payment')
                ");
                $stmtTransPay->execute([
                    'tid' => $tenantId,
                    'sid' => $studentId,
                    'amt' => $amount,
                    'mode' => $paymentMode,
                    'pdate' => $paymentDate,
                    'rno' => $receiptNo
                ]);

                // 5. Queue Receipt Tasks (PDF & Email)
                $queue = new QueueService($db);
                $queue->dispatch('payment_receipt', [
                    'tenant_id' => $tenantId,
                    'student_id' => $studentId,
                    'receipt_no' => $receiptNo,
                    'transaction_ids' => [0] // Use 0 for Direct Payment
                ]);

                $emailStatus = 'no_email';
                try {
                    $stmtGetEmail = $db->prepare("
                        SELECT s.full_name, COALESCE(NULLIF(s.email, ''), u.email) as email 
                        FROM students s 
                        LEFT JOIN users u ON s.user_id = u.id 
                        WHERE s.id = :sid AND s.tenant_id = :tid
                    ");
                    $stmtGetEmail->execute(['sid' => $studentId, 'tid' => $tenantId]);
                    $stdEmailInfo = $stmtGetEmail->fetch(PDO::FETCH_ASSOC);

                    if ($stdEmailInfo && !empty($stdEmailInfo['email'])) {
                        $queue->dispatch('send_email_receipt', [
                            'tenant_id' => $tenantId,
                            'student_id' => $studentId,
                            'recipient_email' => $stdEmailInfo['email'],
                            'recipient_name' => $stdEmailInfo['full_name'],
                            'receipt_no' => $receiptNo
                        ]);
                        $emailStatus = 'queued';
                    }
                } catch (Exception $em) {
                    error_log("Failed to queue direct payment email: " . $em->getMessage());
                }

                $db->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Payment recorded successfully.',
                    'data' => [
                        'receipt_no' => $receiptNo,
                        'amount_paid' => $amount,
                        'email_status' => $emailStatus,
                        'student_id' => $studentId,
                        'student_name' => $stdEmailInfo['full_name'] ?? 'Student',
                        'redirect_url' => '?page=fee-details&receipt_no=' . $receiptNo
                    ]
                ]);
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }
            exit;
        }

        $fullName = $input['full_name'] ?? '';

        $db->beginTransaction();

        // Resolve registration mode — unified SOP
        $regMode   = $input['registration_mode']   ?? 'full';
        $regStatus = $input['registration_status']  ?? 'fully_registered';

        // Accept contact_number (Front Desk / Admin unified form) or phone
        $phone   = $input['contact_number'] ?? $input['phone'] ?? null;
        $batchId = $input['batch_id'] ?? null;
        $rollNo  = $input['roll_no'] ?? null;

        // 1. Handle File Uploads (Optional)
        $photoUrl = $input['photo_url'] ?? null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = APP_ROOT . '/public/uploads/students/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'std_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $fileName)) {
                $photoUrl = APP_URL . '/public/uploads/students/' . $fileName;
            }
        }

        $identityDocUrl = null;
        if (isset($_FILES['identity_doc']) && $_FILES['identity_doc']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = APP_ROOT . '/public/uploads/students/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['identity_doc']['name'], PATHINFO_EXTENSION);
            $fileName = 'id_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['identity_doc']['tmp_name'], $uploadDir . $fileName)) {
                $identityDocUrl = APP_URL . '/public/uploads/students/' . $fileName;
            }
        }

        // 2. Prepare Data for Service
        $registrationData = $input;
        $registrationData['photo_url'] = $photoUrl;
        $registrationData['identity_doc_url'] = $identityDocUrl;
        $registrationData['phone'] = $phone;

        // 3. Register via Service (Handles User, Student, Enrollment, Fees + Transactions + Email)
        $service = new \App\Services\StudentService();
        $result = $service->registerStudent($registrationData, $tenantId);

        echo json_encode([
            'success' => true, 
            'message' => 'Student registered successfully', 
            'id' => $result['student']['id'], 
            'enrollment_id' => $result['enrollment_id']
        ]);
        exit;
    }

    else if ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Student ID is required");

        // Verify ownership
        $stmt = $db->prepare("SELECT id, user_id FROM students WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        $student = $stmt->fetch();
        if (!$student) throw new Exception("Student not found");

        $db->beginTransaction();

        // Handle File Uploads on Update
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = APP_ROOT . '/public/uploads/students/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'std_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $fileName)) {
                $input['photo_url'] = APP_URL . '/public/uploads/students/' . $fileName;
            }
        }
        if (isset($_FILES['identity_doc']) && $_FILES['identity_doc']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = APP_ROOT . '/public/uploads/students/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['identity_doc']['name'], PATHINFO_EXTENSION);
            $fileName = 'id_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['identity_doc']['tmp_name'], $uploadDir . $fileName)) {
                $input['identity_doc_url'] = APP_URL . '/public/uploads/students/' . $fileName;
            }
        }

        // Handle Date Conversions (AD <-> BS) on update
        if (isset($input['dob_ad']) || isset($input['dob_bs'])) {
            $dobAd = !empty($input['dob_ad']) ? $input['dob_ad'] : null;
            $dobBs = !empty($input['dob_bs']) ? $input['dob_bs'] : null;

            if ($dobAd && !$dobBs) {
                try { $input['dob_bs'] = \App\Helpers\DateUtils::adToBs($dobAd); } catch (\Exception $e) {}
            } elseif (!$dobAd && $dobBs) {
                try { $input['dob_ad'] = \App\Helpers\DateUtils::bsToAd($dobBs); } catch (\Exception $e) {}
            }
        }

        $fields = [];
        $params = ['id' => $id, 'tid' => $tenantId];

        $allowedFields = [
            'batch_id', 'roll_no', 'full_name', 'phone', 'email', 'dob_ad', 'dob_bs', 
            'gender', 'blood_group', 'citizenship_no', 'national_id', 
            'father_name', 'mother_name', 'husband_name', 'guardian_name', 'guardian_relation', 
            'photo_url', 'identity_doc_url', 
            'status', 'admission_date', 'registration_mode', 'registration_status',
        ];

        foreach ($allowedFields as $f) {
            if (isset($input[$f])) {
                $fields[] = "$f = :$f";
                $params[$f] = $input[$f];
            }
        }

        // Handle JSON fields (avoid double encoding)
        $jsonFields = [
            'permanent_address' => 'p_addr',
            'temporary_address' => 't_addr',
            'academic_qualifications' => 'qual'
        ];
        foreach ($jsonFields as $orig => $par) {
            if (isset($input[$orig])) {
                $fields[] = "$orig = :$par";
                $val = $input[$orig];
                if (is_array($val)) {
                    $params[$par] = json_encode($val);
                } else if (is_string($val) && (strpos($val, '{') === 0 || strpos($val, '[') === 0)) {
                    $params[$par] = $val; // Treat as already JSON
                } else {
                    $params[$par] = json_encode($val);
                }
            }
        }

        if (!empty($fields)) {
            $sql = "UPDATE students SET " . implode(', ', $fields) . " WHERE id = :id AND tenant_id = :tid";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }

        // Update linked user if name or phone changed
        if ($student['user_id']) {
            $userFields = [];
            $userParams = ['uid' => $student['user_id']];
            if (isset($input['full_name'])) {
                $userFields[] = "name = :name";
                $userParams['name'] = $input['full_name'];
            }
            if (isset($input['phone'])) {
                $userFields[] = "phone = :phone";
                $userParams['phone'] = $input['phone'];
            }
            if (isset($input['status'])) {
                $userFields[] = "status = :status";
                $userParams['status'] = ($input['status'] === 'active' ? 'active' : 'inactive');
            }

            if (!empty($userFields)) {
                $stmt = $db->prepare("UPDATE users SET " . implode(', ', $userFields) . " WHERE id = :uid");
                $stmt->execute($userParams);
            }
        }

        $db->commit();
        
        // --- Trigger ID Card & Completion Email if status changed to fully_registered ---
        if (isset($input['registration_status']) && $input['registration_status'] === 'fully_registered') {
            try {
                // Fetch full fresh data for ID card
                $stmtS = $db->prepare("
                    SELECT s.*, t.name as institute_name 
                    FROM students s 
                    JOIN tenants t ON s.tenant_id = t.id 
                    WHERE s.id = :id
                ");
                $stmtS->execute(['id' => $id]);
                $sData = $stmtS->fetch();

                if ($sData) {
                    $pngData = \App\Helpers\IDCardHelper::generate(['name' => $sData['institute_name']], $sData);
                    if ($pngData) {
                        $tempPath = APP_ROOT . '/public/uploads/temp_id_cards/id_' . $id . '.png';
                        if (!is_dir(dirname($tempPath))) mkdir(dirname($tempPath), 0777, true);
                        file_put_contents($tempPath, $pngData);

                        // Send Email with Attachment via Queue
                        $emailData = [
                            'email' => $sData['email'],
                            'student_name' => $sData['full_name'],
                            'subject' => "Profile Completed & Digital ID Card – " . $sData['institute_name'],
                            'pdf_path' => $tempPath, // Attachment path
                            'template_key' => 'student_profile_updated' // Or a specific ID card template
                        ];
                        
                        \App\Helpers\StudentEmailHelper::notifyProfileUpdated($db, $tenantId, $emailData);
                    }
                }
            } catch (\Exception $e) {
                error_log("[IDCard] Failed to generate/email: " . $e->getMessage());
            }
        }


        echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
    }

    else if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_GET;

        $ids = $input['id'] ?? null;
        if (!$ids) throw new Exception("Student ID(s) required");

        // Convert single ID to array for uniform processing
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $db->beginTransaction();
        try {
            foreach ($ids as $id) {
                // Verify ownership for each student
                $stmt = $db->prepare("SELECT id, user_id FROM students WHERE id = :id AND tenant_id = :tid");
                $stmt->execute(['id' => $id, 'tid' => $tenantId]);
                $student = $stmt->fetch();

                if ($student) {
                    // Soft delete student
                    $stmt = $db->prepare("UPDATE students SET deleted_at = NOW() WHERE id = :id");
                    $stmt->execute(['id' => $id]);

                    // Soft delete user if exists
                    if ($student['user_id']) {
                        $stmt = $db->prepare("UPDATE users SET deleted_at = NOW() WHERE id = :uid");
                        $stmt->execute(['uid' => $student['user_id']]);
                    }
                }
            }
            $db->commit();
            echo json_encode(['success' => true, 'message' => count($ids) > 1 ? 'Students deleted successfully' : 'Student deleted successfully']);
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
