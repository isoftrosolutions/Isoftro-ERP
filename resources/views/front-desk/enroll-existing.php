<?php
/**
 * Dedicated Page: Existing Student Enrollment (Front Desk)
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

// ── Auth Check ──
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];

// ── Data Fetching for Component ──
$stmtCourses = $db->prepare("SELECT id, name, code FROM courses WHERE tenant_id = :tid AND status = 'active' AND is_active = 1 AND deleted_at IS NULL ORDER BY name");
$stmtCourses->execute(['tid' => $tenantId]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

if (!isset($_GET['partial'])) {
    $pageTitle = 'Existing Student Enrollment';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    renderFrontDeskHeader();
    renderFrontDeskSidebar('admission-form');
}

// ── Component Parameters ──
$apiEndpoint        = APP_URL . '/api/frontdesk/students';
$successRedirectUrl = APP_URL . '/dash/front-desk/students';
$componentId        = 'fd_enroll';
$pageTitle          = 'Existing Student Enrollment';

require VIEWS_PATH . '/components/student/enroll-existing-form.php';

if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
