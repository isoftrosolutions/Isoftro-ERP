<?php
/**
 * Student Assignments API
 * Handles assignment viewing and submissions for students
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
$tenantId = $user['tenant_id'] ?? null;
$userId = $user['id'] ?? null;
$studentId = $_SESSION['userData']['student_id'] ?? null;

if (!$tenantId || !$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student record not found']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'pending';

try {
    $db = getDBConnection();
    
    // Get student's batch info
    $stmt = $db->prepare("
        SELECT batch_id FROM students WHERE id = :sid AND tenant_id = :tid LIMIT 1
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $batchId = $student['batch_id'] ?? null;
    
    switch ($action) {
        case 'pending':
            // Get pending assignments (not submitted or not graded)
            $stmt = $db->prepare("
                SELECT a.id, a.title, a.description, a.due_date, a.total_marks as max_marks, a.attachment_path as attachment_url,
                       s.name as subject_name, s.code as subject_code,
                       u.name as teacher_name,
                       DATEDIFF(a.due_date, CURDATE()) as days_remaining,
                       subs.submitted_at, subs.id as submission_id
                FROM homework a
                LEFT JOIN subjects s ON a.subject_id = s.id
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN homework_submissions subs ON a.id = subs.homework_id 
                    AND subs.student_id = :sid
                WHERE a.batch_id = :bid
                  AND a.tenant_id = :tid
                  AND a.status = 'published'
                  AND (subs.id IS NULL OR subs.marks_obtained IS NULL)
                ORDER BY a.due_date ASC
            ");
            $stmt->execute(['sid' => $studentId, 'bid' => $batchId, 'tid' => $tenantId]);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add urgency flag
            foreach ($assignments as &$assignment) {
                $daysRemaining = (int)$assignment['days_remaining'];
                if ($daysRemaining < 0) {
                    $assignment['urgency'] = 'overdue';
                } elseif ($daysRemaining <= 2) {
                    $assignment['urgency'] = 'high';
                } elseif ($daysRemaining <= 5) {
                    $assignment['urgency'] = 'medium';
                } else {
                    $assignment['urgency'] = 'low';
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $assignments,
                'count' => count($assignments)
            ]);
            break;
            
        case 'submitted':
            // Get submitted assignments awaiting grading
            $stmt = $db->prepare("
                SELECT a.id, a.title, a.description, a.due_date, a.total_marks as max_marks,
                       s.name as subject_name,
                       u.name as teacher_name,
                       subs.submitted_at, subs.submission_text, subs.attachment_path as attachment_url
                FROM homework_submissions subs
                JOIN homework a ON subs.homework_id = a.id
                LEFT JOIN subjects s ON a.subject_id = s.id
                LEFT JOIN users u ON a.created_by = u.id
                WHERE subs.student_id = :sid
                  AND a.tenant_id = :tid
                  AND subs.marks_obtained IS NULL
                ORDER BY subs.submitted_at DESC
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $submitted = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $submitted
            ]);
            break;
            
        case 'graded':
            // Get graded assignments
            $stmt = $db->prepare("
                SELECT a.id, a.title, a.description, a.due_date, a.total_marks as max_marks,
                       s.name as subject_name,
                       u.name as teacher_name,
                       subs.submitted_at, subs.marks_obtained, 
                       subs.feedback, subs.graded_at, subs.graded_by,
                       grader.name as graded_by_name
                FROM homework_submissions subs
                JOIN homework a ON subs.homework_id = a.id
                LEFT JOIN subjects s ON a.subject_id = s.id
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN users grader ON subs.graded_by = grader.id
                WHERE subs.student_id = :sid
                  AND a.tenant_id = :tid
                  AND subs.marks_obtained IS NOT NULL
                ORDER BY subs.graded_at DESC
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $graded = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $graded
            ]);
            break;
            
        case 'detail':
            $assignmentId = $_GET['assignment_id'] ?? null;
            
            if (!$assignmentId) {
                echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT a.id, a.title, a.description, a.due_date, a.total_marks as max_marks, a.attachment_path as attachment_url,
                       s.name as subject_name, s.code as subject_code,
                       u.name as teacher_name, u.email as teacher_email,
                       subs.id as submission_id, subs.submission_text,
                       subs.attachment_path as submission_attachment,
                       subs.submitted_at, subs.marks_obtained, subs.feedback,
                       DATEDIFF(a.due_date, CURDATE()) as days_remaining
                FROM homework a
                LEFT JOIN subjects s ON a.subject_id = s.id
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN homework_submissions subs ON a.id = subs.homework_id 
                    AND subs.student_id = :sid
                WHERE a.id = :aid AND a.tenant_id = :tid
            ");
            $stmt->execute(['aid' => $assignmentId, 'sid' => $studentId, 'tid' => $tenantId]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$assignment) {
                echo json_encode(['success' => false, 'message' => 'Assignment not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'data' => $assignment]);
            break;
            
        case 'submit':
            if ($method !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }
            
            $assignmentId = $_POST['assignment_id'] ?? null;
            $submissionText = $_POST['submission_text'] ?? '';
            
            if (!$assignmentId) {
                echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
                exit;
            }
            
            // Check if assignment exists and is not past due
            $stmt = $db->prepare("
                SELECT id, due_date FROM homework 
                WHERE id = :aid AND tenant_id = :tid AND batch_id = :bid
            ");
            $stmt->execute(['aid' => $assignmentId, 'tid' => $tenantId, 'bid' => $batchId]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$assignment) {
                echo json_encode(['success' => false, 'message' => 'Assignment not found']);
                exit;
            }
            
            // Check if already submitted
            $stmt = $db->prepare("
                SELECT id FROM homework_submissions 
                WHERE homework_id = :aid AND student_id = :sid
            ");
            $stmt->execute(['aid' => $assignmentId, 'sid' => $studentId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Handle file upload
            $attachmentUrl = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['attachment'];
                $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png', 'zip'];
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($extension, $allowedExtensions)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
                    exit;
                }
                
                $uploadDir = __DIR__ . '/../../../../public/uploads/assignments/submissions/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $filename = 'assignment_' . $assignmentId . '_student_' . $studentId . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $attachmentUrl = '/public/uploads/assignments/submissions/' . $filename;
                }
            }
            
            if ($existing) {
                // Update existing submission
                $sql = "
                    UPDATE homework_submissions 
                    SET submission_text = :text, submitted_at = NOW()
                ";
                $params = [
                    'text' => $submissionText,
                    'subid' => $existing['id']
                ];
                
                if ($attachmentUrl) {
                    $sql .= ", attachment_path = :attachment";
                    $params['attachment'] = $attachmentUrl;
                }
                
                $sql .= " WHERE id = :subid";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                
                $message = 'Submission updated successfully';
            } else {
                // Create new submission
                $stmt = $db->prepare("
                    INSERT INTO homework_submissions 
                    (homework_id, student_id, submission_text, attachment_path, status, submitted_at)
                    VALUES (:aid, :sid, :text, :attachment, 'submitted', NOW())
                ");
                $stmt->execute([
                    'aid' => $assignmentId,
                    'sid' => $studentId,
                    'text' => $submissionText,
                    'attachment' => $attachmentUrl
                ]);
                
                $message = 'Assignment submitted successfully';
            }
            
            echo json_encode(['success' => true, 'message' => $message]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    error_log("Student Assignments Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'code' => 'DB_ERROR'
    ]);
} catch (Exception $e) {
    error_log("Student Assignments Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred',
        'code' => 'GENERAL_ERROR'
    ]);
}
