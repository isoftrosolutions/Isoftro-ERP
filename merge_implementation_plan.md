# HamroLabs ERP - Database Consolidation & Efficiency Plan

This document outlines the strategy for a "Lean Schema" to improve query performance, reduce JOIN overhead, and simplify backend logic.

> [!IMPORTANT]
> This is a proposal. **No SQL will be executed automatically.** Review the "Edit Points" section to understand the impact on the PHP codebase.

## 1. The "Lean Schema" Cleanup
### Drop Strategy (Unused/Zombie Tables)
The following tables will be removed. They have 0-1 references in the active logic layers.
- **Auth/Security:** `api_keys`, `api_logs`, `login_attempts`, `password_resets`, `impersonation_logs`.
- **Orphaned Features:** `dashboard_checklists`, `dashboard_targets`, `exam_questions`, `questions`, `workflow_checklists`.
- **LMS Redundancy:** `invoice_items` (logic is in `invoice_ledger`), `mail_logs` (use `email_logs`).

---

## 2. Table Merging & Optimization
### A. The "Vitals" Consolidation (Settings Cluster)
**Target:** Merge 5 fragmented tables into a single source.
- **Source Tables:** `attendance_settings`, `fee_settings`, `email_settings`, `sms_settings`, `tenant_email_settings`.
- **Destination:** `tenants` table (as a `settings` JSON column).
- **Benefit:** Reduces application boot time by eliminating 5 separate queries/joins per request.

### B. The Financial Unified Ledger (Payment Cluster)
**Target:** Consolidate student inflow tracking.
- **Source Tables:** `student_payments`, `payments` (student entries only).
- **Destination:** `payment_transactions`.
- **Strategy:** Map `enrollment_id` from `student_payments` to a metadata column in `payment_transactions`.
- **Benefit:** Single source of truth for all student financial history.

### C. Invoice Simplification
**Target:** One invoice table for all billing.
- **Source Tables:** `student_invoices`.
- **Destination:** `invoices`.
- **Benefit:** Centralized PDF generation and status tracking.

---

## 3. Post-Migration "Edit Points" (Codebase Impacts)

If we merge these tables, the following files **MUST** be updated to point to the new structure:

| Functional Area | Primary Files to Edit | Change Required |
| :--- | :--- | :--- |
| **App Initialization** | `app/Http/Middleware/TenantMiddleware.php` | Load all settings from `tenants.settings` JSON in one step. |
| **Financial Services** | [app/Services/FinanceService.php](file:///c:/Apache24/htdocs/erp/app/Services/FinanceService.php) | Update `recordPayment()` to use `payment_transactions` exclusively. |
| **Fee Management** | [app/Http/Controllers/Admin/fees.php](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/Admin/fees.php) | Update action `get_payment_history` to remove potential joins to `payments`. |
| **Models** | [app/Models/StudentInvoice.php](file:///c:/Apache24/htdocs/erp/app/Models/StudentInvoice.php) | Update `$table` property to `invoices` or delete if redundant. |
| **Dashboard** | [app/Services/DashboardCacheService.php](file:///c:/Apache24/htdocs/erp/app/Services/DashboardCacheService.php) | Update stats aggregation queries to use the unified tables. |

---

## 4. Migration SQL (Preview Only)

```sql
-- STEP 1: DROP UNUSED
DROP TABLE IF EXISTS api_keys, api_logs, login_attempts, password_resets, impersonation_logs;
DROP TABLE IF EXISTS dashboard_checklists, dashboard_targets, exam_questions, questions, workflow_checklists;

-- STEP 2: PREPARE TENANTS FOR SETTINGS
ALTER TABLE tenants ADD COLUMN settings JSON DEFAULT NULL;

-- STEP 3: MIGRATE SETTINGS (Example)
-- UPDATE tenants t 
-- SET settings = JSON_OBJECT('fee_prefix', (SELECT invoice_prefix FROM fee_settings fs WHERE fs.tenant_id = t.id))
-- WHERE EXISTS (SELECT 1 FROM fee_settings fs WHERE fs.tenant_id = t.id);

-- STEP 4: CLEANUP OLD SETTINGS TABLES
-- DROP TABLE fee_settings, attendance_settings, email_settings...
```

---

## 5. Risk Assessment
- **Low Risk:** Dropping Category A tables (verified 0 usage).
- **Medium Risk:** Settings consolidation (Requires updating the `TenantScoped` trait or global helpers).
- **High Risk:** Payment merging (requires careful data migration of historical payment records).

---

### Approval Required
Please review the **Edit Points** above. If you approve, I will proceed with creating the individual PHP refactoring scripts before providing the final SQL execution command.
