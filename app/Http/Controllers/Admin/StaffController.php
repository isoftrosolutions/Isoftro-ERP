<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\AdminEmailHelper;
use App\Helpers\MailHelper;
use Exception;
use PDO;

class StaffController
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = getDBConnection();
        $this->tenantId = $_SESSION['userData']['tenant_id'] ?? null;
        
        if (!$this->tenantId) {
            throw new Exception("Tenant ID missing");
        }
    }

    /**
     * Handle incoming request
     */
    public function handle()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($method) {
                case 'GET':
                    return $this->index();
                case 'POST':
                    return $this->store();
                case 'PUT':
                case 'PATCH':
                    return $this->update();
                case 'DELETE':
                    return $this->destroy();
                default:
                    throw new Exception("Method not allowed");
            }
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * List staff members
     */
    public function index()
    {
        $type = $_GET['role'] ?? 'teacher';

        if ($type === 'teacher') {
            $stmt = $this->db->prepare("
                SELECT u.id as user_id, u.email, u.phone, u.status, u.monthly_salary, u.name, t.* 
                FROM users u 
                JOIN teachers t ON u.id = t.user_id 
                WHERE u.tenant_id = :tid AND u.role = 'teacher' AND u.deleted_at IS NULL
            ");
            $stmt->execute(['tid' => $this->tenantId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else if ($type === 'frontdesk') {
            $stmt = $this->db->prepare("
                SELECT id as user_id, email, name, phone, status, monthly_salary, created_at 
                FROM users 
                WHERE tenant_id = :tid AND role = 'frontdesk' AND deleted_at IS NULL
            ");
            $stmt->execute(['tid' => $this->tenantId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            throw new Exception("Invalid role specified");
        }

        return ['success' => true, 'data' => $data];
    }

    /**
     * Create new staff member
     */
    public function store()
    {
        $input = $this->getInput();
        $role = $input['role'] ?? 'teacher';
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $phone = $input['phone'] ?? '';
        $password = $input['password'] ?? 'Hamro@123';

        if (empty($name) || empty($email)) {
            throw new Exception("Name and Email are required");
        }

        // Check if email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['email' => $email, 'tid' => $this->tenantId]);
        if ($stmt->fetch()) {
            throw new Exception("A user with this email already exists in this institute");
        }

        $this->db->beginTransaction();

        // 1. Create User
        $stmt = $this->db->prepare("
            INSERT INTO users (tenant_id, role, email, password_hash, phone, name, status, monthly_salary) 
            VALUES (:tid, :role, :email, :pass, :phone, :name, 'active', :salary)
        ");
        $stmt->execute([
            'tid' => $this->tenantId,
            'role' => $role,
            'email' => $email,
            'pass' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'phone' => $phone,
            'name' => $name,
            'salary' => $input['monthly_salary'] ?? 0
        ]);
        $userId = $this->db->lastInsertId();

        // 2. If Teacher, create teacher profile
        if ($role === 'teacher') {
            $empId = $input['employee_id'] ?? 'TCH-' . str_pad($userId, 3, '0', STR_PAD_LEFT);
            $stmt = $this->db->prepare("
                INSERT INTO teachers (tenant_id, user_id, employee_id, full_name, phone, email, specialization, qualification, joined_date, status) 
                VALUES (:tid, :uid, :eid, :name, :phone, :email, :spec, :qual, :jdate, 'active')
            ");
            $stmt->execute([
                'tid' => $this->tenantId,
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

        $this->db->commit();

        // Queue welcome email
        try {
            $roleLabel = $role === 'teacher' ? 'Teacher' : 'Front Desk Staff';
            AdminEmailHelper::sendStaffWelcome($this->db, (int)$this->tenantId, [
                'staff_email' => $email,
                'staff_name' => $name,
                'role_label' => $roleLabel,
                'temp_password' => $password,
                'login_url' => (defined('APP_URL') ? APP_URL : 'http://localhost/erp') . '/login'
            ]);
        } catch (\Throwable $mailErr) {
            error_log("[MailHelper] Staff credential email failed for {$email}: " . $mailErr->getMessage());
        }

        return [
            'success' => true,
            'message' => ucfirst($role) . ' added successfully',
            'userId' => $userId,
            'email' => $email,
            'name' => $name
        ];
    }

    /**
     * Update staff member
     */
    public function update()
    {
        $input = $this->getInput();
        $userId = $input['user_id'] ?? null;
        $role = $input['role'] ?? 'teacher';

        if (!$userId) {
            throw new Exception("User ID is required");
        }

        // Verify user belongs to this tenant
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :uid AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['uid' => $userId, 'tid' => $this->tenantId]);
        if (!$stmt->fetch()) {
            throw new Exception("User not found or access denied");
        }

        $this->db->beginTransaction();

        // Update users table
        $updateFields = [];
        $params = ['uid' => $userId, 'tid' => $this->tenantId];

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
            $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :uid AND tenant_id = :tid");
            $stmt->execute($params);
        }

        // Update teachers table
        if ($role === 'teacher') {
            $teacherFields = [];
            $teacherParams = ['uid' => $userId, 'tid' => $this->tenantId];

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
                $stmt = $this->db->prepare("UPDATE teachers SET " . implode(', ', $teacherFields) . " WHERE user_id = :uid AND tenant_id = :tid");
                $stmt->execute($teacherParams);
            }
        }

        $this->db->commit();
        return ['success' => true, 'message' => ucfirst($role) . ' updated successfully'];
    }

    /**
     * Delete staff member
     */
    public function destroy()
    {
        $input = $this->getInput();
        if (empty($input)) {
            $input = $_GET;
        }

        $userId = $input['user_id'] ?? null;

        if (!$userId) {
            throw new Exception("User ID is required");
        }

        // Verify user belongs to this tenant
        $stmt = $this->db->prepare("SELECT id, role FROM users WHERE id = :uid AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['uid' => $userId, 'tid' => $this->tenantId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("User not found or access denied");
        }

        // Soft delete
        $stmt = $this->db->prepare("UPDATE users SET deleted_at = NOW() WHERE id = :uid AND tenant_id = :tid");
        $stmt->execute(['uid' => $userId, 'tid' => $this->tenantId]);

        if ($user['role'] === 'teacher') {
            $stmt = $this->db->prepare("UPDATE teachers SET deleted_at = NOW() WHERE user_id = :uid AND tenant_id = :tid");
            $stmt->execute(['uid' => $userId, 'tid' => $this->tenantId]);
        }

        return ['success' => true, 'message' => 'User deleted successfully'];
    }

    /**
     * Helper to get JSON input
     */
    private function getInput()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        return $input ?: $_POST;
    }
}
