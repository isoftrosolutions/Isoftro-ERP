# Feature Gap Analysis — Hamro Labs Academic ERP Expansion
**Version 1.0 | Prepared for Hamro Labs Pvt. Ltd. | March 2026**

---

## Overview

This document compares the current Hamro Labs Academic ERP capabilities (as documented in PRD V3.0 and SRS V1.0) against the feature requirements of four new target segments: Computer Training Institutes, Bridge Course Institutes, Tuition Centers, and Skill/CTEVT Training Centers.

**Gap Classification:**
- ✅ **COVERED** — Feature exists and works as-is for the new segment
- ⚡ **MINOR EXTENSION** — Feature exists but needs light modifications (1–5 dev days)
- 🔧 **MODERATE EXTENSION** — Feature exists but needs significant changes (1–3 weeks)
- 🆕 **NEW MODULE** — Feature does not exist; requires new development
- ❌ **NOT APPLICABLE** — Feature is not relevant to this segment

---

## Module 1: Student Admission & Registration

| Feature | Current State | Computer Training | Bridge Course | Tuition Center | Skill/CTEVT | Gap Action |
|---------|--------------|-------------------|---------------|----------------|-------------|------------|
| Student admission form (full Nepali fields) | ✅ Built | ✅ Covered | ✅ Covered | ⚡ Needs simplified mode (remove heavy fields) | ✅ Covered | Tuition: lightweight form mode |
| Roll number auto-generation | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Document upload (citizenship, photo) | ✅ Built | ✅ Covered | ✅ Covered | ⚡ Optional for young students | ✅ Covered | Tuition: make docs optional |
| Guardian contact capture | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Walk-in inquiry to admission pipeline | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Seasonal bulk admission handling | ❌ Missing | ❌ N/A | 🆕 Needed | ❌ N/A | ❌ N/A | Bridge: bulk CSV import for rush |
| CTEVT-specific intake form fields | ❌ Missing | ❌ N/A | ❌ N/A | ❌ N/A | 🆕 Needed | CTEVT: add program type, trade, level |
| Student grade level field (Grade 1–12) | ❌ Missing | ❌ N/A | ❌ N/A | 🆕 Needed | ❌ N/A | Tuition: add grade/school field |

---

## Module 2: Course & Batch Management

| Feature | Current State | Computer Training | Bridge Course | Tuition Center | Skill/CTEVT | Gap Action |
|---------|--------------|-------------------|---------------|----------------|-------------|------------|
| Course catalog management | ✅ Built (Loksewa categories) | ⚡ Remove Loksewa-only categories; add Computer/IT types | ⚡ Add Science/Mgmt/Hum streams | ⚡ Add per-subject course type | ⚡ Add CTEVT program codes | Extend course `category` enum |
| Batch creation with capacity | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Multiple concurrent batches | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Batch shift (Morning/Day/Evening) | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Lab/PC allocation per batch | ❌ Missing | 🆕 Needed | ❌ N/A | ❌ N/A | 🔧 Partial (for IT trades) | Computer: new lab management sub-module |
| Hours-based batch tracking (not weeks) | ❌ Missing | ⚡ Optional | ❌ N/A | ❌ N/A | 🆕 Required | CTEVT: hours counter per student per batch |
| Subject-level enrollment (not course-level) | ❌ Missing | ❌ N/A | ❌ N/A | 🆕 Needed | ❌ N/A | Tuition: enroll per subject with per-subject fee |
| Seasonal batch auto-archiving | ❌ Missing | ❌ N/A | 🆕 Needed | ❌ N/A | ❌ N/A | Bridge: end-of-season batch close + archive flow |
| Batch intake stream tagging (Science/Mgmt) | ❌ Missing | ❌ N/A | 🆕 Needed | ❌ N/A | ❌ N/A | Bridge: stream field on batch |

---

## Module 3: Fee Management

| Feature | Current State | Computer Training | Bridge Course | Tuition Center | Skill/CTEVT | Gap Action |
|---------|--------------|-------------------|---------------|----------------|-------------|------------|
| Course-based fee structure | ✅ Built | ✅ Covered | ✅ Covered | ⚡ Needs per-subject monthly billing | ✅ Covered | Tuition: monthly subscription billing mode |
| Installment plans | ✅ Built | ✅ Covered | ✅ Covered | ❌ N/A (monthly) | ✅ Covered | None |
| Payment recording & PDF receipt | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Fee due reminders (SMS) | ✅ Built (Phase 2) | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Monthly auto-billing cycle | ❌ Missing | ❌ N/A | ❌ N/A | 🆕 Needed | ❌ N/A | Tuition: recurring monthly billing engine |
| Per-subject fee tracking | ❌ Missing | ❌ N/A | ❌ N/A | 🆕 Needed | ❌ N/A | Tuition: fee ledger by subject not just course |
| Materials fee (notes, books) | ❌ Missing | ❌ N/A | 🆕 Needed | ❌ N/A | ❌ N/A | Bridge: separate materials fee line item |
| Mock exam fee collection | ❌ Missing | ❌ N/A | 🆕 Needed | ❌ N/A | ❌ N/A | Bridge: per-exam fee item with payment gate |
| Government-funded trainee tracking | ❌ Missing | ❌ N/A | ❌ N/A | ❌ N/A | 🆕 Needed | CTEVT: flag student as fee-paying vs sponsored |
| eSewa/Khalti online payment | ⚡ Phase 2 planned | ✅ Will be Covered | ✅ Will be Covered | ✅ Will be Covered | ✅ Will be Covered | Keep on roadmap |

