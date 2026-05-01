<?php
/**
 * Audit Logs API Controller
 * Handles all audit log operations for Institute Admin
 * 
 * Provides endpoints for viewing, filtering, and exporting audit logs
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$tenantId = $user['tenant_id'] ?? null;
$userId = $user['id'] ?? null;
$role = $user['role'] ?? '';

if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

// Allow instituteadmin and superadmin roles (view audit logs)
$allowedRoles = ['instituteadmin', 'superadmin'];
if (!in_array($role, $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Insufficient permissions.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // Get database connection
    $db = getDBConnection();
    
    // GET Requests - Fetch audit logs
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        // Build base query
        $whereClause = "WHERE al.tenant_id = :tenant_id";
        $params = ['tenant_id' => $tenantId];
        
        // Apply filters
        if (!empty($_GET['action_type'])) {
            $whereClause .= " AND al.action = :action_type";
            $params['action_type'] = strtoupper($_GET['action_type']);
        }
        
        if (!empty($_GET['table_name'])) {
            $whereClause .= " AND al.description LIKE :table_name";
            $params['table_name'] = '%' . $_GET['table_name'] . '%';
        }
        
        if (!empty($_GET['user_id'])) {
            $whereClause .= " AND al.user_id = :user_id";
            $params['user_id'] = $_GET['user_id'];
        }
        
        if (!empty($_GET['date_from'])) {
            $whereClause .= " AND DATE(al.created_at) >= :date_from";
            $params['date_from'] = $_GET['date_from'];
        }
        
        if (!empty($_GET['date_to'])) {
            $whereClause .= " AND DATE(al.created_at) <= :date_to";
            $params['date_to'] = $_GET['date_to'];
        }
        
        if (!empty($_GET['search'])) {
            $whereClause .= " AND (al.description LIKE :search OR u.email LIKE :search2)";
            $params['search'] = '%' . $_GET['search'] . '%';
            $params['search2'] = '%' . $_GET['search'] . '%';
        }
        
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $offset = ($page - 1) * $limit;
        
        // Count total
        $countSql = "SELECT COUNT(*) as total 
                     FROM audit_logs al 
                     LEFT JOIN users u ON al.user_id = u.id 
                     $whereClause";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'] ?? 0;
        
        // Fetch logs
        $sql = "SELECT al.*, u.email as user_email 
                FROM audit_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                $whereClause 
                ORDER BY al.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $logs,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
        exit;
    }
    
    // POST Requests - Export or other actions
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'export';
        
        if ($action === 'export') {
            // Build export query (no pagination)
            $whereClause = "WHERE al.tenant_id = :tenant_id";
            $params = ['tenant_id' => $tenantId];
            
            if (!empty($input['action_type'])) {
                $whereClause .= " AND al.action = :action_type";
                $params['action_type'] = strtoupper($input['action_type']);
            }
            
            if (!empty($input['table_name'])) {
                $whereClause .= " AND al.description LIKE :table_name";
                $params['table_name'] = '%' . $input['table_name'] . '%';
            }
            
            if (!empty($input['date_from'])) {
                $whereClause .= " AND DATE(al.created_at) >= :date_from";
                $params['date_from'] = $input['date_from'];
            }
            
            if (!empty($input['date_to'])) {
                $whereClause .= " AND DATE(al.created_at) <= :date_to";
                $params['date_to'] = $input['date_to'];
            }
            
            $sql = "SELECT al.*, u.email as user_email 
                    FROM audit_logs al 
                    LEFT JOIN users u ON al.user_id = u.id 
                    $whereClause 
                    ORDER BY al.created_at DESC 
                    LIMIT 10000";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $logs,
                'count' => count($logs)
            ]);
            exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
    }
    
    // Default response for unknown methods
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    
} catch (PDOException $e) {
    error_log("Audit Logs API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error'
    ]);
    } catch (Exception $e) {
    error_log("Audit Logs API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error'
    ]);
    }
