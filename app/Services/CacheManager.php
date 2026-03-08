<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Closure;

class CacheManager {
    /**
     * Get data with cache-aside pattern
     */
    public function remember($key, $ttl, Closure $callback) {
        try {
            // Try cache first
            $value = Redis::get($key);
            
            if ($value !== null) {
                return $this->unserialize($value);
            }
            
            // Cache miss - fetch from source
            $value = $callback();
            
            // Store in cache
            Redis::setex($key, $ttl, $this->serialize($value));
            
            return $value;
        } catch (\Throwable $e) {
            // Graceful fallback to source if Redis is unavailable
            \Illuminate\Support\Facades\Log::warning("Redis Cache Error: " . $e->getMessage());
            return $callback();
        }
    }
    
    private function serialize($value) {
        return json_encode($value);
    }
    
    private function unserialize($value) {
        return json_decode($value, true);
    }
}
