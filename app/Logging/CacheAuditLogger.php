<?php

namespace App\Logging;

use Illuminate\Support\Facades\Log;

class CacheAuditLogger {
    public static function logAccess($action, $key, $userId, $tenantId) {
        try {
            $ip = request() ? request()->ip() : 'cli';
            Log::channel('daily')->info('Cache Audit Access', [
                'action' => $action,  
                'key' => $key,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'ip' => $ip,
                'timestamp' => date('c'),
            ]);
        } catch (\Exception $e) {
            // Failsafe cache logging exception catch
        }
    }
}
