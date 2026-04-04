<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Support\Facades\DB;

class ExpenseController
{
    private $tenantId;

    public function __construct()
    {
        $this->tenantId = $_SESSION['userData']['tenant_id'] ?? null;
        if (!$this->tenantId && function_exists('getCurrentUser')) {
            $user = getCurrentUser();
            $this->tenantId = $user['tenant_id'] ?? null;
        }
        if (!$this->tenantId) throw new Exception("Unauthorized: Tenant ID missing");
    }

    public function handle()
    {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET' && $action === 'index') return $this->index();
        if ($method === 'POST' && $action === 'store') return $this->store();

        return ['success' => false, 'message' => "Action '{$action}' not supported via explicit API router."];
    }

    public function index()
    {
        $limit    = max(10, (int)($_GET['limit'] ?? 100));
        $tenantId = $this->tenantId;

        $data = DB::select("
            SELECT v.id, v.voucher_no, v.date, v.narration as notes, v.status, p.amount as total_amount
            FROM acc_vouchers v
            JOIN (
                SELECT voucher_id, SUM(debit) as amount FROM acc_ledger_postings p
                JOIN acc_accounts a ON p.account_id = a.id
                WHERE a.type = 'expense'
                GROUP BY voucher_id
            ) p ON p.voucher_id = v.id
            WHERE v.tenant_id = ? AND v.deleted_at IS NULL AND v.type = 'payment'
            ORDER BY v.date DESC, v.id DESC
            LIMIT ?
        ", [$tenantId, $limit]);

        return ['success' => true, 'data' => $data];
    }

    public function store()
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $expenseAccountId = $input['category_id'] ?? null;
        $amount      = (float)($input['amount'] ?? 0);
        $paymentMode = $input['payment_mode'] ?? 'cash';
        $notes       = $input['notes'] ?? 'Expense Booking';
        $date        = $input['date'] ?? date('Y-m-d');

        if (!$expenseAccountId || $amount <= 0) {
            throw new Exception("Valid category and non-zero amount required.");
        }

        $tenantId = $this->tenantId;

        // Single DB::transaction() covers both the accounting voucher (inner savepoint)
        // and the expenses table insert. If either fails, everything rolls back together.
        $voucher = DB::transaction(function () use ($tenantId, $expenseAccountId, $amount, $paymentMode, $date, $notes) {
            $accountingService = new \App\Services\AccountingService();

            $voucher = $accountingService->createExpenseVoucher(
                $tenantId,
                $expenseAccountId,
                $amount,
                $paymentMode,
                $date,
                $notes
            );

            DB::table('expenses')->insert([
                'tenant_id'           => $tenantId,
                'expense_category_id' => $expenseAccountId,
                'amount'              => $amount,
                'date_ad'             => $date,
                'date_bs'             => \App\Helpers\DateUtils::adToBs($date),
                'description'         => $notes,
                'payment_method'      => strtolower($paymentMode),
                'status'              => 'approved',
                'created_by'          => $_SESSION['userData']['id'] ?? null,
                'created_at'          => now(),
            ]);

            return $voucher;
        });

        return ['success' => true, 'message' => 'Expense logged and accounting voucher created.', 'voucher_no' => $voucher->voucher_no];
    }
}
