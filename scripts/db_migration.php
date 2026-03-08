<?php
require_once __DIR__ . '/../config/config.php';

function runMigration($sqlPath) {
    echo "Running migration: $sqlPath\n";
    $db = getDBConnection();
    $sql = file_get_contents($sqlPath);
    
    // Remove comments
    $sql = preg_replace('/--.*?\n/', "\n", $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    $queries = explode(';', $sql);
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query !== '') {
            try {
                $db->exec($query);
                echo "Success: " . substr($query, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "Error executing query: " . $e->getMessage() . "\n";
                if (strpos($e->getMessage(), "Duplicate key name") !== false) {
                    echo "Index already exists, skipping...\n";
                } else {
                    throw $e;
                }
            }
        }
    }
}

try {
    // 1. Create job_queue table
    runMigration(__DIR__ . '/../database/migrations/2026_03_06_create_job_queue_table.sql');
    
    // 2. Apply optimizations (indexes)
    // Note: The hamrolabs_database_optimization.sql has SECTION 10 with the CREATE INDEX commands.
    // I will extract those or just run the ones I need.
    $optimizationsSql = "
        CREATE INDEX IF NOT EXISTS idx_fee_records_status_tenant ON fee_records(status, tenant_id);
        CREATE INDEX IF NOT EXISTS idx_student_fee_summary_lookup ON student_fee_summary(student_id, tenant_id, fee_status);
        CREATE INDEX IF NOT EXISTS idx_audit_logs_date ON audit_logs(tenant_id, created_at DESC);
        CREATE INDEX IF NOT EXISTS idx_audit_logs_action ON audit_logs(action, tenant_id, created_at DESC);
        CREATE INDEX IF NOT EXISTS idx_students_user_id_lookup ON students(user_id, tenant_id);
        CREATE INDEX IF NOT EXISTS idx_pt_date_method_tenant ON payment_transactions(tenant_id, payment_date DESC, payment_method);
        CREATE INDEX IF NOT EXISTS idx_fee_records_batch_tenant ON fee_records(batch_id, tenant_id, status);
    ";
    
    $db = getDBConnection();
    foreach (explode(';', $optimizationsSql) as $q) {
        $q = trim($q);
        if ($q) {
            try {
                $db->exec($q);
                echo "Optimized: " . substr($q, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "Skip optimization: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
