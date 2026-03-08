<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class MonitorTenantCacheCommand extends Command {
    protected $signature = 'cache:monitor-tenant';
    protected $description = 'Monitor per-tenant Redis cache usage footprint';

    public function handle() {
        if (!function_exists('getDBConnection')) {
            $this->error('Database connection function unavailable.');
            return;
        }

        $db = getDBConnection();
        $stmt = $db->query("SELECT id FROM tenants WHERE status = 'active'");
        $tenants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($tenants as $tenant) {
            $tenantId = $tenant['id'];
            $keys = Redis::keys("*:tenant_{$tenantId}*");
            $memoryUsed = 0;
            
            foreach ($keys as $key) {
                // Extract actual key ignoring framework prefixes in some configurations
                $prefix = config('database.redis.options.prefix', '');
                $realKey = str_replace($prefix, '', $key);
                
                try {
                    $memoryUsed += (int) Redis::memory('usage', $realKey);
                } catch (\Exception $e) {}
            }
            
            $memoryMB = round($memoryUsed / 1024 / 1024, 2);
            $this->info("Tenant {$tenantId}: {$memoryMB} MB used");
            
            if ($memoryMB > 10) { 
                Log::warning("Tenant {$tenantId} cache usage high: {$memoryMB} MB");
                $this->warn("Tenant {$tenantId} cache usage high: {$memoryMB} MB");
            }
        }
        
        $this->info("Cache tenant usage scan completed!");
    }
}
