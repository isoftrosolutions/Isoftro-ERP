<?php

namespace App\Http\Controllers\Admin;

use PDO;
use Exception;

class ExpenseController
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = getDBConnection();
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
        $limit = max(10, (int)($_GET['limit'] ?? 100));
        
        $stmt = $this->db->prepare("
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
        ");
        $stmt->execute([$this->tenantId, $limit]);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function store()
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        $expenseAccountId = $input['category_id'] ?? null; 
        $amount = (float)($input['amount'] ?? 0);
        $paymentMode = $input['payment_mode'] ?? 'cash';
        $tdsPercent = (float)($input['tds_percent'] ?? 0);
        $notes = $input['notes'] ?? 'Expense Booking';
        $date = $input['date'] ?? date('Y-m-d');

        if (!$expenseAccountId || $amount <= 0) {
            throw new Exception("Valid category and non-zero amount required.");
        }

        $this->db->beginTransaction();
        try {
            // Use the new AccountingService for robust bookkeeping
            $accountingService = new \App\Services\AccountingService();
            
            // Log the expense via Accounting Service
            $voucher = $accountingService->createExpenseVoucher(
                $this->tenantId,
                $expenseAccountId,
                $amount,
                $paymentMode,
                $date,
                $notes
            );
            
            // Also, record in the 'expenses' table to ensure data consistency as per audit report
            $stmtExp = $this->db->prepare("
                INSERT INTO expenses (
                    tenant_id, expense_category_id, amount, date_ad, date_bs, 
                    description, payment_method, status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $dateBs = \App\Helpers\DateUtils::adToBs($date);
            $userId = $_SESSION['userData']['id'] ?? null;
            
            $stmtExp->execute([
                $this->tenantId,
                $expenseAccountId,
                $amount,
                $date,
                $dateBs,
                $notes,
                strtolower($paymentMode),
                'approved', // Auto-approved when recorded via accounting
                $userId
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => "Expense logged and accounting voucher created.", 'voucher_no' => $voucher->voucher_no];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
