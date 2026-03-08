# 💳 Post-Payment Success Modal — Action Buttons & Behavior Specification
### HamroLabs Academic ERP | Fee Collection Module | Internal Dev Reference

---

> **Scope of This Document**
> This document covers **everything that happens AFTER** the payment transaction is committed to the database — starting from the Payment Success Response, through the Payment Details Page render, and into each of the three Action Buttons: **Print Receipt**, **Download PDF**, and **Send Email Receipt**.
> It does NOT cover pre-payment validation or database transaction internals. For those, refer to the Payment Flow Master Doc.

---

## 📍 Entry Point — Where This Document Begins

```
[DB Commit Successful]
        ↓
[Payment Success Response — API returns HTTP 200]
        ↓
[Redirect to Payment Details Page]
        ↓
[Show Payment Summary Modal / Page]
        ↓
        ★ YOU ARE HERE ★
[Action Buttons Rendered]
```

At this stage:
- The payment record exists in `payment_transactions` table with `status = 'completed'`
- The `fee_records` row has been updated to `status = 'paid'`
- A receipt number has been assigned (e.g., `RCP-000007`)
- The ledger entry has been written
- The operator is now looking at the Payment Summary screen

---

## 🖥️ The Payment Summary Screen (Pre-Button State)

Before any action button is clicked, the screen renders a **read-only payment summary panel** containing the following data fields:

| Field | Source | Example Value |
|---|---|---|
| Student Name | `students.full_name` | Ramesh Kumar Shrestha |
| Roll Number | `students.roll_number` | LKS-2026-0042 |
| Course / Batch | `batches.name` | Loksewa Prep — Batch A |
| Amount Paid | `payment_transactions.amount` | NPR 10,000.00 |
| Payment Method | `payment_transactions.payment_method` | Cash |
| Receipt Number | `payment_transactions.receipt_number` | RCP-000007 |
| Payment Date (AD) | `payment_transactions.payment_date` | 2026-03-06 |
| Payment Date (BS) | Converted on render | 2082-11-22 |
| Cashier Name | `users.full_name` via `recorded_by` | Sita Devi Poudel |
| Notes | `payment_transactions.notes` | Advance for Installment 3 |

> ⚠️ **This page is strictly READ-ONLY.** No edit buttons, no delete buttons. Any modification to a payment record must go through a separate Admin-level correction workflow with an audit reason.

---

## 🔘 The Action Buttons Panel

Directly below the payment summary, three buttons are rendered **in parallel** — they are independent of each other and can be triggered in any order or combination.

```
┌─────────────────────────────────────────────────────────────────┐
│              PAYMENT RECORDED SUCCESSFULLY ✅                    │
│  Receipt No: RCP-000007   |   Amount: NPR 10,000   |   CASH     │
│─────────────────────────────────────────────────────────────────│
│                                                                  │
│   [ 🖨️ Print Receipt ]  [ 📥 Download PDF ]  [ 📧 Send Email ]  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

| Button | Label | Icon | Trigger Type | Server Call? |
|---|---|---|---|---|
| A | Print Receipt | 🖨️ | Browser native | ❌ No |
| B | Download PDF | 📥 | Backend API | ✅ Yes |
| C | Send Email Receipt | 📧 | Backend API + Queue | ✅ Yes |

---

---

# 🖨️ BUTTON A — Print Receipt

## What It Does

Triggers the **browser's native print dialog**, scoped to a **print-optimized view** of the current payment summary. No server call is made. The page content is reformatted using CSS `@media print` rules to produce a clean A5-sized physical receipt.

## Trigger Mechanism

```javascript
// Alpine.js handler
printReceipt() {
    window.print();
}
```

No API call. No loading state. Instant response.

## CSS Print Scope

The print stylesheet hides everything except the receipt content block:

```css
@media print {
    body * { visibility: hidden; }
    #receipt-print-zone,
    #receipt-print-zone * { visibility: visible; }
    #receipt-print-zone { position: absolute; left: 0; top: 0; }
    .no-print { display: none !important; }
}
```

The `#receipt-print-zone` div contains only: Institute logo, receipt number, student details, amount, cashier, date (both BS and AD).

## Physical Receipt Layout (A5 Portrait)

```
┌────────────────────────────────┐
│  [INSTITUTE LOGO]              │
│  Nepal Loksewa Institute       │
│  Kathmandu, Nepal              │
│────────────────────────────────│
│  PAYMENT RECEIPT               │
│  Receipt No: RCP-000007        │
│  Date (AD): 2026-03-06         │
│  Date (BS): 2082-11-22         │
│────────────────────────────────│
│  Student : Ramesh K. Shrestha  │
│  Roll No : LKS-2026-0042       │
│  Course  : Loksewa Prep - A    │
│────────────────────────────────│
│  Amount  : NPR 10,000.00       │
│  Method  : Cash                │
│  Cashier : Sita Devi Poudel    │
│────────────────────────────────│
│  Notes: Advance Installment 3  │
│────────────────────────────────│
│  [Authorized Signature Line]   │
│  This is a computer-generated  │
│  receipt. No signature needed. │
└────────────────────────────────┘
```

