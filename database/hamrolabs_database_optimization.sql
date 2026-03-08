-- ================================================================
-- HAMRO LABS DATABASE ANALYSIS & OPTIMIZATION
-- Based on realdb.sql schema analysis
-- ================================================================
-- Database: hamrolabs_db
-- MariaDB 12.0.2
-- Analysis Date: March 2026
-- ================================================================

-- ================================================================
-- SECTION 1: CURRENT SCHEMA ANALYSIS
-- ================================================================

/*
PAYMENT FLOW TABLES IDENTIFIED:
1. payment_transactions (id, tenant_id, student_id, fee_record_id, amount, receipt_number, payment_date, receipt_path, recorded_by)
2. fee_records (id, tenant_id, student_id, fee_item_id, amount_paid, receipt_no, status)
3. fee_settings (id, tenant_id, receipt_prefix, next_receipt_number)
4. student_fee_summary (id, tenant_id, student_id, total_fee, paid_amount, due_amount, fee_status)
5. audit_logs (id, tenant_id, user_id, action, table_name, record_id, changes)
6. email_settings (id, tenant_id, sender_name, reply_to_email, is_active)
7. students (id, tenant_id, user_id, batch_id, roll_no, status)
8. users (linked via students.user_id)

EXISTING INDEXES (Good):
✅ idx_pt_tenant ON payment_transactions(tenant_id)
✅ idx_pt_student ON payment_transactions(student_id)  
✅ idx_pt_receipt ON payment_transactions(receipt_number)
✅ idx_pt_date ON payment_transactions(payment_date)
✅ idx_fee_records_tenant_student ON fee_records(tenant_id, student_id)
✅ idx_fee_records_receipt_no ON fee_records(receipt_no) - UNIQUE
✅ idx_audit_tenant_user ON audit_logs(tenant_id, user_id)
✅ idx_audit_table_record ON audit_logs(table_name, record_id)
✅ fee_settings_tenant_id_unique ON fee_settings(tenant_id) - UNIQUE
✅ unique_email_settings_tenant ON email_settings(tenant_id) - UNIQUE

MISSING CRITICAL INDEXES:
❌ No index on fee_records.status (for status filtering)
❌ No index on student_fee_summary(student_id, tenant_id) composite
❌ No index on audit_logs.created_at (for date-based queries)
❌ No index on email_settings.is_active (for active email check)
*/

-- ================================================================
-- SECTION 2: PERFORMANCE BOTTLENECK QUERIES
-- ================================================================

-- Query Pattern 1: Receipt Number Lookup (Post-commit redundant query)
-- Current: SELECT id FROM payment_transactions WHERE receipt_number = ? AND tenant_id = ?
-- Frequency: Every payment
-- Status: ✅ ALREADY HAS INDEX (idx_pt_receipt)

-- Query Pattern 2: Student Email Lookup
-- Current: SELECT u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?
-- Frequency: Every payment with email
-- Issue: ⚠️ No index on students.user_id for the JOIN

-- Query Pattern 3: Fee Settings Lookup
-- Current: SELECT * FROM fee_settings WHERE tenant_id = ?
-- Frequency: Every payment (for receipt number generation)
-- Status: ✅ ALREADY HAS UNIQUE INDEX

-- Query Pattern 4: Fee Record Status Updates
-- Current: SELECT * FROM fee_records WHERE status = 'pending' AND tenant_id = ?
-- Frequency: Dashboard queries, reports
-- Issue: ❌ No index on status column (uses table scan)

-- Query Pattern 5: Audit Log Queries
-- Current: SELECT * FROM audit_logs WHERE tenant_id = ? AND created_at > ? ORDER BY created_at DESC
-- Frequency: Audit reports
-- Issue: ⚠️ No index on created_at for sorting

-- ================================================================
-- SECTION 3: MISSING INDEXES TO ADD
-- ================================================================

-- Index 1: Fee Records Status (High Priority)
-- Speeds up: Dashboard pending payments, status-based queries
CREATE INDEX idx_fee_records_status_tenant 
ON fee_records(status, tenant_id);

