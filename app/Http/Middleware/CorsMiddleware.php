<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $allowedOrigins = config('cors.allowed_origins', []);
        $allowedPatterns = config('cors.allowed_origins_patterns', []);
        
        $origin = $request->header('Origin');
        
        if ($origin) {
            $isAllowed = false;
            
            // Check exact matches
            if (in_array($origin, $allowedOrigins)) {
                $isAllowed = true;
            }
            
            // Check pattern matches
            foreach ($allowedPatterns as $pattern) {
                if (@preg_match($pattern, $origin)) {
                    $isAllowed = true;
                    break;
                }
            }
            
            // In development, allow localhost
            if (!$isAllowed && app()->environment('local', 'development')) {
                if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
                    $isAllowed = true;
                }
            }
            
            if ($isAllowed) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Methods', implode(', ', config('cors.allowed_methods', [])));
                $response->headers->set('Access-Control-Allow-Headers', implode(', ', config('cors.allowed_headers', [])));
                $response->headers->set('Access-Control-Expose-Headers', implode(', ', config('cors.exposed_headers', [])));
                $response->headers->set('Access-Control-Max-Age', config('cors.max_age', 86400));
                
                if (config('cors.supports_credentials', false)) {
                    $response->headers->set('Access-Control-Allow-Credentials', 'true');
                }
            }
        }

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            if (!$origin) {
                return response()->json([], 403);
            }

            $isAllowed = in_array($origin, $allowedOrigins, true);
            if (!$isAllowed) {
                foreach ($allowedPatterns as $pattern) {
                    if (@preg_match($pattern, $origin)) {
                        $isAllowed = true;
                        break;
                    }
                }
            }

            if (!$isAllowed) {
                return response()->json([], 403);
            }

            return response()->json([], 200)->withHeaders([
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Methods' => implode(', ', config('cors.allowed_methods', [])),
                'Access-Control-Allow-Headers' => implode(', ', config('cors.allowed_headers', [])),
                'Access-Control-Max-Age' => config('cors.max_age', 86400),
                'Vary' => 'Origin',
            ]);
        }

        return $response;
    }
}
