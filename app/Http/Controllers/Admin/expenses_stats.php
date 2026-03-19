<?php
/**
 * Admin Expenses Summary & Stats API
 * Route: /api/admin/expenses/stats
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('expenses.view')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenant_id = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenant_id) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

try {
    $db = getDBConnection();

    // 1. Total Expenses this month
    $stmt = $db->prepare("SELECT SUM(amount) FROM expenses WHERE tenant_id = :tid AND date_ad >= DATE_FORMAT(NOW(), '%Y-%m-01') AND deleted_at IS NULL AND status = 'approved'");
    $stmt->execute(['tid' => $tenant_id]);
    $total_monthly = floatval($stmt->fetchColumn() ?: 0);

    // 2. Total Expenses this quarter (Nepal Fiscal Quarter)
    // Nepal fiscal year starts from Shrawan (mid-July)
    // For simplicity, let's use calendar quarters or just a 30-day window
    $stmt = $db->prepare("SELECT SUM(amount) FROM expenses WHERE tenant_id = :tid AND date_ad >= DATE_SUB(NOW(), INTERVAL 90 DAY) AND deleted_at IS NULL AND status = 'approved'");
    $stmt->execute(['tid' => $tenant_id]);
    $total_quarterly = floatval($stmt->fetchColumn() ?: 0);

    // 3. Category Breakdown (Current Month)
    $stmt = $db->prepare("
        SELECT c.name as category, SUM(e.amount) as total, c.color, c.icon
        FROM expenses e
        JOIN expense_categories c ON e.expense_category_id = c.id
        WHERE e.tenant_id = :tid AND e.date_ad >= DATE_FORMAT(NOW(), '%Y-%m-01') AND e.deleted_at IS NULL AND e.status = 'approved'
        GROUP BY c.id, c.name, c.color, c.icon
        ORDER BY total DESC
    ");
    $stmt->execute(['tid' => $tenant_id]);
    $category_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Monthly Trend (Last 6 Months)
    $stmt = $db->prepare("
        SELECT DATE_FORMAT(date_ad, '%Y-%m') as month, SUM(amount) as total
        FROM expenses
        WHERE tenant_id = :tid AND date_ad >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 5 MONTH) AND deleted_at IS NULL AND status = 'approved'
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->execute(['tid' => $tenant_id]);
    $monthly_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Recent Expenses (Last 10)
    $stmt = $db->prepare("
        SELECT e.*, c.name as category_name, c.color as category_color
        FROM expenses e
        LEFT JOIN expense_categories c ON e.expense_category_id = c.id
        WHERE e.tenant_id = :tid AND e.deleted_at IS NULL
        ORDER BY e.created_at DESC
        LIMIT 10
    ");
    $stmt->execute(['tid' => $tenant_id]);
    $recent_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'summary' => [
            'total_monthly' => $total_monthly,
            'total_quarterly' => $total_quarterly,
            'category_breakdown' => $category_breakdown,
            'monthly_trend' => $monthly_trend,
            'recent_expenses' => $recent_expenses
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
