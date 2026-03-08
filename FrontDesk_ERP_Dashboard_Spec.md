# HamroLabs ERP — Front Desk Dashboard
## Comprehensive UI/UX Specification & Developer Prompt

> **Document Purpose:** Full production-ready specification for building the Front Desk Dashboard of an educational institution ERP system. This document covers layout, color system, typography, component design, data models, and database schema for every visible section.

---

## Table of Contents

1. [Global Design System](#1-global-design-system)
2. [Top Navigation Bar](#2-top-navigation-bar)
3. [Left Sidebar Navigation](#3-left-sidebar-navigation)
4. [Main Dashboard Header & Search Bar](#4-main-dashboard-header--search-bar)
5. [Alert Banner](#5-alert-banner)
6. [KPI Summary Cards (Row 1)](#6-kpi-summary-cards-row-1)
7. [KPI Summary Cards (Row 2 — Attendance & Admissions)](#7-kpi-summary-cards-row-2--attendance--admissions)
8. [Status Chip Row (Inquiries, Leave, Library, Receipt)](#8-status-chip-row)
9. [Quick Actions Panel](#9-quick-actions-panel)
10. [Today's Fee Transactions Table](#10-todays-fee-transactions-table)
11. [Today's Fee Summary Panel](#11-todays-fee-summary-panel)
12. [Announcements Panel](#12-announcements-panel)
13. [Attendance Snapshot Panel](#13-attendance-snapshot-panel)
14. [Today's Inquiries Panel](#14-todays-inquiries-panel)
15. [Pending Leave Requests Panel](#15-pending-leave-requests-panel)
16. [Today's Timetable Panel](#16-todays-timetable-panel)
17. [Activity Log Panel](#17-activity-log-panel)
18. [Library Desk Panel](#18-library-desk-panel)
19. [Database Schema](#19-database-schema)
20. [API Endpoints](#20-api-endpoints)
21. [Technology Stack](#21-technology-stack)

---

## 1. Global Design System

### 1.1 Color Palette

```
Primary Brand:        #1A3A5C   (deep navy — used for sidebar, header accents)
Primary Accent:       #00A86B   (emerald green — CTAs, positive trends, primary buttons)
Secondary Accent:     #F5A623   (amber — warnings, pending states, badge counts)
Danger / Overdue:     #E84040   (red — dues, overdue alerts, error states)
Info / Neutral:       #3B82F6   (blue — info banners, links, neutral badges)
Background Page:      #F4F6FA   (light grey — main content area background)
Background Card:      #FFFFFF   (pure white — all card/panel surfaces)
Background Sidebar:   #1A2B45   (dark navy — left sidebar)
Sidebar Text Active:  #FFFFFF
Sidebar Text Idle:    #A8BCCF
Border / Divider:     #E5E9F0
Text Primary:         #1A1F36   (near-black — headings, table content)
Text Secondary:       #6B7A99   (muted grey — labels, metadata, timestamps)
Text Positive:        #00A86B   (green amounts, positive delta indicators)
Text Danger:          #E84040   (negative/overdue amounts)
Badge Background:     #FFF3E0   (light amber — pending badge fill)
Badge Text:           #E65100   (dark orange — pending badge text)
Chip Cash:            #E8F5E9 / #2E7D32
Chip eSewa:           #E3F2FD / #1565C0
Chip Khalti:          #F3E5F5 / #6A1B9A
Chip Bank Transfer:   #FFF8E1 / #F57F17
```

### 1.2 Typography

```
Font Family Primary:   "Inter", sans-serif (all UI text)
Font Family Mono:      "JetBrains Mono", monospace (receipt IDs, codes)

Scale:
  Page Title:          20px / 700 weight / #1A1F36
  Section Heading:     14px / 600 weight / #1A1F36
  Card Value Large:    28px / 700 weight / #1A1F36
  Card Label:          12px / 400 weight / #6B7A99
  Table Header:        11px / 600 weight / #6B7A99 / UPPERCASE / letter-spacing: 0.5px
  Table Row Text:      13px / 400 weight / #1A1F36
  Badge / Chip:        11px / 600 weight
  Timestamp:           11px / 400 weight / #6B7A99
  Nav Item:            13px / 500 weight
  Nav Section Label:   10px / 700 weight / #A8BCCF / UPPERCASE / letter-spacing: 1.2px
  Amount Currency:     16px / 700 weight (large figures in KPI cards)
  Delta Indicator:     11px / 600 weight / green or red
```

### 1.3 Spacing System (8px base grid)

```
xs:   4px
sm:   8px
md:   16px
lg:   24px
xl:   32px
2xl:  48px
```

### 1.4 Border Radius

```
Card/Panel:    8px
Button:        6px
Chip/Badge:    999px (pill)
Avatar:        50% (circle)
Input:         6px
```

### 1.5 Shadows

```
Card:          0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04)
Dropdown:      0 4px 16px rgba(0,0,0,0.12)
Modal:         0 8px 32px rgba(0,0,0,0.18)
```

### 1.6 Grid Layout

```
Overall Layout:
  Left Sidebar:        240px fixed width
  Top Navbar:          Full width, 56px height, fixed position (z-index: 100)
  Main Content Area:   calc(100vw - 240px), starts below navbar
  Content Inner Padding: 24px all sides

Dashboard Grid (main content):
  Uses CSS Grid with 12-column layout
  Gap between panels: 16px
  Row heights: auto (content-driven)

Panel Column Assignments:
  KPI Cards Row 1:       6 cols each (2 cards side-by-side)
  KPI Cards Row 2:       6 cols each (2 cards side-by-side)
  Status Chip Row:       12 cols (full width, flex row)
  Quick Actions:         12 cols (full width, flex row)
  Fee Table + Summary:   8 cols + 4 cols
  Announcements:         4 cols (alongside summary)
  Attendance + Inquiries + Leave: 4 cols each
  Timetable + Activity + Library: 4 cols each
```

---

## 2. Top Navigation Bar

### 2.1 Layout

```
Height:        56px
Background:    #FFFFFF
Border-bottom: 1px solid #E5E9F0
Position:      fixed top, full width
Z-index:       100
Padding:       0 24px
Display:       flex, align-items: center, justify-content: space-between
```

### 2.2 Left Section — Logo & Branding

```
Hamburger menu icon (☰):
  Size: 20px, color: #6B7A99
  Margin-right: 16px
  On click: toggle sidebar collapse

Logo Block:
  Background: Linear gradient (135deg, #00C47D → #007A4D)  [green gradient rectangle]
  Width: 32px, Height: 32px, border-radius: 4px
  Inner text: "HL" in white, 14px, 700 weight (monogram)
  
Company Name Block (right of logo):
  Line 1: "HamroLabs ERP"  —  14px, 700, #1A1F36
  Line 2: "Nepal Cyber-Firm · Brightfuture"  —  10px, 400, #6B7A99

Module Breadcrumb (center-left, after separator):
  Green pill badge: background #00A86B, text "FRONT DESK", 
                    10px, 700, white, padding 3px 10px, border-radius 999px
```

### 2.3 Right Section — Utility Icons

```
Left to right order:

1. Clock Display:
   "06:54 AM"  —  13px, 500, #1A1F36
   "Sun, Mar 08, 2026"  — 11px, 400, #6B7A99
   Stacked vertically, right-aligned

2. Notification Bell Icon:
   Icon: bell outline, 20px, #6B7A99
   Red badge with count: 12px circle, #E84040, white text "2"
   Positioned top-right of icon

3. Settings / Gear Icon:
   20px, #6B7A99, clickable

4. User Avatar:
   Circle, 32px diameter
   Background: #1A3A5C (or initials-based color)
   Initials: "SD" — 12px, 700, white
   
5. User Info Block:
   Line 1: "Sunita Devi"  —  13px, 600, #1A1F36
   Line 2: "Front Desk · Nepal Cyber Firm"  —  11px, 400, #6B7A99
   Stacked vertically
```

### 2.4 Database Connection

```sql
-- Logged-in user session
SELECT u.id, u.full_name, u.avatar_url, u.role, 
       b.name AS branch_name, o.name AS org_name
FROM users u
JOIN branches b ON u.branch_id = b.id
JOIN organizations o ON b.org_id = o.id
WHERE u.id = :session_user_id AND u.is_active = true;
```

---

## 3. Left Sidebar Navigation

### 3.1 Layout

```
Width:           240px (collapsible to 60px)
Height:          100vh, fixed position
Background:      #1A2B45
Overflow-y:      auto
Scrollbar:       hidden (custom thin scrollbar on hover)
Padding-top:     72px (below fixed navbar)
```

### 3.2 Structure — Navigation Sections

The sidebar is divided into labeled groups. Each group has a section label in uppercase small caps, followed by nav items.

```
SECTION: OVERVIEW
  ├── Dashboard              [grid-2x2 icon]   — ACTIVE (highlighted)
  └── Today's Attendance     [calendar icon]   — badge: "18" (orange pill)

SECTION: ADMISSIONS
  ├── Student Lookup         [search icon]
  ├── New Admission          [user-plus icon]
  └── Inquiries              [message icon]    — badge: "7" (orange pill)

SECTION: FEE & FINANCE
  ├── Fee Collection         [currency icon]
  ├── Transactions           [list icon]
  ├── Pending Dues           [clock icon]      — badge: "18" (red pill)
  └── Receipts               [receipt icon]

SECTION: OPERATIONS
  ├── Leave Requests         [calendar-x icon] — badge: "5" (green pill)
  ├── Library Desk           [book icon]
  └── Today's Timetable      [clock icon]
```

### 3.3 Nav Item Styling

```
Idle State:
  Background:    transparent
  Text:          #A8BCCF, 13px, 500
  Icon:          #A8BCCF, 16px
  Padding:       10px 20px
  Border-radius: 6px (applied to inner content)

Active State:
  Background:    rgba(255,255,255,0.08)
  Left border:   3px solid #00A86B
  Text:          #FFFFFF, 600
  Icon:          #00A86B

Hover State:
  Background:    rgba(255,255,255,0.05)
  Text:          #FFFFFF

Badge Pill:
  Float right
  Red:    background #E84040, text white — critical/dues
  Orange: background #F5A623, text white — inquiries
  Green:  background #00A86B, text white — leave
  Font:   10px, 700
  Min-width: 18px, height: 18px, padding 0 5px

Section Label:
  Color:         #A8BCCF
  Font:          10px, 700, uppercase, letter-spacing 1.2px
  Padding:       16px 20px 6px
  Margin-top:    8px
```

### 3.4 Footer — Current User (bottom of sidebar)

```
Position: absolute bottom 0, full width
Background: rgba(0,0,0,0.2)
Padding: 12px 16px
Display: flex, align-items: center, gap 10px

Avatar:
  Circle 32px, initials "SD", background #2A4A6B

User Info:
  "Sunita Devi"     — 12px, 600, white
  "Front Desk · Nepal Cyber Firm"  — 10px, 400, #A8BCCF

Green dot indicator (online status): 8px, #00A86B, absolute bottom-left of avatar
```

---

## 4. Main Dashboard Header & Search Bar

### 4.1 Layout

```
Background: #F4F6FA
Padding:    24px 24px 16px
```

### 4.2 Page Title Block

```
"Front Desk Dashboard"
  Font: 20px, 700, #1A1F36
  Margin-bottom: 2px

Subtitle line:
  "Tuesday, March 03, 2026 · Academic Year 2026-2027 · All data for Nepal Cyber Firm"
  Font: 12px, 400, #6B7A99
```

### 4.3 Search Bar

```
Position: Below title, full width of content area
Background: #FFFFFF
Border: 1px solid #E5E9F0
Border-radius: 6px
Height: 40px
Padding: 0 16px
Display: flex, align-items: center, gap: 8px

Left icon: search (magnifying glass), 16px, #A8BCCF
Placeholder text: "Search student, roll no, receipt..."  — 13px, #A8BCCF
Right side: "Record Payment" button
  Background: #00A86B, color white, 13px, 600
  Padding: 8px 20px, border-radius: 6px
  Left icon: circle-plus, 14px, white
```

### 4.4 Database Connection

```sql
-- Global search (debounced, 300ms)
SELECT 
  'student' AS type, s.id, s.full_name AS label, s.roll_no AS sublabel
FROM students s WHERE s.full_name ILIKE :q OR s.roll_no ILIKE :q
UNION ALL
SELECT 'receipt', r.id, r.receipt_no, r.amount::text
FROM fee_receipts r WHERE r.receipt_no ILIKE :q
LIMIT 10;
```

---

## 5. Alert Banner

### 5.1 Appearance

```
Background:     #FFF8E1  (light amber)
Border:         1px solid #F5A623
Border-radius:  8px
Padding:        12px 16px
Margin:         0 0 16px 0
Display:        flex, align-items: center, justify-content: space-between

Left section:
  Warning icon (⚠️): 16px, #F5A623
  Text: "18 students have fee dues overdue by more than 7 days. 
         Reminders sent via SMS yesterday."
  "18 students" portion: 700 weight, #E65100
  Remaining text: 13px, 400, #92400E

Right section:
  "View All" link button: 12px, 500, #1565C0, underline on hover
  "Dismiss" link button:  12px, 400, #6B7A99, margin-left 12px
```

### 5.2 Database Connection

```sql
SELECT COUNT(*) AS overdue_count
FROM fee_dues
WHERE due_date < CURRENT_DATE - INTERVAL '7 days'
  AND status = 'unpaid'
  AND academic_year_id = :current_ay;

-- Check SMS sent
SELECT COUNT(*) FROM sms_logs
WHERE sent_at::date = CURRENT_DATE - 1
  AND purpose = 'fee_reminder';
```

---

## 6. KPI Summary Cards (Row 1)

Two large cards side-by-side occupying full width (6 cols each).

### 6.1 Card: Today's Collection

```
Card Container:
  Background: #FFFFFF
  Border-radius: 8px
  Border: 1px solid #E5E9F0
  Padding: 20px 24px
  Box-shadow: card shadow

Top Row:
  Left:  Green wallet/cash icon, 20px, inside a light-green 36px circle (#E8F5E9)
  Right: Delta chip — "↑ 12%"
         Background: #E8F5E9
         Text: #00A86B, 11px, 600
         Border-radius: 999px, padding: 3px 10px

Main Value:
  "Rs 1,27,500"
  Font: 28px, 700, #1A1F36
  Margin-top: 12px

Card Label:
  "Today's Collection"
  Font: 12px, 400, #6B7A99
  Margin-bottom: 8px

Sub-label:
  "9 transactions · Cash, eSewa, Bank"
  Font: 11px, 400, #6B7A99
```

### 6.2 Card: Pending Dues

```
Identical structure to Today's Collection card, with:
  Icon: orange clock icon, inside #FFF3E0 background circle
  Delta chip: "↓ 1" — background #FFEBEE, text #E84040
  Main Value: "Rs 3,86,000"  — color #E84040 (red, because it's dues)
  Label: "Pending Dues"
  Sub-label: "34 students · Due this week"
```

### 6.3 Database Connection

```sql
-- Today's collection
SELECT 
  SUM(amount) AS total_collected,
  COUNT(*) AS transaction_count,
  STRING_AGG(DISTINCT payment_method, ', ') AS methods
FROM fee_receipts
WHERE receipt_date = CURRENT_DATE
  AND status = 'completed';

-- Pending dues
SELECT 
  SUM(fd.amount - COALESCE(fd.paid_amount, 0)) AS pending_amount,
  COUNT(DISTINCT fd.student_id) AS student_count
FROM fee_dues fd
WHERE fd.status IN ('unpaid', 'partial')
  AND fd.due_date BETWEEN CURRENT_DATE AND CURRENT_DATE + 7;
```

---

## 7. KPI Summary Cards (Row 2 — Attendance & Admissions)

Two cards, each 6 columns wide, below Row 1.

### 7.1 Card: Attendance Today

```
Icon: people/group icon, inside #E3F2FD circle, color #1565C0
Delta chip: "↑ 5%", background #E8F5E9, text #00A86B

Main Value: "87%"
  Font: 28px, 700, #1A1F36

Label: "Attendance Today"
Sub-label: "142/163 present across all batches"
  Font: 11px, #6B7A99
```

### 7.2 Card: New Admissions Today

```
Icon: user-plus icon, inside #F3E5F5 circle, color #6A1B9A
Delta chip: "↑ 2", background #E8F5E9, text #00A86B

Main Value: "4"
  Font: 28px, 700, #1A1F36

Label: "New Admissions Today"
Sub-label: "2 fully registered · 2 pending docs"
```

### 7.3 Database Connection

```sql
-- Attendance
SELECT 
  COUNT(*) FILTER (WHERE status = 'present') AS present,
  COUNT(*) AS total,
  ROUND(COUNT(*) FILTER (WHERE status = 'present') * 100.0 / COUNT(*), 0) AS pct
FROM attendance
WHERE date = CURRENT_DATE;

-- Admissions today
SELECT 
  COUNT(*) AS total_today,
  COUNT(*) FILTER (WHERE doc_status = 'complete') AS fully_registered,
  COUNT(*) FILTER (WHERE doc_status = 'pending') AS pending_docs
FROM student_admissions
WHERE admission_date = CURRENT_DATE;
```

---

## 8. Status Chip Row

A horizontal flex row of 4 compact status indicators spanning full width.

### 8.1 Layout

```
Display: flex, gap: 12px, flex-wrap: wrap
Margin: 16px 0
```

### 8.2 Chip: Open Inquiries

```
Background: #FFFFFF
Border: 1px solid #E5E9F0
Border-radius: 8px
Padding: 10px 16px
Display: flex, align-items: center, gap 10px

Icon: message-circle, 18px, #F5A623, inside #FFF8E1 24px circle
Number: "6" — 16px, 700, #1A1F36
Label: "Open" — 11px, 400, #6B7A99
Status tag: "New" — pill, background #E8F5E9, text #00A86B, 10px 600
```

### 8.3 Chip: Leave Requests

```
Icon: calendar-x, inside #FFF3E0 circle, color #F5A623
Number: "5"
Label: "Leave Requests"
Status: "Pending" — pill, background #FFF3E0, text #E65100
```

### 8.4 Chip: Library Issues Today

```
Icon: book-open, inside #E3F2FD circle, color #1565C0
Number: "11"
Label: "Library Issues Today"
No status tag
```

### 8.5 Chip: Last Receipt

```
Icon: receipt, inside #F3E5F5 circle, color #6A1B9A
Text: "RCP-000009" — 13px, 700, monospace, #1A1F36
Label: "Last Receipt No." — 11px, #6B7A99
```

### 8.6 Database Connection

```sql
SELECT
  (SELECT COUNT(*) FROM inquiries WHERE status = 'open') AS open_inquiries,
  (SELECT COUNT(*) FROM leave_requests WHERE status = 'pending') AS pending_leave,
  (SELECT COUNT(*) FROM library_issues WHERE issue_date = CURRENT_DATE) AS library_today,
  (SELECT receipt_no FROM fee_receipts ORDER BY created_at DESC LIMIT 1) AS last_receipt;
```

---

## 9. Quick Actions Panel

### 9.1 Layout

```
Background: #FFFFFF
Border: 1px solid #E5E9F0
Border-radius: 8px
Padding: 16px 24px
Margin-bottom: 16px

Section Label: "⚡ QUICK ACTIONS"  — 11px, 700, #6B7A99, uppercase
Actions Row: flex, gap 8px, justify-content: space-around, margin-top: 12px
```

### 9.2 Individual Action Button

Each action is a rounded card-like button with icon + label:

```
Width: ~100px (equal flex)
Padding: 12px 8px
Border-radius: 8px
Border: 1px solid #E5E9F0
Background: #FAFAFA on idle, #F0FFF8 on hover
Cursor: pointer
Text-align: center

Icon Container: 
  40px × 40px circle
  Centered above label
  Each action has unique color:
    Collect Fee:    #E8F5E9 bg / #00A86B icon
    New Admission:  #E3F2FD bg / #1565C0 icon
    Mark Attendance:#FFF3E0 bg / #F5A623 icon
    Add Inquiry:    #F3E5F5 bg / #6A1B9A icon
    Print Receipt:  #FFF8E1 bg / #E65100 icon

Label Line 1: Action Name — 12px, 600, #1A1F36
Label Line 2: Sub-label   — 10px, 400, #6B7A99
  e.g., "Record payment", "Today's class", "Log new inquiry"
```

---

## 10. Today's Fee Transactions Table

### 10.1 Layout

```
Grid position: Spans 8 of 12 columns (left side)
Background: #FFFFFF
Border: 1px solid #E5E9F0
Border-radius: 8px
Overflow: hidden

Header Row:
  Background: #FAFAFA
  Border-bottom: 1px solid #E5E9F0
  Padding: 16px 20px
  Display: flex, justify-content: space-between, align-items: center

  Left:
    Icon: receipt-list 16px, #00A86B
    Title: "Today's Fee Transactions" — 13px, 600, #1A1F36
    Sub: "Tuesday, March 03, 2026 · 9 transactions" — 11px, #6B7A99

  Right:
    Filter input: search icon + placeholder "Filter..."
                  Background #F4F6FA, border 1px #E5E9F0, 
                  height 32px, 12px text
    Export button: cloud-download icon + "Export" label
                   Background transparent, border 1px #E5E9F0,
                   12px, 500, #6B7A99
```

### 10.2 Table Column Headers

```
Columns (left to right):
  RECEIPT NO.  | STUDENT    | FEE ITEM    | AMOUNT | METHOD | TIME

Header row:
  Background:      #FAFAFA
  Border-bottom:   1px solid #E5E9F0
  Font:            11px, 600, #6B7A99, uppercase
  Padding:         10px 12px per cell
  Letter-spacing:  0.5px
```

### 10.3 Table Row Data

```
Row Height: 56px (accommodates 2-line student info)
Alternate rows: odd #FFFFFF, even #FAFBFC
Hover: #F0FFF8 (subtle green tint)
Padding per cell: 10px 12px
Border-bottom: 1px solid #F0F2F5

RECEIPT NO. column:
  Text: "RCP-000009" — 12px, monospace (#JetBrains Mono), 500, #1A1F36
  
STUDENT column:
  Avatar circle (32px):
    Background: unique color per first letter (A=#E3F2FD, B=#E8F5E9, etc.)
    Initials: 11px, 700, contrasting color
  Name: "Ramesh Sharma" — 13px, 500, #1A1F36
  Student ID + Batch below name: "STD-0046 / BCA-I" — 10px, #6B7A99

FEE ITEM column:
  Text: "Tuition Fee — Inst. 1" — 12px, 400, #1A1F36
  (wrapped to 2 lines if long)

AMOUNT column:
  "Rs 2,000" — 13px, 700, #1A1F36 (regular amounts)
  Color changes to #E84040 for dues/negative entries

METHOD column:
  Pill badge:
    Cash:          background #E8F5E9, text #2E7D32
    eSewa:         background #E3F2FD, text #1565C0
    Khalti:        background #F3E5F5, text #6A1B9A
    Bank Transfer: background #FFF8E1, text #F57F17
  Font: 10px, 600
  Padding: 3px 10px, border-radius: 999px

TIME column:
  "10:34 AM" — 11px, #6B7A99

Pagination row (bottom):
  Page size indicator: "Showing 9 of 9 transactions"
  Page buttons: ← 1 → 
  Page number active: background #00A86B, text white, 24px circle
  Arrows: #6B7A99 on idle, #1A1F36 on hover
```

### 10.4 Sample Data Rows

| Receipt | Student | Fee Item | Amount | Method | Time |
|---------|---------|----------|--------|--------|------|
| RCP-000009 | Ramesh Sharma / STD-0046 / BCA-I | Tuition Fee – Inst. 1 | Rs 2,000 | Cash | 10:34 AM |
| RCP-000008 | Priyanka Shah / STD-0034 / BCA-I | Admission Fee – Inst. 1 | Rs 10,000 | eSewa | 10:15 AM |
| RCP-000007 | Anita Singh / STD-0039 / BCA-III | Admission Fee – Inst. 1 | Rs 10,000 | Cash | 12:11 AM |
| RCP-000006 | Bikash Kumar / STD-0046 / RCP-P | Exam Fee – Inst. 1 | Rs 2,000 | Khalti | 12:10 PM |
| RCP-000005 | Manisha Patel / STD-0044 / BCA-III | Admission Fee – Inst. 1 | Rs 10,000 | Cash | 12:11 PM |
| RCP-000004 | Rajesh KC / STD-0048 / BCA-I | Exam Fee Level 4 | Rs 2,000 | Cash | 12:10 PM |
| RCP-000003 | Sita Gurung / STD-0037 / BCA-I | Admission Fee – Inst. 1 | Rs 10,000 | Bank Transfer | 12:01 PM |
| RCP-000002 | Nisha Thapa / STD-0042 / BCA-II | Admission Fee – Inst. 1 | Rs 10,000 | Cash | 12:00 PM |
| RCP-000001 | Deepak Lama / STD-0043 / BCA-I | Tuition Fee – Level 1 | Rs 7,000 | Cash | 9:45 AM |

### 10.5 Database Connection

```sql
SELECT 
  r.receipt_no,
  s.full_name AS student_name,
  s.roll_no,
  b.name AS batch_name,
  fi.name AS fee_item_name,
  r.amount,
  r.payment_method,
  r.receipt_date,
  r.created_at::time AS receipt_time
FROM fee_receipts r
JOIN students s ON r.student_id = s.id
JOIN batches b ON s.batch_id = b.id
JOIN fee_items fi ON r.fee_item_id = fi.id
WHERE r.receipt_date = CURRENT_DATE
ORDER BY r.created_at DESC
LIMIT :page_size OFFSET :offset;
```

---

## 11. Today's Fee Summary Panel

### 11.1 Layout

```
Grid position: Spans 4 of 12 columns (right side, alongside fee table)
Background: #FFFFFF
Border: 1px solid #E5E9F0
Border-radius: 8px
Padding: 16px 20px
```

### 11.2 Stacked Bar Chart

```
At top of panel: a horizontal stacked bar chart
Height: 32px, full width, border-radius: 999px
Segments proportional to collection amounts:
  Cash:          #4CAF50 (green)
  eSewa:         #2196F3 (blue)
  Khalti:        #9C27B0 (purple)
  Bank Transfer: #FF9800 (orange)

Legend below chart (flex row, gap 12px):
  Each item: colored 10px square + label text (11px, #6B7A99)
  Labels: Cash, eSewa, Khalti, Bank (icons are colored squares)
```

### 11.3 Breakdown Rows

```
Each payment method row:
  Display: flex, justify-content: space-between
  Padding: 8px 0
  Border-bottom: 1px solid #F0F2F5

  Left: method name  — 13px, 400, #6B7A99
  Right: amount      — 13px, 700, #1A1F36

  Examples:
    Cash          Rs 49,000
    eSewa         Rs 28,500
    Khalti        Rs 19,200
    Bank Transfer Rs 25,800

Total Row:
  Margin-top: 8px
  Border-top: 2px solid #E5E9F0
  Padding-top: 10px
  Left:  "Total Collected"  — 13px, 700, #1A1F36
  Right: "Rs 1,27,500"     — 15px, 700, #00A86B
```

### 11.4 Database Connection

```sql
SELECT 
  payment_method,
  SUM(amount) AS total,
  COUNT(*) AS count
FROM fee_receipts
WHERE receipt_date = CURRENT_DATE AND status = 'completed'
GROUP BY payment_method
ORDER BY total DESC;
```

---

## 12. Announcements Panel

### 12.1 Layout

```
Positioned below Fee Summary (still in 4-col right column)
Background: #FFFFFF
Border: 1px solid #E5E9F0
Border-radius: 8px
Padding: 16px 20px
Margin-top: 16px

Header Row:
  Left: bell icon (16px, #F5A623) + "Announcements" (13px, 600, #1A1F36)
  Right: "2 New" pill — background #FFEBEE, text #E84040, 10px 600
```

### 12.2 Announcement Item

```
Each item:
  Left accent bar: 3px wide, border-radius 2px, full item height
    Red:    urgent/critical
    Green:  informational/positive
    Blue:   neutral/info
  
  Content:
    Title: 13px, 600, #1A1F36
    Body text: 12px, 400, #6B7A99 (max 2 lines, ellipsis overflow)
    Footer: "Author · Role · Time" — 10px, #A8BCCF

  Padding: 10px 12px 10px 16px
  Margin-bottom: 8px
  Background: #FAFAFA
  Border-radius: 6px

Visible items:
  1. [RED] "Fee Deadline Reminder – Feb Batch"
     "All pending fees for Feb 2026 batch must be cleared by March 07."
     "Urgent · Platform Admin · 2 hrs ago"

  2. [GREEN] "Exam Schedule Published – BCA-III"
     "BCA-III internal exams start March 10. Admit cards available at front desk."
     "Normal · Academic Office · 5 hrs ago"

  3. [BLUE] "Holiday: Holi – March 14, 2026"
     "Institution will remain closed on March 14. Make-up class on March 16."
     "Info · Principal Office · Yesterday"
```

### 12.3 Database Connection

```sql
SELECT 
  a.id, a.title, a.body, a.priority,
  u.full_name AS author, u.role AS author_role,
  a.created_at
FROM announcements a
JOIN users u ON a.created_by = u.id
WHERE a.is_active = true
  AND (a.expires_at IS NULL OR a.expires_at > NOW())
  AND (a.target_role = 'all' OR a.target_role = :user_role)
ORDER BY a.priority DESC, a.created_at DESC
LIMIT 10;
```

---

## 13. Attendance Snapshot Panel

### 13.1 Layout

```
Grid position: 4 of 12 columns (leftmost of bottom 3-panel row)
Background: #FFFFFF
Border: 1px solid #E5E9F0
Border-radius: 8px
Padding: 16px 20px
```

### 13.2 Header

```
Left: clipboard icon (16px, #00A86B) + "Attendance Snapshot" (13px, 600, #1A1F36)
Right: "→ Full View" link — 12px, 500, #3B82F6, hover underline

Sub-header: "All Batches · Marked 11 pending" — 11px, #6B7A99
```

### 13.3 Count Summary Row

```
4 colored stat boxes in a row:
  Present: "142" — bg #E8F5E9, text #00A86B, label "Present"
  Absent:  "14"  — bg #FFEBEE, text #E84040, label "Absent"
  Late:    "5"   — bg #FFF3E0, text #F5A623, label "Late"
  Leave:   "2"   — bg #E3F2FD, text #1565C0, label "Leave"

Each box:
  Flex: 1, text-center
  Border-radius: 6px
  Padding: 8px
  Number: 18px, 700
  Label: 10px, 400, same color but lighter shade
```

### 13.4 Overall Rate Bar

```
Label: "Attendance Rate" — 11px, #6B7A99
Value: "87.1%" — 12px, 700, #00A86B (right-aligned)

Progress bar:
  Height: 6px, border-radius: 999px
  Background: #E5E9F0
  Fill: Linear gradient #00A86B → #00C47D
  Width: 87.1% of bar
```

### 13.5 BY BATCH Section

```
Label: "BY BATCH" — 10px, 700, #6B7A99, uppercase, letter-spacing 1px
Margin-top: 12px

Each batch row:
  Display: flex, align-items: center, gap 8px
  Margin-bottom: 8px

  Left avatar: 24px circle, initials-based bg color
    BCA-I (Morning):     bg #E8F5E9, text #2E7D32, initials "B"
    BCA-II (Morning):    bg #E3F2FD, text #1565C0, initials "B"
    BCA-III (Afternoon): bg #FFF3E0, text #E65100, initials "B"
    BCA-IV (Afternoon):  bg #F3E5F5, text #6A1B9A, initials "B"
    +CSIT-I (Morning):   bg #FFEBEE, text #B71C1C, initials "C"

  Batch name: 12px, 500, #1A1F36
  Student count: "32 students · 28 present" — 10px, #6B7A99
  Right: percentage "97.5%" — 12px, 700, #00A86B

  Mini progress bar below batch row:
    Height: 4px, border-radius 999px
    Green fill proportional to % present
```

### 13.6 Database Connection

```sql
SELECT 
  b.id, b.name AS batch_name,
  COUNT(a.id) AS total_students,
  COUNT(a.id) FILTER (WHERE a.status = 'present') AS present,
  COUNT(a.id) FILTER (WHERE a.status = 'absent') AS absent,
  COUNT(a.id) FILTER (WHERE a.status = 'late') AS late,
  COUNT(a.id) FILTER (WHERE a.status = 'leave') AS on_leave,
  ROUND(COUNT(a.id) FILTER (WHERE a.status = 'present') * 100.0 
        / NULLIF(COUNT(a.id), 0), 1) AS percentage
FROM batches b
JOIN student_enrollments se ON se.batch_id = b.id
LEFT JOIN attendance a ON a.student_id = se.student_id AND a.date = CURRENT_DATE
WHERE b.is_active = true
GROUP BY b.id, b.name
ORDER BY b.name;
```

---

## 14. Today's Inquiries Panel

### 14.1 Layout

```
Grid position: 4 of 12 columns (center of bottom 3-panel row)
Background: #FFFFFF
Border: 1px solid #E5E9F0
Border-radius: 8px
Padding: 16px 20px
```

### 14.2 Header

```
Left: chat-bubble icon (16px, #3B82F6) + "Today's Inquiries" (13px, 600, #1A1F36)
Right two elements:
  "7 Open" — pill, background #FFEBEE, text #E84040, 10px 600
  "+ Add" button — background #00A86B, text white, 10px 600, border-radius 4px, padding 4px 10px
```

### 14.3 Inquiry Item

```
Each inquiry:
  Avatar: 32px circle, initials, unique background
  Main block:
    Name: "Asha Rai" — 13px, 600, #1A1F36
    Inquiry type + program: "BCA Program Inquiry" — 11px, #6B7A99
  Right block:
    Time: "10:22 AM" — 10px, #6B7A99
    Status badge:
      "New"      — bg #E8F5E9, text #00A86B
      "Follow-up"— bg #FFF3E0, text #E65100
      "Resolved" — bg #E3F2FD, text #1565C0

Row layout:
  Display: flex, align-items: center, gap 10px
  Padding: 10px 0
  Border-bottom: 1px solid #F0F2F5

Inquiry type sub-label:
  Shows "Walk-In" or "Phone" as a small icon+text

Visible inquiries:
  1. Asha Rai · BCA Program Inquiry · 10:22 AM · [New] [Walk-In]
  2. Prakash Mahato · +2 Science Admission · 09:47 AM · [Follow-up] [Phone]
  3. Sunita Shrestha · Certificate Program · 07:13 AM · [Resolved] [Walk-In]
  (+ partially visible 4th item indicating scroll)
```

### 14.4 Database Connection

```sql
SELECT 
  i.id, s.full_name AS contact_name,
  i.inquiry_type, i.program_of_interest,
  i.inquiry_mode, i.status,
  i.created_at::time AS inquiry_time
FROM inquiries i
JOIN contacts s ON i.contact_id = s.id
WHERE i.inquiry_date = CURRENT_DATE
ORDER BY i.created_at DESC;
```

---

## 15. Pending Leave Requests Panel

### 15.1 Layout

```
Grid position: Right section of bottom-middle area (alongside Inquiries, or below)
Background: #FFFFFF
Border: 1px solid #E5E9F0
Border-radius: 8px
Padding: 16px 20px
```

### 15.2 Header

```
Left: calendar-x icon (16px, #F5A623) + "Pending Leave Requests" (13px, 600, #1A1F36)
Right: "5" count badge — circle 20px, #F5A623 background, white text, 11px 700
```

### 15.3 Leave Request Item

```
Each item:
  Avatar: 32px circle, initials, colored
  Name: "Ramesh Sharma" — 13px, 600, #1A1F36
  Leave date + type: "March 04 · Sick Leave" — 11px, #6B7A99
  Right actions:
    ✓ Approve: 20px circle button, bg #E8F5E9, icon color #00A86B
    ✗ Reject:  20px circle button, bg #FFEBEE, icon color #E84040
  Padding: 10px 0, border-bottom: 1px solid #F0F2F5

Visible requests:
  1. Ramesh Sharma  · March 04 · Sick Leave    ✓ ✗
  2. Nisha Thapa    · March 05 · Personal       ✓ ✗
  3. Bikash KC      · March 06 · Family Function ✓ ✗
```

### 15.4 Database Connection

```sql
SELECT 
  lr.id, u.full_name, lr.leave_type,
  lr.start_date, lr.end_date, lr.reason,
  lr.applied_at
FROM leave_requests lr
JOIN users u ON lr.user_id = u.id
WHERE lr.status = 'pending'
  AND lr.start_date >= CURRENT_DATE
ORDER BY lr.start_date ASC
LIMIT 10;

-- Approve action
UPDATE leave_requests 
SET status = 'approved', approved_by = :current_user_id, approved_at = NOW()
WHERE id = :leave_request_id;
```

---

## 16. Today's Timetable Panel

### 16.1 Layout

```
Grid position: 4 of 12 columns (leftmost of the final bottom row)
Background: #FFFFFF
Border: 1px solid #E5E9F0
Border-radius: 8px
Padding: 16px 20px
```

### 16.2 Header

```
Left: clock icon + "Today's Timetable" (13px, 600, #1A1F36)
Right: "Tuesday" tag — pill, bg #E3F2FD, text #1565C0, 10px 600
```

### 16.3 Timetable Entry

```
Time slot label: "8:00 – 9:30" — 11px, 500, #6B7A99
Subject card:
  Background: #F0FFF8 (for ongoing/current) or #FAFAFA
  Border-left: 3px solid #00A86B (ongoing) or #E5E9F0 (others)
  Border-radius: 0 6px 6px 0
  Padding: 8px 12px
  
  Subject name: "Database Management Systems" — 13px, 600, #1A1F36
  Teacher + Room: "BCA-I · Room 201, Mr. Hari Baniya" — 11px, #6B7A99
  Status: "Ongoing" pill — bg #E8F5E9, text #00A86B, 9px 600 
          or nothing for future classes

Entries:
  8:00-9:30:  Database Management Systems / BCA-I / Room 201 / Ongoing
  9:30-11:00: Computer Networks / BCA-II / Mr. Kumar
  11:00-12:30: Artificial Intelligence / BCA-IV / Room 301
  12:30-2:00: Programming in C / BCA-I / Room 101 Mr. Sto
```

### 16.4 Database Connection

```sql
SELECT 
  ts.start_time, ts.end_time,
  sub.name AS subject_name,
  b.name AS batch_name,
  r.name AS room_name,
  CONCAT(u.full_name) AS teacher_name,
  CASE 
    WHEN CURRENT_TIME BETWEEN ts.start_time AND ts.end_time THEN 'ongoing'
    WHEN ts.start_time > CURRENT_TIME THEN 'upcoming'
    ELSE 'completed'
  END AS status
FROM timetable_slots ts
JOIN subjects sub ON ts.subject_id = sub.id
JOIN batches b ON ts.batch_id = b.id
JOIN rooms r ON ts.room_id = r.id
JOIN users u ON ts.teacher_id = u.id
WHERE ts.day_of_week = EXTRACT(DOW FROM CURRENT_DATE)
  AND ts.academic_year_id = :current_ay
ORDER BY ts.start_time;
```

---

## 17. Activity Log Panel

### 17.1 Layout

```
Grid position: 4 of 12 columns (center of final bottom row)
Background: #FFFFFF
Border: 1px solid #E5E9F0
Border-radius: 8px
Padding: 16px 20px
```

### 17.2 Header

```
Left: activity/pulse icon + "Activity Log" (13px, 600, #1A1F36)
Right: "Today Only" dropdown — 11px, #6B7A99, caret icon
```

### 17.3 Activity Entry

```
Timeline layout (vertical line on left):
  Vertical line: 1px solid #E5E9F0, runs down through all items
  
  Each entry:
    Timeline dot: 8px circle on the line
      Green: #00A86B (payment collected)
      Blue:  #3B82F6 (student registered)
      Orange:#F5A623 (leave/other)
    
    Content block (right of line):
      Action text: 12px, 400, #1A1F36
        "Rs 2,000 collected from Ramesh Sharma"
        Bold parts (names/amounts): 600 weight
      Timestamp: "@ 10:17 PM · Sunita Devi" — 10px, #A8BCCF
      Padding-bottom: 12px

Visible entries:
  🟢 Rs 2,000 collected from Ramesh Sharma (RCP-000009) @ 10:17 PM · Sunita Devi
  🔵 Leave request from Bikash KC received · March 06 @ 1:09 PM · System
  🟢 Rs 10,000 collected from Priyanka Shah (RCP-000008) @ 10:15 PM · Sunita Devi
  🔵 New student Priyanka Shah registered (STD-0034) in BCA-I @ 10:00 AM · System
```

### 17.4 Database Connection

```sql
SELECT 
  al.id, al.action_type, al.description,
  al.entity_type, al.entity_id,
  u.full_name AS performed_by,
  al.created_at
FROM activity_logs al
LEFT JOIN users u ON al.user_id = u.id
WHERE al.created_at::date = CURRENT_DATE
ORDER BY al.created_at DESC
LIMIT 20;
```

---

## 18. Library Desk Panel

### 18.1 Layout

```
Grid position: 4 of 12 columns (rightmost of final bottom row)
Background: #FFFFFF
Border: 1px solid #E5E9F0
Border-radius: 8px
Padding: 16px 20px
```

### 18.2 Header

```
Left: book icon + "Library Desk" (13px, 600, #1A1F36)
Right: "+ Issue" button — bg #00A86B, text white, 10px 600, padding 4px 10px, border-radius 4px

Sub-header: "RECENT ISSUES TODAY" — 10px, 700, #6B7A99, uppercase
```

### 18.3 Book Issue Item

```
Each item:
  Book icon or cover thumbnail: 32px × 40px
    Background: colored rectangle (randomized from palette)
  Book info:
    Title (truncated): "Operating Systems (..." — 12px, 600, #1A1F36
    "Ram Bahadur KC · Due Mar 11" — 10px, #6B7A99
  Status badge (right):
    "Issued"   — bg #E8F5E9, text #00A86B
    "Overdue"  — bg #FFEBEE, text #E84040

Row padding: 8px 0, border-bottom: 1px solid #F0F2F5

Visible entries:
  📗 Operating Systems (... | Ram Bahadur KC · Due Mar 11 | [Issued]
  📘 DBMS Concepts & D... | Bikash Kumar · Due Mar 09   | [Issued]
  📕 C Programming (K...  | Puja Sharma · Due Mar 04    | [Overdue]
  📙 Artificial Intelligence | Anita Singh · Due Mar 11  | [Issued]

Footer:
  "Books in circulation: 47 of 312"
  "47" — 700, #1A1F36 | " of 312" — 400, #6B7A99
  "Overdue:" count in red below
```

### 18.4 Database Connection

```sql
SELECT 
  li.id, b.title AS book_title, b.author,
  s.full_name AS student_name,
  li.issue_date, li.due_date,
  CASE 
    WHEN li.due_date < CURRENT_DATE AND li.return_date IS NULL THEN 'overdue'
    WHEN li.return_date IS NULL THEN 'issued'
    ELSE 'returned'
  END AS status
FROM library_issues li
JOIN books b ON li.book_id = b.id
JOIN students s ON li.student_id = s.id
WHERE li.issue_date = CURRENT_DATE
ORDER BY li.created_at DESC;

-- Circulation summary
SELECT 
  COUNT(*) FILTER (WHERE return_date IS NULL) AS in_circulation,
  (SELECT COUNT(*) FROM books WHERE is_active = true) AS total_books,
  COUNT(*) FILTER (WHERE due_date < CURRENT_DATE AND return_date IS NULL) AS overdue
FROM library_issues
WHERE academic_year_id = :current_ay;
```

---

## 19. Database Schema

### 19.1 Core Tables