---

## Module 4: Attendance Tracking

| Feature | Current State | Computer Training | Bridge Course | Tuition Center | Skill/CTEVT | Gap Action |
|---------|--------------|-------------------|---------------|----------------|-------------|------------|
| Session-based attendance marking | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ⚡ Needs hours logging | CTEVT: capture session hours per entry |
| Per-subject attendance | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Guardian SMS on absence | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered (critical) | ✅ Covered | None |
| Attendance percentage tracker | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Cumulative hours log per student | ❌ Missing | ⚡ Useful | ❌ N/A | ❌ N/A | 🆕 Required | CTEVT: hours accumulation counter for certification eligibility |
| Lab session attendance (per PC) | ❌ Missing | 🆕 Needed | ❌ N/A | ❌ N/A | ⚡ Partial | Computer: lab session type for attendance |
| Attendance prerequisite for exam/cert | ❌ Missing | ✅ N/A | 🆕 Needed (entrance scholarship rule) | ❌ N/A | 🆕 Needed (CTEVT requires minimum hours) | Bridge + CTEVT: attendance gate for progression |

---

## Module 5: Examination & Assessment

| Feature | Current State | Computer Training | Bridge Course | Tuition Center | Skill/CTEVT | Gap Action |
|---------|--------------|-------------------|---------------|----------------|-------------|------------|
| MCQ exam engine | ✅ Built (Phase 2) | ✅ Covered | ✅ Covered (entrance prep) | ✅ Covered | ⚡ Partial (theory part) | None for MCQ |
| Question bank with approval workflow | ✅ Built (Phase 2) | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Auto-evaluation & ranking | ✅ Built (Phase 2) | ✅ Covered | ✅ Covered | ✅ Covered | ❌ N/A | None |
| Mock exam leaderboard / ranking board | ❌ Missing | ❌ N/A | 🆕 Needed (public rankings are marketing tools) | ❌ N/A | ❌ N/A | Bridge: shareable ranking leaderboard |
| Practical assessment (non-MCQ) | ❌ Missing | 🆕 Needed | ❌ N/A | ❌ N/A | 🆕 Needed | New practical/competency assessment module |
| Entrance exam result tracking | ❌ Missing | ❌ N/A | 🆕 Needed (did student get selected?) | ❌ N/A | ❌ N/A | Bridge: post-course outcome tracking field |
| Per-subject grading / marksheet | ❌ Missing | ⚡ Useful | 🆕 Needed | 🆕 Needed | ✅ N/A | Marksheet generation module |
| Competency-based evaluation | ❌ Missing | ⚡ Partial | ❌ N/A | ❌ N/A | 🆕 Needed | CTEVT: pass/fail per competency unit |

---

## Module 6: Certificate Generation

| Feature | Current State | Computer Training | Bridge Course | Tuition Center | Skill/CTEVT | Gap Action |
|---------|--------------|-------------------|---------------|----------------|-------------|------------|
| Student ID card PDF (Python) | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Course completion certificate PDF | ❌ Missing | 🆕 Needed (core demand) | ⚡ Nice to have | ❌ N/A | 🆕 Required (CTEVT format) | New certificate generation module |
| QR code on certificate for verification | ❌ Missing | 🆕 Needed | ❌ N/A | ❌ N/A | 🆕 Needed | Build into certificate module |
| CTEVT-format certificate compliance | ❌ Missing | ❌ N/A | ❌ N/A | ❌ N/A | 🆕 Required | CTEVT: layout, fields, seals per CTEVT spec |
| Bulk certificate print queue | ❌ Missing | 🆕 Needed | 🆕 Needed | ❌ N/A | 🆕 Needed | Batch certificate generation for graduation |
| Certificate serial number registry | ❌ Missing | 🆕 Needed | ❌ N/A | ❌ N/A | 🆕 Needed | Prevent certificate duplication |

---

## Module 7: Timetable & Scheduling