## Success / Failure State

| State | UI Response |
|---|---|
| Browser print dialog opens | Normal — operator selects printer and prints |
| Browser print dialog blocked | Show tooltip: "Please allow popups for this site" |
| No printer connected | OS handles — nothing the app can do |

## Database Impact

> ❌ **None.** Print Receipt does NOT write anything to the database. It is purely a frontend browser action.

---

---

# 📥 BUTTON B — Download PDF

## What It Does

Sends a request to the backend to **generate a styled PDF receipt** using Python WeasyPrint, then streams the PDF file back to the browser as a **direct file download** (not an inline preview).

## Trigger Mechanism

```javascript
// Alpine.js handler
downloadPdf() {
    this.pdfLoading = true;
    window.location.href = `/fee/receipt/${this.receiptNumber}/download`;
    // OR use fetch with blob response for progress tracking
}
```

The URL opens a Laravel route that responds with a file download header.

## Full Backend Flow

```
[Click Download PDF]
        ↓
[GET /fee/receipt/{receipt_number}/download]
        ↓
[Laravel Controller: FeeReceiptController@download]
        ↓
[Check: Does receipt_path already exist in DB?]
        ↓
   ┌────┴────┐
  YES       NO
   ↓         ↓
[Serve      [Call Python WeasyPrint Service]
 existing        ↓
 stored     [Compile HTML template with payment data]
 PDF]            ↓
            [Inject: logo, student info, amount,
             receipt no, cashier, BS/AD date]
                 ↓
            [Generate PDF binary]
                 ↓
            [Store to: storage/app/public/receipts/RCP-000007.pdf]
                 ↓
            [UPDATE payment_transactions SET receipt_path =
             'receipts/RCP-000007.pdf' WHERE receipt_number = 'RCP-000007']
                 ↓
            [UPDATE fee_records SET receipt_path = ... WHERE ...]
        ↓
[Return PDF with headers:
 Content-Type: application/pdf
 Content-Disposition: attachment; filename="RCP-000007.pdf"]
        ↓
[Browser triggers file download]
```

## Laravel Controller Reference

```php
public function download(string $receiptNumber)
{
    $transaction = PaymentTransaction::where('receipt_number', $receiptNumber)
        ->where('tenant_id', auth()->user()->tenant_id)
        ->firstOrFail();

    // Generate or retrieve existing PDF
    if (!$transaction->receipt_path || !Storage::exists($transaction->receipt_path)) {
        $path = $this->receiptService->generatePdf($transaction);
        $transaction->update(['receipt_path' => $path]);
        FeeRecord::where('id', $transaction->fee_record_id)
            ->update(['receipt_path' => $path]);
    }

    return Storage::download(
        $transaction->receipt_path,
        'Receipt-' . $receiptNumber . '.pdf'
    );
}
```

## PDF Template Contents

The WeasyPrint HTML template must render these exact fields:

```
- Institute logo (tenant-specific, pulled from tenant settings)
- Institute name and address
- "OFFICIAL PAYMENT RECEIPT" heading
- Receipt Number (large, prominent)
- Payment Date in BOTH AD and BS formats
- Student full name
- Student roll number
- Course and batch name
- Amount paid (formatted: NPR X,XXX.XX)
- Payment method (Cash / Bank Transfer / Cheque)
- Cashier staff name
- Notes (if present)
- "Computer Generated Receipt" footer
- QR code (future Phase 3 — receipt verification URL)
```

## Button UI States

| State | Visual | Duration |
|---|---|---|
| Default | `📥 Download PDF` — enabled | — |
| Generating | `⏳ Generating...` spinner | Until response |
| Complete | Download starts automatically | Instant |
| Error | `❌ Download Failed. Retry?` | Until dismissed |

## Database Impact

| Table | Field Updated | Condition |
|---|---|---|
| `payment_transactions` | `receipt_path` | Only if null/missing |
| `fee_records` | `receipt_path` | Only if null/missing |

> ✅ After the first download, the PDF is cached on disk. Subsequent downloads serve the stored file — no re-generation needed.

---

---

# 📧 BUTTON C — Send Email Receipt

## What It Does

This is the most complex of the three actions. It:
1. Prepares an email payload with the student's registered email
2. Generates (or reuses) a PDF receipt and attaches it
3. Sends the email through the configured mail gateway (Mailgun)
4. Shows a live progress bar from 1% → 100% during the process
5. Displays a success confirmation or a descriptive error message

## Trigger Mechanism

