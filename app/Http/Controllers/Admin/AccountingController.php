<?php

namespace App\Http\Controllers\Admin;

use PDO;
use Exception;

class AccountingController
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = getDBConnection();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->tenantId = $_SESSION['userData']['tenant_id'] ?? null;
        
        if (!$this->tenantId) {
            // Ensure we are logged in
            if (function_exists('isLoggedIn') && isLoggedIn()) {
                $userData = $_SESSION['userData'] ?? null;
                $this->tenantId = $userData['tenant_id'] ?? null;
            }
        }

        if (!$this->tenantId) {
             throw new Exception("Unauthorized: Tenant ID missing");
        }
    }

    /**
     * Handle incoming request (for direct routing)
     */
    public function handle()
    {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];

        try {
            switch ($action) {
                case 'accounts':
                    return ($method === 'GET') ? $this->getAccounts() : $this->storeAccount();
                case 'vouchers':
                    return ($method === 'GET') ? $this->getVouchers() : $this->storeVoucher();
                case 'trial-balance':
                    return $this->trialBalance();
                case 'ledger':
                    return $this->ledger();
                case 'fiscal-years':
                    return $this->getFiscalYears();
                default:
                    throw new Exception("Action '{$action}' not found");
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get Chart of Accounts
     */
    public function getAccounts()
    {
        $stmt = $this->db->prepare("SELECT * FROM acc_accounts WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY code ASC");
        $stmt->execute([$this->tenantId]);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => $accounts
        ];
    }

    /**
     * Store a new account
     */
    public function storeAccount()
    {
        $input = $this->getInput();
        
        $sql = "INSERT INTO acc_accounts (tenant_id, name, type, parent_id, is_group, code, opening_balance, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $this->tenantId,
            $input['name'],
            $input['type'],
            $input['parent_id'] ?? null,
            $input['is_group'] ?? 0,
            $input['code'] ?? null,
            $input['opening_balance'] ?? 0
        ]);

        return [
            'success' => true,
            'id' => $this->db->lastInsertId(),
            'message' => 'Account created successfully'
        ];
    }

    /**
     * Get Vouchers
     */
    public function getVouchers()
    {
        $stmt = $this->db->prepare("SELECT * FROM acc_vouchers WHERE tenant_id = ? AND deleted_at IS NULL ORDER BY date DESC");
        $stmt->execute([$this->tenantId]);
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($vouchers as &$voucher) {
            $stmt = $this->db->prepare("SELECT p.*, a.name as account_name FROM acc_ledger_postings p JOIN acc_accounts a ON p.account_id = a.id WHERE p.voucher_id = ?");
            $stmt->execute([$voucher['id']]);
            $voucher['postings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [
            'success' => true,
            'data' => $vouchers
        ];
    }

    /**
     * Store a new voucher (Double Entry)
     */
    public function storeVoucher()
    {
        $input = $this->getInput();
        $postings = $input['postings'] ?? [];

        if (count($postings) < 2) {
            throw new Exception("Voucher must have at least two entries (debit and credit)");
        }

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($postings as $p) {
            $totalDebit += (float)($p['debit'] ?? 0);
            $totalCredit += (float)($p['credit'] ?? 0);
        }

        if (abs($totalDebit - $totalCredit) > 0.001) {
            throw new Exception("Total Debits ($totalDebit) must equal Total Credits ($totalCredit)");
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("INSERT INTO acc_vouchers (tenant_id, fiscal_year_id, voucher_no, date, type, narration, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, 'approved', ?, NOW())");
            $stmt->execute([
                $this->tenantId,
                $input['fiscal_year_id'],
                $input['voucher_no'],
                $input['date'],
                $input['type'],
                $input['narration'] ?? null,
                $_SESSION['userData']['id'] ?? null
            ]);
            $voucherId = $this->db->lastInsertId();

            $stmt = $this->db->prepare("INSERT INTO acc_ledger_postings (voucher_id, account_id, debit, credit, description) VALUES (?, ?, ?, ?, ?)");
            foreach ($postings as $p) {
                if (($p['debit'] ?? 0) > 0 || ($p['credit'] ?? 0) > 0) {
                    $stmt->execute([
                        $voucherId,
                        $p['account_id'],
                        $p['debit'] ?? 0,
                        $p['credit'] ?? 0,
                        $p['description'] ?? null
                    ]);
                }
            }

            $this->db->commit();
            return ['success' => true, 'id' => $voucherId, 'message' => 'Voucher saved successfully'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Report: Trial Balance
     */
    public function trialBalance()
    {
        $sql = "SELECT a.id, a.code, a.name, a.type, 
                       COALESCE(SUM(p.debit), 0) as total_debit, 
                       COALESCE(SUM(p.credit), 0) as total_credit 
                FROM acc_accounts a 
                LEFT JOIN acc_ledger_postings p ON a.id = p.account_id 
                WHERE a.tenant_id = ? AND a.deleted_at IS NULL
                GROUP BY a.id, a.code, a.name, a.type";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->tenantId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => $data
        ];
    }

    /**
     * Report: Ledger
     */
    public function ledger()
    {
        $accountId = $_GET['account_id'] ?? null;
        if (!$accountId) throw new Exception("Account ID required");

        $stmt = $this->db->prepare("SELECT * FROM acc_accounts WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$accountId, $this->tenantId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) throw new Exception("Account not found");

        $stmt = $this->db->prepare("SELECT p.*, v.voucher_no, v.date, v.type as voucher_type FROM acc_ledger_postings p JOIN acc_vouchers v ON p.voucher_id = v.id WHERE p.account_id = ? AND v.deleted_at IS NULL ORDER BY v.date ASC, v.id ASC");
        $stmt->execute([$accountId]);
        $postings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'account' => $account,
            'postings' => $postings
        ];
    }

    /**
     * Get Fiscal Years
     */
    public function getFiscalYears()
    {
        $stmt = $this->db->prepare("SELECT * FROM acc_fiscal_years WHERE tenant_id = ? ORDER BY is_active DESC, start_date DESC");
        $stmt->execute([$this->tenantId]);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    private function getInput()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        return $input ?: $_POST;
    }
}
