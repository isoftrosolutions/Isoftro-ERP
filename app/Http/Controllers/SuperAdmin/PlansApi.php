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
    
    switch ($action) {
        case 'list':
            $stmt = $db->query("
                SELECT p.*, 
                (SELECT COUNT(*) FROM tenants WHERE plan = p.slug AND status IN ('active', 'trial')) as tenant_count
                FROM subscription_plans p 
                ORDER BY p.sort_order ASC
            ");
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $plans]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? 0;
            $stmt = $db->prepare("SELECT * FROM subscription_plans WHERE id = ?");
            $stmt->execute([$id]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plan) {
                echo json_encode(['success' => false, 'message' => 'Plan not found']);
                exit;
            }
            
            // Get features (display features)
            $fStmt = $db->prepare("SELECT * FROM plan_features WHERE plan_id = ? ORDER BY sort_order ASC");
            $fStmt->execute([$id]);
            $plan['features'] = $fStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $plan]);
            break;

        case 'save':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;
            $name = $input['name'] ?? '';
            $slug = $input['slug'] ?? strtolower(str_replace(' ', '-', $name));
            $price = $input['price_monthly'] ?? 0;
            $student_limit = $input['student_limit'] ?? 0;
            $description = $input['description'] ?? '';
            $badge_text = $input['badge_text'] ?? null;
            $is_featured = isset($input['is_featured']) ? ($input['is_featured'] ? 1 : 0) : 0;
            $status = $input['status'] ?? 'active';
            $sort_order = $input['sort_order'] ?? 0;

            if (!$name) {
                echo json_encode(['success' => false, 'message' => 'Plan name is required']);
                exit;
            }

            if ($id > 0) {
                // Update
                $stmt = $db->prepare("
                    UPDATE subscription_plans 
                    SET name = ?, slug = ?, price_monthly = ?, student_limit = ?, 
                        description = ?, badge_text = ?, is_featured = ?, status = ?, sort_order = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $slug, $price, $student_limit, $description, $badge_text, $is_featured, $status, $sort_order, $id]);
                $message = 'Plan updated successfully';
            } else {
                // Create
                $stmt = $db->prepare("
                    INSERT INTO subscription_plans (name, slug, price_monthly, student_limit, description, badge_text, is_featured, status, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $slug, $price, $student_limit, $description, $badge_text, $is_featured, $status, $sort_order]);
                $id = $db->lastInsertId();
                $message = 'Plan created successfully';
            }

            echo json_encode(['success' => true, 'message' => $message, 'id' => $id]);
            break;

        case 'delete':
            $id = $_GET['id'] ?? 0;
            
            // Check if plan is in use
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM tenants WHERE plan = (SELECT slug FROM subscription_plans WHERE id = ?)");
            $checkStmt->execute([$id]);
            if ($checkStmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete plan as it is currently assigned to tenants.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM subscription_plans WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Plan deleted successfully']);
            break;
            
        case 'update_display_features':
            $input = json_decode(file_get_contents('php://input'), true);
            $planId = $input['plan_id'] ?? 0;
            $features = $input['features'] ?? []; // Array of {text: '', is_included: bool}
            
            if (!$planId) {
                echo json_encode(['success' => false, 'message' => 'Plan ID is required']);
                exit;
            }
            
            $db->beginTransaction();
            try {
                $db->prepare("DELETE FROM plan_features WHERE plan_id = ?")->execute([$planId]);
                $insert = $db->prepare("INSERT INTO plan_features (plan_id, feature_text, is_included, sort_order) VALUES (?, ?, ?, ?)");
                foreach ($features as $index => $f) {
                    $insert->execute([$planId, $f['text'] ?? 'Feature', (isset($f['is_included']) && $f['is_included']) ? 1 : 0, $index]);
                }
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Display features updated successfully']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'get_system_features':
            $planId = $_GET['id'] ?? 0;
            if (!$planId) {
                echo json_encode(['success' => false, 'message' => 'Plan ID is required']);
                exit;
            }

            // Get all system features
            $allStmt = $db->query("SELECT * FROM system_features ORDER BY feature_name ASC");
            $all = $allStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get currently enabled for this plan
            $enabledStmt = $db->prepare("SELECT feature_id FROM plan_system_features WHERE plan_id = ?");
            $enabledStmt->execute([$planId]);
            $enabled = $enabledStmt->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode(['success' => true, 'all' => $all, 'enabled' => $enabled]);
            break;

        case 'update_system_features':
            $input = json_decode(file_get_contents('php://input'), true);
            $planId = $input['plan_id'] ?? 0;
            $featureIds = $input['feature_ids'] ?? []; // Array of IDs
            
            if (!$planId) {
                echo json_encode(['success' => false, 'message' => 'Plan ID is required']);
                exit;
            }
            
            $db->beginTransaction();
            try {
                $db->prepare("DELETE FROM plan_system_features WHERE plan_id = ?")->execute([$planId]);
                $insert = $db->prepare("INSERT INTO plan_system_features (plan_id, feature_id) VALUES (?, ?)");
                foreach ($featureIds as $fId) {
                    $insert->execute([$planId, $fId]);
                }
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'System features updated successfully']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        case 'toggle_system_feature':
            $input = json_decode(file_get_contents('php://input'), true);
            $featureKey = $input['feature_key'] ?? '';
            $status = $input['status'] ?? 'active';
            
            if (!$featureKey) {
                echo json_encode(['success' => false, 'message' => 'Feature key is required']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE system_features SET status = ? WHERE feature_key = ?");
            $stmt->execute([$status, $featureKey]);
            echo json_encode(['success' => true, 'message' => 'Global feature status updated']);
            break;

        case 'assign':
            $input = json_decode(file_get_contents('php://input'), true);
            $tenantId = $input['tenant_id'] ?? null;
            $planSlug = $input['plan_slug'] ?? null; // Using slug now
            
            if (!$tenantId || !$planSlug) {
                echo json_encode(['success' => false, 'message' => 'Tenant ID and Plan Slug are required']);
                exit;
            }
            
            // Update tenant plan
            $updateTenant = $db->prepare("UPDATE tenants SET plan = ? WHERE id = ?");
            $updateTenant->execute([$planSlug, $tenantId]);
            
            echo json_encode(['success' => true, 'message' => 'Plan assigned successfully']);
            break;
            
        case 'stats':
            $stmt = $db->query("
                SELECT p.name, p.slug, COUNT(t.id) as count 
                FROM subscription_plans p 
                LEFT JOIN tenants t ON t.plan = p.slug AND t.status = 'active'
                GROUP BY p.id
            ");
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("[DB-ERROR] PlansApi error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
