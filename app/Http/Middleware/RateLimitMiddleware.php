<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class RateLimitMiddleware
{
    protected $maxAttempts = 60;
    protected $decayMinutes = 1;
    
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $this->maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => Cache::get($key . ':timer', $this->decayMinutes * 60),
            ], 429);
        }
        
        Cache::put($key, $attempts + 1, now()->addMinutes($this->decayMinutes));
        if ($attempts === 0) {
            Cache::put($key . ':timer', $this->decayMinutes * 60, now()->addMinutes($this->decayMinutes));
        }
        
        return $next($request);
    }
    
    protected function resolveRequestSignature(Request $request): string
    {
        return 'rate_limit:' . sha1(
            $request->ip() . '|' . $request->path()
        );
    }
}

class LoginRateLimitMiddleware extends RateLimitMiddleware
{
    public function __construct()
    {
        $this->maxAttempts = 5;
        $this->decayMinutes = 15;
    }
}

class ApiRateLimitMiddleware extends RateLimitMiddleware
{
    public function __construct()
    {
        $this->maxAttempts = 100;
        $this->decayMinutes = 1;
    }
}
