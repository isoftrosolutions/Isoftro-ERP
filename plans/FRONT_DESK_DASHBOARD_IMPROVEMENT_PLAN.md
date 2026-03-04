# Front Desk Dashboard Improvement - Implementation Plan

**Document Version:** 1.0  
**Based on:** PRD V3.0 Section 4.3, SRS V1.0 Section 6.11  
**Target Completion:** 1-2 days  
**Estimated Effort:** 8-12 hours

---

## Executive Summary

This plan addresses the gaps between the current Front Desk Dashboard implementation and the PRD/SRS specifications. The improvements focus on:
1. Correcting dashboard widget metrics
2. Removing unauthorized menu sections
3. Adding missing workflow support features
4. Implementing BS/AD dual calendar display

---

## Phase 1: Critical Fixes (Priority P0)
**Estimated Time: 3-4 hours**

### 1.1 Fix Dashboard Widgets - "Today's Admissions"

**Current Issue:** Shows weekly inquiries instead of today's admissions  
**Required:** Show students enrolled today only

**Files to Modify:**
- `app/Http/Controllers/FrontDesk/frontdesk_stats.php`
- `public/assets/js/frontdesk.js`

**Implementation Steps:**

#### Step 1.1.1: Update API Query (frontdesk_stats.php)
```php
// Line ~95: REPLACE this query section
// OLD CODE:
(SELECT COUNT(*) FROM students WHERE tenant_id = :tid6 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as weekly_inquiries

// NEW CODE:
(SELECT COUNT(*) FROM students WHERE tenant_id = :tid6 AND DATE(created_at) = :today AND deleted_at IS NULL) as today_admissions
```

#### Step 1.1.2: Update JavaScript Rendering (frontdesk.js)
```javascript
// Line ~377: REPLACE the widget HTML
// OLD CODE:
<div class="sc">
    <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-phone-volume"></i></div><div class="bdg bg-y">Action needed</div></div>
    <div class="sc-val">${s.weekly_inquiries}</div>
    <div class="sc-lbl">Weekly New Inquiries</div>
    <div class="sc-delta">Follow-ups waiting...</div>
</div>

// NEW CODE:
<div class="sc" onclick="goNav('admissions', 'adm-all')" style="cursor:pointer;">
    <div class="sc-top"><div class="sc-ico ic-green"><i class="fa-solid fa-user-plus"></i></div><div class="bdg bg-g">Today</div></div>
    <div class="sc-val">${s.today_admissions || 0}</div>
    <div class="sc-lbl">Today's Admissions</div>
    <div class="sc-delta">New students enrolled</div>
</div>
```

**Testing Checklist:**
- [ ] Create a test student admission
- [ ] Verify widget shows count = 1
- [ ] Verify widget shows count = 0 for yesterday's admissions
- [ ] Verify click navigates to All Students page

---

### 1.2 Remove Unauthorized "Academic" Section from Sidebar

**Current Issue:** Front Desk sees "Academic" menu with Batches, Attendance  
**Required:** Front Desk has NO academic configuration access per PRD Section 3.3

**Files to Modify:**
- `resources/views/front-desk/sidebar.php`
- `public/assets/js/frontdesk.js`

#### Step 1.2.1: Update sidebar.php (Lines 77-103)
```php
// REMOVE this entire section from getFrontDeskMenu():
/* DELETE START
'academic' => [
    'title' => 'Academic',
    'items' => [
        [
            'label'       => 'Batches',
            ...
        ],
        [
            'label'       => 'Attendance',
            ...
        ]
    ]
],
DELETE END */
```

