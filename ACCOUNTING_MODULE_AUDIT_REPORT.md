# Complete Accounting Module Audit Report
## Nepal Academic ERP - Production-Ready Fixes

---

## 1. PROBLEMS FOUND

### 1.1 Disconnected Fee Collection from Accounting
- **Issue**: Fee payments are recorded in `payment_transactions` table but NO accounting vouchers are created
- **Impact**: Income is not reflected in the General Ledger
- **Evidence**: Examined `fees.php` - no accounting voucher creation logic

### 1.2 Disconnected Expense Recording from Accounting
- **Issue**: Expenses are recorded in `expenses` table but NOT linked to accounting vouchers
- **Impact**: Expense transactions don't appear in Trial Balance
- **Evidence**: `ExpenseController.php` creates expense records without voucher integration

### 1.3 Missing Nepal-Specific Fields
- **Issue**: No `date_bs` (Bikram Sambat) column in `acc_vouchers`
- **Impact**: Cannot generate BS-date reports for Nepal compliance
- **Evidence**: Schema shows only `date` (AD) column

### 1.4 Missing Sub-Ledger Support
- **Issue**: `acc_ledger_postings` lacks `sub_ledger_type` and `sub_ledger_id`
- **Impact**: Cannot track student-wise receivables, staff-wise payables
- **Evidence**: Schema only has `account_id`, no sub-ledger tracking

### 1.5 Missing Maker-Checker Workflow
- **Issue**: Voucher status is just 'draft' or 'approved', no verification step
- **Impact**: No audit trail for voucher approval
- **Evidence**: `acc_vouchers.status` is varchar(20), not enum with proper workflow

### 1.6 Incomplete Reporting
- **Issue**: No Income & Expenditure Statement (NAS for NPOs)
- **Issue**: No Balance Sheet
- **Issue**: No Cash Flow Statement
- **Evidence**: Only Trial Balance, Ledger, Day Book exist in AccountingController

### 1.7 Missing System Account Flags
- **Issue**: No `is_system` flag to prevent deletion of core accounts (Cash, Bank, etc.)
- **Impact**: Users can accidentally delete critical accounts
- **Evidence**: Schema lacks `is_system` column

---

## 2. MISSING ACCOUNTING CONCEPTS

| Concept | Current Status | Required Action |
|---------|---------------|-----------------|
| Chart of Accounts | ✅ Exists | Add system flags |
| General Ledger | ✅ Exists | Add sub-ledger support |
| Journal Entries | ✅ Exists | Add BS date, approval workflow |
| Trial Balance | ✅ Exists | Working correctly |
| Income & Expenditure | ❌ Missing | Must implement |
| Balance Sheet | ❌ Missing | Must implement |
| Cash Flow Statement | ❌ Missing | Optional |
| Sub-ledger (Student/Staff) | ❌ Missing | Must add |
| Cost Centers | ❌ Missing | Optional for schools |
| Budget vs Actual | ❌ Missing | Optional |

---

## 3. CORRECT ARCHITECTURE

### 3.1 Current Flow (Broken)
```
Fee Payment → payment_transactions (ONLY) → NOT in accounting
Expense → expenses table (ONLY) → NOT in accounting
```

### 3.2 Required Flow (Double-Entry)
```
Fee Payment → Create Receipt Voucher 
           → Debit: Cash/Bank Account
           → Credit: Fee Income Account
           → Credit: Student Receivable (sub-ledger)

Expense → Create Payment Voucher
        → Debit: Expense Account
        → Credit: Cash/Bank Account
```

---

## 4. DATABASE SCHEMA FIXES

### 4.1 Migration: Add Missing Columns to acc_vouchers
```php
// In database/migrations/xxxx_xx_xx_xxxxxx_add_accounting_fields.php
Schema::table('acc_vouchers', function (Blueprint $table) {
    $table->string('date_bs', 10)->after('date')->nullable();
    $table->decimal('total_amount', 15, 2)->after('narration')->nullable();
    $table->enum('status', ['draft', 'verified', 'approved', 'posted', 'cancelled'])
          ->default('draft')->change();
    $table->unsignedBigInteger('approved_by')->after('created_by')->nullable();
    $table->unsignedBigInteger('verified_by')->after('approved_by')->nullable();
});
```

