# Expenses Module - Functional Specification Document

**Project:** HamroLabs Institute Management System  
**Module:** Expenses Management  
**Version:** 1.0  
**Date:** March 18, 2026  
**Prepared For:** Institute Administrators

---

## 1. Executive Summary

The Expenses Module is designed to streamline financial tracking and management for Nepali educational institutes. This module addresses critical pain points identified in the market research, including revenue leakage, manual reconciliation challenges, and lack of real-time financial visibility. The system supports Bikram Sambat (BS) calendar integration and local payment methods (eSewa, Khalti, cash, bank transfers, and cheques).

### Key Objectives
- Eliminate manual expense tracking and reduce reconciliation errors
- Provide real-time visibility into institutional spending
- Support all expense categories relevant to training institutes
- Enable comprehensive financial reporting with minimal administrative effort
- Track recurring expenses automatically
- Maintain simple, intuitive workflows suitable for single-approval structures

---

## 2. Module Overview

### 2.1 Core Capabilities

The Expenses Module provides institute administrators with the following core capabilities:

1. **Expense Recording** - Quick entry of all institutional expenses with receipt uploads
2. **Category Management** - Track expenses across all operational categories
3. **Payment Tracking** - Support for multiple payment methods including digital wallets
4. **Recurring Expenses** - Automated tracking of regular monthly/quarterly expenses
5. **Financial Reporting** - Comprehensive dashboards and exportable reports
6. **Budget Monitoring** - Real-time expense visibility without complex approval workflows

### 2.2 User Roles

**Institute Admin** (Primary User)
- Full access to all expense management features
- Can create, edit, approve, and delete expense records
- Access to all financial reports and analytics
- Manage expense categories and budgets

---

## 3. Functional Requirements

### 3.1 Expense Entry

#### 3.1.1 Basic Expense Information
Every expense record must capture:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Date | BS/AD Date | Yes | Date of expense (dual calendar support) |
| Amount | Decimal | Yes | Expense amount in NPR |
| Category | Dropdown | Yes | Pre-defined expense category |
| Payment Method | Dropdown | Yes | Cash, Bank Transfer, eSewa, Khalti, Cheque |
| Description | Text | No | Brief description of the expense |
| Receipt | File Upload | No | PDF/Image of receipt (max 5MB) |
| Recurring | Boolean | Yes | Mark if this is a recurring expense |

#### 3.1.2 Expense Categories

The system supports the following expense categories:

**1. Staff Salaries & Benefits**
- Teaching staff salaries
- Administrative staff salaries
- Provident fund contributions
- Health insurance
- Performance bonuses
- Festival allowances

**2. Operational Costs**
- Rent/Lease payments
- Electricity bills
- Water charges
- Internet/telecom
- Office supplies
- Cleaning and maintenance
- Security services

**3. Educational Materials & Resources**
- Textbooks and study materials
- Software licenses (learning platforms)
- Library resources
- Stationery for students
- Lab equipment and supplies

**4. Marketing & Promotional Expenses**
- Facebook/Social media advertising
- Print materials (brochures, banners)
- Event sponsorships
- Website maintenance
- Lead generation tools

**5. Technology & Infrastructure**
- Computer hardware
- Projectors and AV equipment
- Furniture and fixtures
- Software subscriptions (ERP, tools)
- Biometric systems
- Network equipment

**6. Miscellaneous**
- Government fees and registrations
- CTEVT affiliation fees
- Legal and professional fees
- Travel and transportation
- Refreshments and hospitality

#### 3.1.3 Payment Method Details

Each payment method requires specific additional information:

**Cash**
- No additional fields required
- System maintains cash balance tracking

**Bank Transfer**
- Bank name (dropdown)
- Transaction reference number
- Transfer date (if different from expense date)

**eSewa**
- Transaction ID
- eSewa account number

**Khalti**
- Transaction ID
- Khalti mobile number

**Cheque**
- Cheque number
- Bank name
- Cheque date
- Clearance status (Pending/Cleared)

### 3.2 Recurring Expenses Management

#### 3.2.1 Recurring Expense Setup

When marking an expense as recurring, the admin must specify:

| Field | Options | Description |
|-------|---------|-------------|
| Frequency | Monthly, Quarterly, Semi-annually, Annually | How often the expense repeats |
| Start Date | BS/AD Date | When the recurring expense begins |
| End Date | BS/AD Date (Optional) | When the recurring expense stops |
| Auto-create | Yes/No | Automatically create expense entries |
| Notification Days | Number (1-30) | Days before expense due date to notify |

#### 3.2.2 Recurring Expense Workflow

1. **Setup Phase**
   - Admin creates initial expense entry
   - Marks as "Recurring" and sets frequency
   - System validates and saves recurring pattern

2. **Automated Creation** (If enabled)
   - System generates expense entry automatically based on frequency
   - Status set to "Pending Approval"
   - Admin receives notification

3. **Manual Review**
   - Admin reviews auto-generated entry
   - Can modify amount if needed (e.g., variable electricity bill)
   - Approves and uploads receipt

4. **Tracking Dashboard**
   - Dedicated "Recurring Expenses" view
   - Shows upcoming recurring expenses for next 30/60/90 days
   - Highlights missed or overdue entries

### 3.3 Receipt Management

#### 3.3.1 Receipt Upload
- Supported formats: PDF, JPG, PNG, JPEG
- Maximum file size: 5MB per receipt
- Multiple receipts can be attached to single expense (e.g., multiple vendor bills)
- Automatic thumbnail generation for quick preview

#### 3.3.2 Receipt Storage
- Cloud storage with secure access
- Organized by fiscal year and month
- Easy retrieval via expense ID or date range
- Bulk download option for audit purposes

### 3.4 Approval Workflow

#### 3.4.1 Single Approval Model

Given the requirement for "Single approval (admin only)", the workflow is streamlined:

1. **Draft State**
   - Admin creates expense entry
   - All required fields validated
   - Receipt uploaded (if available)

2. **Approval**
   - Admin reviews and clicks "Approve"
   - Expense status changes to "Approved"
   - Amount deducted from available budget (if tracking enabled)
   - Entry becomes part of financial reports

3. **Edit/Delete**
   - Admin can edit approved expenses (audit trail maintained)
   - Delete option available with confirmation
   - System logs all modifications with timestamp

---

## 4. Reporting & Analytics

### 4.1 Dashboard Views

#### 4.1.1 Main Expenses Dashboard

**Top Metrics** (Current Month)
- Total Expenses
- Total Expenses by Category (pie chart)
- Expense Trend (line graph - last 6 months)
- Top 5 Expense Categories

**Quick Filters**
- Date Range (BS/AD)
- Category
- Payment Method
- Recurring vs One-time

#### 4.1.2 Category-wise Breakdown

**Visualization**
- Horizontal bar chart showing expenses by category
- Percentage of total for each category
- Month-over-month comparison
- Drill-down capability to see individual transactions

**Data Table**
- Category name
- Total amount
- Number of transactions
- Average transaction amount
- % of total expenses

### 4.2 Standard Reports

#### 4.2.1 Monthly Financial Summary

**Report Contents:**
- Total expenses by category
- Payment method breakdown
- Recurring vs one-time expenses
- Largest expense entries (top 10)
- Month-over-month variance analysis

**Export Formats:** PDF, Excel, CSV

#### 4.2.2 Quarterly Financial Summary

**Report Contents:**
- Quarter-wise expense trends
- Category performance over 3 months
- Recurring expense summary
- Payment method distribution
- Quarter-over-quarter growth/decline

**Export Formats:** PDF, Excel

#### 4.2.3 Budget vs Actual Comparison

**Report Contents:**
- Category-wise budget allocation
- Actual expenses vs budget
- Variance (amount and percentage)
- Budget utilization rate
- Projected expenses for remaining period

**Visual Elements:**
- Progress bars for each category
- Traffic light indicators (Green: <80%, Yellow: 80-95%, Red: >95%)
- Trend lines for projection

#### 4.2.4 Expense Trends & Forecasting

**Report Contents:**
- Historical expense patterns (last 12 months)
- Seasonal variations identified
- Predicted expenses for next 3 months
- Anomaly detection (unusual spikes or drops)
- Recommendations for budget adjustments

**Methodology:**
- Simple moving average for forecasting
- Considers recurring expenses in projection
- Flags unusual patterns for admin review

