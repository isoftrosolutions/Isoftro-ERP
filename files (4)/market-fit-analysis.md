# Market Fit Analysis — Hamro Labs Academic ERP Expansion
**Version 1.0 | Prepared for Hamro Labs Pvt. Ltd. | March 2026**

---

## Executive Summary

Hamro Labs Academic ERP has successfully established product-market fit within Nepal's Loksewa preparation segment. This document analyzes the market fit opportunity for expanding the platform to serve four additional high-potential segments: **Computer Training Institutes**, **Bridge Course Institutes**, **Tuition Centers**, and **Short-Term Skill Training Centers**.

The research findings indicate a total addressable market of **15,000+ institutes** across these four new segments — approximately 3x the current primary target market — all suffering from identical core pain points: manual operations, fee leakage, attendance chaos, and zero digital infrastructure. The existing ERP architecture shares ~65–70% functional overlap with what these new segments require, making expansion highly capital-efficient.

---

## 1. The Post-SEE Transition Economy: Context

Nepal's Secondary Education Examination (SEE) concludes annually in March–April, triggering a predictable economic event: approximately **500,000+ SEE graduates** enter the market simultaneously, creating a 3–6 month enrollment surge across training institutes. This "post-SEE transition industry" is one of Nepal's most active private education sectors.

Students diverge into four primary paths after SEE:
- Academic bridging (Science, Management, Humanities bridge courses)
- Digital and IT skill acquisition (computer training institutes)
- Public service preparation (Loksewa coaching)
- Vocational and CTEVT-affiliated skill training

This ecosystem creates a **high-turnover, seasonally driven** training market that is structurally different from traditional schools — and perfectly suited for a modular SaaS ERP built for short-course, batch-based operations.

---

## 2. Market Segment Analysis

### 2.1 Computer Training Institutes

**Market Overview**

Computer training is the single most popular post-SEE activity for Nepali students. Basic digital literacy has become a non-negotiable prerequisite for government, banking, and administrative employment. Institutes offering Basic Computer, Tally Prime, Graphic Design, and entry-level programming courses operate year-round with consistent demand.

**Segment Characteristics**

| Attribute | Details |
|-----------|---------|
| Primary audience | SEE graduates, college students, working professionals |
| Course duration | 1 month (Basic) to 6 months (Diploma/Specialized) |
| Pricing range | NPR 4,000 (Basic 1-month) to NPR 50,000 (6-month Diploma) |
| Batch size | 10–25 students per practical lab batch |
| Geographic spread | All 77 districts; highest density in Kathmandu, Pokhara, Chitwan, Biratnagar |
| Estimated institute count | 5,000–8,000 nationally |
| Revenue model | Course fees + certification fees + hardware services |

**Unique Operational Characteristics**
- Lab-based practical sessions requiring computer/resource allocation tracking
- Multiple concurrent short courses running in parallel batches
- Certification is the primary deliverable — students are highly motivated by certificate quality
- High student turnover creates constant re-enrollment cycles and lead generation pressure
- Courses like Tally Prime require progress tracking tied to practical exercises, not just exam scores

**Why Current ERP Partially Fits**
The existing batch, fee, and attendance system maps well to this segment. The gap lies in lab management, certificate generation, practical progress tracking, and course-category flexibility (the current system uses Loksewa-specific exam categories that are irrelevant here).

**Market Fit Score: 8/10** — Highest opportunity among new segments. High institute count, affordable pricing expectations align with Starter/Growth plan, and the segment actively seeks digitization.

---

### 2.2 Bridge Course Institutes

**Market Overview**

Bridge courses serve SEE graduates preparing for +2 (higher secondary) entrance exams. The business is intensely seasonal — 80% of annual revenue is earned in a 10–12 week window between April and July. Top bridge course institutes in Kathmandu batch 40–60 students per section and run multiple parallel sections across Science and Management streams.

Research indicates that students who complete bridge programs perform 40–60% better in first-semester outcomes, creating strong parental demand.

**Segment Characteristics**

| Attribute | Details |
|-----------|---------|
| Primary audience | SEE graduates (Grade 10 completers, age 15–17) |
| Course duration | 8–12 weeks (peak: April–June) |
| Pricing range | NPR 5,000–25,000 per course package |
| Batch size | 30–60 students per batch |
| Revenue model | Package fee (tuition + materials) + mock exam fees + model question set sales |
| Geographic concentration | Kathmandu (dominant), Pokhara, Chitwan, Biratnagar |
| Estimated institute count | 1,500–2,500 nationally |

