# Batch Filtering Implementation Plan

## Executive Summary

**Issue:** The front desk portal and other areas of the application fetch ALL batches (including `completed` batches) when populating dropdowns for student operations. This allows users to incorrectly assign students to finished batches.

**Solution:** Implement status-based filtering to only show relevant batches based on the operation context.

---

## 1. Affected Files Analysis

### 1.1 Front Desk Portal (Priority: HIGH)

| File | Line | Current Query | Purpose | Required Filter |
|------|------|---------------|---------|-----------------|
| [`admission-form.php`](resources/views/front-desk/admission-form.php:25) | 25-27 | All batches | Student admission | `active`, `upcoming` |
| [`attendance-mark.php`](resources/views/front-desk/attendance-mark.php:25) | 25-27 | All batches | Mark attendance | `active` only |
| [`attendance-report.php`](resources/views/front-desk/attendance-report.php:20) | 20-21 | All batches | View attendance reports | `active`, `completed` |
| [`email-send.php`](resources/views/front-desk/email-send.php:20) | 20-21 | All batches | Send emails to batches | All statuses |
| [`sms-send.php`](resources/views/front-desk/sms-send.php:20) | 20-21 | All batches | Send SMS to batches | All statuses |
| [`report-fees.php`](resources/views/front-desk/report-fees.php:20) | 20-21 | All batches | Fee reports | All statuses |

### 1.2 Admin Portal

| File | Line | Current Query | Purpose | Required Filter |
|------|------|---------------|---------|-----------------|
| [`report-fees.php`](resources/views/admin/report-fees.php:33) | 33-34 | All batches | Fee reports | All statuses |

### 1.3 API Controllers

| File | Line | Current Query | Purpose | Required Filter |
|------|------|---------------|---------|-----------------|
| [`students.php`](app/Http/Controllers/FrontDesk/students.php:151) | 151-152 | Validate batch exists | Student registration validation | `active`, `upcoming` |
| [`students.php`](app/Http/Controllers/FrontDesk/students.php:337) | 337-338 | Validate batch for update | Student update validation | `active`, `upcoming` |

---

## 2. Batch Status Definitions

```sql
-- Batches table status enum
status ENUM('upcoming', 'active', 'completed') DEFAULT 'upcoming'
```

| Status | Definition | Use Cases |
|--------|------------|-----------|
| `upcoming` | Batch start date is in the future | New admissions, early enrollment |
| `active` | Batch is currently running | Admissions, attendance, fees |
| `completed` | Batch has ended | Reports, historical data only |

---

## 3. Implementation Rules by Context

### 3.1 Student Admission / Registration
**Rule:** Only show `active` and `upcoming` batches
```php
$stmt = $db->prepare("SELECT id, course_id, name, shift FROM batches 
                     WHERE tenant_id = :tid 
                     AND status IN ('active', 'upcoming') 
                     AND deleted_at IS NULL ORDER BY name");
```

**Files to Update:**
- [`resources/views/front-desk/admission-form.php`](resources/views/front-desk/admission-form.php:25)
- [`app/Http/Controllers/FrontDesk/students.php`](app/Http/Controllers/FrontDesk/students.php:151) (validation)