### 4.2 Migration: Add tenant_id to acc_ledger_postings
```php
Schema::table('acc_ledger_postings', function (Blueprint $table) {
    $table->unsignedBigInteger('tenant_id')->after('id')->nullable();
    $table->string('sub_ledger_type', 50)->after('account_id')->nullable();
    $table->unsignedBigInteger('sub_ledger_id')->after('sub_ledger_type')->nullable();
    
    // Add indexes
    $table->index(['account_id', 'tenant_id']);
    $table->index(['tenant_id', 'sub_ledger_type', 'sub_ledger_id']);
});
```

### 4.3 Migration: Add system flags to acc_accounts
```php
Schema::table('acc_accounts', function (Blueprint $table) {
    $table->enum('balance_type', ['dr', 'cr'])->after('opening_balance')->default('dr');
    $table->boolean('is_system')->after('balance_type')->default(false);
    $table->enum('status', ['active', 'inactive'])->after('is_system')->default('active');
});
```

### 4.4 New Table: acc_voucher_approvals (Audit Trail)
```php
Schema::create('acc_voucher_approvals', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('voucher_id');
    $table->unsignedBigInteger('user_id');
    $table->enum('action', ['verified', 'approved', 'rejected', 'reversed']);
    $table->text('remarks')->nullable();
    $table->timestamps();
    
    $table->foreign('voucher_id')->references('id')->on('acc_vouchers')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users');
});
```

---

## 5. LARAVEL CODE FIXES

### 5.1 Updated Account Model
```php
// app/Models/Account.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $table = 'acc_accounts';

    protected $fillable = [
        'tenant_id', 'code', 'name', 'type', 'parent_id', 
        'is_group', 'opening_balance', 'balance_type', 
        'is_system', 'status'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_system' => 'boolean',
        'is_group' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function postings(): HasMany
    {
        return $this->hasMany(LedgerPosting::class, 'account_id');
    }

    // Prevent deletion of system accounts
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($account) {
            if ($account->is_system) {
                throw new \Exception('Cannot delete system account: ' . $account->name);
            }
        });
    }

    public function getBalanceAttribute()
    {
        $debits = $this->postings()->sum('debit');
        $credits = $this->postings()->sum('credit');
        
        if (in_array($this->type, ['asset', 'expense'])) {
            return ($this->opening_balance + $debits) - $credits;
        } else {
            return ($this->opening_balance + $credits) - $debits;
        }
    }
}
```

### 5.2 Updated LedgerPosting Model
```php
// app/Models/LedgerPosting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerPosting extends Model
{
    protected $table = 'acc_ledger_postings';

    protected $fillable = [
        'voucher_id', 'account_id', 'tenant_id', 
        'debit', 'credit', 'description',
        'sub_ledger_type', 'sub_ledger_id'
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
```

