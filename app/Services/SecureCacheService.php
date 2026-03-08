<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class SecureCacheService {
    public function setSecure($key, $value, $ttl = 1800) {
        $encrypted = Crypt::encryptString(json_encode($value));
        try {
            Redis::setex($key, $ttl, $encrypted);
        } catch (\Throwable $e) {}
    }
    
    public function getSecure($key) {
        try {
            $encrypted = Redis::get($key);
        } catch (\Throwable $e) {
            $encrypted = null;
        }
        
        if ($encrypted === null) {
            return null;
        }
        
        try {
            $decrypted = Crypt::decryptString($encrypted);
            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            Log::error("Cache decryption failed for key: {$key}");
            try {
                Redis::del($key); 
            } catch (\Throwable $ignore) {}
            return null;
        }
    }
}
