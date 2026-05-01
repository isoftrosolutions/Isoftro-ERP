<?php
namespace App\Helpers;

use PDO;

class Logger
{
    /**
     * Log a message to the database audit_logs table
     * 
     * @param string $action The action being performed (e.g., 'Tenant Created')
     * @param string $description Detailed description of the event
     * @param int|null $userId ID of the user performing the action
     * @param array $extra Optional extra data to log
     * @return bool True if successful
     */
    public static function log($action, $description, $userId = null, $extra = [])
    {
        try {
            if (function_exists('getDBConnection')) {
                $pdo = getDBConnection();
            } else {
                return false;
            }

            if ($userId === null && isset($_SESSION['userData']['id'])) {
                $userId = $_SESSION['userData']['id'];
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // If extra data provided, append to description or handle accordingly
            if (!empty($extra)) {
                $redacted = self::redactSensitive($extra);
                $description .= " | Extra: " . json_encode($redacted);
            }

            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            return $stmt->execute([
                $userId,
                $action,
                $ip,
                $userAgent,
                $description
            ]);
        } catch (\Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log an error to system error logs and optionally to database
     * 
     * @param \Throwable $exception
     * @param string $context
     */
    public static function error(\Throwable $exception, $context = 'System')
    {
        $message = sprintf("[%s] %s", $context, get_class($exception));

        error_log($message);

        // Also log to database if it's a critical super admin error
        if (strpos($_SERVER['REQUEST_URI'], '/super-admin/') !== false) {
            self::log('System Error', $message, null, []);
        }
    }

    private static function redactSensitive(array $data): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'authorization', 'api_key', 'jwt', 'cookie'];
        $out = [];
        foreach ($data as $k => $v) {
            $key = strtolower((string)$k);
            if (in_array($key, $sensitiveKeys, true)) {
                $out[$k] = '[REDACTED]';
                continue;
            }
            $out[$k] = is_array($v) ? self::redactSensitive($v) : $v;
        }
        return $out;
    }
}
