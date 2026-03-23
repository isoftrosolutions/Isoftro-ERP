<?php
require_once __DIR__ . '/../../../config/config.php';

header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$userEmail = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
if (empty($userEmail)) {
    echo json_encode(['success' => false, 'message' => 'Email address is required.']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Check if user exists
    $stmt = $db->prepare("SELECT id, tenant_id, role, name FROM users WHERE email = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$userEmail]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // For security, don't reveal if email exists, but here we provide feedback as requested
        echo json_encode(['success' => false, 'message' => 'This email address is not registered in our system.']);
        exit;
    }

    $userId = $user['id'];
    $tenantId = $user['tenant_id'];
    $role = $user['role'];
    $userName = $user['name'] ?? 'User';

    // Generate a secure 6-digit OTP
    $resetToken = sprintf("%06d", random_int(100000, 999999));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    // Insert into password_resets table
    $insertStmt = $db->prepare("INSERT INTO password_resets (tenant_id, user_id, role, email, token, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
    $insertStmt->execute([$tenantId, $userId, $role, $userEmail, $resetToken, $expiresAt]);

    // Dispatch to background queue using AuthEmailHelper
    $success = \App\Helpers\AuthEmailHelper::sendPasswordResetOtp($db, $tenantId, $userEmail, $userName, $resetToken);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'A 6-digit verification code has been sent to your email!']);
    } else {
        throw new \Exception("Failed to dispatch password reset email.");
    }
} catch (\Throwable $e) {
    error_log("[PasswordReset Error] " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => "We couldn't send the reset email. " . (APP_ENV === 'development' ? $e->getMessage() : "Please try again later.")]);
}
?>