#### Step 1.2.2: Update frontdesk.js NAV array (Lines 59-64)
```javascript
// REMOVE this entire object from NAV array:
/* DELETE START
{ id: "academic", icon: "fa-graduation-cap", label: "Academic", sub: [
    { id: "batches", l: "Batches & Schedule", icon: "fa-users-line" },
    { id: "batch-status", l: "Batch Availability", icon: "fa-chart-pie" },
    { id: "att-mark", l: "Mark Attendance", icon: "fa-clipboard-check" },
    { id: "att-rep", l: "Attendance Report", icon: "fa-chart-line" }
], sec: "ACADEMIC" },
DELETE END */
```

#### Step 1.2.3: Remove Academic Route Handler (Lines 254-264)
```javascript
// REMOVE this entire if block:
/* DELETE START
// Academic Routes
if (activeNav.startsWith('academic')) {
    ...
    return;
}
DELETE END */
```

**Testing Checklist:**
- [ ] Login as Front Desk operator
- [ ] Verify "Academic" section does NOT appear in sidebar
- [ ] Verify direct URL access to /dash/front-desk/attendance-mark returns 403

---

## Phase 2: Missing Widgets (Priority P1)
**Estimated Time: 4-5 hours**

### 2.1 Add BS/AD Dual Date Display to Header

**PRD Requirement:** Header shows "Today's Date (BS / AD)"  
**Files:** `resources/views/front-desk/header.php`

#### Step 2.1.1: Add BS/AD Date Helper Function
Add to top of header.php after line 8:
```php
<?php
/**
 * Convert AD date to BS (Bikram Sambat)
 * Uses nepali/calendar composer package
 */
function getCurrentDateDual() {
    $adDate = date('Y-m-d');
    $adFormatted = date('F j, Y'); // January 1, 2025
    
    // Try to convert to BS using Nepali Calendar library
    try {
        $bsDate = \NepaliCalendar::AD2BS($adDate);
        $bsFormatted = $bsDate['year'] . ' ' . $bsDate['month_name'] . ' ' . $bsDate['day'];
    } catch (Exception $e) {
        // Fallback: manual conversion for common dates
        $bsFormatted = '2081 ' . $adFormatted; // Temporary fallback
    }
    
    return [
        'ad' => $adFormatted,
        'bs' => $bsFormatted,
        'day' => date('l') // Monday, Tuesday, etc.
    ];
}

$currentDate = getCurrentDateDual();
?>
```

#### Step 2.1.2: Add Date Display to Header HTML
After line 137 (institute-name div), add:
```html
<!-- Date Display -->
<div class="header-date">
    <div class="date-day"><?php echo $currentDate['day']; ?></div>
    <div class="date-bs"><?php echo $currentDate['bs']; ?></div>
    <div class="date-ad"><?php echo $currentDate['ad']; ?></div>
</div>
```

#### Step 2.1.3: Add CSS Styles
Add to header.php style section (around line 58):
```css
.header-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 16px;
    border-left: 1px solid var(--header-border);
    border-right: 1px solid var(--header-border);
    margin: 0 12px;
}
.date-day {
    font-size: 11px;
    font-weight: 700;
    color: var(--primary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.date-bs {
    font-size: 14px;
    font-weight: 600;
    color: var(--hdr-text-dark);
}
.date-ad {
    font-size: 11px;
    color: var(--hdr-text-light);
}
@media (max-width: 768px) {
    .header-date { display: none; } /* Hide on mobile */
}
```

**Testing Checklist:**
- [ ] BS date displays correctly (e.g., "2081 Falgun 15")
- [ ] AD date displays correctly (e.g., "February 27, 2025")
- [ ] Day name shows correctly
- [ ] Responsive: hides on mobile

---

### 2.2 Add "Pending Inquiries" Widget

**PRD Requirement:** "Pending Inquiries — follow-up due today count with quick-access list"  
**Files:** 
- `app/Http/Controllers/FrontDesk/frontdesk_stats.php`
- `public/assets/js/frontdesk.js`
- Database: Ensure `inquiries` table has `follow_up_date` column