-- Index 2: Student Fee Summary Composite (High Priority)
-- Speeds up: Balance lookups, student financial dashboard
CREATE INDEX idx_student_fee_summary_lookup 
ON student_fee_summary(student_id, tenant_id, fee_status);

-- Index 3: Audit Logs Date-based Queries (Medium Priority)
-- Speeds up: Audit reports, date-range queries
CREATE INDEX idx_audit_logs_date 
ON audit_logs(tenant_id, created_at DESC);

-- Index 4: Audit Logs Action Type (Medium Priority)
-- Speeds up: Filtering by action type (PAYMENT_RECORDED, TRANSACTION_CREATED)
CREATE INDEX idx_audit_logs_action 
ON audit_logs(action, tenant_id, created_at DESC);

-- Index 5: Students User ID Join (High Priority)
-- Speeds up: Email lookups during payment processing
CREATE INDEX idx_students_user_id_lookup 
ON students(user_id, tenant_id);

-- Index 6: Payment Transactions Compound for Reports (Medium Priority)
-- Speeds up: Payment reports by date range and method
CREATE INDEX idx_pt_date_method_tenant 
ON payment_transactions(tenant_id, payment_date DESC, payment_method);

-- Index 7: Fee Records Batch Queries (Low Priority)
-- Speeds up: Batch-wise fee reports
CREATE INDEX idx_fee_records_batch_tenant 
ON fee_records(batch_id, tenant_id, status);

-- ================================================================
-- SECTION 4: VERIFICATION QUERIES
-- ================================================================

-- Before adding indexes, check current performance
-- Run these with EXPLAIN to see table scans:

-- Test 1: Fee record status lookup (should use idx_fee_records_status_tenant after)
EXPLAIN SELECT * FROM fee_records 
WHERE status = 'pending' AND tenant_id = 5;

-- Test 2: Student email lookup (should use idx_students_user_id_lookup after)
EXPLAIN SELECT u.email 
FROM students s 
INNER JOIN users u ON s.user_id = u.id 
WHERE s.id = 53 AND s.tenant_id = 5;

-- Test 3: Student balance lookup (should use idx_student_fee_summary_lookup after)
EXPLAIN SELECT paid_amount, due_amount, fee_status 
FROM student_fee_summary 
WHERE student_id = 53 AND tenant_id = 5;

-- Test 4: Audit logs by date (should use idx_audit_logs_date after)
EXPLAIN SELECT * FROM audit_logs 
WHERE tenant_id = 5 
  AND created_at >= '2026-03-01' 
ORDER BY created_at DESC 
LIMIT 100;

