<?php
/**
 * Hamro ERP — Authentication Functions
 * Platform Blueprint V3.0
 */

require_once 'config.php';

// User Authentication Functions
if (!function_exists('authenticateUser')) {
function authenticateUser($username, $password) {
    $db = getDBConnection();
    
    try {
        // Check if user exists (using email as the identifier as per migrations.sql)
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND status = 'active' LIMIT 1");
        $stmt->execute([':email' => $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Check if account is locked
        if (isAccountLocked($user['id'])) {
            return ['success' => false, 'message' => 'Account locked due to too many failed login attempts. Please try again later.'];
        }
        
        // Verify password (schema uses password_hash)
        if (!password_verify($password, $user['password_hash'])) {
            recordFailedLogin($user['id']);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Clear failed login attempts on successful login
        clearFailedLogins($user['id']);
        
        // Create user session data
        $userData = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['full_name'] ?? $user['email'], // Profile tables usually have full_name
            'role' => $user['role'],
            'avatar' => $user['avatar'],
            'last_login' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ];
        
        // Update last login (schema has last_login_at)
        $stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $user['id']]);
        
        return ['success' => true, 'user' => $userData];
        
    } catch (PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Authentication system error. Please try again later.'];
    }
}
}

// Failed Login Functions
if (!function_exists('recordFailedLogin')) {
function recordFailedLogin($userId) {
    $db = getDBConnection();
    
    try {
        $stmt = $db->prepare("INSERT INTO failed_logins (user_id, ip_address, attempted_at) VALUES (:user_id, :ip_address, NOW())");
        $stmt->execute([
            ':user_id' => $userId,
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        
        // Check if account should be locked
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM failed_logins WHERE user_id = :user_id AND attempted_at > DATE_SUB(NOW(), INTERVAL " . LOGIN_LOCKOUT_TIME . " SECOND)");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        
        if ($result['count'] >= MAX_LOGIN_ATTEMPTS) {
            lockAccount($userId);
        }
        
    } catch (PDOException $e) {
        error_log("Failed login recording error: " . $e->getMessage());
    }
}
}

if (!function_exists('clearFailedLogins')) {
function clearFailedLogins($userId) {
    $db = getDBConnection();
    
    try {
        $stmt = $db->prepare("DELETE FROM failed_logins WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
    } catch (PDOException $e) {
        error_log("Failed login clearing error: " . $e->getMessage());
    }
}
}

if (!function_exists('isAccountLocked')) {
function isAccountLocked($userId) {
    $db = getDBConnection();
    
    try {
        $stmt = $db->prepare("SELECT locked_until FROM users WHERE id = :id AND locked_until > NOW() LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $result = $stmt->fetch();
        
        return $result !== false;
    } catch (PDOException $e) {
        error_log("Account lock check error: " . $e->getMessage());
        return false;
    }
}
}

if (!function_exists('lockAccount')) {
function lockAccount($userId) {
    $db = getDBConnection();
    
    try {
        $stmt = $db->prepare("UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL " . (LOGIN_LOCKOUT_TIME / 60) . " MINUTE) WHERE id = :id");
        $stmt->execute([':id' => $userId]);
    } catch (PDOException $e) {
        error_log("Account locking error: " . $e->getMessage());
    }
}
}

// Session Management Functions
if (!function_exists('login')) {
function login($userData) {
    session_regenerate_id(true);
    $_SESSION['userData'] = $userData;
    $_SESSION['last_activity'] = time();
    
    // Set remember me cookie if requested
    if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Secure flag: true in production, allow false only on localhost/dev
        $isSecure = !(defined('APP_ENV') && APP_ENV === 'development');
        setcookie('remember_token', $token, $expiry, '/', '', $isSecure, true);
        
        // Store token in database
        $db = getDBConnection();
        $stmt = $db->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 30 DAY))");
        $stmt->execute([
            ':user_id' => $userData['id'],
            ':token' => hash('sha256', $token)
        ]);
    }
}
}

if (!function_exists('logout')) {
function logout() {
    // Clear remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        $isSecure = !(defined('APP_ENV') && APP_ENV === 'development');
        setcookie('remember_token', '', time() - 3600, '/', '', $isSecure, true);
        
        // Remove token from database
        $db = getDBConnection();
        $stmt = $db->prepare("DELETE FROM remember_tokens WHERE token = :token");
        $stmt->execute([':token' => hash('sha256', $_COOKIE['remember_token'])]);
    }
    
    // Destroy session
    session_destroy();
    
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}
}

