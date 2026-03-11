<?php
require_once 'config/config.php';
$db = getDBConnection();

try {
    // Check if table matches SRS
    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_logs_new (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          user_id BIGINT UNSIGNED,
          tenant_id BIGINT UNSIGNED,
          action VARCHAR(100) NOT NULL,
          ip_address VARCHAR(45),
          user_agent TEXT,
          metadata JSON,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_user (user_id),
          INDEX idx_tenant (tenant_id),
          INDEX idx_action (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Migrate data if old table exists with different columns
    $db->exec("INSERT INTO audit_logs_new (user_id, tenant_id, action, ip_address, metadata, created_at) 
               SELECT user_id, tenant_id, action, ip_address, 
               JSON_OBJECT('table', table_name, 'record_id', record_id, 'changes', changes, 'description', description),
               created_at FROM audit_logs");
               
    $db->exec("DROP TABLE audit_logs");
    $db->exec("RENAME TABLE audit_logs_new TO audit_logs");
    
    echo "Audit logs table synchronized with SRS FR-AUTH-008.\n";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
