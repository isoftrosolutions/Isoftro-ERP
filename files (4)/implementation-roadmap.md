# Implementation Roadmap — Hamro Labs Academic ERP Expansion
**Version 1.0 | Prepared for Hamro Labs Pvt. Ltd. | March 2026**

---

## Roadmap Overview

This roadmap is designed to sequence expansion work so that Hamro Labs can begin onboarding new segment institutes as fast as possible without disrupting existing Loksewa institute operations. Work is organized into three phases:

| Phase | Duration | Goal | Primary Segments Unlocked |
|-------|----------|------|--------------------------|
| Phase 1 | Months 1–2 | Core expansion foundation | Computer Training |
| Phase 2 | Months 3–4 | Academic and assessment tools | Bridge Course + CTEVT |
| Phase 3 | Months 5–6 | Tuition center + automation | Tuition Center + full platform |

All work is additive and follows a strict principle: no changes to existing Loksewa institute functionality during any phase.

---

## Prerequisites Before Expansion Work Begins

Before Phase 1 starts, the following must be fully stable in production:

- [ ] All Phase 1 Loksewa MVP features deployed and tested (multi-tenant, auth, admission, fee, attendance, SMS)
- [ ] At least 3–5 Loksewa beta institutes live and using the system
- [ ] PDF receipt generation fully working (receipt_path saving correctly to payment_transactions)
- [ ] eSewa/Khalti payment integration completed or confirmed as Phase 2 item
- [ ] Staging/production server environment stable with CI/CD pipeline running

Estimated prerequisite completion: Current (MVP is deployment-ready per memory context).

---

## Phase 1 — Core Expansion Foundation
**Target Completion: Month 2 from kickoff**
**Primary Segment Unlocked: Computer Training Institutes**

### Goals
- Deploy institute type architecture so onboarding wizard supports multiple institute types
- Enable computer training institutes to sign up, configure their courses, and operate
- Launch certificate generation as the killer feature for computer institutes
- Add lab management to serve practical-training institutes professionally

---

### Sprint 1.1 — Institute Type Architecture (Week 1–2)

**Backend Tasks:**

| Task | Developer | Effort | Acceptance Criteria |
|------|-----------|--------|---------------------|
| Run migration: add `institute_type` to `tenants` | Backend Lead | 2h | All existing tenants default to `loksewa`; no data loss |
| Run migration: extend `courses.category` enum | Backend Lead | 1h | New categories selectable in course creation form |
| Add `institute_type` to `Tenant` model with helper methods | Backend Lead | 3h | `$tenant->isComputerTraining()` returns correctly |
| Update feature gate middleware to read `institute_type` | Backend Lead | 4h | Module visibility controlled by institute type |
| Update setup wizard: Step 1 = institute type selector | Frontend Dev | 1 day | New tenants see type selector; existing tenants unaffected |
| Build feature profile config file (`config/institute_profiles.php`) | Backend Lead | 4h | Each institute type maps to enabled module list |

**Testing:**
- Create a test tenant with `institute_type = computer_training`; verify only correct modules appear in sidebar
- Create a test tenant with `institute_type = loksewa`; verify no change in behavior

---

### Sprint 1.2 — Certificate Generation Module (Week 2–4)

**Backend Tasks:**

| Task | Developer | Effort | Acceptance Criteria |
|------|-----------|--------|---------------------|
| Migrations: `certificate_templates` + `certificates` tables | Backend Lead | 3h | Tables created; FK constraints valid |
| `CertificateTemplate` and `Certificate` Eloquent models | Backend | 4h | Global Scope applies; no cross-tenant access possible |
| Certificate serial number generator service | Backend | 4h | Format: HL-{BS Year}-CERT-{5-digit seq}; unique per tenant |
| Certificate eligibility checker (attendance % gate) | Backend | 1 day | Checks attendance threshold before allowing cert issuance |
| WeasyPrint PDF template: Standard Certificate | Python Dev | 3 days | A4 landscape, institute logo, student name, course, date BS/AD, grade, QR code |
| WeasyPrint PDF template: CTEVT Certificate | Python Dev | 2 days | Matches CTEVT prescribed format; includes hours, program code |
| QR verification page (public route — no auth) | Backend + Frontend | 1 day | `verify.hamrolabs.com/cert/{token}` shows certificate validity |
| Certificate storage to Wasabi S3 | Backend | 4h | PDF stored at `tenants/{id}/certs/{cert_no}.pdf` |
| Bulk certificate generation queue job | Backend | 1 day | Admin selects batch → queued → all certs generated in background |

