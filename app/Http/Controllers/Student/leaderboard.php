<?php
/**
 * Student Portal — Leaderboard API
 * Rankings based on: exam scores + attendance rate
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user     = getCurrentUser();
$tenantId = $user['tenant_id'] ?? null;
$userId   = $user['id'] ?? null;

// Resolve student_id
$studentId = $_SESSION['userData']['student_id'] ?? null;
if (!$studentId && $userId) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("
            SELECT s.id, e.batch_id 
            FROM students s 
            LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
            WHERE s.user_id = :uid AND s.tenant_id = :tid 
            LIMIT 1
        ");
        $stmt->execute(['uid' => $userId, 'tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $studentId = $row['id'];
            $_SESSION['userData']['student_id'] = $studentId;
        }
    } catch (Exception $e) {
        error_log('Leaderboard: failed to resolve student_id: ' . $e->getMessage());
    }
}

if (!$tenantId || !$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student record not found']);
    exit;
}

$action = $_GET['action'] ?? 'batch';

try {
    $db = getDBConnection();

    // Get current student's batch
    $stmt = $db->prepare("
        SELECT batch_id 
        FROM enrollments 
        WHERE student_id = :sid AND tenant_id = :tid AND status = 'active' 
        LIMIT 1
    ");
    $stmt->execute(['sid' => $studentId, 'tid' => $tenantId]);
    $me = $stmt->fetch(PDO::FETCH_ASSOC);
    $batchId = $_GET['batch_id'] ?? ($me['batch_id'] ?? null);

    switch ($action) {

        case 'batch':
        default:
            // ── Exam score leaderboard within batch ────────────────────
            // Average score across all exam_attempts per student
            $stmt = $db->prepare("
                SELECT
                    s.id AS student_id,
                    u.name AS full_name,
                    s.photo_url,
                    s.roll_no,
                    COUNT(ea.id)           AS exams_taken,
                    ROUND(AVG(ea.score),1) AS avg_score,
                    MAX(ea.score)          AS best_score,
                    -- Attendance rate
                    ROUND(
                        100.0 * SUM(CASE WHEN a_stats.status = 'present' THEN 1 ELSE 0 END)
                        / NULLIF(COUNT(DISTINCT a_stats.id), 0)
                    , 1) AS attendance_pct
                FROM students s
                JOIN users u ON s.user_id = u.id
                JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                LEFT JOIN exam_attempts ea
                    ON ea.student_id = s.id AND ea.tenant_id = s.tenant_id
                LEFT JOIN attendance a_stats
                    ON a_stats.student_id = s.id AND a_stats.tenant_id = s.tenant_id
                WHERE e.batch_id = :bid AND s.tenant_id = :tid
                  AND (s.status = 'active' OR s.status IS NULL)
                  AND s.deleted_at IS NULL
                GROUP BY s.id, u.name, s.photo_url, s.roll_no
                ORDER BY avg_score DESC, attendance_pct DESC
                LIMIT 50
            ");
            $stmt->execute(['bid' => $batchId, 'tid' => $tenantId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Assign ranks
            $rank = 1;
            foreach ($rows as &$r) {
                $r['rank']      = $rank++;
                $r['is_me']     = ((int)$r['student_id'] === (int)$studentId);
                $r['avg_score'] = (float)($r['avg_score'] ?? 0);
                $r['attendance_pct'] = (float)($r['attendance_pct'] ?? 0);
                // composite score: 70% exam average + 30% attendance
                $r['composite_score'] = round(($r['avg_score'] * 0.7) + ($r['attendance_pct'] * 0.3), 1);
                // photo url
                if ($r['photo_url'] && strpos($r['photo_url'], '/public/') === false) {
                    $r['photo_url'] = '/public' . $r['photo_url'];
                }
            }
            unset($r);

            // Re-sort and re-rank by composite
            usort($rows, fn($a, $b) => $b['composite_score'] <=> $a['composite_score']);
            $rank = 1;
            foreach ($rows as &$r) $r['rank'] = $rank++;
            unset($r);

            // Get batch list for tab switching
            $batchesStmt = $db->prepare("
                SELECT b.id, b.name, c.name as course_name,
                       COUNT(DISTINCT e.id) as student_count
                FROM batches b
                JOIN enrollments e ON e.batch_id = b.id AND e.tenant_id = b.tenant_id AND e.status = 'active'
                LEFT JOIN courses c ON c.id = b.course_id
                WHERE b.tenant_id = :tid AND b.deleted_at IS NULL
                GROUP BY b.id, b.name, c.name
                ORDER BY b.status = 'active' DESC, b.start_date DESC
                LIMIT 20
            ");
            $batchesStmt->execute(['tid' => $tenantId]);
            $batches = $batchesStmt->fetchAll(PDO::FETCH_ASSOC);

            // My rank
            $myRank = null;
            foreach ($rows as $r) {
                if ($r['is_me']) { $myRank = $r; break; }
            }

            echo json_encode([
                'success'     => true,
                'data'        => $rows,
                'my_rank'     => $myRank,
                'batch_id'    => $batchId,
                'batches'     => $batches,
                'total'       => count($rows),
            ]);
            break;

        case 'attendance_only':
            // Pure attendance leaderboard
            $stmt = $db->prepare("
                SELECT
                    s.id AS student_id,
                    u.name AS full_name,
                    s.photo_url,
                    s.roll_no,
                    COUNT(a.id)             AS total_days,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_days,
                    ROUND(100.0 * SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END)
                          / NULLIF(COUNT(a.id), 0), 1) AS attendance_pct
                FROM students s
                JOIN users u ON s.user_id = u.id
                JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
                LEFT JOIN attendance a
                    ON a.student_id = s.id AND a.tenant_id = s.tenant_id
                WHERE e.batch_id = :bid AND s.tenant_id = :tid
                  AND s.deleted_at IS NULL
                GROUP BY s.id, u.name, s.photo_url, s.roll_no
                ORDER BY attendance_pct DESC, present_days DESC
                LIMIT 50
            ");
            $stmt->execute(['bid' => $batchId, 'tid' => $tenantId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $rank = 1;
            foreach ($rows as &$r) {
                $r['rank']  = $rank++;
                $r['is_me'] = ((int)$r['student_id'] === (int)$studentId);
                $r['attendance_pct'] = (float)($r['attendance_pct'] ?? 0);
                if ($r['photo_url'] && strpos($r['photo_url'], '/public/') === false) {
                    $r['photo_url'] = '/public' . $r['photo_url'];
                }
            }
            unset($r);

            echo json_encode(['success' => true, 'data' => $rows, 'batch_id' => $batchId]);
            break;
    }

} catch (Exception $e) {
    error_log('Leaderboard error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load leaderboard: ' . $e->getMessage()]);
}
