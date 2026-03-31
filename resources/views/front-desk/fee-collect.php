<?php
/**
 * Front Desk — Fee Collection
 * Streamlined interface for recording student payments via SPA, aligned with Admin structure
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Collect Fee';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
$studentId = $_GET['student_id'] ?? null;
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('fee-coll');
}

// Include the Premium Payment Processing Modal Component
require_once VIEWS_PATH . '/components/payment-processing-modal.php';
?>

<!-- Content is dynamically rendered via SPA (fd-fees.js), matching the Admin's architecture -->
<div id="mainContent" style="width: 100%;">
    <div class="pg-loading" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 60vh; color: #94a3b8; gap: 16px;">
        <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 32px; color: var(--green);"></i>
        <span>Loading Fee Collection Module...</span>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Provide a slight delay for scripts to parse if loaded directly
        setTimeout(() => {
            if (typeof window.renderFeeCollect === 'function') {
                window.renderFeeCollect();
            } else if (typeof window.renderQuickPayment === 'function' && <?php echo $studentId ? 'true' : 'false'; ?>) {
                window.renderQuickPayment(<?php echo json_encode($studentId); ?>);
            } else {
                console.warn('SPA route fallback initiated for Fee Collection.');
            }
        }, 150);
    });
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS(); // Load base styles
    // Load SPA endpoints if visited directly
    echo '<script src="' . APP_URL . '/assets/js/frontdesk/fd-fees.js"></script>';
    echo '<script src="' . APP_URL . '/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
