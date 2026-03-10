<?php
/**
 * Student Admission Form — Front Desk Operator
 * Uses shared component: resources/views/components/student/add-student-form.php
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

// ── Auth: Front Desk, Institute Admin, or Super Admin ──
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$user = getCurrentUser();
if (!in_array($user['role'] ?? '', ['instituteadmin', 'superadmin', 'frontdesk'])) {
    http_response_code(403);
    echo '<p>Access Denied</p>';
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenantId) {
    echo '<p>Tenant not found</p>';
    exit;
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Student Admission';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}

// ── Data Fetching ──
$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];

// ISSUE-C2 FIX: Also filter by is_active=1 for consistency with API
$stmtCourses = $db->prepare("SELECT id, name, code FROM courses WHERE tenant_id = :tid AND status = 'active' AND is_active = 1 AND deleted_at IS NULL ORDER BY name");
$stmtCourses->execute(['tid' => $tenantId]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

$stmtBatches = $db->prepare("SELECT id, course_id, name, shift FROM batches WHERE tenant_id = :tid AND status IN ('active', 'upcoming') AND deleted_at IS NULL ORDER BY name");
$stmtBatches->execute(['tid' => $tenantId]);
$batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('admission-form');
}
?>

<?php
// ── Shared Component Parameters ──
$apiEndpoint        = APP_URL . '/api/frontdesk/students';
$successRedirectUrl = APP_URL . '/dash/front-desk/students';
$viewAllStudentsUrl  = APP_URL . '/dash/front-desk/students';
$componentId        = 'fd';
$pageTitle          = 'Student Admission';

require VIEWS_PATH . '/components/student/add-student-form.php';
?>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS(); // Load base styles
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
