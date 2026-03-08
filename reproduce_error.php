<?php
define('APP_NAME', 'Hamro ERP');
require_once __DIR__ . '/config/config.php';

// Mock session
$_SESSION['userData'] = [
    'id' => 1,
    'tenant_id' => 1,
    'role' => 'instituteadmin'
];

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [
    'page' => 1,
    'per_page' => 10
];

try {
    // We can't easily include the controller because it calls exit/echo
    // So we'll copy the relevant logic here to see where it fails.
    $db = getDBConnection();
    $tenantId = $_SESSION['userData']['tenant_id'];

    $perPage = (int)$_GET['per_page'];
    $page = (int)$_GET['page'];
    $offset = ($page - 1) * $perPage;

    $whereSql = "WHERE s.tenant_id = :tid AND s.deleted_at IS NULL";
    $params = ['tid' => $tenantId];

    $countQuery = "SELECT COUNT(*) FROM students s 
                   LEFT JOIN batches b ON s.batch_id = b.id 
                   $whereSql";
    $stmtCount = $db->prepare($countQuery);
    $stmtCount->execute($params);
    $total = (int)$stmtCount->fetchColumn();
    echo "Total students: $total\n";

    $query = "SELECT s.*, s.registration_mode, s.registration_status, s.admission_date,
                     b.name as batch_name, b.course_id as course_id, c.name as course_name,
                     COALESCE(sfs.fee_status, 'no_fees') as fee_status,
                     COALESCE(sfs.total_fee, 0) as total_fee,
                     COALESCE(sfs.paid_amount, 0) as paid_amount,
                     COALESCE(sfs.due_amount, 0) as due_amount
              FROM students s 
              LEFT JOIN batches b ON s.batch_id = b.id 
              LEFT JOIN courses c ON b.course_id = c.id
              LEFT JOIN student_fee_summary sfs ON s.id = sfs.student_id
              $whereSql
              ORDER BY s.created_at DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue(":$key", $val);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Fetched " . count($students) . " students\n";

    foreach ($students as &$student) {
        $sid = $student['id'];
        echo "Processing student ID: $sid\n";
        
        $feeStmt = $db->prepare("
            SELECT 
                sfs.fee_status,
                COALESCE(sfs.total_fee, 0) as total_due,
                COALESCE(sfs.paid_amount, 0) as total_paid,
                COALESCE(sfs.due_amount, 0) as pending_amount
            FROM student_fee_summary sfs
            JOIN enrollments e ON sfs.enrollment_id = e.id
            WHERE sfs.student_id = :sid AND sfs.tenant_id = :tid AND e.status = 'active'
            LIMIT 1
        ");
        $feeStmt->execute(['sid' => $sid, 'tid' => $tenantId]);
        $feeData = $feeStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$feeData) {
            $student['fee_status'] = 'no_fees';
            $student['fee_status_label'] = 'No Fees';
            $student['total_due'] = 0;
            $student['total_paid'] = 0;
        } else {
            $student['fee_status'] = $feeData['fee_status'];
            $student['fee_status_label'] = ucfirst(str_replace('_', ' ', $feeData['fee_status']));
            $student['total_due'] = (float)$feeData['total_due'];
            $student['total_paid'] = (float)$feeData['total_paid'];
        }
    }
    echo "Finished processing students\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
