# HamroLabs ERP: Student Table Optimization Plan

This plan aims to transform the `students` table from a "flat and redundant" structure into a modern, normalized relational model.

## Phase 1: Data Normalization (User & Guardian Sync)
**Objective:** Eliminate redundant "Personal Info" that exists in both `students` and `users`.

1.  **Migration Path:**
    *   Verify every student has a linked `user_id`. For those who don't, create a placeholder user account.
    *   Move any unique data in `students.full_name`, `email`, or `phone` to the linked `users` record if the user record is empty.
2.  **Removal:**
    *   DROP columns: `full_name`, `email`, `phone` from `students`.
3.  **Guardian Migration:**
    *   Convert `father_name`, `mother_name`, and `guardian_name` into records in the `guardians` table.
    *   DROP columns: `father_name`, `mother_name`, `husband_name`, `guardian_name`, `guardian_relation`.

## Phase 2: Relational Integrity (Enrollment & History)
**Objective:** Prevent "Batch Desynchronization" errors.

1.  **De-duplication:**
    *   Ensure all data in `students.batch_id` matches the current active record in the `enrollments` table.
2.  **Removal:**
    *   DROP column: `batch_id` from `students`.
3.  **Outcome:** The `enrollments` table becomes the **sole** authority for which student belongs to which batch. This allows for clean "Session Advancement" (moving students to the next year) without breaking historical reports.

## Phase 3: Schema Hardening & Localization
**Objective:** Enforce strict data structures and align with local (BS) calendar preferences.

1.  **Calendar Localization:**
    *   **Action:** Remove `dob_ad` (AD) column.
    *   **Primary Source:** Use `dob_bs` (BS) as the sole source of record for Birth Dates.
    *   **Technical Note:** A PHP helper/service will be implemented to convert BS strings to AD on-the-fly when system-level age calculations or date comparisons are required.
2.  **JSON Constraints:**
    *   ALTER `permanent_address` to include `CHECK (json_valid(permanent_address))`.
    *   ALTER `academic_qualifications` to include `CHECK (json_valid(academic_qualifications))`.
3.  **Status Simplification:**
    *   Merge `registration_mode` and `registration_status` into a single `completeness_score` (TINYINT: 0-100) or a single `proc_status` enum.

## Phase 4: Codebase Refactoring
**Impacted files that require updates:**

| File | Change |
| :--- | :--- |
| [app/Models/Student.php](file:///c:/Apache24/htdocs/erp/app/Models/Student.php) | Add `protected $with = ['user']` to automatically fetch name/email. |
| [app/Services/StudentService.php](file:///c:/Apache24/htdocs/erp/app/Services/StudentService.php) | Update `registerStudent()` to handle multi-table insertion (User -> Student -> Enrollment). |
| [app/Http/Controllers/Admin/students.php](file:///c:/Apache24/htdocs/erp/app/Http/Controllers/Admin/students.php) | Update search queries to use `JOIN users u ON s.user_id = u.id`. |
| `resources/js/components/StudentProfile.vue` | Update props to look for `student.user.full_name` instead of `student.full_name`. |

---

## ⚡ Execution Strategy (The "Safe Mode")
1.  **Dry Run Script:** Create a PHP script that logs how many records would be moved/deleted.
2.  **The Shadow Columns:** Create new relations first, and only DROP the old ones after 24 hours of successful testing.
3.  **Automated Integrity Check:** A script that compares the old `students.full_name` with the new `users.name` to ensure 100% accuracy.

**Shall I begin by creating the "Pre-Optimization Audit" script to see exactly how much data needs to be moved?**