### 3.2 Attendance Marking
**Rule:** Only show `active` batches (can't mark attendance for future or past batches)
```php
$stmt = $db->prepare("SELECT id, course_id, name, shift FROM batches 
                     WHERE tenant_id = :tid 
                     AND status = 'active' 
                     AND deleted_at IS NULL ORDER BY name");
```

**Files to Update:**
- [`resources/views/front-desk/attendance-mark.php`](resources/views/front-desk/attendance-mark.php:25)

### 3.3 Attendance Reports
**Rule:** Show `active` and `completed` batches (for historical reporting)
```php
$stmt = $db->prepare("SELECT id, name FROM batches 
                     WHERE tenant_id = :tid 
                     AND status IN ('active', 'completed') 
                     AND deleted_at IS NULL ORDER BY name");
```

**Files to Update:**
- [`resources/views/front-desk/attendance-report.php`](resources/views/front-desk/attendance-report.php:20)

### 3.4 Communication (Email/SMS)
**Rule:** Show ALL batches (may need to contact students from completed batches)
```php
$stmt = $db->prepare("SELECT id, name FROM batches 
                     WHERE tenant_id = :tid 
                     AND deleted_at IS NULL ORDER BY name");
```

**Files:** No changes needed (already correct)
- [`resources/views/front-desk/email-send.php`](resources/views/front-desk/email-send.php:20)
- [`resources/views/front-desk/sms-send.php`](resources/views/front-desk/sms-send.php:20)

### 3.5 Fee Reports
**Rule:** Show ALL batches (financial reporting needs historical data)
```php
$stmt = $db->prepare("SELECT id, name FROM batches 
                     WHERE tenant_id = :tid 
                     AND deleted_at IS NULL ORDER BY name");
```

**Files:** No changes needed (already correct)
- [`resources/views/front-desk/report-fees.php`](resources/views/front-desk/report-fees.php:20)
- [`resources/views/admin/report-fees.php`](resources/views/admin/report-fees.php:33)

---

## 4. Implementation Phases

### Phase 1: Critical Fixes (Front Desk Admission)
**Priority:** URGENT
**Files:**
1. [`resources/views/front-desk/admission-form.php`](resources/views/front-desk/admission-form.php:25-27)
2. [`app/Http/Controllers/FrontDesk/students.php`](app/Http/Controllers/FrontDesk/students.php:151-152)
3. [`app/Http/Controllers/FrontDesk/students.php`](app/Http/Controllers/FrontDesk/students.php:337-338)

**Changes:**
```php
// Line 25: Add status filter
$stmtBatches = $db->prepare("SELECT id, course_id, name, shift FROM batches 
                             WHERE tenant_id = :tid 
                             AND status IN ('active', 'upcoming') 
                             AND deleted_at IS NULL ORDER BY name");

// Line 151-152: Add validation for active/upcoming only
$stmt = $db->prepare("SELECT id FROM batches 
                      WHERE id = :bid AND tenant_id = :tid 
                      AND status IN ('active', 'upcoming')
                      AND deleted_at IS NULL");
```

### Phase 2: Attendance System
**Priority:** HIGH
**Files:**
1. [`resources/views/front-desk/attendance-mark.php`](resources/views/front-desk/attendance-mark.php:25-27)
2. [`resources/views/front-desk/attendance-report.php`](resources/views/front-desk/attendance-report.php:20-21)

**Changes:**
```php
// attendance-mark.php: Active only
AND status = 'active'

// attendance-report.php: Active and completed
AND status IN ('active', 'completed')
```

### Phase 3: Testing & Validation
**Priority:** HIGH
**Steps:**
1. Create test batches with different statuses
2. Verify admission form only shows active/upcoming
3. Verify attendance marking only shows active
4. Verify validation rejects completed batch IDs

---

## 5. Testing Strategy

### 5.1 Unit Tests
Create test cases for each affected file:

```php
// Example test structure
class BatchFilteringTest extends TestCase
{
    public function test_admission_form_only_shows_active_and_upcoming_batches()
    {
        // Create active batch
        // Create upcoming batch
        // Create completed batch
        // Assert only active and upcoming appear in dropdown
    }
    
    public function test_cannot_register_student_to_completed_batch()
    {
        // Attempt to register student to completed batch
        // Assert validation error
    }
}
```

### 5.2 Manual Testing Checklist

| Feature | Test Case | Expected Result |
|---------|-----------|-----------------|
| Admission Form | Open admission form | Only active/upcoming batches shown |
| Admission Form | Try to hack API with completed batch ID | Error: "Invalid batch selected" |
| Attendance Mark | Open attendance page | Only active batches shown |
| Attendance Report | Open report filter | Active and completed batches shown |
| Fee Collection | Select student from completed batch | Should work (for fee collection) |

---

## 6. Rollback Plan

If issues arise:
1. Revert the specific file changes
2. Clear application cache
3. Notify users of temporary reversion

---

## 7. Success Metrics

1. **Zero** students registered to `completed` batches after implementation
2. **Zero** attendance marked for `upcoming` or `completed` batches
3. All existing functionality preserved for valid use cases

---

## 8. Timeline

| Phase | Duration | Owner |
|-------|----------|-------|
| Phase 1: Critical Fixes | 1 day | Developer |
| Phase 2: Attendance System | 1 day | Developer |
| Phase 3: Testing | 2 days | QA |
| **Total** | **4 days** | |

---

## Appendix: Current vs Proposed Queries

### Current (Problematic)
```php
$stmtBatches = $db->prepare("SELECT id, course_id, name, shift FROM batches 
                             WHERE tenant_id = :tid 
                             AND deleted_at IS NULL ORDER BY name");
```

### Proposed (Fixed)
```php
// For admissions
$stmtBatches = $db->prepare("SELECT id, course_id, name, shift FROM batches 
                             WHERE tenant_id = :tid 
                             AND status IN ('active', 'upcoming')
                             AND deleted_at IS NULL ORDER BY name");

// For attendance marking
$stmtBatches = $db->prepare("SELECT id, course_id, name, shift FROM batches 
                             WHERE tenant_id = :tid 
                             AND status = 'active'
                             AND deleted_at IS NULL ORDER BY name");
```