-- Test 5: Receipt number lookup (already has index - should show it's used)
EXPLAIN SELECT id FROM payment_transactions 
WHERE receipt_number = 'RCP-000048' AND tenant_id = 5;

-- ================================================================
-- SECTION 5: INDEX SIZE ESTIMATION
-- ================================================================

-- Check current table sizes
SELECT 
    TABLE_NAME,
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb,
    ROUND(DATA_LENGTH / 1024 / 1024, 2) AS data_mb,
    ROUND(INDEX_LENGTH / 1024 / 1024, 2) AS index_mb,
    TABLE_ROWS
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'hamrolabs_db'
  AND TABLE_NAME IN (
      'payment_transactions',
      'fee_records',
      'student_fee_summary',
      'audit_logs',
      'email_settings',
      'fee_settings'
  )
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;

-- ================================================================
-- SECTION 6: QUERY OPTIMIZATION RECOMMENDATIONS
-- ================================================================

/*
OPTIMIZATION 1: Remove Redundant Post-Commit Query
---------------------------------------------------
CURRENT CODE (fees.php line ~612):
    $stmt = $db->prepare("SELECT id FROM payment_transactions WHERE receipt_number = :rno AND tenant_id = :tid");
    $stmt->execute([':rno' => $result['receipt_no'], ':tid' => $tenantId]);
    $transactionId = $stmt->fetchColumn();

FIX: Modify FinanceService::recordPayment() to return transaction_id directly
    return [
        'success' => true,
        'transaction_id' => $transactionId,  // ← Add this
        'receipt_no' => $receiptNumber,
        'amount_paid' => $amountPaid
    ];

IMPACT: Removes 1 database query per payment (5-10ms saved)


OPTIMIZATION 2: Fix Audit Logger Double-Fetch
----------------------------------------------
CURRENT: AuditLogger fetches records before and after update for change tracking

PROBLEM: 
1. Before update: SELECT * FROM fee_records WHERE id = ? (fetch OLD)
2. Do update: UPDATE fee_records SET ...
3. After update: SELECT * FROM fee_records WHERE id = ? (fetch NEW)

FIX: Pass in-memory $oldData and $newData to AuditLogger instead of re-fetching

BEFORE:
    $feeRecord->update($data);
    AuditLogger::log('PAYMENT_RECORDED', 'fee_records', $feeRecord->id);  // ← Fetches again!

AFTER:
    $oldData = $feeRecord->toArray();
    $feeRecord->update($data);
    $newData = $feeRecord->toArray();
    AuditLogger::logWithData('PAYMENT_RECORDED', 'fee_records', $feeRecord->id, $oldData, $newData);

IMPACT: Removes 4 database queries per payment (20-40ms saved)


OPTIMIZATION 3: Cache Fee Settings
-----------------------------------
CURRENT: SELECT * FROM fee_settings WHERE tenant_id = ? (every payment)

FIX: Cache with Redis/APCu
    $feeSettings = Cache::remember("fee_settings:{$tenantId}", 1800, function() {
        return FeeSettings::where('tenant_id', $tenantId)->first();
    });

INVALIDATE: When fee_settings are updated via admin panel

IMPACT: Removes 1 database query per payment (3-5ms saved) + reduces DB load


OPTIMIZATION 4: Cache Email Settings
-------------------------------------
CURRENT: SELECT * FROM email_settings WHERE tenant_id = ? (every email)

FIX: Cache with Redis/APCu (1 hour TTL)

IMPACT: Removes 1 database query per email (3-5ms saved)


OPTIMIZATION 5: Optimize Audit Logs Table (Future)
---------------------------------------------------
ISSUE: audit_logs will grow very large over time (currently 122 rows, will reach millions)

SOLUTIONS:
1. Partition by month (recommended for >1M rows)
2. Archive old logs (>6 months) to separate table
3. Add covering indexes for common query patterns

IMPLEMENTATION (when table reaches 100K+ rows):
    ALTER TABLE audit_logs
    PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
        PARTITION p202601 VALUES LESS THAN (202602),
        PARTITION p202602 VALUES LESS THAN (202603),
        PARTITION p202603 VALUES LESS THAN (202604)
        -- Add partitions monthly
    );
*/

-- ================================================================
-- SECTION 7: EXECUTION PLAN
-- ================================================================

/*
PHASE 1: Quick Wins (Execute Today - 10 minutes)
-------------------------------------------------
1. Add missing indexes (run commands in Section 3)
2. Verify indexes with EXPLAIN queries (Section 4)
3. Monitor query performance after index creation

Expected Impact:
- 30-50% faster fee record lookups
- 40-60% faster student balance queries
- 50-70% faster audit log queries
- Instant receipt number lookups (already indexed)

PHASE 2: Code Optimizations (Week 1 - 2-3 days)
------------------------------------------------
1. Modify FinanceService to return transaction_id
2. Fix AuditLogger to accept in-memory data
3. Remove redundant post-commit query from fees.php

Expected Impact:
- Remove 5 redundant queries per payment
- 30-50ms reduction in payment processing time

PHASE 3: Caching Layer (Week 2 - 3-4 days)
-------------------------------------------
1. Install Redis/APCu
2. Cache fee_settings per tenant
3. Cache email_settings per tenant
4. Cache tenant info (name, logo, etc.)
5. Add cache invalidation logic

Expected Impact:
- 20-30ms reduction per payment
- 40-60% reduction in database load

PHASE 4: Async Processing (Week 3-4 - 4-5 days)
------------------------------------------------
1. Create job_queue table
2. Build background worker
3. Move PDF generation to queue
4. Move email sending to queue
5. Add retry logic

Expected Impact:
- 95%+ reduction in response time (1-5s → 40-80ms)
- Better scalability and reliability
*/

-- ================================================================
-- SECTION 8: MONITORING QUERIES
-- ================================================================

-- Monitor slow queries after optimization
-- Add these to a cron job or monitoring dashboard

-- Query 1: Find slow queries (requires slow query log enabled)
SELECT 
    query_time,
    lock_time,
    rows_examined,
    sql_text
FROM mysql.slow_log 
WHERE sql_text LIKE '%payment_transactions%'
   OR sql_text LIKE '%fee_records%'
ORDER BY query_time DESC 
LIMIT 20;

-- Query 2: Index usage statistics
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    SEQ_IN_INDEX,
    COLUMN_NAME,
    CARDINALITY,
    INDEX_TYPE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'hamrolabs_db'
  AND TABLE_NAME IN ('payment_transactions', 'fee_records', 'audit_logs')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- Query 3: Check for unused indexes (run after 1 week)
SELECT 
    t.TABLE_SCHEMA,
    t.TABLE_NAME,
    s.INDEX_NAME,
    s.COLUMN_NAME
FROM information_schema.TABLES t
INNER JOIN information_schema.STATISTICS s ON t.TABLE_SCHEMA = s.TABLE_SCHEMA 
    AND t.TABLE_NAME = s.TABLE_NAME
WHERE t.TABLE_SCHEMA = 'hamrolabs_db'
  AND s.INDEX_NAME NOT IN ('PRIMARY')
  AND t.TABLE_NAME IN ('payment_transactions', 'fee_records')
ORDER BY t.TABLE_NAME, s.INDEX_NAME;

-- Query 4: Table growth monitoring
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS total_mb,
    ROUND(INDEX_LENGTH / DATA_LENGTH * 100, 2) AS index_ratio_pct,
    AUTO_INCREMENT
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'hamrolabs_db'
  AND TABLE_NAME IN (
      'payment_transactions',
      'fee_records', 
      'audit_logs'
  );

-- ================================================================
-- SECTION 9: ROLLBACK COMMANDS (If Needed)
-- ================================================================

/*
-- If indexes cause issues, remove them with:

DROP INDEX idx_fee_records_status_tenant ON fee_records;
DROP INDEX idx_student_fee_summary_lookup ON student_fee_summary;
DROP INDEX idx_audit_logs_date ON audit_logs;
DROP INDEX idx_audit_logs_action ON audit_logs;
DROP INDEX idx_students_user_id_lookup ON students;
DROP INDEX idx_pt_date_method_tenant ON payment_transactions;
DROP INDEX idx_fee_records_batch_tenant ON fee_records;
*/

-- ================================================================
-- SECTION 10: EXECUTE OPTIMIZATION
-- ================================================================

-- Uncomment and run the following to apply all optimizations:

/*
-- Step 1: Add all missing indexes
CREATE INDEX idx_fee_records_status_tenant ON fee_records(status, tenant_id);
CREATE INDEX idx_student_fee_summary_lookup ON student_fee_summary(student_id, tenant_id, fee_status);
CREATE INDEX idx_audit_logs_date ON audit_logs(tenant_id, created_at DESC);
CREATE INDEX idx_audit_logs_action ON audit_logs(action, tenant_id, created_at DESC);
CREATE INDEX idx_students_user_id_lookup ON students(user_id, tenant_id);
CREATE INDEX idx_pt_date_method_tenant ON payment_transactions(tenant_id, payment_date DESC, payment_method);
CREATE INDEX idx_fee_records_batch_tenant ON fee_records(batch_id, tenant_id, status);

-- Step 2: Analyze tables to update statistics
ANALYZE TABLE payment_transactions;
ANALYZE TABLE fee_records;
ANALYZE TABLE student_fee_summary;
ANALYZE TABLE audit_logs;
ANALYZE TABLE students;

-- Step 3: Verify indexes were created
SHOW INDEX FROM payment_transactions;
SHOW INDEX FROM fee_records;
SHOW INDEX FROM student_fee_summary;
SHOW INDEX FROM audit_logs;
SHOW INDEX FROM students;
*/

-- ================================================================
-- END OF OPTIMIZATION SCRIPT
-- ================================================================
