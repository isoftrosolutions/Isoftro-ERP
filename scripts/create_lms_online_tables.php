<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    
    // Create online_classes table
    $queryClasses = "
    CREATE TABLE IF NOT EXISTS `online_classes` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `tenant_id` bigint(20) unsigned NOT NULL,
      `batch_id` bigint(20) unsigned NOT NULL,
      `subject_id` bigint(20) unsigned NOT NULL,
      `teacher_id` bigint(20) unsigned NOT NULL,
      `title` varchar(255) NOT NULL,
      `description` text DEFAULT NULL,
      `start_time` datetime NOT NULL,
      `duration_minutes` int(11) NOT NULL DEFAULT '40',
      `meeting_provider` enum('zoom','google_meet','jitsi','internal') NOT NULL DEFAULT 'zoom',
      `meeting_id` varchar(100) DEFAULT NULL,
      `meeting_password` varchar(100) DEFAULT NULL,
      `join_url` text DEFAULT NULL,
      `start_url` text DEFAULT NULL,
      `status` enum('scheduled','ongoing','completed','canceled') NOT NULL DEFAULT 'scheduled',
      `recorded_url` text DEFAULT NULL,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_tenant_classes` (`tenant_id`),
      KEY `idx_batch_classes` (`batch_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Create online_class_attendance table
    $queryAttendance = "
    CREATE TABLE IF NOT EXISTS `online_class_attendance` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `tenant_id` bigint(20) unsigned NOT NULL,
      `online_class_id` bigint(20) unsigned NOT NULL,
      `student_id` bigint(20) unsigned NOT NULL,
      `joined_at` timestamp NULL DEFAULT NULL,
      `left_at` timestamp NULL DEFAULT NULL,
      `duration_minutes` int(11) NOT NULL DEFAULT '0',
      `status` enum('present','absent','partial') NOT NULL DEFAULT 'absent',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_class_attendance` (`online_class_id`),
      KEY `idx_student_online_attendance` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($queryClasses);
    echo "online_classes table created.\n";
    
    $db->exec($queryAttendance);
    echo "online_class_attendance table created.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
