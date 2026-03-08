<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class BatchCacheService {
    protected $ttl = 1800; // 30 minutes
    
    public function getBatches($tenantId) {
        $cacheKey = "batch:list:tenant_{$tenantId}";
        
        $manager = app(CacheManager::class);
        return $manager->remember($cacheKey, $this->ttl, function() use ($tenantId) {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT * FROM batches WHERE tenant_id = ? AND status = 'active' AND deleted_at IS NULL ORDER BY name");
            $stmt->execute([$tenantId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }

    public function getBatchStudents($batchId, $tenantId) {
        $cacheKey = "batch:{$batchId}:tenant_{$tenantId}:students";
        
        $manager = app(CacheManager::class);
        return $manager->remember($cacheKey, $this->ttl, function() use ($batchId, $tenantId) {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT id FROM students WHERE batch_id = ? AND tenant_id = ? AND status = 'active' AND deleted_at IS NULL");
            $stmt->execute([$batchId, $tenantId]);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        });
    }

    public function invalidate($tenantId, $batchId = null) {
        try {
            Redis::del("batch:list:tenant_{$tenantId}");
            if ($batchId) {
                Redis::del("batch:{$batchId}:tenant_{$tenantId}:students");
            }
        } catch (\Throwable $e) {}
        
        if (class_exists('\App\Services\DashboardCacheService')) {
            try {
                (new \App\Services\DashboardCacheService(new \App\Services\CacheManager()))->invalidate($tenantId);
            } catch (\Exception $e) {}
        }
    }
}
