# Database Upgrade Plan — Hamro Labs Academic ERP Expansion
**Version 1.0 | Prepared for Hamro Labs Pvt. Ltd. | March 2026**

---

## Overview

This document defines all database schema modifications required to support expansion into Computer Training, Bridge Course, Tuition Center, and Skill/CTEVT segments. Every change follows the existing architectural principles:

- All tables include `tenant_id` as a non-nullable indexed foreign key
- Laravel Global Scope automatically scopes all queries to the current tenant
- No breaking changes to existing tables used by Loksewa institutes
- New columns added to existing tables use `NULL` default to ensure backwards compatibility
- All migrations use `up()` and `down()` methods for safe rollback

---

## Section 1: Modifications to Existing Tables

### 1.1 `tenants` table

Add institute type classification at the root tenant level.

```sql
ALTER TABLE tenants
  ADD COLUMN institute_type ENUM(
    'loksewa',
    'computer_training',
    'bridge_course',
    'tuition_center',
    'skill_ctevt',
    'general_coaching'
  ) NOT NULL DEFAULT 'loksewa' AFTER plan_id,

  ADD COLUMN institute_type_confirmed_at TIMESTAMP NULL AFTER institute_type;
```

**Migration file:** `2026_04_01_000001_add_institute_type_to_tenants_table.php`

**Notes:**
- Default value `'loksewa'` ensures all existing tenants are unaffected
- `institute_type_confirmed_at` records when the institute confirmed their type during onboarding wizard
- Feature profile middleware reads `institute_type` to activate the correct module set

---

### 1.2 `courses` table

Extend the course category enum to support non-Loksewa institute types.

```sql
ALTER TABLE courses
  MODIFY COLUMN category ENUM(
    -- Existing Loksewa categories
    'Loksewa',
    'Health',
    'Banking',
    'TSC',
    'General',
    -- New: Computer Training
    'BasicComputer',
    'Tally',
    'GraphicDesign',
    'Programming',
    'WebDevelopment',
    'Networking',
    'ComputerDiploma',
    -- New: Bridge Course
    'ScienceBridge',
    'ManagementBridge',
    'HumanitiesBridge',
    'EntrancePrep',
    -- New: Tuition Center
    'TuitionSubject',
    -- New: CTEVT
    'CTEVT_IT',
    'CTEVT_Hospitality',
    'CTEVT_Construction',
    'CTEVT_Agriculture',
    'CTEVT_Health',
    'CTEVT_General'
  ) NOT NULL DEFAULT 'General',

  ADD COLUMN ctevt_program_code VARCHAR(30) NULL AFTER category COMMENT 'CTEVT official program code, e.g. COMP-S-160h',
  ADD COLUMN required_hours DECIMAL(6,2) NULL AFTER ctevt_program_code COMMENT 'For CTEVT/hours-based courses only',
  ADD COLUMN is_monthly_subject BOOLEAN NOT NULL DEFAULT 0 AFTER required_hours COMMENT '1 = Tuition subject with monthly fee billing',
  ADD COLUMN monthly_fee_amount DECIMAL(10,2) NULL AFTER is_monthly_subject COMMENT 'Default monthly fee for tuition subjects';
```

**Migration file:** `2026_04_01_000002_extend_courses_table_for_new_segments.php`

---

### 1.3 `attendance` table

Add hours tracking for CTEVT compliance and session type classification.

```sql
ALTER TABLE attendance
  ADD COLUMN session_duration_hours DECIMAL(4,2) NULL AFTER status COMMENT 'For CTEVT institutes: hours of this session (e.g., 3.00)',
  ADD COLUMN session_type ENUM('regular','theory','practical','field_visit','lab') NOT NULL DEFAULT 'regular' AFTER session_duration_hours;
```

**Migration file:** `2026_04_01_000003_add_hours_tracking_to_attendance_table.php`

**Notes:**
- `session_duration_hours` is NULL for all non-CTEVT institutes — zero performance impact
- `session_type` defaults to `'regular'` for all existing records — backwards compatible
- An index on `(tenant_id, student_id, batch_id, session_type)` supports the cumulative hours query

```sql
CREATE INDEX idx_attendance_hours ON attendance(tenant_id, student_id, batch_id, session_type);
```

---

### 1.4 `batches` table

Add stream classification for bridge course institutes.