### 4.3 Custom Reports

#### 4.3.1 Report Builder
- Drag-and-drop interface for creating custom reports
- Filter by date range, categories, payment methods
- Select metrics to display
- Save custom report templates for reuse

#### 4.3.2 Scheduled Reports
- Set up automated email delivery
- Daily/Weekly/Monthly frequency
- Multiple recipients support
- Choose report format (PDF/Excel)

---

## 5. Additional Features

### 5.1 Budget Management

#### 5.1.1 Budget Setup
While not requiring complex approval workflows, admins can:
- Set overall monthly/quarterly budgets
- Allocate budgets by category
- Define budget periods (fiscal year basis)

#### 5.1.2 Budget Alerts
- Email/SMS notification when category reaches 80% of budget
- Warning at 90%
- Alert at 100%
- Weekly budget utilization summary

### 5.2 Vendor/Supplier Database (Optional Enhancement)

Though not selected initially, this can be added later:
- Maintain list of regular vendors
- Quick-select vendor when creating expense
- Vendor-wise expense analysis
- Contact information storage

### 5.3 Tax and Compliance

#### 5.3.1 VAT/PAN Support
- Option to mark expenses as VAT-eligible (13%)
- Calculate VAT amount automatically
- Generate VAT-compliant reports
- Support for PAN number recording

#### 5.3.2 Statutory Reports
- TDS (Tax Deducted at Source) summary
- VAT reconciliation report
- Annual expense summary for tax filing
- CTEVT compliance expenditure report

---

## 6. Technical Specifications

### 6.1 Calendar Integration

**Bikram Sambat (BS) Calendar**
- Use @sonill/nepali-dates library for accurate conversion
- All date inputs support both BS and AD
- Default display in BS (configurable by user)
- Fiscal year aligned with Nepal's BS calendar (Shrawan-Ashadh)

### 6.2 Payment Gateway Integration

**eSewa Integration**
- Verify transaction status via eSewa API
- Automatic reconciliation with expense entries
- Settlement tracking (T+1 confirmation)

**Khalti Integration**
- Transaction verification via Khalti API
- Digital receipt generation
- Auto-populate transaction details from API

### 6.3 Data Storage & Security

**Database Requirements**
- Encrypted storage for financial data
- Daily automated backups
- Audit trail for all modifications
- Role-based access control (for future multi-user expansion)

**File Storage**
- Cloud storage for receipts (AWS S3 or similar)
- CDN for fast receipt retrieval
- Automatic compression for large images
- Retention period: 7 years (Nepal tax compliance)

### 6.4 Performance Requirements

- Page load time: < 2 seconds
- Report generation: < 5 seconds for standard reports
- Support for 10,000+ expense entries per year
- Concurrent users: 5-10 admins

---

## 7. User Interface Design Guidelines

### 7.1 Dashboard Design Principles

**Simplicity First**
- Clean, uncluttered interface
- Most important metrics above the fold
- Maximum 3-click access to any feature

**Visual Clarity**
- Use of charts and graphs for quick insights
- Color-coding for expense categories
- Status indicators (green/yellow/red)

**Mobile Responsive**
- Fully functional on tablets and smartphones
- Touch-friendly interface elements
- Simplified views for mobile devices

### 7.2 Key Screens

#### 7.2.1 Main Dashboard
- Summary cards (Total Expenses, This Month, This Quarter)
- Expense trend graph (6 months)
- Category breakdown pie chart
- Recent expense entries table (last 10)
- Quick action buttons (Add Expense, View Reports)

#### 7.2.2 Add/Edit Expense Screen
- Single-page form
- Real-time validation
- Receipt drag-and-drop upload
- Save as draft option
- Quick templates for common expenses

#### 7.2.3 Expense List View
- Searchable and filterable table
- Column sorting
- Bulk actions (export, delete)
- Quick view popup for expense details
- Inline editing capability

#### 7.2.4 Reports Screen
- Report category tabs (Monthly, Quarterly, Custom)
- Filter panel on left
- Report preview in center
- Export options on right
- Save/Schedule buttons

---

## 8. Integration Points

### 8.1 Integration with Other Modules

**Fee Management Module**
- Compare income (from fees) vs expenses
- Calculate net surplus/deficit
- Profit margin analysis

