<?php
/**
 * StudentService
 * Handles complex business logic for students
 */

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\Helpers\MailHelper;
use Exception;
use Illuminate\Support\Facades\DB;

class StudentService {
    private $studentModel;
    private $userModel;

    public function __construct() {
        $this->studentModel = new Student();
        $this->userModel = new User();
    }

    /**
     * Register a new student with transaction
     */
    public function registerStudent($input, $tenantId) {
        DB::beginTransaction();

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
            if(!empty($email)) {
                $existingUser = DB::table('users')
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
                    $studentData['dob_ad'] = \App\Helpers\DateUtils::bsToAd($studentData['dob_bs']);
                } catch (\Throwable $e) {}
            } elseif (!empty($studentData['dob_ad']) && empty($studentData['dob_bs'])) {
                try {
                    $studentData['dob_bs'] = \App\Helpers\DateUtils::adToBs($studentData['dob_ad']);
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
                // Fetch course info
                $courseData = DB::table('batches as b')
                    ->join('courses as c', 'b.course_id', '=', 'c.id')
                    ->select('b.name as batch_name', 'c.id as course_id', 'c.name as course_name', 'c.fee')
                    ->where('b.id', $batchId)
                    ->where('b.tenant_id', $tenantId)
                    ->first();

                if ($courseData) {
                    // ISSUE-B3 FIX: Check batch capacity before enrolling
                    $capacityRow = DB::selectOne(
                        "SELECT b.max_strength,
                                COUNT(e.id) AS enrolled
                         FROM   batches b
                         LEFT JOIN enrollments e ON e.batch_id = b.id AND e.status = 'active'
                         WHERE  b.id = ?
                         GROUP BY b.id",
                        [$batchId]
                    );
                    if ($capacityRow && (int)$capacityRow->enrolled >= (int)$capacityRow->max_strength) {
                        throw new Exception(
                            "This batch is full (maximum {$capacityRow->max_strength} students). " .
                            "Please select a different batch or contact admin to increase capacity."
                        );
                    }

                    // ISSUE-V1 FIX: Generate human-readable enrollment_id
                    $enrollmentCode = 'ENR-' . $tenantId . '-' . date('Y') . '-' . str_pad($studentId, 5, '0', STR_PAD_LEFT);

                    // 5a. Enrollment
                    $enrollmentId = DB::table('enrollments')->insertGetId([
                        'tenant_id'       => $tenantId,
                        'student_id'      => $studentId,
                        'batch_id'        => $batchId,
                        'enrollment_id'   => $enrollmentCode,  // ISSUE-V1 FIX
                        'enrollment_date' => date('Y-m-d'),
                        'status'          => 'active'
                    ]);

                    // 5b. Fee Summary
                    $totalFee = (float)$courseData->fee;
                    $feeStatus = ($totalFee > 0) ? 'unpaid' : 'no_fees';
                    
                    DB::table('student_fee_summary')->insert([
                        'tenant_id' => $tenantId,
                        'student_id' => $studentId,
                        'enrollment_id' => $enrollmentId,
                        'total_fee' => $totalFee,
                        'paid_amount' => 0,
                        'due_amount' => $totalFee,
                        'fee_status' => $feeStatus
                    ]);

                    // 5c. Detailed Fee Records
                    $feeItems = DB::table('fee_items')
                        ->select('id', 'name', 'amount', 'installments')
                        ->where('course_id', $courseData->course_id)
                        ->where('tenant_id', $tenantId)
                        ->where('is_active', 1)
                        ->whereNull('deleted_at')
                        ->get();

                    if ($feeItems->count() > 0) {
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
                            DB::table('fee_records')->insert($recordInsterts);
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

            DB::commit();

            // 6. Post-Registration: Send Welcome Email
            if (!empty($email) && !empty($password)) {
                 $this->sendWelcomeEmail($tenantId, $studentId, $fullName, $email, $password, isset($courseData) ? (array) $courseData : null);
            }

            return [
                'student' => $student,
                'enrollment_id' => $enrollmentId
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function sendWelcomeEmail($tenantId, $studentId, $fullName, $email, $password, $courseData) {
        $emailSent = MailHelper::sendStudentCredentials(DB::connection()->getPdo(), $tenantId, [
            'full_name'      => $fullName,
            'email'          => $email,
            'plain_password' => $password,
            'course_name'    => $courseData['course_name'] ?? 'N/A',
            'batch_name'     => $courseData['batch_name'] ?? 'N/A'
        ]);

        DB::table('email_logs')->insert([
            'tenant_id' => $tenantId,
            'student_id' => $studentId,
            'email' => $email,
            'subject' => 'Welcome Credentials',
            'status' => $emailSent ? 'sent' : 'failed',
            'error_message' => $emailSent ? null : 'Failed to send welcome email'
        ]);
    }
}
