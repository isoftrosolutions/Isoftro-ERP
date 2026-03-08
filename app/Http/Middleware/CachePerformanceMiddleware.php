<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CachePerformanceMiddleware {
    public function handle($request, Closure $next) {
        $startTime = microtime(true);
        
        try {
            DB::connection()->enableQueryLog();
        } catch (\Exception $e) {}
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $queries = [];
        try {
            $queries = DB::connection()->getQueryLog();
        } catch (\Exception $e) {}
        
        try {
            $info = Redis::info('stats');
            $hits = $info['keyspace_hits'] ?? 0;
        } catch (\Exception $e) {
            $hits = 0;
        }
        
        try {
            Log::channel('daily')->info('Request Performance', [
                'endpoint' => $request->path(),
                'response_time' => ($endTime - $startTime) * 1000,
                'db_queries' => count($queries),
                'cache_hits' => $hits,
            ]);
        } catch (\Exception $e) {}
        
        return $response;
    }
}
