# iSoftro ERP — Project Analysis Report

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### 📦 1. MODULE COMPLETION STATUS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

| Module Name | Completion | Status | What is Working | What is Missing/Broken | Code Quality |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Authentication & Auth** | 95% | ✅ Done | JWT & Session hybrid mapping, OTP flows, Impersonation, Password Resets. | None, but CSRF logic is deliberately bypassed (see Security). | Good |
| **Student Management** | 100% | ✅ Done | End-to-end CRUD, CSV export, multi-file uploads (with proper MIME validation), alumni tracking. | Fully featured. | Good |
| **Fees & Invoicing** | 100% | ✅ Done | Ledger, bulk payments, late fines calculation, PDF receipts, background email dispatch. | Fully featured. | Good |
| **Academics (Batches/Classes)**| 95% | ✅ Done | Course categories, shifts, subjects, room allocations. | Minor edge cases in specific reporting flows. | Good |
| **Exams & Homework** | 95% | ✅ Done | Manual/Auto question modes, attempts tracking, assignment submissions & grading. | Analytics visualization might be incomplete. | Good |
| **LMS (Study Materials)** | 95% | ✅ Done | Material uploads, category nesting, strict access control by batch/student, feedback. | Advanced video handling (HLS) absent. | Average |
| **Attendance** | 95% | ✅ Done | Daily logging, locking features, leave requests mapping. | Biometric device sync is absent (manual only). | Good |
| **Communication (SMS/Email)**| 90% | ✅ Done | Background processing via `QueueService`, templating for alerts. | SMS gateway integration currently limited (Sparrow/Aakash).| Good |
| **HR & Payroll** | 80% | 🔄 Partial | Salary tracking, teacher profiles, leave balances. | Advance salary requests / dynamic tax deductions missing. | Average |
| **FrontDesk / Inquiries** | 85% | 🔄 Partial | Appointment tracking, call logs, follow-ups, visitor logs. | Pipeline/Kanban features for admissions. | Average |
| **Accounting** | 65% | 🔄 Partial | `acc_accounts`, vouchers, ledger postings, expense tracking with categories. | Still reliant on older expense wrappers. Needs UI unification.| Needs Refactor |
| **Library** | 0% | ❌ Not Started| Tables exist (`library_books`, `library_issues`). | The API controller (`library.php`) simply returns a stub: *"Library module is coming in V3.1."* | N/A |
| **Transport / Hostel** | 0% | ❌ Not Started| None. | Database tables do not exist. | N/A |

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### 🏆 2. TOP NOTCH EXECUTED FEATURES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. **Fee Collection & PDF Receipts System**
   - **Why it’s well done:** Directly integrated with a robust `student_fee_summary` ledger. It accurately tracks discounts, partial payments, and dynamically calculates late fines. It executes a seamless background job via `QueueService` to shoot out beautifully templated PDF receipts to students.
2. **Multi-Tenant (SaaS) Architecture**
   - **Why it’s well done:** Total data isolation. Almost every single database operation systematically binds the `tenant_id`. RBAC effectively confines visibility. It's built cleanly enough to support true horizontal scale without tenant bleeding.
3. **Super Admin Impersonation**
   - **Why it’s well done:** Super admins can seamlessly impersonate any institute admin using `impersonation_tokens` (`SuperAdminController`). This allows for frictionless customer support without asking clients for passwords.
4. **Student File Handling & Security**
   - **Why it’s well done:** The `validateAndUploadFile` logic in the controllers bypasses standard file extension vulnerability. It properly inspects binary data using `finfo(FILEINFO_MIME_TYPE)` to prevent shell script injection.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### ⚠️ 3. INCOMPLETE OR WEAK MODULES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. **Library Management**
   - **Status:** Backend schema exists, but the associated `library.php` API simply has a hardcoded array response stating it is *"coming in V3.1"*. UI might be present but the backend is a stub.
