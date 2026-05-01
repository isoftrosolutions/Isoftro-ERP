<?php
/**
 * Student Attendance API
 * Handles attendance viewing for students
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

// Ensure user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$tenantId = $user['tenant_id'] ?? null;
$userId = $user['id'] ?? null;
$studentId = $_SESSION['userData']['student_id'] ?? null;

if (!$tenantId || !$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student record not found']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'summary';

try {
    $db = getDBConnection();
    
    switch ($action) {
        case 'summary':
            // Get overall attendance summary
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                    SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days
                FROM attendance 
                WHERE student_id = :sid AND tenant_id = :tid
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalDays = (int)($summary['total_days'] ?? 0);
            $presentDays = (int)($summary['present_days'] ?? 0);
            $lateDays = (int)($summary['late_days'] ?? 0);
            
            // Calculate percentage (late counts as 0.5)
            $percentage = $totalDays > 0 
                ? round((($presentDays + ($lateDays * 0.5)) / $totalDays) * 100, 2) 
                : 0;
            
            // Get monthly breakdown
            $stmt = $db->prepare("
                SELECT 
                    DATE_FORMAT(attendance_date, '%Y-%m') as month,
                    DATE_FORMAT(attendance_date, '%M %Y') as month_name,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave
                FROM attendance 
                WHERE student_id = :sid AND tenant_id = :tid
                GROUP BY DATE_FORMAT(attendance_date, '%Y-%m')
                ORDER BY month DESC
                LIMIT 12
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_days' => $totalDays,
                        'present_days' => $presentDays,
                        'absent_days' => (int)($summary['absent_days'] ?? 0),
                        'late_days' => $lateDays,
                        'leave_days' => (int)($summary['leave_days'] ?? 0),
                        'attendance_percentage' => $percentage
                    ],
                    'monthly_breakdown' => $monthlyData
                ]
            ]);
            break;
            
        case 'daily':
        case 'history':
            // Get daily attendance records
            $month = $_GET['month'] ?? date('m');
            $year = $_GET['year'] ?? date('Y');
            
            $stmt = $db->prepare("
                SELECT a.*, b.name as batch_name
                FROM attendance a
                LEFT JOIN batches b ON a.batch_id = b.id
                WHERE a.student_id = :sid 
                  AND a.tenant_id = :tid
                  AND MONTH(a.attendance_date) = :month
                  AND YEAR(a.attendance_date) = :year
                ORDER BY a.attendance_date DESC
            ");
            $stmt->execute([
                'sid' => $studentId, 
                'tid' => $tenantId,
                'month' => $month,
                'year' => $year
            ]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get available months with data
            $stmt = $db->prepare("
                SELECT DISTINCT DATE_FORMAT(attendance_date, '%Y-%m') as month
                FROM attendance 
                WHERE student_id = :sid AND tenant_id = :tid
                ORDER BY month DESC
                LIMIT 12
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $availableMonths = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $history,
                'month' => $month,
                'year' => $year,
                'available_months' => $availableMonths
            ]);
            break;
            
        case 'calendar':
            // Get attendance for a specific month in calendar format
            $month = $_GET['month'] ?? date('m');
            $year = $_GET['year'] ?? date('Y');
            
            $stmt = $db->prepare("
                SELECT 
                    DAY(attendance_date) as day,
                    attendance_date as date,
                    status
                FROM attendance 
                WHERE student_id = :sid 
                  AND tenant_id = :tid
                  AND MONTH(attendance_date) = :month
                  AND YEAR(attendance_date) = :year
                ORDER BY attendance_date
            ");
            $stmt->execute([
                'sid' => $studentId, 
                'tid' => $tenantId,
                'month' => $month,
                'year' => $year
            ]);
            $calendarData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Build calendar array
            $calendar = [];
            foreach ($calendarData as $day) {
                $calendar[(int)$day['day']] = [
                    'status' => $day['status'],
                    'date' => $day['date']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $calendar,
                'month' => $month,
                'year' => $year
            ]);
            break;
            
        case 'stats':
            // Get detailed statistics
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave,
                    MIN(attendance_date) as first_date,
                    MAX(attendance_date) as last_date
                FROM attendance 
                WHERE student_id = :sid AND tenant_id = :tid
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate streak
            $stmt = $db->prepare("
                SELECT attendance_date, status
                FROM attendance 
                WHERE student_id = :sid AND tenant_id = :tid
                ORDER BY attendance_date DESC
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $presentStreak = 0;
            $absentStreak = 0;
            
            foreach ($records as $record) {
                if ($record['status'] === 'present') {
                    $presentStreak++;
                } else {
                    break;
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total' => (int)($stats['total'] ?? 0),
                    'present' => (int)($stats['present'] ?? 0),
                    'absent' => (int)($stats['absent'] ?? 0),
                    'late' => (int)($stats['late'] ?? 0),
                    'leave' => (int)($stats['leave'] ?? 0),
                    'first_date' => $stats['first_date'] ?? null,
                    'last_date' => $stats['last_date'] ?? null,
                    'present_streak' => $presentStreak
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    error_log("Student Attendance Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'debug' => 'Internal server error'
    ]);
    } catch (Exception $e) {
    error_log("Student Attendance Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred',
        'debug' => 'Internal server error'
    ]);
    }
