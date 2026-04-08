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
                case 'income-expenditure':
                    return $this->incomeExpenditure();
                case 'balance-sheet':
                    return $this->balanceSheet();
                case 'sub-ledger':
                    return $this->subLedger();
                case 'day-book':
                    return $this->dayBook();
                case 'dashboard-stats':
                    return $this->dashboardStats();
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
            $debit = (float)($p['debit'] ?? 0);
            $credit = (float)($p['credit'] ?? 0);
            
            if ($debit < 0 || $credit < 0) {
                throw new Exception("Debit and Credit values cannot be negative.");
            }
            
            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if ($totalDebit == 0 && $totalCredit == 0) {
            throw new Exception("Voucher must have a non-zero value.");
        }

        if (abs($totalDebit - $totalCredit) > 0.001) {
            throw new Exception("Total Debits ($totalDebit) must equal Total Credits ($totalCredit)");
        }

        $this->db->beginTransaction();

        try {
            // Validate fiscal year belongs to tenant
            $stmt = $this->db->prepare("SELECT id FROM acc_fiscal_years WHERE id = ? AND tenant_id = ? AND is_active = 1");
            $stmt->execute([$input['fiscal_year_id'], $this->tenantId]);
            if (!$stmt->fetch()) {
                throw new Exception("Invalid or inactive fiscal year.");
            }

            // Validate all accounts belong to tenant
            $accountIds = array_filter(array_column($postings, 'account_id'));
            if (!empty($accountIds)) {
                $placeholders = str_repeat('?,', count($accountIds) - 1) . '?';
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM acc_accounts WHERE id IN ($placeholders) AND tenant_id = ?");
                $params = array_merge($accountIds, [$this->tenantId]);
                $stmt->execute($params);
                $validCount = $stmt->fetchColumn();

                if ($validCount < count(array_unique($accountIds))) {
                    throw new Exception("One or more accounts do not belong to this tenant or are invalid.");
                }
            }

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

            $stmt = $this->db->prepare("INSERT INTO acc_ledger_postings (tenant_id, voucher_id, account_id, debit, credit, description) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($postings as $p) {
                if (($p['debit'] ?? 0) > 0 || ($p['credit'] ?? 0) > 0) {
                    $stmt->execute([
                        $this->tenantId,
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
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $dateFilter = "";
        $actualParams = [];
        
        if ($dateFrom) {
            $dateFilter .= " AND v.date >= ?";
            $actualParams[] = $dateFrom;
        }
        if ($dateTo) {
            $dateFilter .= " AND v.date <= ?";
            $actualParams[] = $dateTo;
        }
        $actualParams[] = $this->tenantId;

        // 1. Calculate Historical Roll-Forward (before $dateFrom)
        $historicalData = [];
        if ($dateFrom) {
            $sqlHistorical = "SELECT p.account_id, SUM(p.debit) as hist_debit, SUM(p.credit) as hist_credit 
                              FROM acc_ledger_postings p 
                              JOIN acc_vouchers v ON p.voucher_id = v.id 
                              WHERE v.deleted_at IS NULL AND v.status IN ('posted', 'approved') AND v.date < ? AND v.tenant_id = ?
                              GROUP BY p.account_id";
            $stmtHist = $this->db->prepare($sqlHistorical);
            $stmtHist->execute([$dateFrom, $this->tenantId]);
            while ($r = $stmtHist->fetch(PDO::FETCH_ASSOC)) {
                $historicalData[$r['account_id']] = $r;
            }
        }

        // 2. Fetch Period Transactions
        $sql = "SELECT a.id, a.code, a.name, a.type, a.opening_balance,
                       COALESCE(SUM(pv.debit), 0) as period_debit, 
                       COALESCE(SUM(pv.credit), 0) as period_credit 
                FROM acc_accounts a 
                LEFT JOIN (
                    SELECT p.account_id, p.debit, p.credit 
                    FROM acc_ledger_postings p
                    JOIN acc_vouchers v ON p.voucher_id = v.id 
                    WHERE v.deleted_at IS NULL AND v.status IN ('posted', 'approved') $dateFilter
                ) pv ON a.id = pv.account_id
                WHERE a.tenant_id = ? AND a.deleted_at IS NULL
                GROUP BY a.id, a.code, a.name, a.type, a.opening_balance
                ORDER BY a.code ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($actualParams);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $hist = $historicalData[$row['id']] ?? ['hist_debit' => 0, 'hist_credit' => 0];
            $ob = (float)$row['opening_balance'];
            
            if (in_array($row['type'], ['asset', 'expense'])) {
                $ob = $ob + (float)$hist['hist_debit'] - (float)$hist['hist_credit'];
            } else {
                $ob = $ob + (float)$hist['hist_credit'] - (float)$hist['hist_debit'];
            }
            $row['opening_balance'] = $ob;

            $pd = (float)$row['period_debit'];
            $pc = (float)$row['period_credit'];
            
            if (in_array($row['type'], ['asset', 'expense'])) {
                $net = $ob + $pd - $pc;
                $row['total_debit'] = $net > 0 ? $net : 0;
                $row['total_credit'] = $net < 0 ? abs($net) : 0;
            } else {
                $net = $ob + $pc - $pd;
                $row['total_credit'] = $net > 0 ? $net : 0;
                $row['total_debit'] = $net < 0 ? abs($net) : 0;
            }
        }

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
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(10, (int)($_GET['limit'] ?? 50));
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        if (!$accountId) throw new Exception("Account ID required");

        $stmt = $this->db->prepare("SELECT * FROM acc_accounts WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$accountId, $this->tenantId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) throw new Exception("Account not found");

        $dateFilter = "";
        $params = [$accountId];
        
        if ($dateFrom) {
            $dateFilter .= " AND v.date >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $dateFilter .= " AND v.date <= ?";
            $params[] = $dateTo;
        }

        $offset = ($page - 1) * $limit;
        
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM acc_ledger_postings p JOIN acc_vouchers v ON p.voucher_id = v.id WHERE p.account_id = ? AND v.deleted_at IS NULL AND v.status IN ('posted', 'approved') $dateFilter");
        $stmtCount->execute($params);
        $totalItems = $stmtCount->fetchColumn();
        
        // B/F (Brought Forward) Before the Period
        $bfParams = [$accountId];
        $bfQuery = "SELECT COALESCE(SUM(p.debit), 0) as hist_debit, COALESCE(SUM(p.credit), 0) as hist_credit 
                    FROM acc_ledger_postings p
                    JOIN acc_vouchers v ON p.voucher_id = v.id
                    WHERE p.account_id = ? AND v.deleted_at IS NULL AND v.status IN ('posted', 'approved')";
        if ($dateFrom) {
            $bfQuery .= " AND v.date < ?";
            $bfParams[] = $dateFrom;
        } else {
            $bfQuery .= " AND 1=0 "; 
        }
        $stmtBF = $this->db->prepare($bfQuery);
        $stmtBF->execute($bfParams);
        $bfRows = $stmtBF->fetch(PDO::FETCH_ASSOC);
        
        $ob = (float)$account['opening_balance'];
        $bfDebit = (float)$bfRows['hist_debit'];
        $bfCredit = (float)$bfRows['hist_credit'];
        
        $broughtForward = $ob + (in_array($account['type'], ['asset', 'expense']) ? ($bfDebit - $bfCredit) : ($bfCredit - $bfDebit));

        // B/F Up to the specific page offset
        $offsetSumDebit = 0;
        $offsetSumCredit = 0;
        if ($offset > 0) {
            $offsetSql = "SELECT COALESCE(SUM(off_debit), 0) as off_debit, COALESCE(SUM(off_credit), 0) as off_credit FROM (
                SELECT p.debit as off_debit, p.credit as off_credit FROM acc_ledger_postings p 
                JOIN acc_vouchers v ON p.voucher_id = v.id 
                WHERE p.account_id = ? AND v.deleted_at IS NULL AND v.status IN ('posted', 'approved') $dateFilter
                ORDER BY v.date ASC, v.id ASC LIMIT $offset
            ) t";
            $stmtOff = $this->db->prepare($offsetSql);
            $stmtOff->execute($params);
            $offRows = $stmtOff->fetch(PDO::FETCH_ASSOC);
            $offsetSumDebit = (float)$offRows['off_debit'];
            $offsetSumCredit = (float)$offRows['off_credit'];
        }
        
        $broughtForwardPage = $broughtForward + (in_array($account['type'], ['asset', 'expense']) ? ($offsetSumDebit - $offsetSumCredit) : ($offsetSumCredit - $offsetSumDebit));

        $stmtPostings = $this->db->prepare("SELECT p.*, v.voucher_no, v.date, v.type as voucher_type, v.narration FROM acc_ledger_postings p JOIN acc_vouchers v ON p.voucher_id = v.id WHERE p.account_id = ? AND v.deleted_at IS NULL AND v.status IN ('posted', 'approved') $dateFilter ORDER BY v.date ASC, v.id ASC LIMIT $limit OFFSET $offset");
        $stmtPostings->execute($params);
        $postings = $stmtPostings->fetchAll(PDO::FETCH_ASSOC);

        $runningBalance = $broughtForwardPage;
        foreach ($postings as &$post) {
            $d = (float)$post['debit'];
            $c = (float)$post['credit'];
            $runningBalance += in_array($account['type'], ['asset', 'expense']) ? ($d - $c) : ($c - $d);
            $post['running_balance'] = $runningBalance;
        }

        return [
            'success' => true,
            'account' => $account,
            'brought_forward_period' => $broughtForward,
            'brought_forward_page' => $broughtForwardPage,
            'postings' => $postings,
            'pagination' => ['total' => $totalItems, 'page' => $page, 'limit' => $limit, 'total_pages' => ceil($totalItems / $limit)]
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

    /**
     * Report: Day Book
     */
    public function dayBook()
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        $stmt = $this->db->prepare("SELECT v.*, u.name as creator_name 
            FROM acc_vouchers v 
            LEFT JOIN users u ON v.created_by = u.id
            WHERE v.tenant_id = ? AND v.deleted_at IS NULL AND v.status IN ('posted', 'approved') AND v.date BETWEEN ? AND ?
            ORDER BY v.date DESC, v.id DESC");
        $stmt->execute([$this->tenantId, $dateFrom, $dateTo]);
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($vouchers as &$voucher) {
            $stmt = $this->db->prepare("SELECT p.*, a.name as account_name FROM acc_ledger_postings p JOIN acc_accounts a ON p.account_id = a.id WHERE p.voucher_id = ?");
            $stmt->execute([$voucher['id']]);
            $voucher['postings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return ['success' => true, 'data' => $vouchers];
    }

    /**
     * Report: Dashboard Stats
     */
    public function dashboardStats()
    {
        // Get Active Fiscal Year
        $stmtFy = $this->db->prepare("SELECT id FROM acc_fiscal_years WHERE tenant_id = ? AND is_active = 1 LIMIT 1");
        $stmtFy->execute([$this->tenantId]);
        $activeFyId = $stmtFy->fetchColumn();

        $fyFilter = $activeFyId ? " AND v.fiscal_year_id = " . (int)$activeFyId : "";

        $sql = "SELECT a.id, a.type, a.nature, a.name, a.opening_balance,
                       COALESCE(SUM(pv.debit), 0) as period_debit,
                       COALESCE(SUM(pv.credit), 0) as period_credit
                FROM acc_accounts a
                LEFT JOIN (
                    SELECT p.account_id, p.debit, p.credit
                    FROM acc_ledger_postings p
                    JOIN acc_vouchers v ON p.voucher_id = v.id
                    WHERE v.deleted_at IS NULL AND v.status IN ('posted', 'approved') {$fyFilter}
                ) pv ON a.id = pv.account_id
                WHERE a.tenant_id = ? AND a.deleted_at IS NULL AND a.is_group = 0
                GROUP BY a.id, a.type, a.nature, a.name, a.opening_balance";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->tenantId]);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $cashBank = 0;
        $ar = 0;
        $ap = 0;
        $income = 0;
        $expense = 0;

        foreach ($accounts as $row) {
            $nature = $row['nature'] ?? 'GENERAL';
            $ob = (float)$row['opening_balance'];
            $pd = (float)$row['period_debit'];
            $pc = (float)$row['period_credit'];

            if ($row['type'] === 'asset') {
                $bal = $ob + $pd - $pc;
                if ($nature === 'CASH' || $nature === 'BANK') {
                    $cashBank += $bal;
                } elseif ($nature === 'AR') {
                    $ar += $bal;
                }
            } elseif ($row['type'] === 'liability') {
                $bal = $ob + $pc - $pd;
                if ($nature === 'AP') {
                    $ap += $bal;
                }
            } elseif ($row['type'] === 'income') {
                $income += ($ob + $pc - $pd);
            } elseif ($row['type'] === 'expense') {
                $expense += ($ob + $pd - $pc);
            }
        }

        $stmt = $this->db->prepare("SELECT v.id, v.voucher_no, v.date, v.type, v.narration 
            FROM acc_vouchers v 
            WHERE v.tenant_id = ? AND v.deleted_at IS NULL AND v.status IN ('posted', 'approved')
            ORDER BY v.date DESC, v.id DESC LIMIT 5");
        $stmt->execute([$this->tenantId]);
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recent as &$voucher) {
            $stmt = $this->db->prepare("SELECT SUM(debit) as amount FROM acc_ledger_postings WHERE voucher_id = ?");
            $stmt->execute([$voucher['id']]);
            $voucher['amount'] = $stmt->fetchColumn() ?: 0;
        }

        return [
            'success' => true,
            'data' => [
                'cash_bank' => $cashBank,
                'accounts_receivable' => $ar,
                'accounts_payable' => $ap,
                'total_income' => $income,
                'total_expense' => $expense,
                'recent_transactions' => $recent
            ]
        ];
    }

    /**
     * Report: Income & Expenditure Statement
     */
    public function incomeExpenditure()
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-01-01');
        $dateTo = $_GET['date_to'] ?? date('Y-12-31');

        $sql = "SELECT
                    a.code,
                    a.name as account_name,
                    a.type,
                    SUM(COALESCE(pv.credit, 0)) as total_credit,
                    SUM(COALESCE(pv.debit, 0)) as total_debit,
                    CASE
                        WHEN a.type = 'income' THEN SUM(COALESCE(pv.credit, 0)) - SUM(COALESCE(pv.debit, 0))
                        ELSE SUM(COALESCE(pv.debit, 0)) - SUM(COALESCE(pv.credit, 0))
                    END as net_balance
                FROM acc_accounts a
                LEFT JOIN (
                    SELECT lp.account_id, lp.debit, lp.credit
                    FROM acc_ledger_postings lp
                    JOIN acc_vouchers v ON lp.voucher_id = v.id
                    WHERE v.deleted_at IS NULL
                      AND v.status IN ('posted', 'approved')
                      AND v.tenant_id = ?
                      AND v.date BETWEEN ? AND ?
                ) pv ON a.id = pv.account_id
                WHERE a.tenant_id = ?
                    AND a.deleted_at IS NULL
                    AND a.type IN ('income', 'expense')
                    AND a.is_group = 0
                GROUP BY a.id, a.code, a.name, a.type
                ORDER BY a.type, a.code";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->tenantId, $dateFrom, $dateTo, $this->tenantId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalIncome = 0;
        $totalExpense = 0;
        foreach ($data as $row) {
            if ($row['type'] === 'income') $totalIncome += $row['net_balance'];
            else $totalExpense += $row['net_balance'];
        }

        return [
            'success' => true,
            'data' => [
                'items' => $data,
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'surplus_deficit' => $totalIncome - $totalExpense
            ]
        ];
    }

    /**
     * Report: Balance Sheet
     */
    public function balanceSheet()
    {
        $dateTo = $_GET['date_to'] ?? date('Y-12-31');

        $sql = "SELECT a.id, a.code, a.name, a.type, a.opening_balance,
                       COALESCE(SUM(lp.debit), 0) as total_debit, 
                       COALESCE(SUM(lp.credit), 0) as total_credit
                FROM acc_accounts a
                LEFT JOIN acc_ledger_postings lp ON a.id = lp.account_id
                LEFT JOIN acc_vouchers v ON lp.voucher_id = v.id
                WHERE a.tenant_id = ? AND a.type IN ('asset', 'liability', 'equity')
                  AND (v.status IN ('posted', 'approved') OR v.id IS NULL)
                  AND (v.date <= ? OR v.date IS NULL)
                GROUP BY a.id, a.code, a.name, a.type, a.opening_balance
                ORDER BY a.type, a.code";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->tenantId, $dateTo]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $assets = [];
        $liabilities = [];
        $totalAssets = 0;
        $totalLiabilities = 0;

        foreach ($rows as $row) {
            $ob = (float)$row['opening_balance'];
            $dr = (float)$row['total_debit'];
            $cr = (float)$row['total_credit'];
            
            if ($row['type'] === 'asset') {
                $balance = $ob + $dr - $cr;
                $assets[] = array_merge($row, ['balance' => $balance]);
                $totalAssets += $balance;
            } else {
                $balance = $ob + $cr - $dr;
                $liabilities[] = array_merge($row, ['balance' => $balance]);
                $totalLiabilities += $balance;
            }
        }

        return [
            'success' => true,
            'data' => [
                'assets' => $assets,
                'liabilities' => $liabilities,
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'net_worth' => $totalAssets - $totalLiabilities
            ]
        ];
    }

    /**
     * Report: Sub-Ledger (Student, Vendor, etc.)
     */
    public function subLedger()
    {
        $type = $_GET['sub_ledger_type'] ?? 'student';
        $id = $_GET['sub_ledger_id'] ?? null;
        $dateFrom = $_GET['date_from'] ?? date('Y-01-01');
        $dateTo = $_GET['date_to'] ?? date('Y-12-31');

        if (!$id) throw new Exception("Sub-ledger ID required");

        $sql = "SELECT v.date, v.voucher_no, v.type as voucher_type,
                       lp.debit, lp.credit, lp.description,
                       a.name as account_name
                FROM acc_ledger_postings lp
                JOIN acc_vouchers v ON lp.voucher_id = v.id
                JOIN acc_accounts a ON lp.account_id = a.id
                WHERE lp.tenant_id = ? 
                    AND lp.sub_ledger_type = ? 
                    AND lp.sub_ledger_id = ?
                    AND v.status IN ('posted', 'approved')
                    AND v.date BETWEEN ? AND ?
                ORDER BY v.date, v.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->tenantId, $type, $id, $dateFrom, $dateTo]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => $data
        ];
    }

    private function getInput()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        return $input ?: $_POST;
    }
}
