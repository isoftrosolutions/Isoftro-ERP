# ERP Expansion Plan — Hamro Labs Academic ERP
**Version 1.0 | Prepared for Hamro Labs Pvt. Ltd. | March 2026**
**Senior Product Architecture Review**

---

## 1. Expansion Philosophy

The Hamro Labs expansion strategy is governed by a single architectural principle: **one platform, multiple institute personas.** Rather than building separate products for each new segment, all new capabilities are added as configurable modules controlled by the institute's `institute_type` setting at the tenant level. This preserves multi-tenant economics, reduces infrastructure cost, and allows cross-segment features (attendance, fee, SMS) to be reused without duplication.

The expansion is not a rewrite. It is a deliberate extension of what already works.

---

## 2. Institute Type Architecture

### 2.1 Proposed Institute Types (Tenant-Level Setting)

```
institute_types:
  - loksewa          (existing)
  - computer_training
  - bridge_course
  - tuition_center
  - skill_ctevt
  - general_coaching  (catch-all for future segments)
```

Each `institute_type` activates a specific **feature profile** — a pre-configured set of enabled modules, UI labels, default settings, and dashboard widgets. An admin selecting institute type during onboarding automatically receives the right tool set without manual feature flag configuration.

### 2.2 Feature Profile Matrix

| Module | Loksewa | Computer | Bridge | Tuition | CTEVT |
|--------|---------|----------|--------|---------|-------|
| Student Admission (Full) | ✅ | ✅ | ✅ | ⚡ Lite mode | ✅ |
| Course Management | ✅ | ✅ | ✅ | ✅ Subject mode | ✅ |
| Batch Management | ✅ | ✅ | ✅ | ✅ | ✅ |
| Lab Management | ❌ | ✅ | ❌ | ❌ | ⚡ |
| Attendance (Session) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Attendance (Hours) | ❌ | ⚡ | ❌ | ❌ | ✅ |
| Fee (Course-based) | ✅ | ✅ | ✅ | ❌ | ✅ |
| Fee (Monthly Subject) | ❌ | ❌ | ❌ | ✅ | ❌ |
| MCQ Exam Engine | ✅ | ⚡ | ✅ | ✅ | ⚡ |
| Practical Assessment | ❌ | ✅ | ❌ | ❌ | ✅ |
| Certificate Generation | ❌ | ✅ | ⚡ | ❌ | ✅ CTEVT format |
| Guardian Portal | ⚡ | ⚡ | ⚡ | ✅ | ⚡ |
| Mock Exam Leaderboard | ✅ | ❌ | ✅ | ❌ | ❌ |
| CTEVT Export | ❌ | ❌ | ❌ | ❌ | ✅ |
| Outcome Tracking | ❌ | ❌ | ✅ | ❌ | ✅ |
| Materials Tracker | ❌ | ❌ | ✅ | ❌ | ❌ |

---

## 3. New Module Specifications

### 3.1 Certificate Generation Module

**Target Segments:** Computer Training, Skill/CTEVT

**Business Need:** Certificate issuance is the #1 operational pain point and the primary deliverable of computer and CTEVT institutes. Manual certificate creation in MS Word takes 2–4 minutes per student and is error-prone. Automated certificate generation via the ERP eliminates this and creates a significant product hook.

**Core Features:**
- Admin designs a certificate template per course (logo, institute name, layout, signature fields)
- Certificate auto-generates when student meets completion criteria (attendance threshold + assessment passed)
- Python WeasyPrint/ReportLab renders certificates as high-quality PDFs
- QR code embedded in each certificate links to a public verification URL: `verify.hamrolabs.com/cert/{unique_id}`
- Bulk print queue: admin can generate 50 certificates in one click for graduation events
- Certificate serial number registry: unique serial per tenant, prevents forgery/duplication
- CTEVT certificate mode: fixed layout matching CTEVT prescribed format with program code, trade, hours completed, grade fields
- Student can download their certificate directly from the student portal

**Technical Implementation:**
```
certificates table:
  - id, tenant_id, student_id, course_id, batch_id
  - certificate_no (unique per tenant: HL-2081-CERT-0001)
  - issued_date_bs, issued_date_ad
  - template_id (FK to cert_templates)
  - pdf_path (S3/Wasabi path)
  - qr_verification_token (UUID, public)
  - status (draft / issued / revoked)
  - created_by_user_id

certificate_templates table:
  - id, tenant_id, name
  - template_type (standard / ctevt)
  - layout_config (JSON: logo position, fields, signature)
  - background_image_url
```

---

### 3.2 Lab / Resource Management Sub-Module

**Target Segments:** Computer Training

**Business Need:** Computer training institutes run practical sessions where students need assigned workstations. Without tracking, multiple batches collide on the same PCs, disrupting operations. Lab management adds a professional layer that no manual system offers.

**Core Features:**
- Lab room creation: define room name, total PC count (e.g., Lab A — 25 PCs)
- Batch-to-lab assignment per timetable slot
- PC occupancy view: visual grid showing which PCs are allocated per time slot
- Maintenance flag per PC: mark a unit as under repair to exclude from allocation
- Lab attendance: mark which PC a student used per session (optional, for accountability)
- Conflict detection: system prevents two batches from being assigned the same lab at the same time