```javascript
// Alpine.js handler
async sendEmailReceipt() {
    this.emailStatus = 'sending';
    this.emailProgress = 1;

    try {
        const response = await fetch(`/fee/receipt/${this.receiptNumber}/email`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            this.emailStatus = 'sent';
            this.emailProgress = 100;
        } else {
            this.emailStatus = 'error';
            this.emailError = data.message;
        }
    } catch (err) {
        this.emailStatus = 'error';
        this.emailError = 'Connection failed. Please try again.';
    }
}
```

## Full Backend Flow — Step by Step

```
[Click Send Email Receipt]
         ↓
─────────────────────────────────
STEP 1: PREPARE EMAIL REQUEST
─────────────────────────────────
[POST /fee/receipt/{receipt_number}/email]
         ↓
[Laravel Controller: FeeReceiptController@sendEmail]
         ↓
[Fetch student email from students.email]
         ↓
[Validate: Is email address present and valid?]
    ↓ YES              ↓ NO
[Continue]     [Return error: "No email address on file for this student"]

─────────────────────────────────
STEP 2: PROGRESS BAR STARTS (1%)
─────────────────────────────────
[Frontend shows progress bar animation]
[Progress is simulated on frontend while backend processes]
[1% → 30% → 60% → 90% → 100% on success response]

─────────────────────────────────
STEP 3: GENERATE PDF RECEIPT
─────────────────────────────────
[Check: Does receipt_path already exist and file is on disk?]
    ↓ YES                    ↓ NO
[Use existing PDF]    [Generate new PDF via WeasyPrint]
                              ↓
                      [Store to disk]
                      [Update receipt_path in DB]

─────────────────────────────────
STEP 4: ATTACH PDF TO EMAIL
─────────────────────────────────
[Retrieve PDF binary from storage]
         ↓
[Build Laravel Mailable: FeeReceiptMail]
         ↓
[Email subject: "Payment Receipt {RCP-000007} — {Institute Name}"]
[Email body: HTML template with student name, amount, receipt no]
[Attachment: Receipt-RCP-000007.pdf]

─────────────────────────────────
STEP 5: SEND EMAIL VIA API
─────────────────────────────────
[Dispatch: Mail::to($studentEmail)->send(new FeeReceiptMail($transaction))]
         ↓
[OR: Dispatch via queue for async: SendFeeReceiptEmail::dispatch($transaction)]
         ↓
[Mailgun API receives request]
         ↓
[Mailgun processes and delivers to student's inbox]

─────────────────────────────────
STEP 6: API RESPONSE RECEIVED
─────────────────────────────────
         ↓
    ┌────┴────┐
  SUCCESS   ERROR
    ↓         ↓
[Log to    [Log to
email_logs  email_logs
status=     status=
'delivered']['failed']
    ↓         ↓
[Progress  [Progress
 bar → 100%] bar stops]
    ↓         ↓
[Show Green [Show Red
 Confirmation Error Message]
 Banner]
```

## Email Template (HTML)

The email body rendered to the student must contain:

```
Subject: Payment Receipt RCP-000007 — Nepal Loksewa Institute

Dear Ramesh Kumar Shrestha,

Your payment has been successfully recorded.

  Receipt Number : RCP-000007
  Amount Paid    : NPR 10,000.00
  Payment Method : Cash
  Date (AD)      : 2026-03-06
  Date (BS)      : 2082-11-22
  Recorded By    : Sita Devi Poudel

Please find your official receipt attached as a PDF.

For any queries, contact us at:
Nepal Loksewa Institute | Kathmandu | 01-XXXXXXX

Thank you,
Nepal Loksewa Institute
```

## Progress Bar Specification

| Progress % | Stage |
|---|---|
| 1% | Request received by backend |
| 20% | Student email validated |
| 40% | PDF generated or retrieved |
| 65% | Email payload built with attachment |
| 85% | Email dispatched to Mailgun |
| 100% | Mailgun confirms acceptance |

> **Implementation Note:** Since Mailgun delivery confirmation is async, progress at 100% means **accepted by Mailgun**, NOT confirmed delivered to inbox. Final delivery confirmation comes via Mailgun webhook (Phase 2 enhancement).

## Button UI States

| State | Visual Feedback | Color |
|---|---|---|
| Default | `📧 Send Email Receipt` | Primary button |
| Sending | `⏳ Sending... [Progress Bar 1%→100%]` | Blue / animated |
| Success | `✅ Email Sent to ram***@gmail.com` | Green |
| Error — No Email | `❌ No email address on file for this student` | Red |
| Error — API Fail | `❌ Email delivery failed. Please retry.` | Red with Retry button |
| Error — Network | `❌ Connection error. Check internet.` | Red with Retry button |

> **Privacy Note:** The success message shows a **masked email address** (e.g., `ram***@gmail.com`) — never the full email — to protect student data from being visible on a shared Front Desk screen.