**Unique Operational Characteristics**
- Extremely time-compressed enrollment cycle — everything happens in April: inquiries, admissions, batch allocation, first class
- Multiple parallel streams (Science, Management, Humanities) with different teachers and fee structures
- Mock exam management is central — institutes run 5–15 mock exams in a single season
- Result tracking and ranking boards are a key marketing tool (institutes advertise entrance exam selections)
- Materials distribution (printed notes, model questions) needs to be tracked per student

**Why Current ERP Partially Fits**
The MCQ exam engine, batch management, and fee modules are highly relevant. The gap lies in seasonal rush handling (bulk admission flow), materials distribution tracking, mock exam ranking leaderboards, and entrance result tracking (did the student get admitted to their target college).

**Market Fit Score: 7/10** — Good fit, but requires season-aware features. Institutes in this segment are highly performance-conscious and will demand result tracking and ranking features before adoption.

---

### 2.3 Tuition Centers

**Market Overview**

Tuition centers are the most granular and widespread education business in Nepal. Nearly every urban and semi-urban neighborhood has one. They serve school students (Grade 1–12) with subject-specific coaching, typically on an ongoing monthly subscription basis. These are often home-based or single-room operations run by a teacher-entrepreneur.

**Segment Characteristics**

| Attribute | Details |
|-----------|---------|
| Primary audience | School students (Grade 1–12), parents as primary customers |
| Fee model | Monthly subscription (NPR 800–3,000/subject/month) |
| Batch size | 5–20 students per subject group |
| Operation scale | Very small (1–5 teachers, 20–100 students) |
| Geographic spread | All urban/semi-urban areas across Nepal |
| Estimated institute count | 10,000–15,000 nationally |
| Primary pain point | Monthly fee collection tracking; parents forgetting to pay |

**Unique Operational Characteristics**
- Fee structure is fundamentally different: per-subject monthly billing rather than one-time course fee
- Parent communication is more intensive than other segments — parents expect weekly attendance updates
- No formal batch structure in many cases; students can join mid-month
- Very limited budget for software — pricing must be at the absolute minimum
- Guardian portal is critical here, not optional (parents are paying monthly and want visibility)
- Home tutors often have no formal institute registration

**Why Current ERP Partially Fits**
The fee management and attendance systems are directly applicable. The current student admission form is too heavy for this segment (citizenship number, academic qualifications fields are unnecessary for a 12-year-old student). A simplified lightweight admission mode is needed.

**Market Fit Score: 6/10** — Huge market size but very price-sensitive. The ERP's current complexity is overkill for this segment. A simplified "Tuition Mode" with lightweight UI would dramatically increase adoption.

---

### 2.4 Short-Term Skill Training Institutes (CTEVT-Affiliated)

**Market Overview**

CTEVT (Council for Technical Education and Vocational Training) affiliated centers provide government-recognized vocational training in areas including hospitality, construction, agriculture, health, and IT. Training is structured around hours-based curriculum (160, 390, 780 hours) rather than weeks or months. Certificate graduates receive nationally and internationally recognized qualifications.

**Segment Characteristics**

| Attribute | Details |
|-----------|---------|
| Primary audience | Youth (18–30) seeking employment-ready certification |
| Program structure | Hours-based (160–780 hours per program) |
| Core trades | Cook/Baker, Construction, Caregiver, Computer Basic, Agriculture |
| Funding | Some programs funded by government grants; others fee-based |
| Regulatory requirement | CTEVT registration form compliance, hours log maintenance |
| Estimated institute count | 2,000–3,000 nationally |
| Key differentiator | Graduates receive nationally recognized CTEVT certificates |

**Unique Operational Characteristics**
- Attendance must be tracked in hours, not sessions — regulatory requirement for CTEVT certification
- Training logs must be exportable in CTEVT-compatible formats for renewal and audit
- Assessment is practical/competency-based, not exam-based
- Multiple funding sources (fee-paying students, government-sponsored trainees) require separate financial tracking
- Certificate generation must match CTEVT prescribed format exactly

**Why Current ERP Partially Fits**
The attendance and fee modules are foundational. The critical gap is hours-based attendance tracking, CTEVT-format reporting, and competency-based (rather than MCQ) assessment. These require specific module extensions.

