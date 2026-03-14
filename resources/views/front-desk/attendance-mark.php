<?php
/**
 * Front Desk — Attendance Marking
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Mark Attendance';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}

if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
}

// Shared Component Parameters
$apiEndpoint = APP_URL . '/api/frontdesk/attendance';
$componentId = 'fd_att';

include VIEWS_PATH . '/components/academic/attendance-sheet.php';

if (!isset($_GET['partial'])) {
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
