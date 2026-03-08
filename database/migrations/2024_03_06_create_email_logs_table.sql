-- Create email_logs table for tracking sent receipts
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `payment_transaction_id` BIGINT UNSIGNED NULL,
    `receipt_no` VARCHAR(50) NOT NULL,
    `recipient_email` VARCHAR(255) NOT NULL,
    `status` ENUM('queued', 'sent', 'failed') DEFAULT 'queued',
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`tenant_id`),
    INDEX (`student_id`),
    INDEX (`receipt_no`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
