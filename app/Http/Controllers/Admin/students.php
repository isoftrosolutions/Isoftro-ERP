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

$currUser = getCurrentUser();
$tenantId = $_SESSION['userData']['tenant_id'] ?? $currUser['tenant_id'] ?? null;

if (!$tenantId) {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    }
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Method spoofing for multipart/form-data PUT/PATCH
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

/**
 * Securely validate and store an uploaded file.
 * Uses finfo MIME detection (not extension) to prevent PHP/shell upload attacks.
 *
 * @param array  $fileEntry     Entry from $_FILES
 * @param string $prefix        Filename prefix (e.g. 'std' or 'id')
 * @param array  $allowedMimes  Allowed MIME types
 * @param array  $allowedExts   Allowed file extensions (lowercase)
 * @return string|null  Public URL on success, null on failure
 */
function validateAndUploadFile(array $fileEntry, string $prefix, array $allowedMimes, array $allowedExts): ?string {
    if ($fileEntry['error'] !== UPLOAD_ERR_OK) return null;
    if ($fileEntry['size'] > 5 * 1024 * 1024) { // 5 MB cap
        throw new Exception('File too large. Maximum size is 5 MB.');
    }

    // MIME detection via finfo (reads magic bytes, not extension)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileEntry['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes, true)) {
        throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedMimes));
    }

    // Derive and whitelist extension from MIME
    $ext = strtolower(pathinfo($fileEntry['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        // Fall back to a safe extension derived from MIME
        $mimeExtMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];
        $ext = $mimeExtMap[$mimeType] ?? 'bin';
    }

    $uploadDir = APP_ROOT . '/public/uploads/students/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

    // Use a random UUID-style name to prevent any path traversal
    $safeFilename = $prefix . '_' . bin2hex(random_bytes(12)) . '.' . $ext;
    $destination  = $uploadDir . $safeFilename;

    if (!move_uploaded_file($fileEntry['tmp_name'], $destination)) {
        throw new Exception('Failed to move uploaded file.');
    }

    return APP_URL . '/public/uploads/students/' . $safeFilename;
}