**Student Management Module**
- Per-student cost calculation
- Scholarship expense tracking
- Student-related material costs

**Staff Management Module**
- Automated salary expense entries
- Leave and allowance impact on payroll
- Performance bonus tracking

### 8.2 Third-Party Integrations

**Accounting Software**
- Export to Tally format
- Chart of accounts mapping
- Journal entry generation

**Banking**
- Bank statement reconciliation
- Direct bank feed integration (future)
- Cheque clearance status updates

**Notification Services**
- SMS gateway integration (for budget alerts)
- WhatsApp Business API (for report sharing)
- Email service (for scheduled reports)

---

## 9. Workflows & Use Cases

### 9.1 Use Case 1: Recording Daily Operational Expense

**Scenario:** Admin needs to record electricity bill payment

**Steps:**
1. Navigate to Expenses → Add New Expense
2. Select Date: 2083-12-05 (BS) / 2026-03-18 (AD)
3. Amount: NPR 15,750
4. Category: Operational Costs → Electricity bills
5. Payment Method: Bank Transfer
6. Bank Name: Nepal Investment Bank
7. Transaction Reference: TRN2026031800123
8. Upload receipt (PDF)
9. Mark as Recurring: Yes
10. Frequency: Monthly
11. Click "Save & Approve"
12. System confirms and updates dashboard

**Result:** 
- Expense recorded and approved
- Budget updated
- Recurring pattern set for future months
- Receipt stored securely
- Dashboard reflects new expense

### 9.2 Use Case 2: Reviewing Monthly Expenses Before Board Meeting

**Scenario:** Admin needs to prepare monthly expense report for management review

**Steps:**
1. Navigate to Reports → Monthly Financial Summary
2. Select Month: Falgun 2083 (BS)
3. Review category breakdown
4. Check budget vs actual comparison
5. Identify any unusual expenses
6. Add notes/comments for board
7. Export report as PDF
8. Email to board members

**Result:**
- Professional PDF report generated
- All expenses categorized and summarized
- Visual charts for easy understanding
- Ready for presentation

### 9.3 Use Case 3: Managing Recurring Salary Expenses

**Scenario:** Admin needs to process monthly staff salaries

**Steps:**
1. Navigate to Expenses → Recurring Expenses
2. View auto-generated salary entries for current month
3. Review each entry for accuracy
4. Modify amounts if needed (salary increments, new joiners)
5. Upload payment receipts
6. Approve all salary expenses
7. Generate salary expense report
8. Mark as paid in system

**Result:**
- All salary expenses recorded
- Payment documentation attached
- Staff-wise expense tracking updated
- Monthly payroll report available

### 9.4 Use Case 4: Budget Alert Response

**Scenario:** Admin receives alert that marketing budget is at 85%

**Steps:**
1. Receive email/SMS notification
2. Log into system
3. Navigate to Budget vs Actual Report
4. Filter by Category: Marketing & Promotional Expenses
5. Review all marketing expenses this month
6. Identify areas to reduce spending
7. Update remaining month's marketing plan
8. Set reminder to check next week

**Result:**
- Proactive budget management
- Avoided overspending
- Data-driven decision making

---

## 10. Business Rules & Validations

### 10.1 Data Validation Rules

**Mandatory Fields**
- Date, Amount, Category, Payment Method must be filled
- Amount must be greater than 0
- Date cannot be in future (except for post-dated cheques)
- Receipt required for expenses > NPR 5,000

**Amount Validations**
- Maximum amount: NPR 10,00,000 per entry (configurable)
- Warning for amounts > NPR 50,000
- Negative amounts not allowed

**Date Validations**
- Cannot enter expenses more than 1 year in past
- Recurring expense end date must be after start date
- BS/AD date conversion accuracy check

### 10.2 Business Logic Rules

**Recurring Expenses**
- Cannot create duplicate recurring expenses for same category and period
- Auto-generated entries created 7 days before due date
- Maximum recurring period: 5 years

**Budget Rules**
- Cannot exceed budget by more than 20% without override
- Budget resets on fiscal year boundary
- Unspent budget can be rolled over (configurable)

**Deletion Rules**
- Approved expenses can be deleted only within 30 days
- Deletion requires confirmation
- Deleted expenses move to "Archived" status (soft delete)
- Audit trail maintained for all deletions