2. **Accounting Wrappers**
   - **Status:** While the `AccountingController` handles full double-entry bookkeeping ledgers (`acc_vouchers`, `acc_accounts`), some older scripts like `expenses.php` act as simple pass-through wrappers. It leaves a slightly fragmented codebase.
3. **Role & Permission Management**
   - **Status:** Functional but rigidly hardcoded. The permissions are defined statically in `config.php` inside the `$ROLES` global array. There is currently no database table or dynamic UI allowing a tenant to create custom sub-roles (e.g., "Junior Accountant").

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### 🗄️ 4. DATABASE STATUS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- **Created & Migrated Tables:** Over 62 structured tables are successfully deployed.
- **Top Tables Active:** `users`, `tenants`, `students`, `fee_records`, `payment_transactions`, `enrollments`, `study_materials`, `attendance`.
- **Missing Data Connections:** `library_books` and `library_issues` schemas exist, but remain unconnected to API logic.
- **Relationship Quality:** Outstanding. Uses `BIGINT UNSIGNED` for heavy scaling. There are proper mapping tables like `batch_subject_allocations` and `assignment_submissions`.
- **Schema Gaps:** Inventory, Transportation, and Hostel tables are completely unwritten. 

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### 🔐 5. SECURITY & ROLE MANAGEMENT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- **Are Roles Properly Guarded?** 
  - Yes. Handled via `enforceAccess()`, `requirePermission()`, and `requireModule()`. If an `instituteadmin` requests super-admin endpoints, execution dies with a 403 HTTP response.
- **Session Handling vs. JWT:** 
  - The software uses a hybrid mechanism. Newer API endpoints validate a stateless JWT token via `Authorization` header/cookie, while web views predominantly use `$_SESSION['userData']`.
- **SQL Injection:** 
  - None visible. Direct PDO statements utilizing strictly parameterized queries (`$stmt->execute([':id' => $val])`) protect the system comprehensively.
- **⚠️ CRITICAL: CSRF VULNERABILITY**
  - In `config.php`, the global `verifyCSRFToken()` override returns `true` automatically, carrying the comment: *"JWT is its own security, CSRF is disabled globally"*. **This is a dangerous misconfiguration.** Because the logic checks `$_SESSION` as *Priority #1* in `isLoggedIn()`, skipping CSRF validation exposes the platform to cross-site request forgery attacks on all POST routes. 

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
### 📊 6. OVERALL PROJECT SUMMARY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

- **Overall Completion:** **~85%** (Ready for the core market).
- **Top 5 Strongest Features:** 
  1. Billing, Invoicing & Fines.
  2. Sub-Domain SaaS Architecture.
  3. Student Profile & Enrollment flow.
  4. Role Impersonation.
  5. API-Driven Front-Desk tools.

- **Top 5 Weakest/Missing Features:** 
  1. Library Management APIs.
  2. Inventory Tracking.
  3. Advanced Custom Reporting Builder.
  4. Customizable Roles (Hardcoded currently).
  5. CSRF Implementation.

- **Is this project ready for a DEMO to a real institute?**
  **YES.** The lifeblood of any educational ERP is its ability to seamlessly admit students, place them in batches, charge tuition, and issue receipts. iSoftro ERP does this flawlessly. The underlying multi-tenant architecture ensures that the demo will feel production-grade, snappy, and reliable. 

- **TOP 3 Things to Fix FIRST before demo:**
  1. **Hide the Library Module:** Prevent the user from clicking the Library tab and seeing an API "V3.1 coming soon" mock data screen.
  2. **Patch the CSRF Bug:** Re-enable CSRF tokens or enforce `SameSite=Strict` cookies to ensure that session-based web requests cannot be hijacked.
  3. **Sanitize Web Dashboards:** Ensure the "Outstanding Fees" and "Attendance metrics" match the raw data outputs perfectly across all Dashboard UI widgets, as demo environments usually suffer from mismatched dummy data.
