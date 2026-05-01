<?php
/**
 * Admin Homework Store API
 * Route: /api/admin/homework/store
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isLoggedIn() || !hasPermission('exams.view')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenant_id = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenant_id) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}
$user_id = $_SESSION['userData']['id'] ?? null;

try {
    $db = getDBConnection();
    
    $course_id = intval($_POST['course_id'] ?? 0);
    $batch_id = intval($_POST['batch_id'] ?? 0);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    $total_marks = intval($_POST['total_marks'] ?? 100);
    $status = trim($_POST['status'] ?? 'published');

    if ($course_id === 0 || $batch_id === 0 || $subject_id === 0 || empty($title) || empty($due_date)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $attachment_path = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Attachment exceeds 5MB limit']);
            exit;
        }
        $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid attachment type']);
            exit;
        }
        $uploadDir = APP_ROOT . '/storage/uploads/homework/' . $tenant_id . '/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $targetFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFilePath)) {
            $attachment_path = 'storage/uploads/homework/' . $tenant_id . '/' . $fileName;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload attachment']);
            exit;
        }
    }

    $sql = "
        INSERT INTO homework (tenant_id, course_id, batch_id, subject_id, title, description, due_date, total_marks, attachment_path, created_by, status)
        VALUES (:tenant_id, :course_id, :batch_id, :subject_id, :title, :description, :due_date, :total_marks, :attachment_path, :created_by, :status)
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':tenant_id' => $tenant_id,
        ':course_id' => $course_id,
        ':batch_id' => $batch_id,
        ':subject_id' => $subject_id,
        ':title' => $title,
        ':description' => $description,
        ':due_date' => $due_date,
        ':total_marks' => $total_marks,
        ':attachment_path' => $attachment_path,
        ':created_by' => $user_id,
        ':status' => $status
    ]);

    echo json_encode(['success' => true, 'message' => 'Homework assigned successfully']);

} catch (PDOException $e) {
    error_log("Homework Store Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
    }