**Frontend Tasks:**

| Task | Developer | Effort | Acceptance Criteria |
|------|-----------|--------|---------------------|
| Certificate template designer (basic layout config) | Frontend | 2 days | Admin can set: logo position, background, footer text, signature label |
| Certificates list page (admin) | Frontend | 1 day | Lists all issued certs; status badges; search by student |
| Issue certificate single flow | Frontend | 1 day | Admin selects student + course → previews PDF → issues |
| Student portal: Certificate download button | Frontend | 4h | Student sees download button when cert is in `issued` state |

---

### Sprint 1.3 — Lab Management Module (Week 3–4)

**Backend Tasks:**

| Task | Developer | Effort | Acceptance Criteria |
|------|-----------|--------|---------------------|
| Migrations: `labs` + `lab_pcs` + `lab_allocations` | Backend | 3h | Tables created correctly |
| Lab CRUD controller + routes | Backend | 1 day | Admin can create/edit/delete labs; validation enforced |
| Lab allocation: batch-to-lab assignment with conflict detection | Backend | 2 days | System rejects allocation if another batch has same lab at same time |
| Lab utilization API endpoint | Backend | 4h | Returns which labs are free/busy per time slot |

**Frontend Tasks:**

| Task | Developer | Effort | Acceptance Criteria |
|------|-----------|--------|---------------------|
| Lab list and management page | Frontend | 1 day | Admin sees all labs; PC count; status |
| Lab scheduling grid (visual occupancy view) | Frontend | 2 days | Color-coded grid: green = free, yellow = partial, red = full |
| Batch-to-lab assignment interface | Frontend | 1 day | Admin assigns batch to lab with time slot picker |

---

### Sprint 1.4 — Computer Training Courses & Onboarding (Week 4)

| Task | Developer | Effort | Acceptance Criteria |
|------|-----------|--------|---------------------|
| Update course creation form: show computer-relevant categories | Frontend | 4h | Computer Training tenants see IT-relevant course categories |
| Update admin dashboard: Computer Training widget set | Frontend | 1 day | Computer dashboard shows: active courses, lab utilization, certs issued |
| Update onboarding checklist for Computer Training | Frontend | 4h | Checklist guides new tenant: create lab → create course → create batch → issue first cert |
| End-to-end test with a real mock Computer Training tenant | QA | 2 days | Full flow: admission → attendance → assessment → certificate issued |

---

### Phase 1 Deliverable Summary

By end of Month 2:
- Computer Training institutes can sign up and onboard independently
- Certificate generation working: standard PDF with QR code
- Lab management module live
- Institute type architecture deployed; feature profiles enforced
- First 5–10 computer institute beta tenants targeted for onboarding

---

## Phase 2 — Academic and Assessment Tools
**Target Completion: Month 4 from kickoff**
**Primary Segments Unlocked: Bridge Course Institutes, Skill/CTEVT Centers**

### Goals
- Build bridge course-specific features (stream batches, mock exam leaderboard, outcome tracking)
- Add hours-based attendance for CTEVT compliance
- Add practical/competency assessment module
- Launch CTEVT regulatory export

---

### Sprint 2.1 — Bridge Course Features (Week 5–7)

| Task | Developer | Effort | Acceptance Criteria |
|------|-----------|--------|---------------------|
| Migration: add `stream`, `is_seasonal` fields to `batches` | Backend | 2h | Existing batches unaffected (NULL defaults) |
| Stream-aware batch creation UI | Frontend | 4h | Bridge tenants see stream selector (Science/Mgmt/Hum) |
| Seasonal batch auto-archiving: end-of-season workflow | Backend | 1 day | Admin can trigger "End Season" → batch archived, analytics preserved |
| Mock exam leaderboard: auto-calculate rankings after MCQ exam | Backend | 2 days | Rankings generated per exam; stored per exam attempt |
| Leaderboard UI: ranked list with badges | Frontend | 2 days | Shareable public link option; top 10 highlighted |
| Entrance exam outcome tracking: CRUD for student_outcomes | Backend + Frontend | 2 days | Admin can record outcome per student; batch-level placement report |
| Placement rate dashboard widget | Frontend | 1 day | Shows: Total students, Selected, Selection rate (%) |
| Bridge Course onboarding checklist | Frontend | 4h | Guides: create stream batches → open enrollment → schedule exams |

