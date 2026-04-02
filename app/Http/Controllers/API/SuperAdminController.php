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
                $tenant->update(['logo_path' => '/uploads/logos/' . $filename]);
            }

            // 2. Create Admin User
            $admin = User::create([
                'tenant_id' => $tenant->id,
                'role' => 'instituteadmin',
                'email' => $request->adminEmail,
                'password_hash' => password_hash($request->adminPass, PASSWORD_BCRYPT, ['cost' => 12]),
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
                        'is_enabled' => true
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
                $tenant->update(['logo_path' => '/uploads/logos/' . $filename]);
            }

            // Sync Features
            if ($request->has('features')) {
                DB::table('institute_feature_access')->where('tenant_id', $tenant->id)->delete();
                $features = is_array($request->features) ? $request->features : [];
                foreach ($features as $featureId) {
                    DB::table('institute_feature_access')->insert([
                        'tenant_id' => $tenant->id,
                        'feature_id' => $featureId,
                        'is_enabled' => true
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

    /**
     * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     * ADD-ON FEATURES API METHODS
     * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     */

    /**
     * Get all available add-ons with categories
     */
    public function getAvailableAddons(Request $request): JsonResponse
    {
        $category = $request->query('category');

        $query = DB::table('addon_features')
            ->where('status', '!=', 'inactive');

        if ($category) {
            $query->where('category', $category);
        }

        $addons = $query->orderBy('category')->orderBy('sort_order')->get();

        // Group by category
        $grouped = [];
        foreach ($addons as $addon) {
            if (!isset($grouped[$addon->category])) {
                $grouped[$addon->category] = [];
            }
            $grouped[$addon->category][] = $addon;
        }

        return response()->json([
            'success' => true,
            'addons' => $grouped,
            'total' => count($addons)
        ]);
    }

    /**
     * Get add-on details with requirements
     */
    public function getAddonDetails($addonId): JsonResponse
    {
        $addon = DB::table('addon_features')->find($addonId);

        if (!$addon) {
            return response()->json(['success' => false, 'message' => 'Add-on not found'], 404);
        }

        $requirements = DB::table('addon_requirements')
            ->where('addon_id', $addonId)
            ->get();

        return response()->json([
            'success' => true,
            'addon' => $addon,
            'requirements' => $requirements
        ]);
    }

    /**
     * Create new add-on feature
     */
    public function createAddon(Request $request): JsonResponse
    {
        $request->validate([
            'addon_key' => 'required|string|unique:addon_features',
            'addon_name' => 'required|string|max:100',
            'description' => 'string|nullable',
            'monthly_price' => 'required|numeric|min:0',
            'annual_price' => 'numeric|nullable|min:0',
            'category' => 'required|in:analytics,integrations,communications,automation,compliance,support',
            'status' => 'in:active,inactive,beta',
            'requires_approval' => 'boolean'
        ]);

        $addonId = DB::table('addon_features')->insertGetId([
            'addon_key' => strtolower($request->addon_key),
            'addon_name' => $request->addon_name,
            'description' => $request->description,
            'monthly_price' => $request->monthly_price,
            'annual_price' => $request->annual_price,
            'category' => $request->category,
            'status' => $request->status ?? 'active',
            'requires_approval' => $request->requires_approval ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Add-on created successfully',
            'addon_id' => $addonId
        ], 201);
    }

    /**
     * Get add-ons assigned to a tenant
     */
    public function getTenantAddons($tenantId): JsonResponse
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant not found'], 404);
        }

        $addons = DB::table('tenant_addons')
            ->join('addon_features', 'tenant_addons.addon_id', '=', 'addon_features.id')
            ->where('tenant_addons.tenant_id', $tenantId)
            ->select(
                'addon_features.id',
                'addon_features.addon_key',
                'addon_features.addon_name',
                'addon_features.monthly_price',
                'addon_features.annual_price',
                'addon_features.category',
                'tenant_addons.status',
                'tenant_addons.activated_at',
                'tenant_addons.expires_at',
                'tenant_addons.price_paid',
                'tenant_addons.billing_cycle',
                'tenant_addons.notes'
            )
            ->orderBy('addon_features.category')
            ->get();

        return response()->json([
            'success' => true,
            'tenant_id' => $tenantId,
            'plan' => $tenant->plan,
            'addons' => $addons,
            'active_count' => collect($addons)->where('status', 'active')->count()
        ]);
    }

    /**
     * Assign an add-on to a tenant
     */
    public function assignAddonToTenant(Request $request, $tenantId, $addonId): JsonResponse
    {
        $request->validate([
            'billing_cycle' => 'in:monthly,annual',
            'expires_at' => 'date_format:Y-m-d H:i:s|nullable',
            'notes' => 'string|nullable',
            'price_override' => 'numeric|nullable|min:0',
        ]);

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant not found'], 404);
        }

        $addon = DB::table('addon_features')->find($addonId);
        if (!$addon) {
            return response()->json(['success' => false, 'message' => 'Add-on not found'], 404);
        }

        // Check requirements
        $requirementCheck = $this->checkAddonRequirements($tenantId, $addonId);
        if (!$requirementCheck['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Add-on cannot be assigned: ' . $requirementCheck['reason']
            ], 422);
        }

        // Check if already assigned
        $existing = DB::table('tenant_addons')
            ->where('tenant_id', $tenantId)
            ->where('addon_id', $addonId)
            ->first();

        if ($existing && $existing->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This add-on is already active for this tenant'
            ], 409);
        }

        $billingCycle = $request->billing_cycle ?? 'monthly';
        $pricePaid = $request->price_override ?? (
            $billingCycle === 'annual'
                ? $addon->annual_price ?? $addon->monthly_price * 12
                : $addon->monthly_price
        );

        DB::beginTransaction();
        try {
            // Delete previous inactive record if exists
            if ($existing) {
                DB::table('tenant_addons')
                    ->where('id', $existing->id)
                    ->delete();
            }

            // Insert new addon assignment
            DB::table('tenant_addons')->insert([
                'tenant_id' => $tenantId,
                'addon_id' => $addonId,
                'status' => 'active',
                'activated_at' => now(),
                'expires_at' => $request->expires_at,
                'price_paid' => $pricePaid,
                'billing_cycle' => $billingCycle,
                'assigned_by' => 'manual',
                'notes' => $request->notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Audit log
            DB::table('audit_logs')->insert([
                'user_id' => auth('api')->id(),
                'action' => 'Add-on Assigned',
                'description' => "Add-on '{$addon->addon_name}' assigned to tenant {$tenantId}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Add-on '{$addon->addon_name}' assigned successfully",
                'addon_id' => $addonId
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
     * Assign multiple add-ons to a tenant at once
     */
    public function assignMultipleAddons(Request $request, $tenantId): JsonResponse
    {
        $request->validate([
            'addons' => 'required|array|min:1',
            'addons.*.addon_id' => 'required|exists:addon_features,id',
            'addons.*.billing_cycle' => 'in:monthly,annual',
            'addons.*.expires_at' => 'date_format:Y-m-d H:i:s|nullable',
        ]);

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant not found'], 404);
        }

        DB::beginTransaction();
        try {
            $assigned = [];
            $failed = [];

            foreach ($request->addons as $addonRequest) {
                $addonId = $addonRequest['addon_id'];
                $addon = DB::table('addon_features')->find($addonId);

                if (!$addon) {
                    $failed[] = "Add-on {$addonId} not found";
                    continue;
                }

                // Check requirements
                $requirementCheck = $this->checkAddonRequirements($tenantId, $addonId);
                if (!$requirementCheck['valid']) {
                    $failed[] = $addon->addon_name . ": " . $requirementCheck['reason'];
                    continue;
                }

                $billingCycle = $addonRequest['billing_cycle'] ?? 'monthly';
                $pricePaid = $billingCycle === 'annual'
                    ? $addon->annual_price ?? $addon->monthly_price * 12
                    : $addon->monthly_price;

                DB::table('tenant_addons')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'addon_id' => $addonId],
                    [
                        'status' => 'active',
                        'activated_at' => now(),
                        'expires_at' => $addonRequest['expires_at'] ?? null,
                        'price_paid' => $pricePaid,
                        'billing_cycle' => $billingCycle,
                        'assigned_by' => 'manual',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $assigned[] = $addon->addon_name;
            }

            DB::commit();

            return response()->json([
                'success' => count($failed) === 0,
                'message' => count($assigned) . ' add-on(s) assigned successfully',
                'assigned' => $assigned,
                'failed' => $failed
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
     * Remove add-on from tenant
     */
    public function removeAddonFromTenant($tenantId, $addonId): JsonResponse
    {
        $deleted = DB::table('tenant_addons')
            ->where('tenant_id', $tenantId)
            ->where('addon_id', $addonId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Add-on assignment not found'
            ], 404);
        }

        $addon = DB::table('addon_features')->find($addonId);

        DB::table('audit_logs')->insert([
            'user_id' => auth('api')->id(),
            'action' => 'Add-on Removed',
            'description' => "Add-on '{$addon->addon_name}' removed from tenant {$tenantId}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => "Add-on removed successfully"
        ]);
    }

    /**
     * Get add-on pricing summary
     */
    public function getAddonPricing(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id');
        $billingCycle = $request->query('billing_cycle', 'monthly');

        $query = DB::table('addon_features')
            ->where('status', '!=', 'inactive')
            ->orderBy('category')
            ->orderBy('sort_order');

        $addons = $query->get();

        // Calculate pricing
        $addons = $addons->map(function ($addon) use ($billingCycle) {
            return [
                'id' => $addon->id,
                'addon_key' => $addon->addon_key,
                'addon_name' => $addon->addon_name,
                'monthly_price' => $addon->monthly_price,
                'annual_price' => $addon->annual_price,
                'pricing' => $billingCycle === 'annual' && $addon->annual_price
                    ? $addon->annual_price
                    : $addon->monthly_price,
                'savings' => $addon->annual_price && $addon->annual_price < ($addon->monthly_price * 12)
                    ? round((1 - ($addon->annual_price / ($addon->monthly_price * 12))) * 100)
                    : 0,
                'category' => $addon->category,
            ];
        });

        if ($tenantId) {
            $assigned = DB::table('tenant_addons')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->pluck('addon_id')
                ->toArray();

            $addons = $addons->map(function ($addon) use ($assigned) {
                $addon['is_assigned'] = in_array($addon['id'], $assigned);
                return $addon;
            });
        }

        return response()->json([
            'success' => true,
            'billing_cycle' => $billingCycle,
            'addons' => $addons->groupBy('category'),
            'total_price' => $addons->sum('pricing')
        ]);
    }

    /**
     * Check if add-on can be assigned (verify requirements)
     */
    private function checkAddonRequirements($tenantId, $addonId): array
    {
        $requirements = DB::table('addon_requirements')
            ->where('addon_id', $addonId)
            ->get();

        if ($requirements->isEmpty()) {
            return ['valid' => true];
        }

        $tenant = Tenant::find($tenantId);

        foreach ($requirements as $req) {
            if ($req->requirement_type === 'requires_plan') {
                // Check if tenant has this plan or higher
                $plans = ['free' => 0, 'starter' => 1, 'growth' => 2, 'enterprise' => 3];
                $tenantLevel = $plans[$tenant->plan] ?? 0;
                $requiredLevel = $plans[$req->requirement_key] ?? 0;

                if ($tenantLevel < $requiredLevel) {
                    return [
                        'valid' => false,
                        'reason' => $req->reason ?? "Requires {$req->requirement_key} plan or higher"
                    ];
                }
            } elseif ($req->requirement_type === 'requires_addon') {
                // Check if tenant has required add-on
                $hasAddon = DB::table('tenant_addons')
                    ->join('addon_features', 'tenant_addons.addon_id', '=', 'addon_features.id')
                    ->where('tenant_addons.tenant_id', $tenantId)
                    ->where('addon_features.addon_key', $req->requirement_key)
                    ->where('tenant_addons.status', 'active')
                    ->exists();

                if (!$hasAddon) {
                    return [
                        'valid' => false,
                        'reason' => $req->reason ?? "Requires {$req->requirement_key} add-on"
                    ];
                }
            } elseif ($req->requirement_type === 'excludes_addon') {
                // Check that tenant doesn't have conflicting add-on
                $hasAddon = DB::table('tenant_addons')
                    ->join('addon_features', 'tenant_addons.addon_id', '=', 'addon_features.id')
                    ->where('tenant_addons.tenant_id', $tenantId)
                    ->where('addon_features.addon_key', $req->requirement_key)
                    ->where('tenant_addons.status', 'active')
                    ->exists();

                if ($hasAddon) {
                    return [
                        'valid' => false,
                        'reason' => $req->reason ?? "Incompatible with {$req->requirement_key} add-on"
                    ];
                }
            }
        }

        return ['valid' => true];
    }

    /**
     * Get add-on usage for billing
     */
    public function getAddonUsage($tenantId, $addonId): JsonResponse
    {
        $addon = DB::table('addon_features')->find($addonId);
        if (!$addon) {
            return response()->json(['success' => false, 'message' => 'Add-on not found'], 404);
        }

        $usage = DB::table('addon_usage_logs')
            ->where('tenant_id', $tenantId)
            ->where('addon_id', $addonId)
            ->orderBy('logged_at', 'desc')
            ->get();

        $summary = DB::table('addon_usage_logs')
            ->where('tenant_id', $tenantId)
            ->where('addon_id', $addonId)
            ->select('metric_key', DB::raw('SUM(usage_amount) as total_usage'))
            ->groupBy('metric_key')
            ->get();

        return response()->json([
            'success' => true,
            'addon' => $addon,
            'summary' => $summary,
            'logs' => $usage
        ]);
    }
}