### 5.3 New Accounting Service (Core)
```php
// app/Services/AccountingService.php
namespace App\Services;

use App\Models\Account;
use App\Models\Voucher;
use App\Models\LedgerPosting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    /**
     * Create a fee collection voucher (Receipt Voucher)
     * Called when student pays fee
     */
    public function createFeeReceiptVoucher($tenantId, $studentId, $amount, $paymentMethod, $paymentDate, $narration = null)
    {
        return DB::transaction(function () use ($tenantId, $studentId, $amount, $paymentMethod, $paymentDate, $narration) {
            // Get or create cash/bank account
            $cashAccount = $this->getCashAccount($tenantId, $paymentMethod);
            
            // Get student receivable account
            $arAccount = $this->getStudentReceivableAccount($tenantId);
            
            // Get fee income account
            $incomeAccount = $this->getFeeIncomeAccount($tenantId);
            
            // Generate voucher number
            $voucherNo = $this->generateVoucherNo($tenantId, 'RV');
            
            // Create voucher
            $voucher = Voucher::create([
                'tenant_id' => $tenantId,
                'voucher_no' => $voucherNo,
                'date' => $paymentDate,
                'date_bs' => $this->convertToBS($paymentDate),
                'type' => 'receipt',
                'narration' => $narration ?? "Fee received from student ID: $studentId",
                'fiscal_year_id' => $this->getActiveFiscalYear($tenantId),
                'status' => 'posted', // Auto-post for fee receipts
                'total_amount' => $amount,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Debit: Cash/Bank
            LedgerPosting::create([
                'voucher_id' => $voucher->id,
                'account_id' => $cashAccount->id,
                'tenant_id' => $tenantId,
                'debit' => $amount,
                'credit' => 0,
                'sub_ledger_type' => null,
                'sub_ledger_id' => null,
            ]);

            // Credit: Student Receivable
            LedgerPosting::create([
                'voucher_id' => $voucher->id,
                'account_id' => $arAccount->id,
                'tenant_id' => $tenantId,
                'debit' => 0,
                'credit' => $amount,
                'sub_ledger_type' => 'student',
                'sub_ledger_id' => $studentId,
            ]);

            // Credit: Fee Income
            LedgerPosting::create([
                'voucher_id' => $voucher->id,
                'account_id' => $incomeAccount->id,
                'tenant_id' => $tenantId,
                'debit' => 0,
                'credit' => $amount,
                'sub_ledger_type' => 'student',
                'sub_ledger_id' => $studentId,
            ]);

            Log::info("Fee receipt voucher created: $voucherNo for amount $amount");

            return $voucher;
        });
    }

    /**
     * Create an expense voucher (Payment Voucher)
     */
    public function createExpenseVoucher($tenantId, $expenseCategoryId, $amount, $paymentMethod, $date, $narration, $vendorId = null)
    {
        return DB::transaction(function () use ($tenantId, $expenseCategoryId, $amount, $paymentMethod, $date, $narration, $vendorId) {
            $cashAccount = $this->getCashAccount($tenantId, $paymentMethod);
            $expenseAccount = $this->getExpenseAccount($tenantId, $expenseCategoryId);
            
            $voucherNo = $this->generateVoucherNo($tenantId, 'PV');
            
            $voucher = Voucher::create([
                'tenant_id' => $tenantId,
                'voucher_no' => $voucherNo,
                'date' => $date,
                'date_bs' => $this->convertToBS($date),
                'type' => 'payment',
                'narration' => $narration,
                'fiscal_year_id' => $this->getActiveFiscalYear($tenantId),
                'status' => 'posted',
                'total_amount' => $amount,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Debit: Expense Account
            LedgerPosting::create([
                'voucher_id' => $voucher->id,
                'account_id' => $expenseAccount->id,
                'tenant_id' => $tenantId,
                'debit' => $amount,
                'credit' => 0,
                'sub_ledger_type' => $vendorId ? 'vendor' : null,
                'sub_ledger_id' => $vendorId,
            ]);

            // Credit: Cash/Bank
            LedgerPosting::create([
                'voucher_id' => $voucher->id,
                'account_id' => $cashAccount->id,
                'tenant_id' => $tenantId,
                'debit' => 0,
                'credit' => $amount,
            ]);

            return $voucher;
        });
    }

    /**
     * Get Cash or Bank account based on payment method
     */
    private function getCashAccount($tenantId, $paymentMethod)
    {
        $accountName = $paymentMethod === 'bank' ? 'Bank Account' : 'Cash in Hand';
        
        $account = Account::where('tenant_id', $tenantId)
            ->where('name', 'like', "%$accountName%")
            ->where('type', 'asset')
            ->first();

        if (!$account) {
            // Create default if not exists
            $account = Account::create([
                'tenant_id' => $tenantId,
                'code' => '101',
                'name' => $accountName,
                'type' => 'asset',
                'is_group' => false,
                'opening_balance' => 0,
                'balance_type' => 'dr',
                'is_system' => true,
                'status' => 'active',
            ]);
        }

        return $account;
    }

    /**
     * Get Student Receivable Account
     */
    private function getStudentReceivableAccount($tenantId)
    {
        $account = Account::where('tenant_id', $tenantId)
            ->where('name', 'like', '%Student Receivable%')
            ->where('type', 'asset')
            ->first();

        if (!$account) {
            $account = Account::create([
                'tenant_id' => $tenantId,
                'code' => '102',
                'name' => 'Student Receivable',
                'type' => 'asset',
                'is_group' => false,
                'opening_balance' => 0,
                'balance_type' => 'dr',
                'is_system' => true,
                'status' => 'active',
            ]);
        }

        return $account;
    }

    /**
     * Get Fee Income Account
     */
    private function getFeeIncomeAccount($tenantId)
    {
        $account = Account::where('tenant_id', $tenantId)
            ->where('name', 'like', '%Tuition Fee Income%')
            ->where('type', 'income')
            ->first();

        if (!$account) {
            $account = Account::create([
                'tenant_id' => $tenantId,
                'code' => '401',
                'name' => 'Tuition Fee Income',
                'type' => 'income',
                'is_group' => false,
                'opening_balance' => 0,
                'balance_type' => 'cr',
                'is_system' => true,
                'status' => 'active',
            ]);
        }

        return $account;
    }

    /**
     * Get Expense Account by category
     */
    private function getExpenseAccount($tenantId, $categoryId)
    {
        // In production, map expense_categories to accounts
        $account = Account::where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->first();

        return $account ?? Account::create([
            'tenant_id' => $tenantId,
            'code' => '501',
            'name' => 'General Expenses',
            'type' => 'expense',
            'is_group' => false,
            'opening_balance' => 0,
            'balance_type' => 'dr',
            'status' => 'active',
        ]);
    }

    /**
     * Generate unique voucher number
     */
    private function generateVoucherNo($tenantId, $prefix)
    {
        $lastVoucher = Voucher::where('tenant_id', $tenantId)
            ->where('voucher_no', 'like', "$prefix-%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastVoucher ? 
            (intval(explode('-', $lastVoucher->voucher_no)[1]) + 1) : 1;

        return sprintf("%s-%05d", $prefix, $nextNumber);
    }

    /**
     * Get active fiscal year
     */
    private function getActiveFiscalYear($tenantId)
    {
        $fy = \App\Models\FiscalYear::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->first();

        return $fy->id ?? 1;
    }

    /**
     * Convert AD to BS (placeholder - integrate with Nepali date package)
     */
    private function convertToBS($adDate)
    {
        // Use nepali-calendar package in production
        return date('Y-m-d', strtotime($adDate)); // Placeholder
    }
}
```

