<?php
/**
 * Exams API Controller
 * Real schema: exams(id, tenant_id, batch_id, course_id, created_by_user_id,
 *   title, duration_minutes, total_marks, negative_mark, question_mode,
 *   start_at, end_at, status, created_at, updated_at, deleted_at)
 *
 * GET  → list exams
 * POST (action=create) → create new exam
 * POST (action=update) → update exam
 * POST (action=delete) → soft-delete exam
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['userData']['role'] ?? '';
if (!in_array($role, ['instituteadmin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$userId = $_SESSION['userData']['id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDBConnection();

    /* ── GET: List exams ──────────────────────────────────────────── */
    if ($method === 'GET') {
        $query = "SELECT e.id, e.title, e.duration_minutes, e.total_marks,
                         e.negative_mark, e.question_mode, e.start_at, e.end_at,
                         e.status, e.created_at,
                         b.name as batch_name, c.name as course_name
                  FROM exams e
                  LEFT JOIN batches b ON e.batch_id = b.id
                  LEFT JOIN courses c ON e.course_id = c.id
                  WHERE e.tenant_id = :tid AND e.deleted_at IS NULL";
        $params = ['tid' => $tenantId];

        if (!empty($_GET['status'])) {
            $query .= " AND e.status = :status";
            $params['status'] = $_GET['status'];
        }
        if (!empty($_GET['batch_id'])) {
            $query .= " AND e.batch_id = :batch_id";
            $params['batch_id'] = $_GET['batch_id'];
        }
        if (!empty($_GET['search'])) {
            $query .= " AND e.title LIKE :search";
            $params['search'] = '%' . $_GET['search'] . '%';
        }

        $query .= " ORDER BY e.start_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute exam_date and status labels for frontend
        foreach ($exams as &$ex) {
            $ex['exam_date']  = $ex['start_at'] ? substr($ex['start_at'], 0, 10) : null;
            $ex['start_time'] = $ex['start_at'] ? substr($ex['start_at'], 11, 5)  : null;
            $ex['end_time']   = $ex['end_at']   ? substr($ex['end_at'],   11, 5)  : null;
        }
        unset($ex);

        echo json_encode(['success' => true, 'data' => $exams]);
        exit;
    }

    /* ── POST ─────────────────────────────────────────────────────── */
    if ($method === 'POST') {
        $body   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $body['action'] ?? 'create';

        // ── CREATE ──────────────────────────────────────────────────
        if ($action === 'create') {
            $required = ['title', 'batch_id', 'course_id', 'exam_date', 'start_time', 'end_time', 'total_marks', 'duration_minutes'];
            foreach ($required as $f) {
                if (empty($body[$f])) {
                    echo json_encode(['success' => false, 'message' => "Field '{$f}' is required."]);
                    exit;
                }
            }

            $startAt = $body['exam_date'] . ' ' . $body['start_time'] . ':00';
            $endAt   = $body['exam_date'] . ' ' . $body['end_time']   . ':00';

            // Auto status
            $now    = new DateTime();
            $start  = new DateTime($startAt);
            $end    = new DateTime($endAt);
            if ($now < $start)     $status = 'scheduled';
            elseif ($now > $end)   $status = 'completed';
            else                   $status = 'active';

            $stmt = $db->prepare("
                INSERT INTO exams
                    (tenant_id, batch_id, course_id, created_by_user_id,
                     title, duration_minutes, total_marks, negative_mark,
                     question_mode, start_at, end_at, status, created_at, updated_at)
                VALUES
                    (:tid, :batch_id, :course_id, :creator,
                     :title, :duration, :total_marks, :neg_mark,
                     :q_mode, :start_at, :end_at, :status, NOW(), NOW())
            ");
            $stmt->execute([
                'tid'         => $tenantId,
                'batch_id'    => (int)$body['batch_id'],
                'course_id'   => (int)$body['course_id'],
                'creator'     => $userId,
                'title'       => trim($body['title']),
                'duration'    => (int)$body['duration_minutes'],
                'total_marks' => (float)$body['total_marks'],
                'neg_mark'    => (float)($body['negative_mark'] ?? 0),
                'q_mode'      => $body['question_mode'] ?? 'manual',
                'start_at'    => $startAt,
                'end_at'      => $endAt,
                'status'      => $status,
            ]);

            $newId = $db->lastInsertId();
            echo json_encode(['success' => true, 'message' => 'Exam scheduled successfully.', 'id' => $newId]);
            exit;
        }

        // ── UPDATE ──────────────────────────────────────────────────
        if ($action === 'update') {
            if (empty($body['id'])) { echo json_encode(['success' => false, 'message' => 'Exam ID required.']); exit; }

            $startAt = ($body['exam_date'] ?? date('Y-m-d')) . ' ' . ($body['start_time'] ?? '09:00') . ':00';
            $endAt   = ($body['exam_date'] ?? date('Y-m-d')) . ' ' . ($body['end_time']   ?? '11:00') . ':00';
            $now     = new DateTime();
            $start   = new DateTime($startAt);
            $end     = new DateTime($endAt);
            if ($now < $start)   $status = 'scheduled';
            elseif ($now > $end) $status = 'completed';
            else                 $status = 'active';

            $stmt = $db->prepare("
                UPDATE exams SET
                    batch_id = :batch_id, course_id = :course_id, title = :title,
                    duration_minutes = :duration, total_marks = :total_marks,
                    negative_mark = :neg_mark, question_mode = :q_mode,
                    start_at = :start_at, end_at = :end_at, status = :status,
                    updated_at = NOW()
                WHERE id = :id AND tenant_id = :tid
            ");
            $stmt->execute([
                'id'          => (int)$body['id'],
                'tid'         => $tenantId,
                'batch_id'    => (int)($body['batch_id'] ?? 0),
                'course_id'   => (int)($body['course_id'] ?? 0),
                'title'       => trim($body['title'] ?? ''),
                'duration'    => (int)($body['duration_minutes'] ?? 60),
                'total_marks' => (float)($body['total_marks'] ?? 100),
                'neg_mark'    => (float)($body['negative_mark'] ?? 0),
                'q_mode'      => $body['question_mode'] ?? 'manual',
                'start_at'    => $startAt,
                'end_at'      => $endAt,
                'status'      => $status,
            ]);
            echo json_encode(['success' => true, 'message' => 'Exam updated successfully.']);
            exit;
        }

        // ── DELETE (soft) ───────────────────────────────────────────
        if ($action === 'delete') {
            if (empty($body['id'])) { echo json_encode(['success' => false, 'message' => 'Exam ID required.']); exit; }
            $stmt = $db->prepare("UPDATE exams SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => (int)$body['id'], 'tid' => $tenantId]);
            echo json_encode(['success' => true, 'message' => 'Exam deleted.']);
            exit;
        }

        echo json_encode(['success' => false, 'message' => "Unknown action: {$action}"]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
