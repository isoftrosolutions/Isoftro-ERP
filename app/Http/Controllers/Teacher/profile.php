<?php
/**
 * Teacher Profile API
 * Returns profile details and handles password updates
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
$role = $user['role'] ?? '';
$tenantId = $user['tenant_id'] ?? null;
$userId = $user['id'] ?? null;

// Only teachers should access this endpoint (unless admin testing)
if ($role !== 'teacher' && $role !== 'superadmin') {
    // Just a basic check, real permissions would use hasPermission()
}

$teacherId = $_SESSION['userData']['teacher_id'] ?? null;

try {
    $db = getDBConnection();
    
    // Fallback logic to get teacher ID
    if (!$teacherId && $userId) {
        $stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = :uid AND tenant_id = :tid LIMIT 1");
        $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $teacherId = $result['id'];
            $_SESSION['userData']['teacher_id'] = $teacherId;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword)) {
                echo json_encode(['success' => false, 'message' => 'Both passwords are required']);
                exit;
            }
            
            // Verify current password
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => $userId, 'tid' => $tenantId]);
            $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userRecord || !password_verify($currentPassword, $userRecord['password_hash'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid current password']);
                exit;
            }
            
            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id AND tenant_id = :tid");
            $updateStmt->execute(['hash' => $newHash, 'id' => $userId, 'tid' => $tenantId]);
            
            echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
            exit;
        }
    }

    // GET Request - Return Profile
    $stmt = $db->prepare("
        SELECT t.id, t.employee_id, t.full_name, t.phone, t.email, 
               t.qualification, t.specialization, t.joined_date, t.status,
               u.avatar
        FROM teachers t
        LEFT JOIN users u ON t.user_id = u.id
        WHERE t.id = :tid AND t.tenant_id = :tenant_id
        LIMIT 1
    ");
    $stmt->execute(['tid' => $teacherId, 'tenant_id' => $tenantId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        echo json_encode(['success' => false, 'message' => 'Teacher profile not found']);
        exit;
    }

    echo json_encode([
        'success' => true, 
        'data' => $profile
    ]);

} catch (PDOException $e) {
    error_log("Teacher Profile Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    } catch (Exception $e) {
    error_log("Teacher Profile Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
