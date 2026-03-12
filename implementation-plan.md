# Implementation Plan - Database Code Fixes

## Using Existing Schema (No Database Changes)

This plan fixes the codebase to work with the **current database schema** without any schema modifications.

---

## Understanding the Current Schema

Based on the existing `realdb.sql`, here's what actually exists:

### Students Table Structure (EXISTING):

```sql
students(id, tenant_id, user_id, roll_no, dob_bs, gender, blood_group,
         citizenship_no, national_id, permanent_address, temporary_address,
         academic_qualifications, admission_date, photo_url, identity_doc_url,
         status, registration_mode, registration_status, id_card_status)
```

**Key:** Students links to users via `user_id` to get name/email.

### Correct Table Names (EXISTING):

- `library_issues` (NOT library_borrowings)
- `exam_attempts` (NOT exam_results)
- `enrollments` (NOT student_batch_enrollments)
- `teachers` (NOT staff)

---

## Phase 1: Model Fixes (Priority 1)

### 1.1 Update Student Model

**File:** `app/Models/Student.php`

Add accessor methods to get name/email from the linked user:

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\TenantScoped;
use App\Helpers\EncryptionHelper;
use App\Helpers\DateUtils;

class Student extends Model {
    use SoftDeletes, TenantScoped;

    protected $table = 'students';

    protected $with = ['user'];

    protected $fillable = [
        'tenant_id', 'user_id', 'roll_no', 'dob_bs', 'gender', 'blood_group',
        'citizenship_no', 'national_id', 'permanent_address', 'temporary_address',
        'academic_qualifications', 'admission_date', 'photo_url', 'identity_doc_url', 'status',
        'registration_mode', 'registration_status', 'id_card_status'
    ];

    /**
     * Relationship with the User account
     */
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get full name from linked user or use roll_no
     */
    public function getFullNameAttribute() {
        return $this->user ? $this->user->name : $this->roll_no;
    }

    /**
     * Get email from linked user
     */
    public function getEmailAttribute() {
        return $this->user ? $this->user->email : null;
    }

    /**
     * Get phone from linked user
     */
    public function getPhoneAttribute() {
        return $this->user ? $this->user->phone : null;
    }

    /**
     * Relationship with Guardians
     */
    public function guardians() {
        return $this->hasMany(Guardian::class, 'student_id');
    }

    /**
     * Get active enrollment (batch info)
     */
    public function activeEnrollment() {
        return $this->hasOne(Enrollment::class, 'student_id')->where('status', 'active');
    }

    /**
     * Get batch through enrollment
     */
    public function batch() {
        return $this->hasOneThrough(Batch::class, Enrollment::class, 'student_id', 'id')
            ->where('enrollments.status', 'active');
    }

    /**
     * Get course through enrollment
     */
    public function course() {
        return $this->hasOneThrough(Course::class, Enrollment::class, 'student_id', 'id')
            ->where('enrollments.status', 'active');
    }

    /**
     * Get students by batch (Via Enrollments table)
     */
    public static function getByBatch($batchId) {
        return self::join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->where('enrollments.batch_id', $batchId)
            ->where('enrollments.status', 'active')
            ->select('students.*')
            ->orderBy('roll_no')
            ->get();
    }

    /**
     * Get students by tenant (Global Scope handles isolation)
     */
    public static function getByTenant($status = null) {
        $query = self::orderBy('created_at', 'DESC');
        if ($status) $query->where('status', $status);
        return $query->get();
    }

    /**
     * Generate next roll no
     */
    public static function generateRollNo($tenantId = null) {
        $maxId = self::withoutGlobalScopes()->withTrashed()->max('id');
        $nextId = (int)$maxId + 1;
        $year = date('Y');
        try { $year = DateUtils::getCurrentYear(); } catch (\Throwable $e) {}

        return "STD-{$year}-" . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Search students (Joining User table for Name search)
     */
    public static function search($term) {
        return self::join('users', 'students.user_id', '=', 'users.id')
            ->where(function($q) use ($term) {
                $q->where('users.name', 'LIKE', "%{$term}%")
                  ->orWhere('students.roll_no', 'LIKE', "%{$term}%");
            })
            ->select('students.*')
            ->limit(20)
            ->get();
    }
}
```

### 1.2 Update User Model

**File:** `app/Models/User.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\TenantScoped;

class User extends Model {
    use SoftDeletes, TenantScoped;

