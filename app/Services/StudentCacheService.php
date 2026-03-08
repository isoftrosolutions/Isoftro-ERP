<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\Redis;

class StudentCacheService {
    protected $ttl = 1800; // 30 minutes
    
    public function getStudent($studentId, $tenantId) {
        $cacheKey = "student:{$studentId}:tenant_{$tenantId}";
        
        try {
            $student = Redis::hgetall($cacheKey);
        } catch (\Throwable $e) {
            $student = [];
        }
        
        if (empty($student)) {
            $model = new Student();
            $studentData = $model->find($studentId);
            
            if (!$studentData || $studentData['tenant_id'] != $tenantId) {
                return null;
            }
            
            // Format for Hash storage (Redis HSET needs string scalar values)
            $formatted = [];
            foreach ($studentData as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $formatted[$key] = json_encode($value);
                } elseif ($value === null) {
                    $formatted[$key] = ''; // Hashes cannot store null
                } else {
                    $formatted[$key] = (string)$value;
                }
            }
            
            try {
                Redis::hmset($cacheKey, $formatted);
                Redis::expire($cacheKey, $this->ttl);
            } catch (\Throwable $e) {}
            
            return $formatted;
        }
        
        return $student;
    }
    
    public function getStudentsList($tenantId) {
        $cacheKey = "students:list:tenant_{$tenantId}";
        
        $manager = app(CacheManager::class);
        return $manager->remember($cacheKey, $this->ttl, function() use ($tenantId) {
            $model = new Student();
            return $model->getByTenant($tenantId);
        });
    }
    
    public function invalidate($studentId, $tenantId) {
        try {
            Redis::del("student:{$studentId}:tenant_{$tenantId}");
            Redis::del("students:list:tenant_{$tenantId}");
        } catch (\Throwable $e) {}
    }
}
