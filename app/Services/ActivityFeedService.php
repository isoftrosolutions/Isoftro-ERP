<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class ActivityFeedService {
    protected $maxItems = 100;
    protected $ttl = 3600; // 1 hour
    
    public function addActivity($tenantId, $activity) {
        $cacheKey = "activity:tenant_{$tenantId}:recent";
        
        try {
            Redis::lpush($cacheKey, json_encode($activity));
            Redis::ltrim($cacheKey, 0, $this->maxItems - 1);
            Redis::expire($cacheKey, $this->ttl);
        } catch (\Throwable $e) {}
    }
    
    public function getRecentActivity($tenantId, $limit = 10) {
        $cacheKey = "activity:tenant_{$tenantId}:recent";
        
        try {
            $activities = Redis::lrange($cacheKey, 0, $limit - 1);
        } catch (\Throwable $e) {
            $activities = [];
        }
        
        if (empty($activities)) {
            return [];
        }
        
        return array_map(function($item) { 
            return json_decode($item, true); 
        }, $activities);
    }
}
