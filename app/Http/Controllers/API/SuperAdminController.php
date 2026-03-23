<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        // Support both JWT (api) and Session (web) during transition
        $this->middleware('auth:api,web');
        $this->middleware('super_admin');
    }

    /**
     * List all tenants
     */
    public function tenants(): JsonResponse
    {
        $tenants = Tenant::with(['users' => function($query) {
            $query->where('role', 'instituteadmin');
        }])
        ->withCount('users')
        ->get();

        return response()->json([
            'success' => true,
            'tenants' => $tenants
        ]);
    }

    /**
     * Impersonate a tenant admin
     */
    public function impersonate(Request $request, $userId): JsonResponse
    {
        $superAdmin = auth('api')->user();
        
        $targetUser = User::findOrFail($userId);

        if ($targetUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot impersonate another super admin'
            ], 403);
        }

        $targetUser->update([
            'impersonated_by' => $superAdmin->id,
            'impersonation_started_at' => now()
        ]);

        $token = auth('api')->login($targetUser);
        $ttl = auth('api')->factory()->getTTL() * 60;

        return response()->json([
            'success' => true,
            'message' => "Now impersonating {$targetUser->name}",
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $ttl,
            'impersonated_user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'role' => $targetUser->role,
                'tenant_id' => $targetUser->tenant_id,
                'tenant' => $targetUser->tenant,
            ],
            'original_admin' => [
                'id' => $superAdmin->id,
                'name' => $superAdmin->name,
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

    /**
     * Stop impersonation and return to super admin
     */
    public function stopImpersonation(): JsonResponse
    {
        $currentUser = auth('api')->user();

        if (!$currentUser->isImpersonating()) {
            return response()->json([
                'success' => false,
                'message' => 'Not currently impersonating anyone'
            ], 400);
        }

        $superAdmin = User::findOrFail($currentUser->impersonated_by);

        $currentUser->update([
            'impersonated_by' => null,
            'impersonation_started_at' => null
        ]);

        auth('api')->logout();

        $token = auth('api')->login($superAdmin);
        $ttl = auth('api')->factory()->getTTL() * 60;

        return response()->json([
            'success' => true,
            'message' => 'Impersonation stopped. Returned to super admin account.',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $ttl,
            'user' => [
                'id' => $superAdmin->id,
                'name' => $superAdmin->name,
                'email' => $superAdmin->email,
                'role' => $superAdmin->role,
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

    /**
     * Manage tenant modules
     */
    public function assignModules(Request $request, $tenantId): JsonResponse
    {
        $request->validate([
            'module_ids' => 'required|array',
            'module_ids.*' => 'exists:modules,id'
        ]);

        // Clear existing modules
        DB::table('institute_modules')
            ->where('tenant_id', $tenantId)
            ->delete();

        foreach ($request->module_ids as $moduleId) {
            DB::table('institute_modules')->insert([
                'tenant_id' => $tenantId,
                'module_id' => $moduleId,
                'is_enabled' => true,
                'updated_at' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Modules assigned successfully'
        ]);
    }

    /**
     * Get dashboard stats
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_tenants' => Tenant::withoutGlobalScopes()->count(),
            'active_tenants' => Tenant::withoutGlobalScopes()->where('status', 'active')->count(),
            'total_users' => User::withoutGlobalScopes()->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Create a new tenant
     */
    public function saveTenant(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|unique:tenants,subdomain|max:100',
            'adminEmail' => 'required|email|unique:users,email',
            'adminPass' => 'required|string|min:8',
            'instituteType' => 'required|string',
            'plan' => 'string|nullable',
            'status' => 'string|nullable',
        ]);

        try {
            DB::beginTransaction();

            $currentUserId = auth('api')->id();

            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $request->name,
                'nepali_name' => $request->nepaliName,
                'subdomain' => $request->subdomain,
                'institute_type' => $request->instituteType,
                'brand_color' => $request->brandColor ?? '#009E7E',
                'tagline' => $request->tagline,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
                'pan_number' => $request->panNumber,
                'plan' => $request->plan ?? 'starter',
                'status' => $request->status ?? 'trial',
                'created_by' => $currentUserId,
            ]);

            // Handle Logo
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = 'logo_' . $tenant->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/logos'), $filename);
                $tenant->update(['logo_path' => '/public/uploads/logos/' . $filename]);
            }

            // 2. Create Admin User
            $admin = User::create([
                'tenant_id' => $tenant->id,
                'role' => 'instituteadmin',
                'email' => $request->adminEmail,
                'password' => $request->adminPass, // Model should handle hashing or use Hash::make
                'name' => $request->adminName ?? 'Admin',
                'phone' => $request->adminPhone,
                'status' => 'active',
            ]);

            // 3. Assign Features
            if ($request->has('features') && is_array($request->features)) {
                foreach ($request->features as $featureId) {
                    DB::table('institute_feature_access')->insert([
                        'tenant_id' => $tenant->id,
                        'feature_id' => $featureId,
                        'is_enabled' => true,
                        'updated_at' => now()
                    ]);
                }
            }

            // 4. Audit Log
            DB::table('audit_logs')->insert([
                'user_id' => $currentUserId,
                'action' => 'Tenant Created',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => "New tenant '{$tenant->name}' ({$tenant->subdomain}) created via JWT API.",
                'created_at' => now()
            ]);

            DB::commit();

            // 5. Send Welcome Email
            try {
                $loginUrl = url('/auth/login');
                $welcomeData = [
                    'institute_name' => $tenant->name,
                    'admin_name'     => $admin->name,
                    'admin_email'    => $admin->email,
                    'admin_pass'     => $request->adminPass,
                    'subdomain'      => $tenant->subdomain,
                    'login_url'      => $loginUrl
                ];
                
                $tpl = \App\Helpers\MailHelper::getStaticTemplate('tenant_welcome', $welcomeData);
                if ($tpl) {
                    \App\Helpers\MailHelper::sendDirect(DB::getPdo(), $tenant->id, $admin->email, $admin->name, $tpl['subject'], $tpl['body'], '', 0, true);
                }
            } catch (\Exception $e) {
                // Log email error but don't fail the request
                \Log::error("Welcome email failed: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Institute registered successfully!',
                'tenantId' => $tenant->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing tenant
     */
    public function updateTenant(Request $request): JsonResponse
    {
        $id = $request->id ?? $request->route('id');
        
        $request->validate([
            'id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|unique:tenants,subdomain,' . $id,
            'instituteType' => 'required|string',
        ]);

        try {
            DB::beginTransaction();
            $currentUserId = auth('api')->id();
            
            $tenant = Tenant::findOrFail($id);
            $tenant->update([
                'name' => $request->name,
                'nepali_name' => $request->nepaliName,
                'subdomain' => $request->subdomain,
                'institute_type' => $request->instituteType,
                'brand_color' => $request->brandColor,
                'tagline' => $request->tagline,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
                'pan_number' => $request->panNumber,
                'plan' => $request->plan,
                'status' => $request->status,
                'student_limit' => $request->student_limit ?? $tenant->student_limit,
                'sms_credits' => $request->sms_credits ?? $tenant->sms_credits,
            ]);

            // Handle Logo
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = 'logo_' . $tenant->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/logos'), $filename);
                $tenant->update(['logo_path' => '/public/uploads/logos/' . $filename]);
            }

            // Sync Features
            if ($request->has('features')) {
                DB::table('institute_feature_access')->where('tenant_id', $tenant->id)->delete();
                $features = is_array($request->features) ? $request->features : [];
                foreach ($features as $featureId) {
                    DB::table('institute_feature_access')->insert([
                        'tenant_id' => $tenant->id,
                        'feature_id' => $featureId,
                        'is_enabled' => true,
                        'updated_at' => now()
                    ]);
                }
            }

            // Audit Log
            DB::table('audit_logs')->insert([
                'user_id' => $currentUserId,
                'action' => 'Tenant Updated',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => "Tenant '{$tenant->name}' updated via JWT API.",
                'created_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Institute updated successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a tenant (Soft Delete)
     */
    public function deleteTenant(Request $request, $id = null): JsonResponse
    {
        $id = $id ?? $request->id;
        try {
            DB::beginTransaction();
            $currentUserId = auth('api')->id();
            
            $tenant = Tenant::findOrFail($id);
            $tenant->update([
                'deleted_at' => now(),
                'status' => 'suspended'
            ]);

            // Suspend users
            User::where('tenant_id', $id)->update(['status' => 'suspended']);

            // Audit Log
            DB::table('audit_logs')->insert([
                'user_id' => $currentUserId,
                'action' => 'Tenant Deleted',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => "Tenant '{$tenant->name}' (ID: $id) soft-deleted via JWT API.",
                'created_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Institute deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Suspend a tenant
     */
    public function suspendTenant($id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'suspended']);
        User::where('tenant_id', $id)->update(['status' => 'suspended']);
        
        return response()->json(['success' => true, 'message' => 'Tenant suspended']);
    }

    /**
     * Activate a tenant
     */
    public function activateTenant($id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => 'active']);
        User::where('tenant_id', $id)->update(['status' => 'active']);
        
        return response()->json(['success' => true, 'message' => 'Tenant activated']);
    }

    /**
     * Update tenant plan
     */
    public function updatePlan(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:tenants,id',
            'plan' => 'required|string',
        ]);

        $tenant = Tenant::findOrFail($request->id);
        $tenant->update(['plan' => $request->plan]);

        return response()->json([
            'success' => true,
            'message' => 'Plan updated successfully'
        ]);
    }
}
