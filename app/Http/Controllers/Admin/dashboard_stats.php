<?php
/**
 * Institute Admin Dashboard Stats API V2
 * Optimized for performance and comprehensive V2 requirements
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return __DIR__ . '/../../../../storage/' . $path;
    }
}

header('Content-Type: application/json');

// Ensure no previous output (like PHP warnings) ruins the JSON
if (ob_get_length()) ob_clean();

// Auth Check
if (!isLoggedIn() || ($_SESSION['userData']['role'] !== 'instituteadmin' && $_SESSION['userData']['role'] !== 'superadmin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
$userId = $_SESSION['userData']['id'] ?? null;

if ($_SESSION['userData']['role'] === 'superadmin' && isset($_GET['tenant_id'])) {
    $tenantId = $_GET['tenant_id'];
}

try {
    $db = getDBConnection();
    
    // 5 Minute Cache logic
    $cacheFile = storage_path("cache/dashboard_stats_{$tenantId}.json");
    $cacheTime = 300; // 5 minutes

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime) && !isset($_GET['nocache'])) {
        echo file_get_contents($cacheFile);
        exit;
    }

    $stats = [];
    $now = new DateTime();
    $today = $now->format('Y-m-d');
    $thisMonth = $now->format('m');
    $thisYear = $now->format('Y');

    // --- SECTION A: HEADER STRIP & INSTITUTE INFO ---
    $stmt = $db->prepare("SELECT name, plan as plan_name, status as subscription_status, trial_ends_at as expires_at FROM tenants WHERE id = :tid");
    $stmt->execute(['tid' => $tenantId]);
    $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats['header'] = [
        'institute_name' => $tenant['name'] ?? 'Institution',
        'academic_year' => $thisYear . '-' . ($thisYear + 1), 
        'current_date' => $now->format('l, F d, Y'),
        'plan' => ucfirst($tenant['plan_name'] ?? 'growth'),
        'status' => $tenant['subscription_status'] ?? 'trial',
        'expiry' => $tenant['expires_at'] ?? null
    ];

    // --- SECTION B: PRIMARY KPI ROW ---
    
    // 1. Fee Collection (Monthly)
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN DATE_FORMAT(payment_date, '%Y-%m') = :curr THEN amount ELSE 0 END) as curr_month,
            SUM(CASE WHEN DATE_FORMAT(payment_date, '%Y-%m') = :prev THEN amount ELSE 0 END) as prev_month
        FROM payment_transactions 
        WHERE tenant_id = :tid 
        AND payment_date >= DATE_SUB(DATE_FORMAT(NOW() ,'%Y-%m-01'), INTERVAL 1 MONTH)
    ");
    $prevMonthDate = (clone $now)->modify('first day of last month')->format('Y-m');
    $stmt->execute(['tid' => $tenantId, 'curr' => $now->format('Y-m'), 'prev' => $prevMonthDate]);
    $feeData = $stmt->fetch(PDO::FETCH_ASSOC);
    $currFee = (float)$feeData['curr_month'];
    $prevFee = (float)$feeData['prev_month'];
    
    // Get Target
    $stmtTarget = $db->prepare("SELECT fee_collection_target, enrollment_target FROM monthly_targets WHERE tenant_id = :tid AND year = :y AND month = :m");
    $stmtTarget->execute(['tid' => $tenantId, 'y' => $thisYear, 'm' => (int)$thisMonth]);
    $targets = $stmtTarget->fetch(PDO::FETCH_ASSOC) ?: ['fee_collection_target' => 0, 'enrollment_target' => 0];
    
    $stats['kpi_fees'] = [
        'collected' => $currFee,
        'target' => (float)$targets['fee_collection_target'],
        'growth' => $prevFee > 0 ? round((($currFee - $prevFee) / $prevFee) * 100, 1) : ($currFee > 0 ? 100 : 0)
    ];

    // 2. Pending Dues
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(due_amount), 0) as total_dues,
            COUNT(CASE WHEN due_amount > 0 THEN 1 END) as student_count
        FROM student_fee_summary 
        WHERE tenant_id = :tid
    ");
    $stmt->execute(['tid' => $tenantId]);
    $pendingData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Quick pseudo-growth for dues (might need history table for real growth, but using summary for now)
    $stats['kpi_dues'] = [
        'amount' => (float)$pendingData['total_dues'],
        'count' => (int)$pendingData['student_count'],
        'threshold_exceeded' => (float)$pendingData['total_dues'] > 500000 // Red highlight threshold
    ];

    // 3. Active Students
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01') THEN 1 END) as month_new
        FROM students 
        WHERE tenant_id = :tid AND status = 'active' AND deleted_at IS NULL
    ");
    $stmt->execute(['tid' => $tenantId]);
    $studentData = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['kpi_students'] = [
        'total' => (int)$studentData['total'],
        'new' => (int)$studentData['month_new']
    ];

    // 4. Staff
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as active,
            COUNT(CASE WHEN created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01') THEN 1 END) as month_new
        FROM users 
        WHERE tenant_id = :tid AND role IN ('teacher', 'staff', 'admin') AND status = 'active'
    ");
    $stmt->execute(['tid' => $tenantId]);
    $staffData = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['kpi_staff'] = [
        'total' => (int)$staffData['active'],
        'new' => (int)$staffData['month_new']
    ];

    // --- SECTION C: SECONDARY KPI STRIP ---
    // Last 7 days vs previous 7 days comparison
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM batches WHERE tenant_id = :tid1 AND status = 'active' AND deleted_at IS NULL) as active_batches,
            (SELECT COUNT(*) FROM courses WHERE tenant_id = :tid2 AND is_active = 1 AND deleted_at IS NULL) as active_courses,
            (SELECT COUNT(*) FROM inquiries WHERE tenant_id = :tid3 AND status = 'pending' AND deleted_at IS NULL) as open_inquiries,
            (SELECT AVG(CASE WHEN status='present' THEN 100 ELSE 0 END) FROM attendance WHERE tenant_id = :tid4 AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)) as att_last_7,
            (SELECT AVG(CASE WHEN status='present' THEN 100 ELSE 0 END) FROM attendance WHERE tenant_id = :tid5 AND attendance_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 13 DAY) AND DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as att_prev_7
    ");
    $stmt->execute(['tid1' => $tenantId, 'tid2' => $tenantId, 'tid3' => $tenantId, 'tid4' => $tenantId, 'tid5' => $tenantId]);
    $secData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats['secondary_kpi'] = [
        'attendance' => [
            'current' => round((float)$secData['att_last_7'], 1),
            'previous' => round((float)$secData['att_prev_7'], 1)
        ],
        'batches' => (int)$secData['active_batches'],
        'courses' => (int)$secData['active_courses'],
        'inquiries' => (int)$secData['open_inquiries']
    ];

    // --- SECTION D: MAIN ANALYTICS GRAPH ---
    // Single optimized query for monthly trend
    $stmt = $db->prepare("
        SELECT 
            MONTH(payment_date) as month_num,
            DATE_FORMAT(payment_date, '%b') as month_name,
            SUM(amount) as collected
        FROM payment_transactions 
        WHERE tenant_id = :tid 
        AND payment_date >= DATE_FORMAT(NOW() ,'%Y-01-01')
        GROUP BY MONTH(payment_date)
        ORDER BY month_num ASC
    ");
    $stmt->execute(['tid' => $tenantId]);
    $stats['revenue_graph'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- SECTION E: TARGETS ---
    $stats['targets'] = [
        'fee' => [
            'target' => (float)$targets['fee_collection_target'],
            'collected' => $currFee,
            'percent' => $targets['fee_collection_target'] > 0 ? round(($currFee / $targets['fee_collection_target']) * 100) : 0
        ],
        'enrollment' => [
            'target' => (int)$targets['enrollment_target'],
            'current' => (int)$studentData['month_new'],
            'percent' => $targets['enrollment_target'] > 0 ? round(($studentData['month_new'] / $targets['enrollment_target']) * 100) : 0
        ]
    ];

    // --- SECTION F: ACTIVE NOTICES ---
    $stmt = $db->prepare("
        SELECT title, notice_type, priority, created_at, display_until as expiry_date 
        FROM notices 
        WHERE tenant_id = :tid 
        AND status = 'active'
        AND (display_until >= CURDATE() OR display_until IS NULL)
        ORDER BY created_at DESC LIMIT 3
    ");
    $stmt->execute(['tid' => $tenantId]);
    $stats['notices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- SECTION G: RECENT ADMISSIONS ---
    $stmt = $db->prepare("
        SELECT s.full_name, c.name as course_name, s.created_at, s.status, s.photo_url 
        FROM students s
        LEFT JOIN batches b ON s.batch_id = b.id
        LEFT JOIN courses c ON b.course_id = c.id
        WHERE s.tenant_id = :tid AND s.deleted_at IS NULL
        ORDER BY s.created_at DESC LIMIT 5
    ");
    $stmt->execute(['tid' => $tenantId]);
    $stats['recent_admissions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- SECTION H: ATTENDANCE OVERVIEW ---
    $stmt = $db->prepare("
        SELECT 
            COUNT(CASE WHEN status='present' THEN 1 END) as present,
            COUNT(CASE WHEN status='absent' THEN 1 END) as absent,
            COUNT(*) as total
        FROM attendance 
        WHERE tenant_id = :tid AND attendance_date = CURDATE()
    ");
    $stmt->execute(['tid' => $tenantId]);
    $attToday = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Batch-wise
    $stmt = $db->prepare("
        SELECT b.name, 
               COUNT(CASE WHEN a.status='present' THEN 1 END) as present,
               COUNT(*) as total
        FROM attendance a
        JOIN batches b ON a.batch_id = b.id
        WHERE a.tenant_id = :tid AND a.attendance_date = CURDATE()
        GROUP BY b.id
    ");
    $stmt->execute(['tid' => $tenantId]);
    $batchAtt = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats['attendance_overview'] = [
        'today' => $attToday,
        'batches' => $batchAtt
    ];

    // --- SECTION I: ACTIVITY LOG ---
    // Attempting to pull from activity_logs if exists, else audit_logs
    try {
        $stmt = $db->prepare("
            SELECT description, user_name, created_at, related_entity_name 
            FROM activity_logs 
            WHERE tenant_id = :tid 
            ORDER BY created_at DESC LIMIT 10
        ");
        $stmt->execute(['tid' => $tenantId]);
        $stats['activity_log'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $stats['activity_log'] = [];
    }

    // --- SECTION J: UPCOMING EXAMS ---
    $stmt = $db->prepare("
        SELECT e.title, c.name as course, e.start_at as exam_date, e.status 
        FROM exams e
        LEFT JOIN courses c ON e.course_id = c.id
        WHERE e.tenant_id = :tid AND e.start_at >= CURDATE() AND e.deleted_at IS NULL
        ORDER BY e.start_at ASC LIMIT 5
    ");
    $stmt->execute(['tid' => $tenantId]);
    $stats['upcoming_exams'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- SECTION K: LIBRARY MODULE ---
    try {
        $stmt = $db->prepare("
            SELECT 
                SUM(total_copies) as total,
                (SELECT COUNT(*) FROM library_issues WHERE tenant_id = :tid1 AND return_date IS NULL) as issued,
                (SELECT COUNT(*) FROM library_issues WHERE tenant_id = :tid2 AND return_date IS NULL AND due_date < CURDATE()) as overdue
            FROM library_books 
            WHERE tenant_id = :tid3
        ");
        $stmt->execute(['tid1' => $tenantId, 'tid2' => $tenantId, 'tid3' => $tenantId]);
        $stats['library'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $stats['library'] = null;
    }

    $finalJson = json_encode(['success' => true, 'data' => $stats]);
    
    // Save to cache
    if (!is_dir(dirname($cacheFile))) @mkdir(dirname($cacheFile), 0777, true);
    file_put_contents($cacheFile, $finalJson);
    
    echo $finalJson;

} catch (Exception $e) {
    error_log('Dashboard Stats Error V2: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'Backend Error: ' . $e->getMessage()]);
}
