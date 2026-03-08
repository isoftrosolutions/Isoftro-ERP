<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DashboardCacheService;
use App\Services\CourseCacheService;
use App\Services\BatchCacheService;

class WarmCacheCommand extends Command {
    protected $signature = 'cache:warm';
    protected $description = 'Warm up Redis caches for active tenants';

    public function handle() {
        if (!function_exists('getDBConnection')) {
            $this->error('Database connection function unavailable.');
            return;
        }
        
        $db = getDBConnection();
        $stmt = $db->query("SELECT id FROM tenants WHERE status = 'active'");
        $tenants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $dashboardService = app(DashboardCacheService::class);
        $courseService = app(CourseCacheService::class);
        $batchService = app(BatchCacheService::class);
        
        foreach ($tenants as $tenant) {
            $tenantId = $tenant['id'];
            
            try {
                // Warm cache sequentially across main entities
                $dashboardService->getStats($tenantId);
                $courseService->getCourses($tenantId);
                $batchService->getBatches($tenantId);
                
                $this->info("Warmed cache for tenant {$tenantId}");
            } catch (\Exception $e) {
                $this->error("Failed to warm cache for tenant {$tenantId}: " . $e->getMessage());
            }
        }
        
        $this->info("Cache warming completed!");
    }
}
