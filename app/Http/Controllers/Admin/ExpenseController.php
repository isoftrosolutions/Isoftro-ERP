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
            // Find Active Fiscal Year
            $stmtFy = $this->db->prepare("SELECT id FROM acc_fiscal_years WHERE tenant_id = ? AND is_active = 1 LIMIT 1");
            $stmtFy->execute([$this->tenantId]);
            $fyId = $stmtFy->fetchColumn();
            if (!$fyId) throw new Exception("No active fiscal year found.");

            // Determine Asset (Credit) Account
            $paymentModeStr = strtolower($paymentMode) === 'bank' ? 'Bank' : 'Cash';
            $stmtAsset = $this->db->prepare("SELECT id FROM acc_accounts WHERE tenant_id = ? AND type = 'asset' AND name LIKE ? LIMIT 1");
            $stmtAsset->execute([$this->tenantId, "%$paymentModeStr%"]);
            $assetAccountId = $stmtAsset->fetchColumn();

            if (!$assetAccountId) {
                // Auto create Cash/Bank
                $name = $paymentModeStr === 'Bank' ? 'Bank Account' : 'Cash in Hand';
                $stmtInst = $this->db->prepare("INSERT INTO acc_accounts (tenant_id, name, type, is_group, opening_balance, created_at) VALUES (?, ?, 'asset', 0, 0, NOW())");
                $stmtInst->execute([$this->tenantId, $name]);
                $assetAccountId = $this->db->lastInsertId();
            }

            // Create Voucher
            $voucherNo = 'EXP-' . time() . rand(10,99);
            $stmtV = $this->db->prepare("INSERT INTO acc_vouchers (tenant_id, fiscal_year_id, voucher_no, date, type, narration, status, created_by, created_at) VALUES (?, ?, ?, ?, 'payment', ?, 'approved', ?, NOW())");
            // created_by might be missing in session, fallback to NULL
            $userId = isset($_SESSION['userData']['id']) ? $_SESSION['userData']['id'] : null;
            $stmtV->execute([
                $this->tenantId, $fyId, $voucherNo, $date, $notes, $userId
            ]);
            $voucherId = $this->db->lastInsertId();

            $stmtPost = $this->db->prepare("INSERT INTO acc_ledger_postings (voucher_id, account_id, debit, credit, description) VALUES (?, ?, ?, ?, ?)");

            if ($tdsPercent > 0) {
                // TDS Liability Account creation if absent
                $stmtLiab = $this->db->prepare("SELECT id FROM acc_accounts WHERE tenant_id = ? AND type = 'liability' AND name LIKE '%TDS Payable%' LIMIT 1");
                $stmtLiab->execute([$this->tenantId]);
                $tdsAccountId = $stmtLiab->fetchColumn();
                
                if (!$tdsAccountId) {
                    $stmtInstTds = $this->db->prepare("INSERT INTO acc_accounts (tenant_id, name, type, is_group, opening_balance, created_at) VALUES (?, 'TDS Payable', 'liability', 0, 0, NOW())");
                    $stmtInstTds->execute([$this->tenantId]);
                    $tdsAccountId = $this->db->lastInsertId();
                }

                $tdsAmount = round($amount * ($tdsPercent / 100), 2);
                $netAmount = round($amount - $tdsAmount, 2);

                // DEBIT Full Gross Expense
                $stmtPost->execute([$voucherId, $expenseAccountId, $amount, 0, $notes]);
                // CREDIT Asset (Net payout)
                $stmtPost->execute([$voucherId, $assetAccountId, 0, $netAmount, "Payout for " . $notes]);
                // CREDIT Liability (TDS to be paid later)
                $stmtPost->execute([$voucherId, $tdsAccountId, 0, $tdsAmount, "TDS Deducted @ {$tdsPercent}%"]);
            } else {
                // Standard 2-leg entry
                $stmtPost->execute([$voucherId, $expenseAccountId, $amount, 0, $notes]);
                $stmtPost->execute([$voucherId, $assetAccountId, 0, $amount, $notes]);
            }

            $this->db->commit();
            return ['success' => true, 'message' => "Expense logged successfully.", 'voucher_no' => $voucherNo];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
