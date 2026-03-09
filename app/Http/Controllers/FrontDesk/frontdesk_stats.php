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

require_once app_path('Http/Middleware/FrontDeskMiddleware.php');
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];
$today = date('Y-m-d');

try {
    $db = getDBConnection();
    $data = [];

    // ── 1. KPI STATS (Collection, Dues, Attendance, Admissions) ──
    // Collection
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM fee_records WHERE tenant_id = :tid AND paid_date = :today");
    $stmt->execute(['tid' => $tenantId, 'today' => $today]);
    $data['kpi_collection'] = [
        'value' => (float) $stmt->fetchColumn(),
        'trend' => '+12%',
        'label' => "Today's Collection"
    ];

    // Dues (Using balance column or amount_due - amount_paid)
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount_due - amount_paid), 0) FROM fee_records WHERE tenant_id = :tid AND amount_due > amount_paid");
    $stmt->execute(['tid' => $tenantId]);
    $data['kpi_dues'] = [
        'value' => (float) $stmt->fetchColumn(),
        'trend' => '-5%',
        'label' => "Pending Dues"
    ];

    // Attendance (Using attendance_date)
    $stmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE tenant_id = :tid AND attendance_date = :today AND status = 'present'");
    $stmt->execute(['tid' => $tenantId, 'today' => $today]);
    $present = (int) $stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND status = 'active' AND deleted_at IS NULL");
    $stmt->execute(['tid' => $tenantId]);
    $totalStudents = (int) $stmt->fetchColumn();
    $data['kpi_attendance'] = [
        'value' => ($totalStudents > 0) ? round(($present / $totalStudents) * 100, 1) . '%' : '0%',
        'trend' => '+2%',
        'label' => "Today's Attendance"
    ];

    // Admissions
    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid AND DATE(created_at) = :today AND deleted_at IS NULL");
    $stmt->execute(['tid' => $tenantId, 'today' => $today]);
    $data['kpi_admissions'] = [
        'value' => (int) $stmt->fetchColumn(),
        'trend' => '+3',
        'label' => "New Admissions"
    ];

    // ── 2. MINI STATUS CARDS ──
    $data['mini_stats'] = [
        'inquiries' => (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE tenant_id = $tenantId AND status = 'open'")->fetchColumn(),
        'leaves' => (int) $db->query("SELECT COUNT(*) FROM leave_requests WHERE tenant_id = $tenantId AND status = 'pending'")->fetchColumn(),
        'library' => (int) $db->query("SELECT COUNT(*) FROM library_issues WHERE tenant_id = $tenantId AND return_date IS NULL")->fetchColumn(),
        'alerts' => 3 
    ];

    // ── 3. TODAY'S TRANSACTIONS ──
    $stmt = $db->prepare("
        SELECT fr.receipt_no, s.full_name as student_name, fr.amount_paid, fr.payment_mode, fr.paid_date
        FROM fee_records fr
        JOIN students s ON fr.student_id = s.id
        WHERE fr.tenant_id = :tid AND fr.paid_date = :today
        ORDER BY fr.created_at DESC LIMIT 6
    ");
    $stmt->execute(['tid' => $tenantId, 'today' => $today]);
    $data['recent_transactions'] = $stmt->fetchAll();

    // ── 4. FEE SUMMARY (Method Wise) ──
    $stmt = $db->prepare("
        SELECT payment_mode as method, COALESCE(SUM(amount_paid), 0) as total
        FROM fee_records
        WHERE tenant_id = :tid AND paid_date = :today
        GROUP BY payment_mode
    ");
    $stmt->execute(['tid' => $tenantId, 'today' => $today]);
    $data['fee_summary'] = $stmt->fetchAll();

    // ── 5. ANNOUNCEMENTS ──
    $stmt = $db->prepare("
        SELECT title, message, created_at, priority as category
        FROM announcements
        WHERE is_active = 1 AND (ends_at IS NULL OR ends_at >= :today)
        ORDER BY created_at DESC LIMIT 4
    ");
    $stmt->execute(['tid' => $tenantId, 'today' => $today]);
    $data['announcements'] = $stmt->fetchAll();

    // ── 6. ATTENDANCE SNAPSHOT (Batch Wise) ──
    $stmt = $db->prepare("
        SELECT b.name as batch, COUNT(a.id) as present, 
               (SELECT COUNT(*) FROM students s WHERE s.batch_id = b.id AND s.deleted_at IS NULL) as total
        FROM batches b
        LEFT JOIN attendance a ON a.batch_id = b.id AND a.attendance_date = :today AND a.status = 'present'
        WHERE b.tenant_id = :tid AND b.status = 'active'
        GROUP BY b.id LIMIT 5
    ");
    $stmt->execute(['tid' => $tenantId, 'today' => $today]);
    $data['attendance_snapshot'] = $stmt->fetchAll();

    // ── 7. TODAY'S INQUIRIES ──
    $stmt = $db->prepare("
        SELECT full_name as name, source, status, created_at
        FROM inquiries
        WHERE tenant_id = :tid AND DATE(created_at) = :today
        ORDER BY created_at DESC LIMIT 5
    ");
    $stmt->execute(['tid' => $tenantId, 'today' => $today]);
    $data['today_inquiries'] = $stmt->fetchAll();

    // ── 8. LEAVE REQUESTS ──
    $stmt = $db->prepare("
        SELECT s.full_name as student, lr.reason as leave_type, lr.from_date, lr.status
        FROM leave_requests lr
        JOIN students s ON lr.student_id = s.id
        WHERE lr.tenant_id = :tid AND lr.status = 'pending'
        ORDER BY lr.created_at DESC LIMIT 5
    ");
    $stmt->execute(['tid' => $tenantId]);
    $data['leave_requests'] = $stmt->fetchAll();

    // ── 9. TIMETABLE ──
    $data['timetable'] = [
        ['time' => '09:00 AM', 'subject' => 'Mathematics', 'batch' => 'Grade 10-A', 'room' => 'R-101'],
        ['time' => '11:00 AM', 'subject' => 'Science', 'batch' => 'Grade 12-B', 'room' => 'Lab-2'],
        ['time' => '01:30 PM', 'subject' => 'English', 'batch' => 'Grade 9-C', 'room' => 'R-105']
    ];

    // ── 10. ACTIVITY LOG (Using table_name instead of module) ──
    $stmt = $db->prepare("
        SELECT action, table_name as module, created_at
        FROM audit_logs
        WHERE tenant_id = :tid
        ORDER BY created_at DESC LIMIT 10
    ");
    $stmt->execute(['tid' => $tenantId]);
    $data['activity_log'] = $stmt->fetchAll();

    // Header info
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
