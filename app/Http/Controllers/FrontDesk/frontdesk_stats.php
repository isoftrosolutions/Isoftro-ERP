<?php
/**
 * Front Desk Dashboard Stats API
 * Fetches real-time metrics for the front desk dashboard
 * Uses parameterized queries for security and proper caching
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Set JSON response type
http_response_code(200);

// CSRF and role check via Middleware
require_once app_path('Http/Middleware/FrontDeskMiddleware.php');
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];
$userId   = $auth['user_id'];
$role     = $auth['role'];

// Optimized caching using APCu (if available)
$cacheKey = "fd_stats_{$tenantId}";
$cacheExpiry = 300; // 5 minutes

if (!isset($_GET['refresh']) && function_exists('apcu_fetch')) {
    $cached = apcu_fetch($cacheKey);
    if ($cached !== false) {
        echo json_encode([
            'success' => true, 
            'data' => $cached,
            'cached' => true,
            'timestamp' => date('c')
        ]);
        exit;
    }
}

try {
    $db = getDBConnection();
    $stats = [];
    $today = date('Y-m-d');
    
    // ── 1. STUDENT METRICS ──
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM students WHERE tenant_id = :tid1 AND status = 'active' AND deleted_at IS NULL) as total_students,
            (SELECT COUNT(*) FROM students WHERE tenant_id = :tid2 AND status = 'active' AND created_at < DATE_FORMAT(NOW() ,'%Y-%m-01') AND deleted_at IS NULL) as prev_month_students,
            (SELECT COUNT(*) FROM students WHERE tenant_id = :tid3 AND created_at >= :today1 AND deleted_at IS NULL) as today_admissions
    ");
    $stmt->execute(['tid1' => $tenantId, 'tid2' => $tenantId, 'tid3' => $tenantId, 'today1' => $today]);
    $stu = $stmt->fetch();
    $stats['total_students'] = (int) $stu['total_students'];
    $stats['stu_trend'] = ($stu['prev_month_students'] > 0) ? round((($stu['total_students'] - $stu['prev_month_students']) / $stu['prev_month_students']) * 100, 1) : 0;

    // ── 2. REVENUE METRICS (Monthly Focus) ──
    $stmt = $db->prepare("
        SELECT 
            (SELECT COALESCE(SUM(amount_paid), 0) FROM fee_records WHERE tenant_id = :tid4 AND paid_date >= DATE_FORMAT(NOW() ,'%Y-%m-01')) as monthly_revenue,
            (SELECT COALESCE(SUM(amount_paid), 0) FROM fee_records WHERE tenant_id = :tid5 AND paid_date >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH) ,'%Y-%m-01') AND paid_date < DATE_FORMAT(NOW() ,'%Y-%m-01')) as prev_monthly_revenue,
            (SELECT COALESCE(SUM(amount_paid), 0) FROM fee_records WHERE tenant_id = :tid6 AND paid_date = :today2) as today_revenue
    ");
    $stmt->execute(['tid4' => $tenantId, 'tid5' => $tenantId, 'tid6' => $tenantId, 'today2' => $today]);
    $rev = $stmt->fetch();
    $stats['monthly_revenue'] = (float) $rev['monthly_revenue'];
    $stats['today_revenue'] = (float) $rev['today_revenue'];
    $stats['rev_trend'] = ($rev['prev_monthly_revenue'] > 0) ? round((($rev['monthly_revenue'] - $rev['prev_monthly_revenue']) / $rev['prev_monthly_revenue']) * 100, 1) : 0;

    // ── 3. INQUIRY METRICS ──
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM inquiries WHERE tenant_id = :tid7 AND deleted_at IS NULL) as total_inquiries,
            (SELECT COUNT(*) FROM inquiries WHERE tenant_id = :tid8 AND created_at < DATE_FORMAT(NOW() ,'%Y-%m-01') AND deleted_at IS NULL) as prev_month_inquiries
    ");
    $stmt->execute(['tid7' => $tenantId, 'tid8' => $tenantId]);
    $inq = $stmt->fetch();
    $stats['total_inquiries'] = (int) $inq['total_inquiries'];
    $stats['inq_trend'] = ($inq['prev_month_inquiries'] > 0) ? round((($inq['total_inquiries'] - $inq['prev_month_inquiries']) / $inq['prev_month_inquiries']) * 100, 1) : 0;

    // ── 4. LIBRARY METRICS ──
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM library_issues WHERE tenant_id = :tid AND returned_at IS NULL");
        $stmt->execute(['tid' => $tenantId]);
        $stats['library_active'] = (int) $stmt->fetchColumn();
    } catch (Exception $e) { $stats['library_active'] = 0; }

    // ── 5. FINANCE SUMMARY (Persistence) ──
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(amount_due - amount_paid), 0) as pending_dues,
            COUNT(CASE WHEN due_date < :today3 THEN 1 END) as overdue_payments
        FROM fee_records 
        WHERE tenant_id = :tid9 AND amount_due > amount_paid
    ");
    $stmt->execute(['tid9' => $tenantId, 'today3' => $today]);
    $dues = $stmt->fetch();
    $stats['pending_dues'] = (float) $dues['pending_dues'];
    $stats['overdue_payments'] = (int) $dues['overdue_payments'];
    
    // Recent items remain same
    $stmt = $db->prepare("SELECT fr.id, fr.receipt_no, s.full_name as student_name, fr.amount_paid, fr.paid_date, fr.payment_mode 
        FROM fee_records fr 
        JOIN students s ON fr.student_id = s.id 
        WHERE fr.tenant_id = :tid AND fr.paid_date = :today 
        ORDER BY fr.created_at DESC 
        LIMIT 10");
    $stmt->execute(['tid' => $tenantId, 'today' => $today]);
    $stats['today_transactions'] = $stmt->fetchAll();
    
    $stmt = $db->prepare("SELECT id, name, start_date, max_strength FROM batches WHERE tenant_id = :tid AND start_date >= :today AND status = 'upcoming' ORDER BY start_date ASC LIMIT 5");
    $stmt->execute(['tid' => $tenantId, 'today' => $today]);
    $stats['upcoming_batches'] = $stmt->fetchAll();


    
    // Store in cache if APCu is available
    if (function_exists('apcu_store')) {
        apcu_store($cacheKey, $stats, $cacheExpiry);
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $stats,
        'user' => [
            'name' => $_SESSION['userData']['name'] ?? 'Operator',
            'email' => $_SESSION['userData']['email'] ?? ''
        ],
        'tenant_name' => $_SESSION['tenant_name'] ?? 'Institute',
        'cached' => false,
        'timestamp' => date('c')
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("FrontDesk Stats Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'code' => 'DB_ERROR'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("FrontDesk Stats Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred',
        'code' => 'GENERAL_ERROR'
    ]);
}
