<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JwtAuthMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Check for token in Header (Bearer) or Cookie (token)
            $token = $request->bearerToken() ?: $request->cookie('token');
            
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Authorization Token not found'], 401);
            }

            // Set the token for subsequent calls
            $user = auth('api')->setToken($token)->user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 401);
            }

            // --- TENANT VALIDATION ---
            // 1. Get tenant from token claim
            $payload = auth('api')->getPayload();
            $tokenTenantId = $payload->get('tenant_id');

            // 2. Resolve tenant from subdomain (existing logic)
            $identifyTenant = new \App\Http\Middleware\IdentifyTenant();
            $resolvedTenant = $identifyTenant->handle(); // This still sets $_SESSION for now, we'll fix later
            $resolvedTenantId = $resolvedTenant['id'] ?? null;

            // 3. Ensure token tenant matches resolved tenant (unless super admin)
            if ($user->role !== 'superadmin' && $user->role !== 'super-admin') {
                if ($tokenTenantId != $resolvedTenantId) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Tenant mismatch. You are not authorized to access this tenant.'
                    ], 403);
                }
            }

            // 4. Inject user and tenant into session for legacy compatibility DURING transition
            // We will eventually remove this, but many views still use $_SESSION
            $_SESSION['userData'] = [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'tenant_id' => $user->tenant_id,
                'is_jwt' => true
            ];
            
            if ($resolvedTenantId) {
                $_SESSION['tenant_id'] = $resolvedTenantId;
                
                // CRITICAL: Load features for the legacy hasFeature() helper
                if (function_exists('loadFeatures')) {
                    loadFeatures($resolvedTenantId);
                }
            }

        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json(['success' => false, 'message' => 'Token is Invalid'], 401);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json(['success' => false, 'message' => 'Token is Expired'], 401);
            } else {
                return response()->json(['success' => false, 'message' => 'Authorization Token not found or error occurred'], 401);
            }
        }

        return $next($request);
    }
}
