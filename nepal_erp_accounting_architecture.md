---
title: "Production-Ready Architecture: Nepal Academic ERP Accounting System"
author: "Senior ERP Architect & Nepal Financial Systems Auditor"
date: "2026-03-22"
---

# 1. VALIDATION OF EXISTING FRAMEWORK

Based on the review of the existing framework and Nepal's specific accounting practices (NFRS, NAS for NPOs, IRD rules), here is the validation and gap analysis:

### Validated & Correct Assumptions:
- **Dual Calendar System:** Using both BS (Bikram Sambat) and AD (Anno Domini) is critical. Fiscal year boundaries (Shrawan 1 to Ashad 31) are standard.
- **NAS for NPOs:** Using Income & Expenditure rather than Profit & Loss is correct for academic institutions registered as non-profits/trusts. 
- **Chart of Accounts (COA) Hierarchy:** Grouping by Assets, Liabilities, Income, Expenditure, and Funds (Restricted/Unrestricted) aligns with NFRS.
- **Double Entry System:** The current voucher and ledger posting tables enforce `Debit = Credit`.

### Gaps Identified & Expanded:
1. **Hybrid Accounting:** Academic institutions often use **Accrual** for fees and payroll, but **Cash** for daily expenses and miscellaneous income. The system MUST support Hybrid Accounting.
2. **Statutory Withholdings Deductions (TDS, SSF):**
   - **SSF (Social Security Fund):** 31% total (11% employee, 20% employer). Needs automated generation of liability vouchers.
   - **TDS (Tax Deducted at Source):** Needs deduction on vendor payments (1.5% to 15% depending on service/goods) and salary payments (1% SST minimum).
3. **Education Service Fee (ESF):** 1% ESF applies to admission and tuition fees. This must be a separate liability account, not mixed with school income.
4. **Petty Cash Management:** Schools rely heavily on petty cash. An Imprest form of petty cash management is needed.

---

# 2. DEEP ACCOUNTING FLOW EXPANSION

### The Core Flow
`Transaction Event` → `Source Document (Receipt/Invoice)` → `Accounting Voucher` → `Day Book` → `Ledger Postings` → `Trial Balance` → `Financial Statements (I&E, Balance Sheet)`

### Cash vs Accrual vs Hybrid Implementation Logic
- **Student Fees:** Handled on an **Accrual** basis. When fees become due, an invoice is generated (Income recognized, Account Receivable increased).
- **Expenses/Purchases:** Handled on an **Accrual** or **Cash** basis based on configuration. For simplicity, routine expenses are often cash basis (recorded when paid), while large asset purchases or term contracts are accrual.
- **TDS & SSF:** Automatically accrued on the day salary is processed.

### Exact Journal Entries (Real Examples)

#### Scenario A: Student Fee Billing & Collection (Accrual)
**Event 1: Monthly Fee generated for student (NPR 10,000 tuition + 1% ESF)**
- **Debit:** Account Receivable (Student Ledger) - NPR 10,100
- **Credit:** Tuition Fee Income - NPR 10,000
- **Credit:** ESF Payable (Liability) - NPR 100
*(Voucher Type: Journal)*

**Event 2: Student pays NPR 10,100 via Cash**
- **Debit:** Cash in Hand - NPR 10,100
- **Credit:** Account Receivable (Student Ledger) - NPR 10,100
*(Voucher Type: Receipt)*

#### Scenario B: Salary Processing with TDS & SSF
**Event: Monthly Salary of NPR 50,000 for Teacher**
*SSF: Employee 11% (5,500), Employer 20% (10,000)*
*TDS: 1% Social Security Tax (500)*

- **Debit:** Salary Expense - NPR 50,000
- **Debit:** Employer SSF Expense - NPR 10,000
- **Credit:** Staff Salary Payable - NPR 44,000 *(50k - 5.5k - 0.5k)*
- **Credit:** SSF Payable (Liability) - NPR 15,500 *(5.5k + 10k)*
- **Credit:** TDS Payable (Liability) - NPR 500
*(Voucher Type: Journal)*

