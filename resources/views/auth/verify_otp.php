<?php
require_once __DIR__ . '/../../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$otp = $_POST['otp'] ?? '';

if (empty($email) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Email and OTP are required.']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Validate OTP
    $stmt = $db->prepare("SELECT id FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$email, $otp]);
    $resetData = $stmt->fetch();
    
    if (!$resetData) {
        echo json_encode(['success' => false, 'message' => 'The verification code is invalid or has expired.']);
        exit;
    }

    // Generate a long secure reset token
    $resetToken = bin2hex(random_bytes(32));
    
    // Update the record with the reset token and extend expiry slightly for the next step
    $updateStmt = $db->prepare("UPDATE password_resets SET token = ?, expires_at = DATE_ADD(NOW(), INTERVAL 20 MINUTE) WHERE email = ? AND token = ?");
    $updateStmt->execute([$resetToken, $email, $otp]);

    echo json_encode([
        'success' => true, 
        'message' => 'Verification successful!',
        'reset_token' => $resetToken
    ]);
} catch (\Throwable $e) {
    error_log("[VerifyOTP Error] " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => "An error occurred during verification. " . (APP_ENV === 'development' ? $e->getMessage() : "")]);
}
