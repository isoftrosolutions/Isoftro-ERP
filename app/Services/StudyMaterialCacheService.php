<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

/**
 * Study Material Cache Service
 * Handles caching for study materials, categories, and statistics
 */
class StudyMaterialCacheService {
    protected $ttl = 900; // 15 minutes default
    protected $longTtl = 3600; // 1 hour for slowly changing data
    
    /**
     * Get cached categories for a tenant
     */
    public function getCategories($tenantId) {
        $cacheKey = "study_material:categories:tenant_{$tenantId}";
        
        $manager = app(CacheManager::class);
        return $manager->remember($cacheKey, $this->longTtl, function() use ($tenantId) {
            $db = getDBConnection();
            $stmt = $db->prepare("
                SELECT c.*, COUNT(sm.id) as material_count
                FROM study_material_categories c
                LEFT JOIN study_materials sm ON c.id = sm.category_id 
                    AND sm.deleted_at IS NULL 
                    AND sm.status = 'active'
                WHERE c.tenant_id = :tid AND c.deleted_at IS NULL AND c.status = 'active'
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.name ASC
            ");
            $stmt->execute(['tid' => $tenantId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }
    
    /**
     * Get cached material list for admin
     */
    public function getAdminMaterialsList($tenantId, $filters = [], $page = 1, $perPage = 20) {
        $filterHash = md5(json_encode($filters));
        $cacheKey = "study_material:admin:list:tenant_{$tenantId}:{$filterHash}:page_{$page}:per_{$perPage}";
        
        // Don't cache if there are active filters
        if (!empty($filters)) {
            return null; // Let controller handle uncached
        }
        
        $manager = app(CacheManager::class);
        return $manager->remember($cacheKey, $this->ttl, function() use ($tenantId, $filters, $page, $perPage) {
            // This is just a cache key generator, actual data fetched in controller
            return ['cached' => true];
        });
    }
    
    /**
     * Get cached statistics
     */
    public function getStats($tenantId) {
        $cacheKey = "study_material:stats:tenant_{$tenantId}";
        
        $manager = app(CacheManager::class);
        return $manager->remember($cacheKey, $this->ttl, function() use ($tenantId) {
            $db = getDBConnection();
            
            // Get totals
            $totalStmt = $db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(download_count) as total_downloads,
                    SUM(view_count) as total_views
                FROM study_materials
                WHERE tenant_id = :tid AND deleted_at IS NULL
            ");
            $totalStmt->execute(['tid' => $tenantId]);
            $totals = $totalStmt->fetch(\PDO::FETCH_ASSOC);
            
            // Get by category
            $catStmt = $db->prepare("
                SELECT c.name, c.color, COUNT(sm.id) as count
                FROM study_material_categories c
                LEFT JOIN study_materials sm ON c.id = sm.category_id AND sm.deleted_at IS NULL
                WHERE c.tenant_id = :tid AND c.deleted_at IS NULL
                GROUP BY c.id ORDER BY count DESC
            ");
            $catStmt->execute(['tid' => $tenantId]);
            $byCategory = $catStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Get by type
            $typeStmt = $db->prepare("
                SELECT content_type, COUNT(*) as count
                FROM study_materials
                WHERE tenant_id = :tid AND deleted_at IS NULL
                GROUP BY content_type
            ");
            $typeStmt->execute(['tid' => $tenantId]);
            $byType = $typeStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return [
                'totals' => $totals,
                'by_category' => $byCategory,
                'by_type' => $byType
            ];
        });
    }
    
    /**
     * Get cached student-accessible materials count
     */
    public function getStudentMaterialsCount($tenantId) {
        $cacheKey = "study_material:student:count:tenant_{$tenantId}";
        
        $manager = app(CacheManager::class);
        return $manager->remember($cacheKey, $this->ttl, function() use ($tenantId) {
            $db = getDBConnection();
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM study_materials 
                WHERE tenant_id = :tid 
                AND deleted_at IS NULL 
                AND status = 'active'
                AND (published_at IS NULL OR published_at <= NOW())
                AND (expires_at IS NULL OR expires_at >= NOW())
            ");
            $stmt->execute(['tid' => $tenantId]);
            return (int) $stmt->fetchColumn();
        });
    }
    
    /**
     * Invalidate all study material caches for a tenant
     */
    public function invalidate($tenantId) {
        try {
            // Delete category cache
            Redis::del("study_material:categories:tenant_{$tenantId}");
            
            // Delete stats cache
            Redis::del("study_material:stats:tenant_{$tenantId}");
            
            // Delete student count cache
            Redis::del("study_material:student:count:tenant_{$tenantId}");
            
            // Delete all admin list caches (pattern-based)
            $pattern = "study_material:admin:list:tenant_{$tenantId}:*";
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
            
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("StudyMaterial Cache Invalidate Error: " . $e->getMessage());
        }
    }
    
    /**
     * Invalidate specific material cache
     */
    public function invalidateMaterial($tenantId, $materialId) {
        // Material-specific caches would be invalidated here
        // For now, just invalidate general caches
        $this->invalidate($tenantId);
    }
}
