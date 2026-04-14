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
        // Support both 'email' and 'username' (standard JS) field names
        $emailKey = $request->has('username') ? 'username' : 'email';
        
        $validator = Validator::make($request->all(), [
            $emailKey => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide valid credentials.',
                'errors' => $validator->errors()
            ], 422);
        }

        $email    = $request->input($emailKey);
        $password = $request->password;

        // Look up the user WITHOUT the TenantScoped global scope.
        // IdentifyTenant middleware may have already written $_SESSION['userData']
        // from a JWT cookie belonging to a different user/tenant, which would cause
        // TenantScoped to add an incorrect WHERE tenant_id clause and silently drop
        // the real user from the result set — producing a false 401.
        $user = \App\Models\User::withoutGlobalScope('tenant')
            ->where('email', $email)
            ->first();

        // ── TEMP DEBUG (remove after fix confirmed) ──────────────────
        if (!$user) {
            $softDeleted = \App\Models\User::withoutGlobalScope('tenant')->withTrashed()->where('email', $email)->first();
            return response()->json([
                'success'      => false,
                'message'      => 'Invalid email or password',
                '_debug'       => 'user_not_found',
                '_email'       => $email,
                '_soft_deleted'=> $softDeleted ? true : false,
                '_deleted_at'  => $softDeleted?->deleted_at,
                '_status'      => $softDeleted?->status,
            ], 401);
        }
        $hashVal = $user->password_hash;
        if (!$hashVal) {
            return response()->json(['success' => false, 'message' => 'Invalid email or password', '_debug' => 'password_hash_null', '_cols' => array_keys($user->getAttributes())], 401);
        }
        if (!Hash::check($password, $hashVal)) {
            return response()->json(['success' => false, 'message' => 'Invalid email or password', '_debug' => 'hash_mismatch', '_algo' => password_get_info($hashVal)['algoName']], 401);
        }
        // ─────────────────────────────────────────────────────────────

        if (!$user || !Hash::check($password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password'
            ], 401);
        }

        $token = auth('api')->login($user);
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication error. Please try again.'
            ], 500);
        }

        // Check if user's tenant is active (if not super admin)
        if (!$user->isSuperAdmin() && $user->tenant) {
            if ($user->tenant->status !== 'active') {
                auth('api')->logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Your tenant account is prohibited or inactive. Please contact support.'
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
     * Change password for the authenticated user.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'old_password'     => 'required|string',
            'new_password'     => 'required|string|min:8',
            'confirm_password' => 'required|string|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if (!Hash::check($request->old_password, $user->getAuthPassword())) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->password_hash = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully!',
        ]);
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
        $baseUrl = defined('APP_URL') ? APP_URL : '/erp';

        // Role-based redirection logic
        $role = str_replace(['_', ' '], '-', strtolower($user->role));
        $roleSlugMap = [
            'superadmin' => 'super-admin',
            'instituteadmin' => 'admin',
            'frontdesk' => 'front-desk',
            'teacher' => 'teacher',
            'student' => 'student',
            'guardian' => 'guardian',
        ];
        $slug = $roleSlugMap[$user->role] ?? $role;
        $redirectUrl = $baseUrl . '/dash/' . $slug;

        // Calculate cookie domain – support subdomains if on a proper domain
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $cookieDomain = null;
        if ($host && !in_array($host, ['localhost', '127.0.0.1'])) {
            $parts = explode('.', $host);
            if (count($parts) >= 2) {
                $cookieDomain = '.' . implode('.', array_slice($parts, -2));
            }
        }

        return response()->json([
            'success'        => true,
            'access_token'   => $token,
            'token_type'     => 'bearer',
            'expires_in'     => $ttl,
            'redirect'       => $redirectUrl,
            'user' => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $user->role,
                'tenant_id'      => $user->tenant_id,
                'is_impersonating' => $user->isImpersonating(),
            ]
        ])->cookie(
            'token',
            $token,
            $ttl / 60,   // Laravel cookie() expects minutes
            '/',
            $cookieDomain,
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            true,        // HttpOnly — prevents JS from reading the token (XSS protection)
            false,       // Raw
            'Lax'        // SameSite=Lax — allows cookie on top-level navigations (loading screen redirect)
        );
    }
}
