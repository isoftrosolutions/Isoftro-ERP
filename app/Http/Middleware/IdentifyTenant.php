<?php

namespace App\Http\Middleware;

/**
 * Tenant Identification Middleware
 * CRITICAL: Reads subdomain → sets tenant context
 * 
 * This middleware must be applied to ALL tenant-scoped routes
 */

class IdentifyTenant {
    
    /**
     * Handle tenant identification from subdomain
     */
    public function handle() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $subdomain = $this->extractSubdomain($host);
        
        if ($subdomain && $subdomain !== 'www' && $subdomain !== 'localhost') {
            $tenant = $this->lookupTenant($subdomain);
            
            if ($tenant) {
                // Set tenant in application instance
                $_SESSION['tenant_id'] = $tenant['id'];
                $_SESSION['tenant_name'] = $tenant['name'];
                $_SESSION['tenant_subdomain'] = $tenant['subdomain'];
                $_SESSION['tenant_plan'] = $tenant['plan'];
                $_SESSION['tenant_status'] = $tenant['status'];
                // Fix old logo paths that don't have /public prefix
                $logoPath = $tenant['logo_path'];
                if (!empty($logoPath) && strpos($logoPath, '/uploads/') === 0 && strpos($logoPath, '/public/') !== 0) {
                    $logoPath = '/public' . $logoPath;
                }
                $_SESSION['tenant_logo'] = $logoPath;
                $_SESSION['institute_logo'] = $logoPath;
                
                // Load enabled modules for the tenant
                $this->loadTenantModules($tenant['id']);
                
                return $tenant;
            }
        }
        
        // For API requests or direct access, check tenant_id in session
        if (isset($_SESSION['tenant_id'])) {
            $tenant = $this->getTenantById($_SESSION['tenant_id']);
            if ($tenant) {
                // Set all session variables for the tenant
                $_SESSION['tenant_id'] = $tenant['id'];
                $_SESSION['tenant_name'] = $tenant['name'];
                $_SESSION['tenant_subdomain'] = $tenant['subdomain'];
                $_SESSION['tenant_plan'] = $tenant['plan'];
                $_SESSION['tenant_status'] = $tenant['status'];

                // Ensure logo is in session (with /public prefix fix)
                $logoPath = $tenant['logo_path'];
                if (!empty($logoPath) && strpos($logoPath, '/uploads/') === 0 && strpos($logoPath, '/public/') !== 0) {
                    $logoPath = '/public' . $logoPath;
                }
                $_SESSION['tenant_logo'] = $logoPath;
                $_SESSION['institute_logo'] = $logoPath;

                // Ensure modules are loaded
                if (!isset($_SESSION['tenant_modules'])) {
                    $this->loadTenantModules($tenant['id']);
                }
            }
            return $tenant;
        }
        
        return null;
    }
    
    /**
     * Extract subdomain from host
     */
    private function extractSubdomain($host) {
        // Remove port if present
        $host = explode(':', $host)[0];
        
        // Handle localhost
        if ($host === 'localhost' || $host === '127.0.0.1') {
            return null;
        }
        
        // Extract subdomain (everything before the main domain)
        $parts = explode('.', $host);
        
        // If more than 2 parts, first part is subdomain
        if (count($parts) > 2) {
            return $parts[0];
        }
        
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
            $result = $stmt->fetch();
            
            return $result ?: null;
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
            $stmt = $db->prepare("
                SELECT id, name, subdomain, plan, status, 
                       student_limit, sms_credits, logo_path
                FROM tenants 
                WHERE id = :id
                LIMIT 1
            ");
            $stmt->execute(['id' => $tenantId]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Tenant lookup by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get current tenant ID
     */
    public static function getTenantId() {
        return $_SESSION['tenant_id'] ?? null;
    }
    
    /**
     * Get current tenant info
     */
    public static function getTenant() {
        return [
            'id' => $_SESSION['tenant_id'] ?? null,
            'name' => $_SESSION['tenant_name'] ?? null,
            'subdomain' => $_SESSION['tenant_subdomain'] ?? null,
            'plan' => $_SESSION['tenant_plan'] ?? null,
            'status' => $_SESSION['tenant_status'] ?? null
        ];
    }
    
    /**
     * Check if tenant is active
     */
    public static function isTenantActive() {
        return isset($_SESSION['tenant_status']) && 
               in_array($_SESSION['tenant_status'], ['active', 'trial']);
    }
    /**
     * Load enabled modules for the tenant into session
     */
    private function loadTenantModules($tenantId) {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("
                SELECT m.name 
                FROM modules m
                JOIN institute_modules im ON m.id = im.module_id
                WHERE im.tenant_id = :tenant_id 
                AND im.is_enabled = 1
                AND m.status = 'active'
            ");
            $stmt->execute(['tenant_id' => $tenantId]);
            $modules = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $_SESSION['tenant_modules'] = $modules ?: [];
        } catch (\PDOException $e) {
            error_log("Failed to load tenant modules: " . $e->getMessage());
            $_SESSION['tenant_modules'] = [];
        }
    }
}

// Auto-execute on include (optional - can be called manually)
// $tenantMiddleware = new IdentifyTenant();
// $currentTenant = $tenantMiddleware->handle();
