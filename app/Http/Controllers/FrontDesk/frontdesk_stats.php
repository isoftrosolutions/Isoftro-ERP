<?php
/**
 * Front Desk Dashboard Stats API (Overhauled for V3.0)
 * Provides comprehensive data for all 17+ dashboard widgets
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../../Middleware/FrontDeskMiddleware.php';
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];
$today = date('Y-m-d');

try {
    $db = getDBConnection();
    $data = [];

    // ── 1. KPI STATS (Collection, Dues, Attendance, Admissions) ──
    // Collection - Show this month's collection from payment_transactions (Admin Source)
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(amount), 0) as this_month,
            (SELECT COALESCE(SUM(amount), 0) FROM payment_transactions WHERE tenant_id = :tid1 AND status = 'completed' AND DATE(payment_date) = CURDATE()) as today
        FROM payment_transactions 
        WHERE tenant_id = :tid2 AND status = 'completed' 
        AND payment_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
    ");
    $stmt->execute(['tid1' => $tenantId, 'tid2' => $tenantId]);
    $collData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $data['kpi_collection'] = [
        'value' => (float) $collData['this_month'],
        'today' => (float) $collData['today'],
        'trend' => '+12%',
        'label' => "This Month's Collection"
    ];

    // Dues - Using student_fee_summary joined with students to filter out deleted/inactive (Admin Source)
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(sfs.due_amount), 0) as total_dues, 
            COUNT(DISTINCT sfs.student_id) as overdue_count 
        FROM student_fee_summary sfs
        JOIN students s ON sfs.student_id = s.id
        WHERE sfs.tenant_id = :tid AND sfs.due_amount > 0 
        AND s.deleted_at IS NULL AND s.status = 'active'
    ");
    $stmt->execute(['tid' => $tenantId]);
    $duesData = $stmt->fetch(PDO::FETCH_ASSOC);
    $data['kpi_dues'] = [
        'value' => (float) ($duesData['total_dues'] ?? 0),
        'overdue_count' => (int) ($duesData['overdue_count'] ?? 0),
        'trend' => '-5%',
        'label' => "Total Pending Dues"
    ];

    // Attendance (Today's stats) - Admin Logic: (Present / Total Marked)
    $stmt = $db->prepare("
        SELECT 
            COUNT(CASE WHEN status='present' THEN 1 END) as present,
            COUNT(*) as total
        FROM attendance 
        WHERE tenant_id = :tid AND attendance_date = :today
    ");
    $stmt->execute(['tid' => $tenantId, 'today' => $today]);
    $attStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $presentToday = (int)$attStats['present'];
    $totalMarked = (int)$attStats['total'];

    $data['kpi_attendance'] = [
        'value' => ($totalMarked > 0) ? round(($presentToday / $totalMarked) * 100, 1) : 0,
        'present' => $presentToday,
        'total_marked' => $totalMarked,
        'trend' => '+2%',
        'label' => "Attendance Rate (Today)"
    ];

    // Admissions - Month's count
    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND deleted_at IS NULL AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $stmt->execute(['tid' => $tenantId]);
    $data['kpi_admissions'] = [
        'value' => (int) $stmt->fetchColumn(),
        'trend' => '+3',
        'label' => "New Admissions (This Month)"
    ];

    // ── 2. MINI STATUS CARDS ──
    $data['mini_stats'] = [
        'inquiries' => (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE tenant_id = $tenantId AND status = 'open' AND deleted_at IS NULL")->fetchColumn(),
        'leaves' => (int) $db->query("SELECT COUNT(*) FROM leave_requests WHERE tenant_id = $tenantId AND status = 'pending'")->fetchColumn(),
        'library' => (int) $db->query("SELECT COUNT(*) FROM library_issues WHERE tenant_id = $tenantId AND return_date IS NULL")->fetchColumn(),
        'alerts' => 3 
    ];

    // Today's Transactions - Pulled from payment_transactions to match revenue
    $stmt = $db->prepare("
        SELECT pt.transaction_id as receipt_no, u.name as student_name, pt.amount, pt.payment_method, pt.payment_date, pt.created_at
        FROM payment_transactions pt
        JOIN students s ON pt.student_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE pt.tenant_id = :tid AND pt.status = 'completed'
        ORDER BY pt.created_at DESC LIMIT 6
    ");
    $stmt->execute(['tid' => $tenantId]);
    $data['recent_transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── 4. FEE SUMMARY (Method Wise) - This month, matching Admin
    $stmt = $db->prepare("
        SELECT payment_method, COALESCE(SUM(amount), 0) as total
        FROM payment_transactions
        WHERE tenant_id = :tid AND status = 'completed' AND payment_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
        GROUP BY payment_method
    ");
    $stmt->execute(['tid' => $tenantId]);
    $data['fee_summary'] = $stmt->fetchAll();

    // ── 5. NOTICES (Announcements) ──
    $stmt = $db->prepare("
        SELECT title, content as `desc`, DATE_FORMAT(created_at, '%M %d') as time, notice_type as category, priority
        FROM notices
        WHERE tenant_id = :tid AND status = 'active' 
        AND (display_until IS NULL OR display_until >= NOW())
        ORDER BY created_at DESC LIMIT 4
    ");
    $stmt->execute(['tid' => $tenantId]);
    $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($notices as &$n) {
        $n['color'] = ($n['priority'] === 'high' || $n['priority'] === 'critical') ? '#EF4444' : '#10B981';
    }
    $data['announcements'] = $notices;

    // ── 6. ATTENDANCE SNAPSHOT (Batch Wise) ──
    $stmt = $db->prepare("
        SELECT b.name as batch_name, 
               COUNT(a.id) as present, 
               COUNT(*) as total
        FROM attendance a
        JOIN batches b ON a.batch_id = b.id
        WHERE a.tenant_id = :tid AND a.attendance_date = :today
        GROUP BY b.id LIMIT 5
    ");
    $stmt->execute(['tid' => $tenantId, 'today' => $today]);
    $batchAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate rate for each batch
    foreach ($batchAttendance as &$ba) {
        $ba['rate'] = $ba['total'] > 0 ? round(($ba['present'] / $ba['total']) * 100, 1) : 0;
        if ($ba['rate'] >= 90) $ba['color'] = '#16A34A';
        elseif ($ba['rate'] >= 80) $ba['color'] = '#10B981';
        elseif ($ba['rate'] >= 70) $ba['color'] = '#F59E0B';
        else $ba['color'] = '#EF4444';
    }
    $data['batch_attendance'] = $batchAttendance;

    // ── 7. TODAY'S INQUIRIES - Sync with frontend keys
    $stmt = $db->prepare("
        SELECT full_name as name, source as tag, status, DATE_FORMAT(created_at, '%h:%i %p') as time
        FROM inquiries
        WHERE tenant_id = :tid AND deleted_at IS NULL
        ORDER BY created_at DESC LIMIT 5
    ");
    $stmt->execute(['tid' => $tenantId]);
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($inquiries as &$inq) {
        $inq['note'] = 'Inquiry for Admission';
        $inq['tag_color'] = ($inq['tag'] === 'Walk-in') ? '#10B981' : (($inq['tag'] === 'Phone') ? '#F59E0B' : '#3B82F6');
    }
    $data['today_inquiries'] = $inquiries;

    // ── 8. LEAVE REQUESTS ──
    $stmt = $db->prepare("
        SELECT u.name as student_name, lr.reason, lr.from_date, lr.to_date, lr.status
        FROM leave_requests lr
        JOIN students s ON lr.student_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE lr.tenant_id = :tid AND lr.status = 'pending'
        ORDER BY lr.created_at DESC LIMIT 5
    ");
    $stmt->execute(['tid' => $tenantId]);
    $data['pending_leaves'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for frontend
    foreach ($data['pending_leaves'] as &$l) {
        $l['date'] = date('M d', strtotime($l['from_date']));
        if ($l['to_date'] && $l['to_date'] != $l['from_date']) {
            $l['date'] .= ' - ' . date('d', strtotime($l['to_date']));
        }
    }

    // ── 9. TIMETABLE (Real data with status) ──
    $dayOfWeek = date('N'); // 1 (Monday) to 7 (Sunday)
    $currentTime = date('H:i:s');
    $stmt = $db->prepare("
        SELECT 
            TIME_FORMAT(ts.start_time, '%H:%i') as time, 
            TIME_FORMAT(ts.end_time, '%H:%i') as end, 
            s.name as title, 
            CONCAT(b.name, ' · ', ts.room) as sub, 
            NULL as teacher,
            CASE WHEN :now BETWEEN ts.start_time AND ts.end_time THEN 'Ongoing' ELSE NULL END as status
        FROM timetable_slots ts
        JOIN subjects s ON ts.subject_id = s.id
        JOIN batches b ON ts.batch_id = b.id
        WHERE ts.tenant_id = :tid AND ts.day_of_week = :dow
        ORDER BY ts.start_time ASC LIMIT 6
    ");
    $stmt->execute(['tid' => $tenantId, 'dow' => $dayOfWeek, 'now' => $currentTime]);
    $data['today_timetable'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── 10. ACTIVITY LOG ──
    $stmt = $db->prepare("
        SELECT CONCAT(al.action, ' on ', al.table_name) as msg, 
               DATE_FORMAT(al.created_at, '%h:%i %p') as time,
               COALESCE(u.name, 'System') as user
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.tenant_id = :tid
        ORDER BY al.created_at DESC LIMIT 10
    ");
    $stmt->execute(['tid' => $tenantId]);
    $data['activity_log'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── 11. RECENT LIBRARY ISSUES ──
    $stmt = $db->prepare("
        SELECT lb.title, u.name as student, li.issue_date, li.due_date, li.return_date
        FROM library_issues li
        JOIN library_books lb ON li.book_id = lb.id
        JOIN students s ON li.student_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE li.tenant_id = :tid 
        ORDER BY li.created_at DESC LIMIT 4
    ");
    $stmt->execute(['tid' => $tenantId]);
    $recentLibrary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($recentLibrary as &$l) {
        if ($l['return_date']) {
            $l['status'] = 'Returned';
            $l['color'] = '#10B981'; // Green
        } elseif ($l['due_date'] < date('Y-m-d')) {
            $l['status'] = 'Overdue';
            $l['color'] = '#EF4444'; // Red
        } else {
            $l['status'] = 'Issued';
            $l['color'] = '#3B82F6'; // Blue
        }
    }
    $data['recent_library'] = $recentLibrary;

    // Library Summary Stats
    $stmt = $db->prepare("
        SELECT 
            (SELECT COALESCE(SUM(total_copies), 0) FROM library_books WHERE tenant_id = :tid1) as total_books,
            (SELECT COUNT(*) FROM library_issues WHERE tenant_id = :tid2 AND return_date IS NULL) as issued_books,
            (SELECT COUNT(*) FROM library_issues WHERE tenant_id = :tid3 AND return_date IS NULL AND due_date < :today) as overdue_books
    ");
    $stmt->execute(['tid1' => $tenantId, 'tid2' => $tenantId, 'tid3' => $tenantId, 'today' => $today]);
    $data['library_summary'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Header info
    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND status = 'active' AND deleted_at IS NULL");
    $stmt->execute(['tid' => $tenantId]);
    $totalStudents = (int) $stmt->fetchColumn();
    
    $data['kpi_students'] = [
        'total' => $totalStudents,
        'new_today' => $data['kpi_admissions']['value']
    ];
    
    $data['header'] = [
        'institute_name' => $_SESSION['tenant_name'] ?? 'Institute',
        'academic_year' => '2080/81'
    ];

    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
