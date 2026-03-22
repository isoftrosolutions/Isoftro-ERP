<?php
/**
 * Hamro ERP — Save New Tenant API
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
    $name = sanitizeInput($_POST['name'] ?? '');
    $nepaliName = sanitizeInput($_POST['nepaliName'] ?? '');
    $subdomain = sanitizeInput($_POST['subdomain'] ?? '');
    $brandColor = sanitizeInput($_POST['brandColor'] ?? '#009E7E');
    $tagline = sanitizeInput($_POST['tagline'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? ''); // Institute Email
    
    $adminName = sanitizeInput($_POST['adminName'] ?? '');
    $adminEmail = sanitizeInput($_POST['adminEmail'] ?? '');
    $adminPhone = sanitizeInput($_POST['adminPhone'] ?? '');
    $adminPass = $_POST['adminPass'] ?? ''; // Don't sanitize password as it might have special chars
    
    $instituteType = sanitizeInput($_POST['instituteType'] ?? '');
    $plan = sanitizeInput($_POST['plan'] ?? 'starter');
    $status = sanitizeInput($_POST['status'] ?? 'trial');
    
    // Simple validation
    if (!$name || !$subdomain || !$adminEmail || !$adminPass || !$instituteType) {
        throw new Exception("Required fields are missing (Name, Subdomain, Admin Email, Password, and Institute Type).");
    }

    // Password strength validation
    $passwordErrors = [];
    if (strlen($adminPass) < 8) $passwordErrors[] = "at least 8 characters";
    if (!preg_match('/[A-Z]/', $adminPass)) $passwordErrors[] = "one uppercase letter";
    if (!preg_match('/[a-z]/', $adminPass)) $passwordErrors[] = "one lowercase letter";
    if (!preg_match('/[0-9]/', $adminPass)) $passwordErrors[] = "one number";
    if (!preg_match('/[^A-Za-z0-9]/', $adminPass)) $passwordErrors[] = "one special character";
    
    if (!empty($passwordErrors)) {
        throw new Exception("Password must contain: " . implode(", ", $passwordErrors) . ".");
    }
    
    // Check if subdomain exists
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE subdomain = ?");
    $stmt->execute([$subdomain]);
    if ($stmt->fetch()) {
        throw new Exception("The subdomain '$subdomain' is already taken.");
    }
    
    // Check if admin email exists globally or within tenant (here globally for admin unique check)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if ($stmt->fetch()) {
        throw new Exception("The admin email '$adminEmail' is already registered.");
    }

    $pdo->beginTransaction();
    
    // 1. Insert Tenant
    $stmt = $pdo->prepare("INSERT INTO tenants (name, nepali_name, subdomain, institute_type, brand_color, tagline, address, phone, email, plan, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $nepaliName, $subdomain, $instituteType, $brandColor, $tagline, $address, $phone, $email, $plan, $status, $currentUserId]);
    $tenantId = $pdo->lastInsertId();

    // Process logo if uploaded
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../public/uploads/logos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileExt = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'svg', 'webp', 'gif'])) {
            $newFileName = 'logo_' . $tenantId . '_' . time() . '.' . $fileExt;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $newFileName)) {
                $logoPath = '/public/uploads/logos/' . $newFileName;
                $stmtLogo = $pdo->prepare("UPDATE tenants SET logo_path = ? WHERE id = ?");
                $stmtLogo->execute([$logoPath, $tenantId]);
            }
        }
    }
    
    // 2. Create Admin User
    $passwordHash = password_hash($adminPass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (tenant_id, role, email, password_hash, phone, name, status, created_at) VALUES (?, 'instituteadmin', ?, ?, ?, ?, 'active', NOW())");
    $stmt->execute([$tenantId, $adminEmail, $passwordHash, $adminPhone, $adminName]);
    $userId = $pdo->lastInsertId();
    
    // 3. Log the action
    $userIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, description, created_at) VALUES (?, 'Tenant Created', ?, ?, ?, NOW())");
    $stmt->execute([$currentUserId, $userIp, $userAgent, "New tenant '$name' ($subdomain) created with admin '$adminEmail'"]);
    
    $pdo->commit();
    
    // 4. Send Welcome Email
    try {
        if (!class_exists('App\Helpers\MailHelper')) {
            require_once APP_ROOT . '/app/Helpers/MailHelper.php';
        }
        
        // Simple login URL using APP_URL as per requested
        $loginUrl = APP_URL . '/auth/login';
        
        $welcomeData = [
            'institute_name' => $name,
            'admin_name'     => $adminName,
            'admin_email'    => $adminEmail,
            'admin_pass'     => $adminPass,
            'subdomain'      => $subdomain,
            'login_url'      => $loginUrl
        ];
        
        $tpl = \App\Helpers\MailHelper::getStaticTemplate('tenant_welcome', $welcomeData);
        if ($tpl) {
            // Force system SMTP because tenant SMTP is not set up yet
            \App\Helpers\MailHelper::sendDirect($pdo, $tenantId, $adminEmail, $adminName, $tpl['subject'], $tpl['body'], '', 0, true);
        }
    } catch (\Exception $mailEx) {
        error_log("[Tenant Welcome Email Error] " . $mailEx->getMessage());
    }

    // Send back the new CSRF token in header for synchronized AJAX requests
    if (function_exists('getCsrfToken')) {
        header('X-CSRF-Token: ' . getCsrfToken());
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Institute registered successfully!',
        'tenantId' => $tenantId
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
