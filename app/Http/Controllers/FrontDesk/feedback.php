<?php
/**
 * Feedback Controller — Handle user feedback submissions
 * File: app/Http/Controllers/Admin/feedback.php
 */
header('Content-Type: application/json');

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$tenantId = $_SESSION['tenant_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module = $_POST['module'] ?? 'other';
    $page = $_POST['page'] ?? null;
    $problem = $_POST['problem'] ?? null;
    $screenshotPath = null;

    if (empty($problem)) {
        echo json_encode(['success' => false, 'message' => 'Feedback description is required']);
        exit;
    }

    // Handle Screenshot Upload
    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['screenshot']['tmp_name'];
        $fileName = $_FILES['screenshot']['name'];
        $fileSize = $_FILES['screenshot']['size'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');

        if ($fileSize > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Screenshot exceeds 5MB limit']);
            exit;
        }

        if (in_array($fileExtension, $allowedfileExtensions, true)) {
            $uploadFileDir = __DIR__ . '/../../../../uploads/feedback/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $screenshotPath = '/uploads/feedback/' . $newFileName;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid screenshot type']);
            exit;
        }
    }

    try {
        $db = getDBConnection();
        $stmt = $db->prepare("
            INSERT INTO feedbacks (user_id, tenant_id, module, page, problem, screenshot_path, status, created_at, updated_at)
            VALUES (:user_id, :tenant_id, :module, :page, :problem, :screenshot_path, 'open', NOW(), NOW())
        ");

        $result = $stmt->execute([
            'user_id' => $user['id'],
            'tenant_id' => $tenantId,
            'module' => $module,
            'page' => $page,
            'problem' => $problem,
            'screenshot_path' => $screenshotPath
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save feedback']);
        }
    } catch (Exception $e) {
        error_log('Controller exception: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
    exit;
}

// If GET request, maybe return list of feedbacks for this user/tenant (optional)
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit;
