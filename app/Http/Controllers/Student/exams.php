<?php
/**
 * Student Exams & Results API
 * Handles exam viewing, results, and performance analytics for students
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
$studentId = $_SESSION['userData']['student_id'] ?? null;

if (!$tenantId || !$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student record not found']);
    exit;
}

$action = $_GET['action'] ?? 'available';

try {
    $db = getDBConnection();
    
    // Get student's batch info from enrollments
    $stmt = $db->prepare("
        SELECT batch_id FROM enrollments 
        WHERE student_id = :sid AND tenant_id = :tid AND status = 'active' 
        LIMIT 1
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    $batchId = $enrollment['batch_id'] ?? null;
    
    switch ($action) {
        case 'available':
            // Get upcoming/available exams
            $stmt = $db->prepare("
                SELECT e.*, s.name as subject_name, s.code as subject_code
                FROM exams e
                LEFT JOIN subjects s ON e.subject_id = s.id
                WHERE e.batch_id = :bid
                  AND e.tenant_id = :tid
                  AND e.exam_date >= CURDATE()
                  AND e.status = 'published'
                ORDER BY e.exam_date ASC
            ");
            $stmt->execute(['bid' => $batchId, 'tid' => $tenantId]);
            $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $exams
            ]);
            break;
            
        case 'results':
            // Get student's exam results
            $stmt = $db->prepare("
                SELECT er.*, e.title as exam_title, e.exam_date, e.total_marks, e.passing_marks,
                       s.name as subject_name, s.code as subject_code
                FROM exam_results er
                JOIN exams e ON er.exam_id = e.id
                LEFT JOIN subjects s ON e.subject_id = s.id
                WHERE er.student_id = :sid
                  AND er.tenant_id = :tid
                ORDER BY e.exam_date DESC
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate statistics
            $totalExams = count($results);
            $passedExams = 0;
            $totalPercentage = 0;
            
            foreach ($results as $result) {
                if ($result['marks_obtained'] >= $result['passing_marks']) {
                    $passedExams++;
                }
                $percentage = ($result['marks_obtained'] / $result['total_marks']) * 100;
                $totalPercentage += $percentage;
            }
            
            $averagePercentage = $totalExams > 0 ? round($totalPercentage / $totalExams, 2) : 0;
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'results' => $results,
                    'stats' => [
                        'total_exams' => $totalExams,
                        'passed' => $passedExams,
                        'failed' => $totalExams - $passedExams,
                        'average_percentage' => $averagePercentage
                    ]
                ]
            ]);
            break;
            
        case 'analytics':
            // Get performance analytics data
            $stmt = $db->prepare("
                SELECT 
                    s.name as subject_name,
                    AVG((er.marks_obtained / e.total_marks) * 100) as average_percentage,
                    COUNT(*) as exam_count
                FROM exam_results er
                JOIN exams e ON er.exam_id = e.id
                LEFT JOIN subjects s ON e.subject_id = s.id
                WHERE er.student_id = :sid
                  AND er.tenant_id = :tid
                GROUP BY e.subject_id
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $subjectPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get trend data (last 6 months)
            $stmt = $db->prepare("
                SELECT 
                    DATE_FORMAT(e.exam_date, '%Y-%m') as month,
                    AVG((er.marks_obtained / e.total_marks) * 100) as avg_percentage
                FROM exam_results er
                JOIN exams e ON er.exam_id = e.id
                WHERE er.student_id = :sid
                  AND er.tenant_id = :tid
                  AND e.exam_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(e.exam_date, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
            $trendData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'subject_performance' => $subjectPerformance,
                    'trend_data' => $trendData
                ]
            ]);
            break;
            
        case 'leaderboard':
            // Get batch leaderboard
            $examId = $_GET['exam_id'] ?? null;
            
            if ($examId) {
                // Leaderboard for specific exam
                $stmt = $db->prepare("
                    SELECT 
                        er.marks_obtained,
                        er.rank_position,
                        u.name as student_name,
                        s.roll_no,
                        e.total_marks
                    FROM exam_results er
                    JOIN students s ON er.student_id = s.id
                    JOIN exams e ON er.exam_id = e.id
                    WHERE er.exam_id = :eid
                      AND er.tenant_id = :tid
                    ORDER BY er.marks_obtained DESC, er.rank_position ASC
                    LIMIT 10
                ");
                $stmt->execute(['eid' => $examId, 'tid' => $tenantId]);
            } else {
                // Overall batch leaderboard (based on average)
                $stmt = $db->prepare("
                    SELECT 
                        u.name as student_name,
                        s.roll_no,
                        AVG((er.marks_obtained / e.total_marks) * 100) as average_percentage
                    FROM exam_results er
                    JOIN students s ON er.student_id = s.id
                    JOIN exams e ON er.exam_id = e.id
                    WHERE s.batch_id = :bid
                      AND er.tenant_id = :tid
                    GROUP BY er.student_id
                    ORDER BY average_percentage DESC
                    LIMIT 10
                ");
                $stmt->execute(['bid' => $batchId, 'tid' => $tenantId]);
            }
            
            $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Find current student's position
            $myPosition = null;
            foreach ($leaderboard as $index => $entry) {
                if (isset($entry['student_id']) && $entry['student_id'] == $studentId) {
                    $myPosition = $index + 1;
                    break;
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $leaderboard,
                'my_position' => $myPosition
            ]);
            break;
            
        case 'mock_tests':
            // Get available mock tests
            $stmt = $db->prepare("
                SELECT mt.*, s.name as subject_name
                FROM mock_tests mt
                LEFT JOIN subjects s ON mt.subject_id = s.id
                WHERE mt.batch_id = :bid
                  AND mt.tenant_id = :tid
                  AND mt.status = 'active'
                ORDER BY mt.created_at DESC
            ");
            $stmt->execute(['bid' => $batchId, 'tid' => $tenantId]);
            $mockTests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $mockTests
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    error_log("Student Exams Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'code' => 'DB_ERROR'
    ]);
    } catch (Exception $e) {
    error_log("Student Exams Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred',
        'code' => 'GENERAL_ERROR'
    ]);
    }
