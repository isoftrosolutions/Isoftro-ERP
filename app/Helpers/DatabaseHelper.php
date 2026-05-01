<?php
namespace App\Helpers;

use PDO;
use Exception;
use PDOException;

/**
 * Database Helper - Centralized Query Handling & Observability
 */
class DatabaseHelper
{
    private static $lastQuery = '';
    private static $slowQueryThreshold = 0.5; // 500ms

    /**
     * Execute a query and return statement
     */
    public static function execute($sql, $params = [])
    {
        $db = getDBConnection();
        $start = microtime(true);
        
        try {
            $stmt = $db->prepare($sql);
            
            // Smarter parameter binding
            foreach ($params as $key => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : (is_bool($value) ? PDO::PARAM_BOOL : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR));
                
                // Handle both :name and name keys
                $paramKey = (strpos($key, ':') === 0) ? $key : ":$key";
                $stmt->bindValue($paramKey, $value, $type);
            }
            
            $stmt->execute();
            
            $duration = microtime(true) - $start;
            self::logQuery($sql, $params, $duration);
            
            return $stmt;
        } catch (PDOException $e) {
            self::logError($e, $sql, $params);
            throw $e;
        }
    }

    /**
     * Fetch a single row
     */
    public static function fetch($sql, $params = [])
    {
        return self::execute($sql, $params)->fetch();
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll($sql, $params = [])
    {
        return self::execute($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single column
     */
    public static function fetchColumn($sql, $params = [])
    {
        return self::execute($sql, $params)->fetchColumn();
    }

    /**
     * Logging & Observability
     */
    private static function logQuery($sql, $params, $duration)
    {
        $safeParams = self::redactParams($params);
        if ($duration > self::$slowQueryThreshold) {
            error_log("[DB-SLOW] ({$duration}s) $sql | Params: " . json_encode($safeParams));
        }

        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("[DB-QUERY] " . $sql);
        }
    }

    private static function logError($e, $sql, $params)
    {
        error_log("[DB-ERROR] Database query failed | SQL: $sql | Params: " . json_encode(self::redactParams($params)));
    }

    /**
     * Safe JSON response for API endpoints
     */
    public static function sendErrorResponse($message, $exception = null)
    {
        $response = ['success' => false];
        
        if (defined('APP_ENV') && APP_ENV === 'development' && $exception) {
            $response['message'] = $message;
        } else {
            $response['message'] = $message;
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    private static function redactParams(array $params): array
    {
        $sensitive = ['password', 'token', 'secret', 'api_key', 'authorization', 'cookie'];
        $clean = [];
        foreach ($params as $k => $v) {
            $key = strtolower((string)$k);
            if (in_array($key, $sensitive, true)) {
                $clean[$k] = '[REDACTED]';
            } else {
                $clean[$k] = $v;
            }
        }
        return $clean;
    }
}
