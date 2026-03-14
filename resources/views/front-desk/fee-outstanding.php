<?php
/**
 * Front Desk — Outstanding Fee Dues
 * Real table structure and data fetching for outstanding dues
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Outstanding Dues';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
        renderFrontDeskHeader();
    }
    
    // Shared Component Parameters
    $apiEndpoint = APP_URL . '/api/frontdesk/fee-reports';
    $componentId = 'fd_dues';

    include VIEWS_PATH . '/components/financial/outstanding-dues-manager.php';

    if (!isset($_GET['partial'])) {
        echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
        echo '</body></html>';
    }
?>

