<?php
/**
 * Super Admin Revenue API
 * Returns JSON data for revenue analytics
 */

if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Auth check
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
if (!$user || ($user['role'] ?? '') !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// CSRF check for POST/PUT/DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    try {
        \App\Helpers\CsrfHelper::requireCsrfToken();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'CSRF token mismatch.']);
        exit;
    }
}

$action = $_GET['action'] ?? 'dashboard';

try {
    $db = getDBConnection();
    
    switch ($action) {
        case 'dashboard':
        case 'mrr':
            // MRR Data - Last 12 months
            $mrrData = [];
            $prices = ['starter' => 1500, 'growth' => 3500, 'professional' => 12000, 'enterprise' => 25000];
            
            for ($i = 11; $i >= 0; $i--) {
                $month = date('M Y', strtotime("-$i months"));
                $monthStart = date('Y-m-01', strtotime("-$i months"));
                $monthEnd = date('Y-m-t', strtotime("-$i months"));
                
                $stmt = $db->prepare("
                    SELECT plan, COUNT(*) as count 
                    FROM tenants 
                    WHERE status = 'active' AND created_at <= ?
                    GROUP BY plan
                ");
                $stmt->execute([$monthEnd]);
                
                $mMrr = 0;
                $tenantCount = 0;
                while ($row = $stmt->fetch()) {
                    $mMrr += ($prices[$row['plan']] ?? 0) * $row['count'];
                    $tenantCount += $row['count'];
                }
                
                $mrrData[] = [
                    'month' => $month,
                    'mrr' => $mMrr,
                    'mrrK' => round($mMrr / 1000, 1),
                    'tenants' => $tenantCount
                ];
            }
            
            // Calculate totals
            $totalMrr = array_sum(array_column($mrrData, 'mrr'));
            $currentMrr = end($mrrData)['mrr'] ?? 0;
            
            // YoY Growth
            $lastYearMrr = isset($mrrData[0]) ? $mrrData[0]['mrr'] : 0;
            $yoyGrowth = $lastYearMrr > 0 ? round((($currentMrr - $lastYearMrr) / $lastYearMrr) * 100, 1) : 0;
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'mrr_trend' => $mrrData,
                    'current_mrr' => $currentMrr,
                    'total_mrr' => $totalMrr,
                    'mrr_formatted' => 'Rs. ' . number_format($currentMrr),
                    'yoy_growth' => $yoyGrowth
                ]
            ]);
            break;
            
        case 'payments':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;
            $status = $_GET['status'] ?? null;
            
            $where = [];
            $params = [];
            
            if ($status) {
                $where[] = 'p.status = :status';
                $params['status'] = $status;
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Get total
            $countStmt = $db->prepare("SELECT COUNT(*) FROM payments p $whereClause");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get payments
            $stmt = $db->prepare("
                SELECT p.*, t.name as tenant_name, t.subdomain
                FROM payments p
                LEFT JOIN tenants t ON p.tenant_id = t.id
                $whereClause
                ORDER BY p.paid_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            // Explicitly bind integers for LIMIT/OFFSET to avoid string quoting issues
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $payments = $stmt->fetchAll();
            
            // Get summary stats
            $summaryStmt = $db->query("
                SELECT 
                    COUNT(*) as total_count,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as completed_amount,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount,
                    COALESCE(SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END), 0) as failed_amount
                FROM payments
            ");
            $summary = $summaryStmt->fetch();
            
            echo json_encode([
                'success' => true,
                'data' => $payments,
                'summary' => $summary,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'invoices':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;
            
            // Get total
            $total = $db->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
            
            // Get invoices
            $stmt = $db->prepare("
                SELECT i.*, t.name as tenant_name, t.subdomain
                FROM invoices i
                LEFT JOIN tenants t ON i.tenant_id = t.id
                ORDER BY i.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $invoices = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $invoices,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'summary':
            // Quick revenue summary
            $stmt = $db->query("
                SELECT 
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_revenue,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count
                FROM payments
            ");
            $summary = $stmt->fetch();
            
            // This month
            $thisMonthStart = date('Y-m-01');
            $stmt2 = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) as monthly
                FROM payments
                WHERE status = 'completed' AND paid_at >= ?
            ");
            $stmt2->execute([$thisMonthStart]);
            $monthly = $stmt2->fetch();
            
            $summary['monthly_revenue'] = (float)$monthly['monthly'];
            
            echo json_encode(['success' => true, 'data' => $summary]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("[DB-ERROR] RevenueApi error: " . $e->getMessage());
    $msg = (defined('APP_ENV') && APP_ENV === 'development') ? $e->getMessage() : 'An internal error occurred. Please try again.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