#### Step 2.2.1: Check/Add Database Column
```sql
-- Check if column exists
SHOW COLUMNS FROM inquiries LIKE 'follow_up_date';

-- If not exists, run migration:
ALTER TABLE inquiries ADD COLUMN follow_up_date DATE NULL AFTER status;
ALTER TABLE inquiries ADD INDEX idx_follow_up_date (follow_up_date);
```

#### Step 2.2.2: Add API Query (frontdesk_stats.php)
After line 130 (attendance_marked query), add:
```php
// 13. Pending Inquiries - Follow-up due today
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as pending_inquiries_count,
        (SELECT COUNT(*) FROM inquiries 
         WHERE tenant_id = :tid AND follow_up_date = :today AND status IN ('new', 'contacted')
         LIMIT 5) as needs_followup_today
    FROM inquiries 
    WHERE tenant_id = :tid2 AND follow_up_date = :today2 AND status IN ('new', 'contacted')
");
$stmt->execute([
    'tid' => $tenantId, 'tid2' => $tenantId,
    'today' => $today, 'today2' => $today
]);
$inquiryData = $stmt->fetch();
$stats['pending_inquiries'] = (int) $inquiryData['pending_inquiries_count'];

// Get recent pending inquiries list
$stmt = $db->prepare("
    SELECT id, full_name, phone, course_interest, status, follow_up_date 
    FROM inquiries 
    WHERE tenant_id = :tid AND follow_up_date = :today AND status IN ('new', 'contacted')
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute(['tid' => $tenantId, 'today' => $today]);
$stats['pending_inquiries_list'] = $stmt->fetchAll();
```

#### Step 2.2.3: Add Widget to Dashboard (frontdesk.js)
Replace the "Weekly New Inquiries" widget (after fixing it to "Today's Admissions"), add new widget:
```javascript
// Add after Today's Admissions widget
<div class="sc" onclick="goNav('inquiries', 'inq-rem')" style="cursor:pointer;">
    <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-phone-volume"></i></div>
        ${s.pending_inquiries > 0 ? '<div class="bdg bg-r pulse">Action Needed</div>' : '<div class="bdg bg-g">All Clear</div>'}
    </div>
    <div class="sc-val">${s.pending_inquiries || 0}</div>
    <div class="sc-lbl">Pending Follow-ups</div>
    <div class="sc-delta">Due today</div>
</div>
```

#### Step 2.2.4: Add Quick-Access List Section
After the stat grid in dashboard HTML, add:
```javascript
// Add before Recent Transactions section
${s.pending_inquiries > 0 ? `
<div class="card mb">
    <div class="card-header" style="border:none; padding:0; margin-bottom:15px;">
        <div class="ct" style="margin:0;"><i class="fa-solid fa-bell"></i> Follow-ups Due Today</div>
        <button class="btn bs btn-sm" onclick="goNav('inquiries', 'inq-rem')">View All</button>
    </div>
    <div class="tw" style="border:none; box-shadow:none;">
        ${s.pending_inquiries_list.map(iq => `
            <div class="ai" style="align-items:center;">
                <div class="ad ic-amber">${iq.full_name.charAt(0)}</div>
                <div class="nm-row" style="flex:1;">
                    <div>
                        <div class="nm">${iq.full_name}</div>
                        <div class="sub-txt">${iq.course_interest || 'No course specified'}</div>
                    </div>
                </div>
                <a href="tel:${iq.phone}" class="btn btn-sm bs" style="margin-right:8px;">
                    <i class="fa-solid fa-phone"></i> Call
                </a>
                <button class="btn btn-sm bt" onclick="quickSMS('${iq.phone}', '${iq.full_name}')">
                    <i class="fa-solid fa-message"></i> SMS
                </button>
            </div>
        `).join('')}
    </div>
</div>
` : ''}
```

**Testing Checklist:**
- [ ] Create inquiry with follow_up_date = today
- [ ] Verify widget shows count = 1 with "Action Needed" badge
- [ ] Verify quick-access list shows inquiry with Call/SMS buttons
- [ ] Verify phone link works
- [ ] Verify "All Clear" shows when no pending inquiries