**Payment Method Rules**
- Cash expenses > NPR 25,000 require additional authorization code
- Cheque expenses must have cheque number
- Digital wallet expenses must have transaction ID

---

## 11. Reporting Schedule

### 11.1 Daily Reports (Auto-generated)

**Daily Expense Summary**
- Sent to admin at 6:00 PM daily
- Lists all expenses entered that day
- Total daily expenses
- Pending approvals (if any in future phases)

### 11.2 Weekly Reports

**Weekly Expense Digest**
- Sent every Friday at 5:00 PM
- Week's total expenses
- Top categories
- Comparison with previous week
- Recurring expenses due next week

### 11.3 Monthly Reports

**Comprehensive Monthly Report**
- Sent on 1st of every month (for previous month)
- Full category breakdown
- Budget vs actual
- Recurring expense summary
- Payment method distribution
- Year-to-date comparison

### 11.4 Quarterly Reports

**Quarterly Business Review**
- Sent at end of each quarter
- 3-month trends and analysis
- Budget performance
- Forecasting for next quarter
- Recommendations

---

## 12. Implementation Phases

### Phase 1: Core Functionality (Weeks 1-4)
- Basic expense entry with all fields
- Category management
- Receipt upload
- Simple dashboard with key metrics
- Export to Excel

### Phase 2: Recurring Expenses & Advanced Reporting (Weeks 5-8)
- Recurring expense setup and automation
- All standard reports implementation
- Budget vs actual tracking
- Mobile-responsive design

### Phase 3: Integrations & Alerts (Weeks 9-12)
- Payment gateway integration (eSewa, Khalti)
- Budget alert system
- Email/SMS notifications
- Integration with fee management module

### Phase 4: Analytics & Optimization (Weeks 13-16)
- Forecasting engine
- Custom report builder
- Advanced analytics dashboard
- Performance optimization

---

## 13. Success Metrics

### 13.1 Operational Metrics

**Efficiency Gains**
- Reduce expense entry time by 60% (from 10 min to 4 min per entry)
- Eliminate manual reconciliation (save 5 hours/week)
- Receipt retrieval time: < 30 seconds

**Accuracy Improvements**
- Reduce data entry errors by 90%
- Zero lost receipts
- 100% expense categorization accuracy

### 13.2 Financial Metrics

**Cost Savings**
- Reduce accountant hours by 20 hours/month
- Prevent revenue leakage through better tracking
- Identify 10-15% cost reduction opportunities

**Budget Compliance**
- 95% budget adherence rate
- Early identification of overspending (within 48 hours)
- Improved forecasting accuracy (±5%)

### 13.3 User Adoption Metrics

**Usage Targets**
- 100% of expenses entered digitally within 3 months
- Daily active usage by admin
- All reports generated from system (zero Excel reports)

---

## 14. Training & Support

### 14.1 Admin Training Program

**Initial Training (2 days)**
- Day 1: System overview, expense entry, basic reports
- Day 2: Recurring expenses, advanced features, troubleshooting

**Training Materials**
- Video tutorials (Nepali and English)
- User manual with screenshots
- Quick reference guide
- FAQ document

### 14.2 Ongoing Support

**Support Channels**
- In-app chat support
- WhatsApp support group
- Email support (24-hour response time)
- Phone support during business hours

**Knowledge Base**
- Searchable help articles
- Video library
- Common scenarios and solutions
- Regular updates and tips

---

## 15. Future Enhancements (Roadmap)

### Version 2.0 (6 months post-launch)
- Multi-branch expense tracking and consolidation
- Vendor/supplier management system
- Staff reimbursement workflow
- Mobile app for expense entry on-the-go
- OCR for automatic receipt data extraction

### Version 3.0 (12 months post-launch)
- AI-powered expense categorization
- Predictive analytics for budget planning
- Integration with government tax systems
- Blockchain-based expense verification
- Advanced fraud detection

---

## 16. Risk Assessment & Mitigation

### 16.1 Technical Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Payment gateway downtime | Medium | Low | Fallback to manual entry with later verification |
| Data loss | High | Very Low | Daily backups, redundant storage |
| Performance degradation | Medium | Medium | Regular optimization, load testing |
| Calendar conversion errors | High | Low | Use battle-tested library, extensive testing |

