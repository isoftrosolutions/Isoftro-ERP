<?php
/**
 * Front Desk — Call Log
 * Track incoming and outgoing calls using the inquiries table
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Call Log';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
        renderFrontDeskHeader();
    }
    
    // Shared Component Parameters
    $apiEndpoint = APP_URL . '/api/frontdesk/call-logs';
    $componentId = 'fd_calls';

    include VIEWS_PATH . '/components/reception/call-log-manager.php';

    if (!isset($_GET['partial'])) {
        echo '<script src="' . APP_URL . '/assets/js/frontdesk.js"></script>';
        echo '</body></html>';
    }
?>