if (!function_exists('checkRememberMe')) {
function checkRememberMe() {
    if (isset($_COOKIE['remember_token']) && !isLoggedIn()) {
        $token = $_COOKIE['remember_token'];
        $db = getDBConnection();
        
        try {
            $stmt = $db->prepare("SELECT user_id FROM remember_tokens WHERE token = :token AND expires_at > NOW() LIMIT 1");
            $stmt->execute([':token' => hash('sha256', $token)]);
            $result = $stmt->fetch();
            
            if ($result) {
                // Get user data
                $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND status = 'active' LIMIT 1");
                $stmt->execute([':id' => $result['user_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $userData = [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'name' => $user['full_name'] ?? $user['email'],
                        'role' => $user['role'],
                        'last_login' => $user['last_login_at'],
                        'ip_address' => $_SERVER['REMOTE_ADDR']
                    ];
                    
                    login($userData);
                    return true;
                }
            }
        } catch (PDOException $e) {
            error_log("Remember me check error: " . $e->getMessage());
        }
    }
    
    return false;
}
}

// Password Management Functions
if (!function_exists('hashPassword')) {
function hashPassword($password) {
    // FIX BUG 9: Must use PASSWORD_BCRYPT (cost 12) — NOT Argon2ID.
    // Laravel's Hash::check() (used by auth()->attempt()) relies on bcrypt.
    // Mixing Argon2ID and bcrypt causes intermittent auth failures where
    // login works via custom PDO path but fails via tymon/jwt-auth's attempt().
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}
}

if (!function_exists('verifyPassword')) {
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
}

if (!function_exists('generatePasswordResetToken')) {
function generatePasswordResetToken($userId) {
    $db = getDBConnection();
    $token = bin2hex(random_bytes(32));
    
    try {
        $stmt = $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
        $stmt->execute([
            ':user_id' => $userId,
            ':token' => hash('sha256', $token)
        ]);
        
        return $token;
    } catch (PDOException $e) {
        error_log("Password reset token generation error: " . $e->getMessage());
        return false;
    }
}
}

if (!function_exists('validatePasswordResetToken')) {
function validatePasswordResetToken($token) {
    $db = getDBConnection();
    
    try {
        $stmt = $db->prepare("SELECT user_id FROM password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1");
        $stmt->execute([':token' => hash('sha256', $token)]);
        $result = $stmt->fetch();
        
        return $result ? $result['user_id'] : false;
    } catch (PDOException $e) {
        error_log("Password reset token validation error: " . $e->getMessage());
        return false;
    }
}
}

if (!function_exists('clearPasswordResetToken')) {
function clearPasswordResetToken($token) {
    $db = getDBConnection();
    
    try {
        $stmt = $db->prepare("DELETE FROM password_resets WHERE token = :token");
        $stmt->execute([':token' => hash('sha256', $token)]);
    } catch (PDOException $e) {
        error_log("Password reset token clearing error: " . $e->getMessage());
    }
}
}

// User Management Functions
if (!function_exists('getUserById')) {
function getUserById($id) {
    $db = getDBConnection();
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get user by ID error: " . $e->getMessage());
        return false;
    }
}
}

if (!function_exists('getUserByUsername')) {
function getUserByUsername($email) {
    $db = getDBConnection();
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get user by email error: " . $e->getMessage());
        return false;
    }
}
}

if (!function_exists('createUser')) {
function createUser($userData) {
    $db = getDBConnection();
    
    try {
        $stmt = $db->prepare("INSERT INTO users (email, password_hash, role, status) VALUES (:email, :password, :role, :status)");
        
        $result = $stmt->execute([
            ':email' => $userData['email'],
            ':password' => hashPassword($userData['password']),
            ':role' => $userData['role'],
            ':status' => $userData['status'] ?? 'active'
        ]);
        
        return $result ? $db->lastInsertId() : false;
    } catch (PDOException $e) {
        error_log("Create user error: " . $e->getMessage());
        return false;
    }
}
}

if (!function_exists('updateUser')) {
function updateUser($id, $userData) {
    $db = getDBConnection();
    
    try {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($userData['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = $userData['email'];
        }
        
        if (isset($userData['role'])) {
            $fields[] = "role = :role";
            $params[':role'] = $userData['role'];
        }
        
        if (isset($userData['status'])) {
            $fields[] = "status = :status";
            $params[':status'] = $userData['status'];
        }
        
        if (isset($userData['password'])) {
            $fields[] = "password_hash = :password";
            $params[':password'] = hashPassword($userData['password']);
        }
        
        if (empty($fields)) {
            return true; // Nothing to update
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        return $stmt->execute($params);
        
    } catch (PDOException $e) {
        error_log("Update user error: " . $e->getMessage());
        return false;
    }
}
}

// Permission Functions
if (!function_exists('hasRole')) {
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    return $user['role'] === $role;
}
}

if (!function_exists('hasAnyRole')) {
function hasAnyRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    return in_array($user['role'], $roles);
}
}

if (!function_exists('requireRole')) {
function requireRole($role) {
    if (!hasRole($role)) {
        http_response_code(403);
        die('Access Denied: You must be a ' . $role . ' to view this page.');
    }
}
}

if (!function_exists('requireAnyRole')) {
function requireAnyRole($roles) {
    if (!hasAnyRole($roles)) {
        http_response_code(403);
        die('Access Denied: You must be one of these roles: ' . implode(', ', $roles) . ' to view this page.');
    }
}
}

// Session Security Functions
if (!function_exists('validateSession')) {
function validateSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        logout();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    // Check IP address (optional security measure)
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        logout();
        return false;
    }
    
    return true;
}
}

// Initialize authentication
// (session_start is already called in config.php)

// Check remember me cookie
checkRememberMe();

// Validate session
validateSession();
?>
