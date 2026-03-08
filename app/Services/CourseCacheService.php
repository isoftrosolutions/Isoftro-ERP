<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class CourseCacheService {
    protected $ttl = 21600; // 6 hours
    
    public function getCourses($tenantId) {
        $cacheKey = "course:list:tenant_{$tenantId}";
        
        $manager = app(CacheManager::class);
        return $manager->remember($cacheKey, $this->ttl, function() use ($tenantId) {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT * FROM courses WHERE tenant_id = ? AND is_active = 1 AND deleted_at IS NULL ORDER BY name");
            $stmt->execute([$tenantId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }

    public function invalidate($tenantId) {
        try {
            Redis::del("course:list:tenant_{$tenantId}");
        } catch (\Throwable $e) {}
        
        if (class_exists('\App\Services\DashboardCacheService')) {
            try {
                (new \App\Services\DashboardCacheService(new \App\Services\CacheManager()))->invalidate($tenantId);
            } catch (\Exception $e) {}
        }
    }
}
