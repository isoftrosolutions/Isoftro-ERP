<?php
/**
 * Student Notices API
 * Handles notices and announcements for students
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

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

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
        case 'list':
            $type = $_GET['type'] ?? 'all'; // all, institute, batch, fee
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 10);
            $offset = ($page - 1) * $perPage;
            
            $whereClause = "n.tenant_id = :tid AND n.status = 'active'";
            $params = ['tid' => $tenantId];
            
            // Filter by target type
            if ($type === 'institute') {
                $whereClause .= " AND n.target_type = 'all'";
            } elseif ($type === 'batch') {
                $whereClause .= " AND (n.target_type = 'batch' AND n.target_id = :batch_id)";
                $params['batch_id'] = $batchId;
            } elseif ($type === 'fee') {
                $whereClause .= " AND n.category = 'fee'";
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) FROM notices n WHERE $whereClause";
            $stmt = $db->prepare($countSql);
            $stmt->execute($params);
            $totalCount = (int)$stmt->fetchColumn();
            
            // Get notices with read status
            $sql = "
                SELECT n.*, 
                       CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END as is_read,
                       nr.read_at
                FROM notices n
                LEFT JOIN notice_reads nr ON n.id = nr.notice_id 
                    AND nr.student_id = :sid
                WHERE $whereClause
                ORDER BY n.is_important DESC, n.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $params['sid'] = $studentId;
            $params['limit'] = $perPage;
            $params['offset'] = $offset;
            
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue(":$key", $value, $type);
            }
            $stmt->execute();
            $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get unread count
            $unreadSql = "
                SELECT COUNT(*) FROM notices n
                WHERE n.tenant_id = :tid 
                  AND n.status = 'active'
                  AND NOT EXISTS (
                      SELECT 1 FROM notice_reads nr 
                      WHERE nr.notice_id = n.id AND nr.student_id = :sid
                  )
            ";
            $stmt = $db->prepare($unreadSql);
            $stmt->execute(['tid' => $tenantId, 'sid' => $studentId]);
            $unreadCount = (int)$stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'data' => $notices,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage)
                ],
                'unread_count' => $unreadCount
            ]);
            break;
            
        case 'mark_read':
            if ($method !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $noticeId = $input['notice_id'] ?? null;
            
            if (!$noticeId) {
                echo json_encode(['success' => false, 'message' => 'Notice ID required']);
                exit;
            }
            
            // Check if already marked
            $stmt = $db->prepare("
                SELECT id FROM notice_reads 
                WHERE notice_id = :nid AND student_id = :sid
            ");
            $stmt->execute(['nid' => $noticeId, 'sid' => $studentId]);
            
            if (!$stmt->fetch()) {
                $stmt = $db->prepare("
                    INSERT INTO notice_reads (notice_id, student_id, read_at)
                    VALUES (:nid, :sid, NOW())
                ");
                $stmt->execute(['nid' => $noticeId, 'sid' => $studentId]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Marked as read']);
            break;
            
        case 'mark_all_read':
            if ($method !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
            }
            
            // Mark all unread notices as read
            $stmt = $db->prepare("
                INSERT INTO notice_reads (notice_id, student_id, read_at)
                SELECT n.id, :sid, NOW()
                FROM notices n
                WHERE n.tenant_id = :tid
                  AND n.status = 'active'
                  AND NOT EXISTS (
                      SELECT 1 FROM notice_reads nr 
                      WHERE nr.notice_id = n.id AND nr.student_id = :sid2
                  )
            ");
            $stmt->execute([
                'sid' => $studentId,
                'tid' => $tenantId,
                'sid2' => $studentId
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'All notices marked as read',
                'marked_count' => $stmt->rowCount()
            ]);
            break;
            
        case 'detail':
            $noticeId = $_GET['notice_id'] ?? null;
            
            if (!$noticeId) {
                echo json_encode(['success' => false, 'message' => 'Notice ID required']);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT n.*, 
                       CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END as is_read,
                       nr.read_at,
                       u.name as posted_by_name
                FROM notices n
                LEFT JOIN notice_reads nr ON n.id = nr.notice_id AND nr.student_id = :sid
                LEFT JOIN users u ON n.created_by = u.id
                WHERE n.id = :nid AND n.tenant_id = :tid
            ");
            $stmt->execute(['nid' => $noticeId, 'sid' => $studentId, 'tid' => $tenantId]);
            $notice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$notice) {
                echo json_encode(['success' => false, 'message' => 'Notice not found']);
                exit;
            }
            
            // Auto-mark as read
            if (!$notice['is_read']) {
                $stmt = $db->prepare("
                    INSERT INTO notice_reads (notice_id, student_id, read_at)
                    VALUES (:nid, :sid, NOW())
                ");
                $stmt->execute(['nid' => $noticeId, 'sid' => $studentId]);
            }
            
            echo json_encode(['success' => true, 'data' => $notice]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    error_log("Student Notices Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'code' => 'DB_ERROR'
    ]);
} catch (Exception $e) {
    error_log("Student Notices Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred',
        'code' => 'GENERAL_ERROR'
    ]);
}
