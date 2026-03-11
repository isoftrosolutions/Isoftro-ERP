<?php
/**
 * AuditLogger Helper
 * Tracks every CUD (Create, Update, Delete) operation
 */

namespace App\Helpers;

class AuditLogger {
    /**
     * Log a security or data event (SRS FR-AUTH-008 Compliance)
     */
    public static function log($action, $userId = null, $tenantId = null, $metadata = []) {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userId = $userId ?: ($_SESSION['userData']['id'] ?? null);
            $tenantId = $tenantId ?: ($_SESSION['userData']['tenant_id'] ?? null);
            
            // Filter sensitive fields
            $sensitiveFields = ['password', 'password_hash', 'token', 'access_token', 'refresh_token', 'otp', 'secret'];
            if (is_array($metadata)) {
                array_walk_recursive($metadata, function (&$value, $key) use ($sensitiveFields) {
                    if (in_array(strtolower($key), $sensitiveFields)) {
                        $value = '********';
                    }
                });
            }

            if (class_exists('\Illuminate\Support\Facades\DB') && \Illuminate\Support\Facades\DB::getFacadeRoot()) {
                $db = \Illuminate\Support\Facades\DB::connection()->getPdo();
            } elseif (function_exists('getDBConnection')) {
                $db = getDBConnection();
            } else {
                return false;
            }

            $query = "INSERT INTO audit_logs (user_id, tenant_id, action, ip_address, user_agent, metadata) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $userId,
                $tenantId,
                strtoupper($action),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                !empty($metadata) ? json_encode($metadata) : null
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Audit Logger Failed: " . $e->getMessage());
            return false;
        }
    }
}
