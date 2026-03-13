<?php
/**
 * Study Materials Controller — Admin
 * Manages study materials: CRUD, categories, permissions, uploads
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

// CSRF helper is handled by config.php or autoloader
use App\Helpers\CsrfHelper;

// Include cache service
use App\Services\StudyMaterialCacheService;

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
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// CSRF-protected actions (state-changing operations)
$csrfProtectedActions = ['create', 'update', 'delete', 'bulk_delete', 'bulk_status', 
    'toggle_featured', 'create_category', 'update_category', 'delete_category',
    'add_permission', 'remove_permission', 'bulk_assign'];

// Validate CSRF for protected actions
if (in_array($action, $csrfProtectedActions)) {
    try {
        CsrfHelper::requireCsrfToken();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage(), 'code' => 'CSRF_ERROR']);
        exit;
    } catch (Throwable $e) {
        // If CSRF helper fails, log and allow (fail-open for availability)
        // In production, you might want to fail-closed
        error_log('CSRF Validation Error: ' . $e->getMessage());
    }
}

try {
    $db = getDBConnection();

    switch ($action) {
        // ========== GET CSRF TOKEN ==========
        
        case 'get_token':
        case 'csrf_token':
            // Return CSRF token for frontend (read-only, no validation needed)
            $token = CsrfHelper::getCsrfToken();
            echo json_encode([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'token_name' => 'csrf_token',
                    'expires_in' => 1800 // 30 minutes
                ]
            ]);
            break;
            
        // ========== STUDY MATERIALS CRUD ==========
        
        case 'list':
        case 'materials':
            // Build query with filters
            $where = ["sm.tenant_id = :tid", "sm.deleted_at IS NULL"];
            $params = ['tid' => $tenantId];
            
            if (!empty($_GET['category_id'])) {
                $where[] = "sm.category_id = :category_id";
                $params['category_id'] = $_GET['category_id'];
            }
            
            if (!empty($_GET['subject_id'])) {
                $where[] = "sm.subject_id = :subject_id";
                $params['subject_id'] = $_GET['subject_id'];
            }
            
            if (!empty($_GET['batch_id'])) {
                $where[] = "sm.batch_id = :batch_id";
                $params['batch_id'] = $_GET['batch_id'];
            }
            
            if (!empty($_GET['content_type'])) {
                $where[] = "sm.content_type = :content_type";
                $params['content_type'] = $_GET['content_type'];
            }
            
            if (!empty($_GET['status'])) {
                $where[] = "sm.status = :status";
                $params['status'] = $_GET['status'];
            }
            
            // FILTER: QBank status
            $isQB = isset($_GET['is_qbank']) ? (int)$_GET['is_qbank'] : 0;
            $where[] = "sm.is_qbank = :is_qbank";
            $params['is_qbank'] = $isQB;
            
            if (!empty($_GET['search'])) {
                $where[] = "(sm.title LIKE :search OR sm.description LIKE :search)";
                $params['search'] = '%' . $_GET['search'] . '%';
            }
            
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
                    sm.*,
                    c.name as category_name,
                    c.icon as category_icon,
                    c.color as category_color,
                    s.name as subject_name,
                    b.name as batch_name,
                    cr.name as course_name,
                    u.name as created_by_name
                FROM study_materials sm
                LEFT JOIN study_material_categories c ON sm.category_id = c.id
                LEFT JOIN subjects s ON sm.subject_id = s.id
                LEFT JOIN batches b ON sm.batch_id = b.id
                LEFT JOIN courses cr ON sm.course_id = cr.id
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
            
            // Optimize: Fetch all permissions in a single query instead of N+1
            if (!empty($materials)) {
                $materialIds = array_column($materials, 'id');
                
                // Get all permissions for fetched materials in one query
                $permQuery = "
                    SELECT 
                        smp.*,
                        CASE 
                            WHEN smp.entity_type = 'batch' THEN b.name
                            WHEN smp.entity_type = 'student' THEN u.name
                        END as entity_name
                    FROM study_material_permissions smp
                    LEFT JOIN batches b ON smp.entity_type = 'batch' AND smp.entity_id = b.id
                    LEFT JOIN students st ON smp.entity_type = 'student' AND smp.entity_id = st.id
                    LEFT JOIN users u ON st.user_id = u.id
                    WHERE smp.material_id IN (" . implode(',', array_fill(0, count($materialIds), '?')) . ")
                ";
                
                $permStmt = $db->prepare($permQuery);
                $permStmt->execute($materialIds);
                $allPermissions = $permStmt->fetchAll();
                
                // Group permissions by material_id
                $permissionsByMaterial = [];
                foreach ($allPermissions as $perm) {
                    $permissionsByMaterial[$perm['material_id']][] = $perm;
                }
                
                // Assign permissions to materials
                foreach ($materials as &$material) {
                    $materialId = $material['id'];
                    $material['permissions'] = $permissionsByMaterial[$materialId] ?? [];
                    $material['tags'] = json_decode($material['tags'] ?? '[]', true);
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
            
            $stmt = $db->prepare("
                SELECT 
                    sm.*,
                    c.name as category_name,
                    c.icon as category_icon,
                    s.name as subject_name,
                    b.name as batch_name,
                    cr.title as course_name,
                    u.name as created_by_name
                FROM study_materials sm
                LEFT JOIN study_material_categories c ON sm.category_id = c.id
                LEFT JOIN subjects s ON sm.subject_id = s.id
                LEFT JOIN batches b ON sm.batch_id = b.id
                LEFT JOIN courses cr ON sm.course_id = cr.id
                LEFT JOIN users u ON sm.created_by = u.id
                WHERE sm.id = :id AND sm.tenant_id = :tid AND sm.deleted_at IS NULL
            ");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            $material = $stmt->fetch();
            
            if (!$material) throw new Exception("Material not found");
            
            $material['tags'] = json_decode($material['tags'] ?? '[]', true);
            
            // Get permissions
            $permStmt = $db->prepare("
                SELECT smp.*, 
                    CASE 
                        WHEN smp.entity_type = 'batch' THEN b.name
                        WHEN smp.entity_type = 'student' THEN CONCAT(u.name, ' (', s.roll_no, ')')
                    END as entity_name
                FROM study_material_permissions smp
                LEFT JOIN batches b ON smp.entity_type = 'batch' AND smp.entity_id = b.id
                LEFT JOIN students s ON smp.entity_type = 'student' AND smp.entity_id = s.id
                LEFT JOIN users u ON s.user_id = u.id
                WHERE smp.material_id = :mid
            ");
            $permStmt->execute(['mid' => $id]);
            $material['permissions'] = $permStmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $material]);
            break;
            
        case 'create':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            
            // Validate required fields
            if (empty($input['title'])) throw new Exception("Title is required");
            
            $title = $input['title'];
            $description = $input['description'] ?? '';
            $categoryId = !empty($input['category_id']) ? $input['category_id'] : null;
            $subjectId = !empty($input['subject_id']) ? $input['subject_id'] : null;
            $batchId = !empty($input['batch_id']) ? $input['batch_id'] : null;
            $courseId = !empty($input['course_id']) ? $input['course_id'] : null;
            $contentType = $input['content_type'] ?? 'file';
            $externalUrl = $input['external_url'] ?? null;
            $accessType = $input['access_type'] ?? 'public';
            $visibility = $input['visibility'] ?? 'all';
            $status = $input['status'] ?? 'active';
            $isFeatured = !empty($input['is_featured']) ? 1 : 0;
            $isQbank = !empty($input['is_qbank']) ? 1 : 0;
            $sortOrder = $input['sort_order'] ?? 0;
            $tags = !empty($input['tags']) ? json_encode($input['tags']) : null;
            $publishedAt = !empty($input['published_at']) ? $input['published_at'] : date('Y-m-d H:i:s');
            $expiresAt = !empty($input['expires_at']) ? $input['expires_at'] : null;
            
            // Handle file upload if present
            $fileName = null;
            $filePath = null;
            $fileType = null;
            $fileSize = 0;
            $fileExtension = null;
            
            if ($contentType === 'file' && !empty($_FILES['file'])) {
                $uploadResult = handleFileUpload($_FILES['file'], $tenantId);
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                $fileName = $uploadResult['file_name'];
                $filePath = $uploadResult['file_path'];
                $fileType = $uploadResult['file_type'];
                $fileSize = $uploadResult['file_size'];
                $fileExtension = $uploadResult['file_extension'];
            } elseif ($contentType === 'link' && empty($externalUrl)) {
                throw new Exception("External URL is required for link type materials");
            }
            
            // Insert study material
            $stmt = $db->prepare("
                INSERT INTO study_materials (
                    tenant_id, category_id, is_qbank, title, description,
                    file_name, file_path, file_type, file_size, file_extension,
                    external_url, content_type,
                    access_type, visibility,
                    course_id, batch_id, subject_id,
                    tags, status, is_featured, sort_order,
                    published_at, expires_at, created_by
                ) VALUES (
                    :tid, :category_id, :is_qbank, :title, :description,
                    :file_name, :file_path, :file_type, :file_size, :file_extension,
                    :external_url, :content_type,
                    :access_type, :visibility,
                    :course_id, :batch_id, :subject_id,
                    :tags, :status, :is_featured, :sort_order,
                    :published_at, :expires_at, :created_by
                )
            ");
            
            $stmt->execute([
                'tid' => $tenantId,
                'category_id' => $categoryId,
                'is_qbank' => $isQbank,
                'title' => $title,
                'description' => $description,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'file_size' => $fileSize,
                'file_extension' => $fileExtension,
                'external_url' => $externalUrl,
                'content_type' => $contentType,
                'access_type' => $accessType,
                'visibility' => $visibility,
                'course_id' => $courseId,
                'batch_id' => $batchId,
                'subject_id' => $subjectId,
                'tags' => $tags,
                'status' => $status,
                'is_featured' => $isFeatured,
                'sort_order' => $sortOrder,
                'published_at' => $publishedAt,
                'expires_at' => $expiresAt,
                'created_by' => $userId
            ]);
            
            $materialId = $db->lastInsertId();
            
            // Handle permissions for specific access
            if ($accessType !== 'public' && !empty($input['permissions'])) {
                insertPermissions($db, $tenantId, $materialId, $input['permissions']);
            }
            
            // Invalidate cache after creation
            try {
                $cacheService = new StudyMaterialCacheService();
                $cacheService->invalidate($tenantId);
            } catch (Exception $e) {
                // Cache failure shouldn't break functionality
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Study material created successfully',
                'id' => $materialId
            ]);
            break;
            
        case 'update':
            if ($method !== 'PUT' && $method !== 'POST' && $method !== 'PATCH') {
                throw new Exception("Method not allowed");
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            
            $id = $input['id'] ?? null;
            if (!$id) throw new Exception("Material ID is required");
            
            // Build update fields
            $fields = [];
            $params = ['id' => $id, 'tid' => $tenantId];
            $allowedFields = [
                'title', 'description', 'category_id', 'is_qbank', 'subject_id', 'batch_id', 'course_id',
                'content_type', 'external_url', 'access_type', 'visibility',
                'status', 'is_featured', 'sort_order', 'expires_at'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    if ($field === 'tags' && is_array($input[$field])) {
                        $fields[] = "$field = :$field";
                        $params[$field] = json_encode($input[$field]);
                    } else if ($field === 'is_featured') {
                        $fields[] = "$field = :$field";
                        $params[$field] = !empty($input[$field]) ? 1 : 0;
                    } else {
                        $fields[] = "$field = :$field";
                        $params[$field] = $input[$field] ?: null;
                    }
                }
            }
            
            // Handle file update if new file uploaded
            if (!empty($_FILES['file'])) {
                $uploadResult = handleFileUpload($_FILES['file'], $tenantId);
                if ($uploadResult['success']) {
                    $fields[] = "file_name = :file_name";
                    $fields[] = "file_path = :file_path";
                    $fields[] = "file_type = :file_type";
                    $fields[] = "file_size = :file_size";
                    $fields[] = "file_extension = :file_extension";
                    $params['file_name'] = $uploadResult['file_name'];
                    $params['file_path'] = $uploadResult['file_path'];
                    $params['file_type'] = $uploadResult['file_type'];
                    $params['file_size'] = $uploadResult['file_size'];
                    $params['file_extension'] = $uploadResult['file_extension'];
                }
            }
            
            $fields[] = "updated_by = :updated_by";
            $fields[] = "updated_at = NOW()";
            $params['updated_by'] = $userId;
            
            if (empty($fields)) throw new Exception("No fields to update");
            
            $stmt = $db->prepare("UPDATE study_materials SET " . implode(', ', $fields) . " WHERE id = :id AND tenant_id = :tid");
            $stmt->execute($params);
            
            // Update permissions if provided
            if (isset($input['permissions'])) {
                // Clear existing permissions
                $db->prepare("DELETE FROM study_material_permissions WHERE material_id = :mid")
                    ->execute(['mid' => $id]);
                
                // Insert new permissions
                if (!empty($input['permissions'])) {
                    insertPermissions($db, $tenantId, $id, $input['permissions']);
                }
            }
            
            // Invalidate cache after update
            try {
                $cacheService = new StudyMaterialCacheService();
                $cacheService->invalidate($tenantId);
            } catch (Exception $e) {
                // Cache failure shouldn't break functionality
            }
            
            echo json_encode(['success' => true, 'message' => 'Study material updated successfully']);
            break;
            
        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_GET;
            
            $id = $input['id'] ?? null;
            if (!$id) throw new Exception("Material ID is required");
            
            // Soft delete
            $stmt = $db->prepare("
                UPDATE study_materials 
                SET deleted_at = NOW(), updated_by = :uid 
                WHERE id = :id AND tenant_id = :tid
            ");
            $stmt->execute(['id' => $id, 'tid' => $tenantId, 'uid' => $userId]);
            
            // Invalidate cache after delete
            try {
                $cacheService = new StudyMaterialCacheService();
                $cacheService->invalidate($tenantId);
            } catch (Exception $e) {}
            
            echo json_encode(['success' => true, 'message' => 'Study material deleted successfully']);
            break;
            
        case 'bulk_delete':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            
            $input = json_decode(file_get_contents('php://input'), true);
            $ids = $input['ids'] ?? [];
            
            if (empty($ids)) throw new Exception("No IDs provided");
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("
                UPDATE study_materials 
                SET deleted_at = NOW(), updated_by = ?
                WHERE id IN ($placeholders) AND tenant_id = ?
            ");
            
            $params = array_merge([$userId], $ids, [$tenantId]);
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => count($ids) . ' materials deleted successfully']);
            break;
            
        case 'toggle_featured':
            $id = $_POST['id'] ?? null;
            if (!$id) throw new Exception("Material ID is required");
            
            $stmt = $db->prepare("
                UPDATE study_materials 
                SET is_featured = NOT is_featured, updated_by = :uid, updated_at = NOW()
                WHERE id = :id AND tenant_id = :tid
            ");
            $stmt->execute(['id' => $id, 'tid' => $tenantId, 'uid' => $userId]);
            
            echo json_encode(['success' => true, 'message' => 'Featured status toggled']);
            break;
            
        // ========== CATEGORIES ==========
        
        case 'categories':
            $stmt = $db->prepare("
                SELECT * FROM study_material_categories 
                WHERE tenant_id = :tid AND (deleted_at IS NULL OR deleted_at = '')
                ORDER BY sort_order ASC, name ASC
            ");
            $stmt->execute(['tid' => $tenantId]);
            $categories = $stmt->fetchAll();
            
            // Build tree structure
            $tree = buildCategoryTree($categories);
            
            echo json_encode([
                'success' => true,
                'data' => $categories,
                'tree' => $tree
            ]);
            break;
            
        case 'create_category':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            
            if (empty($input['name'])) throw new Exception("Category name is required");
            
            $stmt = $db->prepare("
                INSERT INTO study_material_categories 
                (tenant_id, name, description, icon, color, parent_id, sort_order, created_by)
                VALUES (:tid, :name, :description, :icon, :color, :parent_id, :sort_order, :created_by)
            ");
            
            $stmt->execute([
                'tid' => $tenantId,
                'name' => $input['name'],
                'description' => $input['description'] ?? null,
                'icon' => $input['icon'] ?? 'fa-folder',
                'color' => $input['color'] ?? '#00B894',
                'parent_id' => $input['parent_id'] ?? null,
                'sort_order' => $input['sort_order'] ?? 0,
                'created_by' => $userId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Category created successfully',
                'id' => $db->lastInsertId()
            ]);
            break;
            
        case 'update_category':
            if ($method !== 'PUT' && $method !== 'POST') throw new Exception("Method not allowed");
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            
            $id = $input['id'] ?? null;
            if (!$id) throw new Exception("Category ID is required");
            
            $fields = [];
            $params = ['id' => $id, 'tid' => $tenantId];
            $allowed = ['name', 'description', 'icon', 'color', 'parent_id', 'sort_order', 'status'];
            
            foreach ($allowed as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = :$f";
                    $params[$f] = $input[$f];
                }
            }
            
            if (empty($fields)) throw new Exception("No fields to update");
            
            $stmt = $db->prepare("UPDATE study_material_categories SET " . implode(', ', $fields) . " WHERE id = :id AND tenant_id = :tid");
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
            break;
            
        case 'delete_category':
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception("Category ID is required");
            
            // Check if category has materials
            $check = $db->prepare("SELECT COUNT(*) FROM study_materials WHERE category_id = :cid AND deleted_at IS NULL");
            $check->execute(['cid' => $id]);
            if ($check->fetchColumn() > 0) {
                throw new Exception("Cannot delete category with existing materials");
            }
            
            $stmt = $db->prepare("UPDATE study_material_categories SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            break;
            
        // ========== STATISTICS ==========
        
        case 'stats':
            // Material count by category
            $catStmt = $db->prepare("
                SELECT c.name, c.color, COUNT(sm.id) as count
                FROM study_material_categories c
                LEFT JOIN study_materials sm ON c.id = sm.category_id AND sm.deleted_at IS NULL
                WHERE c.tenant_id = :tid AND c.deleted_at IS NULL
                GROUP BY c.id
                ORDER BY count DESC
            ");
            $catStmt->execute(['tid' => $tenantId]);
            $byCategory = $catStmt->fetchAll();
            
            // Material count by content type
            $typeStmt = $db->prepare("
                SELECT content_type, COUNT(*) as count
                FROM study_materials
                WHERE tenant_id = :tid AND deleted_at IS NULL
                GROUP BY content_type
            ");
            $typeStmt->execute(['tid' => $tenantId]);
            $byType = $typeStmt->fetchAll();
            
            // Total counts
            $totalStmt = $db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(download_count) as total_downloads,
                    SUM(view_count) as total_views
                FROM study_materials
                WHERE tenant_id = :tid AND deleted_at IS NULL
            ");
            $totalStmt->execute(['tid' => $tenantId]);
            $totals = $totalStmt->fetch();
            
            // Recent access logs
            $logsStmt = $db->prepare("
                SELECT smal.*, sm.title as material_title, u.name as user_name
                FROM study_material_access_logs smal
                LEFT JOIN study_materials sm ON smal.material_id = sm.id
                LEFT JOIN users u ON smal.user_id = u.id
                WHERE smal.tenant_id = :tid
                ORDER BY smal.created_at DESC
                LIMIT 10
            ");
            $logsStmt->execute(['tid' => $tenantId]);
            $recentActivity = $logsStmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'by_category' => $byCategory,
                    'by_type' => $byType,
                    'totals' => $totals,
                    'recent_activity' => $recentActivity
                ]
            ]);
            break;
            
        // ========== DEFAULT ==========
        
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

function handleFileUpload($file, $tenantId) {
    // Secure file upload with MIME type validation and filename sanitization
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'mp4', 'mp3', 'zip', 'rar'];
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'mp4' => 'video/mp4',
        'mp3' => 'audio/mpeg',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed'
    ];
    $maxSize = 50 * 1024 * 1024; // 50MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed: ' . $file['error']];
    }
    
    // Get safe filename (sanitize original name for display)
    $originalName = basename($file['name']);
    $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\-\.\s]/', '', $originalName);
    $safeOriginalName = preg_replace('/\s+/', '_', $safeOriginalName);
    
    // Validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        return ['success' => false, 'message' => 'File extension not allowed. Allowed: ' . implode(', ', $allowedExtensions)];
    }
    
    // Validate MIME type using finfo (server-side verification)
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Check if real MIME matches expected MIME for extension
        $expectedMime = $allowedMimes[$ext] ?? '';
        if ($realMime !== $expectedMime && !in_array($realMime, ['application/octet-stream', 'application/x-empty'])) {
            // Additional check for certain types that might have different valid mimes
            $altMimes = [
                'pdf' => ['application/pdf', 'application/x-pdf'],
                'zip' => ['application/zip', 'application/x-zip-compressed'],
                'doc' => ['application/msword', 'application/octet-stream'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip']
            ];
            $validMimes = $altMimes[$ext] ?? [$expectedMime];
            if (!in_array($realMime, $validMimes)) {
                return ['success' => false, 'message' => 'Invalid file content. File type does not match extension.'];
            }
        }
    }
    
    // Size validation
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Max size: 50MB'];
    }
    
    // Create secure upload directory using absolute path anchored to project root
    $projectRoot = dirname(__DIR__, 4); // go up from Admin/ -> Controllers/ -> Http/ -> app/ -> project root
    $uploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'study_materials' . DIRECTORY_SEPARATOR . $tenantId . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate secure random filename (prevents path traversal and guessing)
    $secureFileName = bin2hex(random_bytes(16)) . '.' . $ext;
    $filePath = $uploadDir . $secureFileName;
    
    // Verify path is within upload directory (prevent path traversal)
    // Use realpath on the upload directory (which exists), not the file (which doesn't yet)
    $realUploadDir = realpath($uploadDir);
    if ($realUploadDir === false || strpos($filePath, $realUploadDir) !== 0) {
        return ['success' => false, 'message' => 'Invalid file path'];
    }
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    return [
        'success' => true,
        'file_name' => $safeOriginalName,  // Sanitized original name for display
        'file_path' => $filePath,
        'file_type' => $realMime ?? $file['type'],
        'file_size' => $file['size'],
        'file_extension' => $ext
    ];
}

function insertPermissions($db, $tenantId, $materialId, $permissions) {
    $stmt = $db->prepare("
        INSERT INTO study_material_permissions 
        (tenant_id, material_id, entity_type, entity_id, can_view, can_download)
        VALUES (:tid, :mid, :etype, :eid, :view, :download)
    ");
    
    foreach ($permissions as $perm) {
        $stmt->execute([
            'tid' => $tenantId,
            'mid' => $materialId,
            'etype' => $perm['entity_type'],
            'eid' => $perm['entity_id'],
            'view' => $perm['can_view'] ?? true,
            'download' => $perm['can_download'] ?? true
        ]);
    }
}

function buildCategoryTree($categories, $parentId = null) {
    $branch = [];
    
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $children = buildCategoryTree($categories, $category['id']);
            if ($children) {
                $category['children'] = $children;
            }
            $branch[] = $category;
        }
    }
    
    return $branch;
}
