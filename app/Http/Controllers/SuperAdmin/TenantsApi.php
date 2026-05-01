<?php
/**
 * Super Admin Tenants API
 * Returns JSON data for tenants management
 */

if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Auth check
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
if (!$user || ($user['role'] ?? '') !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// CSRF check for POST/PUT/DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    try {
        \App\Helpers\CsrfHelper::requireCsrfToken();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'CSRF token mismatch.']);
        exit;
    }
}

$action = $_GET['action'] ?? 'list';

try {
    $db = getDBConnection();
    
    switch ($action) {
        case 'list':
            $status = $_GET['status'] ?? null;
            $search = $_GET['search'] ?? '';
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;
            
            $where = [];
            $params = [];
            
            if ($status && $status !== 'all') {
                $where[] = 't.status = :status';
                $params['status'] = $status;
            }
            
            if ($search) {
                $where[] = '(t.name LIKE :search OR t.subdomain LIKE :search2 OR t.phone LIKE :search3)';
                $params['search'] = "%$search%";
                $params['search2'] = "%$search%";
                $params['search3'] = "%$search%";
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Get total count
            $countStmt = $db->prepare("SELECT COUNT(*) FROM tenants t $whereClause");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get tenants
            $stmt = $db->prepare("
                SELECT t.*, 
                       s.plan as subscription_plan,
                       s.status as subscription_status,
                       s.end_date as subscription_end,
                       (SELECT COUNT(*) FROM users WHERE tenant_id = t.id) as user_count
                FROM tenants t
                LEFT JOIN subscriptions s ON t.id = s.tenant_id AND s.status = 'active'
                $whereClause
                ORDER BY t.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $tenants = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $tenants,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'get':
            $id = (int)$_GET['id'];
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Tenant ID required']);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT t.*, 
                       s.plan as subscription_plan,
                       s.status as subscription_status,
                       s.start_date as subscription_start,
                       s.end_date as subscription_end,
                       s.billing_cycle
                FROM tenants t
                LEFT JOIN subscriptions s ON t.id = s.tenant_id AND s.status = 'active'
                WHERE t.id = :id
            ");
            $stmt->execute(['id' => $id]);
            $tenant = $stmt->fetch();
            
            if (!$tenant) {
                echo json_encode(['success' => false, 'message' => 'Tenant not found']);
                exit;
            }
            
            // Get user count
            $userCount = $db->prepare("SELECT COUNT(*) FROM users WHERE tenant_id = ?");
            $userCount->execute([$id]);
            $tenant['user_count'] = $userCount->fetchColumn();
            
            // Get student count
            $studentCount = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = ?");
            $studentCount->execute([$id]);
            $tenant['student_count'] = $studentCount->fetchColumn();
            
            echo json_encode(['success' => true, 'data' => $tenant]);
            break;
            
        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['name']) || empty($input['subdomain'])) {
                echo json_encode(['success' => false, 'message' => 'Name and subdomain are required']);
                exit;
            }
            
            // Check subdomain uniqueness
            $check = $db->prepare("SELECT id FROM tenants WHERE subdomain = ?");
            $check->execute([$input['subdomain']]);
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Subdomain already exists']);
                exit;
            }
            
            $stmt = $db->prepare("
                INSERT INTO tenants (name, subdomain, institute_type, phone, address, province, plan, status, student_limit, sms_credits, trial_ends_at, created_at)
                VALUES (:name, :subdomain, :institute_type, :phone, :address, :province, :plan, :status, :student_limit, :sms_credits, :trial_ends_at, NOW())
            ");
            
            $stmt->execute([
                'name' => $input['name'],
                'subdomain' => $input['subdomain'],
                'institute_type' => $input['institute_type'] ?? null,
                'phone' => $input['phone'] ?? null,
                'address' => $input['address'] ?? null,
                'province' => $input['province'] ?? null,
                'plan' => $input['plan'] ?? 'starter',
                'status' => $input['status'] ?? 'trial',
                'student_limit' => $input['student_limit'] ?? 100,
                'sms_credits' => $input['sms_credits'] ?? 500,
                'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+60 days'))
            ]);
            
            $tenantId = $db->lastInsertId();

            // Auto-seed accounting: fiscal year + chart of accounts
            try {
                $fyStmt = $db->prepare("INSERT INTO acc_fiscal_years (tenant_id, name, start_date, end_date, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");
                $fyStmt->execute([$tenantId, 'FY 2082-83 (2025-26)', '2025-07-16', '2026-07-15']);

                $accInsert = $db->prepare("INSERT INTO acc_accounts (tenant_id, code, name, type, nature, parent_id, is_group, opening_balance, balance_type, is_system, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0.00, ?, 1, 'active', NOW(), NOW())");
                $accounts = [
                    ['1000','Cash & Cash Equivalents','asset','CASH',null,1,'dr'],
                    ['1010','Petty Cash','asset','CASH','1000',0,'dr'],
                    ['1011','Cash in Hand','asset','CASH','1000',0,'dr'],
                    ['1020','Bank Account','asset','BANK','1000',0,'dr'],
                    ['1100','Accounts Receivable','asset','AR',null,1,'dr'],
                    ['1101','Student Fees Receivable','asset','AR','1100',0,'dr'],
                    ['1200','Prepaid Expenses','asset','GENERAL',null,0,'dr'],
                    ['1300','Other Current Assets','asset','GENERAL',null,0,'dr'],
                    ['1500','Fixed Assets','asset','GENERAL',null,1,'dr'],
                    ['2000','Accounts Payable','liability','AP',null,1,'cr'],
                    ['2001','Vendor Payables','liability','AP','2000',0,'cr'],
                    ['2100','Accrued Expenses','liability','GENERAL',null,0,'cr'],
                    ['2200','Tax Payable','liability','GENERAL',null,1,'cr'],
                    ['2201','TDS Payable','liability','GENERAL','2200',0,'cr'],
                    ['2300','Salary Payable','liability','GENERAL',null,0,'cr'],
                    ['2400','Advance from Students','liability','GENERAL',null,0,'cr'],
                    ['3000',"Owner's Equity",'equity','GENERAL',null,0,'cr'],
                    ['3100','Retained Earnings','equity','GENERAL',null,0,'cr'],
                    ['4000','Income','income','GENERAL',null,1,'cr'],
                    ['4001','Tuition Fees','income','GENERAL','4000',0,'cr'],
                    ['4002','Admission Fees','income','GENERAL','4000',0,'cr'],
                    ['4003','Examination Fees','income','GENERAL','4000',0,'cr'],
                    ['4004','Registration Fees','income','GENERAL','4000',0,'cr'],
                    ['4005','Other Income','income','GENERAL','4000',0,'cr'],
                    ['5000','Expenses','expense','GENERAL',null,1,'dr'],
                    ['5001','Salary Expense','expense','GENERAL','5000',0,'dr'],
                    ['5002','Rent Expense','expense','GENERAL','5000',0,'dr'],
                    ['5003','Utilities Expense','expense','GENERAL','5000',0,'dr'],
                    ['5004','Office Supplies','expense','GENERAL','5000',0,'dr'],
                    ['5005','Marketing & Advertising','expense','GENERAL','5000',0,'dr'],
                    ['5006','Maintenance & Repairs','expense','GENERAL','5000',0,'dr'],
                    ['5007','Bank Charges','expense','GENERAL','5000',0,'dr'],
                    ['5008','Miscellaneous Expense','expense','GENERAL','5000',0,'dr'],
                ];
                $codeToId = [];
                foreach ($accounts as $a) {
                    if ($a[4] === null) {
                        $accInsert->execute([$tenantId, $a[0], $a[1], $a[2], $a[3], null, $a[5], $a[6]]);
                        $codeToId[$a[0]] = (int)$db->lastInsertId();
                    }
                }
                foreach ($accounts as $a) {
                    if ($a[4] !== null) {
                        $accInsert->execute([$tenantId, $a[0], $a[1], $a[2], $a[3], $codeToId[$a[4]] ?? null, $a[5], $a[6]]);
                        $codeToId[$a[0]] = (int)$db->lastInsertId();
                    }
                }
            } catch (\Throwable $ae) {
                // Non-fatal: tenant is created, accounting seed failed silently
            }

            echo json_encode([
                'success' => true,
                'message' => 'Tenant created successfully',
                'data' => ['id' => $tenantId]
            ]);
            break;

        case 'update':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)$input['id'];
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Tenant ID required']);
                exit;
            }
            
            $fields = [];
            $params = ['id' => $id];
            
            $allowedFields = ['name', 'phone', 'address', 'province', 'plan', 'status', 'student_limit', 'sms_credits', 'institute_type'];
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = :$field";
                    $params[$field] = $input[$field];
                }
            }
            
            if (empty($fields)) {
                echo json_encode(['success' => false, 'message' => 'No fields to update']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE tenants SET " . implode(', ', $fields) . " WHERE id = :id");
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Tenant updated successfully']);
            break;
            
        case 'delete':
            $id = (int)$_GET['id'];
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Tenant ID required']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM tenants WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Tenant deleted successfully']);
            break;
            
        case 'get-modules':
            $id = (int)$_GET['id'];
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Tenant ID required']);
                exit;
            }

            // Get all modules and their status for this tenant
            $stmt = $db->prepare("
                SELECT m.id, m.name, m.label, m.is_core, 
                       COALESCE(im.is_enabled, 0) as is_enabled
                FROM modules m
                LEFT JOIN institute_modules im ON m.id = im.module_id AND im.tenant_id = :tenant_id
                ORDER BY m.is_core DESC, m.label ASC
            ");
            $stmt->execute(['tenant_id' => $id]);
            $modules = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $modules]);
            break;

        case 'update-modules':
            $input = json_decode(file_get_contents('php://input'), true);
            $tenantId = (int)($input['tenant_id'] ?? 0);
            $enabledModules = $input['modules'] ?? []; // Array of module IDs

            if (!$tenantId) {
                echo json_encode(['success' => false, 'message' => 'Tenant ID required']);
                exit;
            }

            $db->beginTransaction();
            try {
                // 1. Reset all modules for this tenant (except core if we want to be safe, but UI should handle core)
                // Actually, just set is_enabled = 0 for all for this tenant
                $db->prepare("DELETE FROM institute_modules WHERE tenant_id = ?")->execute([$tenantId]);

                // 2. Insert enabled modules
                if (!empty($enabledModules)) {
                    $insertStmt = $db->prepare("INSERT INTO institute_modules (tenant_id, module_id, is_enabled) VALUES (?, ?, 1)");
                    foreach ($enabledModules as $moduleId) {
                        $insertStmt->execute([$tenantId, (int)$moduleId]);
                    }
                }

                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Modules updated successfully']);
                
                // Clear session cache for this tenant if they are currently logged in? 
                // Hard to do across all sessions, but next IdentifyTenant call will reload it.
            } catch (Exception $e) {
                error_log('Controller exception: ' . $e->getMessage());
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
            break;

        case 'stats':
            // Quick stats for tenant management
            $stats = [
                'total' => 0,
                'active' => 0,
                'trial' => 0,
                'suspended' => 0
            ];
            
            $stmt = $db->query("
                SELECT status, COUNT(*) as count 
                FROM tenants 
                GROUP BY status
            ");
            while ($row = $stmt->fetch()) {
                $stats[$row['status']] = (int)$row['count'];
                $stats['total'] += (int)$row['count'];
            }
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'suspend':
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Tenant ID required']); exit; }

            $tenantStmt = $db->prepare("SELECT name FROM tenants WHERE id = :id");
            $tenantStmt->execute(['id' => $id]);
            $tenant = $tenantStmt->fetch();
            if (!$tenant) { echo json_encode(['success' => false, 'message' => 'Tenant not found']); exit; }

            $db->prepare("UPDATE tenants SET status = 'suspended', updated_at = NOW() WHERE id = ?")->execute([$id]);
            $db->prepare("UPDATE users SET status = 'suspended' WHERE tenant_id = ? AND role != 'superadmin'")->execute([$id]);

            require_once app_path('Models/SuperAdmin/AuditLogModel.php');
            (new \App\Models\SuperAdmin\AuditLogModel($db))
                ->logAction(getCurrentUser()['id'], $id, 'tenant_suspend', ['name' => $tenant['name']]);

            echo json_encode(['success' => true, 'message' => htmlspecialchars($tenant['name']) . ' has been suspended.']);
            break;

        case 'activate':
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Tenant ID required']); exit; }

            $tenantStmt = $db->prepare("SELECT name FROM tenants WHERE id = :id");
            $tenantStmt->execute(['id' => $id]);
            $tenant = $tenantStmt->fetch();
            if (!$tenant) { echo json_encode(['success' => false, 'message' => 'Tenant not found']); exit; }

            $db->prepare("UPDATE tenants SET status = 'active', updated_at = NOW() WHERE id = ?")->execute([$id]);
            $db->prepare("UPDATE users SET status = 'active' WHERE tenant_id = ? AND role != 'superadmin'")->execute([$id]);

            require_once app_path('Models/SuperAdmin/AuditLogModel.php');
            (new \App\Models\SuperAdmin\AuditLogModel($db))
                ->logAction(getCurrentUser()['id'], $id, 'tenant_activate', ['name' => $tenant['name']]);

            echo json_encode(['success' => true, 'message' => htmlspecialchars($tenant['name']) . ' has been activated.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("[DB-ERROR] TenantsApi error: " . $e->getMessage());
    $msg = (defined('APP_ENV') && APP_ENV === 'development') ? $e->getMessage() : 'An internal error occurred. Please try again.';
    echo json_encode(['success' => false, 'message' => $msg]);
    }
