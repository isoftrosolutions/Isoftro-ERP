# Front Desk Portal - Implementation Plan for Placeholders

This document outlines all placeholders and incomplete implementations found in the Front Desk Portal, with an implementation plan to address each one.

---

## Summary of Placeholders Found

| Category | Count | Priority |
|----------|-------|----------|
| Backend Stubs (V3.1/V3.2) | 6 | High |
| Form Input Placeholders | 100+ | Low (legitimate UI) |
| Fee Report Export | 1 | Medium |
| Total Issues | 7 | - |

---

## 1. Backend Placeholders (High Priority)

### 1.1 Library Module - STUB (V3.1)

**Location:** [`app/Http/Controllers/FrontDesk/library.php`](app/Http/Controllers/FrontDesk/library.php)

**Current Status:**
```php
/**
 * Library Controller — Catalog, Issue/Return, Overdue Tracking, Stock Report
 * STUB: Returns placeholder data + graceful "coming soon" shape.
 * Full implementation in V3.1.
 */
```

**Missing Features:**
- Book catalog management
- Issue/Return tracking
- Overdue tracking
- Stock reports

**Implementation Plan:**

| Step | Task | Estimated Effort |
|------|------|------------------|
| 1.1.1 | Create `library_books` table migration | 2 hours |
| 1.1.2 | Create `library_issues` table migration | 2 hours |
| 1.1.3 | Implement book catalog CRUD | 4 hours |
| 1.1.4 | Implement issue/return logic | 4 hours |
| 1.1.5 | Add overdue calculation & notifications | 3 hours |
| 1.1.6 | Implement stock reports | 3 hours |
| 1.1.7 | Add frontend JavaScript integration | 4 hours |

**Total Estimate:** 22 hours

---

### 1.2 Communications Module - STUB (V3.1)

**Location:** [`app/Http/Controllers/FrontDesk/communications.php`](app/Http/Controllers/FrontDesk/communications.php)

**Current Status:**
```php
/**
 * Communications Controller — SMS Broadcast, Email Campaigns, Templates, Message Log
 * STUB: Returns placeholder data + graceful "coming soon" shape.
 * Full SMS gateway integration in V3.1.
 */
```

**Missing Features:**
- SMS gateway integration (eSewa, SMSNepal, etc.)
- Email broadcast campaigns
- Message templates
- Message log/history

**Implementation Plan:**

| Step | Task | Estimated Effort |
|------|------|------------------|
| 1.2.1 | Create SMS gateway integration service | 8 hours |
| 1.2.2 | Implement email campaign service | 6 hours |
| 1.2.3 | Create message templates system | 4 hours |
| 1.2.4 | Implement message history/logging | 3 hours |
| 1.2.5 | Add frontend campaign builder UI | 8 hours |

**Total Estimate:** 29 hours

---

### 1.3 LMS Online Classes - STUB (V3.2)

**Location:** [`app/Http/Controllers/FrontDesk/lms.php`](app/Http/Controllers/FrontDesk/lms.php:302-318)

**Current Status:**
```php
// ========== ONLINE CLASSES (STUB for future) ==========

case 'online_classes':
    // STUB: Online classes functionality - to be implemented with live streaming integration
    echo json_encode([
        'success' => true,
        'message' => 'Online classes module coming in V3.2',
        'data' => [],
        'meta' => ['total' => 0]
    ]);
    break;
    
case 'schedule_class':
    // STUB: Schedule online class
    echo json_encode([
        'success' => true,
        'message' => 'Online class scheduling coming in V3.2'
    ]);
    break;
```

**Missing Features:**
- Live streaming integration (Zoom/Google Meet)
- Online class scheduling
- Attendance tracking for online classes
- Recording management

**Implementation Plan:**

| Step | Task | Estimated Effort |
|------|------|------------------|
| 1.3.1 | Create Zoom/Google Meet API integration | 12 hours |
| 1.3.2 | Implement class scheduling with video | 8 hours |
| 1.3.3 | Add online attendance tracking | 4 hours |
| 1.3.4 | Implement session recording storage | 6 hours |

**Total Estimate:** 30 hours

---

### 1.4 Fee Reports Export - Not Implemented

**Location:** [`app/Http/Controllers/FrontDesk/FeeReports.php`](app/Http/Controllers/FrontDesk/FeeReports.php:231-232)

**Current Status:**
```php
} else {
    throw new Exception("Export for report type '$reportType' is not implemented yet.");
}
```

**Missing Export Formats:**
- PDF export
- Excel export
- Print-friendly view

**Implementation Plan:**

| Step | Task | Estimated Effort |
|------|------|------------------|
| 1.4.1 | Implement PDF export using Dompdf | 4 hours |
| 1.4.2 | Implement Excel export using PhpSpreadsheet | 4 hours |
| 1.4.3 | Create print-friendly CSS | 2 hours |

**Total Estimate:** 10 hours

---

## 2. Frontend Placeholders (Low Priority)

The following are **legitimate UI placeholders** for form inputs and search fields. These are standard HTML `placeholder` attributes and do NOT need to be fixed.

### Examples Found:
- `"Search by name, roll, email or phone…"` - fd-students.js
- `"Enter email subject..."` - fd-students.js
- `"e.g. Room 101"` - fd-timetable.js
- `"Search materials..."` - fd-study-materials.js

**Action:** No action needed - these are intentional UX elements.

---

## 3. Implementation Roadmap

### Phase 1: Quick Wins (Week 1-2)
1. ✅ Fee Reports Export - **10 hours**

### Phase 2: Core Modules (Week 3-6)
2. Library Module - **22 hours**
3. Communications Module - **29 hours**

### Phase 3: Advanced Features (Week 7-9)
4. LMS Online Classes - **30 hours**

---

## 4. Dependencies

Before implementing the placeholders, ensure these services are available:

| Service | Purpose | Status |
|---------|---------|--------|
| SMS Gateway API | Communications | Required for Phase 2 |
| Video Platform (Zoom/Google Meet) | Online Classes | Required for Phase 3 |
| Dompdf/PHPExcel libraries | Export features | Already in composer.json |
| Database migrations | New tables | To be created |

---

## 5. Testing Requirements

Each implemented feature should include:

1. **Unit Tests** - Service layer methods
2. **Integration Tests** - API endpoints
3. **UI Tests** - Frontend functionality
4. **Security Tests** - Input validation, authorization

---

## 6. Estimated Total Development Time

| Module | Hours |
|--------|-------|
| Fee Reports Export | 10 |
| Library Module | 22 |
| Communications Module | 29 |
| LMS Online Classes | 30 |
| **Total** | **91 hours** |

---

**Document Created:** 2026-03-09  
**Version:** 1.0
