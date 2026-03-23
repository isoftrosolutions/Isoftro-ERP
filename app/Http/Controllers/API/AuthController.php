<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        // JWT Auth attempt using custom password column (if configured in User model)
        // Since we overridden getAuthPasswordName, attempt() should work with password key
        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password'
            ], 401);
        }

        $user = auth('api')->user();

        // Check if user's tenant is active (if not super admin)
        if (!$user->isSuperAdmin() && $user->tenant) {
            if ($user->tenant->status !== 'active') {
                auth('api')->logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Your tenant account is not active. Please contact support.'
                ], 403);
            }
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'tenant_id' => $user->tenant_id,
                'tenant' => $user->tenant,
                'is_impersonating' => $user->isImpersonating(),
                'impersonated_by' => $user->impersonated_by,
            ]
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ])->withoutCookie('token');
    }

    /**
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken($token): JsonResponse
    {
        $user = auth('api')->user();
        $ttl = auth('api')->factory()->getTTL() * 60;

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $ttl,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'tenant_id' => $user->tenant_id,
                'is_impersonating' => $user->isImpersonating(),
            ]
        ])->cookie(
            'token', 
            $token, 
            $ttl / 60, 
            '/', 
            null, 
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', 
            true, 
            false, 
            'Lax'
        );
    }
}
