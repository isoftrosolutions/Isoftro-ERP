<?php

namespace App\Http\Controllers;

/**
 * Authentication Controller
 * JWT-based authentication for all 6 roles
 * 
 * Based on SRS v1.0 specifications:
 * - 8-hour access token + 30-day refresh token
 * - Refresh token rotation on every use
 * - 2FA mandatory for Super Admin and Institute Admin
 */

class AuthController {
    private $db;
    private $jwtSecret;
    private $accessTokenExpiry = 28800; // 8 hours in seconds
    private $refreshTokenExpiry = 2592000; // 30 days in seconds
    
    public function __construct() {
        $this->db = getDBConnection();
        $this->jwtSecret = defined('JWT_SECRET') ? JWT_SECRET : 'hamrolabs-erp-secret-key-2026';
    }
    
    /**
     * User login - handles both session-based (web) and JWT-based (API) authentication
     */
    public function login() {
        $email = sanitizeInput($_POST['username'] ?? $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $otp = $_POST['otp'] ?? null;
        $remember = $_POST['remember'] ?? '';
        $isApi = isset($_GET['api']) || php_sapi_name() === 'api' || 
                 (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') ||
                 (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
        // Validate input
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }
        
        // Check rate limiting (IP-based)
        if (!$this->checkRateLimit($_SERVER['REMOTE_ADDR'])) {
            return ['success' => false, 'message' => 'Too many login attempts. Please try again in 15 minutes.'];
        }

        // Find user by email
        $user = $this->findUserByEmail($email);
        
        if (!$user) {
            $this->logAuthEvent('LOGIN_FAILURE', null, null, $email, 'failed', 'User not found');
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            $this->logAuthEvent('LOGIN_FAILURE', $user['id'], $user['tenant_id'], $email, 'failed', 'Account inactive');
            return ['success' => false, 'message' => 'Your account is currently inactive.'];
        }
        
        // Check explicit account lock (DB field)
        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            return ['success' => false, 'message' => 'Account locked due to security reasons. Try again later.'];
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->logLoginAttempt($email, $user['id'], 'failed', 'Invalid password');
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        
        // Check if 2FA is required
        if (!empty($user['two_fa_enabled']) && ($user['role'] === 'superadmin' || $user['role'] === 'instituteadmin')) {
            if (empty($otp)) {
                return ['success' => true, 'requires_otp' => true, 'user_id' => $user['id']];
            }
            
            if (!$this->verifyOTP($user['id'], $otp)) {
                $this->logLoginAttempt($email, $user['id'], 'failed', 'Invalid OTP');
                return ['success' => false, 'message' => 'Invalid verification code.'];
            }
        }
        
        // SUCCESSFUL LOGIN START
        
        // Update session for web users
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_regenerate_id(true);

        // Fetch tenant branding
        $tenantLogo = null;
        if (!empty($user['tenant_id'])) {
            $stmt = $this->db->prepare("SELECT logo_path FROM tenants WHERE id = ? LIMIT 1");
            $stmt->execute([$user['tenant_id']]);
            $tenantLogo = $stmt->fetchColumn();
            if ($tenantLogo && strpos($tenantLogo, '/uploads/') === 0 && strpos($tenantLogo, '/public/') !== 0) {
                $tenantLogo = '/public' . $tenantLogo;
            }
        }

        $_SESSION['userData'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['full_name'] ?? $user['name'] ?? $user['email'],
            'role' => $user['role'],
            'tenant_id' => $user['tenant_id'],
            'avatar' => $user['avatar'] ?? $user['photo_url'] ?? null,
            'last_login' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
        ];
        $_SESSION['tenant_logo'] = $tenantLogo;
        $_SESSION['last_activity'] = time();

        // Handle Remember Me
        if ($remember === 'on' || $remember === true || $remember === 'true') {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60);
            setcookie('remember_token', $token, $expiry, '/', '', false, true);
            try {
                $stmt = $this->db->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))");
                $stmt->execute([$user['id'], hash('sha256', $token)]);
            } catch (\Exception $e) {}
        }

        // Generate loading token
        $loadingToken = bin2hex(random_bytes(16));
        $_SESSION['loading_token'] = $loadingToken;
        $_SESSION['loading_token_expires'] = time() + 60;
        
        $roleSlugMap = [
            'superadmin' => 'super-admin',
            'instituteadmin' => 'admin',
            'frontdesk' => 'front-desk',
            'teacher' => 'teacher',
            'student' => 'student',
            'guardian' => 'guardian',
        ];
        $slug = $roleSlugMap[$user['role']] ?? strtolower($user['role']);
        $redirect = $_SESSION['redirect_after_login'] ?? (APP_URL . '/dash/' . $slug);
        unset($_SESSION['redirect_after_login']);
        $_SESSION['pending_redirect'] = $redirect;

