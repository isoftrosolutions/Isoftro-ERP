<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class SessionCacheService {
    protected $ttl = 7200; // 2 hours
    
    public function storeSession($userId, $sessionData) {
        $cacheKey = "session:user_{$userId}";
        
        // Ensure values are strings
        $formatted = [];
        foreach ($sessionData as $key => $val) {
            $formatted[$key] = is_scalar($val) ? (string)$val : json_encode($val);
        }
        
        try {
            Redis::hmset($cacheKey, $formatted);
            Redis::expire($cacheKey, $this->ttl);
        } catch (\Throwable $e) {}
    }
    
    public function getSession($userId) {
        try {
            return Redis::hgetall("session:user_{$userId}");
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    public function refreshSession($userId) {
        try {
            Redis::expire("session:user_{$userId}", $this->ttl);
        } catch (\Throwable $e) {}
    }
    
    public function destroySession($userId) {
        try {
            Redis::del("session:user_{$userId}");
        } catch (\Throwable $e) {}
    }
}
