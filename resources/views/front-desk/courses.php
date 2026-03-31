<?php
/**
 * Front Desk — Courses View
 * Brochure-style view for front desk to explain course offerings
 */

if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar();
}
if (!isset($_GET['partial'])) {
        renderFrontDeskHeader();
    }
    
    include VIEWS_PATH . '/components/academic/course-brochure.php';

    if (!isset($_GET['partial'])) {
        echo '<script src="' . APP_URL . '/assets/js/frontdesk.js"></script>';
        echo '</body></html>';
    }
?>
