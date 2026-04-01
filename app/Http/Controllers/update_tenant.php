<?php
/**
 * iSoftro ERP — Update Tenant API
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // 0. Security Checks
    $currentUserId = $_SESSION['userData']['id'] ?? null;
    if (!$currentUserId) {
        throw new Exception("Unauthorized: No active session.");
    }
    
    // CSRF Check
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        throw new Exception("Security violation: Invalid CSRF token.");
    }

    // Get POST data
    $id = sanitizeInput($_POST['id'] ?? '');
    $name = sanitizeInput($_POST['name'] ?? '');
    $nepaliName = sanitizeInput($_POST['nepaliName'] ?? '');
    $subdomain = sanitizeInput($_POST['subdomain'] ?? '');
    $brandColor = sanitizeInput($_POST['brandColor'] ?? '#009E7E');
    $tagline = sanitizeInput($_POST['tagline'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $panNumber = sanitizeInput($_POST['panNumber'] ?? '');
    $instituteType = sanitizeInput($_POST['instituteType'] ?? '');
    
    $plan = sanitizeInput($_POST['plan'] ?? 'starter');
    $status = sanitizeInput($_POST['status'] ?? 'trial');
    $studentLimit = (int)($_POST['student_limit'] ?? 100);
    $smsCredits = (int)($_POST['sms_credits'] ?? 500);
    
    if (!$id || !$name || !$subdomain || !$instituteType) {
        throw new Exception("Required fields are missing.");
    }
    
    // Check if subdomain exists for other tenants
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE subdomain = ? AND id != ?");
    $stmt->execute([$subdomain, $id]);
    if ($stmt->fetch()) {
        throw new Exception("The subdomain '$subdomain' is already taken.");
    }

    $pdo->beginTransaction();
    
    // Update Tenant
    $stmt = $pdo->prepare("UPDATE tenants SET name = ?, nepali_name = ?, subdomain = ?, institute_type = ?, brand_color = ?, tagline = ?, address = ?, phone = ?, email = ?, pan_number = ?, plan = ?, status = ?, student_limit = ?, sms_credits = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$name, $nepaliName, $subdomain, $instituteType, $brandColor, $tagline, $address, $phone, $email, $panNumber, $plan, $status, $studentLimit, $smsCredits, $id]);
    
    // Process logo if uploaded
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../uploads/logos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileExt = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'svg', 'webp', 'gif'])) {
            $newFileName = 'logo_' . $id . '_' . time() . '.' . $fileExt;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $newFileName)) {
                $logoPath = '/uploads/logos/' . $newFileName;
                $stmtLogo = $pdo->prepare("UPDATE tenants SET logo_path = ? WHERE id = ?");
                $stmtLogo->execute([$logoPath, $id]);
            }
        }
    }
    
    // Log the action
    $userIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, description, created_at) VALUES (?, 'Tenant Updated', ?, ?, ?, NOW())");
    $stmt->execute([$currentUserId, $userIp, $userAgent, "Tenant '$name' ($subdomain) updated."]);
    
    // Attach Features
    $stmtDel = $pdo->prepare("DELETE FROM institute_feature_access WHERE tenant_id = ?");
    $stmtDel->execute([$id]);
    
    $features = $_POST['features'] ?? [];
    if (is_array($features) && count($features) > 0) {
        $stmtFeats = $pdo->prepare("INSERT INTO institute_feature_access (tenant_id, feature_id, is_enabled) VALUES (?, ?, 1)");
        foreach ($features as $f) {
            $featId = (int)$f;
            if ($featId > 0) {
                $stmtFeats->execute([$id, $featId]);
            }
        }
    }

    
    $pdo->commit();
    
    // Send back the new CSRF token in header for synchronized AJAX requests
    if (function_exists('getCsrfToken')) {
        header('X-CSRF-Token: ' . getCsrfToken());
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Institute updated successfully!'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[UpdateTenant Error] " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

