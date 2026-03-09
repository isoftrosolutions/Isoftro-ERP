<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    
    // Create sms_settings table
    $query = "
    CREATE TABLE IF NOT EXISTS `sms_settings` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `tenant_id` bigint(20) unsigned NOT NULL,
      `provider` varchar(50) NOT NULL DEFAULT 'mock',
      `api_key` varchar(255) DEFAULT NULL,
      `api_secret` varchar(255) DEFAULT NULL,
      `sender_id` varchar(20) DEFAULT 'HamroLabs',
      `is_active` tinyint(1) NOT NULL DEFAULT '1',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `idx_tenant_sms` (`tenant_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($query);
    echo "sms_settings table created.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