    protected $table = 'users';

    protected $fillable = [
        'tenant_id', 'role', 'email', 'password_hash', 'name', 'phone',
        'status', 'two_fa_enabled', 'locked_until', 'avatar',
        'two_factor_enabled', 'two_factor_secret'
    ];

    protected $hidden = [
        'password_hash', 'two_factor_secret'
    ];

    /**
     * Get the student record for this user (if role is student)
     */
    public function student() {
        return $this->hasOne(Student::class, 'user_id');
    }

    /**
     * Get the teacher record for this user (if role is teacher)
     */
    public function teacher() {
        return $this->hasOne(Teacher::class, 'user_id');
    }

    /**
     * Get all users (Eloquent version)
     */
    public function allUsers() {
        return self::orderBy('created_at', 'DESC')->get()->toArray();
    }

    /**
     * Static finder for email
     */
    public static function findByEmail($email) {
        return self::where('email', $email)->first();
    }

    /**
     * Create new user (Eloquent version)
     */
    public function createUser($data) {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            unset($data['password']);
        }

        $user = self::create($data);
        return $user->toArray();
    }

    /**
     * Update user (Eloquent version)
     */
    public function updateUser($id, $data) {
        $user = self::find($id);
        if (!$user) return null;

        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            unset($data['password']);
        }

