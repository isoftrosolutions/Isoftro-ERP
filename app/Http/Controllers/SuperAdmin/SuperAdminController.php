<?php
/**
 * Super Admin Controller
 * Platform-wide management, billing, and support
 */

if (!defined('LARAVEL_START')) {
    require_once '../../config.php';
}

require_once app_path('Models/SuperAdmin/AuditLogModel.php');
require_once app_path('Models/SuperAdmin/TenantModel.php');

class SuperAdminController {
    private $db;
    
    public function __construct() {
        $this->db = getDBConnection();
    }
    
    /**
     * Dashboard - Platform overview
     */
    public function index() {
        $stats = $this->getPlatformStats();
        include '../../pages/super_admin/index.php';
    }
    
    /**
     * Get platform-wide statistics
     */
    private function getPlatformStats() {
        $stats = [
            'total_tenants' => 0,
            'active_tenants' => 0,
            'trial_tenants' => 0,
            'suspended_tenants' => 0,
            'total_students' => 0,
            'total_staff' => 0,
            'active_subscriptions' => 0,
            'mrr' => 0,
            'total_sms_sent' => 0,
            'sms_credits_remaining' => 0
        ];
        
        try {
            // Get tenant counts
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'trial' THEN 1 ELSE 0 END) as trial,
                    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended
                FROM tenants
            ");
            $result = $stmt->fetch();
            if ($result) {
                $stats['total_tenants'] = $result['total'];
                $stats['active_tenants'] = $result['active'];
                $stats['trial_tenants'] = $result['trial'];
                $stats['suspended_tenants'] = $result['suspended'];
            }
            
            // Get subscription stats
            $stmt = $this->db->query("
                SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total
                FROM subscriptions 
                WHERE status = 'active' AND billing_cycle = 'monthly'
            ");
            $result = $stmt->fetch();
            if ($result) {
                $stats['active_subscriptions'] = $result['count'];
                $stats['mrr'] = $result['total'];
            }
            
            // Get SMS credits
            $stmt = $this->db->query("SELECT COALESCE(SUM(sms_credits), 0) as total FROM tenants");
            $result = $stmt->fetch();
            if ($result) {
                $stats['sms_credits_remaining'] = $result['total'];
            }
            
        } catch (PDOException $e) {
            error_log("Error getting platform stats: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * List all tenants
     */
    public function tenants() {
        $tenants = $this->getAllTenants();
        include '../../pages/super_admin/tenant-management.php';
    }
    
    /**
     * Get all tenants with subscription info
     */
    private function getAllTenants() {
        try {
            $stmt = $this->db->query("
                SELECT t.*, 
                       s.plan as subscription_plan,
                       s.status as subscription_status,
                       s.end_date as subscription_end,
                       (SELECT COUNT(*) FROM users WHERE tenant_id = t.id) as user_count
                FROM tenants t
                LEFT JOIN subscriptions s ON t.id = s.tenant_id AND s.status = 'active'
                ORDER BY t.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("[DB-ERROR] Error getting tenants: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create new tenant
     */
    public function createTenant($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO tenants (name, subdomain, phone, address, province, plan, status, student_limit, sms_credits, trial_ends_at)
                VALUES (:name, :subdomain, :phone, :address, :province, :plan, :status, :student_limit, :sms_credits, :trial_ends_at)
            ");
            
            $stmt->execute([
                'name' => $data['name'],
                'subdomain' => $data['subdomain'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'province' => $data['province'] ?? null,
                'plan' => $data['plan'] ?? 'starter',
                'status' => $data['status'] ?? 'trial',
                'student_limit' => $data['student_limit'] ?? 100,
                'sms_credits' => $data['sms_credits'] ?? 500,
                'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+60 days'))
            ]);
            
            return ['success' => true, 'tenant_id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Manage subscription plans
     */
    public function plans() {
        $plans = $this->getPlans();
        include '../../pages/super_admin/plans.php';
    }
    
    /**
     * Get subscription plans
     */
    private function getPlans() {
        return [
            'starter' => [
                'name' => 'Starter',
                'price' => 1500,
                'student_limit' => 150,
                'admin_accounts' => 1,
                'features' => ['sms' => 500, 'attendance' => true, 'fees' => true, 'exams' => false, 'lms' => false]
            ],
            'growth' => [
                'name' => 'Growth',
                'price' => 3500,
                'student_limit' => 500,
                'admin_accounts' => 3,
                'features' => ['sms' => 2000, 'attendance' => true, 'fees' => true, 'exams' => true, 'lms' => true]
            ],
            'professional' => [
                'name' => 'Professional',
                'price' => 12000,
                'student_limit' => 1500,
                'admin_accounts' => 10,
                'features' => ['sms' => 5000, 'attendance' => true, 'fees' => true, 'exams' => true, 'lms' => true, 'reports' => true]
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price' => 25000,
                'student_limit' => -1, // unlimited
                'admin_accounts' => -1,
                'features' => ['sms' => 'unlimited', 'attendance' => true, 'fees' => true, 'exams' => true, 'lms' => true, 'reports' => true, 'api' => true]
            ]
        ];
    }
    
    /**
     * View support tickets
     */
    public function supportTickets() {
        $tickets = $this->getSupportTickets();
        include '../../pages/super_admin/support-tickets.php';
    }
    
    /**
     * Get support tickets
     */
    private function getSupportTickets() {
        $stmt = $this->db->query("
            SELECT st.*, t.name as tenant_name, u.name as user_name
            FROM support_tickets st
            LEFT JOIN tenants t ON st.tenant_id = t.id
            LEFT JOIN users u ON st.user_id = u.id
            ORDER BY st.created_at DESC
            LIMIT 50
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Revenue analytics
     */
    public function revenue() {
        $revenue = $this->getRevenueData();
        include '../../pages/super_admin/revenue.php';
    }
    
    /**
     * Get revenue data
     */
    private function getRevenueData() {
        $data = [
            'monthly' => [],
            'total' => 0,
            'growth' => 0
        ];
        
        try {
            // Get last 12 months revenue
            $stmt = $this->db->query("
                SELECT 
                    DATE_FORMAT(paid_at, '%Y-%m') as month,
                    SUM(amount) as revenue
                FROM payments
                WHERE status = 'completed' AND paid_at IS NOT NULL
                GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
                ORDER BY month DESC
                LIMIT 12
            ");
            $data['monthly'] = array_reverse($stmt->fetchAll());
            
            // Get total
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM payments
                WHERE status = 'completed'
            ");
            $result = $stmt->fetch();
            $data['total'] = $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error getting revenue data: " . $e->getMessage());
        }
        
        return $data;
    }
    
    /**
     * System logs
     */
    public function logs() {
        $logs = $this->getSystemLogs();
        include '../../pages/super_admin/logs.php';
    }
    
    /**
     * Get system logs
     */
    private function getSystemLogs($limit = 100) {
        try {
            $limit = intval($limit); // Sanitize
            $stmt = $this->db->prepare("
                SELECT al.*, u.email as user_email, t.name as tenant_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN tenants t ON al.tenant_id = t.id
                ORDER BY al.created_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("[DB-ERROR] Error getting system logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * SMS management
     */
    public function sms() {
        $templates = $this->getSmsTemplates();
        include '../../pages/super_admin/sms.php';
    }
    
    /**
     * Get SMS templates
     */
    private function getSmsTemplates() {
        try {
            $stmt = $this->db->query("SELECT * FROM sms_templates ORDER BY is_default DESC, name ASC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("[DB-ERROR] Error getting SMS templates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create announcement
     */
    public function createAnnouncement($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO announcements (title, message, target_audience, priority, starts_at, ends_at, created_by)
                VALUES (:title, :message, :audience, :priority, :starts_at, :ends_at, :created_by)
            ");
            
            $stmt->execute([
                'title' => $data['title'],
                'message' => $data['message'],
                'audience' => $data['target_audience'] ?? 'all',
                'priority' => $data['priority'] ?? 'normal',
                'starts_at' => $data['starts_at'] ?? date('Y-m-d H:i:s'),
                'ends_at' => $data['ends_at'] ?? null,
                'created_by' => $_SESSION['userData']['id'] ?? 1
            ]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Toggle maintenance mode
     */
    public function toggleMaintenance($enabled) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO platform_settings (setting_key, setting_value, setting_type)
                VALUES ('maintenance_mode', :value, 'boolean')
                ON DUPLICATE KEY UPDATE setting_value = :value2
            ");
            $value = $enabled ? '1' : '0';
            $stmt->execute(['value' => $value, 'value2' => $value]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Impersonate tenant admin
     */
    public function impersonate($tenantId) {
        try {
            // Check if user is superadmin
            $currentUser = getCurrentUser();
            if ($currentUser['role'] !== 'superadmin' && $currentUser['role'] !== 'super-admin') {
                return ['success' => false, 'message' => 'Unauthorized'];
            }

            // Find target admin user
            $stmt = $this->db->prepare("SELECT id FROM users WHERE tenant_id = ? AND role = 'instituteadmin' AND status = 'active' LIMIT 1");
            $stmt->execute([$tenantId]);
            $targetUserId = $stmt->fetchColumn();

            if (!$targetUserId) {
                return ['success' => false, 'message' => 'No active admin found for this institute.'];
            }

            // Generate token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            // Store token
            $stmt = $this->db->prepare("
                INSERT INTO impersonation_tokens (token, user_id, created_by, expires_at)
                VALUES (:token, :user_id, :created_by, :expires_at)
            ");
            $stmt->execute([
                'token' => $token,
                'user_id' => $targetUserId,
                'created_by' => $currentUser['id'],
                'expires_at' => $expiresAt
            ]);

            // Audit Log
            $audit = new \App\Models\SuperAdmin\AuditLogModel($this->db);
            $audit->logAction($currentUser['id'], $tenantId, 'impersonation_initiated', ['target_user_id' => $targetUserId]);

            return ['success' => true, 'token' => $token];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Complete impersonation login via token
     */
    public function impersonateLogin($token) {
        try {
            // Find and validate token
            $stmt = $this->db->prepare("
                SELECT it.*, u.email, u.name, u.role, u.tenant_id, u.avatar
                FROM impersonation_tokens it
                JOIN users u ON it.user_id = u.id
                WHERE it.token = :token AND it.expires_at > NOW()
                LIMIT 1
            ");
            $stmt->execute(['token' => $token]);
            $data = $stmt->fetch();

            if (!$data) {
                die("Invalid or expired impersonation token.");
            }

            // Delete token after use
            $stmt = $this->db->prepare("DELETE FROM impersonation_tokens WHERE id = ?");
            $stmt->execute([$data['id']]);

            // Setup session
            $_SESSION['original_userData'] = $_SESSION['userData'];
            $_SESSION['impersonating'] = true;
            
            $_SESSION['userData'] = [
                'id' => $data['user_id'],
                'email' => $data['email'],
                'name' => $data['name'] ?? $data['email'],
                'role' => $data['role'],
                'tenant_id' => $data['tenant_id'],
                'avatar' => $data['avatar'],
                'is_impersonated' => true
            ];

            // Load tenant features
            loadFeatures($data['tenant_id']);

            // Redirect to admin dash
            header("Location: " . APP_URL . "/dash/admin");
            exit;
        } catch (Exception $e) {
            die("Impersonation error: " . $e->getMessage());
        }
    }
    
    /**
     * End impersonation
     */
    public function endImpersonation() {
        if (isset($_SESSION['impersonation_log_id'])) {
            try {
                $stmt = $this->db->prepare("
                    UPDATE impersonation_logs 
                    SET ended_at = NOW() 
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $_SESSION['impersonation_log_id']]);
            } catch (PDOException $e) {
                error_log("Error ending impersonation: " . $e->getMessage());
            }
        }
        
        // Restore original session if it exists
        if (isset($_SESSION['original_userData'])) {
            $_SESSION['userData'] = $_SESSION['original_userData'];
            unset($_SESSION['original_userData']);
        }
        
        unset($_SESSION['impersonating']);
        unset($_SESSION['impersonation_log_id']);
        
        return ['success' => true];
    }
}

// Handle requests only when accessed directly
if (isset($_GET['action']) && !defined('LARAVEL_START')) {
    $controller = new SuperAdminController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'tenants':
            $controller->tenants();
            break;
        case 'plans':
            $controller->plans();
            break;
        case 'support':
            $controller->supportTickets();
            break;
        case 'revenue':
            $controller->revenue();
            break;
        case 'logs':
            $controller->logs();
            break;
        case 'sms':
            $controller->sms();
            break;
        default:
            $controller->index();
    }
} elseif (!defined('LARAVEL_START')) {
    $controller = new SuperAdminController();
    $controller->index();
}