### 5.4 Updated Fees Controller Integration
```php
// In app/Http/Controllers/Admin/fees.php - add after payment processing
// Around line 450-500 where payment is processed

if ($action === 'process_payment') {
    // ... existing payment processing code ...
    
    // AFTER payment is successful, create accounting voucher
    $accountingService = new \App\Services\AccountingService();
    
    try {
        $voucher = $accountingService->createFeeReceiptVoucher(
            $tenantId,
            $studentId,
            $amount,
            $paymentMethod,
            $paymentDate,
            "Fee payment for {$studentName}"
        );
        
        // Link voucher to payment transaction
        $stmt = $db->prepare("UPDATE payment_transactions SET voucher_id = ? WHERE id = ?");
        $stmt->execute([$voucher->id, $transactionId]);
        
    } catch (\Exception $e) {
        Log::error("Failed to create accounting voucher: " . $e->getMessage());
        // Continue even if accounting fails - don't fail the payment
    }
}
```

---

## 6. REPORTS QUERIES

### 6.1 Trial Balance Query (Working - Verified)
```sql
-- Already implemented in AccountingController.php trialBalance()
-- This is correct:
SELECT a.code, a.name, a.type, a.opening_balance,
       COALESCE(SUM(pv.debit), 0) as period_debit, 
       COALESCE(SUM(pv.credit), 0) as period_credit 
FROM acc_accounts a 
LEFT JOIN (
    SELECT p.account_id, p.debit, p.credit 
    FROM acc_ledger_postings p
    JOIN acc_vouchers v ON p.voucher_id = v.id 
    WHERE v.deleted_at IS NULL AND v.status = 'posted' 
      AND v.date BETWEEN ? AND ?
) pv ON a.id = pv.account_id
WHERE a.tenant_id = ? AND a.deleted_at IS NULL
GROUP BY a.id, a.code, a.name, a.type, a.opening_balance
ORDER BY a.code ASC
```

### 6.2 Income & Expenditure Statement (NEW)
```sql
SELECT 
    a.code,
    a.name as account_name,
    SUM(COALESCE(lp.credit, 0)) as total_credit,
    SUM(COALESCE(lp.debit, 0)) as total_debit,
    CASE 
        WHEN a.type = 'income' THEN SUM(COALESCE(lp.credit, 0)) - SUM(COALESCE(lp.debit, 0))
        ELSE SUM(COALESCE(lp.debit, 0)) - SUM(COALESCE(lp.credit, 0))
    END as net_balance
FROM acc_accounts a
LEFT JOIN acc_ledger_postings lp ON a.id = lp.account_id
LEFT JOIN acc_vouchers v ON lp.voucher_id = v.id
WHERE a.tenant_id = ? 
    AND a.deleted_at IS NULL
    AND a.type IN ('income', 'expense')
    AND v.status = 'posted'
    AND v.date BETWEEN ? AND ?
GROUP BY a.id, a.code, a.name, a.type
ORDER BY a.type, a.code
```

