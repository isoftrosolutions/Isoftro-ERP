<?php
/**
 * LMS Controller — Learning Management System
 * Integrates Study Materials, Videos, Assignments, Online Classes
 * Full implementation connecting to study_materials module
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

$role = $_SESSION['userData']['role'] ?? '';
if (!in_array($role, ['instituteadmin', 'superadmin', 'teacher'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
$userId = $_SESSION['userData']['id'] ?? null;

if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

try {
    $db = getDBConnection();

    switch ($action) {
        // ========== DASHBOARD / OVERVIEW ==========
        
        case 'dashboard':
        case 'overview':
            // Get summary statistics
            $stats = [];
            
            // Total materials
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM study_materials 
                WHERE tenant_id = :tid AND deleted_at IS NULL
            ");
            $stmt->execute(['tid' => $tenantId]);
            $stats['total_materials'] = $stmt->fetchColumn();
            
            // Materials by content type
            $typeStmt = $db->prepare("
                SELECT content_type, COUNT(*) as count
                FROM study_materials
                WHERE tenant_id = :tid AND deleted_at IS NULL
                GROUP BY content_type
            ");
            $typeStmt->execute(['tid' => $tenantId]);
            $stats['by_type'] = $typeStmt->fetchAll();
            
            // Most viewed materials
            $popularStmt = $db->prepare("
                SELECT sm.id, sm.title, sm.view_count, sm.download_count, sm.content_type,
                       c.name as category_name
                FROM study_materials sm
                LEFT JOIN study_material_categories c ON sm.category_id = c.id
                WHERE sm.tenant_id = :tid AND sm.deleted_at IS NULL
                ORDER BY sm.view_count DESC
                LIMIT 5
            ");
            $popularStmt->execute(['tid' => $tenantId]);
            $stats['most_viewed'] = $popularStmt->fetchAll();
            
            // Recent uploads
            $recentStmt = $db->prepare("
                SELECT sm.id, sm.title, sm.created_at, sm.content_type,
                       c.name as category_name, u.name as created_by_name
                FROM study_materials sm
                LEFT JOIN study_material_categories c ON sm.category_id = c.id
                LEFT JOIN users u ON sm.created_by = u.id
                WHERE sm.tenant_id = :tid AND sm.deleted_at IS NULL
                ORDER BY sm.created_at DESC
                LIMIT 10
            ");
            $recentStmt->execute(['tid' => $tenantId]);
            $stats['recent_uploads'] = $recentStmt->fetchAll();
            
            // Total categories
            $catStmt = $db->prepare("
                SELECT COUNT(*) FROM study_material_categories 
                WHERE tenant_id = :tid AND deleted_at IS NULL
            ");
            $catStmt->execute(['tid' => $tenantId]);
            $stats['total_categories'] = $catStmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        // ========== STUDY MATERIALS PROXY ==========
        // These endpoints proxy to study_materials controller for convenience
        
        case 'materials':
            // Proxy to study_materials list
            $_GET['action'] = 'list';
            require_once __DIR__ . '/study_materials.php';
            break;
            
        case 'get_material':
            $_GET['action'] = 'get';
            require_once __DIR__ . '/study_materials.php';
            break;
            
        case 'create_material':
        case 'create':
            if ($method === 'POST') {
                $_POST['action'] = 'create';
            } else {
                $_GET['action'] = 'create';
            }
            require_once __DIR__ . '/study_materials.php';
            break;
            
        case 'update_material':
        case 'update':
            $_POST['action'] = 'update';
            require_once __DIR__ . '/study_materials.php';
            break;
            
        case 'delete_material':
        case 'delete':
            if ($method === 'POST' || $method === 'DELETE' || $method === 'GET') {
                $_GET['action'] = 'delete';
            }
            require_once __DIR__ . '/study_materials.php';
            break;
            
        // ========== CATEGORIES ==========
        
        case 'categories':
            $_GET['action'] = 'categories';
            require_once __DIR__ . '/study_materials.php';
            break;
            
        case 'create_category':
            $_POST['action'] = 'create_category';
            require_once __DIR__ . '/study_materials.php';
            break;
            
        case 'update_category':
            $_POST['action'] = 'update_category';
            require_once __DIR__ . '/study_materials.php';
            break;
            
        case 'delete_category':
            $_GET['action'] = 'delete_category';
            require_once __DIR__ . '/study_materials.php';
            break;
            
        // ========== VIDEO MANAGEMENT ==========
        
        case 'videos':
            // Get video-type materials
            $where = ["sm.tenant_id = :tid", "sm.deleted_at IS NULL", "sm.content_type = 'video'"];
            $params = ['tid' => $tenantId];
            
            if (!empty($_GET['subject_id'])) {
                $where[] = "sm.subject_id = :subject_id";
                $params['subject_id'] = $_GET['subject_id'];
            }
            
            if (!empty($_GET['category_id'])) {
                $where[] = "sm.category_id = :category_id";
                $params['category_id'] = $_GET['category_id'];
            }
            
            $whereClause = implode(' AND ', $where);
            
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 20);
            $offset = ($page - 1) * $perPage;
            
            // Get total count
            $countStmt = $db->prepare("SELECT COUNT(*) FROM study_materials sm WHERE $whereClause");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            $query = "
                SELECT 
                    sm.id, sm.title, sm.description,
                    sm.external_url, sm.file_path,
                    sm.view_count, sm.download_count,
                    sm.created_at,
                    c.name as category_name,
                    s.name as subject_name,
                    u.name as created_by_name
                FROM study_materials sm
                LEFT JOIN study_material_categories c ON sm.category_id = c.id
                LEFT JOIN subjects s ON sm.subject_id = s.id
                LEFT JOIN users u ON sm.created_by = u.id
                WHERE $whereClause
                ORDER BY sm.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $db->prepare($query);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $videos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $videos,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage
                ]
            ]);
            break;
            
        case 'add_video':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            
            // Create as video-type material
            $input['content_type'] = 'video';
            $_POST = $input;
            $_POST['action'] = 'create';
            require_once __DIR__ . '/study_materials.php';
            break;
            
        // ========== ASSIGNMENTS ==========
        
        case 'assignments':
            // Get assignment-type materials
            $where = ["sm.tenant_id = :tid", "sm.deleted_at IS NULL"];
            $params = ['tid' => $tenantId];
            
            // Filter by category that contains "assignment"
            $where[] = "(c.name LIKE '%assignment%' OR c.name LIKE '%homework%' OR sm.title LIKE '%assignment%')";
            
            if (!empty($_GET['batch_id'])) {
                $where[] = "(sm.batch_id = :batch_id OR sm.batch_id IS NULL)";
                $params['batch_id'] = $_GET['batch_id'];
            }
            
            $whereClause = implode(' AND ', $where);
            
            $stmt = $db->prepare("
                SELECT 
                    sm.*,
                    c.name as category_name,
                    b.name as batch_name,
                    s.name as subject_name,
                    u.name as created_by_name
                FROM study_materials sm
                LEFT JOIN study_material_categories c ON sm.category_id = c.id
                LEFT JOIN batches b ON sm.batch_id = b.id
                LEFT JOIN subjects s ON sm.subject_id = s.id
                LEFT JOIN users u ON sm.created_by = u.id
                WHERE $whereClause
                ORDER BY sm.created_at DESC
            ");
            $stmt->execute($params);
            $assignments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $assignments]);
            break;
            
        case 'create_assignment':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            
            // Set category to Assignments if not specified
            if (empty($input['category_id'])) {
                $catStmt = $db->prepare("
                    SELECT id FROM study_material_categories 
                    WHERE tenant_id = :tid AND name LIKE '%assignment%'
                    LIMIT 1
                ");
                $catStmt->execute(['tid' => $tenantId]);
                $category = $catStmt->fetch();
                if ($category) {
                    $input['category_id'] = $category['id'];
                }
            }
            
            $_POST = $input;
            $_POST['action'] = 'create';
            require_once __DIR__ . '/study_materials.php';
            break;
            
        // ========== ONLINE CLASSES ==========
        
        case 'online_classes':
            $batchId = $_GET['batch_id'] ?? null;
            $status = $_GET['status'] ?? 'scheduled';
            
            $where = ["tenant_id = :tid"];
            $params = ['tid' => $tenantId];
            
            if ($batchId) {
                $where[] = "batch_id = :bid";
                $params['bid'] = $batchId;
            }
            if ($status) {
                $where[] = "status = :status";
                $params['status'] = $status;
            }
            
            $whereClause = implode(' AND ', $where);
            $stmt = $db->prepare("
                SELECT oc.*, b.name as batch_name, s.name as subject_name, t.full_name as teacher_name
                FROM online_classes oc
                JOIN batches b ON oc.batch_id = b.id
                JOIN subjects s ON oc.subject_id = s.id
                JOIN teachers t ON oc.teacher_id = t.id
                WHERE oc.$whereClause
                ORDER BY oc.start_time ASC
            ");
            $stmt->execute($params);
            $classes = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $classes,
                'meta' => ['total' => count($classes)]
            ]);
            break;
            
        case 'schedule_class':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            $title = $input['title'] ?? '';
            $batch_id = $input['batch_id'] ?? null;
            $subject_id = $input['subject_id'] ?? null;
            $teacher_id = $input['teacher_id'] ?? $userId;
            $start_time = $input['start_time'] ?? '';
            $duration = (int)($input['duration_minutes'] ?? 40);
            $provider = $input['meeting_provider'] ?? 'zoom';
            
            if (empty($title) || empty($batch_id) || empty($subject_id) || empty($start_time)) {
                throw new Exception("Title, Batch, Subject and Start Time are required");
            }
            
            // Integrate with provider
            require_once __DIR__ . '/../../Services/OnlineClassService.php';
            $onlineService = new \App\Services\OnlineClassService();
            
            $meetingData = [];
            if ($provider === 'zoom') {
                $meetingData = $onlineService->scheduleZoomMeeting($input);
            } elseif ($provider === 'google_meet') {
                $meetingData = $onlineService->scheduleGoogleMeet($input);
            } else {
                throw new Exception("Invalid meeting provider: $provider");
            }
            
            if (!$meetingData['success']) {
                throw new Exception("Failed to schedule meeting via $provider");
            }
            
            $stmt = $db->prepare("
                INSERT INTO online_classes 
                (tenant_id, batch_id, subject_id, teacher_id, title, description, start_time, duration_minutes, 
                 meeting_provider, meeting_id, meeting_password, join_url, start_url, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')
            ");
            
            $stmt->execute([
                $tenantId, $batch_id, $subject_id, $teacher_id, $title, $input['description'] ?? '', 
                $start_time, $duration, $provider, $meetingData['meeting_id'], 
                $meetingData['password'] ?? '', $meetingData['join_url'], $meetingData['start_url']
            ]);
            $newClassId = $db->lastInsertId();
            
            \App\Helpers\AuditLogger::log('CREATE', 'online_classes', $newClassId, null, $input);
            
            echo json_encode([
                'success' => true,
                'message' => 'Online class scheduled successfully',
                'id' => $newClassId,
                'meeting_id' => $meetingData['meeting_id'],
                'join_url' => $meetingData['join_url']
            ]);
            break;
            
        case 'class_attendance':
            $classId = $_GET['class_id'] ?? null;
            if (!$classId) throw new Exception("Class ID required");
            
            require_once __DIR__ . '/../../Services/OnlineClassService.php';
            $onlineService = new \App\Services\OnlineClassService();
            $data = $onlineService->getClassAttendance($db, $tenantId, $classId);
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        // ========== ANALYTICS ==========
        
        case 'analytics':
        case 'stats':
            $_GET['action'] = 'stats';
            require_once __DIR__ . '/study_materials.php';
            break;
            
        case 'access_logs':
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 50);
            $offset = ($page - 1) * $perPage;
            
            $where = ["smal.tenant_id = :tid"];
            $params = ['tid' => $tenantId];
            
            if (!empty($_GET['material_id'])) {
                $where[] = "smal.material_id = :mid";
                $params['mid'] = $_GET['material_id'];
            }
            
            if (!empty($_GET['action_filter'])) {
                $where[] = "smal.action = :action";
                $params['action'] = $_GET['action_filter'];
            }
            
            $whereClause = implode(' AND ', $where);
            
            $stmt = $db->prepare("
                SELECT 
                    smal.*,
                    sm.title as material_title,
                    u.name as user_name
                FROM study_material_access_logs smal
                LEFT JOIN study_materials sm ON smal.material_id = sm.id
                LEFT JOIN users u ON smal.user_id = u.id
                WHERE $whereClause
                ORDER BY smal.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $logs]);
            break;
            
        // ========== BULK OPERATIONS ==========
        
        case 'bulk_update_status':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            
            $input = json_decode(file_get_contents('php://input'), true);
            $ids = $input['ids'] ?? [];
            $status = $input['status'] ?? null;
            
            if (empty($ids)) throw new Exception("No IDs provided");
            if (!in_array($status, ['active', 'inactive', 'draft'])) {
                throw new Exception("Invalid status");
            }
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("
                UPDATE study_materials 
                SET status = ?, updated_by = ?, updated_at = NOW()
                WHERE id IN ($placeholders) AND tenant_id = ?
            ");
            
            $params = array_merge([$status, $userId], $ids, [$tenantId]);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => count($ids) . ' materials updated'
            ]);
            break;
            
        case 'bulk_move_category':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            
            $input = json_decode(file_get_contents('php://input'), true);
            $ids = $input['ids'] ?? [];
            $categoryId = $input['category_id'] ?? null;
            
            if (empty($ids)) throw new Exception("No IDs provided");
            if (!$categoryId) throw new Exception("Category ID is required");
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("
                UPDATE study_materials 
                SET category_id = ?, updated_by = ?, updated_at = NOW()
                WHERE id IN ($placeholders) AND tenant_id = ?
            ");
            
            $params = array_merge([$categoryId, $userId], $ids, [$tenantId]);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => count($ids) . ' materials moved'
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => "LMS action '{$action}' not implemented or unknown"
            ]);
    }
} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
