<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tenant Identification Middleware
 * 
 * Standard Laravel Middleware refactored from legacy logic.
 * Identifies the tenant from:
 * 1. Subdomain (for public and authenticated routes)
 * 2. Authenticated user's tenant_id (as fallback for authenticated routes)
 * 
 * Sets context in:
 * - Request object (as '_tenant_id' and '_tenant')
 * - PHP $_SESSION (for legacy views and helpers)
 * - Static property for global access via IdentifyTenant::current()
 */
class IdentifyTenant {
    
    private static $currentTenant = null;

    /**
     * Handle tenant identification
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next) {
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);
        
        $tenant = null;
        
        // Priority 1: Subdomain lookup
        if ($subdomain && !in_array($subdomain, ['www', 'localhost', '127.0.0.1'])) {
            $tenant = $this->lookupTenant($subdomain);
        }
        
        // Priority 2: JWT / Authenticated User (if already authenticated by previous middleware)
        // This is safe because IdentifyTenant runs as global middleware or via groups
        try {
            if (function_exists('auth') && auth('api')->check()) {
                $user = auth('api')->user();
                if ($user) {
                    // Populate legacy session if missing or stale
                    if (empty($_SESSION['userData']) || $_SESSION['userData']['id'] != $user->id) {
                        $_SESSION['userData'] = [
                            'id'        => $user->id,
                            'email'     => $user->email,
                            'name'      => $user->name,
                            'role'      => $user->role,
                            'tenant_id' => $user->tenant_id,
                            'is_jwt'    => true,
                        ];
                    }
                    
                    if (!$tenant && $user->tenant_id) {
                        $tenant = $this->getTenantById($user->tenant_id);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail auth identification and continue (non-blocking)
            error_log("[IdentifyTenant] Silent Auth Error: " . $e->getMessage());
        }
        
        // Priority 3: Session fallback (for pure legacy flows)
        if (!$tenant && isset($_SESSION['tenant_id'])) {
            $tenant = $this->getTenantById($_SESSION['tenant_id']);
        }
        
        if ($tenant) {
            self::$currentTenant = $tenant;
            
            // Populate sessions ONLY if not already populated to avoid unnecessary DB calls 
            // from loadFeatures in subsequent requests if session is already active
            if (!isset($_SESSION['tenant_id']) || $_SESSION['tenant_id'] != $tenant['id']) {
                $_SESSION['tenant_id'] = $tenant['id'];
                $_SESSION['tenant_name'] = $tenant['name'];
                $_SESSION['tenant_subdomain'] = $tenant['subdomain'];
                $_SESSION['tenant_plan'] = $tenant['plan'];
                $_SESSION['tenant_status'] = $tenant['status'];
                
                $logoPath = $tenant['logo_path'];
                if (!empty($logoPath) && strpos($logoPath, '/uploads/') === 0 && strpos($logoPath, '/public/') !== 0) {
                    $logoPath = '/public' . $logoPath;
                }
                $_SESSION['tenant_logo'] = $logoPath;
                $_SESSION['institute_logo'] = $logoPath;
                
                // Load features into session for legacy hasFeature() calls
                if (function_exists('loadFeatures')) {
                    loadFeatures($tenant['id']);
                }
            }
            
            // Merge into request for Laravel controllers
            $request->merge([
                'tenant' => $tenant,
                'tenant_id' => $tenant['id']
            ]);
        }
        
        return $next($request);
    }
    
    /**
     * Extract subdomain from host
     */
    private function extractSubdomain($host) {
        if ($host === 'localhost' || $host === '127.0.0.1') return null;
        $parts = explode('.', $host);
        if (count($parts) > 2) return $parts[0];
        return null;
    }
    
    /**
     * Lookup tenant by subdomain
     */
    private function lookupTenant($subdomain) {
        try {
            return (array)DB::table('tenants')
                ->select('id', 'name', 'subdomain', 'plan', 'status', 'student_limit', 'sms_credits', 'logo_path')
                ->where('subdomain', $subdomain)
                ->where('status', '!=', 'suspended')
                ->first() ?: null;
        } catch (\Exception $e) {
            error_log("Tenant lookup failed (subdomain=$subdomain): " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get tenant by ID
     */
    private function getTenantById($tenantId) {
        try {
            return (array)DB::table('tenants')
                ->select('id', 'name', 'subdomain', 'plan', 'status', 'logo_path')
                ->where('id', $tenantId)
                ->first() ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Global accessors
     */
    public static function current() {
        return self::$currentTenant;
    }
    
    public static function getTenant() {
        return self::current();
    }
    
    public static function getTenantId() {
        return self::$currentTenant['id'] ?? null;
    }
}