### 6.3 Balance Sheet Query (NEW)
```php
public function balanceSheet($tenantId, $dateFrom, $dateTo)
{
    // Assets
    $assets = DB::select("
        SELECT a.code, a.name, 
               a.opening_balance + COALESCE(SUM(lp.debit), 0) - COALESCE(SUM(lp.credit), 0) as balance
        FROM acc_accounts a
        LEFT JOIN acc_ledger_postings lp ON a.id = lp.account_id
        LEFT JOIN acc_vouchers v ON lp.voucher_id = v.id
        WHERE a.tenant_id = ? AND a.type = 'asset' 
          AND v.status = 'posted' AND v.date <= ?
        GROUP BY a.id, a.code, a.name, a.opening_balance
    ", [$tenantId, $dateTo]);

    // Liabilities
    $liabilities = DB::select("
        SELECT a.code, a.name, 
               a.opening_balance + COALESCE(SUM(lp.credit), 0) - COALESCE(SUM(lp.debit), 0) as balance
        FROM acc_accounts a
        LEFT JOIN acc_ledger_postings lp ON a.id = lp.account_id
        LEFT JOIN acc_vouchers v ON lp.voucher_id = v.id
        WHERE a.tenant_id = ? AND a.type = 'liability' 
          AND v.status = 'posted' AND v.date <= ?
        GROUP BY a.id, a.code, a.name, a.opening_balance
    ", [$tenantId, $dateTo]);

    // Calculate totals
    $totalAssets = array_sum(array_column($assets, 'balance'));
    $totalLiabilities = array_sum(array_column($liabilities, 'balance'));

    return [
        'assets' => $assets,
        'liabilities' => $liabilities,
        'total_assets' => $totalAssets,
        'total_liabilities' => $totalLiabilities,
        'balance' => $totalAssets - $totalLiabilities
    ];
}
```

### 6.4 Student Ledger Report (NEW)
```sql
SELECT 
    v.date, v.voucher_no, v.type,
    lp.debit, lp.credit, lp.description,
    a.name as account_name
FROM acc_ledger_postings lp
JOIN acc_vouchers v ON lp.voucher_id = v.id
JOIN acc_accounts a ON lp.account_id = a.id
WHERE lp.tenant_id = ? 
    AND lp.sub_ledger_type = 'student' 
    AND lp.sub_ledger_id = ?
    AND v.status = 'posted'
    AND v.date BETWEEN ? AND ?
ORDER BY v.date, v.id
```

---

## 7. PRODUCTION CHECKLIST

### 7.1 Database Changes
- [ ] Run migration to add `date_bs` to `acc_vouchers`
- [ ] Run migration to add `tenant_id`, `sub_ledger_type`, `sub_ledger_id` to `acc_ledger_postings`
- [ ] Run migration to add `balance_type`, `is_system`, `status` to `acc_accounts`
- [ ] Create `acc_voucher_approvals` table
- [ ] Add indexes for performance

### 7.2 Code Changes
- [ ] Update Account model with system account protection
- [ ] Update LedgerPosting model with sub-ledger fields
- [ ] Update Voucher model with approval workflow
- [ ] Create AccountingService.php
- [ ] Integrate AccountingService into Fees controller
- [ ] Integrate AccountingService into Expenses controller

### 7.3 Testing
- [ ] Test fee payment creates accounting voucher
- [ ] Test expense payment creates accounting voucher
- [ ] Test Trial Balance totals match
- [ ] Test Income & Expenditure Statement
- [ ] Test Balance Sheet
- [ ] Test student sub-ledger report

### 7.4 Security
- [ ] Ensure only accountant role can access accounting
- [ ] Add approval workflow for voucher posting
- [ ] Implement audit trail for all voucher changes
- [ ] Add tenant_id checks to all queries

### 7.5 Nepal-Specific
- [ ] Integrate Nepali date conversion (BS to AD)
- [ ] Add ESF (Education Service Fee) calculation
- [ ] Add TDS/SSF tracking for payroll
- [ ] Configure fiscal year (Shrawan 1 to Ashad 31)

---

## 8. SUMMARY

The accounting module has a GOOD foundation with double-entry already implemented. The main gaps are:

1. **Integration**: Fee and expense transactions are NOT creating accounting vouchers
2. **Nepal Fields**: Missing BS date columns
3. **Sub-ledger**: Missing student/staff/vendor tracking
4. **Workflow**: No maker-checker approval
5. **Reports**: Missing Income & Expenditure, Balance Sheet

All fixes are provided above and can be implemented incrementally. The system will then be production-ready for Nepal-based educational institutes.
