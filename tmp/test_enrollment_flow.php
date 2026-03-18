<?php
/**
 * Test Student Enrollment & Fee Generation
 * Run this from the CLI: php tmp/test_enrollment_flow.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

$db = getDBConnection();

// Boot Eloquent Capsule for model support
use Illuminate\Database\Capsule\Manager as Capsule;
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => DB_HOST,
    'database'  => DB_NAME,
    'username'  => DB_USER,
    'password'  => DB_PASS,
    'charset'   => 'utf8mb4',
]);
// Force same PDO instance
$capsule->getDatabaseManager()->connection()->setPdo($db);
$capsule->getDatabaseManager()->connection()->setReadPdo($db);
$capsule->setAsGlobal();
$capsule->bootEloquent();

require_once __DIR__ . '/../app/Services/StudentService.php';
require_once __DIR__ . '/../app/Models/Student.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Helpers/DateUtils.php';
require_once __DIR__ . '/../app/Helpers/AuditLogger.php';
require_once __DIR__ . '/../app/Helpers/StudentEmailHelper.php';
require_once __DIR__ . '/../app/Services/QueueService.php';

use App\Services\StudentService;

$db = getDBConnection();
$tenantId = 3; // Using tenant ID from list_batches discovery

echo "Starting Enrollment Flow Test for Tenant {$tenantId}...\n";

try {
    $db->beginTransaction();

    $service = new StudentService();

    // 1. Mock enrollment data
    $enrollData = [
        'full_name' => 'Automated Test Student ' . time(),
        'email' => 'test_std_' . time() . '@example.com',
        'phone' => '98' . rand(0, 9) . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT),
        'batch_id' => 1, // Batch ID from discovery
        'admission_date' => date('Y-m-d'),
        'gender' => 'male',
        'dob_bs' => '2060-01-01'
    ];

    echo "Registering student: {$enrollData['full_name']}...\n";

    // 2. Call StudentService::registerStudent
    $result = $service->registerStudent($enrollData, $tenantId);

    if ($result && isset($result['student']['id'])) {
        $studentId = $result['student']['id'];
        echo "PASSED: Student registered with ID: {$studentId}\n";
        
        // 3. Verify Enrollment
        $stmt = $db->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND batch_id = ?");
        $stmt->execute([$studentId, $enrollData['batch_id']]);
        if ($stmt->fetchColumn() > 0) {
            echo "PASSED: Enrollment record created\n";
        } else {
            throw new Exception("Enrollment record NOT found");
        }

        // 4. Verify Fees
        $stmt = $db->prepare("SELECT COUNT(*) FROM student_fee_summary WHERE student_id = ?");
        $stmt->execute([$studentId]);
        if ($stmt->fetchColumn() > 0) {
            echo "PASSED: Fee summary created\n";
        } else {
            echo "WARNING: Fee summary NOT found (check if item fees exist for this course)\n";
        }
    } else {
        throw new Exception("Registration failed to return student ID");
    }

    $db->rollBack();
    echo "Test Finished (Rolled back changes for cleanup)\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "FAILED: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
