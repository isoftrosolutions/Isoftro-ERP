<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    
    // Create library_books table
    $queryBooks = "
    CREATE TABLE IF NOT EXISTS `library_books` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `tenant_id` bigint(20) unsigned NOT NULL,
      `title` varchar(255) NOT NULL,
      `author` varchar(255) DEFAULT NULL,
      `isbn` varchar(50) DEFAULT NULL,
      `publisher` varchar(255) DEFAULT NULL,
      `category` varchar(100) DEFAULT NULL,
      `price` decimal(10,2) DEFAULT '0.00',
      `rack_no` varchar(50) DEFAULT NULL,
      `quantity` int(11) NOT NULL DEFAULT '1',
      `available` int(11) NOT NULL DEFAULT '1',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `deleted_at` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_tenant_books` (`tenant_id`),
      KEY `idx_books_isbn` (`isbn`),
      KEY `idx_books_category` (`category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Create library_issues table
    $queryIssues = "
    CREATE TABLE IF NOT EXISTS `library_issues` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `tenant_id` bigint(20) unsigned NOT NULL,
      `book_id` bigint(20) unsigned NOT NULL,
      `user_type` enum('student','staff','teacher') NOT NULL,
      `user_id` bigint(20) unsigned NOT NULL,
      `issue_date` date NOT NULL,
      `due_date` date NOT NULL,
      `return_date` date DEFAULT NULL,
      `status` enum('issued','returned','lost','overdue') NOT NULL DEFAULT 'issued',
      `fine_amount` decimal(10,2) DEFAULT '0.00',
      `fine_paid` tinyint(1) NOT NULL DEFAULT '0',
      `notes` text DEFAULT NULL,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_tenant_issues` (`tenant_id`),
      KEY `idx_issues_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($queryBooks);
    echo "library_books table created.\n";
    
    $db->exec($queryIssues);
    echo "library_issues table created.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