**Market Fit Score: 6.5/10** — Regulatory complexity is a barrier but also a moat. If the ERP can generate CTEVT-compliant reports automatically, it becomes an indispensable tool for this segment.

---

## 3. Comparative Segment Summary

| Dimension | Loksewa (Current) | Computer Training | Bridge Course | Tuition Center | Skill/CTEVT |
|-----------|-------------------|-------------------|---------------|----------------|-------------|
| Institute Count | 5,000+ | 5,000–8,000 | 1,500–2,500 | 10,000–15,000 | 2,000–3,000 |
| Avg. Student Count | 100–500 | 30–150 | 100–600 (seasonal) | 20–100 | 20–80 |
| Fee Model | Course-based | Course-based | Package | Monthly/Subject | Hours-based |
| Exam Type | MCQ mock tests | Practical/MCQ | MCQ entrance prep | Subject tests | Competency |
| Certificate Needed | No (PSC cert) | Yes (institute cert) | No | No | Yes (CTEVT) |
| Seasonal? | No | Mild | Intense | No | Mild |
| Guardian Portal Need | Low | Low | Medium | **High** | Low |
| Regulatory Compliance | Low | Low | Low | Low | **High** |
| Price Sensitivity | Medium | Medium | Medium | **High** | Medium |
| ERP Overlap with Current | — | 65–70% | 60–65% | 50–55% | 55–60% |
| Market Fit Score | ✅ Live | 8/10 | 7/10 | 6/10 | 6.5/10 |

---

## 4. Total Addressable Market Estimate

| Segment | Estimated Institutes | Conversion Target (Year 2) | ARPU (NPR/month) | Projected MRR |
|---------|---------------------|--------------------------|------------------|---------------|
| Loksewa (current) | 5,000+ | 200 | 2,500 | NPR 5,00,000 |
| Computer Training | 5,000–8,000 | 150 | 2,000 | NPR 3,00,000 |
| Bridge Course | 1,500–2,500 | 80 | 2,500 | NPR 2,00,000 |
| Tuition Center | 10,000–15,000 | 300 | 800 | NPR 2,40,000 |
| Skill/CTEVT | 2,000–3,000 | 50 | 2,500 | NPR 1,25,000 |
| **TOTAL** | **~26,000** | **780** | — | **~NPR 13,65,000** |

> Note: ARPU estimates are conservative. Growth and Professional plans will increase blended ARPU as institutes scale.

---

## 5. Competitive Landscape

Nepal currently has no purpose-built multi-segment training institute ERP. The closest competitors are:

- **Pathami ERP** — School-focused, not training institute-specific; no BS calendar
- **Genius EduSoft** — Foreign-designed, expensive, not Nepal-localized
- **Delta Tech Nepal** — Basic school management; no exam engine or multi-tenant SaaS
- **ZenoxERP** — Generic pricing, not education-vertical
- **Manual tools** — Excel + WhatsApp + paper registers dominate 80%+ of the market

**Hamro Labs' sustainable advantages for expansion:**
1. Already has BS calendar, Nepali Unicode SMS, and Nepal-specific admission forms built
2. Multi-tenant architecture scales to new segments without infrastructure changes
3. Feature flag system allows per-tenant module enablement — new institute types can be served without code forking
4. Founder (Devbarat) has direct domain expertise operating Nepal Cyber Firm in this exact ecosystem

---

## 6. Strategic Recommendation

The expansion should be sequenced by market fit score and architectural complexity:

**Priority 1: Computer Training Institutes**
- Highest institute count, highest immediate adoption potential
- Requires only certificate generation + lab module additions
- Can reuse 70% of existing modules
- Target: Add 100 computer institute tenants within 6 months of launch

**Priority 2: Bridge Course Institutes**
- Large seasonal revenue opportunity
- Seasonal enrollment rush tooling is the key differentiator
- Target: Onboard before April SEE rush; 50–80 institutes in Year 1

**Priority 3: Skill/CTEVT Centers**
- Medium complexity; high value if CTEVT compliance is solved
- Target: 30–50 institutes in Year 1 once CTEVT reporting module is built

**Priority 4: Tuition Centers**
- Largest volume but requires simplified "Tuition Mode" to be viable
- Best addressed via a cheaper entry-level plan (NPR 500–800/month)
- Target: 200–300 tenants via self-serve onboarding in Year 2

---

*End of Market Fit Analysis*
