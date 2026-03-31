<?php
/**
 * Front Desk — Inquiry Management
 * Modern, elegant listing of all student inquiries
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Inquiry Management';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
        renderFrontDeskHeader();
    }
    
    // Shared Component Parameters
    $apiEndpoint = APP_URL . '/api/frontdesk/inquiries';
    $componentId = 'fd_inq';
    $canAddInquiry = true;

    include VIEWS_PATH . '/components/inquiry/inquiry-manager.php';

    if (!isset($_GET['partial'])) {
        echo '<script src="' . APP_URL . '/assets/js/frontdesk.js"></script>';
        echo '</body></html>';
    }
?>

