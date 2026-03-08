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
        if ($this->db instanceof \PDO) {
            $this->db->beginTransaction();
        } elseif (class_exists('Illuminate\Support\Facades\DB')) {
            \Illuminate\Support\Facades\DB::beginTransaction();
        }

        try {
            // 1. Prepare User Data
            $fullName = $input['full_name'];
            $email = $input['email'] ?? null;
            $phone = $input['contact_number'] ?? $input['phone'] ?? null;
            $password = $input['password'] ?? 'Student@123'; 

            if (empty($email)) {
                throw new Exception("Email address is required for student registration.");
            }

            // 2. Create/Reuse User Account
            $existingUser = null;
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

            if ($existingUser) {
                $userId = $existingUser->id;
            } else {
                $user = $this->userModel->create([
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
            $student = $this->studentModel->create($studentData);
            $studentId = $student['id'];

            // 5. Handle Course Enrollment & Fees
            $batchId = $input['batch_id'] ?? null;
            $enrollmentId = null;

            if ($batchId) {
                if ($this->db instanceof \PDO) {
                    $stmt = $this->db->prepare("
                        SELECT b.name as batch_name, b.shift as batch_shift, c.id as course_id, c.name as course_name, c.fee 
                        FROM batches as b
                        JOIN courses as c ON b.course_id = c.id
                        WHERE b.id = ? AND b.tenant_id = ?
                    ");
                    $stmt->execute([$batchId, $tenantId]);
                    $courseData = $stmt->fetch(\PDO::FETCH_OBJ);
                } elseif (class_exists('Illuminate\Support\Facades\DB')) {
                    $courseData = \Illuminate\Support\Facades\DB::table('batches as b')
                        ->join('courses as c', 'b.course_id', '=', 'c.id')
                        ->select('b.name as batch_name', 'b.shift as batch_shift', 'c.id as course_id', 'c.name as course_name', 'c.fee')
                        ->where('b.id', $batchId)
                        ->where('b.tenant_id', $tenantId)
                        ->first();
                }

                if ($courseData) {
                    // ISSUE-V1 FIX: Generate human-readable enrollment_id
                    $enrollmentCode = 'ENR-' . $tenantId . '-' . date('Y') . '-' . str_pad($studentId, 5, '0', STR_PAD_LEFT);

                    if ($this->db instanceof \PDO) {
                        $stmt = $this->db->prepare("
                            INSERT INTO enrollments (tenant_id, student_id, batch_id, enrollment_id, enrollment_date, status)
                            VALUES (?, ?, ?, ?, ?, 'active')
                        ");
                        $stmt->execute([$tenantId, $studentId, $batchId, $enrollmentCode, date('Y-m-d')]);
                        $enrollmentId = $this->db->lastInsertId();
                    } elseif (class_exists('Illuminate\Support\Facades\DB')) {
                        $enrollmentId = \Illuminate\Support\Facades\DB::table('enrollments')->insertGetId([
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
                    } elseif (class_exists('Illuminate\Support\Facades\DB')) {
                        \Illuminate\Support\Facades\DB::table('student_fee_summary')->insert([
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
                    } elseif (class_exists('Illuminate\Support\Facades\DB')) {
                        $feeItems = \Illuminate\Support\Facades\DB::table('fee_items')
                            ->select('id', 'name', 'amount', 'installments')
                            ->where('course_id', $courseData->course_id)
                            ->where('tenant_id', $tenantId)
                            ->where('is_active', 1)
                            ->whereNull('deleted_at')
                            ->get();
                    }

                    if (count($feeItems) > 0) {
                        foreach ($feeItems as $item) {
                            $instCount = max(1, (int)$item->installments);
                            $instAmount = round($item->amount / $instCount, 2);
                            
                            $recordInsterts = [];
                            for ($i = 1; $i <= $instCount; $i++) {
                                $dueDate = date('Y-m-d', strtotime("+" . ($i - 1) . " month"));
                                $recordInsterts[] = [
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
                            if ($this->db instanceof \PDO) {
                                $stmt = $this->db->prepare("
                                    INSERT INTO fee_records (tenant_id, student_id, batch_id, fee_item_id, installment_no, amount_due, due_date, status, academic_year)
                                    VALUES " . implode(', ', array_fill(0, count($recordInsterts), '(?, ?, ?, ?, ?, ?, ?, ?, ?)'))
                                );
                                $flat = [];
                                foreach($recordInsterts as $r) {
                                    $flat = array_merge($flat, array_values($r));
                                }
                                $stmt->execute($flat);
                            } elseif (class_exists('Illuminate\Support\Facades\DB')) {
                                \Illuminate\Support\Facades\DB::table('fee_records')->insert($recordInsterts);
                            }
                        }
                    } else if ($totalFee > 0) {
                        // Fallback: Total fee is > 0 but no fee items exist. Create a dummy base fee item and record.
                        $dummyItem = DB::table('fee_items')
                            ->select('id')
                            ->where('tenant_id', $tenantId)
                            ->where('name', 'Generic Course Fee')
                            ->first();
                            
                        $dummyItemId = null;
                        if ($dummyItem) {
                            $dummyItemId = $dummyItem->id;
                        } else {
                            $dummyItemId = DB::table('fee_items')->insertGetId([
                                'tenant_id' => $tenantId,
                                'course_id' => $courseData->course_id,
                                'name' => 'Generic Course Fee',
                                'type' => 'other',
                                'amount' => 0,
                                'installments' => 1,
                                'is_active' => 1
                            ]);
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
            }

            if ($this->db instanceof \PDO) {
                $this->db->commit();
            } elseif (class_exists('Illuminate\Support\Facades\DB')) {
                \Illuminate\Support\Facades\DB::commit();
            }

            // Post-commit: Audit log (safe to read from DB now)
            if (class_exists('\App\Helpers\AuditLogger')) {
                $auditStudent = $this->studentModel->find($studentId);
                \App\Helpers\AuditLogger::log('CREATE', 'students', $studentId, null, $auditStudent);
            }

            // 6. Post-Registration: Send Welcome Email
            if (!empty($email) && !empty($password)) {
                 $this->sendWelcomeEmail($tenantId, $studentId, $fullName, $email, $password, isset($courseData) ? (array) $courseData : null, $student['roll_no'] ?? 'N/A');
            }

            return [
                'student' => $student,
                'enrollment_id' => $enrollmentId
            ];

        } catch (Exception $e) {
            error_log("StudentService ERROR: " . $e->getMessage());
            if ($e instanceof \PDOException) {
                error_log("PDO Error Info: " . json_encode($e->errorInfo));
            }
            if ($this->db instanceof \PDO && $this->db->inTransaction()) {
                $this->db->rollBack();
            } elseif (class_exists('Illuminate\Support\Facades\DB')) {
                \Illuminate\Support\Facades\DB::rollBack();
            }
            throw $e;
        }
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

