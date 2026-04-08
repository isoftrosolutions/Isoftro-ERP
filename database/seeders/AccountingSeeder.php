<?php
/**
 * Accounting Seeder
 * Seeds default fiscal year and chart of accounts for all active tenants.
 * Run: php database/seeders/AccountingSeeder.php
 */

require_once __DIR__ . '/../../config/config.php';

$db = getDBConnection();

// Get all tenants
$stmt = $db->query("SELECT id, name FROM tenants WHERE status IN ('active', 'trial') ORDER BY id");
$tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($tenants) . " tenant(s).\n";

foreach ($tenants as $tenant) {
    $tenantId = $tenant['id'];
    echo "\nSeeding tenant #{$tenantId}: {$tenant['name']}\n";

    // 1. Create fiscal year if none exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM acc_fiscal_years WHERE tenant_id = ?");
    $stmt->execute([$tenantId]);
    if ($stmt->fetchColumn() == 0) {
        // Nepal FY 2081-82: July 16, 2024 – July 15, 2025
        // Nepal FY 2082-83: July 16, 2025 – July 15, 2026 (current)
        $db->prepare("INSERT INTO acc_fiscal_years (tenant_id, name, start_date, end_date, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())")
           ->execute([$tenantId, 'FY 2082-83 (2025-26)', '2025-07-16', '2026-07-15']);
        echo "  Created fiscal year FY 2082-83.\n";
    } else {
        echo "  Fiscal year already exists, skipping.\n";
    }

    // 2. Create chart of accounts if none exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM acc_accounts WHERE tenant_id = ?");
    $stmt->execute([$tenantId]);
    if ($stmt->fetchColumn() == 0) {
        seedChartOfAccounts($db, $tenantId);
        echo "  Created chart of accounts.\n";
    } else {
        echo "  Chart of accounts already exists, skipping.\n";
    }
}

echo "\nDone!\n";

function seedChartOfAccounts(PDO $db, int $tenantId): void
{
    $insert = $db->prepare("
        INSERT INTO acc_accounts
            (tenant_id, code, name, type, nature, parent_id, is_group, opening_balance, balance_type, is_system, status, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, 0.00, ?, 1, 'active', NOW(), NOW())
    ");

    // Group accounts first (parent_id = null), then sub-accounts referencing group IDs
    $accounts = [
        // ASSETS
        ['1000', 'Cash & Cash Equivalents',    'asset',     'CASH',    null, 1, 'dr'],
        ['1010', 'Petty Cash',                 'asset',     'CASH',    '1000', 0, 'dr'],
        ['1011', 'Cash in Hand',               'asset',     'CASH',    '1000', 0, 'dr'],
        ['1020', 'Bank Account',               'asset',     'BANK',    '1000', 0, 'dr'],
        ['1100', 'Accounts Receivable',        'asset',     'AR',      null, 1, 'dr'],
        ['1101', 'Student Fees Receivable',    'asset',     'AR',      '1100', 0, 'dr'],
        ['1200', 'Prepaid Expenses',           'asset',     'GENERAL', null, 0, 'dr'],
        ['1300', 'Other Current Assets',       'asset',     'GENERAL', null, 0, 'dr'],
        ['1500', 'Fixed Assets',               'asset',     'GENERAL', null, 1, 'dr'],
        ['1501', 'Furniture & Fixtures',       'asset',     'GENERAL', '1500', 0, 'dr'],
        ['1502', 'Computer & Equipment',       'asset',     'GENERAL', '1500', 0, 'dr'],

        // LIABILITIES
        ['2000', 'Accounts Payable',           'liability', 'AP',      null, 1, 'cr'],
        ['2001', 'Vendor Payables',            'liability', 'AP',      '2000', 0, 'cr'],
        ['2100', 'Accrued Expenses',           'liability', 'GENERAL', null, 0, 'cr'],
        ['2200', 'Tax Payable',                'liability', 'GENERAL', null, 1, 'cr'],
        ['2201', 'TDS Payable',                'liability', 'GENERAL', '2200', 0, 'cr'],
        ['2202', 'SSF/ESF Payable',            'liability', 'GENERAL', '2200', 0, 'cr'],
        ['2300', 'Salary Payable',             'liability', 'GENERAL', null, 0, 'cr'],
        ['2400', 'Advance from Students',      'liability', 'GENERAL', null, 0, 'cr'],

        // EQUITY
        ['3000', 'Owner\'s Equity',            'equity',    'GENERAL', null, 0, 'cr'],
        ['3100', 'Retained Earnings',          'equity',    'GENERAL', null, 0, 'cr'],

        // INCOME
        ['4000', 'Income',                     'income',    'GENERAL', null, 1, 'cr'],
        ['4001', 'Tuition Fees',               'income',    'GENERAL', '4000', 0, 'cr'],
        ['4002', 'Admission Fees',             'income',    'GENERAL', '4000', 0, 'cr'],
        ['4003', 'Examination Fees',           'income',    'GENERAL', '4000', 0, 'cr'],
        ['4004', 'Registration Fees',          'income',    'GENERAL', '4000', 0, 'cr'],
        ['4005', 'Other Income',               'income',    'GENERAL', '4000', 0, 'cr'],

        // EXPENSES
        ['5000', 'Expenses',                   'expense',   'GENERAL', null, 1, 'dr'],
        ['5001', 'Salary Expense',             'expense',   'GENERAL', '5000', 0, 'dr'],
        ['5002', 'Rent Expense',               'expense',   'GENERAL', '5000', 0, 'dr'],
        ['5003', 'Utilities Expense',          'expense',   'GENERAL', '5000', 0, 'dr'],
        ['5004', 'Office Supplies',            'expense',   'GENERAL', '5000', 0, 'dr'],
        ['5005', 'Marketing & Advertising',    'expense',   'GENERAL', '5000', 0, 'dr'],
        ['5006', 'Maintenance & Repairs',      'expense',   'GENERAL', '5000', 0, 'dr'],
        ['5007', 'Bank Charges',               'expense',   'GENERAL', '5000', 0, 'dr'],
        ['5008', 'Miscellaneous Expense',      'expense',   'GENERAL', '5000', 0, 'dr'],
    ];

    // Two-pass: first insert groups, then link children by code
    $codeToId = [];

    // Pass 1: Insert group accounts (parent_id = null or placeholder)
    foreach ($accounts as $acc) {
        [$code, $name, $type, $nature, $parentCode, $isGroup, $balType] = $acc;
        if ($parentCode === null) {
            $insert->execute([$tenantId, $code, $name, $type, $nature, null, $isGroup, $balType]);
            $codeToId[$code] = (int)$db->lastInsertId();
        }
    }

    // Pass 2: Insert leaf accounts with resolved parent IDs
    foreach ($accounts as $acc) {
        [$code, $name, $type, $nature, $parentCode, $isGroup, $balType] = $acc;
        if ($parentCode !== null) {
            $parentId = $codeToId[$parentCode] ?? null;
            $insert->execute([$tenantId, $code, $name, $type, $nature, $parentId, $isGroup, $balType]);
            $codeToId[$code] = (int)$db->lastInsertId();
        }
    }
}
