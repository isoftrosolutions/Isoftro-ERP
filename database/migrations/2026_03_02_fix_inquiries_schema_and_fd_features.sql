-- =============================================================================
-- Hamro ERP — Front Desk Phase 0 + Phase 2 Migration
-- Date: 2026-03-02
-- Description:
--   Phase 0: Fix broken `inquiries` schema (deleted_at, enum mismatch, source format)
--   Phase 2: Extend `inquiries` with inquiry_type + appointment/visitor columns
--            Extend `students` with id_card columns
--            Fix payment_transactions NULL timestamps
-- =============================================================================
-- BACKUP COMMAND (run before executing this file):
--   mysqldump -u root hamrolabs_db inquiries > inquiries_backup.sql
-- =============================================================================

USE hamrolabs_db;

-- ─── 1. FIX: Add missing deleted_at to inquiries ────────────────────────────
ALTER TABLE `inquiries`
  ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  ADD INDEX IF NOT EXISTS `idx_inquiries_deleted` (`deleted_at`);

-- ─── 2. FIX: Extend status enum (additive — keeps existing values safe) ──────
ALTER TABLE `inquiries`
  MODIFY COLUMN `status` ENUM(
    'pending',
    'contacted',
    'admitted',
    'closed',
    'follow_up',
    'converted',
    'open',
    'in_progress',
    'resolved'
  ) NOT NULL DEFAULT 'pending';

-- ─── 3. FIX: Normalize source column (hyphen → underscore) ──────────────────
ALTER TABLE `inquiries`
  MODIFY COLUMN `source` VARCHAR(100) DEFAULT 'walk_in';

UPDATE `inquiries`
  SET `source` = REPLACE(`source`, '-', '_')
  WHERE `source` LIKE '%--%' OR `source` = 'walk-in';

-- ─── 4. FEATURE: Add inquiry_type column (multi-purpose hub) ─────────────────
--   'inquiry'     = standard student inquiry (default, preserves existing rows)
--   'visitor'     = visitor log entry
--   'appointment' = scheduled appointment
--   'call_log'    = inbound/outbound call record
--   'complaint'   = complaint registration
ALTER TABLE `inquiries`
  ADD COLUMN IF NOT EXISTS `inquiry_type` ENUM(
    'inquiry', 'visitor', 'appointment', 'call_log', 'complaint'
  ) NOT NULL DEFAULT 'inquiry' AFTER `tenant_id`;

-- Index for fast type-filtered queries
ALTER TABLE `inquiries`
  ADD INDEX IF NOT EXISTS `idx_type_tenant_date` (`tenant_id`, `inquiry_type`, `created_at`);

-- ─── 5. FEATURE: Appointment scheduling columns ──────────────────────────────
ALTER TABLE `inquiries`
  ADD COLUMN IF NOT EXISTS `appointment_date` DATE NULL DEFAULT NULL AFTER `notes`,
  ADD COLUMN IF NOT EXISTS `appointment_time` TIME NULL DEFAULT NULL AFTER `appointment_date`;

-- ─── 6. FEATURE: Visitor log check-in/check-out ─────────────────────────────
ALTER TABLE `inquiries`
  ADD COLUMN IF NOT EXISTS `check_in_at`  TIMESTAMP NULL DEFAULT NULL AFTER `appointment_time`,
  ADD COLUMN IF NOT EXISTS `check_out_at` TIMESTAMP NULL DEFAULT NULL AFTER `check_in_at`;

-- ─── 7. FEATURE: ID Card tracking on students table ─────────────────────────
ALTER TABLE `students`
  ADD COLUMN IF NOT EXISTS `id_card_status` ENUM('none','requested','processing','issued')
    NOT NULL DEFAULT 'none' AFTER `registration_status`,
  ADD COLUMN IF NOT EXISTS `id_card_issued_at` TIMESTAMP NULL DEFAULT NULL
    AFTER `id_card_status`,
  ADD INDEX IF NOT EXISTS `idx_students_id_card` (`tenant_id`, `id_card_status`);

-- ─── 8. FIX: payment_transactions NULL timestamps ───────────────────────────
ALTER TABLE `payment_transactions`
  MODIFY COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  MODIFY COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP;

-- ─── Verification queries (run after migration to confirm) ───────────────────
-- SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
--   FROM INFORMATION_SCHEMA.COLUMNS
--   WHERE TABLE_NAME = 'inquiries' AND TABLE_SCHEMA = 'hamrolabs_db'
--   AND COLUMN_NAME IN ('deleted_at','inquiry_type','appointment_date','appointment_time','check_in_at','check_out_at','status','source');
--
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
--   WHERE TABLE_NAME = 'students' AND TABLE_SCHEMA = 'hamrolabs_db'
--   AND COLUMN_NAME IN ('id_card_status','id_card_issued_at');