| Feature | Current State | Computer Training | Bridge Course | Tuition Center | Skill/CTEVT | Gap Action |
|---------|--------------|-------------------|---------------|----------------|-------------|------------|
| Weekly timetable builder | ✅ Built (Phase 2) | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Teacher-batch subject allocation | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Lab/computer room scheduling | ❌ Missing | 🆕 Needed | ❌ N/A | ❌ N/A | ⚡ Partial | Computer: room resource type = lab |
| Multi-stream timetable conflict detection | ⚡ Exists (basic) | ✅ Covered | 🔧 Needs stream-aware conflict detection | ✅ Covered | ✅ Covered | Bridge: stream-aware timetable |

---

## Module 8: Communication & Notifications

| Feature | Current State | Computer Training | Bridge Course | Tuition Center | Skill/CTEVT | Gap Action |
|---------|--------------|-------------------|---------------|----------------|-------------|------------|
| Automated SMS (Sparrow + Aakash) | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Email notifications (Mailgun) | ✅ Built (Phase 2) | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Exam schedule announcement | ✅ Built (Phase 2) | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Result announcement broadcast | ✅ Built (Phase 2) | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Entrance result congratulations SMS | ❌ Missing | ❌ N/A | 🆕 Nice to have | ❌ N/A | ❌ N/A | Bridge: outcome SMS template |
| Monthly fee due reminder | ✅ Built | ❌ N/A | ❌ N/A | 🔧 Needs monthly billing trigger | ❌ N/A | Tuition: trigger on monthly billing cycle |

---

## Module 9: Reports & Analytics

| Feature | Current State | Computer Training | Bridge Course | Tuition Center | Skill/CTEVT | Gap Action |
|---------|--------------|-------------------|---------------|----------------|-------------|------------|
| Fee collection report (PDF/Excel) | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Attendance report | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ⚡ Needs hours summary | CTEVT: hours-based report |
| Exam performance report | ✅ Built (Phase 2) | ✅ Covered | ✅ Covered | ✅ Covered | ❌ N/A | None |
| CTEVT-format export (registration, hours log) | ❌ Missing | ❌ N/A | ❌ N/A | ❌ N/A | 🆕 Required | CTEVT: dedicated regulatory export module |
| Entrance exam selection rate report | ❌ Missing | ❌ N/A | 🆕 Needed | ❌ N/A | ❌ N/A | Bridge: outcome tracking report |
| Monthly billing summary per student | ❌ Missing | ❌ N/A | ❌ N/A | 🆕 Needed | ❌ N/A | Tuition: per-student monthly ledger |

---

## Module 10: Portal & Access

| Feature | Current State | Computer Training | Bridge Course | Tuition Center | Skill/CTEVT | Gap Action |
|---------|--------------|-------------------|---------------|----------------|-------------|------------|
| Student portal (timetable, fees, results) | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Guardian portal (read-only monitoring) | ✅ Built (Phase 2) | ⚡ Optional | ⚡ Optional | 🆕 Critical feature | ⚡ Optional | Tuition: guardian portal is primary parent touchpoint |
| Teacher portal (attendance, marks) | ✅ Built | ✅ Covered | ✅ Covered | ✅ Covered | ✅ Covered | None |
| Certificate download portal (student) | ❌ Missing | 🆕 Needed | ❌ N/A | ❌ N/A | 🆕 Needed | Student can download their certificate |

---

## Gap Summary by New Module Required

| New Module | Required For | Complexity | Priority |
|-----------|-------------|------------|----------|
| Certificate Generation System | Computer Training, CTEVT | Medium | P1 |
| Lab/Computer Room Management | Computer Training | Low–Medium | P1 |
| Hours-Based Attendance Tracking | CTEVT | Low–Medium | P1 |
| Monthly Subscription Billing | Tuition Center | Medium | P1 |
| Per-Subject Enrollment | Tuition Center | Medium | P1 |
| Practical/Competency Assessment | Computer Training, CTEVT | Medium | P2 |
| Mock Exam Ranking Leaderboard | Bridge Course | Low | P2 |
| Entrance Exam Outcome Tracking | Bridge Course | Low | P2 |
| Seasonal Bulk Admission Import | Bridge Course | Medium | P2 |
| CTEVT Regulatory Export Module | CTEVT | High | P2 |
| Lightweight Admission Mode | Tuition Center | Low | P2 |
| Materials Distribution Tracker | Bridge Course | Low | P3 |
| Certificate Serial Registry | Computer Training, CTEVT | Low | P3 |
| Monthly Billing Cycle Engine | Tuition Center | Medium | P3 |

---

## Overall Gap Score by Segment

| Segment | Features Covered (%) | New Features Needed | Extension Effort | Total Dev Estimate |
|---------|---------------------|--------------------|-----------------|--------------------|
| Computer Training | ~70% | 5 new modules | Low-Medium | 4–6 weeks |
| Bridge Course | ~65% | 5 new modules | Low-Medium | 3–5 weeks |
| Tuition Center | ~50% | 6 new modules | Medium | 5–8 weeks |
| Skill/CTEVT | ~55% | 5 new modules | Medium-High | 6–10 weeks |

---

*End of Feature Gap Analysis*
