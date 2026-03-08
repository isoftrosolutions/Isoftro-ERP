<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class TenantCacheGuard {
    public function get($key, $tenantId) {
        if (!str_contains($key, "tenant_{$tenantId}")) {
            throw new \Exception("Tenant isolation violation: key must contain tenant_{$tenantId}");
        }
        
        try {
            return Redis::get($key);
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    public function set($key, $value, $ttl, $tenantId) {
        if (!str_contains($key, "tenant_{$tenantId}")) {
            throw new \Exception("Tenant isolation violation: key must contain tenant_{$tenantId}");
        }
        
        try {
            return Redis::setex($key, $ttl, $value);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
