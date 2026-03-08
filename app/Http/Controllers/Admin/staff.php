<?php
/**
 * Staff API Controller
 * Handles Front Desk and Teacher management for the Institute Admin
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}
// Load MailHelper for credential emails
if (!class_exists('App\\Helpers\\MailHelper')) {
    require_once __DIR__ . '/../../../Helpers/MailHelper.php';
}
use App\Helpers\MailHelper;


header('Content-Type: application/json');
ob_start();

// Ensure user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
try {
    $db = getDBConnection();

    if ($method === 'GET') {
        $type = $_GET['role'] ?? 'teacher'; // default to teacher
        
        if ($type === 'teacher') {
            $stmt = $db->prepare("
                SELECT u.id as user_id, u.email, u.phone, u.status, u.monthly_salary, t.* 
                FROM users u 
                JOIN teachers t ON u.id = t.user_id 
                WHERE u.tenant_id = :tid AND u.role = 'teacher' AND u.deleted_at IS NULL
            ");
            $stmt->execute(['tid' => $tenantId]);
            $data = $stmt->fetchAll();
        } else if ($type === 'frontdesk') {
            $stmt = $db->prepare("
                SELECT id as user_id, email, name, phone, status, monthly_salary, created_at 
                FROM users 
                WHERE tenant_id = :tid AND role = 'frontdesk' AND deleted_at IS NULL
            ");
            $stmt->execute(['tid' => $tenantId]);
            $data = $stmt->fetchAll();
        } else {
            throw new Exception("Invalid role specified");
        }

        echo json_encode(['success' => true, 'data' => $data]);

    } else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            // Handle form data if not JSON
            $input = $_POST;
        }

        $role = $input['role'] ?? 'teacher';
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $phone = $input['phone'] ?? '';
        $password = $input['password'] ?? 'Hamro@123'; // Default password

        if (empty($name) || empty($email)) {
            throw new Exception("Name and Email are required");
        }

        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['email' => $email, 'tid' => $tenantId]);
        if ($stmt->fetch()) {
            throw new Exception("A user with this email already exists in this institute");
        }

        $db->beginTransaction();

        // 1. Create User
        $stmt = $db->prepare("
            INSERT INTO users (tenant_id, role, email, password_hash, phone, name, status, monthly_salary) 
            VALUES (:tid, :role, :email, :pass, :phone, :name, 'active', :salary)
        ");
        $stmt->execute([
            'tid' => $tenantId,
            'role' => $role,
            'email' => $email,
            'pass' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'phone' => $phone,
            'name' => $name,
            'salary' => $input['monthly_salary'] ?? 0
        ]);
        $userId = $db->lastInsertId();

        // 2. If Teacher, create teacher profile
        if ($role === 'teacher') {
            $empId = $input['employee_id'] ?? 'TCH-' . str_pad($userId, 3, '0', STR_PAD_LEFT);
            $stmt = $db->prepare("
                INSERT INTO teachers (tenant_id, user_id, employee_id, full_name, phone, email, specialization, qualification, joined_date, status) 
                VALUES (:tid, :uid, :eid, :name, :phone, :email, :spec, :qual, :jdate, 'active')
            ");
            $stmt->execute([
                'tid' => $tenantId,
                'uid' => $userId,
                'eid' => $empId,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'spec' => $input['specialization'] ?? null,
                'qual' => $input['qualification'] ?? null,
                'jdate' => $input['join_date'] ?? date('Y-m-d')
            ]);
        }

        $db->commit();

        // Fire-and-forget: queue login credentials email to the new staff member
        try {
            $roleLabel = $role === 'teacher' ? 'Teacher' : 'Front Desk Staff';
            \App\Helpers\AdminEmailHelper::sendAnnouncement($db, (int)$tenantId, [
                'email' => $email,
                'name' => $name,
                'subject' => "Your {$roleLabel} Account at " . ($_SESSION['userData']['institute_name'] ?? 'the Institute'),
                'body' => MailHelper::buildStaffWelcomeHtml(
                    $name,
                    $roleLabel,
                    $email,
                    $password,
                    (defined('APP_URL') ? APP_URL : 'http://localhost/erp') . '/login'
                )
            ]);
        } catch (\Throwable $mailErr) {
            error_log("[MailHelper] Staff credential email failed for {$email}: " . $mailErr->getMessage());
        }


        echo json_encode(['success' => true, 'message' => ucfirst($role) . ' added successfully', 'userId' => $userId, 'email' => $email, 'name' => $name]);

    }

    // UPDATE (PUT/PATCH)
    elseif ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $userId = $input['user_id'] ?? null;
        $role = $input['role'] ?? 'teacher';
        
        if (!$userId) {
            throw new Exception("User ID is required");
        }

        // Verify user belongs to this tenant
        $stmt = $db->prepare("SELECT id FROM users WHERE id = :uid AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        if (!$stmt->fetch()) {
            throw new Exception("User not found or access denied");
        }

        $db->beginTransaction();

        // Update users table
        $updateFields = [];
        $params = ['uid' => $userId, 'tid' => $tenantId];
        
        if (!empty($input['name'])) {
            $updateFields[] = 'name = :name';
            $params['name'] = $input['name'];
        }
        if (!empty($input['phone'])) {
            $updateFields[] = 'phone = :phone';
            $params['phone'] = $input['phone'];
        }
        if (!empty($input['status'])) {
            $updateFields[] = 'status = :status';
            $params['status'] = $input['status'];
        }
        if (isset($input['monthly_salary'])) {
            $updateFields[] = 'monthly_salary = :salary';
            $params['salary'] = $input['monthly_salary'];
        }
        if (!empty($input['password'])) {
            $updateFields[] = 'password_hash = :password';
            $params['password'] = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if (!empty($updateFields)) {
            $stmt = $db->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :uid AND tenant_id = :tid");
            $stmt->execute($params);
        }

        // Update teachers table
        if ($role === 'teacher') {
            $teacherFields = [];
            $teacherParams = ['uid' => $userId, 'tid' => $tenantId];
            
            if (!empty($input['name'])) {
                $teacherFields[] = 'full_name = :full_name';
                $teacherParams['full_name'] = $input['name'];
            }
            if (!empty($input['phone'])) {
                $teacherFields[] = 'phone = :phone';
                $teacherParams['phone'] = $input['phone'];
            }
            if (isset($input['specialization'])) {
                $teacherFields[] = 'specialization = :specialization';
                $teacherParams['specialization'] = $input['specialization'];
            }
            if (isset($input['qualification'])) {
                $teacherFields[] = 'qualification = :qualification';
                $teacherParams['qualification'] = $input['qualification'];
            }
            if (isset($input['status'])) {
                $teacherFields[] = 'status = :teacher_status';
                $teacherParams['teacher_status'] = $input['status'];
            }

            if (!empty($teacherFields)) {
                $stmt = $db->prepare("UPDATE teachers SET " . implode(', ', $teacherFields) . " WHERE user_id = :uid AND tenant_id = :tid");
                $stmt->execute($teacherParams);
            }
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => ucfirst($role) . ' updated successfully']);
    }

    // DELETE
    elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_GET;
        }

        $userId = $input['user_id'] ?? null;
        
        if (!$userId) {
            throw new Exception("User ID is required");
        }

        // Verify user belongs to this tenant
        $stmt = $db->prepare("SELECT id, role FROM users WHERE id = :uid AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("User not found or access denied");
        }

        // Soft delete - set deleted_at timestamp
        $stmt = $db->prepare("UPDATE users SET deleted_at = NOW() WHERE id = :uid AND tenant_id = :tid");
        $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);

        // Also soft delete from teachers table if teacher
        if ($user['role'] === 'teacher') {
            $stmt = $db->prepare("UPDATE teachers SET deleted_at = NOW() WHERE user_id = :uid AND tenant_id = :tid");
            $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        }

        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Output buffered JSON and exit
ob_end_flush();
exit;
