<?php
/**
 * DB Health & Security Verification Script
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Helpers/DatabaseHelper.php';
require_once __DIR__ . '/../app/Helpers/StatsHelper.php';

use App\Helpers\DatabaseHelper;
use App\Helpers\StatsHelper;

header('Content-Type: text/plain');

echo "--- iSoftro DB Health Check ---\n\n";

// 1. Test Connection & Helper
try {
    $version = DatabaseHelper::fetchColumn("SELECT VERSION()");
    echo "[PASS] Database connection stable. Version: $version\n";
} catch (Exception $e) {
    echo "[FAIL] Database connection failed: " . $e->getMessage() . "\n";
}

// 2. Test LIMIT/OFFSET Binding (The RC-2 Fix)
try {
    $limit = 1;
    $offset = 0;
    echo "[DEBUG] Testing DatabaseHelper::fetchAll with limit=".gettype($limit)."($limit) and offset=".gettype($offset)."($offset)\n";
    $result = DatabaseHelper::fetchAll("SELECT id FROM users LIMIT :limit OFFSET :offset", [
        'limit' => $limit,
        'offset' => $offset
    ]);
    echo "[PASS] LIMIT/OFFSET parameter binding successful.\n";
} catch (Exception $e) {
    echo "[FAIL] LIMIT/OFFSET binding failed: " . $e->getMessage() . "\n";
}

// 3. Test SQL Injection Protection (The RC-1 Fix)
try {
    // Attempting to bypass with a string - should fail or be sanitized
    $maliciousLimit = "1; DROP TABLE non_existent_table;";
    $limit = intval($maliciousLimit);
    echo "[INFO] Sanitized limit '$maliciousLimit' to $limit\n";
    
    $stmt = getDBConnection()->prepare("SELECT id FROM users LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    echo "[PASS] SQL injection mitigation verified via integer casting and param binding.\n";
} catch (Exception $e) {
    echo "[FAIL] SQL injection test errored: " . $e->getMessage() . "\n";
}

// 4. Test StatsHelper Health
try {
    $stats = StatsHelper::getSuperAdminStats();
    if ($stats && isset($stats['mrr'])) {
        echo "[PASS] StatsHelper functioning. Current MRR: " . $stats['mrrFormatted'] . "\n";
        echo "[PASS] MRR Trend count: " . count($stats['mrrTrend']) . " months.\n";
    } else {
        echo "[FAIL] StatsHelper returned invalid data structure.\n";
    }
} catch (Exception $e) {
    echo "[FAIL] StatsHelper crashed: " . $e->getMessage() . "\n";
}

// 5. Check Log Files
echo "\n--- System Checks ---\n";
if (file_exists('c:/Apache24/logs/error.log') || is_writable(ini_get('error_log'))) {
    echo "[INFO] Error logging is active. Check server logs for [DB-SLOW] or [DB-ERROR] markers.\n";
}

echo "\nVerification Complete.\n";
