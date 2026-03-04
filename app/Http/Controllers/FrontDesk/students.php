<?php
/**
 * Front Desk / Institute Admin — Student Registration Controller
 *
 * POST  → Quick Registration (creates user + minimal student record)
 * PUT   → Full Profile Completion (updates existing student record)
 * GET   → List students or single student for this tenant
 *
 * Access:
 *   - frontdesk / instituteadmin: full CRUD
 *   - student: read own record only (handled by Admin controller)
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

header('Content-Type: application/json');

// Load Composer autoload (PHPMailer etc.)
if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../../vendor/autoload.php';
}
use App\Helpers\MailHelper;


// CSRF and role check via Middleware
require_once app_path('Http/Middleware/FrontDeskMiddleware.php');
$auth = FrontDeskMiddleware::check();
$tenantId = $auth['tenant_id'];
$role = $auth['role'];
$userId = $auth['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDBConnection();

    // ────────────────────────────────────────────────────────
    // GET — list students or single student
    // ────────────────────────────────────────────────────────
    if ($method === 'GET') {
        $id = !empty($_GET['id']) ? (int)$_GET['id'] : null;
        
        if ($id) {
            // Full details for single student
            $query = "SELECT s.*, b.name AS batch_name, c.name AS course_name, c.id AS course_id
                      FROM   students s
                      LEFT JOIN batches b  ON s.batch_id = b.id
                      LEFT JOIN courses c  ON b.course_id = c.id
                      WHERE  s.id = :sid AND s.tenant_id = :tid AND s.deleted_at IS NULL";
            $params = ['sid' => $id, 'tid' => $tenantId];
            // Pagination
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Count total first (with same filters)
            $countQuery = "SELECT COUNT(*) FROM students s WHERE s.tenant_id = :tid AND s.deleted_at IS NULL";
            $countParams = ['tid' => $tenantId];
            if (!empty($_GET['search'])) {
                $countQuery .= " AND (s.full_name LIKE :s OR s.roll_no LIKE :s OR s.phone LIKE :s OR s.email LIKE :s)";
                $countParams['s'] = '%' . $_GET['search'] . '%';
            }
            if (!empty($_GET['registration_status'])) {
                $countQuery .= " AND s.registration_status = :rs";
                $countParams['rs'] = $_GET['registration_status'];
            }
            $totalStmt = $db->prepare($countQuery);
            $totalStmt->execute($countParams);
            $totalRecords = (int)$totalStmt->fetchColumn();

            $query .= " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;
        }

        $stmt = $db->prepare($query);
        // Bind parameters manually to ensure correct types for LIMIT/OFFSET
        foreach ($params as $key => $val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":{$key}", $val, $type);
        }
        $stmt->execute();


        if (!empty($_GET['id'])) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true, 
                'data' => $rows, 
                'total' => $totalRecords,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalRecords / $limit)
            ]);
        }
        exit;
    }

    // ────────────────────────────────────────────────────────
    // POST — Quick Registration
    // ────────────────────────────────────────────────────────
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        // Required fields for Admission
        $required = ['full_name', 'email', 'password', 'batch_id', 'dob_ad', 'gender', 'father_name', 'permanent_address'];
        $missing  = [];
        foreach ($required as $f) {
            if (empty($input[$f])) $missing[] = $f;
        }
        if (!empty($missing)) {
            echo json_encode(['success' => false, 'message' => 'Required fields missing: ' . implode(', ', $missing)]);
            exit;
        }

        $fullName = trim($input['full_name']);
        $email    = trim($input['email']);
        $password = $input['password'];
        $batchId  = (int)$input['batch_id'];
        $phone    = !empty($input['contact_number']) ? trim($input['contact_number'])
                  : (!empty($input['phone']) ? trim($input['phone']) : null);

        // Optional/Full Fields
        $dobAd         = $input['dob_ad'];
        $dobBs         = $input['dob_bs'] ?? null;
        $gender        = $input['gender'];
        $bloodGroup    = $input['blood_group'] ?? null;
        $citizenshipNo = $input['citizenship_no'] ?? $input['citizenship'] ?? null;
        $fatherName    = trim($input['father_name'] ?? '');
        
        // Handle Addresses
        $permanentAddr = $input['permanent_address'] ?? null;
        if (!empty($permanentAddr) && !isJson($permanentAddr)) {
            $permanentAddr = json_encode(['address' => $permanentAddr]);
        }
        $temporaryAddr = $input['temporary_address'] ?? null;
        if (!empty($temporaryAddr) && !isJson($temporaryAddr)) {
            $temporaryAddr = json_encode(['address' => $temporaryAddr]);
        }

        $academicQual = $input['academic_qualifications'] ?? $input['academic_qualification'] ?? '[]';
        if (is_array($academicQual)) $academicQual = json_encode($academicQual);

        // Validate batch belongs to this tenant and is active/upcoming
        $stmt = $db->prepare("SELECT id FROM batches WHERE id = :bid AND tenant_id = :tid AND status IN ('active', 'upcoming') AND deleted_at IS NULL");
        $stmt->execute(['bid' => $batchId, 'tid' => $tenantId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Invalid batch selected.']);
            exit;
        }

        $db->beginTransaction();

        // 1. Create user account
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['email' => $email, 'tid' => $tenantId]);
        if ($stmt->fetch()) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => "A user account with email '{$email}' already exists."]);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO users (tenant_id, role, email, password_hash, phone, name, status, created_at, updated_at)
            VALUES (:tid, 'student', :email, :pass, :phone, :name, 'active', NOW(), NOW())
        ");
        $stmt->execute([
            'tid'   => $tenantId,
            'email' => $email,
            'pass'  => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'phone' => $phone,
            'name'  => $fullName,
        ]);
        $userId = $db->lastInsertId();

        // 2. Generate roll number (Standard format)
        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = :tid");
        $stmt->execute(['tid' => $tenantId]);
        $count = (int)$stmt->fetchColumn() + 1;
        $rollNo = 'STU-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        // 3. Create student record
        $stmt = $db->prepare("
            INSERT INTO students (
                tenant_id, user_id, batch_id, roll_no, full_name,
                phone, email,
                dob_ad, dob_bs, gender, blood_group,
                citizenship_no, father_name,
                permanent_address, temporary_address,
                academic_qualifications,
                status, registration_mode, registration_status,
                admission_date,
                created_at, updated_at
            ) VALUES (
                :tid, :uid, :bid, :roll, :name,
                :phone, :email,
                :dob_ad, :dob_bs, :gender, :blood,
                :citiz, :father,
                :perm, :temp,
                :qual,
                'active', 'full', 'fully_registered',
                CURDATE(),
                NOW(), NOW()
            )
        ");
        $stmt->execute([
            'tid'    => $tenantId,
            'uid'    => $userId,
            'bid'    => $batchId,
            'roll'   => $rollNo,
            'name'   => $fullName,
            'phone'  => $phone,
            'email'  => $email,
            'dob_ad' => $dobAd,
            'dob_bs' => $dobBs,
            'gender' => $gender,
            'blood'  => $bloodGroup,
            'citiz'  => $citizenshipNo,
            'father' => $fatherName,
            'perm'   => $permanentAddr,
            'temp'   => $temporaryAddr,
            'qual'   => $academicQual,
        ]);
        $studentId = $db->lastInsertId();

        $studentId = $db->lastInsertId();
        $db->commit();

        // ── Fire-and-forget: send login credentials to student ──
        MailHelper::sendStudentCredentials($db, $tenantId, [
            'full_name'      => $fullName,
            'email'          => $email,
            'plain_password' => $password, 
        ]);

        // Security: clear plain password from memory
        unset($password);

        echo json_encode([
            'success'    => true,
            'message'    => "Student '{$fullName}' registered successfully! Login credentials have been emailed.",
            'student_id' => $studentId,
            'roll_no'    => $rollNo,
            'mode'       => 'quick_registered',
        ]);
        exit;
    }

    // ────────────────────────────────────────────────────────
    // PUT — Full Profile Completion (idempotent, no duplicates)
    // ────────────────────────────────────────────────────────
    if ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $studentId = (int)($input['id'] ?? 0);
        if (!$studentId) {
            echo json_encode(['success' => false, 'message' => 'Student ID is required for profile completion.']);
            exit;
        }

        // Verify student belongs to this tenant
        $stmt = $db->prepare("SELECT id, user_id, registration_status FROM students WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute(['id' => $studentId, 'tid' => $tenantId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student not found.']);
            exit;
        }

        // Validate mandatory full-mode fields
        $requiredFull = ['dob_ad', 'gender', 'parent_or_husband_name', 'permanent_address'];
        $missingFull  = [];
        foreach ($requiredFull as $f) {
            if (empty($input[$f])) $missingFull[] = $f;
        }
        if (!empty($missingFull)) {
            echo json_encode(['success' => false, 'message' => 'Required fields for full registration: ' . implode(', ', $missingFull)]);
            exit;
        }

        $db->beginTransaction();

        // Handle date conversion
        $dobAd = $input['dob_ad'];
        $dobBs = $input['dob_bs'] ?? null;

        if (!empty($dobAd) && empty($dobBs)) {
            if (class_exists('App\\Helpers\\DateUtils')) {
                try { $dobBs = \App\Helpers\DateUtils::adToBs($dobAd); } catch (\Throwable $e) {}
            }
        }
        if (empty($dobAd) && !empty($dobBs)) {
            if (class_exists('App\\Helpers\\DateUtils')) {
                try { $dobAd = \App\Helpers\DateUtils::bsToAd($dobBs); } catch (\Throwable $e) {}
            }
        }

        // Resolve parent/husband field — the flowchart calls it parent_or_husband_name
        $parentHusband = trim($input['parent_or_husband_name'] ?? '');
        // Store in father_name (generic parent/guardian) by convention
        $fatherName  = $parentHusband;
        $husbandName = null;
        // If gender is female and they explicitly pass husband_name, use it
        if (!empty($input['husband_name'])) {
            $husbandName = trim($input['husband_name']);
            $fatherName  = null;
        } elseif (!empty($input['father_name'])) {
            $fatherName  = trim($input['father_name']);
        }

        // Handle JSON address fields
        $permanentAddr = $input['permanent_address'] ?? null;
        if (is_array($permanentAddr)) $permanentAddr = json_encode($permanentAddr);
        elseif (!empty($permanentAddr) && !isJson($permanentAddr)) {
            $permanentAddr = json_encode(['address' => $permanentAddr]);
        }

        $temporaryAddr = $input['temporary_address'] ?? null;
        if (is_array($temporaryAddr)) $temporaryAddr = json_encode($temporaryAddr);
        elseif (!empty($temporaryAddr) && !isJson($temporaryAddr)) {
            $temporaryAddr = json_encode(['address' => $temporaryAddr]);
        }

        $academicQual = $input['academic_qualifications'] ?? $input['academic_qualification'] ?? '[]';
        if (is_array($academicQual)) $academicQual = json_encode($academicQual);

        // Handle batch update (if course/batch was switched)
        $batchId = !empty($input['batch_id']) ? (int)$input['batch_id'] : null;
        if ($batchId) {
            $stmt = $db->prepare("SELECT id FROM batches WHERE id = :bid AND tenant_id = :tid AND status IN ('active', 'upcoming') AND deleted_at IS NULL");
            $stmt->execute(['bid' => $batchId, 'tid' => $tenantId]);
            if (!$stmt->fetch()) $batchId = null; // ignore invalid batch
        }

        // Build dynamic UPDATE
        $fields = [
            'dob_ad'                  => $dobAd,
            'dob_bs'                  => $dobBs,
            'gender'                  => $input['gender'],
            'blood_group'             => $input['blood_group'] ?? null,
            'citizenship_no'          => $input['citizenship'] ?? $input['citizenship_no'] ?? null,
            'father_name'             => $fatherName,
            'husband_name'            => $husbandName,
            'permanent_address'       => $permanentAddr,
            'temporary_address'       => $temporaryAddr,
            'academic_qualifications' => $academicQual,
            'registration_mode'       => 'full',
            'registration_status'     => 'fully_registered',
            'updated_at'              => date('Y-m-d H:i:s'),
        ];
        if ($batchId) $fields['batch_id'] = $batchId;

        // Also update full_name / phone if provided
        if (!empty($input['full_name']))   $fields['full_name'] = trim($input['full_name']);
        if (!empty($input['phone']))       $fields['phone']     = trim($input['phone']);
        if (!empty($input['contact_number'])) $fields['phone']  = trim($input['contact_number']);

        $setParts = [];
        $params   = ['id' => $studentId, 'tid' => $tenantId];
        foreach ($fields as $col => $val) {
            $setParts[] = "`{$col}` = :{$col}";
            $params[$col] = $val;
        }

        $sql = "UPDATE students SET " . implode(', ', $setParts)
             . " WHERE id = :id AND tenant_id = :tid";
        $db->prepare($sql)->execute($params);

        // Sync user name/phone if student has a linked user
        if ($student['user_id']) {
            $uFields = ['updated_at' => date('Y-m-d H:i:s')];
            if (!empty($fields['full_name'])) $uFields['name']  = $fields['full_name'];
            if (!empty($fields['phone']))     $uFields['phone'] = $fields['phone'];

            $uParts  = [];
            $uParams = ['uid' => $student['user_id']];
            foreach ($uFields as $c => $v) {
                $uParts[]  = "`{$c}` = :{$c}";
                $uParams[$c] = $v;
            }
            $db->prepare("UPDATE users SET " . implode(', ', $uParts) . " WHERE id = :uid")->execute($uParams);
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Student profile fully completed and registered successfully!",
            'student_id' => $studentId,
            'status'  => 'fully_registered',
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Method not supported.']);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log("FrontDesk Students DB Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log("FrontDesk Students Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}

// ── Helper ───────────────────────────────────────────────────
function isJson($str) {
    if (!is_string($str)) return false;
    json_decode($str);
    return json_last_error() === JSON_ERROR_NONE;
}
