<?php

namespace App\Http\Middleware;

/**
 * Tenant Identification Middleware
 * CRITICAL: Reads subdomain → sets tenant context
 * 
 * This middleware must be applied to ALL tenant-scoped routes
 * Refactored to remove session-based source of truth.
 */

class IdentifyTenant {
    
    private static $currentTenant = null;

    /**
     * Handle tenant identification from subdomain
     */
    public function handle() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $subdomain = $this->extractSubdomain($host);
        
        if ($subdomain && $subdomain !== 'www' && $subdomain !== 'localhost') {
            $tenant = $this->lookupTenant($subdomain);
            
            if ($tenant) {
                self::$currentTenant = $tenant;
                
                // For transitional support only, we'll keep the session populated
                // but our logic won't RELY on it as source of truth.
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
                
                $this->loadTenantModules($tenant['id']);
                
                return $tenant;
            }
        }
        
        // If not resolved from subdomain, check if we have a token that tells us
        try {
            if (function_exists('auth') && auth('api')->check()) {
                $user = auth('api')->user();
                if ($user && $user->tenant_id) {
                    if (!self::$currentTenant || self::$currentTenant['id'] != $user->tenant_id) {
                        $tenant = $this->getTenantById($user->tenant_id);
                        if ($tenant) {
                            self::$currentTenant = $tenant;
                        }
                    }
                }
            }
        } catch (\Exception $e) {}

        return self::$currentTenant;
    }
    
    /**
     * Extract subdomain from host
     */
    private function extractSubdomain($host) {
        $host = explode(':', $host)[0];
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
            $db = getDBConnection();
            $stmt = $db->prepare("
                SELECT id, name, subdomain, plan, status, 
                       student_limit, sms_credits, logo_path
                FROM tenants 
                WHERE subdomain = :subdomain 
                AND status != 'suspended'
                LIMIT 1
            ");
            $stmt->execute(['subdomain' => $subdomain]);
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            error_log("Tenant lookup failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get tenant by ID
     */
    private function getTenantById($tenantId) {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT id, name, subdomain, plan, status, logo_path FROM tenants WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $tenantId]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get current tenant ID
     */
    public static function getTenantId() {
        if (self::$currentTenant) return self::$currentTenant['id'];
        
        // Priority 1: JWT
        try {
            if (function_exists('auth') && auth('api')->check()) {
                $user = auth('api')->user();
                return $user->tenant_id ?? null;
            }
        } catch (\Exception $e) {}

        return $_SESSION['tenant_id'] ?? null;
    }
    
    /**
     * Get current tenant info
     */
    public static function getTenant() {
        if (self::$currentTenant) return self::$currentTenant;

        $tenantId = self::getTenantId();
        if ($tenantId) {
            return (new self())->getTenantById($tenantId);
        }
        
        return null;
    }
    
    /**
     * Check if tenant is active
     */
    public static function isTenantActive() {
        $tenant = self::getTenant();
        return $tenant && in_array($tenant['status'], ['active', 'trial']);
    }

    /**
     * Load enabled modules for the tenant
     */
    private function loadTenantModules($tenantId) {
        if (function_exists('loadFeatures')) {
            loadFeatures($tenantId);
        }
    }
}
