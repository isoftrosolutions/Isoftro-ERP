<?php
/**
 * Student Profile API
 * Handles profile viewing and updates for students
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

// Ensure user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$role = $user['role'] ?? '';
$tenantId = $user['tenant_id'] ?? null;
$userId = $user['id'] ?? null;

// Get student_id from session or user data
$studentId = $_SESSION['userData']['student_id'] ?? null;

// If role is student but no student_id, try to fetch it
if ($role === 'student' && !$studentId && $userId) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id FROM students WHERE user_id = :uid AND tenant_id = :tid LIMIT 1");
        $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $studentId = $result['id'];
            $_SESSION['userData']['student_id'] = $studentId;
        }
    } catch (Exception $e) {
        error_log("Failed to fetch student_id in profile: " . $e->getMessage());
    }
}

if (!$tenantId || !$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student record not found']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'view';

// ── Receipt download (served as HTML or PDF, not JSON) ──────────────────────
if ($action === 'receipt') {
    $receiptNo = $_GET['receipt_no'] ?? null;
    $isPdf     = !empty($_GET['is_pdf']);

    if (!$receiptNo) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Receipt number required']);
        exit;
    }

    try {
        $db = getDBConnection();

        // Security: confirm receipt belongs to THIS student
        $stmt = $db->prepare("
            SELECT pt.id, pt.receipt_number, pt.tenant_id
            FROM payment_transactions pt
            WHERE pt.receipt_number = :rno
              AND pt.student_id     = :sid
              AND pt.tenant_id      = :tid
            LIMIT 1
        ");
        $stmt->execute(['rno' => $receiptNo, 'sid' => $studentId, 'tid' => $tenantId]);
        $txn = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$txn) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Receipt not found']);
            exit;
        }

        // Load ReceiptHelper
        require_once base_path('vendor/autoload.php');

        $html = \App\Helpers\ReceiptHelper::getHtml($db, $tenantId, null, $receiptNo);
        if (!$html) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Could not generate receipt']);
            exit;
        }

        if ($isPdf) {
            $pdfPath = \App\Helpers\ReceiptHelper::generatePdf($db, $tenantId, null, $receiptNo);
            if (!$pdfPath || !file_exists($pdfPath)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'PDF generation failed']);
                exit;
            }
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Receipt_' . $receiptNo . '.pdf"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
        } else {
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
        }
        exit;

    } catch (Exception $e) {
        error_log('Student receipt error: ' . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error generating receipt']);
        exit;
    }
}

// All remaining actions return JSON
try {
    $db = getDBConnection();
    
    switch ($action) {
        case 'view':

            // Get complete student profile
            $stmt = $db->prepare("
                SELECT s.*, 
                       b.name as batch_name, b.start_date as batch_start,
                       c.name as course_name, 
                       COALESCE(CONCAT(c.duration_months, ' Months'), CONCAT(c.duration_weeks, ' Weeks'), 'N/A') as duration,
                       c.fee as course_fee,
                       t.name as institute_name, t.address as institute_address,
                       t.phone as institute_phone, t.email as institute_email,
                       t.logo_path as institute_logo,
                       u.email as login_email, u.phone as login_phone,
                       u.last_login_at
                FROM students s
                LEFT JOIN batches b ON s.batch_id = b.id
                LEFT JOIN courses c ON b.course_id = c.id
                LEFT JOIN tenants t ON s.tenant_id = t.id
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.id = :sid AND s.tenant_id = :tid
                LIMIT 1
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$profile) {
                echo json_encode(['success' => false, 'message' => 'Profile not found']);
                exit;
            }
            
            // Parse JSON fields
            if ($profile['permanent_address']) {
                $profile['permanent_address'] = json_decode($profile['permanent_address'], true);
            }
            if ($profile['temporary_address']) {
                $profile['temporary_address'] = json_decode($profile['temporary_address'], true);
            }
            if ($profile['academic_qualifications']) {
                $profile['academic_qualifications'] = json_decode($profile['academic_qualifications'], true);
            }
            
            echo json_encode(['success' => true, 'data' => $profile]);
            break;
            
        case 'update':
            if ($method !== 'POST' && $method !== 'PUT') {
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            
            // Fields that students can update
            $allowedFields = ['full_name', 'phone', 'email', 'date_of_birth', 'gender', 'blood_group', 'nationality', 'temporary_address', 'permanent_address', 'guardian_name', 'guardian_phone', 'guardian_relation', 'address'];
            $updates = [];
            $params = ['sid' => $studentId, 'tid' => $tenantId];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = :$field";
                    $params[$field] = $input[$field];
                }
            }
            
            if (empty($updates)) {
                echo json_encode(['success' => false, 'message' => 'No fields to update']);
                exit;
            }
            
            $sql = "UPDATE students SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :sid AND tenant_id = :tid";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            // Also update user email/phone if changed
            if (isset($input['email']) || isset($input['phone'])) {
                $userUpdates = [];
                $userParams = ['uid' => $userId];
                
                if (isset($input['email'])) {
                    $userUpdates[] = "email = :email";
                    $userParams['email'] = $input['email'];
                }
                if (isset($input['phone'])) {
                    $userUpdates[] = "phone = :phone";
                    $userParams['phone'] = $input['phone'];
                }
                
                if (!empty($userUpdates)) {
                    $userSql = "UPDATE users SET " . implode(', ', $userUpdates) . ", updated_at = NOW() WHERE id = :uid";
                    $stmt = $db->prepare($userSql);
                    $stmt->execute($userParams);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            break;
        
        case 'academic_history':
            // Get academic history (enrollments, results, etc.)
            $stmt = $db->prepare("
                SELECT 
                    sbe.*, b.name as batch_name, c.name as course_name,
                    c.duration, c.fee as course_fee
                FROM student_batch_enrollments sbe
                JOIN batches b ON sbe.batch_id = b.id
                JOIN courses c ON b.course_id = c.id
                WHERE sbe.student_id = :sid AND sbe.tenant_id = :tid
                ORDER BY sbe.created_at DESC
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $enrollments
            ]);
            break;

        case 'change_password':
            if ($method !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $currentPassword = $input['current_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';
            $confirmPassword = $input['confirm_password'] ?? '';
            
            // Validation
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
                exit;
            }
            
            if (strlen($newPassword) < 8) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
                exit;
            }
            
            // Verify current password
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :uid");
            $stmt->execute(['uid' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($currentPassword, $user['password_hash'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            
            // Update password
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :uid");
            $stmt->execute(['hash' => $newHash, 'uid' => $userId]);
            
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            break;
            
        case 'upload_document':
            // Handle document uploads (profile photo, citizenship, etc.)
            if ($method !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }
            
            $documentType = $_POST['document_type'] ?? '';
            $allowedTypes = ['photo', 'citizenship', 'transcript', 'certificate'];
            
            if (!in_array($documentType, $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Invalid document type']);
                exit;
            }
            
            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'File upload failed']);
                exit;
            }
            
            $file = $_FILES['document'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedExtensions)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, PDF']);
                exit;
            }
            
            // Create upload directory
            $uploadDir = __DIR__ . '/../../../../public/uploads/students/documents/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $filename = $documentType . '_' . $studentId . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update database based on document type
                $dbField = $documentType === 'photo' ? 'photo_url' : $documentType . '_document_url';
                $relativePath = '/public/uploads/students/documents/' . $filename;
                
                $stmt = $db->prepare("UPDATE students SET $dbField = :path, updated_at = NOW() WHERE id = :sid");
                $stmt->execute(['path' => $relativePath, 'sid' => $studentId]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Document uploaded successfully',
                    'path' => $relativePath
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save file']);
            }
            break;
            
        case 'fees':
            // Get fee details for the student
            try {
                // NOTE: fee_items only has: id, tenant_id, course_id, name, type, amount,
                //       installments, late_fine_per_day, is_active, created_at, updated_at, deleted_at
                $stmt = $db->prepare("
                    SELECT fr.id, fr.student_id, fr.fee_item_id, fr.batch_id,
                           fr.installment_no, fr.amount_due, fr.amount_paid,
                           fr.discount_amount, fr.due_date, fr.paid_date,
                           fr.receipt_no, fr.payment_mode, fr.fine_applied,
                           fr.fine_waived, fr.notes, fr.academic_year, fr.status,
                           fi.name as fee_item_name, fi.type as fee_item_type
                    FROM fee_records fr
                    LEFT JOIN fee_items fi ON fr.fee_item_id = fi.id
                    WHERE fr.student_id = :sid AND fr.tenant_id = :tid
                    ORDER BY fr.due_date DESC
                    LIMIT 50
                ");
                $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
                $feeRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get payment transactions
                // NOTE: payment_transactions uses payment_method (not payment_mode) and receipt_number
                $stmt = $db->prepare("
                    SELECT id, student_id, amount, payment_date, payment_mode, reference as receipt_number, reference, 'historical' as source, NULL as fee_item_name
                    FROM student_payments 
                    WHERE student_id = :sid1 AND tenant_id = :tid1
                    UNION ALL
                    SELECT pt.id, pt.student_id, pt.amount, pt.payment_date, pt.payment_method as payment_mode, pt.receipt_number, pt.receipt_number as reference, 'transaction' as source, fi.name as fee_item_name
                    FROM payment_transactions pt
                    LEFT JOIN fee_records fr ON pt.fee_record_id = fr.id
                    LEFT JOIN fee_items fi ON fr.fee_item_id = fi.id
                    WHERE pt.student_id = :sid2 AND pt.tenant_id = :tid2
                    ORDER BY payment_date DESC
                    LIMIT 50
                ");
                $stmt->execute(['sid1' => $studentId, 'tid1' => $tenantId, 'sid2' => $studentId, 'tid2' => $tenantId]);
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate summary from fee_records
                $totalDue = 0;
                $totalPaid = 0;
                foreach ($feeRecords as $fr) {
                    $totalDue += floatval($fr['amount_due'] ?? 0);
                    $totalPaid += floatval($fr['amount_paid'] ?? 0);
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'records'  => $feeRecords,
                        'payments' => $payments,
                        'summary'  => [
                            'total_due'   => $totalDue,
                            'total_paid'  => $totalPaid,
                            'outstanding' => $totalDue - $totalPaid
                        ]
                    ]
                ]);
            } catch (Exception $e) {
                error_log('Student Profile fees error: ' . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to load fee data: ' . $e->getMessage(),
                    'data'    => ['records' => [], 'payments' => [], 'summary' => ['total_due' => 0, 'total_paid' => 0, 'outstanding' => 0]]
                ]);
            }
            break;
            
        case 'attendance':
            // Get attendance records for the student
            try {
                $stmt = $db->prepare("
                    SELECT a.*, s.name as subject_name, s.code as subject_code
                    FROM attendance a
                    LEFT JOIN subjects s ON a.subject_id = s.id
                    WHERE a.student_id = :sid AND a.tenant_id = :tid
                    ORDER BY a.attendance_date DESC
                    LIMIT 100
                ");
                $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate summary
                $total = count($records);
                $present = 0;
                $absent = 0;
                $late = 0;
                foreach ($records as $r) {
                    $status = strtolower($r['status'] ?? '');
                    if ($status === 'present') $present++;
                    elseif ($status === 'absent') $absent++;
                    elseif ($status === 'late') $late++;
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'records' => $records,
                        'summary' => [
                            'total' => $total,
                            'present' => $present,
                            'absent' => $absent,
                            'late' => $late,
                            'percentage' => $total > 0 ? round((($present + $late * 0.5) / $total) * 100, 2) : 0
                        ]
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => true, 'data' => ['records' => [], 'summary' => ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'percentage' => 0]]]);
            }
            break;
            
        case 'exam_results':
            // Get exam results for the student
            try {
                $stmt = $db->prepare("
                    SELECT er.*, e.exam_name, e.exam_date, e.max_marks, e.pass_marks,
                           s.name as subject_name, s.code as subject_code
                    FROM exam_results er
                    LEFT JOIN exams e ON er.exam_id = e.id
                    LEFT JOIN subjects s ON e.subject_id = s.id
                    WHERE er.student_id = :sid AND er.tenant_id = :tid
                    ORDER BY e.exam_date DESC
                    LIMIT 50
                ");
                $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate summary
                $totalExams = count($results);
                $totalMarks = 0;
                $obtainedMarks = 0;
                $passed = 0;
                foreach ($results as $r) {
                    $obtainedMarks += floatval($r['marks_obtained'] ?? 0);
                    $totalMarks += floatval($r['max_marks'] ?? 0);
                    if (floatval($r['marks_obtained'] ?? 0) >= floatval($r['pass_marks'] ?? 0)) {
                        $passed++;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'results' => $results,
                        'summary' => [
                            'total_exams' => $totalExams,
                            'passed' => $passed,
                            'failed' => $totalExams - $passed,
                            'average_percentage' => $totalMarks > 0 ? round(($obtainedMarks / $totalMarks) * 100, 2) : 0
                        ]
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => true, 'data' => ['results' => [], 'summary' => ['total_exams' => 0, 'passed' => 0, 'failed' => 0, 'average_percentage' => 0]]]);
            }
            break;
            
        case 'course':
            // Get course and batch details
            try {
                $stmt = $db->prepare("
                    SELECT s.*, b.name as batch_name, b.start_date as batch_start_date, 
                           b.end_date as batch_end_date, b.status as batch_status,
                           c.name as course_name, 
                           COALESCE(CONCAT(c.duration_months, ' Months'), CONCAT(c.duration_weeks, ' Weeks'), 'N/A') as duration,
                           c.fee as course_fee,
                           c.description as course_description
                    FROM students s
                    LEFT JOIN batches b ON s.batch_id = b.id
                    LEFT JOIN courses c ON b.course_id = c.id
                    WHERE s.id = :sid AND s.tenant_id = :tid
                    LIMIT 1
                ");
                $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
                $courseInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $courseInfo ?: []
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => true, 'data' => []]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    $errorMsg = $e->getMessage();
    error_log("Student Profile Error: " . $errorMsg);
    
    // Check for specific common errors and provide more helpful messages
    $userMessage = 'Database error occurred';
    $errorCode = 'DB_ERROR';
    
    if (strpos($errorMsg, 'Table') !== false && strpos($errorMsg, "doesn't exist") !== false) {
        $userMessage = 'Required database table is missing. Please contact administrator.';
        $errorCode = 'TABLE_MISSING';
    } elseif (strpos($errorMsg, 'Column') !== false && strpos($errorMsg, "doesn't exist") !== false) {
        $userMessage = 'Database column missing. Please contact administrator.';
        $errorCode = 'COLUMN_MISSING';
    } elseif (strpos($errorMsg, 'Unknown database') !== false) {
        $userMessage = 'Database connection error. Please contact administrator.';
        $errorCode = 'DB_CONNECT_ERROR';
    }
    
    // In development, include more details
    $debugInfo = defined('APP_DEBUG') && APP_DEBUG ? ['debug' => ['sql_error' => $errorMsg, 'line' => $e->getLine()]] : [];
    
    echo json_encode(array_merge([
        'success' => false, 
        'message' => $userMessage,
        'code' => $errorCode
    ], $debugInfo));
} catch (Exception $e) {
    error_log("Student Profile Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred',
        'code' => 'GENERAL_ERROR'
    ]);
}
