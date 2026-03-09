<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    
    // Create audit_logs table
    $query = "
    CREATE TABLE IF NOT EXISTS `audit_logs` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `tenant_id` bigint(20) unsigned NOT NULL,
      `user_id` bigint(20) unsigned NOT NULL,
      `ip_address` varchar(45) NOT NULL DEFAULT 'system',
      `action` varchar(50) NOT NULL,
      `table_name` varchar(100) NOT NULL,
      `record_id` bigint(20) unsigned DEFAULT NULL,
      `changes` longtext DEFAULT NULL,
      `description` text DEFAULT NULL,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_tenant_audit` (`tenant_id`),
      KEY `idx_user_audit` (`user_id`),
      KEY `idx_action_audit` (`action`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($query);
    echo "audit_logs table created.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
