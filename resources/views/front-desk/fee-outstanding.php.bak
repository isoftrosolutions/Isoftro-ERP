<?php
/**
 * Front Desk — Outstanding Fee Dues
 * Shell for the JS-driven outstanding dues module
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'Outstanding Dues';
require_once VIEWS_PATH . '/layouts/header_1.php';
require_once __DIR__ . '/sidebar.php';
?>

<?php renderFrontDeskHeader(); ?>
<?php renderFrontDeskSidebar('fees'); ?>

<main class="main" id="mainContent">
    <div class="pg-loading" style="padding: 100px; text-align: center; color: #94a3b8;">
        <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 32px; margin-bottom: 15px;"></i>
        <p>Initializing Outstanding Dues Module...</p>
    </div>
</main>

<script>
window.addEventListener('load', () => {
    // Force active nav and render
    if (window.goNav) {
        window.goNav('fee', 'fee-out');
    }
});
</script>

<?php
renderSuperAdminCSS();
echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
?>
</body>
</html>
