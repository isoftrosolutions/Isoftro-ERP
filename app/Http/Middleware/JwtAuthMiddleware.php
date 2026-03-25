<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * JWT Authentication Middleware
 * 
 * Handles token extraction from both Authorization header and cookie.
 * Validates via tymon/jwt-auth only — does NOT call IdentifyTenant internally
 * (which caused a recursive auth() call and TokenInvalidException).
 * 
 * Tenant validation is derived purely from JWT claims — no subdomain lookup
 * inside this middleware to prevent circular dependencies.
 */
class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Step 1: Extract token from Bearer header OR HttpOnly cookie
            $token = $request->bearerToken();

            if (!$token && $request->hasCookie('token')) {
                $token = $request->cookie('token');
                // Inject into Authorization header so tymon's parser can find it
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token not found. Please login.',
                ], 401);
            }

            // Step 2: Validate token via tymon (signature + expiry + blacklist)
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or account deleted.',
                ], 401);
            }

            // Step 3: Tenant isolation — read from JWT payload, NOT from subdomain
            // (IdentifyTenant is intentionally NOT called here — it caused recursive auth() calls)
            $payload = JWTAuth::parseToken()->getPayload();
            $tokenTenantId = $payload->get('tenant_id');

            if (!$user->isSuperAdmin()) {
                if (empty($tokenTenantId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token is missing tenant context. Please re-login.',
                    ], 403);
                }

                // Bind resolved tenant_id to request for downstream controllers
                $request->merge(['_tenant_id' => $tokenTenantId]);
            }

            // Step 4: Populate $_SESSION for legacy PHP views still reading session
            // This is a transitional bridge — gradually remove as views are modernised
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['userData'] = [
                    'id'        => $user->id,
                    'email'     => $user->email,
                    'name'      => $user->name,
                    'role'      => $user->role,
                    'tenant_id' => $user->tenant_id,
                    'is_jwt'    => true,
                ];
                if ($tokenTenantId) {
                    $_SESSION['tenant_id'] = $tokenTenantId;
                }
            }

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired. Please refresh your token or re-login.',
                'code'    => 'TOKEN_EXPIRED',
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid.',
                'code'    => 'TOKEN_INVALID',
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token error: ' . $e->getMessage(),
                'code'    => 'TOKEN_ERROR',
            ], 401);
        }

        return $next($request);
    }
}