---

### Sprint 2.2 — CTEVT / Hours Tracking (Week 6–8)

| Task | Developer | Effort | Acceptance Criteria |
|------|-----------|--------|---------------------|
| Migration: `session_duration_hours` + `session_type` in `attendance` | Backend | 2h | NULL for non-CTEVT; zero impact on existing data |
| Migration: `student_hours_summary` table | Backend | 2h | Table created with correct FKs |
| Hours recalculation job: rebuild summary after each attendance mark | Backend | 2 days | After teacher marks attendance with hours, summary auto-updates |
| CTEVT attendance marking UI: show hours field + session type | Frontend | 1 day | CTEVT teachers see duration hours input; regular teachers do not |
| Student profile: hours progress bar | Frontend | 4h | Visual bar: X of Y hours (Z%) completed |
| Certification eligibility check: hours gate | Backend | 4h | Certificate cannot be issued until hours threshold met |
| CTEVT regulatory export: hours log per student | Python Dev | 3 days | PDF/Excel in CTEVT-compatible format; grouped by program |
| CTEVT dashboard widgets | Frontend | 1 day | Shows: hours progress by batch; eligibility count; certification pipeline |

---

### Sprint 2.3 — Practical Assessment Module (Week 7–8)

| Task | Developer | Effort | Acceptance Criteria |
|------|-----------|--------|---------------------|
| Migrations: `practical_assessments` + `practical_assessment_results` | Backend | 2h | Tables created |
| Practical assessment CRUD (admin/teacher creates assessment) | Backend + Frontend | 2 days | Teacher creates assessment; defines rubric items |
| Teacher: submit result per student | Frontend | 2 days | Rubric form: tick/score each item; submit; marks student Pass/Fail |
| Competency checklist mode for CTEVT | Frontend | 1 day | CTEVT teachers see pass/fail per competency unit (not scored rubric) |
| Link practical result to certificate eligibility | Backend | 4h | Certificate not issuable if practical assessment = fail |

---

### Phase 2 Deliverable Summary

By end of Month 4:
- Bridge Course institutes fully functional (stream batches, leaderboard, outcomes)
- CTEVT institutes can track hours, export compliance reports, issue CTEVT-format certificates
- Practical assessment module live for both Computer Training and CTEVT
- Target: 30–40 combined new segment tenants onboarded

---

## Phase 3 — Tuition Center + Platform Automation
**Target Completion: Month 6 from kickoff**
**Primary Segments Unlocked: Tuition Centers; Platform-wide automation improvements**

### Goals
- Monthly subscription billing engine for tuition centers
- Per-subject enrollment and lightweight admission mode
- Guardian portal enhancements for tuition use case
- Platform-wide improvements: seasonal tools, analytics, self-serve onboarding

---

### Sprint 3.1 — Tuition Center Core (Week 9–11)

| Task | Developer | Effort | Acceptance Criteria |
|------|-----------|--------|---------------------|
| Migrations: subject enrollments + billing cycles + monthly dues | Backend | 4h | Tables created; FKs valid |
| Lightweight admission mode: simplified form for Tuition type | Frontend | 2 days | Tuition tenants see Grade + School + Guardian fields; heavy fields hidden |
| Per-subject enrollment CRUD | Backend + Frontend | 2 days | Admin can enroll student in Math, Science, English independently |
| Monthly billing cycle generator: first-of-month job | Backend | 2 days | Queued job runs on 1st BS of each month; creates monthly_fee_dues for all active enrollments |
| Monthly fee collection UI (admin) | Frontend | 2 days | Admin sees: student list, each subject, due/paid status; one-click record payment |
| Monthly due reminder SMS: auto-trigger on 5th if unpaid | Backend | 1 day | Queue job on 5th: SMS sent to guardian for each unpaid subject |
| Student portal: subject-wise fee status | Frontend | 4h | Student sees each subject with current month paid/due status |
| Monthly ledger report per student | Python Dev | 2 days | PDF: all subjects, monthly history, totals |

---

### Sprint 3.2 — Guardian Portal Enhancement for Tuition (Week 10–11)

