<?php
/**
 * StudentService
 * Handles complex business logic for students
 */

namespace App\Services;

use App\Models\Student;
use App\Models\User;
// use App\Helpers\MailHelper; // Remove or comment out if not reliably namespaced
use Exception;
use Illuminate\Support\Facades\DB;

class StudentService {
    private $db;
    private $studentModel;
    private $userModel;

    public function __construct() {
        $this->studentModel = new Student();
        $this->userModel = new User();
        
        if (class_exists('\Illuminate\Support\Facades\DB') && \Illuminate\Support\Facades\DB::getFacadeRoot()) {
            $this->db = \Illuminate\Support\Facades\DB::connection()->getPdo();
        } elseif (function_exists('getDBConnection')) {
            $this->db = getDBConnection();
        }
    }

    /**
     * Register a new student with transaction
     */
    public function registerStudent($input, $tenantId) {
        $startedTransaction = false;
        if ($this->db instanceof \PDO && !$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $startedTransaction = true;
        } elseif (class_exists('Illuminate\Support\Facades\DB') && \Illuminate\Support\Facades\DB::getFacadeRoot() && !\Illuminate\Support\Facades\DB::transactionLevel()) {
            \Illuminate\Support\Facades\DB::beginTransaction();
            $startedTransaction = true;
        }

        try {
            // 1. Prepare User Data
            $fullName = $input['full_name'] ?? '';
            $email = $input['email'] ?? null;
            $phone = $input['contact_number'] ?? $input['phone'] ?? null;
            $password = $input['password'] ?? 'Student@123'; 
            $studentId = $input['student_id'] ?? null;

            if (empty($email) && !$studentId) {
                throw new Exception("Email address is required for student registration.");
            }

            // 2. Create/Reuse User Account
            $existingUser = null;
            if ($email) {
                if ($this->db instanceof \PDO) {
                    $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND tenant_id = ? AND deleted_at IS NULL");
                    $stmt->execute([$email, $tenantId]);
                    $existingUser = $stmt->fetch(\PDO::FETCH_OBJ);
                } elseif (class_exists('Illuminate\Support\Facades\DB')) {
                    $existingUser = \Illuminate\Support\Facades\DB::table('users')
                        ->where('email', $email)
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at')
                        ->first();
                }
            }

            if ($existingUser) {
                $userId = $existingUser->id;
            } elseif ($studentId) {
                // Fetch userId from existing student
                if ($this->db instanceof \PDO) {
                    $stmt = $this->db->prepare("SELECT user_id FROM students WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$studentId, $tenantId]);
                    $userId = $stmt->fetchColumn();
                } else {
                    $userId = DB::table('students')->where('id', $studentId)->where('tenant_id', $tenantId)->value('user_id');
                }
                if (!$userId) throw new Exception("Existing student profile has no associated user account.");
            } else {
                $user = $this->userModel->createUser([
                    'tenant_id' => $tenantId,
                    'role' => 'student',
                    'email' => $email,
                    'password' => $password,
                    'name' => $fullName,
                    'phone' => $phone,
                    'status' => 'active'
                ]);
                $userId = $user['id'];
            }

            // 3. Prepare Student Data & Conversions
            $studentData = $input;
            $studentData['tenant_id'] = $tenantId;
            $studentData['user_id'] = $userId;
            $studentData['phone'] = $phone;
            
            // Handle Date Conversions
            if (empty($studentData['dob_ad']) && !empty($studentData['dob_bs'])) {
                try {
                    if (class_exists('DateUtils')) {
                        $studentData['dob_ad'] = DateUtils::bsToAd($studentData['dob_bs']);
                    } elseif (class_exists('\App\Helpers\DateUtils')) {
                        $studentData['dob_ad'] = \App\Helpers\DateUtils::bsToAd($studentData['dob_bs']);
                    }
                } catch (\Throwable $e) {}
            } elseif (!empty($studentData['dob_ad']) && empty($studentData['dob_bs'])) {
                try {
                    if (class_exists('DateUtils')) {
                        $studentData['dob_bs'] = DateUtils::adToBs($studentData['dob_ad']);
                    } elseif (class_exists('\App\Helpers\DateUtils')) {
                        $studentData['dob_bs'] = \App\Helpers\DateUtils::adToBs($studentData['dob_ad']);
                    }
                } catch (\Throwable $e) {}
            }
            if (empty($studentData['dob_ad'])) $studentData['dob_ad'] = date('Y-m-d');

            // Generate Roll Number if not provided
            if (empty($studentData['roll_no'])) {
                $studentData['roll_no'] = $this->studentModel->generateRollNo($tenantId);
            }
            
            // 4. Create Student Record (Audit logged inside Model)
            $studentId = $input['student_id'] ?? null;
            $student = null;

            if (!$studentId) {
                $student = $this->studentModel->create($studentData);
                $studentId = $student['id'];
            } else {
                // Fetch existing student for the result array
                if ($this->db instanceof \PDO) {
                    $stmt = $this->db->prepare("SELECT * FROM students WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$studentId, $tenantId]);
                    $student = (array) $stmt->fetch(\PDO::FETCH_ASSOC);
                } else {
                    $student = (array) DB::table('students')->where('id', $studentId)->where('tenant_id', $tenantId)->first();
                }
            }

            // 5. Handle Course Enrollment & Fees
            $batchIds = $input['batch_ids'] ?? (isset($input['batch_id']) ? [$input['batch_id']] : []);
            $enrollmentIds = [];

            foreach ($batchIds as $batchId) {
                try {
                    $enrollmentId = $this->enrollInBatch($studentId, $batchId, $tenantId);
                    if ($enrollmentId) $enrollmentIds[] = $enrollmentId;
                } catch (Exception $e) {
                    // If multiple batches, we might want to continue, but for single batch requests (common), we should re-throw
                    if (count($batchIds) === 1) throw $e;
                    error_log("Enrollment skip for batch $batchId: " . $e->getMessage());
                }
            }

            if ($studentId && empty($enrollmentIds)) {
                throw new Exception("No new enrollments were created. The student might already be enrolled in all selected batches.");
            }

            if ($startedTransaction) {
                if ($this->db instanceof \PDO && $this->db->inTransaction()) {
                    $this->db->commit();
                } elseif (class_exists('Illuminate\Support\Facades\DB') && \Illuminate\Support\Facades\DB::getFacadeRoot()) {
                    \Illuminate\Support\Facades\DB::commit();
                }
            }

            // Post-commit: Audit log (only for NEW student creation)
            if (!$input['student_id'] && class_exists('\App\Helpers\AuditLogger')) {
                \App\Helpers\AuditLogger::log('CREATE', 'students', $studentId, null, $student);
            }

            // 6. Post-Registration: Send Welcome Email (only if new student or explicitly requested)
            if (empty($input['student_id']) && !empty($email) && !empty($password)) {
                 // For now just using the first batch's course data for the email
                 $firstBatchId = $batchIds[0] ?? null;
                 $courseData = null;
                 if ($firstBatchId) {
                    $stmt = $this->db->prepare("SELECT b.name as batch_name, b.shift as batch_shift, c.name as course_name FROM batches b JOIN courses c ON b.course_id = c.id WHERE b.id = ?");
                    $stmt->execute([$firstBatchId]);
                    $courseData = $stmt->fetch(\PDO::FETCH_ASSOC);
                 }
                 $this->sendWelcomeEmail($tenantId, $studentId, $fullName, $email, $password, $courseData, $student['roll_no'] ?? 'N/A');
            }

            return [
                'student' => $student,
                'enrollment_ids' => $enrollmentIds,
                'enrollment_id' => $enrollmentIds[0] ?? null // for backward compatibility
            ]; 


        } catch (Exception $e) {
            error_log("StudentService ERROR: " . $e->getMessage());
            if ($e instanceof \PDOException) {
                error_log("PDO Error Info: " . json_encode($e->errorInfo));
            }
            if ($startedTransaction) {
                if ($this->db instanceof \PDO && $this->db->inTransaction()) {
                    $this->db->rollBack();
                } elseif (class_exists('Illuminate\Support\Facades\DB') && \Illuminate\Support\Facades\DB::getFacadeRoot()) {
                    \Illuminate\Support\Facades\DB::rollBack();
                }
            }
            throw $e;
        }
    }

    /**
     * Enroll a student into a batch and set up fees
     */
    public function enrollInBatch($studentId, $batchId, $tenantId) {
        if (!$batchId) return null;

        $enrollmentId = null;

        if ($this->db instanceof \PDO) {
            $stmt = $this->db->prepare("
                SELECT b.name as batch_name, b.shift as batch_shift, c.id as course_id, c.name as course_name, c.fee 
                FROM batches as b
                JOIN courses as c ON b.course_id = c.id
                WHERE b.id = ? AND b.tenant_id = ?
            ");
            $stmt->execute([$batchId, $tenantId]);
            $courseData = $stmt->fetch(\PDO::FETCH_OBJ);
        } else {
            $courseData = DB::table('batches as b')
                ->join('courses as c', 'b.course_id', '=', 'c.id')
                ->select('b.name as batch_name', 'b.shift as batch_shift', 'c.id as course_id', 'c.name as course_name', 'c.fee')
                ->where('b.id', $batchId)
                ->where('b.tenant_id', $tenantId)
                ->first();
        }

        if (!$courseData) return null;

        // Check if already enrolled in this batch to avoid duplicates
        if ($this->db instanceof \PDO) {
            $stmt = $this->db->prepare("SELECT id FROM enrollments WHERE student_id = ? AND batch_id = ? AND status = 'active' AND tenant_id = ?");
            $stmt->execute([$studentId, $batchId, $tenantId]);
            if ($stmt->fetch()) {
                throw new Exception("Student is already enrolled in " . ($courseData->batch_name ?? 'this batch') . ".");
            }
        } else {
            if (DB::table('enrollments')->where('student_id', $studentId)->where('batch_id', $batchId)->where('status', 'active')->where('tenant_id', $tenantId)->exists()) {
                throw new Exception("Student is already enrolled in " . ($courseData->batch_name ?? 'this batch') . ".");
            }
        }

        // Generate human-readable enrollment_id
        $enrollmentCode = 'ENR-' . $tenantId . '-' . date('Y') . '-' . str_pad($studentId, 5, '0', STR_PAD_LEFT) . '-' . mt_rand(10, 99);

        if ($this->db instanceof \PDO) {
            $stmt = $this->db->prepare("
                INSERT INTO enrollments (tenant_id, student_id, batch_id, enrollment_id, enrollment_date, status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$tenantId, $studentId, $batchId, $enrollmentCode, date('Y-m-d')]);
            $enrollmentId = $this->db->lastInsertId();
        } else {
            $enrollmentId = DB::table('enrollments')->insertGetId([
                'tenant_id'       => $tenantId,
                'student_id'      => $studentId,
                'batch_id'        => $batchId,
                'enrollment_id'   => $enrollmentCode,
                'enrollment_date' => date('Y-m-d'),
                'status'          => 'active'
            ]);
        }

        // 5b. Fee Summary
        $totalFee = (float)$courseData->fee;
        $feeStatus = ($totalFee > 0) ? 'unpaid' : 'no_fees';
        
        if ($this->db instanceof \PDO) {
            $stmt = $this->db->prepare("
                INSERT INTO student_fee_summary (tenant_id, student_id, enrollment_id, total_fee, paid_amount, due_amount, fee_status)
                VALUES (?, ?, ?, ?, 0, ?, ?)
            ");
            $stmt->execute([$tenantId, $studentId, $enrollmentId, $totalFee, $totalFee, $feeStatus]);
        } else {
            DB::table('student_fee_summary')->insert([
                'tenant_id' => $tenantId,
                'student_id' => $studentId,
                'enrollment_id' => $enrollmentId,
                'total_fee' => $totalFee,
                'paid_amount' => 0,
                'due_amount' => $totalFee,
                'fee_status' => $feeStatus
            ]);
        }

        // 5c. Detailed Fee Records
        $feeItems = [];
        if ($this->db instanceof \PDO) {
            $stmt = $this->db->prepare("
                SELECT id, name, amount, installments FROM fee_items 
                WHERE course_id = ? AND tenant_id = ? AND is_active = 1 AND deleted_at IS NULL
            ");
            $stmt->execute([$courseData->course_id, $tenantId]);
            $feeItems = $stmt->fetchAll(\PDO::FETCH_OBJ);
        } else {
            $feeItems = DB::table('fee_items')
                ->select('id', 'name', 'amount', 'installments')
                ->where('course_id', $courseData->course_id)
                ->where('tenant_id', $tenantId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->get();
        }

        if (count($feeItems) > 0) {
            $recordInserts = [];
            foreach ($feeItems as $item) {
                $instCount = max(1, (int)$item->installments);
                $instAmount = round($item->amount / $instCount, 2);
                
                for ($i = 1; $i <= $instCount; $i++) {
                    $dueDate = date('Y-m-d', strtotime("+" . ($i - 1) . " month"));
                    $recordInserts[] = [
                        'tenant_id' => $tenantId,
                        'student_id' => $studentId,
                        'batch_id' => $batchId,
                        'fee_item_id' => $item->id,
                        'installment_no' => $i,
                        'amount_due' => $instAmount,
                        'due_date' => $dueDate,
                        'status' => 'pending',
                        'academic_year' => date('Y') . '-' . (date('Y') + 1)
                    ];
                }
            }
            if (!empty($recordInserts)) {
                if ($this->db instanceof \PDO) {
                    $placeholders = implode(', ', array_fill(0, count($recordInserts), '(?, ?, ?, ?, ?, ?, ?, ?, ?)'));
                    $stmt = $this->db->prepare("
                        INSERT INTO fee_records (tenant_id, student_id, batch_id, fee_item_id, installment_no, amount_due, due_date, status, academic_year)
                        VALUES $placeholders
                    ");
                    $flat = [];
                    foreach($recordInserts as $r) {
                        $flat = array_merge($flat, array_values($r));
                    }
                    $stmt->execute($flat);
                } else {
                    DB::table('fee_records')->insert($recordInserts);
                }
            }
        } else if ($totalFee > 0) {
            if ($this->db instanceof \PDO) {
                $stmt = $this->db->prepare("SELECT id FROM fee_items WHERE tenant_id = ? AND name = 'Generic Course Fee' LIMIT 1");
                $stmt->execute([$tenantId]);
                $dummyItem = $stmt->fetch(\PDO::FETCH_OBJ);
                if (!is_object($dummyItem)) {
                    $stmt = $this->db->prepare("INSERT INTO fee_items (tenant_id, course_id, name, type, amount, installments, is_active) VALUES (?, ?, ?, 'other', 0, 1, 1)");
                    $stmt->execute([$tenantId, $courseData->course_id, 'Generic Course Fee']);
                    $dummyItemId = $this->db->lastInsertId();
                } else {
                    $dummyItemId = $dummyItem->id;
                }

                $stmt = $this->db->prepare("INSERT INTO fee_records (tenant_id, student_id, batch_id, fee_item_id, installment_no, amount_due, due_date, status, academic_year) VALUES (?, ?, ?, ?, 1, ?, CURDATE(), 'pending', ?)");
                $stmt->execute([$tenantId, $studentId, $batchId, $dummyItemId, $totalFee, date('Y') . '-' . (date('Y') + 1)]);
            } else {
                $dummyItem = DB::table('fee_items')->where('tenant_id', $tenantId)->where('name', 'Generic Course Fee')->first();
                if (!is_object($dummyItem)) {
                    $dummyItemId = DB::table('fee_items')->insertGetId([
                        'tenant_id' => $tenantId,
                        'course_id' => $courseData->course_id,
                        'name' => 'Generic Course Fee',
                        'type' => 'other',
                        'amount' => 0,
                        'installments' => 1,
                        'is_active' => 1
                    ]);
                } else {
                    $dummyItemId = $dummyItem->id;
                }
                DB::table('fee_records')->insert([
                    'tenant_id' => $tenantId,
                    'student_id' => $studentId,
                    'batch_id' => $batchId,
                    'fee_item_id' => $dummyItemId,
                    'installment_no' => 1,
                    'amount_due' => $totalFee,
                    'due_date' => DB::raw('CURDATE()'),
                    'status' => 'pending',
                    'academic_year' => date('Y') . '-' . (date('Y') + 1)
                ]);
            }
        }
        return $enrollmentId;
    }

    private function sendWelcomeEmail($tenantId, $studentId, $fullName, $email, $password, $courseData, $rollNo = 'N/A') {
        $payload = [
            'student_id'      => $studentId,
            'student_name'    => $fullName,
            'student_email'   => $email,
            'temp_password'   => $password,
            'course_name'     => $courseData['course_name'] ?? 'N/A',
            'batch_name'      => $courseData['batch_name'] ?? 'N/A',
            'batch_shift'     => ucfirst($courseData['batch_shift'] ?? 'N/A'),
            'roll_no'         => $rollNo,
            'admission_date'  => date('Y-m-d'),
            'login_url'       => (defined('APP_URL') ? APP_URL : 'http://localhost/erp') . '/login'
        ];

        return \App\Helpers\StudentEmailHelper::sendWelcomeEmail($this->db, $tenantId, $payload);
    }
}

