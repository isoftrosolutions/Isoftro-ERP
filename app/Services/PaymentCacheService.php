<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class PaymentCacheService {
    public function getDuesSummary($tenantId) {
        $cacheKey = "payment:dues:summary:tenant_{$tenantId}";
        
        $manager = app(CacheManager::class);
        return $manager->remember($cacheKey, 600, function() use ($tenantId) { // 10 min TTL
            $db = getDBConnection();
            $stmt = $db->prepare("
                SELECT 
                    SUM(balance) as total_dues,
                    COUNT(DISTINCT student_id) as student_count,
                    SUM(CASE WHEN DATEDIFF(NOW(), due_date) <= 30 THEN balance ELSE 0 END) as aging_0_30,
                    SUM(CASE WHEN DATEDIFF(NOW(), due_date) BETWEEN 31 AND 60 THEN balance ELSE 0 END) as aging_31_60,
                    SUM(CASE WHEN DATEDIFF(NOW(), due_date) BETWEEN 61 AND 90 THEN balance ELSE 0 END) as aging_61_90,
                    SUM(CASE WHEN DATEDIFF(NOW(), due_date) > 90 THEN balance ELSE 0 END) as aging_90_plus
                FROM payments
                WHERE tenant_id = ? AND status = 'pending'
            ");
            $stmt->execute([$tenantId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        });
    }
    
    public function invalidateOnPayment($tenantId, $studentId) {
        try {
            Redis::del("payment:dues:summary:tenant_{$tenantId}");
        } catch (\Throwable $e) {}
        
        if (class_exists('\App\Services\StudentCacheService')) {
            try {
                (new \App\Services\StudentCacheService())->invalidate($studentId, $tenantId);
            } catch (\Exception $e) {}
        }
        
        if (class_exists('\App\Services\DashboardCacheService')) {
            try {
                (new \App\Services\DashboardCacheService(new \App\Services\CacheManager()))->invalidate($tenantId);
            } catch (\Exception $e) {}
        }
    }
}