| Task | Developer | Effort | Acceptance Criteria |
|------|-----------|--------|---------------------|
| Guardian portal: subject-wise fee due view | Frontend | 1 day | Guardian sees each enrolled subject and current month fee status |
| Guardian portal: attendance by subject | Frontend | 4h | Guardian can see attendance per subject (not just overall) |
| Guardian portal: monthly fee payment history | Frontend | 1 day | Guardian sees last 6 months of payment records per subject |
| Guardian push notification: monthly due (PWA) | Backend | 4h | PWA push notification on fee due date (5th of month) |

---

### Sprint 3.3 — Platform-Wide Automation Improvements (Week 11–12)

| Task | Developer | Effort | Notes |
|------|-----------|--------|-------|
| Institute type analytics in Super Admin dashboard | Backend + Frontend | 2 days | MRR breakdown by institute_type; churn by segment |
| Self-serve onboarding improvement: type-specific checklist | Frontend | 1 day | Each institute type gets a step-by-step first-time setup guide |
| Bulk admission import (CSV → students) | Backend + Python | 3 days | For Bridge Course season rush; validates and imports via pandas |
| Certificate expiry/renewal reminder (for CTEVT certs with expiry) | Backend | 1 day | SMS/email reminder 30 days before cert expiry |
| Inter-segment referral tracking | Backend | 1 day | Institutes can refer other institutes; referral credit tracking |

---

### Phase 3 Deliverable Summary

By end of Month 6:
- Tuition Center segment fully functional with monthly billing
- All four new segments live and onboardable
- Platform-wide automation improvements deployed
- Target: 50–80 new segment tenants total across all new types
- Hamro Labs total tenant target: 100+ (existing Loksewa + new segments)

---

## Resource Requirements for Expansion

| Role | Phase 1 Load | Phase 2 Load | Phase 3 Load | Notes |
|------|-------------|-------------|-------------|-------|
| Senior PHP/Laravel Developer | 60% | 60% | 40% | Continues Loksewa Phase 2 work in parallel |
| Junior PHP Developer | 80% | 70% | 80% | Primary worker for new module backends |
| Frontend Developer | 80% | 80% | 80% | Dashboard, UI components for all new modules |
| Python Developer | 40% | 60% | 40% | WeasyPrint certs, CTEVT export, bulk import |
| QA Engineer | 60% | 60% | 60% | Regression testing + new segment flows |

---

## Risk Register

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Bridge course seasonal window missed (April–June) | High | High | Prioritize bridge course features in Phase 1.5 if needed; launch minimal viable version (stream batches + MCQ exam) before April |
| CTEVT format compliance inaccuracy | Medium | High | Partner with 1 CTEVT affiliate institute before building export; validate format with their coordinator |
| Tuition center monthly billing complexity underestimated | Medium | Medium | Build billing cycle as queue job; test edge cases (mid-month join, inactive enrollment) rigorously |
| Computer institutes expect physical biometric integration | Low | Medium | ERP handles software attendance; note biometric is Phase 3 enterprise add-on |
| Existing Loksewa tenants see unwanted new modules | Low | Low | Feature profile middleware prevents this; test with existing tenant accounts in staging |
| Laravel Global Scope missing from new models | Medium | High | Code review checklist item for every new model PR; unit test verifies cross-tenant isolation |

---

## Success Metrics by Month 6

| KPI | Target |
|-----|--------|
| New segment tenants onboarded | 60+ |
| Certificates generated | 500+ |
| Monthly billing cycles processed | 3+ cycles (for Tuition tenants) |
| CTEVT institutes using hours tracking | 15+ |
| Bridge course institutes onboarded before April season | 20+ |
| Overall platform MRR | NPR 5,00,000+ |
| Support tickets from new segments | < 5% of total tickets |
| Onboarding time (new tenant to first student) | < 30 minutes |

---

## Parallel Work: Loksewa Core Progress

Expansion phases run parallel to existing Loksewa Phase 2–3 work. The following Loksewa items should continue on a separate track without delaying expansion:

- MCQ exam engine (Phase 2 — Months 4–6 of Loksewa roadmap)
- LMS and study materials (Phase 2)
- eSewa/Khalti payment integration (Phase 2)
- Teacher module and payroll (Phase 2)
- Guardian portal base (Phase 2 — feeds directly into Tuition enhancement in Expansion Phase 3)
- AI analytics (Phase 3)

The expansion work and Loksewa Phase 2 work can be parallelized with a 2-developer backend team and 1 frontend developer working across both tracks.

---

*End of Implementation Roadmap*
