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