<?php
/**
 * Automation Rules API Controller
 * Handles CRUD for notification automation rules.
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$tenantId = $user['tenant_id'] ?? null;
$role = $user['role'] ?? '';

if (!$tenantId || !in_array($role, ['instituteadmin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$ruleModel = new \App\Models\NotificationAutomationRule();

try {
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $rule = $ruleModel->find($id);
            if ($rule && $rule['tenant_id'] == $tenantId) {
                echo json_encode(['success' => true, 'data' => $rule]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Rule not found']);
            }
        } else {
            $rules = $ruleModel->getActiveByTenant($tenantId);
            echo json_encode(['success' => true, 'data' => $rules]);
        }
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $action = $input['action'] ?? 'save';

        if ($action === 'save') {
            $id = $input['id'] ?? null;
            $data = [
                'tenant_id' => $tenantId,
                'name' => $input['name'] ?? 'New Rule',
                'trigger_type' => $input['trigger_type'] ?? 'absent',
                'conditions' => $input['conditions'] ?? [],
                'message_template' => $input['message_template'] ?? '',
                'is_active' => isset($input['is_active']) ? ($input['is_active'] ? 1 : 0) : 1
            ];

            if ($id) {
                $rule = $ruleModel->update($id, $data);
                echo json_encode(['success' => true, 'message' => 'Rule updated', 'data' => $rule]);
            } else {
                $rule = $ruleModel->create($data);
                echo json_encode(['success' => true, 'message' => 'Rule created', 'data' => $rule]);
            }
        } 
        elseif ($action === 'delete') {
            $id = $input['id'] ?? null;
            if ($id) {
                $ruleModel->delete($id, $tenantId);
                echo json_encode(['success' => true, 'message' => 'Rule deleted']);
            }
        }
    }
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