**Technical Implementation:**
```
labs table:
  - id, tenant_id, name, total_pcs, room_number, status

lab_allocations table:
  - id, tenant_id, lab_id, batch_id, timetable_slot_id
  - allocated_pcs_count
```

---

### 3.3 Hours-Based Attendance Tracking

**Target Segments:** Skill/CTEVT

**Business Need:** CTEVT programs are measured in hours (160h, 390h, 780h). Regulatory compliance for certificate issuance and program renewal requires documented proof that each student has accumulated the required minimum hours. This is currently done manually with log books.

**Core Features:**
- Each attendance session captures duration in hours (e.g., 3-hour session)
- Per-student cumulative hours counter updated after each session
- Hours progress bar on student profile (e.g., "312 of 390 hours completed — 80%")
- Certification eligibility flag: student is marked eligible for certificate only after reaching minimum hours threshold
- CTEVT report export: generates an official-format hours log per student, sortable by program and batch
- Session type tracking: Theory | Practical | Field Visit (different session types for CTEVT program requirements)

**Technical Implementation:**
```
Extend existing `attendance` table:
  - session_duration_hours DECIMAL(4,2) NULL  (nullable for non-CTEVT tenants)
  - session_type ENUM('regular','practical','field','theory') DEFAULT 'regular'

Add student_hours_summary (materialized cache):
  - student_id, tenant_id, batch_id
  - total_hours_completed DECIMAL(6,2)
  - required_hours DECIMAL(6,2)
  - is_eligible_for_cert BOOLEAN
  - last_updated_at
```

---

### 3.4 Monthly Subscription Billing Module

**Target Segments:** Tuition Center

**Business Need:** Tuition centers charge fees monthly, per subject. A student enrolled in Math + Science + English pays three separate monthly fees. This is fundamentally different from course-based billing and cannot be handled by the existing fee_items model without significant extension.

**Core Features:**
- Per-subject monthly fee setup (e.g., Math = NPR 1,200/month, Science = NPR 1,000/month)
- Student-subject enrollment with independent fee tracking per subject
- Monthly billing cycle generation: on the 1st of each month, the system auto-creates a fee due record for each active student-subject enrollment
- Payment recording works as existing (cash, eSewa, Khalti)
- Unpaid subjects shown distinctly per student on the admin fee dashboard
- Guardian SMS auto-reminder on the 5th of each month for any unpaid subjects
- Monthly ledger report per student: shows all subjects, paid amounts, dues across the month

**Technical Implementation:**
```
student_subject_enrollments table:
  - id, tenant_id, student_id, course_id (subject), monthly_fee
  - enrolled_date, status (active/inactive)

monthly_billing_cycles table:
  - id, tenant_id, billing_month_bs, billing_year_bs
  - generated_at, generated_by

monthly_fee_dues table:
  - id, tenant_id, student_id, subject_enrollment_id
  - billing_cycle_id, amount_due, amount_paid
  - due_date_bs, paid_date_bs, status
  - payment_transaction_id FK (nullable)
```

---

### 3.5 Practical / Competency Assessment Module

**Target Segments:** Computer Training, Skill/CTEVT

**Business Need:** Both computer training and CTEVT programs require non-MCQ assessments. A student learning Tally Prime is assessed by performing a live accounting task, not answering multiple-choice questions. CTEVT requires pass/fail per competency unit.

**Core Features:**
- Teacher creates a practical task or competency checklist (e.g., "Complete a 3-month Tally trial balance")
- Practical assessment has a rubric: checklist items with individual pass/fail marks
- Teacher submits assessment result per student with remarks
- Competency-based evaluation: define a list of competency units; mark each as Achieved / Not Achieved / In Progress
- Overall assessment result (Pass/Fail/Distinction) calculated from rubric
- Assessment result linked to certificate eligibility gate

---

### 3.6 Mock Exam Ranking Leaderboard

**Target Segments:** Bridge Course

**Business Need:** Bridge course institutes compete for students based on how many of their students get selected in entrance exams for top colleges. Public ranking leaderboards after mock exams are both a student motivator and a powerful marketing tool. Institutes currently produce these manually in Excel.

**Core Features:**
- After each mock exam evaluation, rankings auto-calculate across all batches for that exam
- Shareable public leaderboard page (optional: institute can make it public or private)
- Rankings display: Rank, Student Name (or roll number for privacy), Score, Percentile
- Top-performer highlight: automatic "Top 10 Scorer" badges
- Historical ranking comparison: see rank trend across multiple mock exams for each student
- PDF export of ranking list for display at institute (printable wall board)

---

### 3.7 Entrance Exam Outcome Tracking

**Target Segments:** Bridge Course

**Business Need:** Bridge course institutes differentiate themselves by their placement rates. Currently this data is collected informally. Systematic tracking creates marketing data, allows follow-up with non-selected students, and informs course quality.

