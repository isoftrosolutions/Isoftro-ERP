when adding a new batch or course for an existing student there is submit or confirm btn and      it is not reflecting in the student profile page

You are acting as a Senior ERP Product Auditor, Education Operations Analyst, and Software Architecture Reviewer.

Your task is to perform a comprehensive audit of the currently implemented "Time Builder / Timetable Builder" functionality in the system.

First, carefully read and analyze all relevant files related to this feature, including:

- Controllers
- Models
- Services
- Database schema
- Migration files
- Views / UI components
- Routes
- API endpoints
- Any scheduling or timetable related utilities

After reviewing the implementation, perform the following analysis:

1. Understand the intended purpose of the Time Builder feature.
2. Document the current workflow of how a timetable is created, edited, and managed.
3. Identify all database tables and relationships used for scheduling.
4. Analyze business logic used for:
   - class scheduling
   - teacher allocation
   - room allocation
   - batch/course timing
5. Detect architectural or design issues.
6. Identify missing validations, edge cases, or broken logic.
7. Detect unused or partially implemented code.
8. Identify performance risks or scalability limitations.
9. Check if the feature supports:
   - conflict detection (teacher/room/time)
   - drag-and-drop timetable building
   - recurring schedules
   - batch/course specific timetables
   - teacher availability
   - room availability
10. Identify gaps between current implementation and a robust institute-level timetable system.

Then produce the following outputs:

1. **TimeBuilder_Audit_Report.md**
   - Current implementation overview
   - Architecture analysis
   - Database structure analysis
   - Identified gaps
   - Missing features
   - UX limitations
   - Technical risks

2. **TimeBuilder_Gap_Analysis.md**
   - Feature gaps
   - Business workflow gaps
   - Missing automation
   - Missing constraints
   - Missing UI capabilities

3. **TimeBuilder_Improvement_Roadmap.md**
   - Immediate fixes
   - Structural improvements
   - Feature upgrades
   - Long-term scalability improvements

Before generating the final reports, ask clarification questions if any part of the implementation is unclear.


why the academic time table builder page is not opening there is only showing a  loading spinner


refactor the teacher sidebar and make it as same as the front desk portal in the in the sense color , font, typography, and the hover effects 

Now connect  the  teacher portal to the database records  fetch the teacher actual real classes , lectures , attendance , pendig graeds , submmitted question papers , etc

You are acting as a Senior Software Architect, Security Auditor, and ERP System Analyst.

I have completed the Institute Admin Portal of an ERP system for education institutes (Loksewa institutes, computer training institutes, bridge courses, and small tuition centers).

Your task is to perform a complete technical audit of the Admin Portal.

Before starting the audit:

Carefully read all relevant project files, including but not limited to:

Controllers

Models

Services

Middleware

API routes

Admin portal pages / UI files

Database schema (readme.sql, migrations)

Config files

Utility/helper functions

Authentication and session handling logic

Role & permission system

Any documentation files

Do not start auditing until you fully understand the system structure.

Phase 1 — System Understanding

First analyze and summarize:

Overall system architecture

Module structure of the Admin Portal

Key features available for Institute Admin

Database structure and relationships

Authentication and role management

API structure

Produce a short System Overview Section.

Phase 2 — Functional Audit

Check whether the Admin Portal correctly supports typical institute operations:

Examples:

Student management

Course management

Batch management

Fee management

Teacher management

Timetable management

Attendance

Leave management

Notifications

Reports / analytics

For each module check:

Missing functionality

Broken logic

Incomplete flows

Edge cases not handled

Phase 3 — Code Quality Audit

Analyze the codebase for:

Code duplication

Poor naming

Tight coupling

Unused files or dead code

Large controllers or services

Missing validation

Inconsistent architecture

Poor separation of concerns

Recommend clean architecture improvements.

Phase 4 — Database Audit

Inspect database design:

Missing indexes

Poor table normalization

Weak foreign key relationships

Redundant tables

Improper column types

Scalability risks

Also detect:

Queries that may break after schema changes

Inefficient joins

N+1 query risks

Phase 5 — Security Audit

Check for vulnerabilities such as:

SQL injection risks

Improper input validation

Authentication flaws

Session security issues

Missing authorization checks

File upload vulnerabilities

XSS risks

CSRF risks

Sensitive data exposure

Provide recommended fixes.

Phase 6 — Performance Audit

Analyze:

Heavy queries
Unoptimized endpoints

Large payload responses

Missing caching opportunities

Slow page rendering risks

Suggest performance improvements.

Phase 7 — UX / Product Audit

Evaluate Admin Portal from a product perspective:

Missing dashboards

Missing reports

Hard workflows for institute admins

Confusing UI logic

Missing automation opportunities

Suggest improvements to make the ERP more competitive for institutes in Nepal.

Phase 8 — Generate Reports

Produce the following files:

1️⃣ audit-report.md

Complete audit report including:

System architecture review

Module analysis

Code quality review

Security issues

Performance issues

UX/Product suggestions

2️⃣ critical-fixes.md

List urgent problems that must be fixed immediately, such as:

Security vulnerabilities

Broken queries

Crashing features

Data integrity risks

3️⃣ improvement-roadmap.md

Suggest a roadmap including:

Short term improvements

Medium term improvements

Long term ERP scaling strategy

Important Rules

Do not guess. Only analyze based on the files you read.

If something is unclear, ask questions before making assumptions.

Prioritize real production risks.