---

### 2.3 Add "Documents Pending Verification" Widget

**PRD Requirement:** "Documents Pending Verification — count with student names"  
**Files:** Same as above

#### Step 2.3.1: Check Document Verification Schema
```sql
-- Check if students table has document verification fields
SHOW COLUMNS FROM students LIKE '%document%';
SHOW COLUMNS FROM students LIKE '%verified%';

-- If not exists, document verification is likely tracked separately
-- Check for student_documents table
SHOW TABLES LIKE '%document%';
```

#### Step 2.3.2: Add API Query (frontdesk_stats.php)
```php
// 14. Documents Pending Verification
// Assumes: students table has document_verified boolean or separate tracking
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as pending_docs_count
    FROM students 
    WHERE tenant_id = :tid 
    AND status = 'active' 
    AND deleted_at IS NULL
    AND (document_verified = 0 OR document_verified IS NULL)
");
$stmt->execute(['tid' => $tenantId]);
$stats['pending_documents'] = (int) $stmt->fetchColumn();

// Get list of students with pending documents
$stmt = $db->prepare("
    SELECT id, full_name, roll_no, photo_url, created_at
    FROM students 
    WHERE tenant_id = :tid 
    AND status = 'active' 
    AND deleted_at IS NULL
    AND (document_verified = 0 OR document_verified IS NULL)
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute(['tid' => $tenantId]);
$stats['pending_documents_list'] = $stmt->fetchAll();
```

#### Step 2.3.3: Add Widget (frontdesk.js)
```javascript
<div class="sc" onclick="goNav('admissions', 'adm-doc')" style="cursor:pointer;">
    <div class="sc-top"><div class="sc-ico ic-navy"><i class="fa-solid fa-file-circle-exclamation"></i></div>
        ${s.pending_documents > 0 ? '<div class="bdg bg-r">Verify</div>' : '<div class="bdg bg-g">Complete</div>'}
    </div>
    <div class="sc-val">${s.pending_documents || 0}</div>
    <div class="sc-lbl">Docs Pending Verification</div>
    <div class="sc-delta">Students awaiting verification</div>
</div>
```

---

## Phase 3: Operator-Specific Features (Priority P2)
**Estimated Time: 2-3 hours**

### 3.1 Show "My Collection" Instead of Total Revenue

**PRD Requirement:** "Today's Fee Collection — NPR total collected by THIS operator"  
**Current Issue:** Shows total institute collection

#### Step 3.1.1: Modify API Query (frontdesk_stats.php)
```php
// Line ~106: Change today's revenue query
// OLD:
$stmt = $db->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM fee_records WHERE tenant_id = :tid AND (paid_date = :today OR (paid_date IS NULL AND created_at >= :today_ts AND amount_paid > 0))");

// NEW - Filter by cashier_user_id = current user:
$stmt = $db->prepare("
    SELECT COALESCE(SUM(amount_paid), 0) 
    FROM fee_records 
    WHERE tenant_id = :tid 
    AND cashier_user_id = :uid  /* ADD THIS FILTER */
    AND (paid_date = :today OR (paid_date IS NULL AND created_at >= :today_ts AND amount_paid > 0))
");
$stmt->execute([
    'tid' => $tenantId, 
    'uid' => $userId,  // ADD THIS
    'today' => $today, 
    'today_ts' => $today
]);

// Also update transactions query to show only my transactions
// Line ~144: Add cashier filter
```

#### Step 3.1.2: Update Widget Label (frontdesk.js)
```javascript
<div class="sc-lbl">My Today's Collection</div>
<div class="sc-delta">Collected by you: ${s.today_transactions.length} transactions</div>
```

---

## Phase 4: Testing & Validation
**Estimated Time: 1-2 hours**