#### Scenario C: Vendor Payment with TDS
**Event: Paid NPR 20,000 for printing materials (1.5% TDS)**
- **Debit:** Printing Expense - NPR 20,000
- **Credit:** Bank/Cash - NPR 19,700
- **Credit:** TDS on Purchases (Liability) - NPR 300
*(Voucher Type: Payment)*

---

# 3. REAL ERP IMPLEMENTATION LOGIC

We bridge the gap between Operations (Fees/Payroll) and Accounting using an **Event-Driven Architecture**.

### The API & Event Trigger Flow
1. **Module Action:** `FeeController@collectFee` runs. Updates `fee_records` status to 'paid'.
2. **Event Dispatch:** `event(new FeeCollected($paymentTransaction));`
3. **Accounting Listener (`AutoVoucherListener`):** 
   - Receives the event.
   - Determines the accounts mapping (Cash Account, Income Account, Student AR).
   - Starts a Database Transaction.
   - Creates a [Voucher](file:///c:/Apache24/htdocs/erp/app/Models/Voucher.php#9-42) in `acc_vouchers`.
   - Creates [LedgerPosting](file:///c:/Apache24/htdocs/erp/app/Models/LedgerPosting.php#8-30) rows in `acc_ledger_postings`.
   - Commits DB Transaction.

### Database Transaction (Laravel Example)
```php
DB::transaction(function () use ($payment) {
    // 1. Create Voucher
    $voucher = Voucher::create([
        'tenant_id' => $payment->tenant_id,
        'voucher_no' => generateVoucherNo('RV'), // Receipt Voucher
        'date_ad' => $payment->payment_date,
        'date_bs' => ADtoBS($payment->payment_date),
        'type' => 'receipt',
        'narration' => "Fee received from {$payment->student->name} for {$payment->fee_record->feeItem->name}",
        'reference_no' => $payment->receipt_number,
        'status' => 'posted' // auto-post
    ]);

    // 2. Debit Cash/Bank
    LedgerPosting::create([
        'voucher_id' => $voucher->id,
        'account_id' => $payment->payment_method === 'cash' ? cashAcc() : bankAcc($payment->bank_id),
        'debit' => $payment->amount,
        'credit' => 0
    ]);

    // 3. Credit Account Receivable (Sub-ledger for student)
    LedgerPosting::create([
        'voucher_id' => $voucher->id,
        'account_id' => studentAcc($payment->student_id),
        'debit' => 0,
        'credit' => $payment->amount
    ]);
});
```

---

# 4. ADVANCED MODULE DESIGN

### 1. Fees & Billing Engine
- **Features:** Auto-invoicing, Late fine calculation (daily/flat), Discount/Waiver tracking.
- **Integration:** Maps each `fee_item_type` to a specific GL Account (e.g., "Library Fee" -> "Library Income Account", "Caution Money" -> "Deposit Liability").

### 2. Accounting Engine (GL)
- **Features:** Double-entry validation (`sum(debit) == sum(credit)`), Fiscal Year management (auto-switch on Shrawan 1), Day Book locking.
- **Workflow:** Maker-Checker model. Entry clerk drafts voucher -> Head Accountant verifies -> Principal/Finance Head approves -> System posts to ledger.

### 3. Payroll + SSF + TDS Engine
- **Features:** Dynamic salary components (Basic, Grade, Allowance), automated tax slab calculations.
- **Compliance:** Generates SSF upload format excel, IRD e-TDS return format.

### 4. Compliance Engine
- **ESF (Education Service Fee):** Automatically segregates 1% of applicable fees into the IRD ESF Liability account. Generates monthly ESF deposit forms.
- **VAT/PAN:** Tracks Vendor PAN numbers. For billing > NPR 20,000, enforces PAN validation.

### 5. Audit & Reporting System
- **Immutable Logs:** Once a voucher is 'posted', it cannot be deleted. Modifications require a 'Reversal Voucher'.
- **Social Audit:** Nepal specific requirement for schools. Tracks % of scholarships given, teacher-student ratio, infrastructure spending vs total income.

---

# 5. DETAILED DATABASE ARCHITECTURE

The accounting schema must reside in the same DB but clearly separated from operational tables.

```sql
-- Chart of Accounts
CREATE TABLE acc_accounts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT,
    code VARCHAR(50) NOT NULL, -- e.g. 101, 101.01
    name VARCHAR(255) NOT NULL,
    type ENUM('asset', 'liability', 'equity', 'income', 'expense') NOT NULL,
    is_group BOOLEAN DEFAULT 0,
    parent_id BIGINT NULL,
    opening_balance DECIMAL(15,2) DEFAULT 0.00,
    balance_type ENUM('dr', 'cr') NOT NULL,
    is_system BOOLEAN DEFAULT 0, -- Prevents user from deleting 'Cash' or 'Retained Earnings'
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Vouchers
CREATE TABLE acc_vouchers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT,
    fiscal_year_id BIGINT,
    voucher_no VARCHAR(50) NOT NULL,
    date_ad DATE NOT NULL,
    date_bs VARCHAR(10) NOT NULL, -- Crucial for Nepal
    type ENUM('journal', 'receipt', 'payment', 'contra', 'sales', 'purchase') NOT NULL,
    narration TEXT,
    reference_no VARCHAR(100), -- Bill no, Cheque no
    total_amount DECIMAL(15,2),
    status ENUM('draft', 'verified', 'approved', 'posted', 'cancelled') DEFAULT 'draft',
    created_by BIGINT,
    approved_by BIGINT NULL,
    created_at TIMESTAMP
);

-- Ledger Postings (The Journal Lines)
CREATE TABLE acc_ledger_postings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT,
    voucher_id BIGINT NOT NULL,
    account_id BIGINT NOT NULL,
    sub_ledger_type VARCHAR(50) NULL, -- 'student', 'staff', 'vendor'
    sub_ledger_id BIGINT NULL,        -- The actual ID of student/staff
    debit DECIMAL(15,2) DEFAULT 0.00,
    credit DECIMAL(15,2) DEFAULT 0.00,
    description VARCHAR(255),
    FOREIGN KEY (voucher_id) REFERENCES acc_vouchers(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES acc_accounts(id)
);

-- Adding critical indexes for reporting speed
CREATE INDEX idx_postings_acc_tenant ON acc_ledger_postings(account_id, tenant_id);
CREATE INDEX idx_vouchers_date_bs ON acc_vouchers(tenant_id, date_bs);
```

---

# 6. AUTOMATION LOGIC

- **Auto ESF:** In `FeeService@generateInvoice`, hook checks if the fee category is subject to ESF. If yes, calculates 1%, deducts from base fee, pushes to ESF payable.
- **Auto TDS:** In `ExpenseController`, if amount > predefined threshold and vendor type requires TDS, the UI shows a TDS toggle. Backend automatically splits the payment voucher into `Vendor Payable` and `TDS Payable`.
- **Auto Accrual (Month End):** CRON Job runs on the 1st of every BS Month. It scans all active staff and generates Salary Payable vouchers for the previous month. It scans all recurring expenses (Rent, Internet) and posts accrual journal entries.
- **Auto Late Fine:** CRON job runs daily at 12:01 AM. Finds overdue fees. Appends a fine line-item to the `fee_records` and posts a Journal Voucher Debit AR, Credit Fine Income.

---

# 7. CRITICAL EDGE CASES (NEPAL CONTEXT)

### 1. Fiscal Year Overlap (Shrawan issue)
- **Problem:** Shrawan 1 falls mid-July. AD calendar transactions during July 1-15 belong to old FY, July 16-31 belong to new FY.
- **Solution:** ALWAYS determine the active Fiscal Year based on `date_bs`, not `date_ad`. The middleware must resolve the FY context using `Nepcal::ad_to_bs($date)`.

### 2. Caution Deposit (Refundable)
- **Flow:** When collected, Debit Cash, Credit `Caution Deposit (Liability)`. 
- **Refund:** When a student graduates and requires NOC (No Objection Certificate), system checks AR. If dues exist, it uses the deposit to settle dues (Debit Deposit, Credit AR). Remaining balance is refunded (Debit Deposit, Credit Cash).

### 3. Scholarship Adjustment
- **Flow:** A NPR 5,000 scholarship is granted against a NPR 10,000 fee.
- **Entry:** Do not reduce fee income natively immediately in reporting for transparency. 
  - Debit: Account Receivable 10,000, Credit: Fee Income 10,000.
  - Debit: Scholarship/Discount Expense 5,000, Credit: Account Receivable 5,000.

### 4. Advance Payments
- **Flow:** Student pays NPR 50,000 for the whole year upfront.
- **Entry:** Debit Cash 50,000. Credit `Advance Fee Received (Liability)` 50,000. 
- **Amortization:** Every month, auto-journal: Debit `Advance Fee Received`, Credit `Account Receivable`.

---

# 8. REPORTING SYSTEM CALCULATION LOGIC

### Extracting the Trial Balance
To prevent massive queries on production, keep a running total or use grouped aggregations.
```sql
SELECT 
    a.code, a.name, 
    SUM(p.debit) as total_db, 
    SUM(p.credit) as total_cr,
    (a.opening_balance + SUM(p.debit) - SUM(p.credit)) as closing_balance
FROM acc_accounts a
LEFT JOIN acc_ledger_postings p ON a.id = p.account_id
JOIN acc_vouchers v ON p.voucher_id = v.id
WHERE v.status = 'posted' AND v.date_bs BETWEEN '2082-04-01' AND '2083-03-31'
GROUP BY a.id
```

### NAS for NPOs: Income & Expenditure Statement
Filters all accounts where `type` is 'income' or 'expense'. Grouped by Account Groups (Direct Receipts, Grants, Operational Costs, Administrative Costs). Net result is "Surplus/(Deficit) transferred to General Fund".

### The Balance Sheet
Assets vs Liabilities + Funds. 
Funds block includes: Initial Corpus Fund + Restricted Grants + Accumulated Surplus.

---

# 9. UI/UX FOR NEPAL ACCOUNTANTS

Nepali accountants are heavily accustomed to manual ledgers, Tally ERP, and Swastik. The UI must minimize the learning curve.

1. **Voucher Entry UI (The Tally Way):**
   - Keyboard-first navigation (Enter to move to next field).
   - Dynamic rows for Dr/Cr with auto-balancing footer.
   - Hotkeys: `F4` for Contra, `F5` for Payment, `F6` for Receipt, `F7` for Journal.
2. **Dual Date Picker:**
   - Entering BS Date automatically populates AD Date, and vice versa. Absolutely mandatory.
3. **The Day Book View:**
   - A primary screen showing today's chronological transactions, cash in/out, and closing cash balance at the top of the dashboard.
   - Filterable strictly by BS Date Ranges (Mangsir 1 to Mangsir 30).

---

# 10. FINAL IMPLEMENTATION WORKFLOW

1. **DB Setup:** Run the provided SQL migrations for `acc_accounts`, `acc_vouchers`, `acc_ledger_postings`.
2. **Seed COA:** Seed a standard Nepal Academic Chart of Accounts (Assets, Liabilities, Funds, Revenue, Expenses).
3. **Events Hookup:** Attach Listeners to `FeeCollected`, `ExpenseApproved`, and `SalaryPaid` events.
4. **Integration of Hybrid UI:** Implement the 9 HTML pages provided in the previous step into Laravel Blade, wiring them directly to the [Voucher](file:///c:/Apache24/htdocs/erp/app/Models/Voucher.php#9-42) and [LedgerPosting](file:///c:/Apache24/htdocs/erp/app/Models/LedgerPosting.php#8-30) models.
5. **Testing Edge Cases:** Test the Shrawan overlap explicitly using Nepcal package logic.