## Database Impact

| Table | Action | When |
|---|---|---|
| `payment_transactions` | `receipt_path` updated | If PDF was newly generated |
| `fee_records` | `receipt_path` updated | If PDF was newly generated |
| `email_logs` | New row inserted | Every attempt, success or failure |

### `email_logs` Table Schema (To Be Created)

```sql
CREATE TABLE email_logs (
    id          BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id   INT NOT NULL,
    student_id  INT NOT NULL,
    receipt_no  VARCHAR(20) NOT NULL,
    recipient   VARCHAR(255) NOT NULL,
    subject     VARCHAR(255),
    status      ENUM('sent', 'failed', 'bounced') DEFAULT 'sent',
    error_msg   TEXT NULL,
    sent_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_receipt (receipt_no),
    INDEX idx_student (tenant_id, student_id)
);
```

## Retry Logic

If the email fails, a **Retry** button appears. Clicking Retry re-triggers the same flow. The system does NOT auto-retry to prevent duplicate email delivery. Manual retry is intentional.

```javascript
retryEmail() {
    this.emailStatus = 'idle';
    this.emailProgress = 0;
    this.emailError = null;
    this.sendEmailReceipt(); // re-trigger
}
```

---

---

# 🔁 Combined Action Flow Summary

```
Payment Details Page Loaded
│
├─── [Print Receipt] ──────────────────► Browser print dialog
│                                        No DB change
│
├─── [Download PDF] ──────────────────► Generate/fetch PDF
│                                        Store file to disk
│                                        Update receipt_path in DB
│                                        Stream file to browser
│
└─── [Send Email Receipt] ────────────► Validate student email
                                         Generate/fetch PDF
                                         Build email + attach PDF
                                         Send via Mailgun
                                         Log to email_logs
                                         Show result to operator
```

---

# ⚙️ Implementation Checklist

Use this checklist to track what's built and what's pending:

### Print Receipt
- [ ] `@media print` CSS scoped to `#receipt-print-zone`
- [ ] All required fields inside print zone
- [ ] Institute logo renders correctly in print
- [ ] BS date displayed alongside AD date
- [ ] `window.print()` wired to button click
- [ ] Action buttons hidden in print view (`class="no-print"`)

### Download PDF
- [ ] WeasyPrint service connected and responsive
- [ ] HTML receipt template created with all fields
- [ ] PDF stored to `storage/app/public/receipts/`
- [ ] `receipt_path` written back to `payment_transactions`
- [ ] `receipt_path` written back to `fee_records`
- [ ] Subsequent downloads serve cached file (no re-generation)
- [ ] Loading state shown on button during generation
- [ ] Error state shown if generation fails
- [ ] Multi-tenant logo injection working correctly

### Send Email Receipt
- [ ] `email_logs` table created and migrated
- [ ] Mailgun credentials configured in `.env`
- [ ] `FeeReceiptMail` Mailable class created
- [ ] HTML email template created
- [ ] PDF attached to email payload
- [ ] Progress bar animates during send (1%→100%)
- [ ] Success banner shows masked email address
- [ ] Error messages specific to failure type
- [ ] Retry button functional
- [ ] Email log row inserted on every attempt
- [ ] Student email validation before sending
- [ ] Queue worker active for async email dispatch

---

# 📌 Notes for Developers

**1. Receipt Path Null Issue (Current Bug)**
All existing `payment_transactions` records have `receipt_path = null`. This means Download PDF will always trigger a fresh WeasyPrint call. Once implemented, the first download per receipt will generate and cache; all future calls serve the cached file.

**2. Email Before PDF Download**
If a user clicks Send Email before Download PDF, the system must still be able to generate the PDF internally. The email flow must never depend on the Download PDF button having been clicked first. Both flows independently generate the PDF if not yet cached.

**3. BS Date Conversion**
The system must convert all AD dates to BS (Bikram Sambat) for display on receipts. Use a PHP/JavaScript BS calendar library. Both dates must appear on the physical receipt, PDF receipt, and email body.

**4. Multi-Tenancy**
Every PDF must use the **tenant's own logo and institute name** — not a HamroLabs logo. The WeasyPrint template must accept `tenant_id` and pull logo from `tenant_settings` before rendering.

**5. Queue vs Synchronous Email**
For production, always dispatch email via Laravel Queue (`SendFeeReceiptEmail::dispatch()`). Synchronous email blocks the HTTP response and can timeout on slow connections (common in Nepal's network conditions). Ensure `queue:work` is running as a supervised daemon.

---

*Document Version: 1.0 | Module: Fee Collection | Sub-Module: Post-Payment Actions*
*Prepared for: HamroLabs Academic ERP Internal Development Team*
*Reference: Payment Flow Diagram (mermaid-diagram__3_.png)*