### 4.1 Functional Testing

| Test Case | Expected Result | Status |
|-----------|-----------------|--------|
| Login as Front Desk | No "Academic" menu visible | ⬜ |
| Admit student today | Today's Admissions = 1 | ⬜ |
| Create inquiry with follow-up today | Pending Follow-ups = 1 | ⬜ |
| Header shows BS date | BS date visible (e.g., 2081 Falgun) | ⬜ |
| Collect fee payment | My Today's Collection shows amount | ⬜ |
| Click on widget | Navigates to correct page | ⬜ |
| Direct URL to /attendance-mark | Returns 403 Forbidden | ⬜ |

### 4.2 Responsive Testing

| Device | Header Date | Widgets Layout | Quick Actions |
|--------|-------------|----------------|---------------|
| Desktop (1920px) | Visible | 4 columns | All visible |
| Tablet (768px) | Visible | 2 columns | All visible |
| Mobile (375px) | Hidden | 1 column | Scrollable |

### 4.3 Performance Testing

| Metric | Target | Test Method |
|--------|--------|-------------|
| Dashboard load time | < 2 seconds | Stopwatch from login |
| API response time | < 500ms | Browser Network tab |
| Widget render | < 100ms | React Profiler equivalent |

---

## Rollback Plan

If critical issues occur:

1. **Revert Code Changes:**
   ```bash
   git checkout -- resources/views/front-desk/sidebar.php
   git checkout -- public/assets/js/frontdesk.js
   git checkout -- app/Http/Controllers/FrontDesk/frontdesk_stats.php
   git checkout -- resources/views/front-desk/header.php
   ```

2. **Database Rollback (if needed):**
   ```sql
   -- If migration was run
   ALTER TABLE inquiries DROP COLUMN follow_up_date;
   ```

3. **Clear Cache:**
   ```bash
   # Clear file-based cache
   rm -rf /tmp/fd_cache_*
   
   # Clear any Redis cache if implemented
   ```

---

## Success Criteria

The implementation is successful when:

- [ ] **All P0 items completed** - Today's Admissions widget fixed, Academic section removed
- [ ] **All P1 items completed** - BS/AD date visible, Pending Inquiries widget added
- [ ] **Dashboard matches PRD spec** - All 6 widgets present per Section 4.3
- [ ] **No console errors** - JavaScript runs without errors
- [ ] **Responsive verified** - Works on mobile, tablet, desktop
- [ ] **Tested by user** - Front Desk operator can perform daily workflow

---

## Appendix A: Database Schema Checks

### Required Table Structure

```sql
-- inquiries table must have:
DESCRIBE inquiries;
-- Expected: id, tenant_id, full_name, phone, email, course_interest, 
--           status, follow_up_date, created_at, updated_at

-- students table must have:
DESCRIBE students;
-- Expected: id, tenant_id, user_id, roll_no, full_name, ...
--           document_verified (or similar), created_at

-- fee_records table must have:
DESCRIBE fee_records;
-- Expected: id, tenant_id, student_id, cashier_user_id, amount_paid, 
--           paid_date, payment_mode, receipt_no
```

---

## Appendix B: PRD/SRS Reference Mapping

| Feature | PRD Section | SRS Section | Implementation |
|---------|-------------|-------------|----------------|
| Today's Admissions widget | 4.3 | 6.11 | Phase 1.1 |
| Pending Inquiries widget | 4.3 | 6.11 | Phase 2.2 |
| Documents Pending Verification | 4.3 | 6.11 | Phase 2.3 |
| Library Books Overdue | 4.3 | 6.11 | Future Phase |
| BS/AD Date Header | 4.3 | 2.4 | Phase 2.1 |
| Remove Academic Access | 3.3 | 5.3 | Phase 1.2 |
| Operator-specific Collection | 4.3 | 6.4 | Phase 3.1 |

---

**End of Implementation Plan**
