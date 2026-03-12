<?php
/**
 * Study Materials Controller — Student
 * Allows students to view, search, and download study materials
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

// CSRF helper is handled by config.php or autoloader
use App\Helpers\CsrfHelper;

header('Content-Type: application/json');

// Ensure user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['userData']['role'] ?? '';
if (!in_array($role, ['student', 'instituteadmin', 'superadmin', 'teacher', 'frontdesk'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
$userId = $_SESSION['userData']['id'] ?? null;
$studentId = $_SESSION['userData']['student_id'] ?? null;

if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

// CSRF-protected actions for students
$csrfProtectedActions = ['add_favorite', 'remove_favorite', 'feedback'];

// Validate CSRF for protected actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
if (in_array($action, $csrfProtectedActions)) {
    try {
        CsrfHelper::requireCsrfToken();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage(), 'code' => 'CSRF_ERROR']);
        exit;
    } catch (Throwable $e) {
        // If CSRF helper fails, log and allow
        error_log('CSRF Validation Error: ' . $e->getMessage());
    }
}

// Get student details
$db = getDBConnection();
$studentData = null;

if ($role === 'student' && $studentId) {
    $stmt = $db->prepare("
        SELECT s.*, b.id as batch_id, b.course_id
        FROM students s
        LEFT JOIN batches b ON s.batch_id = b.id
        WHERE s.id = :sid AND s.tenant_id = :tid
        LIMIT 1
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $studentData = $stmt->fetch();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
        case 'materials':
            // Build accessible materials query
            $where = ["sm.tenant_id = :tid", "sm.deleted_at IS NULL", "sm.status = 'active'"];
            $where[] = "(sm.published_at IS NULL OR sm.published_at <= NOW())";
            $where[] = "(sm.expires_at IS NULL OR sm.expires_at >= NOW())";
            $params = ['tid' => $tenantId];
            
            // Access control for students
            if ($role === 'student' && $studentData) {
                $accessWhere = ["sm.access_type = 'public'"];
                
                // Batch-specific access
                if ($studentData['batch_id']) {
                    $accessWhere[] = "(sm.access_type = 'batch' AND sm.batch_id = :batch_id)";
                    $params['batch_id'] = $studentData['batch_id'];
                    
                    // Permission-based access
                    $accessWhere[] = "(sm.access_type IN ('batch', 'student') AND EXISTS (
                        SELECT 1 FROM study_material_permissions smp 
                        WHERE smp.material_id = sm.id 
                        AND ((smp.entity_type = 'batch' AND smp.entity_id = :batch_id2) 
                             OR (smp.entity_type = 'student' AND smp.entity_id = :student_id))
                        AND smp.can_view = 1
                    ))";
                    $params['batch_id2'] = $studentData['batch_id'];
                    $params['student_id'] = $studentId;
                }
                
                $where[] = "(" . implode(' OR ', $accessWhere) . ")";
            }
            
            // Additional filters
            if (!empty($_GET['category_id'])) {
                $where[] = "sm.category_id = :category_id";
                $params['category_id'] = $_GET['category_id'];
            }
            
            if (!empty($_GET['subject_id'])) {
                $where[] = "sm.subject_id = :subject_id";
                $params['subject_id'] = $_GET['subject_id'];
            }
            
            if (!empty($_GET['content_type'])) {
                $where[] = "sm.content_type = :content_type";
                $params['content_type'] = $_GET['content_type'];
            }
            
            if (!empty($_GET['search'])) {
                $where[] = "(sm.title LIKE :search OR sm.description LIKE :search OR JSON_CONTAINS(sm.tags, JSON_QUOTE(:search_tag)))";
                $params['search'] = '%' . $_GET['search'] . '%';
                $params['search_tag'] = $_GET['search'];
            }

            // FILTER: QBank status
            $isQB = isset($_GET['is_qbank']) ? (int)$_GET['is_qbank'] : 0;
            $where[] = "sm.is_qbank = :is_qbank";
            $params['is_qbank'] = $isQB;
            
            $whereClause = implode(' AND ', $where);
            
            // Get total count
            $countStmt = $db->prepare("SELECT COUNT(*) FROM study_materials sm WHERE $whereClause");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get materials with pagination
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 20);
            $offset = ($page - 1) * $perPage;
            
            $query = "
                SELECT 
                    sm.id, sm.title, sm.description,
                    sm.file_name, sm.file_type, sm.file_size, sm.file_extension,
                    sm.external_url, sm.content_type,
                    sm.download_count, sm.view_count,
                    sm.created_at, sm.updated_at,
                    c.name as category_name,
                    c.icon as category_icon,
                    c.color as category_color,
                    s.name as subject_name,
                    u.name as created_by_name
                FROM study_materials sm
                LEFT JOIN study_material_categories c ON sm.category_id = c.id
                LEFT JOIN subjects s ON sm.subject_id = s.id
                LEFT JOIN users u ON sm.created_by = u.id
                WHERE $whereClause
                ORDER BY sm.is_featured DESC, sm.sort_order ASC, sm.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $db->prepare($query);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $materials = $stmt->fetchAll();
            
            // Check favorites and format for each material
            foreach ($materials as &$material) {
                $material['tags'] = json_decode($material['tags'] ?? '[]', true);
                $material['file_size_formatted'] = formatFileSize($material['file_size']);
                $material['is_favorite'] = false;
                
                if ($role === 'student' && $studentId) {
                    $favStmt = $db->prepare("
                        SELECT id FROM study_material_favorites 
                        WHERE material_id = :mid AND student_id = :sid
                    ");
                    $favStmt->execute(['mid' => $material['id'], 'sid' => $studentId]);
                    $material['is_favorite'] = (bool)$favStmt->fetch();
                }
                
                // Determine if downloadable
                $material['can_download'] = true;
                if ($role === 'student') {
                    $dlStmt = $db->prepare("
                        SELECT can_download FROM study_material_permissions 
                        WHERE material_id = :mid AND (
                            (entity_type = 'batch' AND entity_id = :bid) OR
                            (entity_type = 'student' AND entity_id = :sid)
                        )
                    ");
                    $dlStmt->execute([
                        'mid' => $material['id'],
                        'bid' => $studentData['batch_id'] ?? 0,
                        'sid' => $studentId ?? 0
                    ]);
                    $perm = $dlStmt->fetch();
                    if ($perm) {
                        $material['can_download'] = (bool)$perm['can_download'];
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $materials,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception("Material ID is required");
            
            // Check access permission
            if ($role === 'student' && !canAccessMaterial($db, $id, $studentId, $studentData)) {
                throw new Exception("You don't have permission to access this material");
            }
            
            $stmt = $db->prepare("
                SELECT 
                    sm.*,
                    c.name as category_name,
                    c.icon as category_icon,
                    s.name as subject_name,
                    u.name as created_by_name
                FROM study_materials sm
                LEFT JOIN study_material_categories c ON sm.category_id = c.id
                LEFT JOIN subjects s ON sm.subject_id = s.id
                LEFT JOIN users u ON sm.created_by = u.id
                WHERE sm.id = :id AND sm.tenant_id = :tid AND sm.deleted_at IS NULL AND sm.status = 'active'
            ");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            $material = $stmt->fetch();
            
            if (!$material) throw new Exception("Material not found");
            
            $material['tags'] = json_decode($material['tags'] ?? '[]', true);
            $material['file_size_formatted'] = formatFileSize($material['file_size']);
            
            // Check if favorite
            $material['is_favorite'] = false;
            if ($role === 'student' && $studentId) {
                $favStmt = $db->prepare("
                    SELECT id FROM study_material_favorites 
                    WHERE material_id = :mid AND student_id = :sid
                ");
                $favStmt->execute(['mid' => $id, 'sid' => $studentId]);
                $material['is_favorite'] = (bool)$favStmt->fetch();
            }
            
            // Get related materials
            $relatedStmt = $db->prepare("
                SELECT sm.id, sm.title, sm.content_type, sm.file_extension, sm.view_count
                FROM study_materials sm
                WHERE sm.id != :id AND sm.tenant_id = :tid 
                AND sm.deleted_at IS NULL AND sm.status = 'active'
                AND (sm.category_id = :cat_id OR sm.subject_id = :sub_id)
                ORDER BY sm.view_count DESC
                LIMIT 5
            ");
            $relatedStmt->execute([
                'id' => $id,
                'tid' => $tenantId,
                'cat_id' => $material['category_id'],
                'sub_id' => $material['subject_id']
            ]);
            $material['related'] = $relatedStmt->fetchAll();
            
            // Log view
            logAccess($db, $tenantId, $id, $userId, $role, 'view');
            
            // Increment view count
            $db->prepare("UPDATE study_materials SET view_count = view_count + 1 WHERE id = :id")
                ->execute(['id' => $id]);
            
            echo json_encode(['success' => true, 'data' => $material]);
            break;
            
        case 'download':
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception("Material ID is required");
            
            // Check access permission
            if ($role === 'student' && !canAccessMaterial($db, $id, $studentId, $studentData)) {
                throw new Exception("You don't have permission to download this material");
            }
            
            $stmt = $db->prepare("
                SELECT * FROM study_materials 
                WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL AND status = 'active'
            ");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            $material = $stmt->fetch();
            
            if (!$material) throw new Exception("Material not found");
            
            if ($material['content_type'] === 'link') {
                echo json_encode([
                    'success' => true,
                    'type' => 'redirect',
                    'url' => $material['external_url']
                ]);
                exit;
            }
            
            if (empty($material['file_path']) || !file_exists($material['file_path'])) {
                throw new Exception("File not found on server");
            }
            
            // Log download
            logAccess($db, $tenantId, $id, $userId, $role, 'download');
            
            // Increment download count
            $db->prepare("UPDATE study_materials SET download_count = download_count + 1 WHERE id = :id")
                ->execute(['id' => $id]);
            
            // Serve file directly
            header('Content-Type: ' . ($material['file_type'] ?? 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . ($material['file_name'] ?? basename($material['file_path'])) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($material['file_path']));
            
            // Clear output buffer if any
            if (ob_get_level()) ob_end_clean();
            
            readfile($material['file_path']);
            exit;
            
        case 'categories':
            // Get categories with material counts
            $stmt = $db->prepare("
                SELECT 
                    c.*,
                    COUNT(sm.id) as material_count
                FROM study_material_categories c
                LEFT JOIN study_materials sm ON c.id = sm.category_id 
                    AND sm.deleted_at IS NULL 
                    AND sm.status = 'active'
                WHERE c.tenant_id = :tid AND c.deleted_at IS NULL AND c.status = 'active'
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.name ASC
            ");
            $stmt->execute(['tid' => $tenantId]);
            $categories = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $categories]);
            break;
            
        case 'favorites':
            if ($role !== 'student' || !$studentId) {
                throw new Exception("Only students can manage favorites");
            }
            
            $stmt = $db->prepare("
                SELECT sm.*, c.name as category_name, c.icon as category_icon
                FROM study_material_favorites f
                JOIN study_materials sm ON f.material_id = sm.id
                LEFT JOIN study_material_categories c ON sm.category_id = c.id
                WHERE f.student_id = :sid AND f.tenant_id = :tid
                AND sm.deleted_at IS NULL AND sm.status = 'active'
                ORDER BY f.created_at DESC
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $favorites = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $favorites]);
            break;
            
        case 'add_favorite':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            if ($role !== 'student' || !$studentId) {
                throw new Exception("Only students can add favorites");
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $materialId = $input['material_id'] ?? null;
            
            if (!$materialId) throw new Exception("Material ID is required");
            
            // Check if already favorited
            $checkStmt = $db->prepare("
                SELECT id FROM study_material_favorites 
                WHERE material_id = :mid AND student_id = :sid
            ");
            $checkStmt->execute(['mid' => $materialId, 'sid' => $studentId]);
            if ($checkStmt->fetch()) {
                echo json_encode(['success' => true, 'message' => 'Already in favorites']);
                exit;
            }
            
            $stmt = $db->prepare("
                INSERT INTO study_material_favorites (tenant_id, material_id, student_id)
                VALUES (:tid, :mid, :sid)
            ");
            $stmt->execute(['tid' => $tenantId, 'mid' => $materialId, 'sid' => $studentId]);
            
            echo json_encode(['success' => true, 'message' => 'Added to favorites']);
            break;
            
        case 'remove_favorite':
            if ($method !== 'POST' && $method !== 'DELETE') throw new Exception("Method not allowed");
            if ($role !== 'student' || !$studentId) {
                throw new Exception("Only students can remove favorites");
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_GET;
            
            $materialId = $input['material_id'] ?? null;
            if (!$materialId) throw new Exception("Material ID is required");
            
            $stmt = $db->prepare("
                DELETE FROM study_material_favorites 
                WHERE material_id = :mid AND student_id = :sid AND tenant_id = :tid
            ");
            $stmt->execute(['mid' => $materialId, 'sid' => $studentId, 'tid' => $tenantId]);
            
            echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
            break;
            
        case 'feedback':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            if ($role !== 'student' || !$studentId) {
                throw new Exception("Only students can submit feedback");
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $materialId = $input['material_id'] ?? null;
            $rating = $input['rating'] ?? null;
            $comment = $input['comment'] ?? '';
            
            if (!$materialId) throw new Exception("Material ID is required");
            if (!$rating || $rating < 1 || $rating > 5) throw new Exception("Rating must be between 1 and 5");
            
            // Check if already has feedback
            $checkStmt = $db->prepare("
                SELECT id FROM study_material_feedback 
                WHERE material_id = :mid AND student_id = :sid
            ");
            $checkStmt->execute(['mid' => $materialId, 'sid' => $studentId]);
            
            if ($checkStmt->fetch()) {
                // Update existing feedback
                $stmt = $db->prepare("
                    UPDATE study_material_feedback 
                    SET rating = :rating, comment = :comment, updated_at = NOW()
                    WHERE material_id = :mid AND student_id = :sid
                ");
            } else {
                // Insert new feedback
                $stmt = $db->prepare("
                    INSERT INTO study_material_feedback (tenant_id, material_id, student_id, rating, comment)
                    VALUES (:tid, :mid, :sid, :rating, :comment)
                ");
                $stmt->bindValue(':tid', $tenantId);
            }
            
            $stmt->bindValue(':mid', $materialId);
            $stmt->bindValue(':sid', $studentId);
            $stmt->bindValue(':rating', $rating);
            $stmt->bindValue(':comment', $comment);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Feedback submitted']);
            break;
            
        case 'stats':
            if ($role !== 'student' || !$studentId) {
                throw new Exception("Only students can view their stats");
            }
            
            // Total materials accessible
            $totalStmt = $db->prepare("
                SELECT COUNT(*) FROM study_materials sm
                WHERE sm.tenant_id = :tid AND sm.deleted_at IS NULL AND sm.status = 'active'
            ");
            $totalStmt->execute(['tid' => $tenantId]);
            $totalMaterials = $totalStmt->fetchColumn();
            
            // Favorites count
            $favStmt = $db->prepare("
                SELECT COUNT(*) FROM study_material_favorites 
                WHERE student_id = :sid AND tenant_id = :tid
            ");
            $favStmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $favoritesCount = $favStmt->fetchColumn();
            
            // Downloads by student
            $dlStmt = $db->prepare("
                SELECT COUNT(*) as total, DATE(created_at) as date
                FROM study_material_access_logs
                WHERE user_id = :uid AND user_type = 'student' AND action = 'download'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date
            ");
            $dlStmt->execute(['uid' => $userId]);
            $downloads = $dlStmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_materials' => $totalMaterials,
                    'favorites_count' => $favoritesCount,
                    'downloads' => $downloads
                ]
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => "Unknown action: {$action}"
            ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Helper Functions

function canAccessMaterial($db, $materialId, $studentId, $studentData) {
    $stmt = $db->prepare("
        SELECT access_type, batch_id FROM study_materials 
        WHERE id = :id AND status = 'active' AND deleted_at IS NULL
    ");
    $stmt->execute(['id' => $materialId]);
    $material = $stmt->fetch();
    
    if (!$material) return false;
    
    // Public access
    if ($material['access_type'] === 'public') return true;
    
    // Check permissions
    if ($material['access_type'] === 'batch') {
        return $studentData && $studentData['batch_id'] == $material['batch_id'];
    }
    
    // Check specific permissions
    $permStmt = $db->prepare("
        SELECT can_view FROM study_material_permissions 
        WHERE material_id = :mid AND (
            (entity_type = 'batch' AND entity_id = :bid) OR
            (entity_type = 'student' AND entity_id = :sid)
        ) AND can_view = 1
    ");
    $permStmt->execute([
        'mid' => $materialId,
        'bid' => $studentData['batch_id'] ?? 0,
        'sid' => $studentId ?? 0
    ]);
    
    return (bool)$permStmt->fetch();
}

function logAccess($db, $tenantId, $materialId, $userId, $userType, $action) {
    $stmt = $db->prepare("
        INSERT INTO study_material_access_logs 
        (tenant_id, material_id, user_id, user_type, action, ip_address, user_agent)
        VALUES (:tid, :mid, :uid, :utype, :action, :ip, :ua)
    ");
    
    $stmt->execute([
        'tid' => $tenantId,
        'mid' => $materialId,
        'uid' => $userId,
        'utype' => $userType,
        'action' => $action,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $unitIndex), 2) . ' ' . $units[$unitIndex];
}