        $user->update($data);
        return $user->toArray();
    }

    /**
     * Verify password
     */
    public function verifyPassword($email, $password) {
        $user = self::findByEmail($email);

        if ($user && password_verify($password, $user->password_hash)) {
            return $user->toArray();
        }

        return null;
    }

    /**
     * Update last login
     */
    public function updateLastLogin($id) {
        return self::where('id', $id)->update(['last_login_at' => now()]);
    }

    /**
     * Record Failed Login Attempt
     */
    public function recordFailedLogin($userId, $ipAddress) {
        return \App\Helpers\AuditLogger::log('LOGIN_FAILURE', $userId, null, ['ip' => $ipAddress]);
    }

    /**
     * For authentication - Laravel uses this method
     */
    public function getAuthPassword() {
        return $this->password_hash;
    }
}
```

### 1.3 Add Enrollment Model

**File:** `app/Models/Enrollment.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class Enrollment extends Model {
    use TenantScoped;

    protected $table = 'enrollments';
    protected $fillable = [
        'tenant_id', 'student_id', 'batch_id', 'enrollment_id',
        'enrollment_date', 'status', 'status_changed_at'
    ];

    public function student() {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function batch() {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function feeSummary() {
        return $this->hasOne(StudentFeeSummary::class, 'enrollment_id');
    }
}
```

### 1.4 Add Teacher Model

**File:** `app/Models/Teacher.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\TenantScoped;

class Teacher extends Model {
    use SoftDeletes, TenantScoped;

    protected $table = 'teachers';

    protected $fillable = [
        'tenant_id', 'user_id', 'employee_id', 'full_name', 'phone', 'email',
        'qualification', 'specialization', 'joined_date', 'monthly_salary',
        'leave_balance', 'status'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getNameAttribute() {
        return $this->full_name;
    }
}
```

---

## Phase 2: Controller Fixes (Priority 2)

### 2.1 Fix Student Queries - Admin/students.php

Replace all occurrences of `s.full_name` and `s.email` with proper JOINs:

**Pattern 1 - Basic student listing:**

```php
// BEFORE (broken):
$query = "SELECT s.id, s.full_name, s.email, s.roll_no FROM students s";

// AFTER (fixed):
$query = "SELECT s.id, COALESCE(u.name, s.roll_no) as full_name, u.email, s.roll_no
           FROM students s
           LEFT JOIN users u ON s.user_id = u.id";
```

**Pattern 2 - With batch/course info:**

```php
// BEFORE (broken):
$query = "SELECT s.full_name, s.email, b.name as batch_name, c.name as course_name
           FROM students s
           LEFT JOIN batches b ON s.batch_id = b.id";

// AFTER (fixed):
$query = "SELECT COALESCE(u.name, s.roll_no) as full_name, u.email, b.name as batch_name, c.name as course_name
           FROM students s
           LEFT JOIN users u ON s.user_id = u.id
           LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
           LEFT JOIN batches b ON e.batch_id = b.id
           LEFT JOIN courses c ON b.course_id = c.id";
```

### 2.2 Fix Library Queries - Student/library.php

Replace `library_borrowings` with `library_issues`:

```php
// BEFORE (broken):
$query = "SELECT lb.*, b.title as book_title FROM library_borrowings lb
          JOIN library_books b ON lb.book_id = b.id";

// AFTER (fixed):
$query = "SELECT li.*, b.title as book_title FROM library_issues li
          JOIN library_books b ON li.book_id = b.id";
```

Also check column mappings:

- `lb.status` → verify exists in library_issues
- `lb.returned_at` → check if column exists

### 2.3 Fix Exam Queries - Student/exams.php

Replace `exam_results` with `exam_attempts`:

```php
// BEFORE (broken):
$query = "SELECT er.*, e.title as exam_title FROM exam_results er
          JOIN exams e ON er.exam_id = e.id";

// AFTER (fixed):
$query = "SELECT ea.*, e.title as exam_title FROM exam_attempts ea
          JOIN exams e ON ea.exam_id = e.id";
```

Column mapping:

- `er.marks_obtained` → `ea.score`
- `er.rank_position` → May need calculation from score

### 2.4 Fix Staff References - Student/assignments.php

Replace `staff` table with `teachers`:

```php
// BEFORE (broken):
$query = "SELECT a.*, st.full_name as teacher_name FROM assignments a
          LEFT JOIN staff st ON a.teacher_id = st.id";

// AFTER (fixed):
$query = "SELECT a.*, t.full_name as teacher_name FROM assignments a
          LEFT JOIN teachers t ON a.teacher_id = t.id";
```

### 2.5 Fix Batch Enrollment - Student/leaderboard.php

Replace direct `students.batch_id` with enrollments:

```php
// BEFORE (broken):
$query = "SELECT s.* FROM students s
          JOIN batches b ON s.batch_id = b.id";

// AFTER (fixed):
$query = "SELECT s.* FROM students s
          JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
          JOIN batches b ON e.batch_id = b.id";
```

---

## Phase 3: Missing Table Handling (Priority 3)

### 3.1 Handle student_payments

Since `student_payments` doesn't exist, redirect to use `payment_transactions`:

```php
// In Admin/students.php, Student/profile.php
// Replace:
$stmt = $db->prepare("SELECT * FROM student_payments WHERE ...");
// With:
$stmt = $db->prepare("
    SELECT id, student_id, amount, payment_date, payment_method as payment_mode,
           receipt_number as reference, receipt_number as receipt_number,
           'transaction' as source
    FROM payment_transactions
    WHERE ..."
);
```

For historical data, create a UNION:

```php
$query = "
    SELECT id, student_id, amount, payment_date, payment_method,
           receipt_number, 'transaction' as source_type
    FROM payment_transactions
    WHERE student_id = :sid AND tenant_id = :tid
    UNION
    SELECT id, student_id, amount, payment_date, payment_mode,
           reference, 'historical' as source_type
    FROM fee_records
    WHERE student_id = :sid AND tenant_id = :tid AND amount_paid > 0
    ORDER BY payment_date DESC
";
```

### 3.2 Handle support_replies

Create view or skip for now:

```php
// In SuperAdmin/SupportApi.php
// Since support_replies doesn't exist, modify to not depend on it
// Or add try-catch to handle missing table gracefully
```

---

## Phase 4: Create Helper Trait (Priority 4)

### 4.1 Student Query Helper Trait

**File:** `app/Helpers/StudentQueryHelper.php`

```php
<?php
namespace App\Helpers;

class StudentQueryHelper {

    /**
     * Get basic student select with user join
     */
    public static function selectBasic() {
        return "s.id, COALESCE(u.name, s.roll_no) as full_name, u.email,
                s.roll_no, s.status, s.registration_mode, s.photo_url";
    }

    /**
     * Get student with enrollment info
     */
    public static function selectWithEnrollment() {
        return "s.id, COALESCE(u.name, s.roll_no) as full_name, u.email, u.phone,
                s.roll_no, s.status, s.photo_url,
                b.id as batch_id, b.name as batch_name,
                c.id as course_id, c.name as course_name";
    }

    /**
     * Build student join clause
     */
    public static function joinUser() {
        return "LEFT JOIN users u ON s.user_id = u.id";
    }

    /**
     * Build enrollment join clause
     */
    public static function joinEnrollment() {
        return "LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                LEFT JOIN batches b ON e.batch_id = b.id
                LEFT JOIN courses c ON b.course_id = c.id";
    }

    /**
     * Build payment history query using existing tables
     */
    public static function getPaymentHistoryQuery($studentId, $tenantId) {
        return "
            SELECT 'transaction' as source_type, id, amount, payment_date,
                   payment_method as payment_mode, receipt_number as reference
            FROM payment_transactions
            WHERE student_id = :sid AND tenant_id = :tid

            UNION ALL

            SELECT 'fee_record' as source_type, id, amount_paid as amount,
                   paid_date as payment_date, payment_mode, receipt_no as reference
            FROM fee_records
            WHERE student_id = :sid AND tenant_id = :tid AND amount_paid > 0

            ORDER BY payment_date DESC
        ";
    }
}
```

---

## Quick Reference: File-by-File Changes

| File                                           | Changes Required                                                      |
| ---------------------------------------------- | --------------------------------------------------------------------- |
| `app/Models/Student.php`                       | Add full_name, email, phone accessors; add batch/course relationships |
| `app/Models/User.php`                          | Add student/teacher relationships                                     |
| `app/Models/Enrollment.php`                    | Create new model                                                      |
| `app/Models/Teacher.php`                       | Create new model                                                      |
| `app/Http/Controllers/Admin/students.php`      | 15+ query fixes - add JOINs                                           |
| `app/Http/Controllers/FrontDesk/students.php`  | 15+ query fixes - add JOINs                                           |
| `app/Http/Controllers/Student/library.php`     | library_borrowings → library_issues                                   |
| `app/Http/Controllers/Student/exams.php`       | exam_results → exam_attempts                                          |
| `app/Http/Controllers/Student/profile.php`     | Multiple fixes                                                        |
| `app/Http/Controllers/Student/leaderboard.php` | students.batch_id → enrollments                                       |
| `app/Http/Controllers/Student/assignments.php` | staff → teachers                                                      |
| `app/Helpers/StudentQueryHelper.php`           | Create helper trait                                                   |

---

## Testing Checklist

After implementing fixes:

- [ ] Student listing page loads
- [ ] Student search works
- [ ] Payment history displays
- [ ] Library books display
- [ ] Exam results display
- [ ] Student profile loads
- [ ] Teacher assignments show teacher name
- [ ] Leaderboard shows correct students

---

## Summary

This implementation plan fixes all controller and model issues **without changing the database schema**:

1. **Model updates** - Add accessor methods to Student/User models
2. **Query fixes** - Add proper JOINs to controllers
3. **Table name fixes** - Use correct existing table names
4. **Helper utility** - Create reusable query helper

The key insight: The **students table is designed to link to users via user_id**, so all queries must JOIN with users table to get name/email/phone information.
