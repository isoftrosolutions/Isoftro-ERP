<?php
/**
 * Dedicated Page: Existing Student Enrollment (Admin)
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

// ── Auth Check ──
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$user = getCurrentUser();
if (!in_array($user['role'] ?? '', ['instituteadmin', 'superadmin'])) {
    http_response_code(403);
    echo '<p>Access Denied</p>';
    exit;
}

$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];

// ── Data Fetching for Component ──
$stmtCourses = $db->prepare("SELECT id, name, code FROM courses WHERE tenant_id = :tid AND status = 'active' AND is_active = 1 AND deleted_at IS NULL ORDER BY name");
$stmtCourses->execute(['tid' => $tenantId]);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

// ── Component Parameters ──
$apiEndpoint        = APP_URL . '/api/admin/students';
$successRedirectUrl = 'javascript:goNav(\'students\')';
$componentId        = 'adm_enroll';
$pageTitle          = 'Existing Student Enrollment';

require VIEWS_PATH . '/components/student/enroll-existing-form.php';
?>
