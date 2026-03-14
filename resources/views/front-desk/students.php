<?php
/**
 * Front Desk — All Students Listing
 * Shows all students with registration status badges
 * Allows completing profiles for quick-registered students
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'All Students';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
        renderFrontDeskHeader();
    }
    
    // Shared Component Parameters
    $apiEndpoint = APP_URL . '/api/frontdesk/students';
    $componentId = 'fd_stu';

    include VIEWS_PATH . '/components/student/student-registry.php';

    if (!isset($_GET['partial'])) {
        echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
        echo '</body></html>';
    }
?>

