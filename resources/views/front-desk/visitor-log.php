<?php
/**
 * Front Desk — Visitor Log
 * Real-time tracking of visitors using the inquiries table
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Visitor Log';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
        renderFrontDeskHeader();
    }
    
    // Shared Component Parameters
    $apiEndpoint = APP_URL . '/api/frontdesk/visitor-log';
    $componentId = 'fd_vis';

    include VIEWS_PATH . '/components/reception/visitor-manager.php';

    if (!isset($_GET['partial'])) {
        echo '<script src="' . APP_URL . '/assets/js/frontdesk.js"></script>';
        echo '</body></html>';
    }
?>