```sql
ALTER TABLE batches
  ADD COLUMN stream ENUM('Science','Management','Humanities','IT','General','CTEVT') NULL AFTER shift COMMENT 'For Bridge Course stream classification',
  ADD COLUMN is_seasonal BOOLEAN NOT NULL DEFAULT 0 AFTER stream COMMENT '1 = Bridge seasonal batch, auto-archive eligible',
  ADD COLUMN season_end_date DATE NULL AFTER is_seasonal;
```

**Migration file:** `2026_04_01_000004_add_stream_fields_to_batches_table.php`

---

### 1.5 `students` table

Add grade level field for tuition center students.

```sql
ALTER TABLE students
  ADD COLUMN grade_level TINYINT UNSIGNED NULL AFTER gender COMMENT 'Grade 1-12, for Tuition Center students',
  ADD COLUMN school_name VARCHAR(150) NULL AFTER grade_level COMMENT 'Current school name, for Tuition Center students';
```

**Migration file:** `2026_04_01_000005_add_grade_level_to_students_table.php`

---

## Section 2: New Tables

### 2.1 `labs` — Lab/Computer Room Registry

```sql
CREATE TABLE labs (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id       BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(100) NOT NULL COMMENT 'e.g., Lab A, Ground Floor Lab',
  room_number     VARCHAR(30) NULL,
  total_pcs       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  functional_pcs  SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Excludes under-repair units',
  status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  notes           TEXT NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  CONSTRAINT fk_labs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  INDEX idx_labs_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.2 `lab_pcs` — Individual PC/Workstation Registry

```sql
CREATE TABLE lab_pcs (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id       BIGINT UNSIGNED NOT NULL,
  lab_id          BIGINT UNSIGNED NOT NULL,
  pc_number       VARCHAR(20) NOT NULL COMMENT 'e.g., PC-01, WS-12',
  status          ENUM('functional','under_repair','retired') NOT NULL DEFAULT 'functional',
  specs           JSON NULL COMMENT 'Optional: {cpu, ram, os}',
  notes           TEXT NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  CONSTRAINT fk_lab_pcs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_lab_pcs_lab FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE,
  UNIQUE KEY uq_pc_per_lab (tenant_id, lab_id, pc_number),
  INDEX idx_lab_pcs_lab (tenant_id, lab_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.3 `lab_allocations` — Batch-to-Lab Schedule Assignments

```sql
CREATE TABLE lab_allocations (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id       BIGINT UNSIGNED NOT NULL,
  lab_id          BIGINT UNSIGNED NOT NULL,
  batch_id        BIGINT UNSIGNED NOT NULL,
  timetable_slot_id BIGINT UNSIGNED NULL COMMENT 'FK to timetable_slots if using timetable builder',
  day_of_week     TINYINT UNSIGNED NULL COMMENT '1=Sun, 2=Mon... 7=Sat',
  start_time      TIME NULL,
  end_time        TIME NULL,
  allocated_pcs   SMALLINT UNSIGNED NULL COMMENT 'How many PCs reserved for this batch',
  effective_from  DATE NOT NULL,
  effective_to    DATE NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  CONSTRAINT fk_lab_alloc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_lab_alloc_lab FOREIGN KEY (lab_id) REFERENCES labs(id),
  CONSTRAINT fk_lab_alloc_batch FOREIGN KEY (batch_id) REFERENCES batches(id),
  INDEX idx_lab_alloc_schedule (tenant_id, lab_id, day_of_week, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.4 `certificate_templates` — Per-Tenant Certificate Design

```sql
CREATE TABLE certificate_templates (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id       BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(150) NOT NULL,
  template_type   ENUM('standard','ctevt','marksheet') NOT NULL DEFAULT 'standard',
  is_default      BOOLEAN NOT NULL DEFAULT 0,
  layout_config   JSON NULL COMMENT '{logo_pos, fields, colors, signature_label}',
  background_image_url VARCHAR(500) NULL,
  footer_text     TEXT NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  CONSTRAINT fk_cert_tmpl_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  INDEX idx_cert_tmpl_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.5 `certificates` — Issued Certificate Registry

```sql
CREATE TABLE certificates (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id       BIGINT UNSIGNED NOT NULL,
  student_id      BIGINT UNSIGNED NOT NULL,
  course_id       BIGINT UNSIGNED NOT NULL,
  batch_id        BIGINT UNSIGNED NULL,
  template_id     BIGINT UNSIGNED NOT NULL,
  certificate_no  VARCHAR(50) NOT NULL COMMENT 'e.g., HL-2081-CERT-00142',
  issued_date_bs  VARCHAR(10) NOT NULL COMMENT 'BS date: 2081-09-15',
  issued_date_ad  DATE NOT NULL,
  hours_completed DECIMAL(6,2) NULL COMMENT 'For CTEVT certificates',
  grade           VARCHAR(20) NULL COMMENT 'e.g., Distinction, Pass, A+',
  verification_token VARCHAR(64) NOT NULL UNIQUE COMMENT 'Public QR/URL token',
  pdf_path        VARCHAR(500) NULL COMMENT 'Wasabi/S3 storage path',
  status          ENUM('draft','issued','revoked') NOT NULL DEFAULT 'draft',
  issued_by_user_id BIGINT UNSIGNED NOT NULL,
  revoked_reason  TEXT NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  CONSTRAINT fk_cert_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_cert_student FOREIGN KEY (student_id) REFERENCES students(id),
  CONSTRAINT fk_cert_course FOREIGN KEY (course_id) REFERENCES courses(id),
  CONSTRAINT fk_cert_template FOREIGN KEY (template_id) REFERENCES certificate_templates(id),
  UNIQUE KEY uq_cert_no_per_tenant (tenant_id, certificate_no),
  INDEX idx_cert_student (tenant_id, student_id),
  INDEX idx_cert_verification (verification_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.6 `student_hours_summary` — CTEVT Hours Cache

```sql
CREATE TABLE student_hours_summary (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id             BIGINT UNSIGNED NOT NULL,
  student_id            BIGINT UNSIGNED NOT NULL,
  batch_id              BIGINT UNSIGNED NOT NULL,
  total_hours_completed DECIMAL(8,2) NOT NULL DEFAULT 0,
  required_hours        DECIMAL(8,2) NOT NULL DEFAULT 0,
  theory_hours          DECIMAL(8,2) NOT NULL DEFAULT 0,
  practical_hours       DECIMAL(8,2) NOT NULL DEFAULT 0,
  field_hours           DECIMAL(8,2) NOT NULL DEFAULT 0,
  is_eligible_for_cert  BOOLEAN NOT NULL DEFAULT 0,
  last_recalculated_at  TIMESTAMP NULL,
  CONSTRAINT fk_hrs_summary_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_hrs_summary_student FOREIGN KEY (student_id) REFERENCES students(id),
  UNIQUE KEY uq_student_batch_hours (tenant_id, student_id, batch_id),
  INDEX idx_hrs_summary_batch (tenant_id, batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.7 `student_subject_enrollments` — Tuition Per-Subject Enrollment

```sql
CREATE TABLE student_subject_enrollments (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id       BIGINT UNSIGNED NOT NULL,
  student_id      BIGINT UNSIGNED NOT NULL,
  course_id       BIGINT UNSIGNED NOT NULL COMMENT 'Subject (course.is_monthly_subject = 1)',
  batch_id        BIGINT UNSIGNED NULL,
  monthly_fee     DECIMAL(10,2) NOT NULL,
  enrolled_date   DATE NOT NULL,
  end_date        DATE NULL,
  status          ENUM('active','inactive','completed') NOT NULL DEFAULT 'active',
  notes           TEXT NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  CONSTRAINT fk_subj_enroll_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_subj_enroll_student FOREIGN KEY (student_id) REFERENCES students(id),
  CONSTRAINT fk_subj_enroll_course FOREIGN KEY (course_id) REFERENCES courses(id),
  INDEX idx_subj_enroll_student (tenant_id, student_id),
  INDEX idx_subj_enroll_status (tenant_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.8 `monthly_billing_cycles` — Monthly Billing Cycle Control

```sql
CREATE TABLE monthly_billing_cycles (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id       BIGINT UNSIGNED NOT NULL,
  billing_month   TINYINT UNSIGNED NOT NULL COMMENT 'BS month: 1-12',
  billing_year    SMALLINT UNSIGNED NOT NULL COMMENT 'BS year: 2081, 2082...',
  generated_at    TIMESTAMP NULL,
  generated_by    BIGINT UNSIGNED NULL,
  total_dues_created SMALLINT UNSIGNED NULL,
  CONSTRAINT fk_billing_cycle_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  UNIQUE KEY uq_billing_cycle (tenant_id, billing_year, billing_month),
  INDEX idx_billing_cycle_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.9 `monthly_fee_dues` — Per-Student Monthly Fee Records

```sql
CREATE TABLE monthly_fee_dues (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id             BIGINT UNSIGNED NOT NULL,
  student_id            BIGINT UNSIGNED NOT NULL,
  subject_enrollment_id BIGINT UNSIGNED NOT NULL,
  billing_cycle_id      BIGINT UNSIGNED NOT NULL,
  amount_due            DECIMAL(10,2) NOT NULL,
  amount_paid           DECIMAL(10,2) NOT NULL DEFAULT 0,
  due_date_bs           VARCHAR(10) NOT NULL,
  due_date_ad           DATE NOT NULL,
  paid_date_bs          VARCHAR(10) NULL,
  paid_date_ad          DATE NULL,
  status                ENUM('pending','partial','paid','overdue','waived') NOT NULL DEFAULT 'pending',
  payment_transaction_id BIGINT UNSIGNED NULL COMMENT 'FK to payment_transactions when paid',
  notes                 TEXT NULL,
  created_at            TIMESTAMP NULL,
  updated_at            TIMESTAMP NULL,
  CONSTRAINT fk_mfd_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_mfd_student FOREIGN KEY (student_id) REFERENCES students(id),
  CONSTRAINT fk_mfd_enrollment FOREIGN KEY (subject_enrollment_id) REFERENCES student_subject_enrollments(id),
  CONSTRAINT fk_mfd_cycle FOREIGN KEY (billing_cycle_id) REFERENCES monthly_billing_cycles(id),
  INDEX idx_mfd_student (tenant_id, student_id, status),
  INDEX idx_mfd_cycle (tenant_id, billing_cycle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.10 `practical_assessments` — Non-MCQ Assessment Records

```sql
CREATE TABLE practical_assessments (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id       BIGINT UNSIGNED NOT NULL,
  batch_id        BIGINT UNSIGNED NOT NULL,
  course_id       BIGINT UNSIGNED NOT NULL,
  title           VARCHAR(200) NOT NULL,
  assessment_type ENUM('practical_task','competency_checklist','project','viva') NOT NULL DEFAULT 'practical_task',
  rubric          JSON NULL COMMENT '[{item, max_marks, pass_mark}] or [{competency_unit, required}]',
  conducted_date_bs VARCHAR(10) NULL,
  conducted_date_ad DATE NULL,
  created_by      BIGINT UNSIGNED NOT NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  CONSTRAINT fk_prac_assess_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  INDEX idx_prac_assess_batch (tenant_id, batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.11 `practical_assessment_results` — Student-Level Results

```sql
CREATE TABLE practical_assessment_results (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id             BIGINT UNSIGNED NOT NULL,
  assessment_id         BIGINT UNSIGNED NOT NULL,
  student_id            BIGINT UNSIGNED NOT NULL,
  rubric_scores         JSON NULL COMMENT 'Scores per rubric item or competency pass/fail',
  total_marks_obtained  DECIMAL(6,2) NULL,
  total_marks_possible  DECIMAL(6,2) NULL,
  overall_result        ENUM('pass','fail','distinction','not_evaluated') NOT NULL DEFAULT 'not_evaluated',
  teacher_remarks       TEXT NULL,
  evaluated_by          BIGINT UNSIGNED NOT NULL,
  evaluated_at          TIMESTAMP NULL,
  created_at            TIMESTAMP NULL,
  updated_at            TIMESTAMP NULL,
  CONSTRAINT fk_par_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_par_assessment FOREIGN KEY (assessment_id) REFERENCES practical_assessments(id),
  UNIQUE KEY uq_student_assessment (tenant_id, assessment_id, student_id),
  INDEX idx_par_student (tenant_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.12 `student_outcomes` — Post-Course Outcome Tracking

```sql
CREATE TABLE student_outcomes (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id       BIGINT UNSIGNED NOT NULL,
  student_id      BIGINT UNSIGNED NOT NULL,
  batch_id        BIGINT UNSIGNED NOT NULL,
  outcome_type    ENUM('entrance_exam','employment','migration','higher_study','other') NOT NULL DEFAULT 'entrance_exam',
  target_institution VARCHAR(200) NULL COMMENT 'College/employer applied to',
  result          ENUM('selected','waitlisted','not_selected','pending','unknown') NOT NULL DEFAULT 'pending',
  scholarship_received BOOLEAN NULL,
  scholarship_amount DECIMAL(10,2) NULL,
  notes           TEXT NULL,
  recorded_by     BIGINT UNSIGNED NOT NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  CONSTRAINT fk_outcome_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  INDEX idx_outcome_batch (tenant_id, batch_id),
  INDEX idx_outcome_student (tenant_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Section 3: Migration Sequence

Run migrations in this exact order to respect foreign key dependencies:

```
Phase 1 (Core schema changes — no new FKs):
1. 2026_04_01_000001_add_institute_type_to_tenants_table
2. 2026_04_01_000002_extend_courses_table_for_new_segments
3. 2026_04_01_000003_add_hours_tracking_to_attendance_table
4. 2026_04_01_000004_add_stream_fields_to_batches_table
5. 2026_04_01_000005_add_grade_level_to_students_table

Phase 2 (New tables — Computer Training):
6. 2026_04_02_000001_create_labs_table
7. 2026_04_02_000002_create_lab_pcs_table
8. 2026_04_02_000003_create_lab_allocations_table

Phase 3 (New tables — Certificate System):
9. 2026_04_03_000001_create_certificate_templates_table
10. 2026_04_03_000002_create_certificates_table

Phase 4 (New tables — CTEVT):
11. 2026_04_04_000001_create_student_hours_summary_table
12. 2026_04_04_000002_create_practical_assessments_table
13. 2026_04_04_000003_create_practical_assessment_results_table

Phase 5 (New tables — Tuition Center):
14. 2026_04_05_000001_create_student_subject_enrollments_table
15. 2026_04_05_000002_create_monthly_billing_cycles_table
16. 2026_04_05_000003_create_monthly_fee_dues_table

Phase 6 (New tables — Bridge Course):
17. 2026_04_06_000001_create_student_outcomes_table
```

---

## Section 4: Eloquent Model Changes

### New Models to Create
- `Lab` (HasMany: LabPcs, LabAllocations)
- `LabPc` (BelongsTo: Lab)
- `LabAllocation` (BelongsTo: Lab, Batch)
- `CertificateTemplate` (HasMany: Certificates)
- `Certificate` (BelongsTo: Student, Course, CertificateTemplate)
- `StudentHoursSummary` (BelongsTo: Student, Batch)
- `PracticalAssessment` (HasMany: PracticalAssessmentResults)
- `PracticalAssessmentResult` (BelongsTo: PracticalAssessment, Student)
- `StudentSubjectEnrollment` (BelongsTo: Student, Course)
- `MonthlyBillingCycle` (HasMany: MonthlyFeeDues)
- `MonthlyFeeDue` (BelongsTo: StudentSubjectEnrollment, MonthlyBillingCycle)
- `StudentOutcome` (BelongsTo: Student, Batch)

### Existing Models to Update
- `Tenant`: add `institute_type` attribute; add `hasInstituteType()` helper method
- `Course`: add `is_monthly_subject`, `required_hours`, `ctevt_program_code`
- `Attendance`: add `session_duration_hours`, `session_type`
- `Batch`: add `stream`, `is_seasonal`, `season_end_date`
- `Student`: add `grade_level`, `school_name`

### Global Scope Reminder
All new models must apply `TenantScope` via `boot()` to ensure tenant isolation. No new model should ever be accessible without `tenant_id` filtering.

---

## Section 5: Indexes Summary

Critical indexes to add for performance:

```sql
-- Certificate fast lookup by verification token (public QR URLs)
CREATE INDEX idx_cert_verify_token ON certificates(verification_token);

-- Hours summary batch rollup query performance
CREATE INDEX idx_hours_batch ON student_hours_summary(tenant_id, batch_id, is_eligible_for_cert);

-- Monthly due status reports
CREATE INDEX idx_mfd_status_month ON monthly_fee_dues(tenant_id, status, due_date_ad);

-- Lab conflict detection
CREATE INDEX idx_lab_alloc_conflict ON lab_allocations(tenant_id, lab_id, day_of_week, start_time, end_time);

-- Outcome placement rate aggregation
CREATE INDEX idx_outcome_result ON student_outcomes(tenant_id, batch_id, result);
```

---

*End of Database Upgrade Plan*
