<?php
/**
 * StudyMaterial Model
 * Handles data access and business logic for study materials
 */

class StudyMaterial {
    private $db;
    private $tenantId;
    
    public function __construct($db, $tenantId = null) {
        $this->db = $db;
        $this->tenantId = $tenantId;
    }
    
    /**
     * Get material by ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT sm.*, 
                   c.name as category_name,
                   c.icon as category_icon,
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
            WHERE sm.id = :id AND sm.deleted_at IS NULL
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Get materials with filters
     */
    public function getMaterials($filters = [], $page = 1, $perPage = 20) {
        $where = ["sm.deleted_at IS NULL"];
        $params = [];
        
        if ($this->tenantId) {
            $where[] = "sm.tenant_id = :tid";
            $params['tid'] = $this->tenantId;
        }
        
        if (!empty($filters['category_id'])) {
            $where[] = "sm.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['subject_id'])) {
            $where[] = "sm.subject_id = :subject_id";
            $params['subject_id'] = $filters['subject_id'];
        }
        
        if (!empty($filters['batch_id'])) {
            $where[] = "sm.batch_id = :batch_id";
            $params['batch_id'] = $filters['batch_id'];
        }
        
        if (!empty($filters['content_type'])) {
            $where[] = "sm.content_type = :content_type";
            $params['content_type'] = $filters['content_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "sm.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(sm.title LIKE :search OR sm.description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['access_type'])) {
            $where[] = "sm.access_type = :access_type";
            $params['access_type'] = $filters['access_type'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM study_materials sm WHERE $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Get materials
        $offset = ($page - 1) * $perPage;
        $query = "
            SELECT sm.*,
                   c.name as category_name,
                   c.icon as category_icon,
                   s.name as subject_name,
                   b.name as batch_name,
                   u.name as created_by_name
            FROM study_materials sm
            LEFT JOIN study_material_categories c ON sm.category_id = c.id
            LEFT JOIN subjects s ON sm.subject_id = s.id
            LEFT JOIN batches b ON sm.batch_id = b.id
            LEFT JOIN users u ON sm.created_by = u.id
            WHERE $whereClause
            ORDER BY sm.is_featured DESC, sm.sort_order ASC, sm.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Create new material
     */
    public function create($data) {
        $fields = [
            'tenant_id', 'category_id', 'title', 'description',
            'file_name', 'file_path', 'file_type', 'file_size', 'file_extension',
            'external_url', 'content_type',
            'access_type', 'visibility',
            'course_id', 'batch_id', 'subject_id',
            'tags', 'status', 'is_featured', 'sort_order',
            'published_at', 'expires_at', 'created_by'
        ];
        
        $insertFields = [];
        $insertValues = [];
        $params = [];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $insertFields[] = $field;
                $insertValues[] = ":$field";
                $params[$field] = $data[$field];
            }
        }
        
        $sql = "INSERT INTO study_materials (" . implode(', ', $insertFields) . ") 
                VALUES (" . implode(', ', $insertValues) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update material
     */
    public function update($id, $data) {
        $fields = [
            'title', 'description', 'category_id', 'subject_id', 'batch_id', 'course_id',
            'content_type', 'external_url', 'access_type', 'visibility',
            'status', 'is_featured', 'sort_order', 'expires_at', 'updated_by'
        ];
        
        $updates = [];
        $params = ['id' => $id];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($updates)) return false;
        
        $updates[] = "updated_at = NOW()";
        
        $sql = "UPDATE study_materials SET " . implode(', ', $updates) . " WHERE id = :id";
        if ($this->tenantId) {
            $sql .= " AND tenant_id = :tid";
            $params['tid'] = $this->tenantId;
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Soft delete material
     */
    public function delete($id, $userId) {
        $sql = "UPDATE study_materials SET deleted_at = NOW(), updated_by = :uid WHERE id = :id";
        $params = ['id' => $id, 'uid' => $userId];
        
        if ($this->tenantId) {
            $sql .= " AND tenant_id = :tid";
            $params['tid'] = $this->tenantId;
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Get categories
     */
    public function getCategories($parentId = null) {
        $where = ["deleted_at IS NULL"];
        $params = [];
        
        if ($this->tenantId) {
            $where[] = "tenant_id = :tid";
            $params['tid'] = $this->tenantId;
        }
        
        if ($parentId !== null) {
            $where[] = "parent_id = :parent_id";
            $params['parent_id'] = $parentId;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->prepare("
            SELECT * FROM study_material_categories 
            WHERE $whereClause
            ORDER BY sort_order ASC, name ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get statistics
     */
    public function getStats() {
        $where = $this->tenantId ? "WHERE tenant_id = :tid" : "";
        $params = $this->tenantId ? ['tid' => $this->tenantId] : [];
        
        // Total materials
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM study_materials $where AND deleted_at IS NULL");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // By content type
        $stmt = $this->db->prepare("
            SELECT content_type, COUNT(*) as count 
            FROM study_materials 
            $where AND deleted_at IS NULL 
            GROUP BY content_type
        ");
        $stmt->execute($params);
        $byType = $stmt->fetchAll();
        
        // By category
        $sql = "
            SELECT c.name, c.color, COUNT(sm.id) as count
            FROM study_material_categories c
            LEFT JOIN study_materials sm ON c.id = sm.category_id AND sm.deleted_at IS NULL
            WHERE c.deleted_at IS NULL
        ";
        if ($this->tenantId) {
            $sql .= " AND c.tenant_id = :tid";
        }
        $sql .= " GROUP BY c.id ORDER BY count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $byCategory = $stmt->fetchAll();
        
        // Total downloads and views
        $stmt = $this->db->prepare("
            SELECT SUM(download_count) as total_downloads, SUM(view_count) as total_views
            FROM study_materials 
            $where AND deleted_at IS NULL
        ");
        $stmt->execute($params);
        $totals = $stmt->fetch();
        
        return [
            'total_materials' => $total,
            'by_type' => $byType,
            'by_category' => $byCategory,
            'total_downloads' => $totals['total_downloads'] ?? 0,
            'total_views' => $totals['total_views'] ?? 0
        ];
    }
    
    /**
     * Log access
     */
    public function logAccess($materialId, $userId, $userType, $action) {
        $stmt = $this->db->prepare("
            INSERT INTO study_material_access_logs 
            (tenant_id, material_id, user_id, user_type, action, ip_address, user_agent)
            VALUES (:tid, :mid, :uid, :utype, :action, :ip, :ua)
        ");
        
        return $stmt->execute([
            'tid' => $this->tenantId,
            'mid' => $materialId,
            'uid' => $userId,
            'utype' => $userType,
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Increment view count
     */
    public function incrementViews($id) {
        $stmt = $this->db->prepare("UPDATE study_materials SET view_count = view_count + 1 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Increment download count
     */
    public function incrementDownloads($id) {
        $stmt = $this->db->prepare("UPDATE study_materials SET download_count = download_count + 1 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
