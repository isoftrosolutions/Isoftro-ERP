# FrontDesk Portal - UI Documentation

## Overview

The `frontdesk_portal.html` is a single-page application (SPA) for the Front Desk module of the HamroLabs ERP system. It provides a comprehensive dashboard for front desk operators to manage daily operations including admissions, fee collection, attendance tracking, and more.

---

## 1. Header (`.hdr`)

### Structure
The header is **fixed** at the top of the viewport with a height of `56px` (`--hdr-h`).

### Visual Design
| Property | Value |
|----------|-------|
| Background | `--green` (#00B894) |
| Box Shadow | `0 2px 8px rgba(0,184,148,.30)` |
| Position | Fixed, top: 0 |
| Z-Index | 1000 |

### Components

#### Brand Section (`.hdr-brand`)
- **Logo** (`.hdr-logo`): 34x34px rounded square (9px radius) with white semi-transparent background
- **Institute Name** (`.hdr-name`): Font size 14px, weight 800
- **Subtitle** (`.hdr-sub`): Font size 10px, opacity 0.75

#### Portal Tag (`.hdr-portal-tag`)
- Badge displaying "FRONT DESK"
- Background: rgba(255,255,255,0.18)
- Border: 1px solid rgba(255,255,255,0.3)
- Border radius: 20px
- Font size: 11px, weight 700

#### Right Section (`.hdr-right`)
- **Clock** (`.hdr-clock`): Shows current time, 12px, weight 600
- **Date** (`.hdr-date`): Shows current date, 11px
- **Action Buttons** (`.hbtn`): 34x34px, white semi-transparent background
  - Notification bell with badge (`.nbadge`) - red counter
  - Search button
  - Settings button

#### User Section
- **Avatar** (`.hdr-avatar`): 32px circle with user's initial
- **User Info** (`.hdr-uinfo`): Name and role displayed in columns

#### Sidebar Toggle (`.sb-toggle`)
- Hamburger menu button to toggle sidebar visibility
- 34x34px, visible on all screen sizes

---

## 2. Sidebar (`.sb`)

### Structure
The sidebar is positioned **fixed** on the left side below the header.

### Dimensions
| Property | Value |
|----------|-------|
| Width | 252px (`--sb-w`) |
| Position | Fixed, top: 56px (below header) |
| Background | White (#ffffff) |
| Border Right | 1px solid `--card-border` |
| Z-Index | 999 |

### Navigation Sections

#### Section Labels (`.sb-sec-lbl`)
- Font size: 10px, weight 700
- Color: `--text-light` (#94A3B8)
- Text transform: uppercase
- Padding: 14px 20px 6px

#### Navigation Buttons (`.sb-btn`)
- **Dimensions**: Full width, padding 10px 20px
- **Layout**: Flex with 11px gap, icon (18px width) + label
- **States**:
  - Default: No background, color `--text-body`
  - Hover: Background `#f1f5f9`, color `--text-dark`
  - Active: Background `#e6f7f3`, color `--green`, left border 3px solid `--green`, weight 700

#### Badge Styles (`.sb-badge`)
- Font size: 10px, weight 800
- Border radius: 20px
- Padding: 2px 7px
- Colors:
  - Default: `--red` (#E11D48)
  - `.green`: `--green`
  - `.amber`: `--amber`

### Navigation Items by Section

#### Overview
| Icon | Label | Active State |
|------|-------|--------------|
| `fa-th-large` | Dashboard | ✓ (default) |
| `fa-calendar-check` | Today's Attendance | |

#### Admissions
| Icon | Label |
|------|-------|
| `fa-user-graduate` | Student Lookup |
| `fa-user-plus` | New Admission |
| `fa-comments` | Inquiries |

#### Fee & Finance
| Icon | Label |
|------|-------|
| `fa-money-bill-wave` | Fee Collection |
| `fa-exchange-alt` | Transactions |
| `fa-clock` | Pending Dues |
| `fa-receipt` | Receipts |

#### Operations
| Icon | Label |
|------|-------|
| `fa-user-clock` | Leave Requests |
| `fa-book` | Library Desk |
| `fa-table` | Today's Timetable |
| `fa-bullhorn` | Announcements |

#### Support
| Icon | Label |
|------|-------|
| `fa-headset` | Support Tickets |
| `fa-shield-alt` | Activity Log |

### Sidebar Footer (`.sb-footer`)
- User avatar (34px circle)
- User name (12px, weight 700)
- User role (11px, `--text-light`)

### Responsive Behavior
- Overlay (`.sb-overlay`) appears on mobile with backdrop blur
- Sidebar slides in/out with CSS transition (0.3s cubic-bezier)

---

## 3. Dashboard Page (`.page`)

### Layout Structure

#### Page Header (`.page-hdr`)
- **Left**: Page title (1.25rem, weight 800) + subtitle (12px, `--text-light`)
- **Right**: Action buttons

#### Main Content Areas

##### Primary Stats Grid (`.stat-grid`)
4-column grid layout with gap of 14px

| Card | Color | Icon | Value | Label | Subtitle |
|------|-------|------|-------|-------|----------|
| 1 | Green | `fa-money-bill-wave` | Rs 1,27,500 | Today's Collection | 9 transactions · Cash, eSewa, Bank |
| 2 | Amber | `fa-clock` | Rs 3,86,000 | Pending Dues | 18 students · Due this week |
| 3 | Sky | `fa-user-check` | 87% | Attendance Today | 142 / 163 present across all batches |
| 4 | Purple | `fa-user-plus` | 4 | New Admissions Today | 2 fully registered · 2 pending docs |

##### Secondary Stats Row
4-column grid with smaller cards:

| Card | Icon | Value | Label | Badge |
|------|------|-------|-------|-------|
| 1 | `fa-comments` | 7 | Open Inquiries | Orange "New" |
| 2 | `fa-user-clock` | 5 | Leave Requests | Amber "Pending" |
| 3 | `fa-book` | 11 | Library Issues Today | - |
| 4 | `fa-receipt` | RCP-000009 | Last Receipt | - |

### Card Design System

#### Stat Card (`.stat-card`)
- Background: White
- Border: 1px solid `--card-border`
- Border radius: 12px
- Padding: 18px 20px
- Box shadow: `--shadow`
- Hover: translateY(-2px), shadow `--shadow-md`

#### Color Theming
Each card has a colored circle decorative element:
- `.green`: `--green` (#00B894)
- `.red`: `--red` (#E11D48)
- `.amber`: `--amber` (#F59E0B)
- `.sky`: `--sky` (#0EA5E9)
- `.purple`: `--purple` (#8141A5)
- `.orange`: `--orange` (#EA580C)

#### Trend Indicators (`.stat-trend`)
- `.trend-up`: Green background (#dcfce7), green text
- `.trend-down`: Red background (#fee2e2), red text
- Border radius: 20px
- Font size: 11px, weight 600

### Typography

| Element | Size | Weight | Color |
|---------|------|--------|-------|
| Stat Value | 1.7rem | 800 | `--text-dark` |
| Stat Label | 12px | 500 | `--text-light` |
| Stat Subtitle | 11px | 400 | `--text-body` |

---

## 4. Design Tokens

### Colors
```css
--green: #00B894;
--green-d: #007a62;
--green-h: #00A180;
--teal: #009E7E;
--navy: #0F172A;
--red: #E11D48;
--purple: #8141A5;
--amber: #F59E0B;
--sky: #0EA5E9;
--orange: #EA580C;
--bg: #F8FAFC;
--card-border: #E2E8F0;
--text-dark: #1E293B;
--text-body: #475569;
--text-light: #94A3B8;
--white: #ffffff;
```

### Typography
- Font Family: 'Plus Jakarta Sans', sans-serif
- Base size: clamp(13px, 1.1vw, 15px)

### Spacing
- Border radius: 12px (standard), 8px (small)
- Card padding: 18-20px
- Grid gap: 14px

### Shadows
```css
--shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.05);
--shadow-md: 0 4px 16px rgba(0,0,0,.08);
--shadow-lg: 0 8px 32px rgba(0,0,0,.12);
```

---

## 5. Key Features

### Interactive Elements
- **Sidebar toggle**: Hamburger menu to show/hide sidebar
- **Navigation buttons**: Click to switch between pages (SPA behavior)
- **Stat cards**: Hover effect with elevation
- **Quick action buttons**: Search, notifications, settings

### SPA Navigation
The portal uses JavaScript `switchPage()` function to navigate between:
- Dashboard (default)
- Today's Attendance
- Student Lookup
- New Admission
- Inquiries
- Fee Collection
- Transactions
- Pending Dues
- Receipts
- Leave Requests
- Library Desk
- Today's Timetable
- Announcements
- Support Tickets
- Activity Log

---

## 6. Responsive Considerations

- Header is fixed and always visible
- Sidebar has overlay mode for mobile
- Grid layouts use `fr` units for flexibility
- Font sizes use `clamp()` for responsive scaling

---

*Document generated from analysis of `frontdesk_portal.html`*
