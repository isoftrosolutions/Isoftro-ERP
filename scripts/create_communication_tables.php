<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    
    // 1. Create message_templates table
    $queryTemplates = "
    CREATE TABLE IF NOT EXISTS `message_templates` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `tenant_id` bigint(20) unsigned NOT NULL,
      `name` varchar(255) NOT NULL,
      `type` enum('sms','email','whatsapp') NOT NULL DEFAULT 'sms',
      `subject` varchar(255) DEFAULT NULL,
      `content` text NOT NULL,
      `is_active` tinyint(1) NOT NULL DEFAULT '1',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_tenant_templates` (`tenant_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // 2. Create communication_logs table
    $queryLogs = "
    CREATE TABLE IF NOT EXISTS `communication_logs` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `tenant_id` bigint(20) unsigned NOT NULL,
      `type` enum('sms','email','whatsapp') NOT NULL,
      `sender_id` bigint(20) unsigned DEFAULT NULL,
      `recipient_id` bigint(20) unsigned DEFAULT NULL, -- Student or Staff ID
      `recipient_type` enum('student','staff','teacher','other') DEFAULT 'other',
      `recipient_contact` varchar(255) NOT NULL,
      `subject` varchar(255) DEFAULT NULL,
      `message` text NOT NULL,
      `status` enum('pending','sent','failed','delivered') NOT NULL DEFAULT 'pending',
      `provider_response` text DEFAULT NULL,
      `sent_at` timestamp NULL DEFAULT NULL,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_tenant_comm_logs` (`tenant_id`),
      KEY `idx_comm_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($queryTemplates);
    echo "message_templates table created.\n";
    
    $db->exec($queryLogs);
    echo "communication_logs table created.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