try {
    $db = getDBConnection();

    // Handle CSV Export
    if ($method === 'GET' && isset($_GET['export']) && $_GET['export'] === 'csv') {
        $whereSql = "WHERE s.tenant_id = :tid AND s.deleted_at IS NULL";
        $params = ['tid' => $tenantId];

        if (!empty($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            $whereSql .= " AND (u.name LIKE :s1 OR s.roll_no LIKE :s2 OR u.email LIKE :s3 OR u.phone LIKE :s4)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
            $params['s4'] = $search;
        }
        if (!empty($_GET['status'])) {
            $whereSql .= " AND s.status = :status";
            $params['status'] = $_GET['status'];
        }
        if (!empty($_GET['batch_id'])) {
            $whereSql .= " AND e.batch_id = :batch_id";
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
        $query = "SELECT s.roll_no, u.name as full_name, u.email, u.phone, s.gender, s.dob_bs, s.dob_ad,
                         s.citizenship_no, s.father_name, s.mother_name, s.guardian_name, s.guardian_relation,
                         s.permanent_address, s.temporary_address,
                         b.name as batch_name, c.name as course_name,
                         s.status, s.admission_date, s.created_at
                  FROM students s 
                  JOIN users u ON s.user_id = u.id
                  LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                  LEFT JOIN batches b ON e.batch_id = b.id 
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
        $csv = "Roll No,Full Name,Email,Phone,Gender,DOB (BS),DOB (AD),Citizenship No,Father's Name,Mother's Name,Guardian Name,Guardian Relation,Permanent Address,Temporary Address,Batch,Course,Status,Admission Date,Joined Date\n";

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
                    '"' . str_replace('"', '""', ($s['father_name'] ?? '')) . '",' .
                    '"' . str_replace('"', '""', ($s['mother_name'] ?? '')) . '",' .
                    '"' . str_replace('"', '""', ($s['guardian_name'] ?? '')) . '",' .
                    '"' . str_replace('"', '""', ($s['guardian_relation'] ?? '')) . '",' .
                    '"' . str_replace('"', '""', $addr) . '",' .
                    '"' . str_replace('"', '""', $taddr) . '",' .
                    '"' . ($s['batch_name'] ?? '') . '",' .
                    '"' . ($s['course_name'] ?? '') . '",' .
                    '"' . ($s['status'] ?? '') . '",' .
                    '"' . ($s['admission_date'] ?? '') . '",' .
                    '"' . ($s['created_at'] ?? '') . '"' . "\n";
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }

    // Handle Stats
    if ($method === 'GET' && isset($_GET['stats'])) {
        $stats = [];

        // Total current students (Active + Inactive)
        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND status NOT IN ('alumni', 'dropped') AND deleted_at IS NULL");
        $stmt->execute(['tid' => $tenantId]);
        $stats['total'] = (int)$stmt->fetchColumn();

        // Count new students this month (excluding those who already left)
        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND status NOT IN ('alumni', 'dropped') AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND deleted_at IS NULL");
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

        // Total Courses
        $stmt = $db->prepare("SELECT COUNT(*) FROM courses WHERE tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['tid' => $tenantId]);
        $stats['courses'] = (int)$stmt->fetchColumn();

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
            $whereSql .= " AND (u.name LIKE :s1 OR s.roll_no LIKE :s2 OR u.email LIKE :s3 OR u.phone LIKE :s4)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
            $params['s4'] = $search;
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
        } elseif (empty($_GET['id'])) {
            $whereSql .= " AND s.status NOT IN ('alumni', 'dropped')";
        }

        // Course filter
        if (!empty($_GET['course_id'])) {
            $whereSql .= " AND b.course_id = :course_id";
            $params['course_id'] = $_GET['course_id'];
        }

        // Course filter
        if (!empty($_GET['course_id'])) {
            $whereSql .= " AND b.course_id = :course_id";
            $params['course_id'] = $_GET['course_id'];
        }

        // Batch filter
        if (!empty($_GET['batch_id'])) {
            $whereSql .= " AND e.batch_id = :batch_id";
            $params['batch_id'] = $_GET['batch_id'];
        }

        // Count total for pagination
        $countQuery = "SELECT COUNT(*) FROM students s 
                       JOIN users u ON s.user_id = u.id
                       LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                       LEFT JOIN batches b ON e.batch_id = b.id 
                       $whereSql";
        $stmtCount = $db->prepare($countQuery);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Data query
        $query = "SELECT s.*, u.name as full_name, u.name as name, u.email, u.phone, s.admission_date,
                         b.name as batch_name, e.batch_id as batch_id, b.course_id as course_id, c.name as course_name,
                         COALESCE(sfs.fee_status, 'no_fees') as fee_status,
                         COALESCE(sfs.total_fee, 0) as total_fee,
                         COALESCE(sfs.paid_amount, 0) as paid_amount,
                         COALESCE(sfs.due_amount, 0) as due_amount
                  FROM students s 
                  JOIN users u ON s.user_id = u.id
                  LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                  LEFT JOIN batches b ON e.batch_id = b.id 
                  LEFT JOIN courses c ON b.course_id = c.id
                  LEFT JOIN student_fee_summary sfs ON s.id = sfs.student_id AND (e.id = sfs.enrollment_id OR e.id IS NULL)
                  $whereSql
                  GROUP BY s.id
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
                SELECT id, amount, payment_date, payment_method as payment_mode, receipt_number as reference, receipt_number, 'transaction' as source 
                FROM payment_transactions 
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

        // --- Action: Mark Alumni ---
        if ($action === 'mark_alumni') {
            $studentId = $input['student_id'] ?? null;
            $alumniYear = $input['alumni_year'] ?? date('Y');
            $completionStatus = $input['completion_status'] ?? 'completed';
            $remarks = $input['remarks'] ?? '';

            if (!$studentId) throw new Exception("Student ID is required");

            $stmt = $db->prepare("UPDATE students SET 
                status = 'alumni', 
                is_active = 0, 
                alumni_year = :year, 
                completion_status = :cstatus, 
                alumni_remarks = :remarks 
                WHERE id = :id AND tenant_id = :tid");
            
            $stmt->execute([
                'id' => $studentId, 
                'tid' => $tenantId, 
                'year' => $alumniYear, 
                'cstatus' => $completionStatus, 
                'remarks' => $remarks
            ]);

            // Also deactivate user if exists
            $stmt = $db->prepare("SELECT user_id FROM students WHERE id = :id");
            $stmt->execute(['id' => $studentId]);
            $std = $stmt->fetch();
            if ($std && $std['user_id']) {
                $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE id = :uid");
                $stmt->execute(['uid' => $std['user_id']]);
            }

            echo json_encode(['success' => true, 'message' => 'Student marked as alumni successfully']);
            exit;
        }

        // --- Action: Restore Student ---
        if ($action === 'restore_student') {
            $studentId = $input['student_id'] ?? null;
            if (!$studentId) throw new Exception("Student ID is required");

            $stmt = $db->prepare("UPDATE students SET 
                status = 'active', 
                is_active = 1,
                alumni_year = NULL,
                completion_status = NULL
                WHERE id = :id AND tenant_id = :tid");
            
            $stmt->execute(['id' => $studentId, 'tid' => $tenantId]);

            // Reactivate user if exists
            $stmt = $db->prepare("SELECT user_id FROM students WHERE id = :id");
            $stmt->execute(['id' => $studentId]);
            $std = $stmt->fetch();
            if ($std && $std['user_id']) {
                $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = :uid");
                $stmt->execute(['uid' => $std['user_id']]);
            }

            echo json_encode(['success' => true, 'message' => 'Student restored to active status']);
            exit;
        }

        // --- Action: Send Email ---
        if ($action === 'send_email') {
            $studentId = $input['student_id'] ?? null;
            $subject = $input['subject'] ?? '';
            $message = $input['message'] ?? '';
            $sendCredentials = !empty($input['send_credentials']);

            if (!$studentId) throw new Exception("Student ID is required");

            // Fetch student email, name, course and batch info
            $stmt = $db->prepare("SELECT u.name as full_name, u.email, s.roll_no, c.name as course_name, b.name as batch_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id LEFT JOIN courses c ON b.course_id = c.id WHERE s.id = :id AND s.tenant_id = :tid AND s.deleted_at IS NULL");
            $stmt->execute(['id' => $studentId, 'tid' => $tenantId]);
            $student = $stmt->fetch();

            if (!$student) throw new Exception("Student not found");
            if (empty($student['email'])) throw new Exception("Student does not have a registered email address");

            $success = false;

            if ($sendCredentials) {
                // Use default password if not provided
                $password = $input['password'] ?? 'Student@123'; 
                $success = \App\Helpers\StudentEmailHelper::sendWelcomeEmail($db, $tenantId, [
                    'student_name' => $student['full_name'],
                    'student_email' => $student['email'],
                    'roll_no' => $student['roll_no'] ?? '',
                    'course_name' => $student['course_name'] ?? '',
                    'batch_name' => $student['batch_name'] ?? '',
                    'temp_password' => $password,
                    'login_url' => (defined('APP_URL') ? APP_URL : '') . '/?page=login'
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
            $stmt = $db->prepare("SELECT s.id, u.name as full_name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.id IN ($placeholders) AND s.tenant_id = ? AND u.email IS NOT NULL AND u.email != '' AND s.deleted_at IS NULL");
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
            try {
                $studentId = $input['student_id'] ?? null;
                $amount = floatval($input['amount'] ?? 0);
                $paymentMode = $input['payment_mode'] ?? 'cash';
                $reference = $input['reference'] ?? null;
                $paymentDate = $input['payment_date'] ?? date('Y-m-d');
                $notes = $input['notes'] ?? 'Direct Payment via Student Profile';

                if (!$studentId || $amount <= 0) {
                    throw new Exception("Student ID and valid amount are required.");
                }

                $financeService = new \App\Services\FinanceService();
                $result = $financeService->recordBulkPayment([
                    'student_id' => $studentId,
                    'amount' => $amount,
                    'payment_mode' => $paymentMode,
                    'payment_date' => $paymentDate,
                    'notes' => $notes
                ], $tenantId);

                if ($result['success']) {
                    $receiptNo = $result['receipt_no'];
                    $transactionId = $result['transaction_ids'][0] ?? null;

                    // Queue receipt and email tasks
                    $queue = new \App\Services\QueueService();
                    $stdStmt = $db->prepare("SELECT u.name as student_name, u.email as student_email, s.roll_no, c.name as course_name, b.name as batch_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN batches b ON e.batch_id = b.id LEFT JOIN courses c ON b.course_id = c.id WHERE s.id = :sid");
                    $stdStmt->execute(['sid' => $studentId]);
                    $studentInfo = $stdStmt->fetch(PDO::FETCH_ASSOC);

                    $queue->dispatch('payment_receipt', [
                        'transaction_id' => $transactionId,
                        'receipt_no' => $receiptNo,
                        'student_id' => $studentId,
                        'student_name' => $studentInfo['student_name'] ?? 'Student',
                        'student_email' => $studentInfo['student_email'] ?? '',
                        'roll_no' => $studentInfo['roll_no'] ?? '',
                        'course_name' => $studentInfo['course_name'] ?? '',
                        'batch_name' => $studentInfo['batch_name'] ?? '',
                        'amount' => $amount,
                        'paid_date' => $paymentDate,
                        'payment_mode' => $paymentMode,
                        'login_url' => (defined('APP_URL') ? APP_URL : '') . '/?page=login'
                    ], $tenantId);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Payment recorded and allocated successfully.',
                        'data' => [
                            'receipt_no' => $receiptNo,
                            'amount_paid' => $amount,
                            'student_name' => $studentInfo['student_name'] ?? 'Student',
                            'redirect_url' => '?page=fee-details&receipt_no=' . $receiptNo
                        ]
                    ]);
                } else {
                    throw new Exception($result['message'] ?? 'Failed to record payment');
                }
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

        // Accept contact_number (Front Desk / Admin unified form) or phone

        // Accept contact_number (Front Desk / Admin unified form) or phone
        $phone   = $input['contact_number'] ?? $input['phone'] ?? null;
        $batchId = $input['batch_id'] ?? null;
        $rollNo  = $input['roll_no'] ?? null;

        // 1. Handle File Uploads with MIME validation
        $allowedImageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedImageExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedDocMimes   = ['image/jpeg', 'image/png', 'application/pdf'];
        $allowedDocExts    = ['jpg', 'jpeg', 'png', 'pdf'];

        $photoUrl = $input['photo_url'] ?? null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $photoUrl = validateAndUploadFile($_FILES['profile_image'], 'std', $allowedImageMimes, $allowedImageExts);
        }

        $identityDocUrl = null;
        if (isset($_FILES['identity_doc']) && $_FILES['identity_doc']['error'] === UPLOAD_ERR_OK) {
            $identityDocUrl = validateAndUploadFile($_FILES['identity_doc'], 'id', $allowedDocMimes, $allowedDocExts);
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

        // Handle File Uploads on Update — with MIME validation
        $allowedImageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedImageExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedDocMimes   = ['image/jpeg', 'image/png', 'application/pdf'];
        $allowedDocExts    = ['jpg', 'jpeg', 'png', 'pdf'];

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $url = validateAndUploadFile($_FILES['profile_image'], 'std', $allowedImageMimes, $allowedImageExts);
            if ($url) $input['photo_url'] = $url;
        }
        if (isset($_FILES['identity_doc']) && $_FILES['identity_doc']['error'] === UPLOAD_ERR_OK) {
            $url = validateAndUploadFile($_FILES['identity_doc'], 'id', $allowedDocMimes, $allowedDocExts);
            if ($url) $input['identity_doc_url'] = $url;
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

        // Only fields that exist in the `students` table
        $allowedFields = [
            'roll_no', 'dob_ad', 'dob_bs', 
            'gender', 'blood_group', 'citizenship_no', 'national_id', 
            'father_name', 'mother_name', 'husband_name', 'guardian_name', 'guardian_relation', 
            'photo_url', 'identity_doc_url', 
            'status', 'admission_date',
        ];

        foreach ($allowedFields as $f) {
            if (isset($input[$f])) {
                // Convert empty strings to NULL for date fields
                if (in_array($f, ['admission_date', 'dob_ad']) && $input[$f] === '') {
                    $input[$f] = null;
                }
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

        // Update batch_id in enrollments table (not in students)
        if (isset($input['batch_id']) && $input['batch_id']) {
            $enrStmt = $db->prepare("SELECT id FROM enrollments WHERE student_id = :sid AND tenant_id = :tid AND status = 'active' LIMIT 1");
            $enrStmt->execute(['sid' => $id, 'tid' => $tenantId]);
            $enrollment = $enrStmt->fetch(PDO::FETCH_ASSOC);
            if ($enrollment) {
                $db->prepare("UPDATE enrollments SET batch_id = :bid WHERE id = :eid")
                   ->execute(['bid' => $input['batch_id'], 'eid' => $enrollment['id']]);
            }
        }

        // Update linked user record (full_name, phone, email, status live in users table)
        if ($student['user_id']) {
            $userFields = [];
            $userParams = ['uid' => $student['user_id']];
            if (isset($input['full_name'])) {
                $userFields[] = "name = :name";
                $userParams['name'] = $input['full_name'];
            }
            if (isset($input['email'])) {
                $userFields[] = "email = :email";
                $userParams['email'] = $input['email'];
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
        
        // --- Trigger ID Card & Completion Email logic removed as registration_status column was dropped ---



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