**Core Features:**
- After course completion, a follow-up record for each student: which college applied, result (Selected/Waitlisted/Not Selected)
- Batch and course-level placement rate dashboards
- Scholarship tracking: did the student receive a scholarship at their selected institution
- Success rate report: exportable for marketing use (e.g., "92% of our 2081 Science Bridge students got into their target college")

---

### 3.8 Lightweight Admission Mode

**Target Segments:** Tuition Center

**Business Need:** Tuition centers serve school children where extensive admission forms (citizenship number, province, permanent address in JSON) are irrelevant and create friction. A simplified intake form increases adoption for this segment.

**Core Features:**
- Institute type = `tuition_center` activates a lightweight admission form
- Required fields only: Full Name, Grade, School, Date of Birth, Guardian Name, Guardian Phone
- Optional fields: Photo, Gender, Address
- All existing heavy fields (citizenship number, province, ward, blood group, academic qualifications) hidden but not deleted from schema (preserved for segments that need them)
- Student roll number still auto-generated
- Admission can be completed in under 2 minutes

---

## 4. Onboarding Setup Wizard Extension

When a new tenant signs up, the setup wizard's first screen asks: **"What type of institute are you?"**

```
[ ] Loksewa / Coaching Institute
[ ] Computer Training Institute
[ ] Bridge Course / Entrance Preparation
[ ] Tuition Center
[ ] Skill Training / CTEVT Affiliated
[ ] Other
```

The selection sets `tenants.institute_type` and activates the correct feature profile. All subsequent onboarding steps (default course categories, fee structure suggestions, dashboard widgets) are pre-configured for that institute type. This dramatically reduces setup time and prevents confusion from irrelevant features.

---

## 5. Dashboard Customization by Institute Type

### Computer Training Institute Dashboard
- Active courses grid (Basic Computer, Tally, Graphics, Programming)
- Lab utilization heatmap (which labs are at capacity, which have free slots)
- Certificates issued this month (counter)
- Revenue by course type (chart)
- Expiring batch enrollments (students whose course ends in 7 days)

### Bridge Course Institute Dashboard
- Enrollment surge monitor (daily admission count, capacity vs enrolled)
- Mock exam ranking board (top 5 performers, overall average score)
- Stream-wise enrollment breakdown (Science | Management | Humanities)
- Placement rate tracker (updated as results come in)
- Season countdown (days until peak season ends)

### Tuition Center Dashboard
- Monthly fee collection status (paid vs due for current month)
- Per-subject student counts
- Today's attendance summary
- Guardian portal activity (last login per guardian)
- Overdue fee list (students with 2+ months unpaid)

### Skill/CTEVT Training Dashboard
- Hours completion progress (batch-level average hours completed vs required)
- Eligibility count (students who have cleared hours requirement this month)
- Certification pipeline (eligible → cert generated → issued)
- Upcoming CTEVT inspection checklist (document readiness tracker)

---

## 6. Plan Tier Adjustments for New Segments

### New Plan: Tuition Starter (NPR 499/month)
- Up to 50 students
- Monthly billing module
- Basic attendance + SMS (300 SMS/month)
- Guardian portal (read-only)
- No exam engine, no certificate module
- Designed as entry point for home tutors

### Updated Starter Plan (NPR 1,500/month)
- Expanded to include Computer Training and Bridge Course types
- Certificate module added (up to 100 certificates/month)
- Lab management (up to 2 labs)

### Growth and Professional Plans
- All new modules included
- Unlimited certificates
- CTEVT export module (Professional and above)
- Monthly billing (all plans with Tuition type)

---

## 7. UI/UX Changes

### Admin Dashboard
- Institute type indicator visible in top navigation
- Module sidebar filtered to show only relevant modules for the institute type
- "Quick Actions" panel customized per type (e.g., "Issue Certificate" for Computer, "Start Enrollment Rush" for Bridge)

### Student Portal
- Certificate download button visible when certificate has been issued
- Hours progress bar visible for CTEVT students
- Subject-wise fee status visible for Tuition students

### Teacher Portal
- Lab session type selector when marking attendance for Computer Training
- Practical assessment form accessible for Computer and CTEVT teachers
- Competency checklist entry for CTEVT teachers

### Mobile PWA
- Certificate download functional on mobile
- Attendance entry with hours field for CTEVT teachers
- Monthly fee due notifications for Tuition guardian app

---

## 8. Technical Architecture Summary

The expansion requires zero changes to the core multi-tenant infrastructure, authentication, or data isolation architecture. All changes are additive:

1. New tables added (certificates, labs, lab_allocations, student_subject_enrollments, monthly_billing_cycles, monthly_fee_dues)
2. Existing tables extended (attendance: `session_duration_hours`, `session_type`; tenants: `institute_type`; courses: expanded `category` enum)
3. New feature flags added to the feature gate middleware
4. New Blade/Alpine.js UI components for new modules
5. Python WeasyPrint templates added for certificate PDF generation
6. New API endpoints for certificate download and hours tracking

No breaking changes to existing Loksewa institute functionality.

---

*End of ERP Expansion Plan*