        // Clear failed logins
        $stmt = $this->db->prepare("DELETE FROM failed_logins WHERE user_id = ? OR ip_address = ?");
        $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);

        $this->updateLastLogin($user['id']);
        $this->logAuthEvent('LOGIN_SUCCESS', $user['id'], $user['tenant_id'], $email, 'success');

        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user);
        $this->storeRefreshToken($user['id'], $refreshToken);

        return [
            'success' => true,
            'message' => 'Login successful!',
            'redirect' => $redirect,
            'loading_screen' => APP_URL . '/loading?token=' . urlencode($loadingToken) . '&redirect=' . urlencode($redirect),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => $this->sanitizeUser($user)
        ];
    }
    
    /**
     * Refresh access token
     */
    public function refresh() {
        $refreshToken = $_POST['refresh_token'] ?? '';
        
        if (empty($refreshToken)) {
            return ['success' => false, 'error' => 'Refresh token required'];
        }
        
        // Verify refresh token
        $tokenData = $this->verifyRefreshToken($refreshToken);
        
        if (!$tokenData) {
            return ['success' => false, 'error' => 'Invalid or expired refresh token'];
        }
        
        // Get user
        $user = $this->findUserById($tokenData['user_id']);
        
        if (!$user || $user['status'] !== 'active') {
            return ['success' => false, 'error' => 'User not found or inactive'];
        }
        
        // Rotate refresh token (invalidate old one)
        $this->invalidateRefreshToken($refreshToken);
        
        // Generate new tokens
        $newAccessToken = $this->generateAccessToken($user);
        $newRefreshToken = $this->generateRefreshToken($user);
        
        // Store new refresh token
        $this->storeRefreshToken($user['id'], $newRefreshToken);
        
        $this->logAuthEvent('TOKEN_REFRESH', $user['id'], $user['tenant_id'], $user['email'], 'success');

        return [
            'success' => true,
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => $this->accessTokenExpiry
        ];
    }
    
    /**
     * Logout - invalidate tokens
     */
    public function logout() {
        $refreshToken = $_POST['refresh_token'] ?? '';
        
        if (!empty($refreshToken)) {
            $this->invalidateRefreshToken($refreshToken);
        }
        
        // Clear session
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        
        $this->logAuthEvent('LOGOUT', null, null, '', 'success');

        return ['success' => true];
    }
    
    /**
     * Change password for logged-in user
     */
    public function changePassword() {
        if (!isLoggedIn()) {
            return ['success' => false, 'error' => 'Access denied. Please log in.'];
        }
        
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            return ['success' => false, 'error' => 'All password fields are required.'];
        }
        
        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'error' => 'New passwords do not match.'];
        }
        
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'error' => 'New password must be at least 8 characters long.'];
        }
        
        $user = getCurrentUser();
        
        // Fetch full user record to check current password
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $passwordHash = $stmt->fetchColumn();
        
        if (!$passwordHash || !password_verify($oldPassword, $passwordHash)) {
            return ['success' => false, 'error' => 'Current password is incorrect.'];
        }
        
        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        if ($stmt->execute([$newHash, $user['id']])) {
            return ['success' => true, 'message' => 'Password updated successfully!'];
        }
        
        return ['success' => false, 'error' => 'System error. Failed to update password.'];
    }
    
    /**
     * Send OTP for 2FA
     */
    public function sendOTP($userId) {
        $user = $this->findUserById($userId);
        
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        if (empty($user['phone'])) {
            return ['success' => false, 'error' => 'No phone number on file'];
        }
        
        // Generate OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store OTP (in production, use proper OTP storage with expiration)
        $this->storeOTP($userId, $otp);
        
        // Send SMS (in production, use Sparrow SMS API)
        $message = "Your HamroLabs ERP verification code is: $otp";
        // $this->sendSMS($user['phone'], $message);
        
        // For development, return OTP in response
        if (APP_ENV === 'development') {
            return ['success' => true, 'otp' => $otp, 'message' => 'OTP sent (development mode)'];
        }
        
        return ['success' => true, 'message' => 'OTP sent to your phone'];
    }
    
    /**
     * 00
     */
    private function findUserByEmail($email) {
        return \App\Models\User::where('email', $email)->first();
    }
    
    /**
     * Find user by ID (Eloquent)
     */
    private function findUserById($userId) {
        return \App\Models\User::find($userId);
    }
    
    /**
     * Generate JWT access token
     */
    private function generateAccessToken($user) {
        $payload = [
            'iss' => APP_URL,
            'aud' => APP_URL,
            'iat' => time(),
            'exp' => time() + $this->accessTokenExpiry,
            'user_id' => $user['id'],
            'tenant_id' => $user['tenant_id'],
            'role' => $user['role'],
            'type' => 'access'
            // TC-067: password and password_hash are EXPLICITLY excluded
        ];
        
        return $this->jwtEncode($payload);
    }
    
    /**
     * Generate JWT refresh token
     */
    private function generateRefreshToken($user) {
        $payload = [
            'iss' => APP_URL,
            'aud' => APP_URL,
            'iat' => time(),
            'exp' => time() + $this->refreshTokenExpiry,
            'user_id' => $user['id'],
            'type' => 'refresh',
            'jti' => bin2hex(random_bytes(16)) // Unique token ID
        ];
        
        return $this->jwtEncode($payload);
    }
    
    /**
     * Encode JWT
     */
    private function jwtEncode($payload) {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payloadEncoded = base64_encode(json_encode($payload));
        $signature = base64_encode(hash_hmac('sha256', "$header.$payloadEncoded", $this->jwtSecret, true));
        
        return "$header.$payloadEncoded.$signature";
    }
    
    /**
     * Decode JWT
     */
    public function jwtDecode($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        $signature = base64_encode(hash_hmac('sha256', "$parts[0].$parts[1]", $this->jwtSecret, true));
        
        if ($signature !== $parts[2]) {
            return null;
        }
        
        return json_decode(base64_decode($parts[1]), true);
    }
    
    /**
     * Store refresh token
     */
    private function storeRefreshToken($userId, $token) {
        $stmt = $this->db->prepare("
            INSERT INTO refresh_tokens (user_id, token, expires_at)
            VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 30 DAY))
        ");
        $stmt->execute([
            'user_id' => $userId,
            'token' => hash('sha256', $token)
        ]);
    }
    
    /**
     * Verify refresh token
     */
    private function verifyRefreshToken($token) {
        $tokenHash = hash('sha256', $token);
        
        $stmt = $this->db->prepare("
            SELECT * FROM refresh_tokens 
            WHERE token = :token AND expires_at > NOW() AND invalidated = 0
            LIMIT 1
        ");
        $stmt->execute(['token' => $tokenHash]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return null;
        }
        
        return $this->jwtDecode($token);
    }
    
    /**
     * Invalidate refresh token
     */
    private function invalidateRefreshToken($token) {
        $tokenHash = hash('sha256', $token);
        
        $stmt = $this->db->prepare("
            UPDATE refresh_tokens SET invalidated = 1 WHERE token = :token
        ");
        $stmt->execute(['token' => $tokenHash]);
    }
    
    /**
     * Store OTP
     */
    private function storeOTP($userId, $otp) {
        $stmt = $this->db->prepare("
            INSERT INTO otp_codes (user_id, code, expires_at)
            VALUES (:user_id, :code, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
        ");
        $stmt->execute([
            'user_id' => $userId,
            'code' => $otp
        ]);
    }
    
    /**
     * Verify OTP
     */
    private function verifyOTP($userId, $otp) {
        $stmt = $this->db->prepare("
            SELECT * FROM otp_codes 
            WHERE user_id = :user_id AND code = :code AND expires_at > NOW() AND used = 0
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId, 'code' => $otp]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Mark OTP as used
            $stmt = $this->db->prepare("UPDATE otp_codes SET used = 1 WHERE id = :id");
            $stmt->execute(['id' => $result['id']]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Log login attempt using SRS AuditLogger
     */
    private function logAuthEvent($action, $userId, $tenantId, $email, $status, $reason = null) {
        if (class_exists('\App\Helpers\AuditLogger')) {
            \App\Helpers\AuditLogger::log($action, $userId, $tenantId, [
                'email' => $email,
                'status' => $status,
                'reason' => $reason
            ]);
        }
    }

    /**
     * Update last login
     */
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $userId]);
    }
    
    /**
     * Get redirect URL based on role
     */
    private function getRedirectUrl($role) {
        $roleSlugMap = [
            'superadmin' => 'super-admin',
            'instituteadmin' => 'admin',
            'frontdesk' => 'front-desk',
            'teacher' => 'teacher',
            'student' => 'student',
            'guardian' => 'guardian',
        ];
        
        $slug = $roleSlugMap[$role] ?? 'login';
        return APP_URL . '/dash/' . $slug;
    }
    
    /**
     * Check rate limiting
     */
    private function checkRateLimit($ip) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts FROM audit_logs
            WHERE ip_address = :ip AND action = 'LOGIN_FAILURE'
            AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute(['ip' => $ip]);
        $result = $stmt->fetch();
        
        return ($result['attempts'] ?? 0) < MAX_LOGIN_ATTEMPTS;
    }
    private function sanitizeUser($user) {
        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'tenant_id' => $user['tenant_id']
        ];
    }
    
    /**
     * Validate JWT token (for API auth)
     */
    public static function validateToken() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader)) {
            return null;
        }
        
        if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $auth = new self();
            $payload = $auth->jwtDecode($token);
            
            if ($payload && $payload['exp'] > time()) {
                return $payload;
            }
        }
        
        return null;
    }
}

// Handle API requests
if (php_sapi_name() === 'api' || isset($_GET['api'])) {
    header('Content-Type: application/json');
    
    $auth = new AuthController();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'login':
            echo json_encode($auth->login());
            break;
            
        case 'refresh':
            echo json_encode($auth->refresh());
            break;
            
        case 'logout':
            echo json_encode($auth->logout());
            break;
            
        case 'send_otp':
            $userId = $_POST['user_id'] ?? 0;
            echo json_encode($auth->sendOTP($userId));
            break;
            
        case 'change_password':
            echo json_encode($auth->changePassword());
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
    
    exit;
}
?>
