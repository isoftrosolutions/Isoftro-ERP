-- ============================================================
-- Hamro ERP — Student Admission Flow: Schema Fix Migration
-- File: 2026_03_04_admission_fixes.sql
-- Run once in a maintenance window. All statements are idempotent
-- where possible. Back up the database before running.
-- ============================================================

-- -------------------------------------------------------
-- ISSUE-V3: Fix payment_transactions timestamps stuck at NULL
-- -------------------------------------------------------
UPDATE payment_transactions
SET    created_at = NOW(),
       updated_at = NOW()
WHERE  created_at IS NULL;

-- -------------------------------------------------------
-- ISSUE-V2: Normalize academic_year format to "YYYY-YYYY"
-- Records stored as just "2026" get updated to "2026-2027"
-- -------------------------------------------------------
UPDATE fee_records
SET    academic_year = CONCAT(
           YEAR(COALESCE(created_at, NOW())),
           '-',
           YEAR(COALESCE(created_at, NOW())) + 1
       )
WHERE  academic_year NOT LIKE '%-%'
   AND academic_year != '';

-- -------------------------------------------------------
-- ISSUE-D4: Correct fee_items that have type='admission'
-- but are actually monthly tuition fees
-- -------------------------------------------------------
UPDATE fee_items
SET    type = 'monthly'
WHERE  name LIKE 'Tuition Fee%'
  AND  type  = 'admission';

-- -------------------------------------------------------
-- ISSUE-D1: Add FK constraint students.batch_id → batches.id
-- Using RESTRICT so a batch with students cannot be hard-deleted
-- (Only run if constraint does not already exist)
-- -------------------------------------------------------
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE  TABLE_SCHEMA   = DATABASE()
      AND  TABLE_NAME     = 'students'
      AND  CONSTRAINT_NAME = 'fk_students_batch'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE `students`
     ADD CONSTRAINT `fk_students_batch`
     FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`)
     ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT "fk_students_batch already exists, skipping" AS note'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -------------------------------------------------------
-- ISSUE-D3: Add status_changed_at to enrollments for audit trail
-- -------------------------------------------------------
ALTER TABLE `enrollments`
ADD COLUMN IF NOT EXISTS `status_changed_at` timestamp NULL DEFAULT NULL
    COMMENT 'Timestamp when enrollment status last changed (completed/dropped/transferred)';

-- -------------------------------------------------------
-- ISSUE-D5: Add composite index for student list queries
-- Covers: tenant_id + status + soft-delete filter
-- -------------------------------------------------------
CREATE INDEX IF NOT EXISTS `idx_students_tenant_status`
    ON `students` (`tenant_id`, `status`, `deleted_at`);

-- -------------------------------------------------------
-- ISSUE-V6: Document roll number format change
-- Old format: "000005", "000006" (admin auto-assign)
-- New format: "STD-0040" (from StudentService.generateRollNo)
-- No data change — this is a note for reporting queries.
-- To align old records (optional, uncomment if desired):
-- -------------------------------------------------------
-- UPDATE students
-- SET    roll_no = CONCAT('STD-', LPAD(CAST(roll_no AS UNSIGNED), 4, '0'))
-- WHERE  roll_no REGEXP '^[0-9]+$';

-- -------------------------------------------------------
-- SUMMARY
-- -------------------------------------------------------
SELECT 'Migration 2026_03_04_admission_fixes.sql completed.' AS result;
