<?php
/**
 * Hamro ERP — Front Desk Dashboard
 * Seamless Single Page Application (SPA) entry point
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'Front Desk Dashboard';
require_once VIEWS_PATH . '/layouts/header_1.php';
require_once __DIR__ . '/sidebar.php';

// Render base layout
renderFrontDeskHeader();
renderFrontDeskSidebar('index');
?>

<link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/frontdesk.css?v=1.1">

<!-- ── MAIN CONTENT Shell ── -->
<main class="main" id="mainContent">
    <div class="pg fu">
        <div class="pg-loading">
            <i class="fa-solid fa-circle-notch fa-spin"></i>
            <span>Initializing Operations...</span>
        </div>
    </div>
</main>

<script src="<?php echo APP_URL; ?>/public/assets/js/nepal-data.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/nexus-data-loader.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk.js?v=1.1"></script>
<style>
/* Dashboard Shell specific optimizations */
.pg-loading {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    height: 60vh; color: #94a3b8; gap: 16px;
}
.pg-loading i { font-size: 32px; color: var(--green); }
</style>
<?php
// Include necessary CSS/JS from layout
renderSuperAdminCSS();
?>
</body>
</html>
<?php exit; // End of shell ?>
