-- Migration: Create fee_ledger table
-- Purpose: Support dedicated double-entry accounting for student fees

CREATE TABLE IF NOT EXISTS `fee_ledger` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `payment_transaction_id` BIGINT UNSIGNED DEFAULT NULL, -- Link to payment_transactions for credits
  `fee_record_id` BIGINT UNSIGNED DEFAULT NULL, -- Link to fee_records for debits
  `entry_date` DATE NOT NULL,
  `entry_type` ENUM('debit', 'credit') NOT NULL, -- debit (new fee), credit (payment)
  `amount` DECIMAL(15, 2) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_tenant_student` (`tenant_id`, `student_id`),
  INDEX `idx_entry_date` (`entry_date`),
  CONSTRAINT `fk_fee_ledger_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
