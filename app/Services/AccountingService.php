<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Voucher;
use App\Models\LedgerPosting;
use App\Models\FiscalYear;
use App\Helpers\DateUtils;
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
                'date_bs' => DateUtils::adToBs($paymentDate),
                'type' => 'receipt',
                'narration' => $narration ?? "Fee received from student ID: $studentId",
                'fiscal_year_id' => $this->getActiveFiscalYear($tenantId),
                'status' => 'approved',
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
                'description' => $voucher->narration,
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
                'description' => "Fee credit for student ID: $studentId",
            ]);

            // Note: In some systems, you might credit Fee Income directly if not using Receivable clearing.
            // But following the audit report: Debit: Cash, Credit: Student Receivable (which was already debited on invoice)

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
                'date_bs' => DateUtils::adToBs($date),
                'type' => 'payment',
                'narration' => $narration,
                'fiscal_year_id' => $this->getActiveFiscalYear($tenantId),
                'status' => 'approved',
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
                'description' => $narration,
            ]);

            // Credit: Cash/Bank
            LedgerPosting::create([
                'voucher_id' => $voucher->id,
                'account_id' => $cashAccount->id,
                'tenant_id' => $tenantId,
                'debit' => 0,
                'credit' => $amount,
                'description' => $narration,
            ]);

            Log::info("Expense payment voucher created: $voucherNo for amount $amount");

            return $voucher;
        });
    }

    /**
     * Get Cash or Bank account based on payment method.
     * Uses firstOrCreate keyed by (tenant_id, code) to prevent duplicate accounts
     * under concurrent requests.
     */
    private function getCashAccount($tenantId, $paymentMethod)
    {
        $isBank = in_array($paymentMethod, ['bank', 'bank_transfer', 'cheque']);
        $code   = $isBank ? '102' : '101';
        $name   = $isBank ? 'Bank Account' : 'Cash in Hand';

        return Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => $code],
            [
                'name'            => $name,
                'type'            => 'asset',
                'is_group'        => false,
                'opening_balance' => 0,
                'balance_type'    => 'dr',
                'is_system'       => true,
                'status'          => 'active',
            ]
        );
    }

    /**
     * Get Student Receivable account.
     */
    private function getStudentReceivableAccount($tenantId)
    {
        return Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '103'],
            [
                'name'            => 'Student Receivable',
                'type'            => 'asset',
                'is_group'        => false,
                'opening_balance' => 0,
                'balance_type'    => 'dr',
                'is_system'       => true,
                'status'          => 'active',
            ]
        );
    }

    /**
     * Get Fee Income account.
     */
    private function getFeeIncomeAccount($tenantId)
    {
        return Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '401'],
            [
                'name'            => 'General Fee Income',
                'type'            => 'income',
                'is_group'        => false,
                'opening_balance' => 0,
                'balance_type'    => 'cr',
                'is_system'       => true,
                'status'          => 'active',
            ]
        );
    }

    /**
     * Get Expense account by category.
     * Tries to match a tenant expense account by category name; falls back to
     * the first expense account; creates a General Expenses account if none exist.
     */
    private function getExpenseAccount($tenantId, $categoryId)
    {
        $category    = DB::table('expense_categories')->where('id', $categoryId)->first();
        $accountName = $category->name ?? 'General Expenses';

        $account = Account::where('tenant_id', $tenantId)
            ->where('name', 'like', "%$accountName%")
            ->where('type', 'expense')
            ->first();

        if (!$account) {
            $account = Account::where('tenant_id', $tenantId)
                ->where('type', 'expense')
                ->first();
        }

        return $account ?? Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '501'],
            [
                'name'            => 'General Expenses',
                'type'            => 'expense',
                'is_group'        => false,
                'opening_balance' => 0,
                'balance_type'    => 'dr',
                'is_system'       => true,
                'status'          => 'active',
            ]
        );
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

        $nextNumber = 1;
        if ($lastVoucher) {
            $parts = explode('-', $lastVoucher->voucher_no);
            if (isset($parts[1])) {
                $nextNumber = intval($parts[1]) + 1;
            }
        }

        return sprintf("%s-%05d", $prefix, $nextNumber);
    }

    /**
     * Get active fiscal year ID — throws if none is configured
     */
    private function getActiveFiscalYear($tenantId)
    {
        $fy = FiscalYear::where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->first();

        if (!$fy) {
            throw new \RuntimeException(
                "No active fiscal year found for tenant {$tenantId}. " .
                "Configure one in Accounting → Fiscal Years before recording transactions."
            );
        }

        return $fy->id;
    }
}