### 16.2 Operational Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| User adoption resistance | High | Medium | Comprehensive training, easy UI |
| Incomplete data entry | Medium | High | Mandatory field validation, alerts |
| Budget overruns | Medium | Medium | Real-time alerts, early warnings |
| Receipt management failure | Low | Low | Cloud storage, multiple backups |

---

## 17. Compliance & Audit

### 17.1 Regulatory Compliance

**Nepal Tax Compliance**
- 7-year data retention
- VAT calculation and reporting
- TDS tracking and reporting
- Audit trail maintenance

**CTEVT Requirements**
- Expense reporting in CTEVT format
- Training material cost documentation
- Infrastructure investment tracking

### 17.2 Audit Trail

**What is Logged**
- Every expense creation, modification, deletion
- User who performed the action
- Timestamp (BS and AD)
- Before and after values for edits
- IP address and device information

**Audit Reports**
- User activity report
- Modification history
- Deleted expense report
- Compliance checklist

---

## 18. Glossary

| Term | Definition |
|------|------------|
| **BS** | Bikram Sambat - Nepali calendar system |
| **AD** | Anno Domini - Gregorian calendar system |
| **VAT** | Value Added Tax - Currently 13% in Nepal |
| **PAN** | Permanent Account Number - Tax identification |
| **CTEVT** | Council for Technical Education and Vocational Training |
| **TDS** | Tax Deducted at Source |
| **Recurring Expense** | Regular, repeating expenses (monthly, quarterly, etc.) |
| **One-time Expense** | Non-recurring, single occurrence expense |
| **Fiscal Year** | In Nepal: Shrawan 1 to Ashadh 31 (BS calendar) |

---

## 19. Appendices

### Appendix A: Sample Expense Categories Hierarchy

```
1. Staff Salaries & Benefits
   1.1 Teaching Staff
       1.1.1 Full-time Teachers
       1.1.2 Part-time Teachers
       1.1.3 Guest Lecturers
   1.2 Administrative Staff
       1.2.1 Office Manager
       1.2.2 Front Desk
       1.2.3 Accounts Staff
   1.3 Benefits
       1.3.1 Provident Fund
       1.3.2 Health Insurance
       1.3.3 Festival Bonus

2. Operational Costs
   2.1 Facility
       2.1.1 Rent
       2.1.2 Maintenance
   2.2 Utilities
       2.2.1 Electricity
       2.2.2 Water
       2.2.3 Internet
   2.3 Supplies
       2.3.1 Office Supplies
       2.3.2 Cleaning Supplies

(Continues for all categories...)
```

### Appendix B: Report Templates

**Template 1: Monthly Executive Summary**
```
HamroLabs Institute
Monthly Expense Report
Month: Falgun 2083

SUMMARY
Total Expenses: NPR 5,45,000
Budget: NPR 6,00,000
Variance: NPR 55,000 (9% under budget)

CATEGORY BREAKDOWN
Staff Salaries: NPR 3,00,000 (55%)
Operational: NPR 1,50,000 (27%)
Marketing: NPR 50,000 (9%)
Technology: NPR 30,000 (6%)
Other: NPR 15,000 (3%)

TOP EXPENSES
1. Teacher Salaries - NPR 2,50,000
2. Rent - NPR 80,000
3. Electricity - NPR 25,000
...
```

### Appendix C: API Endpoints (for Integrations)

```
GET  /api/expenses                 - List all expenses
POST /api/expenses                 - Create new expense
GET  /api/expenses/{id}            - Get expense details
PUT  /api/expenses/{id}            - Update expense
DELETE /api/expenses/{id}          - Delete expense

GET  /api/expenses/recurring       - List recurring expenses
POST /api/expenses/recurring       - Create recurring expense

GET  /api/reports/monthly          - Monthly report
GET  /api/reports/quarterly        - Quarterly report
GET  /api/reports/budget-vs-actual - Budget comparison

GET  /api/categories               - List expense categories
POST /api/receipts/upload          - Upload receipt
```

---

## Document Version History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-03-18 | Product Team | Initial specification based on stakeholder requirements |

---

**End of Document**
