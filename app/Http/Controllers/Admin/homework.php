<?php
/**
 * Admin Homework API
 * Route: /api/admin/homework
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('exams.view')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenant_id = $_SESSION['userData']['tenant_id'] ?? null;
$role = $_SESSION['userData']['role'] ?? '';
$user_id = $_SESSION['userData']['id'] ?? null;

if (!$tenant_id) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$action = $_GET['action'] ?? 'list';

try {
    $db = getDBConnection();

    switch ($action) {
        case 'list':
            $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
            $batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
            $status = isset($_GET['status']) ? $_GET['status'] : '';

            $where = ["h.tenant_id = :tenant_id"];
            $params = [':tenant_id' => $tenant_id];

            // If teacher, show their own homework OR homework for their allocated batches/subjects
            if ($role === 'teacher' && $user_id) {
                // Get teacher_id first
                $tStmt = $db->prepare("SELECT id FROM teachers WHERE user_id = :uid AND tenant_id = :tid");
                $tStmt->execute(['uid' => $user_id, 'tid' => $tenant_id]);
                $teacherId = $tStmt->fetchColumn();

                if ($teacherId) {
                    $where[] = "(h.created_by = :created_by OR (h.batch_id, h.subject_id) IN (SELECT batch_id, subject_id FROM batch_subject_allocations WHERE teacher_id = :tid))";
                    $params[':created_by'] = $user_id;
                    $params[':tid'] = $teacherId;
                } else {
                    $where[] = "h.created_by = :created_by";
                    $params[':created_by'] = $user_id;
                }
            }

            if ($course_id > 0) {
                $where[] = "h.course_id = :course_id";
                $params[':course_id'] = $course_id;
            }
            if ($batch_id > 0) {
                $where[] = "h.batch_id = :batch_id";
                $params[':batch_id'] = $batch_id;
            }
            if (!empty($status)) {
                $where[] = "h.status = :status";
                $params[':status'] = $status;
            }

            $whereClause = implode(" AND ", $where);
            $sql = "
                SELECT 
                    h.id, h.title, h.due_date, h.total_marks, h.status,
                    c.name as course_name, b.name as batch_name, s.name as subject_name,
                    (SELECT COUNT(*) FROM homework_submissions WHERE homework_id = h.id) as submission_count
                FROM homework h
                LEFT JOIN courses c ON h.course_id = c.id
                LEFT JOIN batches b ON h.batch_id = b.id
                LEFT JOIN subjects s ON h.subject_id = s.id
                WHERE {$whereClause}
                ORDER BY h.created_at DESC
                LIMIT 100
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $homework = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'homework' => $homework]);
            break;

        case 'submissions':
            $homework_id = isset($_GET['homework_id']) ? intval($_GET['homework_id']) : 0;
            if (!$homework_id) throw new Exception("Homework ID missing");

            // Verify access
            $checkStmt = $db->prepare("SELECT created_by, batch_id, subject_id FROM homework WHERE id = :hid AND tenant_id = :tid");
            $checkStmt->execute(['hid' => $homework_id, 'tid' => $tenant_id]);
            $hw = $checkStmt->fetch();
            if (!$hw) throw new Exception("Access Denied");

            if ($role === 'teacher' && $hw['created_by'] != $user_id) {
                // If not creator, check if assigned to this batch/subject
                $tStmt = $db->prepare("SELECT id FROM teachers WHERE user_id = :uid AND tenant_id = :tid");
                $tStmt->execute(['uid' => $user_id, 'tid' => $tenant_id]);
                $teacherId = $tStmt->fetchColumn();

                if (!$teacherId || !($db->query("SELECT id FROM batch_subject_allocations WHERE teacher_id = {$teacherId} AND batch_id = {$hw['batch_id']} AND subject_id = {$hw['subject_id']}")->fetch())) {
                    throw new Exception("Access Denied");
                }
            }

            $sql = "
                SELECT 
                    subs.id, subs.submission_text, subs.submission_attachment, subs.submitted_at,
                    subs.marks_obtained, subs.graded_at, subs.comments,
                    u.name as student_name, s.roll_no
                FROM homework_submissions subs
                JOIN students s ON subs.student_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE subs.homework_id = :hid AND subs.tenant_id = :tid
                ORDER BY subs.submitted_at DESC
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute(['hid' => $homework_id, 'tid' => $tenant_id]);
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'submissions' => $submissions]);
            break;

        case 'grade':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Invalid Method");
            $submission_id = intval($_POST['id'] ?? 0);
            $marks = $_POST['marks'] ?? null;
            $comments = $_POST['comments'] ?? '';

            if (!$submission_id) throw new Exception("Submission ID missing");

            // Verify access to the homework via this submission
            $checkStmt = $db->prepare("
                SELECT h.created_by, h.batch_id, h.subject_id FROM homework h
                JOIN homework_submissions s ON h.id = s.homework_id
                WHERE s.id = :sid AND h.tenant_id = :tid
            ");
            $checkStmt->execute(['sid' => $submission_id, 'tid' => $tenant_id]);
            $hw = $checkStmt->fetch();
            if (!$hw) throw new Exception("Access Denied");

            if ($role === 'teacher' && $hw['created_by'] != $user_id) {
                // If not creator, check if assigned to this batch/subject
                $tStmt = $db->prepare("SELECT id FROM teachers WHERE user_id = :uid AND tenant_id = :tid");
                $tStmt->execute(['uid' => $user_id, 'tid' => $tenant_id]);
                $teacherId = $tStmt->fetchColumn();

                if (!$teacherId || !($db->query("SELECT id FROM batch_subject_allocations WHERE teacher_id = {$teacherId} AND batch_id = {$hw['batch_id']} AND subject_id = {$hw['subject_id']}")->fetch())) {
                    throw new Exception("Access Denied");
                }
            }

            $stmt = $db->prepare("
                UPDATE homework_submissions 
                SET marks_obtained = :marks, comments = :comments, graded_at = NOW(), status = 'graded'
                WHERE id = :sid AND tenant_id = :tid
            ");
            $stmt->execute([
                'marks' => $marks,
                'comments' => $comments,
                'sid' => $submission_id,
                'tid' => $tenant_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Graded successfully']);
            break;

        default:
            throw new Exception("Invalid Action");
    }

} catch (Exception $e) {
    error_log("Homework API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
