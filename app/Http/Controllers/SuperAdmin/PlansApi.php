<?php
/**
 * Super Admin Plans API
 * Returns JSON data for subscription plans management
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
    
    // Plan definitions
    $plans = [
        'starter' => [
            'id' => 'starter',
            'name' => 'Starter',
            'price' => 1500,
            'student_limit' => 150,
            'admin_accounts' => 1,
            'features' => ['sms' => 500, 'attendance' => true, 'fees' => true, 'exams' => false, 'lms' => false]
        ],
        'growth' => [
            'id' => 'growth',
            'name' => 'Growth',
            'price' => 3500,
            'student_limit' => 500,
            'admin_accounts' => 3,
            'features' => ['sms' => 2000, 'attendance' => true, 'fees' => true, 'exams' => true, 'lms' => true]
        ],
        'professional' => [
            'id' => 'professional',
            'name' => 'Professional',
            'price' => 12000,
            'student_limit' => 1500,
            'admin_accounts' => 10,
            'features' => ['sms' => 5000, 'attendance' => true, 'fees' => true, 'exams' => true, 'lms' => true, 'reports' => true]
        ],
        'enterprise' => [
            'id' => 'enterprise',
            'name' => 'Enterprise',
            'price' => 25000,
            'student_limit' => -1,
            'admin_accounts' => -1,
            'features' => ['sms' => 'unlimited', 'attendance' => true, 'fees' => true, 'exams' => true, 'lms' => true, 'reports' => true, 'api' => true]
        ]
    ];
    
    switch ($action) {
        case 'list':
            // Get tenant counts per plan
            $planCounts = [];
            $stmt = $db->query("SELECT plan, COUNT(*) as count FROM tenants WHERE status IN ('active', 'trial') GROUP BY plan");
            while ($row = $stmt->fetch()) {
                $planCounts[$row['plan']] = (int)$row['count'];
            }
            
            // Add counts to plans
            foreach ($plans as $key => &$plan) {
                $plan['tenant_count'] = $planCounts[$key] ?? 0;
            }
            
            echo json_encode(['success' => true, 'data' => array_values($plans)]);
            break;
            
        case 'get':
            $planId = $_GET['id'] ?? '';
            if (!isset($plans[$planId])) {
                echo json_encode(['success' => false, 'message' => 'Plan not found']);
                exit;
            }
            
            // Get tenant count
            $stmt = $db->prepare("SELECT COUNT(*) FROM tenants WHERE plan = ? AND status IN ('active', 'trial')");
            $stmt->execute([$planId]);
            $plans[$planId]['tenant_count'] = (int)$stmt->fetchColumn();
            
            // Get feature flags for this plan
            $featureStmt = $db->prepare("SELECT feature_key, is_enabled FROM plan_features WHERE plan_id = ?");
            $featureStmt->execute([$planId]);
            $features = [];
            while ($f = $featureStmt->fetch()) {
                $features[$f['feature_key']] = (bool)$f['is_enabled'];
            }
            $plans[$planId]['feature_flags'] = $features;
            
            echo json_encode(['success' => true, 'data' => $plans[$planId]]);
            break;
            
        case 'update_features':
            $input = json_decode(file_get_contents('php://input'), true);
            $planId = $input['plan_id'] ?? '';
            $features = $input['features'] ?? [];
            
            if (!isset($plans[$planId])) {
                echo json_encode(['success' => false, 'message' => 'Plan not found']);
                exit;
            }
            
            $db->beginTransaction();
            try {
                // Delete existing features
                $deleteStmt = $db->prepare("DELETE FROM plan_features WHERE plan_id = ?");
                $deleteStmt->execute([$planId]);
                
                // Insert new features
                $insertStmt = $db->prepare("INSERT INTO plan_features (plan_id, feature_key, is_enabled) VALUES (?, ?, ?)");
                foreach ($features as $key => $enabled) {
                    $insertStmt->execute([$planId, $key, $enabled ? 1 : 0]);
                }
                
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Features updated successfully']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        case 'assign':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $tenantId = $input['tenant_id'] ?? null;
            $planId = $input['plan_id'] ?? null;
            $billingCycle = $input['billing_cycle'] ?? 'monthly';
            
            if (!$tenantId || !$planId) {
                echo json_encode(['success' => false, 'message' => 'Tenant ID and Plan ID are required']);
                exit;
            }
            
            if (!isset($plans[$planId])) {
                echo json_encode(['success' => false, 'message' => 'Invalid plan']);
                exit;
            }
            
            // Update tenant plan
            $updateTenant = $db->prepare("UPDATE tenants SET plan = ? WHERE id = ?");
            $updateTenant->execute([$planId, $tenantId]);
            
            // Update or create subscription
            $checkSub = $db->prepare("SELECT id FROM subscriptions WHERE tenant_id = ? AND status = 'active'");
            $checkSub->execute([$tenantId]);
            $existingSub = $checkSub->fetch();
            
            if ($existingSub) {
                $updateSub = $db->prepare("
                    UPDATE subscriptions 
                    SET plan = ?, billing_cycle = ?, start_date = NOW(), end_date = DATE_ADD(NOW(), INTERVAL 1 MONTH)
                    WHERE id = ?
                ");
                $updateSub->execute([$planId, $billingCycle, $existingSub['id']]);
            } else {
                $insertSub = $db->prepare("
                    INSERT INTO subscriptions (tenant_id, plan, status, billing_cycle, start_date, end_date)
                    VALUES (?, ?, 'active', ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH))
                ");
                $insertSub->execute([$tenantId, $planId, $billingCycle]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Plan assigned successfully']);
            break;
            
        case 'stats':
            // Get plan statistics
            $stats = [
                'total_tenants' => 0,
                'mrr' => 0,
                'plans' => []
            ];
            
            $prices = ['starter' => 1500, 'growth' => 3500, 'professional' => 12000, 'enterprise' => 25000];
            
            $stmt = $db->query("SELECT plan, status, COUNT(*) as count FROM tenants GROUP BY plan, status");
            while ($row = $stmt->fetch()) {
                $plan = $row['plan'];
                if (!isset($stats['plans'][$plan])) {
                    $stats['plans'][$plan] = ['active' => 0, 'trial' => 0, 'total' => 0];
                }
                $stats['plans'][$plan][$row['status']] = (int)$row['count'];
                $stats['plans'][$plan]['total'] += (int)$row['count'];
                $stats['total_tenants'] += (int)$row['count'];
                
                if ($row['status'] === 'active') {
                    $stats['mrr'] += ($prices[$plan] ?? 0) * (int)$row['count'];
                }
            }
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
